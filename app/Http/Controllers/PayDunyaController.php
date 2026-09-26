<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\OrderService;
use App\Services\PayDunyaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayDunyaController extends Controller
{
    public function __construct(
        private readonly PayDunyaService $payDunya,
        private readonly OrderService $orders,
    ) {
    }

    /**
     * PayDunya redirige le navigateur ici après un paiement (return_url). Le token qu'ils
     * ajoutent à l'URL permet de déclencher tout de suite une vérification active — en plus du
     * callback serveur à serveur, jamais à sa place : cette page ne confirme rien par
     * elle-même, elle affiche l'état réellement en base après avoir tenté cette vérification.
     */
    public function success(Request $request, ?Order $order = null): RedirectResponse
    {
        $order = $order ?? $this->resolveFromRef($request->query('ref'));

        abort_unless($order, 404);

        $token = $request->query('token');
        if ($token) {
            $payment = Payment::where('transaction_id', 'like', $order->order_number.'-%')
                ->where('gateway', 'paydunya')
                ->where('status', 'processing')
                ->latest()
                ->first();

            if ($payment) {
                $this->confirmAndProcess($payment, $token);
            }
        }

        return redirect()->route('checkout.confirmation', $order);
    }

    /**
     * PayDunya redirige ici si le client annule sur sa page de paiement.
     */
    public function cancel(Request $request, ?Order $order = null): RedirectResponse
    {
        $order = $order ?? $this->resolveFromRef($request->query('ref'));

        abort_unless($order, 404);

        $order->payments()
            ->where('gateway', 'paydunya')
            ->where('status', 'processing')
            ->latest()
            ->first()
            ?->update(['status' => 'cancelled']);

        PaymentEvent::record($order, 'payment_cancelled');
        $this->orders->notifyPaymentCancelled($order);

        return redirect()->route('checkout.confirmation', $order)
            ->withErrors(['payment_method' => 'Paiement annulé. Vous pouvez réessayer ci-dessous.']);
    }

    /**
     * IPN PayDunya (callback_url). Leur documentation publique ne décrit aucune signature pour
     * cette notification — jamais fiable telle quelle. La tentative est retrouvée via
     * custom_data.payment_id (qu'on a fourni nous-mêmes à la création), puis son statut RÉEL est
     * obtenu par une vérification active auprès de PayDunya (Checkout::confirm() de leur propre
     * SDK officiel) avant de considérer quoi que ce soit comme payé.
     */
    public function callback(Request $request): JsonResponse
    {
        $payment = $this->resolvePaymentFromCallback($request);

        if (! $payment) {
            report(new RuntimeException('PayDunya callback : tentative introuvable dans les données reçues.'));

            return response()->json(['message' => 'unknown payment'], 404);
        }

        if (in_array($payment->status, ['success', 'cancelled', 'refunded'], true)) {
            return response()->json(['message' => 'already processed']);
        }

        $stored = json_decode($payment->raw_response ?? '', true) ?? [];
        $token = $stored['token'] ?? null;

        if (! $token) {
            report(new RuntimeException('PayDunya callback : jeton introuvable pour la tentative '.$payment->transaction_id));

            return response()->json(['message' => 'missing token'], 422);
        }

        $result = $this->confirmAndProcess($payment, $token);

        return response()->json(['message' => $result ? 'ok' : 'not yet complete']);
    }

    /**
     * Vérification active + traitement — utilisée à la fois par le retour navigateur (success)
     * et par le callback serveur à serveur, pour ne jamais dupliquer cette logique entre les
     * deux points d'entrée.
     */
    private function confirmAndProcess(Payment $payment, string $token): bool
    {
        $status = $this->payDunya->confirmStatus($token);

        if (! $status) {
            return false;
        }

        if ($status['status'] !== 'completed') {
            if (in_array($status['status'], ['cancelled', 'declined', 'failed'], true)) {
                $payment->update(['status' => 'cancelled', 'raw_response' => json_encode($status['raw'])]);

                if ($status['status'] === 'cancelled') {
                    PaymentEvent::record($payment->order, 'payment_cancelled', ['payment_id' => $payment->id]);
                    $this->orders->notifyPaymentCancelled($payment->order);
                } else {
                    PaymentEvent::record($payment->order, 'payment_failed', ['payment_id' => $payment->id]);
                    $this->orders->notifyPaymentFailed($payment->order);
                }
            }

            return false;
        }

        if ($status['total_amount'] < (int) $payment->amount) {
            report(new RuntimeException(sprintf(
                'PayDunya : montant confirmé (%d) inférieur au montant attendu (%d) pour %s.',
                $status['total_amount'],
                $payment->amount,
                $payment->transaction_id,
            )));

            return false;
        }

        DB::transaction(function () use ($payment, $status) {
            // Verrou + re-vérification à l'intérieur de la transaction : le retour navigateur et
            // le callback serveur peuvent arriver en même temps, jamais un double traitement.
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'success') {
                return;
            }

            $order = $locked->order()->lockForUpdate()->first();

            $locked->update([
                'status' => 'success',
                'raw_response' => json_encode($status['raw']),
                'paid_at' => now(),
            ]);

            PaymentEvent::record($order, 'payment_success', ['payment_id' => $locked->id, 'reference' => $locked->transaction_id]);

            if ($order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid']);
                $this->orders->confirm($order);
                $this->orders->notifyPaymentReceived($order, $locked);
            }
        });

        return true;
    }

    /**
     * Retrouve la tentative visée par une notification callback via custom_data.payment_id —
     * qu'on a fourni nous-mêmes à la création de la facture, donc toujours présent quelle que
     * soit la forme exacte (non documentée publiquement) du corps de la notification.
     */
    private function resolvePaymentFromCallback(Request $request): ?Payment
    {
        $paymentId = $request->input('custom_data.payment_id') ?? $request->input('payment_id');

        if (! $paymentId) {
            $raw = $request->input('data');
            if ($raw) {
                $decoded = json_decode($raw, true) ?? json_decode(urldecode((string) $raw), true);
                $paymentId = data_get($decoded, 'custom_data.payment_id');
            }
        }

        if (! $paymentId) {
            return null;
        }

        return Payment::where('id', $paymentId)->where('gateway', 'paydunya')->first();
    }

    private function resolveFromRef(?string $ref): ?Order
    {
        if (! $ref) {
            return null;
        }

        return Order::where('order_number', $ref)->first();
    }
}

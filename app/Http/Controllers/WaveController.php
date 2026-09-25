<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\WaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WaveController extends Controller
{
    public function __construct(
        private readonly WaveService $wave,
        private readonly OrderService $orders,
    ) {
    }

    /**
     * Wave redirige le navigateur ici après un paiement réussi. Ne confirme jamais rien par
     * elle-même — le webhook (serveur à serveur, signature vérifiée) est la seule source de
     * vérité ; cette page affiche seulement l'état déjà en base à cet instant.
     */
    public function success(Request $request, ?Order $order = null): RedirectResponse
    {
        $order = $order ?? $this->resolveFromRef($request->query('ref'));

        abort_unless($order, 404);

        return redirect()->route('checkout.confirmation', $order);
    }

    /**
     * Wave redirige ici en cas d'échec/annulation du paiement (`error_url`). Le stock n'a
     * jamais été décrémenté à ce stade — la tentative est simplement marquée "cancelled".
     */
    public function cancel(Request $request, ?Order $order = null): RedirectResponse
    {
        $order = $order ?? $this->resolveFromRef($request->query('ref'));

        abort_unless($order, 404);

        $order->payments()
            ->where('gateway', 'wave')
            ->where('status', 'processing')
            ->latest()
            ->first()
            ?->update(['status' => 'cancelled']);

        return redirect()->route('checkout.confirmation', $order)
            ->withErrors(['payment_method' => 'Paiement Wave annulé ou échoué. Vous pouvez réessayer ci-dessous.']);
    }

    /**
     * Webhook Wave (checkout.session.completed / checkout.session.payment_failed). La signature
     * DOIT être vérifiée sur le corps BRUT de la requête (voir WaveService::verifyWebhookSignature)
     * — Wave le souligne explicitement : un framework qui re-sérialise le JSON avant vérification
     * invalide systématiquement la signature.
     */
    public function webhook(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('Wave-Signature');

        if (! $this->wave->verifyWebhookSignature($rawBody, $signature)) {
            report(new RuntimeException('Wave webhook : signature invalide ou absente.'));

            return response()->json(['message' => 'invalid signature'], 401);
        }

        $event = json_decode($rawBody, true) ?? [];
        $type = $event['type'] ?? null;
        $data = $event['data'] ?? [];

        // L'identifiant de session Wave (data.id) sert de référence — c'est celui qu'on a
        // enregistré comme transaction_id après la création réussie de la tentative.
        $payment = Payment::where('transaction_id', $data['id'] ?? null)
            ->where('gateway', 'wave')
            ->first();

        if (! $payment) {
            report(new RuntimeException('Wave webhook : session inconnue '.($data['id'] ?? '?')));

            return response()->json(['message' => 'unknown session'], 404);
        }

        if (in_array($payment->status, ['success', 'cancelled', 'refunded'], true)) {
            return response()->json(['message' => 'already processed']);
        }

        if ($type === 'checkout.session.payment_failed') {
            $payment->update(['status' => 'cancelled', 'raw_response' => $rawBody]);

            return response()->json(['message' => 'ok']);
        }

        if ($type !== 'checkout.session.completed') {
            return response()->json(['message' => 'ignored']);
        }

        $amountPaid = (int) ($data['amount'] ?? 0);

        if ($amountPaid < (int) $payment->amount) {
            report(new RuntimeException(sprintf(
                'Wave webhook : montant reçu (%d) inférieur au montant attendu (%d) pour %s.',
                $amountPaid,
                $payment->amount,
                $payment->transaction_id,
            )));

            return response()->json(['message' => 'amount mismatch'], 422);
        }

        DB::transaction(function () use ($payment, $rawBody, $amountPaid) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'success') {
                return;
            }

            $order = $locked->order()->lockForUpdate()->first();

            $locked->update([
                'status' => 'success',
                'amount' => $amountPaid,
                'raw_response' => $rawBody,
                'paid_at' => now(),
            ]);

            if ($order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid']);
                $this->orders->confirm($order);
                $this->orders->notifyPlaced($order);
            }
        });

        return response()->json(['message' => 'ok']);
    }

    private function resolveFromRef(?string $ref): ?Order
    {
        if (! $ref) {
            return null;
        }

        return Order::where('order_number', $ref)->first();
    }
}

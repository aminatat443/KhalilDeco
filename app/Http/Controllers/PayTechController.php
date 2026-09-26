<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\OrderService;
use App\Services\PayTechService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayTechController extends Controller
{
    public function __construct(
        private readonly PayTechService $payTech,
        private readonly OrderService $orders,
    ) {
    }

    /**
     * PayTech redirige le navigateur ici après un paiement réussi. Ne confirme jamais rien par
     * elle-même : la notification IPN (serveur à serveur, vérifiée par signature) est la seule
     * source de vérité sur le paiement — cette page ne fait qu'afficher l'état déjà en base à
     * cet instant, "en cours de vérification" si l'IPN n'est pas encore arrivée.
     */
    public function success(Request $request, ?Order $order = null): RedirectResponse
    {
        $order = $order ?? $this->resolveFromRef($request->query('ref'));

        abort_unless($order, 404);

        return redirect()->route('checkout.confirmation', $order);
    }

    /**
     * PayTech redirige ici si le client annule sur sa page de paiement. Le stock n'a jamais été
     * décrémenté à ce stade (seule la confirmation IPN le fait), donc rien à restaurer — la
     * tentative de paiement en cours est simplement marquée "cancelled" pour l'historique.
     */
    public function cancel(Request $request, ?Order $order = null): RedirectResponse
    {
        $order = $order ?? $this->resolveFromRef($request->query('ref'));

        abort_unless($order, 404);

        $order->payments()
            ->where('status', 'processing')
            ->latest()
            ->first()
            ?->update(['status' => 'cancelled']);

        PaymentEvent::record($order, 'payment_cancelled');
        $this->orders->notifyPaymentCancelled($order);

        return redirect()->route('checkout.confirmation', $order)
            ->withErrors(['payment_method' => 'Paiement annulé. Vous pouvez réessayer ci-dessous, ou choisir le paiement à la livraison pour une prochaine commande.']);
    }

    /**
     * Notification serveur à serveur de PayTech (paiement réussi/annulé/remboursé). Toujours
     * répondre 200 une fois la signature vérifiée — PayTech réessaie sinon — même quand la
     * notification est ignorée (tentative déjà traitée, événement non pertinent).
     */
    public function ipn(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->payTech->verifyIpn($payload)) {
            report(new RuntimeException('PayTech IPN : signature invalide ou absente.'));

            return response()->json(['message' => 'invalid signature'], 401);
        }

        // La référence identifie la TENTATIVE (une ligne `payments`), pas directement la
        // commande — une commande peut avoir plusieurs tentatives après un échec/annulation.
        $payment = Payment::where('transaction_id', $payload['ref_command'] ?? null)->first();

        if (! $payment) {
            report(new RuntimeException('PayTech IPN : référence inconnue '.($payload['ref_command'] ?? '?')));

            return response()->json(['message' => 'unknown reference'], 404);
        }

        // Idempotence : PayTech peut renvoyer la même notification plusieurs fois — un statut
        // déjà final (succès, annulé, remboursé) ne doit jamais être retraité.
        if (in_array($payment->status, ['success', 'cancelled', 'refunded'], true)) {
            return response()->json(['message' => 'already processed']);
        }

        $typeEvent = $payload['type_event'] ?? null;

        if ($typeEvent === 'sale_canceled') {
            $payment->update(['status' => 'cancelled', 'raw_response' => json_encode($payload)]);
            PaymentEvent::record($payment->order, 'payment_cancelled', ['payment_id' => $payment->id]);
            $this->orders->notifyPaymentCancelled($payment->order);

            return response()->json(['message' => 'ok']);
        }

        if ($typeEvent !== 'sale_complete') {
            return response()->json(['message' => 'ignored']);
        }

        $amountPaid = (int) ($payload['final_item_price'] ?? $payload['item_price'] ?? 0);

        // Le seuil attendu est le montant réellement ENVOYÉ à PayTech (item_price), pas le total
        // complet de la commande — quand la compensation de frais est active
        // (services.paytech.fee_rate), les deux diffèrent délibérément (voir
        // PayTechService::createPayment) et comparer au total complet rejetterait à tort des
        // paiements pourtant légitimes.
        $expectedMinimum = $this->payTech->minimumAcceptableAmount((int) $payment->amount);

        if ($amountPaid < $expectedMinimum) {
            report(new RuntimeException(sprintf(
                'PayTech IPN : montant reçu (%d) inférieur au montant attendu (%d) pour %s.',
                $amountPaid,
                $expectedMinimum,
                $payment->transaction_id,
            )));

            return response()->json(['message' => 'amount mismatch'], 422);
        }

        DB::transaction(function () use ($payment, $payload) {
            // Verrou + re-vérification à l'intérieur de la transaction : deux notifications
            // reçues en parallèle ne doivent jamais faire confirmer/décrémenter deux fois.
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'success') {
                return;
            }

            $order = $locked->order()->lockForUpdate()->first();

            // `amount` garde le vrai total de la commande fixé à la création (comptabilité) —
            // jamais écrasé par la valeur brute de la notification, qui peut différer de ce
            // total réel quand la compensation de frais est active. Le détail complet de la
            // notification reste consultable dans raw_response.
            $locked->update([
                'status' => 'success',
                'raw_response' => json_encode($payload),
                'paid_at' => now(),
            ]);

            PaymentEvent::record($order, 'payment_success', ['payment_id' => $locked->id, 'reference' => $locked->transaction_id]);

            if ($order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid']);
                $this->orders->confirm($order);
                $this->orders->notifyPaymentReceived($order, $locked);
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

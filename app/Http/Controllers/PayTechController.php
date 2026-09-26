<?php

namespace App\Http\Controllers;

use App\Models\PaymentAttempt;
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
     * PayTech redirige le navigateur ici après un paiement réussi. La commande n'existe pas
     * forcément encore à cet instant (elle n'est créée qu'à l'IPN, voir ipn() ci-dessous, qui peut
     * arriver après ce retour navigateur) — direction la page d'attente le temps qu'elle arrive,
     * sauf si l'IPN a déjà fait son travail entre-temps.
     */
    public function success(Request $request, ?PaymentAttempt $attempt = null): RedirectResponse
    {
        $attempt = $attempt ?? $this->resolveFromRef($request->query('ref'));

        abort_unless($attempt, 404);

        if ($attempt->status === 'success' && $attempt->order_id) {
            return redirect()->route('checkout.confirmation', $attempt->order_id);
        }

        return redirect()->route('checkout.attempt.show', $attempt);
    }

    /**
     * PayTech redirige ici si le client annule sur sa page de paiement. Aucune commande n'a
     * jamais existé pour cette tentative — rien à restaurer, le panier est toujours intact.
     */
    public function cancel(Request $request, ?PaymentAttempt $attempt = null): RedirectResponse
    {
        $attempt = $attempt ?? $this->resolveFromRef($request->query('ref'));

        abort_unless($attempt, 404);

        if (in_array($attempt->status, ['pending', 'processing'], true)) {
            $attempt->update(['status' => 'cancelled']);
        }

        return redirect()->route('checkout.index')
            ->withErrors(['payment_method' => 'Paiement annulé. Votre panier est toujours disponible — vous pouvez réessayer ou choisir le paiement à la livraison.']);
    }

    /**
     * Notification serveur à serveur de PayTech (paiement réussi/annulé). Toujours répondre 200
     * une fois la signature vérifiée — PayTech réessaie sinon — même quand la notification est
     * ignorée (tentative déjà traitée, événement non pertinent).
     */
    public function ipn(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->payTech->verifyIpn($payload)) {
            report(new RuntimeException('PayTech IPN : signature invalide ou absente.'));

            return response()->json(['message' => 'invalid signature'], 401);
        }

        $attempt = PaymentAttempt::where('reference', $payload['ref_command'] ?? null)->first();

        if (! $attempt) {
            report(new RuntimeException('PayTech IPN : référence inconnue '.($payload['ref_command'] ?? '?')));

            return response()->json(['message' => 'unknown reference'], 404);
        }

        // Idempotence : PayTech peut renvoyer la même notification plusieurs fois — une tentative
        // déjà finalisée (succès ou annulée) ne doit jamais être retraitée.
        if (in_array($attempt->status, ['success', 'cancelled'], true)) {
            return response()->json(['message' => 'already processed']);
        }

        $typeEvent = $payload['type_event'] ?? null;

        if ($typeEvent === 'sale_canceled') {
            // Trace minimale seulement — aucune commande n'a jamais existé pour cette tentative,
            // donc aucune notification équipe à envoyer ici (rien à annoncer).
            $attempt->update(['status' => 'cancelled', 'raw_response' => json_encode($payload)]);

            return response()->json(['message' => 'ok']);
        }

        if ($typeEvent !== 'sale_complete') {
            return response()->json(['message' => 'ignored']);
        }

        $amountPaid = (int) ($payload['final_item_price'] ?? $payload['item_price'] ?? 0);

        // Le seuil attendu est le montant réellement ENVOYÉ à PayTech (item_price), pas le
        // montant complet de la tentative — quand la compensation de frais est active
        // (services.paytech.fee_rate), les deux diffèrent délibérément (voir
        // PayTechService::createPayment) et comparer au montant complet rejetterait à tort des
        // paiements pourtant légitimes.
        $expectedMinimum = $this->payTech->minimumAcceptableAmount((int) $attempt->amount);

        if ($amountPaid < $expectedMinimum) {
            report(new RuntimeException(sprintf(
                'PayTech IPN : montant reçu (%d) inférieur au montant attendu (%d) pour %s.',
                $amountPaid,
                $expectedMinimum,
                $attempt->reference,
            )));

            return response()->json(['message' => 'amount mismatch'], 422);
        }

        DB::transaction(function () use ($attempt, $payload) {
            // Verrou + re-vérification à l'intérieur de la transaction : deux notifications
            // reçues en parallèle ne doivent jamais faire créer/confirmer/décrémenter deux fois.
            $locked = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'success') {
                return;
            }

            $locked->update(['status' => 'success', 'raw_response' => json_encode($payload)]);

            $order = $this->orders->createFromAttempt($locked);

            $payment = $order->payments()->create([
                'provider' => $order->payment_method,
                'gateway' => 'paytech',
                'transaction_id' => $locked->reference,
                'amount' => $order->total,
                'status' => 'success',
                'raw_response' => json_encode($payload),
                'paid_at' => now(),
            ]);

            $locked->update(['order_id' => $order->id]);

            $this->orders->confirm($order);
            $this->orders->notifyNewOnlineOrder($order, $payment);
        });

        return response()->json(['message' => 'ok']);
    }

    private function resolveFromRef(?string $ref): ?PaymentAttempt
    {
        if (! $ref) {
            return null;
        }

        return PaymentAttempt::where('reference', $ref)->first();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrangeMoneyService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrangeMoneyController extends Controller
{
    public function __construct(
        private readonly OrangeMoneyService $orangeMoney,
        private readonly OrderService $orders,
    ) {
    }

    /**
     * Orange Money redirige le navigateur ici après un paiement réussi. N'affiche que l'état
     * déjà en base — la notification (vérifiée activement, voir notif()) est seule habilitée
     * à faire passer le paiement à "payé".
     */
    public function success(Request $request, ?Order $order = null): RedirectResponse
    {
        $order = $order ?? $this->resolveFromRef($request->query('ref'));

        abort_unless($order, 404);

        return redirect()->route('checkout.confirmation', $order);
    }

    /**
     * Orange Money redirige ici si le client annule sur sa page de paiement.
     */
    public function cancel(Request $request, ?Order $order = null): RedirectResponse
    {
        $order = $order ?? $this->resolveFromRef($request->query('ref'));

        abort_unless($order, 404);

        $order->payments()
            ->where('gateway', 'orange_money')
            ->where('status', 'processing')
            ->latest()
            ->first()
            ?->update(['status' => 'cancelled']);

        return redirect()->route('checkout.confirmation', $order)
            ->withErrors(['payment_method' => 'Paiement Orange Money annulé. Vous pouvez réessayer ci-dessous.']);
    }

    /**
     * Notification Orange Money sur notif_url.
     *
     * IMPORTANT : la documentation publique d'Orange Money Web Payment ne décrit aucune
     * signature cryptographique pour cette notification (contrairement à PayTech et Wave) — son
     * contenu n'est donc JAMAIS traité comme une preuve de paiement. Il ne sert qu'à déclencher
     * une vérification ACTIVE auprès d'Orange (endpoint transactionstatus, qui fait réellement
     * autorité) avant de considérer quoi que ce soit comme payé — voir
     * OrangeMoneyService::verifyAndFetchStatus().
     */
    public function notif(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id') ?? $request->input('reference');

        $payment = Payment::where('transaction_id', $orderId)
            ->where('gateway', 'orange_money')
            ->first();

        if (! $payment) {
            report(new RuntimeException('Orange Money notif : référence inconnue '.($orderId ?? '?')));

            return response()->json(['message' => 'unknown reference'], 404);
        }

        if (in_array($payment->status, ['success', 'cancelled', 'refunded'], true)) {
            return response()->json(['message' => 'already processed']);
        }

        $stored = json_decode($payment->raw_response ?? '', true) ?? [];
        $payToken = $stored['pay_token'] ?? null;

        if (! $payToken) {
            report(new RuntimeException('Orange Money notif : pay_token introuvable pour '.$payment->transaction_id));

            return response()->json(['message' => 'missing pay_token'], 422);
        }

        // Seule source faisant autorité : jamais le corps de cette notification.
        $status = $this->orangeMoney->verifyAndFetchStatus($payment->transaction_id, (int) $payment->amount, $payToken);

        if ($status === null) {
            report(new RuntimeException('Orange Money notif : vérification active injoignable pour '.$payment->transaction_id));

            return response()->json(['message' => 'status check unavailable'], 502);
        }

        if (in_array($status, ['FAILED', 'EXPIRED'], true)) {
            $payment->update(['status' => 'cancelled', 'raw_response' => json_encode($stored + ['last_notif' => $request->all(), 'verified_status' => $status])]);

            return response()->json(['message' => 'ok']);
        }

        if ($status !== 'SUCCESS') {
            return response()->json(['message' => 'not yet complete']);
        }

        DB::transaction(function () use ($payment, $stored, $request, $status) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'success') {
                return;
            }

            $order = $locked->order()->lockForUpdate()->first();

            $locked->update([
                'status' => 'success',
                'raw_response' => json_encode($stored + ['last_notif' => $request->all(), 'verified_status' => $status]),
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

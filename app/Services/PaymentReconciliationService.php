<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Collection;

/**
 * Rapprochement commande ↔ paiement ↔ transaction (section 6 du cahier des charges multi-rôles).
 * Chaque anomalie est une vraie requête sur les données existantes — rien n'est simulé.
 */
class PaymentReconciliationService
{
    /**
     * @return array{
     *     paidWithoutPayment: Collection<int, Order>,
     *     paymentWithoutPaidStatus: Collection<int, Order>,
     *     amountMismatch: Collection<int, array{order: Order, paidAmount: int}>,
     *     duplicates: Collection<int, array{order: Order, count: int}>,
     *     stalePending: Collection<int, Payment>,
     *     orphanPayments: Collection<int, Payment>,
     * }
     */
    public function anomalies(): array
    {
        // Commande marquée payée mais aucun paiement "réussi" enregistré (ex. statut basculé
        // manuellement sans passer par un vrai paiement).
        $paidWithoutPayment = Order::where('payment_status', 'paid')
            ->where('status', '!=', 'annulee')
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'success'))
            ->latest()
            ->get();

        // Paiement réussi enregistré, mais la commande n'est pas (ou plus) marquée payée.
        $paymentWithoutPaidStatus = Order::whereHas('payments', fn ($q) => $q->where('status', 'success'))
            ->where('payment_status', '!=', 'paid')
            ->latest()
            ->get();

        // Montant total des paiements réussis différent du total de la commande.
        $amountMismatch = Order::whereHas('payments', fn ($q) => $q->where('status', 'success'))
            ->with(['payments' => fn ($q) => $q->where('status', 'success')])
            ->get()
            ->map(fn (Order $order) => ['order' => $order, 'paidAmount' => (int) $order->payments->sum('amount')])
            ->filter(fn (array $row) => $row['paidAmount'] !== (int) $row['order']->total)
            ->values();

        // Plus d'un paiement réussi pour la même commande (doublon potentiel) — agrégation directe
        // plutôt qu'un alias en HAVING, non supporté par Postgres.
        $duplicates = Order::query()
            ->select('orders.*')
            ->selectRaw('COUNT(payments.id) as successful_payments_count')
            ->join('payments', fn ($join) => $join->on('payments.order_id', '=', 'orders.id')->where('payments.status', 'success'))
            ->groupBy('orders.id')
            ->havingRaw('COUNT(payments.id) > 1')
            ->get()
            ->map(fn (Order $order) => ['order' => $order, 'count' => (int) $order->successful_payments_count])
            ->values();

        // Paiements restés "en attente" plus de 24h — probablement bloqués.
        $stalePending = Payment::where('status', 'pending')
            ->where('created_at', '<=', now()->subDay())
            ->with('order')
            ->latest()
            ->get();

        // Paiement sans commande associée — structurellement improbable (clé étrangère en
        // cascade), vérifié quand même par souci de complétude de l'audit.
        $orphanPayments = Payment::whereDoesntHave('order')->get();

        return compact('paidWithoutPayment', 'paymentWithoutPaidStatus', 'amountMismatch', 'duplicates', 'stalePending', 'orphanPayments');
    }
}

<?php

namespace App\Services\Dashboards;

use App\Models\Order;
use App\Models\Payment;
use App\Support\DashboardPeriod;

/**
 * Espace Caisse — centré sur la journée par nature (section 7 du cahier des charges), mais
 * accepte tout de même une période pour permettre de consulter un jour précédent si besoin.
 */
class CashierDashboardService
{
    public function build(DashboardPeriod $period): array
    {
        $confirmed = Order::CONFIRMED_STATUSES;

        $salesCount = Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->start, $period->end])->count();
        $salesTotal = (int) Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->start, $period->end])->sum('total');

        $collectedByMethod = Payment::where('payments.status', 'success')
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->selectRaw('orders.payment_method, count(*) as count, sum(payments.amount) as total')
            ->groupBy('orders.payment_method')
            ->get();

        $pendingPayments = Payment::where('status', 'pending')->whereBetween('created_at', [$period->start, $period->end])->count();
        $failedPayments = Payment::where('status', 'failed')->whereBetween('created_at', [$period->start, $period->end])->count();
        $refunds = Payment::where('status', 'refunded')->whereBetween('updated_at', [$period->start, $period->end])->get();

        $toCollect = Order::where('payment_status', '!=', 'paid')
            ->where('status', '!=', 'annulee')
            ->orderByDesc('created_at')
            ->take(10)
            ->get(['id', 'order_number', 'customer_name', 'total', 'payment_method', 'payment_status', 'created_at']);

        return [
            'period' => $period,
            'salesCount' => $salesCount,
            'salesTotal' => $salesTotal,
            'collectedByMethod' => $collectedByMethod,
            'collectedTotal' => (int) $collectedByMethod->sum('total'),
            'pendingPayments' => $pendingPayments,
            'failedPayments' => $failedPayments,
            'refunds' => $refunds,
            'refundsTotal' => (int) $refunds->sum('amount'),
            'toCollect' => $toCollect,
        ];
    }
}

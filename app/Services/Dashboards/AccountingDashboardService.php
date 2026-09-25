<?php

namespace App\Services\Dashboards;

use App\Models\CouponUsage;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Support\DashboardPeriod;
use App\Support\MarginCalculator;

/**
 * Espace Comptable — chiffre d'affaires, encaissements réels (table payments, pas seulement
 * orders.total), impayés, remboursements, remises, dépenses réelles et marge brute quand le coût
 * d'achat est renseigné sur les produits vendus (voir MarginCalculator — jamais extrapolée).
 */
class AccountingDashboardService
{
    public function build(DashboardPeriod $period): array
    {
        $confirmed = Order::CONFIRMED_STATUSES;

        $revenueGross = (int) Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->start, $period->end])->sum('total');
        $discounts = (int) Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->start, $period->end])->sum('discount');
        $revenueNet = $revenueGross - $discounts;

        $prevRevenueGross = (int) Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->previousStart, $period->previousEnd])->sum('total');

        $collected = (int) Payment::where('status', 'success')->whereBetween('paid_at', [$period->start, $period->end])->sum('amount');
        $pending = (int) Payment::where('status', 'pending')->whereBetween('created_at', [$period->start, $period->end])->sum('amount');
        $refunded = (int) Payment::where('status', 'refunded')->whereBetween('updated_at', [$period->start, $period->end])->sum('amount');
        $failed = Payment::where('status', 'failed')->whereBetween('created_at', [$period->start, $period->end])->count();

        $unpaidOrders = Order::where('payment_status', '!=', 'paid')
            ->where('status', '!=', 'annulee')
            ->whereBetween('created_at', [$period->start, $period->end])
            ->get(['id', 'order_number', 'customer_name', 'total', 'payment_status', 'created_at']);

        $paymentsByMethod = Order::whereIn('status', $confirmed)
            ->whereBetween('created_at', [$period->start, $period->end])
            ->selectRaw('payment_method, count(*) as count, sum(total) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $couponUsageCount = CouponUsage::whereBetween('created_at', [$period->start, $period->end])->count();

        $expenses = (int) Expense::whereBetween('expense_date', [$period->start, $period->end])->sum('amount');
        $margin = MarginCalculator::compute($period->start, $period->end);
        $netResult = $margin['available'] ? $margin['margin'] - $expenses : null;

        // Série quotidienne encaissements vs revenus déclarés — 14 derniers jours de la période
        // sélectionnée, pour rester lisible sur un graphique sans dépendre de la largeur de la
        // période choisie.
        $seriesStart = $period->end->copy()->subDays(13)->startOfDay();
        $revenueSeries = Order::whereIn('status', $confirmed)
            ->whereBetween('created_at', [$seriesStart, $period->end])
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')->orderBy('day')->pluck('total', 'day');
        $paymentSeries = Payment::where('status', 'success')
            ->whereBetween('paid_at', [$seriesStart, $period->end])
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->groupBy('day')->orderBy('day')->pluck('total', 'day');

        $days = [];
        for ($d = $seriesStart->copy(); $d->lte($period->end); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $days[] = [
                'date' => $d->copy(),
                'revenue' => (int) ($revenueSeries[$key] ?? 0),
                'collected' => (int) ($paymentSeries[$key] ?? 0),
            ];
        }

        return [
            'period' => $period,
            'revenueGross' => $revenueGross,
            'revenueNet' => $revenueNet,
            'revenueTrend' => DashboardPeriod::trend($revenueGross, $prevRevenueGross),
            'discounts' => $discounts,
            'collected' => $collected,
            'pending' => $pending,
            'refunded' => $refunded,
            'failedPaymentsCount' => $failed,
            'unpaidOrders' => $unpaidOrders,
            'unpaidTotal' => (int) $unpaidOrders->sum('total'),
            'paymentsByMethod' => $paymentsByMethod,
            'couponUsageCount' => $couponUsageCount,
            'series' => $days,
            'expenses' => $expenses,
            'margin' => $margin,
            'netResult' => $netResult,
        ];
    }
}

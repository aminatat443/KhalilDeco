<?php

namespace App\Services\Dashboards;

use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductReturn;
use App\Support\DashboardPeriod;

/**
 * Espace Service client — listes actionnables (commandes à traiter, bloquées, non livrées,
 * réclamations) plutôt qu'un tableau de chiffres, conformément à la nature du métier.
 */
class CustomerServiceDashboardService
{
    public function build(DashboardPeriod $period): array
    {
        $recentOrders = Order::whereBetween('created_at', [$period->start, $period->end])->latest()->take(8)->get();

        $pendingOrdersQuery = Order::where('status', 'recue');
        $pendingOrdersCount = (clone $pendingOrdersQuery)->count();
        $pendingOrders = $pendingOrdersQuery->orderBy('created_at')->take(10)->get();

        $notDeliveredQuery = Order::overdueShipping();
        $notDeliveredCount = (clone $notDeliveredQuery)->count();
        $notDelivered = $notDeliveredQuery->orderBy('created_at')->take(10)->get();

        $returnsQuery = ProductReturn::whereIn('status', ['demandee', 'acceptee']);
        $returnsCount = (clone $returnsQuery)->count();
        $returns = $returnsQuery->with('orderItem.order')->orderBy('created_at')->take(10)->get();

        $abandonedCartsQuery = Cart::whereNull('reminded_at')
            ->where('updated_at', '<=', now()->subHours(2))
            ->whereNotNull('user_id');
        $abandonedCartsCount = (clone $abandonedCartsQuery)->count();
        $abandonedCarts = $abandonedCartsQuery->with('user')->latest('updated_at')->take(10)->get();

        return [
            'period' => $period,
            'recentOrders' => $recentOrders,
            'pendingOrders' => $pendingOrders,
            'pendingOrdersCount' => $pendingOrdersCount,
            'notDelivered' => $notDelivered,
            'notDeliveredCount' => $notDeliveredCount,
            'returns' => $returns,
            'returnsCount' => $returnsCount,
            'abandonedCarts' => $abandonedCarts,
            'abandonedCartsCount' => $abandonedCartsCount,
        ];
    }
}

<?php

namespace App\Services\Dashboards;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Support\DashboardPeriod;

/**
 * Espace Gestion de boutique — opérations quotidiennes (commandes à préparer/expédier, stock
 * bas, ventes récentes), sans les fonctions financières sensibles réservées au Comptable/Admin.
 */
class StoreManagerDashboardService
{
    public function build(DashboardPeriod $period): array
    {
        $confirmed = Order::CONFIRMED_STATUSES;

        $ordersToday = Order::whereDate('created_at', today())->count();
        $toPrepare = Order::where('status', 'confirmee')->count();
        $toShip = Order::where('status', 'en_preparation')->count();
        $delivered = Order::where('status', 'livree')->whereBetween('updated_at', [$period->start, $period->end])->count();

        $threshold = Setting::current()->low_stock_threshold ?? 5;
        $lowStockCount = ProductVariant::where('stock', '>', 0)->where('stock', '<=', $threshold)->count()
            + Product::where('stock', '>', 0)->where('stock', '<=', $threshold)->whereDoesntHave('variants')->count();

        $recentSales = Order::whereIn('status', $confirmed)->latest()->take(8)->get();
        $newClients = User::where('role', 'client')->whereBetween('created_at', [$period->start, $period->end])->count();

        $recentActivity = Order::whereBetween('created_at', [$period->start, $period->end])->latest()->take(10)->get();

        return [
            'period' => $period,
            'ordersToday' => $ordersToday,
            'toPrepare' => $toPrepare,
            'toShip' => $toShip,
            'delivered' => $delivered,
            'lowStockCount' => $lowStockCount,
            'recentSales' => $recentSales,
            'newClients' => $newClients,
            'recentActivity' => $recentActivity,
        ];
    }
}

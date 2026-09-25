<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;

/**
 * Calcule la marge brute réelle sur une période, uniquement à partir des lignes de commande dont
 * le produit a un coût d'achat renseigné (Product.cost_price, ajouté pour ce besoin). Comme la
 * plupart des produits n'auront pas encore ce coût au départ, le résultat inclut toujours son
 * taux de couverture — jamais une marge extrapolée sur les articles sans coût connu.
 */
class MarginCalculator
{
    public static function compute(Carbon $start, Carbon $end): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', Order::CONFIRMED_STATUSES)
            ->whereBetween('orders.created_at', [$start, $end])
            ->selectRaw('order_items.quantity, order_items.subtotal, products.cost_price')
            ->get();

        $withCost = $rows->whereNotNull('cost_price');
        $cogs = (int) $withCost->sum(fn ($r) => $r->quantity * $r->cost_price);
        $revenueWithCost = (int) $withCost->sum('subtotal');

        return [
            'available' => $withCost->isNotEmpty(),
            'coverageRatio' => $rows->count() > 0 ? (int) round($withCost->count() / $rows->count() * 100) : 0,
            'cogs' => $cogs,
            'margin' => $revenueWithCost - $cogs,
            'revenueWithCost' => $revenueWithCost,
            'itemsTotal' => $rows->count(),
            'itemsWithCost' => $withCost->count(),
        ];
    }
}

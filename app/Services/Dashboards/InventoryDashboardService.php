<?php

namespace App\Services\Dashboards;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Support\DashboardPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Espace Stock — mouvements réels (table stock_movements, ajoutée pour le système RBAC), seuils
 * de la Configuration existante, et une liste « à réapprovisionner » basée sur la vitesse de
 * vente réelle des 30 derniers jours (pas une prévision inventée).
 */
class InventoryDashboardService
{
    public function build(DashboardPeriod $period): array
    {
        $threshold = Setting::current()->low_stock_threshold ?? 5;

        $totalStockUnits = (int) ProductVariant::sum('stock') + (int) Product::whereDoesntHave('variants')->sum('stock');

        // Valeur du stock = stock × coût d'achat, uniquement pour les produits dont le coût est
        // renseigné (Product.cost_price) — jamais une estimation pour les autres.
        $productsWithStock = Product::where('stock', '>', 0)->orWhereHas('variants', fn ($q) => $q->where('stock', '>', 0));
        $productsWithStockCount = (clone $productsWithStock)->count();
        $productsWithCostCount = (clone $productsWithStock)->whereNotNull('cost_price')->count();

        $stockValue = (int) Product::whereNotNull('cost_price')
            ->withSum('variants as variants_stock', 'stock')
            ->get()
            ->sum(fn (Product $p) => $p->variants_stock > 0 ? $p->variants_stock * $p->cost_price : $p->stock * $p->cost_price);

        $outOfStockCount = ProductVariant::where('stock', 0)->count()
            + Product::where('stock', 0)->whereDoesntHave('variants')->count();

        $lowStockVariants = ProductVariant::where('stock', '>', 0)->where('stock', '<=', $threshold)
            ->with('product', 'color', 'size')->orderBy('stock')->get();
        $lowStockProducts = Product::where('stock', '>', 0)->where('stock', '<=', $threshold)
            ->whereDoesntHave('variants')->orderBy('stock')->get();

        $movementsToday = StockMovement::whereDate('created_at', today())->get();
        $entriesToday = $movementsToday->where('type', StockMovement::TYPE_ENTREE)->sum('quantity');
        $exitsToday = $movementsToday->where('type', StockMovement::TYPE_SORTIE)->sum('quantity');

        $movementsPeriod = StockMovement::with('product', 'variant.color', 'variant.size', 'user')
            ->whereBetween('created_at', [$period->start, $period->end])
            ->latest()
            ->take(15)
            ->get();

        // Vitesse de vente réelle (30 derniers jours) pour recommander une quantité de
        // réapprovisionnement — jamais une estimation arbitraire.
        $salesVelocity = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', \App\Models\Order::CONFIRMED_STATUSES)
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as sold_30d, MAX(orders.created_at) as last_sale')
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        $restockList = $lowStockVariants->map(function (ProductVariant $variant) use ($salesVelocity) {
            $velocity = $salesVelocity->get($variant->product_id);
            $sold30d = $velocity->sold_30d ?? null;

            return [
                'productId' => $variant->product_id,
                'label' => $variant->product?->name.($variant->label() ? ' — '.$variant->label() : ''),
                'stock' => $variant->stock,
                'threshold' => null,
                'sold30d' => $sold30d,
                'dailyVelocity' => $sold30d ? round($sold30d / 30, 2) : null,
                'lastSale' => $velocity->last_sale ?? null,
                'recommendedQty' => $sold30d ? max(1, (int) ceil($sold30d / 30 * 14)) : null,
            ];
        })->concat($lowStockProducts->map(function (Product $product) use ($salesVelocity) {
            $velocity = $salesVelocity->get($product->id);
            $sold30d = $velocity->sold_30d ?? null;

            return [
                'productId' => $product->id,
                'label' => $product->name,
                'stock' => $product->stock,
                'threshold' => null,
                'sold30d' => $sold30d,
                'dailyVelocity' => $sold30d ? round($sold30d / 30, 2) : null,
                'lastSale' => $velocity->last_sale ?? null,
                'recommendedQty' => $sold30d ? max(1, (int) ceil($sold30d / 30 * 14)) : null,
            ];
        }))->sortBy('stock')->values();

        return [
            'period' => $period,
            'threshold' => $threshold,
            'totalStockUnits' => $totalStockUnits,
            'outOfStockCount' => $outOfStockCount,
            'lowStockCount' => $lowStockVariants->count() + $lowStockProducts->count(),
            'entriesToday' => $entriesToday,
            'exitsToday' => $exitsToday,
            'movementsPeriod' => $movementsPeriod,
            'restockList' => $restockList,
            'stockValue' => $stockValue,
            'stockValueAvailable' => $productsWithCostCount > 0,
            'stockValueCoverage' => $productsWithStockCount > 0 ? (int) round($productsWithCostCount / $productsWithStockCount * 100) : 0,
        ];
    }
}

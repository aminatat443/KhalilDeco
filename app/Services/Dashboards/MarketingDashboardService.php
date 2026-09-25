<?php

namespace App\Services\Dashboards;

use App\Models\Campaign;
use App\Models\Cart;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\DashboardPeriod;

/**
 * Espace Marketing — performance produits/catégories réellement vendue, clients nouveaux vs
 * récurrents, campagnes (envoyés/échecs réels). Pas de taux d'ouverture/clic/conversion : aucun
 * tracking de ce type n'existe dans l'application (voir analyse préalable).
 */
class MarketingDashboardService
{
    public function build(DashboardPeriod $period): array
    {
        $confirmed = Order::CONFIRMED_STATUSES;

        $ordersCount = Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->start, $period->end])->count();
        $revenue = (int) Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->start, $period->end])->sum('total');
        $averageOrder = $ordersCount > 0 ? (int) round($revenue / $ordersCount) : 0;

        $newClients = User::where('role', 'client')->whereBetween('created_at', [$period->start, $period->end])->count();

        $returningClientIds = Order::whereIn('status', $confirmed)
            ->whereBetween('created_at', [$period->start, $period->end])
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->countBy()
            ->filter(fn ($count) => $count > 1)
            ->count();

        $topByQuantity = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', $confirmed)
            ->whereBetween('orders.created_at', [$period->start, $period->end])
            ->selectRaw('order_items.product_id, order_items.product_name, SUM(order_items.quantity) as qty, SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('qty')
            ->take(8)
            ->get();

        $topByRevenue = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', $confirmed)
            ->whereBetween('orders.created_at', [$period->start, $period->end])
            ->selectRaw('order_items.product_id, order_items.product_name, SUM(order_items.quantity) as qty, SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('revenue')
            ->take(8)
            ->get();

        $topCategories = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('orders.status', $confirmed)
            ->whereBetween('orders.created_at', [$period->start, $period->end])
            ->selectRaw('categories.name, SUM(order_items.quantity) as qty, SUM(order_items.subtotal) as revenue')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->take(6)
            ->get();

        $mostFavorited = Favorite::selectRaw('product_id, count(*) as favorites_count')
            ->groupBy('product_id')
            ->orderByDesc('favorites_count')
            ->with('product')
            ->take(6)
            ->get();

        $abandonedCartsCount = Cart::whereNull('reminded_at')->where('updated_at', '<=', now()->subHours(2))->count();

        $campaigns = Campaign::whereBetween('created_at', [$period->start, $period->end])->get();

        $engagement = \App\Models\CampaignSend::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('status', 'sent')
            ->selectRaw('campaign_id, count(*) as sent_total, count(opened_at) as opened_total, count(clicked_at) as clicked_total')
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        $campaignsByType = $campaigns->groupBy('campaign_type')->map(function ($group) use ($engagement) {
            $sentTotal = (int) $group->sum(fn ($c) => $engagement->get($c->id)?->sent_total ?? 0);
            $openedTotal = (int) $group->sum(fn ($c) => $engagement->get($c->id)?->opened_total ?? 0);
            $clickedTotal = (int) $group->sum(fn ($c) => $engagement->get($c->id)?->clicked_total ?? 0);

            return [
                'label' => Campaign::CAMPAIGN_TYPES[$group->first()->campaign_type] ?? $group->first()->campaign_type,
                'count' => $group->count(),
                'sent' => (int) $group->sum('sent_count'),
                'failed' => (int) $group->sum('failed_count'),
                'openRate' => $sentTotal > 0 ? (int) round($openedTotal / $sentTotal * 100) : null,
                'clickRate' => $sentTotal > 0 ? (int) round($clickedTotal / $sentTotal * 100) : null,
                'opened' => $openedTotal,
                'clicked' => $clickedTotal,
            ];
        })->values();

        $openClickTrackingAvailable = $engagement->isNotEmpty() && $engagement->sum('sent_total') > 0;

        return [
            'period' => $period,
            'ordersCount' => $ordersCount,
            'revenue' => $revenue,
            'averageOrder' => $averageOrder,
            'newClients' => $newClients,
            'returningClients' => $returningClientIds,
            'topByQuantity' => $topByQuantity,
            'topByRevenue' => $topByRevenue,
            'topCategories' => $topCategories,
            'mostFavorited' => $mostFavorited,
            'abandonedCartsCount' => $abandonedCartsCount,
            'campaignsByType' => $campaignsByType,
            'campaignsActive' => Campaign::whereIn('status', [Campaign::STATUS_SCHEDULED, Campaign::STATUS_PENDING, Campaign::STATUS_PROCESSING])->count(),
            'openClickTrackingAvailable' => $openClickTrackingAvailable,
            'conversionRateAvailable' => false,
        ];
    }
}

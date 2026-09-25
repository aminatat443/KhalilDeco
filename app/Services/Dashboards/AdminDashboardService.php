<?php

namespace App\Services\Dashboards;

use App\Models\Campaign;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ProductVariant;
use App\Models\Expense;
use App\Models\Setting;
use App\Models\User;
use App\Support\DashboardPeriod;
use App\Support\MarginCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Cockpit global (Super Admin / Administrateur) — seul espace qui agrège toutes les données de
 * l'entreprise. Chaque métrique vient des tables réelles ; rien n'est estimé au-delà de ce que
 * les données permettent (pas de coût d'achat en base => pas de marge/bénéfice affiché).
 */
class AdminDashboardService
{
    public function build(DashboardPeriod $period): array
    {
        $confirmed = Order::CONFIRMED_STATUSES;

        $revenue = (int) Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->start, $period->end])->sum('total');
        $prevRevenue = (int) Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->previousStart, $period->previousEnd])->sum('total');

        $ordersCount = Order::whereBetween('created_at', [$period->start, $period->end])->count();
        $prevOrdersCount = Order::whereBetween('created_at', [$period->previousStart, $period->previousEnd])->count();

        $confirmedCount = Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->start, $period->end])->count();

        $paidCount = Order::where('payment_status', 'paid')->whereBetween('created_at', [$period->start, $period->end])->count();
        $unpaidCount = Order::where('payment_status', '!=', 'paid')->where('status', '!=', 'annulee')->whereBetween('created_at', [$period->start, $period->end])->count();

        $newClients = User::where('role', 'client')->whereBetween('created_at', [$period->start, $period->end])->count();
        $prevNewClients = User::where('role', 'client')->whereBetween('created_at', [$period->previousStart, $period->previousEnd])->count();

        $threshold = Setting::current()->low_stock_threshold ?? 5;
        $lowStockVariants = ProductVariant::where('stock', '>', 0)->where('stock', '<=', $threshold)->with('product')->orderBy('stock')->take(8)->get();
        $outOfStockCount = ProductVariant::where('stock', 0)->count()
            + Product::where('stock', 0)->whereDoesntHave('variants')->count();

        $abandonedCarts = Cart::whereNull('reminded_at')->where('updated_at', '<=', now()->subHours(2))->get();
        $abandonedCartsValue = 0;
        foreach ($abandonedCarts as $cart) {
            foreach (($cart->items ?? []) as $item) {
                $product = Product::find($item['product_id'] ?? null);
                if (! $product) {
                    continue;
                }
                $variant = ! empty($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;
                $price = $variant?->price ?? $product->price;
                $abandonedCartsValue += $price * ($item['quantity'] ?? 1);
            }
        }

        $campaigns = Campaign::whereBetween('created_at', [$period->start, $period->end])->get();

        $paymentsByStatus = Payment::whereBetween('created_at', [$period->start, $period->end])
            ->selectRaw('status, count(*) as count, sum(amount) as total')
            ->groupBy('status')
            ->get();

        $couponUsage = CouponUsage::whereBetween('created_at', [$period->start, $period->end])->count();
        $couponRevenue = (int) Order::whereIn('status', $confirmed)
            ->whereNotNull('coupon_id')
            ->whereBetween('created_at', [$period->start, $period->end])
            ->sum('total');

        $onlineStaffCount = DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->where('sessions.last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->whereNotNull('sessions.user_id')
            ->distinct('sessions.user_id')
            ->count('sessions.user_id');

        $pendingReturns = ProductReturn::where('status', 'demandee')->count();

        $margin = MarginCalculator::compute($period->start, $period->end);
        $expenses = (int) Expense::whereBetween('expense_date', [$period->start, $period->end])->sum('amount');

        return [
            'period' => $period,
            'revenue' => $revenue,
            'revenueTrend' => DashboardPeriod::trend($revenue, $prevRevenue),
            'ordersCount' => $ordersCount,
            'ordersTrend' => DashboardPeriod::trend($ordersCount, $prevOrdersCount),
            'confirmedCount' => $confirmedCount,
            'confirmationRate' => $ordersCount > 0 ? round(($confirmedCount / $ordersCount) * 100) : 0,
            'paidCount' => $paidCount,
            'unpaidCount' => $unpaidCount,
            'newClients' => $newClients,
            'newClientsTrend' => DashboardPeriod::trend($newClients, $prevNewClients),
            'lowStockVariants' => $lowStockVariants,
            'outOfStockCount' => $outOfStockCount,
            'abandonedCartsCount' => $abandonedCarts->count(),
            'abandonedCartsValue' => $abandonedCartsValue,
            'campaignsSent' => $campaigns->whereIn('status', [Campaign::STATUS_SENT, Campaign::STATUS_PARTIAL])->count(),
            'campaignsFailed' => (int) $campaigns->sum('failed_count'),
            'emailsSent' => (int) $campaigns->sum('sent_count'),
            'paymentsByStatus' => $paymentsByStatus,
            'couponUsage' => $couponUsage,
            'couponRevenue' => $couponRevenue,
            'onlineStaffCount' => $onlineStaffCount,
            'pendingReturns' => $pendingReturns,
            'recentOrders' => Order::latest()->take(6)->get(),
            'expenses' => $expenses,
            'margin' => $margin,
            'netResult' => $margin['available'] ? $margin['margin'] - $expenses : null,
        ];
    }
}

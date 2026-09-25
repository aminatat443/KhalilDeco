<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FinanceController extends Controller
{
    /**
     * Vue d'ensemble financière — même définition du chiffre d'affaires que le tableau de bord
     * (statuts "confirmée" et suivants, une commande "reçue" n'étant pas encore acquise).
     */
    private const CONFIRMED_STATUSES = ['confirmee', 'en_preparation', 'expediee', 'livree'];

    public function index(): View
    {
        $this->authorize('viewAny', Order::class);

        $confirmed = Order::whereIn('status', self::CONFIRMED_STATUSES);

        $revenue = (clone $confirmed)->sum('total');
        $ordersCount = (clone $confirmed)->count();

        $thisMonth = (clone $confirmed)->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('total');
        $lastMonth = (clone $confirmed)
            ->whereYear('created_at', now()->subMonthNoOverflow()->year)
            ->whereMonth('created_at', now()->subMonthNoOverflow()->month)
            ->sum('total');
        $monthTrend = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100) : ($thisMonth > 0 ? 100 : 0);

        $thisYear = (clone $confirmed)->whereYear('created_at', now()->year)->sum('total');
        $lastYear = (clone $confirmed)->whereYear('created_at', now()->subYear()->year)->sum('total');
        $yearTrend = $lastYear > 0 ? round((($thisYear - $lastYear) / $lastYear) * 100) : ($thisYear > 0 ? 100 : 0);

        $byPaymentMethod = (clone $confirmed)
            ->selectRaw('payment_method, count(*) as orders_count, sum(total) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        return view('admin.finances.index', [
            'revenue' => $revenue,
            'ordersCount' => $ordersCount,
            'averageOrder' => $ordersCount > 0 ? (int) round($revenue / $ordersCount) : 0,
            'totalDiscounts' => (clone $confirmed)->sum('discount'),
            'thisMonth' => $thisMonth,
            'monthTrend' => $monthTrend,
            'thisYear' => $thisYear,
            'yearTrend' => $yearTrend,
            'daily' => $this->dailySeries(),
            'monthly' => $this->monthlySeries(),
            'yearly' => $this->yearlySeries(),
            'byPaymentMethod' => $byPaymentMethod,
        ]);
    }

    /**
     * Chiffre d'affaires jour par jour sur les 30 derniers jours (jours sans commande à 0).
     */
    private function dailySeries(): array
    {
        $from = now()->subDays(29)->startOfDay();

        $rows = Order::whereIn('status', self::CONFIRMED_STATUSES)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as d, SUM(total) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        return collect(range(29, 0))->map(function (int $daysAgo) use ($rows) {
            $day = now()->subDays($daysAgo);
            $key = $day->format('Y-m-d');

            return [
                'label' => $day->translatedFormat('d M'),
                'revenue' => (float) ($rows[$key] ?? 0),
                'date' => $key,
            ];
        })->all();
    }

    /**
     * Chiffre d'affaires mois par mois sur les 12 derniers mois (mois sans commande à 0).
     */
    private function monthlySeries(): array
    {
        $from = now()->subMonthsNoOverflow(11)->startOfMonth();

        $rows = Order::whereIn('status', self::CONFIRMED_STATUSES)
            ->where('created_at', '>=', $from)
            ->selectRaw("to_char(created_at, 'YYYY-MM') as m, SUM(total) as total")
            ->groupBy('m')
            ->pluck('total', 'm');

        return collect(range(11, 0))->map(function (int $monthsAgo) use ($rows) {
            $month = now()->subMonthsNoOverflow($monthsAgo);
            $key = $month->format('Y-m');

            return [
                'label' => $month->translatedFormat('M Y'),
                'revenue' => (float) ($rows[$key] ?? 0),
                'date' => $key,
            ];
        })->all();
    }

    /**
     * Chiffre d'affaires année par année, depuis la première commande confirmée (5 ans max).
     */
    private function yearlySeries(): array
    {
        $firstOrder = Order::whereIn('status', self::CONFIRMED_STATUSES)->oldest('created_at')->first();
        $firstYear = $firstOrder ? Carbon::parse($firstOrder->created_at)->year : now()->year;
        $span = min(4, now()->year - $firstYear);

        $rows = Order::whereIn('status', self::CONFIRMED_STATUSES)
            ->selectRaw('EXTRACT(YEAR FROM created_at)::int as y, SUM(total) as total')
            ->groupBy('y')
            ->pluck('total', 'y');

        return collect(range($span, 0))->map(function (int $yearsAgo) use ($rows) {
            $year = now()->subYears($yearsAgo)->year;

            return [
                'label' => (string) $year,
                'revenue' => (float) ($rows[$year] ?? 0),
                'date' => (string) $year,
            ];
        })->all();
    }
}

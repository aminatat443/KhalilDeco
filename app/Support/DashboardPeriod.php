<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Résout une période de tableau de bord (clé courte + éventuelles bornes personnalisées) en
 * dates concrètes, avec la période précédente équivalente pour les comparaisons — partagé par
 * tous les services de tableau de bord par rôle pour éviter de dupliquer ce calcul.
 */
class DashboardPeriod
{
    public const OPTIONS = [
        'today' => "Aujourd'hui",
        'yesterday' => 'Hier',
        'last_7_days' => '7 derniers jours',
        'last_30_days' => '30 derniers jours',
        'this_month' => 'Ce mois-ci',
        'last_month' => 'Mois précédent',
        'this_year' => 'Cette année',
        'custom' => 'Période personnalisée',
    ];

    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly Carbon $previousStart,
        public readonly Carbon $previousEnd,
        public readonly string $key,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
    ) {
    }

    public function label(): string
    {
        return self::OPTIONS[$this->key] ?? self::OPTIONS['last_7_days'];
    }

    public static function resolve(?string $key, ?string $from = null, ?string $to = null): self
    {
        $key = array_key_exists((string) $key, self::OPTIONS) ? $key : 'last_7_days';
        $now = now();

        [$start, $end] = match ($key) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : $now->copy()->subDays(6)->startOfDay(),
                $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
        };

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $days = $start->diffInDays($end) + 1;
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return new self($start, $end, $previousStart, $previousEnd, $key, $from, $to);
    }

    public static function trend(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}

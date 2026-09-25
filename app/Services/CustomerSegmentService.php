<?php

namespace App\Services;

use App\Models\Category;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Segments clients utilisables pour cibler une campagne personnalisée (section 13 du cahier des
 * charges multi-rôles) — calculés à partir des abonnés newsletter réellement liés à un compte
 * client et de leur véritable historique de commandes, jamais d'un profil supposé. La base de
 * chaque segment est toujours "abonné newsletter actif" (whereNull unsubscribed_at) : un client
 * désinscrit n'est jamais compté, même s'il correspond par ailleurs au critère du segment.
 */
class CustomerSegmentService
{
    private const NEW_CLIENT_DAYS = 7;
    private const RECENT_BUYER_DAYS = 7;
    private const INACTIVE_DAYS = 90;

    public const SEGMENTS = [
        'all' => 'Tous les abonnés actifs',
        'new_clients' => 'Nouveaux clients',
        'recent_buyers' => 'Ont acheté récemment',
        'inactive' => "N'ont pas acheté depuis plus de 90 jours",
        'repeat_customers' => 'Clients récurrents',
        'category_buyers' => 'Ont acheté dans une catégorie donnée',
    ];

    public const DESCRIPTIONS = [
        'all' => 'Inscrits à la newsletter avec un compte actif.',
        'new_clients' => 'Compte créé il y a moins de '.self::NEW_CLIENT_DAYS.' jours.',
        'recent_buyers' => 'Commande effectuée il y a moins de '.self::RECENT_BUYER_DAYS.' jours.',
        'inactive' => 'Dernière commande il y a plus de '.self::INACTIVE_DAYS.' jours.',
        'repeat_customers' => '2 commandes ou plus.',
        'category_buyers' => 'Sélectionnez une catégorie pour calculer le segment.',
    ];

    /**
     * @return Collection<int, NewsletterSubscriber>
     */
    public function resolve(string $segment, ?int $categoryId = null): Collection
    {
        $base = NewsletterSubscriber::whereNull('unsubscribed_at');
        $confirmed = Order::CONFIRMED_STATUSES;

        return match ($segment) {
            'new_clients' => $base->whereHas('user', fn ($q) => $q->where('created_at', '>=', now()->subDays(self::NEW_CLIENT_DAYS)))->get(),

            'recent_buyers' => $base->whereHas('user.orders', fn ($q) => $q->whereIn('status', $confirmed)->where('created_at', '>=', now()->subDays(self::RECENT_BUYER_DAYS)))->get(),

            'inactive' => $base->whereHas('user')
                ->whereHas('user.orders', fn ($q) => $q->whereIn('status', $confirmed))
                ->whereDoesntHave('user.orders', fn ($q) => $q->whereIn('status', $confirmed)->where('created_at', '>=', now()->subDays(self::INACTIVE_DAYS)))
                ->get(),

            'repeat_customers' => $base->whereHas('user', function ($q) use ($confirmed) {
                $q->whereHas('orders', fn ($o) => $o->whereIn('status', $confirmed), '>=', 2);
            })->get(),

            'category_buyers' => $categoryId
                ? $base->whereHas('user.orders', function ($q) use ($confirmed, $categoryId) {
                    $q->whereIn('status', $confirmed)
                        ->whereHas('items.product', fn ($p) => $p->where('category_id', $categoryId));
                })->get()
                : collect(),

            default => $base->get(),
        };
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        return collect(self::SEGMENTS)->keys()
            ->reject(fn ($key) => $key === 'category_buyers')
            ->mapWithKeys(fn ($key) => [$key => $this->resolve($key)->count()])
            ->all();
    }

    public function categories()
    {
        return Category::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Détail exploitable d'un segment (section 2 du cahier des charges) : un abonné par ligne,
     * enrichi de son historique de commandes réel quand il est lié à un compte. Un abonné invité
     * (sans compte, newsletter seule) reste dans la liste mais avec "—" sur les colonnes qui
     * dépendent d'un compte client.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function subscriberDetails(string $segment, ?int $categoryId = null): Collection
    {
        $subscribers = $this->resolve($segment, $categoryId)->load('user');
        $confirmed = Order::CONFIRMED_STATUSES;

        $userIds = $subscribers->pluck('user_id')->filter()->values();

        $stats = User::whereIn('id', $userIds)
            ->withCount(['orders' => fn ($q) => $q->where('status', '!=', 'annulee')])
            ->withMax(['orders as last_order_at' => fn ($q) => $q->where('status', '!=', 'annulee')], 'created_at')
            ->withSum(['orders as total_spent' => fn ($q) => $q->whereIn('status', $confirmed)], 'total')
            ->get()
            ->keyBy('id');

        return $subscribers->map(function (NewsletterSubscriber $subscriber) use ($stats) {
            $stat = $subscriber->user_id ? $stats->get($subscriber->user_id) : null;

            return [
                'name' => $subscriber->user?->name ?? $subscriber->email,
                'email' => $subscriber->email,
                'phone' => $subscriber->user?->phone,
                'account_created_at' => $subscriber->user?->created_at,
                'last_order_at' => $stat?->last_order_at,
                'orders_count' => $stat?->orders_count ?? 0,
                'total_spent' => $stat?->total_spent,
                'has_account' => $subscriber->user_id !== null,
            ];
        })->values();
    }
}

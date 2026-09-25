<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recherche globale de l'espace équipier (palette de commande, Ctrl+K) — chaque catégorie n'est
 * incluse que si l'équipier connecté a le droit de voir ce type de ressource (mêmes policies que
 * les pages elles-mêmes), pour ne jamais faire apparaître un résultat vers une page qu'il n'a
 * pas le droit d'ouvrir.
 */
class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q'));

        if (mb_strlen($query) < 2) {
            return response()->json(['groups' => []]);
        }

        $user = $request->user();
        $term = '%'.$query.'%';
        $groups = [];

        if ($user->can('viewAny', Order::class)) {
            $orders = Order::query()
                ->where(fn ($q) => $q->where('order_number', 'ilike', $term)->orWhere('customer_name', 'ilike', $term))
                ->latest()
                ->limit(6)
                ->get();

            if ($orders->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Commandes',
                    'icon' => 'fa-bag-shopping',
                    'items' => $orders->map(fn (Order $o) => [
                        'title' => $o->order_number,
                        'subtitle' => $o->customer_name.' · '.number_format($o->total, 0, ',', ' ').' FCFA',
                        'badge' => Order::STATUS_LABELS[$o->status] ?? $o->status,
                        'url' => route('admin.orders.show', $o),
                    ])->values(),
                ];
            }
        }

        if ($user->can('viewAny', Product::class)) {
            $products = Product::query()
                ->where('name', 'ilike', $term)
                ->orderBy('name')
                ->limit(6)
                ->get();

            if ($products->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Produits',
                    'icon' => 'fa-boxes-stacked',
                    'items' => $products->map(fn (Product $p) => [
                        'title' => $p->name,
                        'subtitle' => number_format($p->price, 0, ',', ' ').' FCFA · Stock : '.$p->stock,
                        'badge' => $p->is_active ? null : 'Inactif',
                        'url' => route('admin.products.edit', $p),
                    ])->values(),
                ];
            }
        }

        if ($user->can('viewAny', User::class)) {
            $clients = User::query()
                ->where(fn ($q) => $q->where('name', 'ilike', $term)->orWhere('email', 'ilike', $term))
                ->orderBy('name')
                ->limit(6)
                ->get();

            if ($clients->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Utilisateurs',
                    'icon' => 'fa-user-group',
                    'items' => $clients->map(fn (User $u) => [
                        'title' => $u->name,
                        'subtitle' => $u->email,
                        'badge' => null,
                        'url' => route('admin.users.edit', $u),
                    ])->values(),
                ];
            }
        }

        if ($user->can('viewAny', Payment::class)) {
            $payments = Payment::query()
                ->with('order')
                ->where(fn ($q) => $q->where('transaction_id', 'ilike', $term)
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'ilike', $term)))
                ->latest()
                ->limit(6)
                ->get();

            if ($payments->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Paiements',
                    'icon' => 'fa-money-check-dollar',
                    'items' => $payments->map(fn (Payment $p) => [
                        'title' => $p->transaction_id ?: ('Paiement #'.$p->id),
                        'subtitle' => ($p->order?->order_number ?? '—').' · '.number_format($p->amount, 0, ',', ' ').' FCFA',
                        'badge' => \App\Models\Payment::STATUS_LABELS[$p->status] ?? $p->status,
                        'url' => $p->order ? route('admin.orders.show', $p->order) : route('admin.payments.index'),
                    ])->values(),
                ];
            }
        }

        if ($user->can('viewAny', Coupon::class)) {
            $coupons = Coupon::query()
                ->where('code', 'ilike', $term)
                ->orderBy('code')
                ->limit(6)
                ->get();

            if ($coupons->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Codes promo',
                    'icon' => 'fa-tag',
                    'items' => $coupons->map(fn (Coupon $c) => [
                        'title' => $c->code,
                        'subtitle' => $c->type === 'percentage' ? $c->value.'%' : number_format($c->value, 0, ',', ' ').' FCFA',
                        'badge' => null,
                        'url' => route('admin.coupons.edit', $c),
                    ])->values(),
                ];
            }
        }

        return response()->json(['groups' => $groups]);
    }
}

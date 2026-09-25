<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        // Le scoping "commandes du client uniquement" se fait au niveau du contrôleur (Order::where('user_id', ...))
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isGestionnaire() || $user->hasPermission('orders.view_details') || $user->id === $order->user_id;
    }

    public function create(User $user): bool
    {
        // Enregistrement d'une commande sur place, depuis le back-office
        return $user->isGestionnaire();
    }

    public function update(User $user, Order $order): bool
    {
        // Changer le statut, confirmer (docs/SPEC.md §2.4)
        return $user->isGestionnaire() || $user->hasPermission('orders.update') || $user->hasPermission('orders.update_status');
    }

    public function cancel(User $user, Order $order): bool
    {
        // Le client peut annuler tant que la commande n'a pas été confirmée (docs/SPEC.md §2.3)
        if ($user->isGestionnaire() || $user->hasPermission('orders.cancel')) {
            return true;
        }

        return $user->id === $order->user_id && $order->status === 'recue';
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}

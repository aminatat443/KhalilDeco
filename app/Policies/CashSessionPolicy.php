<?php

namespace App\Policies;

use App\Models\CashSession;
use App\Models\User;

class CashSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGestionnaire() || $user->hasPermission('cash.view');
    }

    public function open(User $user): bool
    {
        return $user->isGestionnaire() || $user->hasPermission('cash.open');
    }

    public function close(User $user, CashSession $session): bool
    {
        if (! $user->isGestionnaire() && ! $user->hasPermission('cash.close')) {
            return false;
        }

        // Un caissier ne clôture que sa propre session ; un profil de gestion (isGestionnaire())
        // peut clôturer celle d'un autre (caisse laissée ouverte, absence...).
        return $user->id === $session->user_id || $user->isGestionnaire();
    }
}

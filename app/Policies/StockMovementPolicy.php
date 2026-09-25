<?php

namespace App\Policies;

use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGestionnaire() && ($user->role_id === null || $user->hasPermission('stock.movements'));
    }

    public function create(User $user): bool
    {
        return $user->isGestionnaire() && ($user->role_id === null || $user->hasPermission('stock.entry') || $user->hasPermission('stock.exit'));
    }
}

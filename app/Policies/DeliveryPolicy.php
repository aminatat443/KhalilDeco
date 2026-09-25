<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGestionnaire();
    }

    public function view(User $user, Delivery $delivery): bool
    {
        return $user->isGestionnaire();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Delivery $delivery): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Delivery $delivery): bool
    {
        return $user->isAdmin();
    }
}

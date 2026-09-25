<?php

namespace App\Policies;

use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGestionnaire() || $user->hasPermission('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->isGestionnaire() || $user->hasPermission('payments.record');
    }

    public function verify(User $user): bool
    {
        return $user->isGestionnaire() || $user->hasPermission('payments.verify');
    }

    public function refund(User $user): bool
    {
        return $user->isGestionnaire() || $user->hasPermission('payments.refund');
    }
}

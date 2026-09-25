<?php

namespace App\Policies;

use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGestionnaire() || $user->hasPermission('finances.view_expenses');
    }

    public function create(User $user): bool
    {
        return $user->isGestionnaire() || $user->hasPermission('finances.manage_expenses');
    }

    public function delete(User $user): bool
    {
        return $this->create($user);
    }
}

<?php

namespace App\Policies;

use App\Models\Attribute;
use App\Models\User;

class AttributePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Attribute $attribute): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Attribute $attribute): bool
    {
        return $user->isAdmin();
    }
}

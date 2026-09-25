<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGestionnaire();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isGestionnaire() || $user->id === $model->id;
    }

    /**
     * Autorise la création d'un compte du rôle donné (docs/SPEC.md §2.5).
     * Usage : $user->can('create', [User::class, Role::Admin])
     */
    public function create(User $user, Role $targetRole): bool
    {
        return match ($targetRole) {
            Role::SuperAdmin => false, // pas de création via l'application, réservé à un seul compte
            Role::Admin => $user->isSuperAdmin(),
            Role::Gestionnaire => $user->isAdmin(),
            Role::Client => true,
        };
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true; // son propre profil
        }

        return match ($model->role) {
            Role::SuperAdmin => false,
            Role::Admin => $user->isSuperAdmin(),
            Role::Gestionnaire => $user->isAdmin(),
            Role::Client => $user->isGestionnaire(),
        };
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->isSuperAdmin()) {
            return false; // le Super Administrateur ne peut jamais être supprimé via l'application
        }

        return match ($model->role) {
            Role::Admin => $user->isSuperAdmin(),
            Role::Gestionnaire => $user->isAdmin(),
            Role::Client => $user->isGestionnaire(),
        };
    }
}

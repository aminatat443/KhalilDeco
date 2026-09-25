<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('users.manage_roles');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('users.manage_roles');
    }

    /**
     * Un rôle ne peut être modifié que par un acteur dont le rang est strictement supérieur à
     * celui du rôle visé (section 16 : « impossible de modifier les permissions d'un rôle
     * supérieur au sien ») — le Super Admin passe toujours puisqu'aucun rang ne lui est
     * supérieur.
     */
    public function update(User $user, Role $role): bool
    {
        if (! $user->isAdmin() && ! $user->hasPermission('users.manage_roles')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->actorRank($user) > $role->rank();
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->is_system) {
            return false; // les 8 rôles fournis par défaut ne se suppriment pas, seulement leurs permissions se modifient
        }

        return $this->update($user, $role);
    }

    private function actorRank(User $user): int
    {
        if ($user->roleModel) {
            return $user->roleModel->rank();
        }

        return match (true) {
            $user->isAdmin() => 90,
            $user->isGestionnaire() => 50,
            default => 0,
        };
    }
}

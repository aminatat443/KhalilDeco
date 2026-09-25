<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const SUPER_ADMIN = 'super-admin';

    protected $fillable = ['name', 'slug', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === self::SUPER_ADMIN;
    }

    public function hasPermission(string $slug): bool
    {
        return $this->isSuperAdmin() || $this->permissions->contains('slug', $slug);
    }

    /**
     * Rang hiérarchique pour la règle « impossible de modifier un rôle supérieur au sien »
     * (section 16 du cahier des charges RBAC). Plus le chiffre est élevé, plus le rôle est
     * supérieur ; les rôles personnalisés créés par le Super Admin sont rangés au niveau le
     * plus bas de la hiérarchie de protection.
     */
    public function rank(): int
    {
        return match ($this->slug) {
            self::SUPER_ADMIN => 100,
            'administrateur' => 90,
            'gestionnaire-boutique' => 50,
            default => 10,
        };
    }
}

<?php

namespace App\Models;

use App\Enums\Role;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'role_id',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin || $this->roleModel?->isSuperAdmin() === true;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [Role::Admin, Role::SuperAdmin], true) || $this->roleModel?->isSuperAdmin() === true;
    }

    /**
     * Volontairement limité aux 3 rôles hiérarchiques historiques (enum) — un utilisateur RBAC
     * (Caissier, Comptable...) NE doit PAS passer ce contrôle juste parce qu'il a un role_id : ce
     * booléen gouverne encore la plupart des 14 Policies historiques (ex. OrderPolicy::update),
     * et un Caissier n'a par exemple pas le droit de modifier une commande. L'accès au back-office
     * pour les rôles RBAC est admis séparément par EnsureUserIsStaff ; l'accès à chaque action est
     * ensuite tranché par hasPermission().
     */
    public function isGestionnaire(): bool
    {
        return in_array($this->role, [Role::Gestionnaire, Role::Admin, Role::SuperAdmin], true);
    }

    /**
     * Admis dans le back-office : hiérarchie historique OU rôle RBAC (Caissier, Comptable...).
     * C'est ce contrôle — pas isGestionnaire() seul — qui doit décider « cette personne fait
     * partie de l'équipe », que ce soit pour la porte d'entrée /admin (EnsureUserIsStaff) ou la
     * redirection post-connexion (AuthController) ; isGestionnaire() reste réservé aux
     * autorisations fines par action.
     */
    public function isStaffMember(): bool
    {
        return $this->isGestionnaire() || $this->role_id !== null;
    }

    /**
     * Équipe Khalil Déco (Gestionnaire/Admin/Super Admin, ou tout rôle RBAC) — destinataires des
     * notifications internes (nouvelle commande, nouvelle demande de retour...).
     */
    public function scopeStaff($query)
    {
        return $query->whereIn('role', [Role::Gestionnaire, Role::Admin, Role::SuperAdmin])
            ->orWhereNotNull('role_id');
    }

    /**
     * Rôle RBAC granulaire (base de données) — coexiste avec l'ancien enum `role` ci-dessus qui
     * continue de piloter les Policies historiques via isAdmin()/isGestionnaire(). Nommé
     * différemment de `role` pour éviter toute confusion avec l'attribut/l'enum.
     */
    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Role::class, 'role_id');
    }

    /**
     * Permission granulaire du nouveau système RBAC. Le Super Admin (ancien enum ou nouveau rôle)
     * passe toujours ; un utilisateur sans role_id (comptes historiques) n'a aucune permission
     * granulaire et reste couvert uniquement par l'ancienne hiérarchie de rôles.
     */
    public function hasPermission(string $slug): bool
    {
        return $this->isSuperAdmin() || ($this->roleModel?->hasPermission($slug) ?? false);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites');
    }

    /**
     * Version française de l'email de vérification (section 37 du cahier des charges).
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * "En ligne" = une session active (table `sessions`, driver database) avec une activité
     * dans les 5 dernières minutes — pas de connexion permanente à observer autrement.
     */
    public function isOnline(): bool
    {
        return $this->last_activity && $this->last_activity >= now()->subMinutes(5)->timestamp;
    }
}

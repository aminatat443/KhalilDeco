<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role as RbacRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Vue d'ensemble de tous les comptes (clients + équipe) et de leur activité de connexion.
     * "En ligne" / "dernière activité" vient de la table `sessions` (driver database, section
     * — pas de connexion permanente à observer autrement) ; "dernière connexion" vient de
     * users.last_login_at, renseigné à chaque connexion réussie (voir AppServiceProvider).
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $lastActivity = DB::table('sessions')
            ->selectRaw('user_id, MAX(last_activity) as last_activity')
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $users = User::query()
            ->with('roleModel')
            ->leftJoinSub($lastActivity, 'activity', 'activity.user_id', '=', 'users.id')
            ->select('users.*', 'activity.last_activity')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(fn ($q) => $q->where('users.name', 'ilike', $term)->orWhere('users.email', 'ilike', $term));
            })
            ->when($request->filled('role'), fn ($q) => $q->where('users.role', $request->input('role')))
            ->when($request->filled('status'), function ($query) use ($request) {
                $onlineThreshold = now()->subMinutes(5)->timestamp;
                match ($request->input('status')) {
                    'online' => $query->where('activity.last_activity', '>=', $onlineThreshold),
                    'offline' => $query->whereNotNull('users.last_login_at')
                        ->where(fn ($q) => $q->whereNull('activity.last_activity')->orWhere('activity.last_activity', '<', $onlineThreshold)),
                    'never' => $query->whereNull('users.last_login_at'),
                    default => null,
                };
            })
            ->when($request->filled('from'), fn ($q) => $q->whereDate('users.created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('users.created_at', '<=', $request->input('to')))
            // "flag=repeat_customers" reproduit la carte "Nouveaux / récurrents" du tableau de bord
            // Marketing : les clients ayant passé plus d'une commande confirmée sur la même
            // période — bornée par les mêmes from/to que la carte, transmis séparément puisqu'ils
            // filtrent ici les commandes (pas la date de création du compte).
            ->when($request->input('flag') === 'repeat_customers', function ($query) use ($request) {
                $from = $request->input('repeat_from');
                $to = $request->input('repeat_to');
                $repeatUserIds = \App\Models\Order::whereIn('status', \App\Models\Order::CONFIRMED_STATUSES)
                    ->whereNotNull('user_id')
                    ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                    ->select('user_id')
                    ->groupBy('user_id')
                    ->havingRaw('count(*) > 1')
                    ->pluck('user_id');
                $query->whereIn('users.id', $repeatUserIds);
            })
            ->orderByDesc('activity.last_activity')
            ->paginate(25)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.users.partials.table', ['users' => $users])->render(),
            ]);
        }

        return view('admin.users.index', ['users' => $users]);
    }

    /**
     * Création manuelle d'un compte, client ou équipe — le rôle proposé (Client, ou l'un des
     * rôles RBAC : Super Admin, Administrateur, Gestionnaire de boutique, Caissier...) dépend de
     * ce que le compte connecté est autorisé à attribuer (section 16 du cahier des charges RBAC :
     * rang strictement inférieur au sien, sauf pour un Super Admin).
     */
    public function create(): View
    {
        $actor = auth()->user();
        abort_unless($actor->isAdmin() || $actor->hasPermission('users.create'), 403);

        return view('admin.users.create', ['rbacRoles' => $this->assignableRbacRoles($actor)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isAdmin() || $actor->hasPermission('users.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_selection' => ['required', 'string'],
        ]);

        $rbacRole = null;
        $legacyRole = Role::Client;

        if ($data['role_selection'] !== 'client') {
            $rbacRoleId = (int) str_replace('rbac-', '', $data['role_selection']);
            $rbacRole = $this->assignableRbacRoles($actor)->firstWhere('id', $rbacRoleId);

            if (! $rbacRole) {
                throw ValidationException::withMessages(['role_selection' => "Vous n'êtes pas autorisé à attribuer ce rôle."]);
            }

            $legacyRole = match ($rbacRole->slug) {
                RbacRole::SUPER_ADMIN => Role::SuperAdmin,
                'administrateur' => Role::Admin,
                'gestionnaire-boutique' => Role::Gestionnaire,
                default => Role::Client,
            };
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $legacyRole,
            'role_id' => $rbacRole?->id,
            // Un compte équipe créé par un administrateur n'a pas besoin de vérifier son email —
            // un client créé depuis l'admin suit en revanche exactement les mêmes règles qu'une
            // auto-inscription (email à confirmer avant de pouvoir se connecter, voir
            // AuthController::register()). C'est la présence d'un rôle RBAC qui distingue un
            // compte équipe d'un compte client, PAS l'ancien enum : un Caissier ou un Comptable
            // sont mappés sur l'enum "client" (aucun équivalent hiérarchique) mais restent des
            // comptes équipe à activer immédiatement.
            'email_verified_at' => $rbacRole === null ? null : now(),
        ]);

        if ($rbacRole === null) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
            }
        } else {
            ActivityLog::record('users', 'created', $actor->name.' a créé le compte '.$user->name.' ('.$rbacRole->name.')', $user);
        }

        return redirect()->route('admin.users.index')->with('status', 'Compte créé.');
    }

    /**
     * Attribution du rôle RBAC (granulaire) et activation/désactivation du compte — vient
     * compléter la création (toujours pilotée par l'ancien enum ci-dessus) sans y toucher.
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'targetUser' => $user,
            'roles' => RbacRole::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $actor = $request->user();
        $newRole = $data['role_id'] ? RbacRole::find($data['role_id']) : null;
        $roleChanged = $user->role_id !== $data['role_id'];
        $statusChanged = $user->is_active !== $data['is_active'];

        // Section 16 : un utilisateur ne peut jamais modifier son propre rôle ou son propre statut.
        if ($actor->id === $user->id && ($roleChanged || $statusChanged)) {
            throw ValidationException::withMessages(['role_id' => 'Vous ne pouvez pas modifier votre propre rôle ou statut.']);
        }

        // Section 16 : impossible de s'auto-attribuer (ou d'attribuer à un tiers) le rôle Super
        // Admin si l'on n'est pas déjà Super Admin soi-même.
        if ($newRole?->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            throw ValidationException::withMessages(['role_id' => 'Seul un Super Admin peut attribuer le rôle Super Admin.']);
        }

        // Section 16 : impossible de modifier un compte dont le rôle est supérieur au sien, ni de
        // lui attribuer un rôle supérieur au sien.
        if (! $actor->isSuperAdmin()) {
            $actorRank = $this->actorRank($actor);
            $targetCurrentRank = $user->roleModel?->rank() ?? 0;
            $targetNewRank = $newRole?->rank() ?? 0;

            if ($targetCurrentRank >= $actorRank || $targetNewRank >= $actorRank) {
                throw ValidationException::withMessages(['role_id' => 'Vous ne pouvez pas attribuer un rôle égal ou supérieur au vôtre.']);
            }
        }

        // Le dernier Super Admin ne peut jamais perdre ce statut ni être désactivé.
        if ($user->isSuperAdmin() && (($roleChanged && ! $newRole?->isSuperAdmin()) || ($statusChanged && ! $data['is_active']))) {
            $remainingSuperAdmins = User::where('id', '!=', $user->id)
                ->where(function ($q) {
                    $q->where('role', Role::SuperAdmin)->orWhereHas('roleModel', fn ($q2) => $q2->where('slug', RbacRole::SUPER_ADMIN));
                })
                ->count();

            if ($remainingSuperAdmins === 0) {
                throw ValidationException::withMessages(['role_id' => 'Impossible : ce compte est le dernier Super Admin.']);
            }
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role_id' => $data['role_id'] ?? null,
            'is_active' => $data['is_active'],
        ]);

        // Synchronise l'ancien enum hiérarchique pour que les 14 Policies historiques restent
        // cohérentes avec le nouveau rôle RBAC attribué (voir app/Models/User.php).
        if ($newRole) {
            $user->role = match ($newRole->slug) {
                RbacRole::SUPER_ADMIN => Role::SuperAdmin,
                'administrateur' => Role::Admin,
                'gestionnaire-boutique' => Role::Gestionnaire,
                default => $user->role === Role::Client ? Role::Client : $user->role,
            };
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        // Un compte équipe (rôle RBAC attribué) n'a pas besoin de vérifier son email, que ce soit
        // dès la création ou plus tard via cette page — sinon un client promu à un rôle depuis son
        // compte non vérifié resterait bloqué à la connexion malgré son nouveau rôle.
        if ($user->role_id && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        if ($roleChanged) {
            ActivityLog::record('users', 'updated', sprintf(
                '%s a modifié le rôle de %s (%s)',
                $actor->name,
                $user->name,
                $newRole?->name ?? 'Aucun rôle',
            ), $user);
        }

        return redirect()->route('admin.users.index')->with('status', 'Compte mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        ActivityLog::record('users', 'deleted', auth()->user()->name.' a supprimé le compte '.$user->name, null, ['deleted_user_email' => $user->email]);

        return redirect()->route('admin.users.index')->with('status', 'Compte supprimé.');
    }

    /**
     * Rôles RBAC que $actor peut attribuer (à la création ou en modification) — un Super Admin
     * peut tout attribuer, y compris Super Admin ; les autres ne peuvent attribuer qu'un rôle de
     * rang strictement inférieur au leur, et jamais Super Admin (section 16 du cahier des charges).
     *
     * @return \Illuminate\Support\Collection<int, RbacRole>
     */
    private function assignableRbacRoles(User $actor): \Illuminate\Support\Collection
    {
        return RbacRole::orderBy('name')->get()->filter(function (RbacRole $role) use ($actor) {
            if ($actor->isSuperAdmin()) {
                return true;
            }

            if ($role->isSuperAdmin()) {
                return false;
            }

            return $role->rank() < $this->actorRank($actor);
        })->values();
    }

    private function actorRank(User $actor): int
    {
        return $actor->roleModel?->rank() ?? ($actor->isAdmin() ? 90 : ($actor->isGestionnaire() ? 50 : 0));
    }
}

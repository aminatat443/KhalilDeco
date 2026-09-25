<?php

namespace App\Http\Middleware;

use App\Services\SecurityMonitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique une permission RBAC granulaire au niveau de la route (section 13 du cahier des
 * charges : un utilisateur sans la permission doit être bloqué même en accédant directement à
 * l'URL, pas seulement en lui cachant le bouton). Complète — ne remplace pas — les Policies
 * existantes, qui restent la seule protection pour les comptes qui n'ont pas encore de rôle RBAC
 * (role_id null, couverts par l'ancienne hiérarchie isAdmin()/isGestionnaire()).
 *
 * Usage : Route::...->middleware('permission:orders.cancel')
 */
class EnsureUserHasPermission
{
    public function __construct(private readonly SecurityMonitor $security)
    {
    }

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Comptes historiques sans rôle RBAC : la permission granulaire ne s'applique pas à eux,
        // ils restent gouvernés par la Policy du contrôleur (ancienne hiérarchie).
        if ($user && $user->role_id === null) {
            return $next($request);
        }

        if (! $user || ! $user->hasPermission($permission)) {
            $this->security->recordUnauthorizedAccess($request, "Tentative d'accès sans la permission « {$permission} » ({$request->path()})");

            abort(403, "Vous n'avez pas la permission nécessaire pour cette action.");
        }

        return $next($request);
    }
}

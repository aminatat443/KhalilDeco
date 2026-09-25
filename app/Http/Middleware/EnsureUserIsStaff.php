<?php

namespace App\Http\Middleware;

use App\Services\SecurityMonitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    public function __construct(private readonly SecurityMonitor $security)
    {
    }

    /**
     * Réserve le back-office aux rôles Gestionnaire / Administrateur / Super Administrateur
     * (docs/SPEC.md §2.5). Le Client n'a jamais accès à /admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStaffMember() || ! $user->is_active) {
            $this->security->recordUnauthorizedAccess($request, "Tentative d'accès au back-office ({$request->path()})");

            abort(403, "Accès réservé à l'équipe Khalil Déco.");
        }

        return $next($request);
    }
}

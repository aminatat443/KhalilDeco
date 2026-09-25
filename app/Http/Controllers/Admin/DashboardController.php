<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Dashboards\AccountingDashboardService;
use App\Services\Dashboards\AdminDashboardService;
use App\Services\Dashboards\CashierDashboardService;
use App\Services\Dashboards\CustomerServiceDashboardService;
use App\Services\Dashboards\InventoryDashboardService;
use App\Services\Dashboards\MarketingDashboardService;
use App\Services\Dashboards\StoreManagerDashboardService;
use App\Support\DashboardPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Un tableau de bord par métier, pas le même écran pour tout le monde (cahier des charges
     * « système professionnel multi-rôles ») — le rôle RBAC (ou, à défaut, l'ancien rôle
     * hiérarchique) détermine quel service de données et quelle vue sont utilisés. Super Admin et
     * Administrateur partagent le cockpit global ; un rôle personnalisé sans équivalent connu
     * reçoit par défaut le tableau de bord "Gestion de boutique" (le plus généraliste).
     */
    public function index(Request $request): View|JsonResponse
    {
        $period = DashboardPeriod::resolve(
            $request->input('period'),
            $request->input('from'),
            $request->input('to'),
        );

        $user = $request->user();
        $roleSlug = $user->roleModel?->slug ?? $this->legacyRoleSlug($user);

        [$view, $data] = match ($roleSlug) {
            'super-admin', 'administrateur' => ['admin.dashboard.super-admin', app(AdminDashboardService::class)->build($period)],
            'comptable' => ['admin.dashboard.accounting', app(AccountingDashboardService::class)->build($period)],
            'caissier' => ['admin.dashboard.cashier', app(CashierDashboardService::class)->build($period)],
            'gestionnaire-stock' => ['admin.dashboard.inventory', app(InventoryDashboardService::class)->build($period)],
            'responsable-marketing' => ['admin.dashboard.marketing', app(MarketingDashboardService::class)->build($period)],
            'service-client' => ['admin.dashboard.customer-service', app(CustomerServiceDashboardService::class)->build($period)],
            default => ['admin.dashboard.store-manager', app(StoreManagerDashboardService::class)->build($period)],
        };

        // Changement de période sans rechargement complet (sections 7/10 du cahier des charges
        // d'harmonisation UI/UX) — même mécanisme ajaxFilter que le reste de l'admin : seul le
        // contenu (partials.*-content) est renvoyé, jamais la mise en page complète.
        if ($request->ajax()) {
            $partial = str_replace('admin.dashboard.', 'admin.dashboard.partials.', $view).'-content';

            return response()->json(['html' => view($partial, $data)->render()]);
        }

        return view($view, $data);
    }

    /**
     * Correspondance pour les comptes historiques sans rôle RBAC (role_id null) — reprend le
     * même mapping que celui utilisé lors de la synchronisation faite par RbacSeeder.
     */
    private function legacyRoleSlug(User $user): string
    {
        return match ($user->role?->value) {
            'super_admin' => 'super-admin',
            'admin' => 'administrateur',
            'gestionnaire' => 'gestionnaire-boutique',
            default => 'gestionnaire-boutique',
        };
    }
}

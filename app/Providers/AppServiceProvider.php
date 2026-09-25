<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Favorite;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Horodatage de connexion (back-office "Utilisateurs") — couvre les trois parcours de
        // connexion (email/mot de passe, Google, lien de vérification) sans dupliquer la logique
        // dans chaque contrôleur, puisque Laravel déclenche cet événement dans les trois cas.
        Event::listen(Login::class, function (Login $event) {
            $event->user->update(['last_login_at' => now()]);
        });

        // Le header (mega menu + compteur panier) a besoin de ces données sur toutes les pages
        // publiques — on les injecte via un composer plutôt que de les répéter dans chaque contrôleur.
        View::composer(['components.header', 'components.footer'], function ($view) {
            $view->with([
                'navCategories' => Category::whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                    ->get(),
                'cartCount' => app(CartService::class)->count(),
            ]);
        });

        // Le panier flottant (drawer) est hydraté côté client (store Alpine) avec cet état initial,
        // puis mis à jour par fetch à chaque ajout — pas de rechargement de page (docs/SPEC.md).
        View::composer('components.header', function ($view) {
            $view->with('cartSummary', app(CartService::class)->summary());
        });

        // Favoris connus du serveur pour le client connecté — fusionnés avec un éventuel
        // localStorage pré-connexion par $store.favorites.hydrate() ; un invité reçoit `null` et
        // reste sur son localStorage seul (pas de compte à qui rattacher un favori serveur).
        View::composer('components.header', function ($view) {
            $view->with('favoritesSummary', auth()->check() ? Favorite::summaryFor(auth()->id()) : null);
        });
    }
}

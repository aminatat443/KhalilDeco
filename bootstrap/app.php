<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff' => \App\Http\Middleware\EnsureUserIsStaff::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
        ]);

        // Render (et la plupart des hébergeurs PaaS) termine le HTTPS à son propre niveau puis
        // transmet la requête en HTTP au conteneur — sans ceci, Laravel ne sait jamais que la
        // requête d'origine était en HTTPS et génère des URL d'assets (CSS/JS via @vite, mais
        // aussi route()/asset() partout ailleurs) en http:// sur une page servie en https://,
        // que le navigateur bloque silencieusement (contenu mixte) : la page HTML s'affiche mais
        // aucune feuille de style ni script ne charge. On ne connaît pas l'IP du proxy Render à
        // l'avance (elle peut changer), donc '*' — on fait confiance à la plateforme d'hébergement
        // elle-même, pas à un intermédiaire arbitraire.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);

        // Volet prévention (IPS) de la surveillance applicative — avant toute route, sur
        // l'ensemble du site.
        $middleware->web(append: [\App\Http\Middleware\CheckBlockedIp::class]);

        // Notifications serveur à serveur des prestataires de paiement — aucun jeton CSRF
        // possible, ce ne sont pas des navigateurs qui appellent ces routes. L'authenticité de
        // chacune est vérifiée autrement (signature HMAC pour PayTech et Wave, vérification
        // active auprès d'Orange Money/PayDunya — voir les services correspondants).
        // admin/products/*/discard-if-pristine : appelée via navigator.sendBeacon quand l'admin
        // quitte un brouillon "Nouveau produit" jamais retouché (voir Admin\ProductController
        // ::discardIfPristine()) — sendBeacon ne permet pas d'en-tête personnalisé, donc pas de
        // jeton CSRF possible. Sans risque : l'action ne fait que supprimer un brouillon déjà
        // vide et jamais modifié, jamais un produit réel.
        $middleware->validateCsrfTokens(except: ['payment/ipn', 'payment/wave/webhook', 'payment/orange-money/notif', 'payment/paydunya/callback', 'admin/products/*/discard-if-pristine']);

        // Pas de page de connexion dédiée (fenêtre flottante uniquement) — un invité qui tente
        // d'accéder à une page protégée est renvoyé à l'accueil avec la modale de connexion ouverte.
        $middleware->redirectGuestsTo(fn () => route('home', ['login' => 1]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Session expirée (jeton CSRF invalide, ex. onglet resté ouvert trop longtemps puis
        // action déclenchée, comme "Se déconnecter") : plutôt que la page technique "419 Page
        // Expired", on déconnecte proprement et on renvoie à l'accueil avec la modale de
        // connexion ouverte, même mécanisme que pour un invité sur une page protégée.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            // Capturé avant invalidate() (qui efface aussi _previous.url) pour renvoyer sur la
            // page où l'action a échoué (ex. recherche filtrée) plutôt que de perdre ce contexte
            // en revenant systématiquement à l'accueil.
            $previousUrl = url()->previous();

            \Illuminate\Support\Facades\Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $isSameSite = str_starts_with($previousUrl, url('/'));
            $target = $isSameSite ? $previousUrl : route('home');
            $separator = str_contains($target, '?') ? '&' : '?';

            return redirect($target.$separator.'login=1');
        });

        // Permission refusée dans le back-office (section 15 du cahier des charges
        // multi-rôles) : jamais la page technique "403 Forbidden" — on reste dans l'interface,
        // avec une alerte rouge en français, via le même mécanisme que les erreurs de
        // validation ($errors->any(), déjà affiché par layouts.admin). Scopé à /admin pour ne
        // pas toucher le blocage IP (CheckBlockedIp), qui s'applique à tout le site et doit
        // garder son comportement actuel.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $status = match (true) {
                $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException => $e->getStatusCode(),
                default => null,
            };

            if ($status !== 403 || ! $request->is('admin*')) {
                return null;
            }

            $message = "Accès refusé : vous n'avez pas les permissions nécessaires pour effectuer cette action.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            // Le referer de LA REQUÊTE REFUSÉE elle-même (pas url()->previous(), qui suit la
            // session et peut avoir été écrasé entre-temps par un fetch d'arrière-plan comme le
            // sondage des notifications) — la page réellement affichée quand le clic a eu lieu.
            $referer = $request->headers->get('referer');
            $isSameSite = $referer && str_starts_with($referer, url('/')) && $referer !== $request->fullUrl();
            $target = $isSameSite ? $referer : route('admin.dashboard');

            return redirect($target)->withErrors(['permission' => $message]);
        });
    })->create();

{{-- Charge en AJAX les pages "modale flottante" du back-office (création/modification —
     layouts/admin-modal.blade.php) sans jamais recharger la page en dessous (liste, filtres,
     scroll conservés). Déclenché depuis n'importe quel tableau via :
     onclick="event.preventDefault(); window.openAdminModal(this.href || '...')"
     L'URL réelle reste poussée dans l'historique (retour/avant du navigateur fonctionnent,
     rechargement direct sur cette URL affiche la page complète normalement — voir la condition
     request()->ajax() dans layouts/admin-modal.blade.php). --}}
<div
    x-data="{
        open: false,
        loading: false,
        html: '',
        async load(url, pushState = true) {
            this.loading = true;
            this.open = true;
            if (pushState) history.pushState({ adminModal: url }, '', url);
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } });
                // Redirection suivie (ex. permission refusée) : le contenu récupéré n'est plus
                // celui attendu, une vraie navigation gère alors correctement l'erreur/le message.
                if (! res.ok || res.redirected) throw new Error('load failed');
                this.html = await res.text();
            } catch (e) {
                window.location = url;
                return;
            }
            this.loading = false;
        },
        close(navigateBack = true) {
            this.open = false;
            this.html = '';
            if (navigateBack && history.state?.adminModal) history.back();
        },
        // Méthode init() et non attribut x-init : Alpine exécute comme un handler toute fonction
        // renvoyée par l'expression x-init — l'affectation window.openAdminModal = ... aurait été
        // appelée sans argument au chargement (load(undefined) → navigation vers /undefined, 404).
        init() {
            window.openAdminModal = (url) => this.load(url);
            window.addEventListener('popstate', (e) => {
                if (e.state?.adminModal) { this.load(e.state.adminModal, false); }
                else { this.open = false; this.html = ''; }
            });
        },
    }"
    x-show="open"
    x-cloak
    @keydown.escape.window="if (! window.__nestedOverlayOpen) close()"
    class="fixed inset-0 z-[90]"
>
    <div class="absolute inset-0 bg-secondary-shade/40" @click="close()"></div>

    <div class="relative flex h-full items-start justify-center overflow-y-auto px-4 py-10 sm:py-16">
        <div class="relative w-full max-w-3xl border border-secondary-shade/10 bg-white shadow-sm dark:border-white/10 dark:bg-[#16201f]">
            <button type="button" @click="close()" class="absolute right-5 top-5 z-10 flex h-9 w-9 items-center justify-center border border-secondary-shade/10 text-secondary-shade/60 transition hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-white/50 dark:hover:text-primary" aria-label="Fermer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <div x-show="loading" class="flex items-center justify-center p-16">
                <i class="fa-solid fa-circle-notch fa-spin text-2xl text-primary"></i>
            </div>

            <div x-show="! loading" x-html="html"></div>
        </div>
    </div>
</div>

@props(['product', 'variant' => null, 'images'])

@php
    // Gestionnaire de photos réutilisable — soit la galerie générique d'un produit ($variant
    // omis), soit les photos propres à une variante exacte ($variant fourni). Mêmes
    // fonctionnalités dans les deux cas (glisser-déposer, aperçu plein écran, principale,
    // suppression, envoi multiple) ; seule la route de réordonnancement est calculée ici, les
    // autres (envoi, principale, suppression) sont construites par vignette dans
    // image-grid.blade.php, qui reçoit lui aussi $product/$variant via ce même @include.
    $reorderUrl = $variant
        ? route('admin.products.variants.images.reorder', [$product, $variant])
        : route('admin.products.images.reorder', $product);
@endphp

<div
    x-data="{
        dragId: null,
        uploadError: null,
        previewUrl: null,
        // Réordonnancement au doigt (tablette/mobile) — le drag-and-drop HTML5 natif
        // (dragstart/dragover/drop) ne déclenche aucun événement tactile, seulement la
        // souris. On le remplace par les Pointer Events, qui couvrent souris ET tactile de
        // la même façon, déclenchés depuis une poignée dédiée (touch-none) pour ne pas
        // intercepter le défilement de la page quand on parcourt les vignettes au doigt.
        startDrag(e, id) {
            e.preventDefault();
            this.dragId = id;
            const grid = this.$refs.grid;
            const move = (ev) => {
                const dragged = grid.querySelector(`[data-image-id='${this.dragId}']`);
                const target = document.elementFromPoint(ev.clientX, ev.clientY)?.closest('[data-image-id], [data-drop-end]');
                if (! dragged || ! target || target === dragged) return;
                if (target.hasAttribute('data-drop-end')) {
                    grid.insertBefore(dragged, target);
                } else {
                    const rect = target.getBoundingClientRect();
                    target[ev.clientX < rect.left + rect.width / 2 ? 'before' : 'after'](dragged);
                }
            };
            const stop = () => {
                window.removeEventListener('pointermove', move);
                window.removeEventListener('pointerup', stop);
                window.removeEventListener('pointercancel', stop);
                this.persist();
            };
            window.addEventListener('pointermove', move);
            window.addEventListener('pointerup', stop);
            window.addEventListener('pointercancel', stop);
        },
        async persist() {
            const ids = [...this.$refs.grid.querySelectorAll('[data-image-id]')].map(el => el.dataset.imageId);
            const response = await fetch('{{ $reorderUrl }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                body: JSON.stringify({ ids }),
            });
            if (response.ok) {
                // Le badge principale suit l image glissée en première position sans
                // attendre un rechargement de page (sort_order détermine déjà la
                // principale côté serveur, seul l affichage devait se mettre à jour).
                this.$refs.grid.innerHTML = (await response.json()).html;
            }
        },
        async upload(e) {
            this.uploadError = null;
            const form = e.target;
            const body = new FormData(form);
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                    body,
                });
                const data = await response.json();
                if (!response.ok) {
                    this.uploadError = Object.values(data.errors ?? {})[0]?.[0] ?? 'Envoi impossible.';
                    return;
                }
                this.$refs.grid.innerHTML = data.html;
            } catch (err) {
                this.uploadError = 'Envoi impossible.';
            }
        },
        async setPrimary(e) {
            const response = await fetch(e.target.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                body: new FormData(e.target),
            });
            if (response.ok) {
                this.$refs.grid.innerHTML = (await response.json()).html;
            }
        },
        async destroyImage(e) {
            if (! confirm('Supprimer cette image ?')) return;
            const response = await fetch(e.target.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                body: new FormData(e.target),
            });
            if (response.ok) {
                this.$refs.grid.innerHTML = (await response.json()).html;
            }
        },
    }"
    x-init="$watch('previewUrl', (value) => window.__nestedOverlayOpen = !!value)"
>
    <div x-ref="grid" class="mt-5 grid grid-cols-5 gap-3 sm:grid-cols-6 lg:grid-cols-8">
        @include('admin.products.partials.image-grid', ['images' => $images])
    </div>
    <p x-show="uploadError" x-cloak x-text="uploadError" class="mt-2 text-xs text-red-600 dark:text-red-400"></p>
    <p class="mt-2 text-xs text-grey dark:text-white/40">Cliquez sur une vignette pour l'agrandir. Glissez la poignée en haut à droite d'une vignette pour changer l'ordre — la première est la photo principale. Cliquez sur la tuile "+" pour choisir plusieurs photos à la fois (ou déposez-les dessus), l'envoi se lance automatiquement.</p>

    {{-- Vue agrandie d'une vignette (mêmes vignettes que products/show.blade.php) --}}
    <div
        x-show="previewUrl"
        x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @click="previewUrl = null"
        @keydown.escape.window="previewUrl = null"
        class="fixed inset-0 z-[120] flex items-center justify-center bg-secondary-shade/90 p-6"
    >
        <button type="button" @click="previewUrl = null" class="absolute right-6 top-6 text-white transition hover:text-primary" aria-label="Fermer">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>
        <img :src="previewUrl" alt="" class="max-h-full max-w-full object-contain" @click.stop>
    </div>
</div>

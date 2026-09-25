@php
    // Catégories rapides affichées avant toute saisie — mêmes permissions que la recherche
    // elle-même (SearchController), pour ne jamais proposer de raccourci vers une page interdite.
    $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
    $quickLinks = collect([
        ['label' => 'Commandes', 'icon' => 'fa-bag-shopping', 'url' => $gate->check('viewAny', \App\Models\Order::class) ? route('admin.orders.index') : null],
        ['label' => 'Produits', 'icon' => 'fa-boxes-stacked', 'url' => $gate->check('viewAny', \App\Models\Product::class) ? route('admin.products.index') : null],
        ['label' => 'Utilisateurs', 'icon' => 'fa-user-group', 'url' => $gate->check('viewAny', \App\Models\User::class) ? route('admin.users.index') : null],
        ['label' => 'Paiements', 'icon' => 'fa-money-check-dollar', 'url' => $gate->check('viewAny', \App\Models\Payment::class) ? route('admin.payments.index') : null],
        ['label' => 'Codes promo', 'icon' => 'fa-tag', 'url' => $gate->check('viewAny', \App\Models\Coupon::class) ? route('admin.coupons.index') : null],
    ])->filter(fn ($link) => $link['url'])->values();
@endphp

<div
    x-data="{
        open: false,
        query: '',
        loading: false,
        groups: [],
        activeIndex: -1,
        searchTimer: null,
        quickLinks: @js($quickLinks),
        launch() {
            this.open = true;
            this.query = '';
            this.groups = [];
            this.activeIndex = -1;
            this.$nextTick(() => this.$refs.commandInput?.focus());
        },
        close() {
            this.open = false;
        },
        flatItems() {
            return this.query.trim().length >= 2 ? this.groups.flatMap((g) => g.items) : this.quickLinks;
        },
        runSearch() {
            clearTimeout(this.searchTimer);

            const q = this.query.trim();
            if (q.length < 2) {
                this.groups = [];
                this.activeIndex = -1;
                this.loading = false;
                return;
            }

            this.loading = true;
            this.searchTimer = setTimeout(() => {
                fetch('{{ route('admin.search') }}?q=' + encodeURIComponent(q), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then((r) => r.json())
                    .then((data) => {
                        let i = 0;
                        (data.groups || []).forEach((g) => g.items.forEach((item) => { item._idx = i++; }));
                        this.groups = data.groups || [];
                        this.activeIndex = this.groups.length ? 0 : -1;
                        this.loading = false;
                    })
                    .catch(() => { this.loading = false; });
            }, 250);
        },
        moveActive(delta) {
            const items = this.flatItems();
            if (! items.length) return;
            const currentPos = this.activeIndex < 0 ? -1 : this.activeIndex;
            this.activeIndex = (currentPos + delta + items.length) % items.length;
            this.$nextTick(() => {
                this.$refs.resultsList?.querySelector('[data-result-index=\'' + this.activeIndex + '\']')?.scrollIntoView({ block: 'nearest' });
            });
        },
        selectActive() {
            const item = this.flatItems()[this.activeIndex];
            if (item) window.location.href = item.url;
        },
    }"
    x-on:open-command-palette.window="launch()"
    x-on:keydown.window="
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            open ? close() : launch();
        }
    "
    x-on:keydown.escape.window="close()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[120] flex items-start justify-center p-4 pt-[12vh] sm:p-6 sm:pt-[15vh]"
>
    <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-secondary-shade/50"></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        @click.outside="close()"
        class="relative flex max-h-[70vh] w-full max-w-xl flex-col overflow-hidden border border-secondary-shade/10 bg-white shadow-xl dark:border-white/10 dark:bg-[#16201f]"
        @keydown.down.prevent="moveActive(1)"
        @keydown.up.prevent="moveActive(-1)"
        @keydown.enter.prevent="selectActive()"
    >
        <div class="flex shrink-0 items-center gap-3 border-b border-secondary-shade/10 px-4 py-3.5 dark:border-white/10">
            <i class="fa-solid fa-magnifying-glass shrink-0 text-secondary-shade/40 dark:text-white/30"></i>
            <input
                type="text"
                x-ref="commandInput"
                x-model="query"
                @input="runSearch()"
                placeholder="Rechercher une commande, un produit, un client…"
                class="min-w-0 flex-1 bg-transparent text-sm text-secondary-shade outline-none placeholder:text-grey/60 dark:text-white dark:placeholder:text-white/30"
                autocomplete="off"
            >
            <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin shrink-0 text-secondary-shade/40 dark:text-white/30"></i>
            <button type="button" @click="close()" aria-label="Fermer" class="shrink-0 text-secondary-shade/40 transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto" x-ref="resultsList">
            {{-- Avant toute saisie : quelques catégories utiles, pour que Ctrl+K ne s'ouvre jamais sur un vide. --}}
            <template x-if="query.trim().length < 2">
                <div class="py-2">
                    <p class="px-4 py-1 text-[10px] font-semibold uppercase tracking-[0.15em] text-grey/70 dark:text-white/30">Accès rapide</p>
                    <template x-for="(link, i) in quickLinks" :key="link.url">
                        <a
                            :href="link.url"
                            :data-result-index="i"
                            @mouseenter="activeIndex = i"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm transition"
                            :class="activeIndex === i ? 'bg-grey-tint text-secondary-shade dark:bg-white/10 dark:text-white' : 'text-secondary-shade dark:text-white/85'"
                        >
                            <i class="fa-solid shrink-0 text-xs text-grey/50 dark:text-white/30" :class="link.icon"></i>
                            <span x-text="link.label"></span>
                        </a>
                    </template>
                    <p x-show="! quickLinks.length" class="px-4 py-6 text-center text-xs text-grey dark:text-white/40">Tapez au moins 2 caractères pour rechercher.</p>
                </div>
            </template>

            <template x-if="query.trim().length >= 2 && ! loading && groups.length === 0">
                <p class="px-4 py-10 text-center text-sm text-grey dark:text-white/40">Aucun résultat pour « <span x-text="query"></span> ».</p>
            </template>

            <template x-if="query.trim().length >= 2">
                <template x-for="group in groups" :key="group.label">
                    <div class="border-b border-secondary-shade/10 py-2 last:border-b-0 dark:border-white/10">
                        <p class="px-4 py-1 text-[10px] font-semibold uppercase tracking-[0.15em] text-grey/70 dark:text-white/30" x-text="group.label"></p>
                        <template x-for="item in group.items" :key="item._idx">
                            <a
                                :href="item.url"
                                :data-result-index="item._idx"
                                @mouseenter="activeIndex = item._idx"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm transition"
                                :class="activeIndex === item._idx ? 'bg-grey-tint text-secondary-shade dark:bg-white/10 dark:text-white' : 'text-secondary-shade dark:text-white/85'"
                            >
                                <i class="fa-solid shrink-0 text-xs text-grey/50 dark:text-white/30" :class="group.icon"></i>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium" x-text="item.title"></span>
                                    <span class="block truncate text-xs text-grey dark:text-white/40" x-text="item.subtitle"></span>
                                </span>
                                <span x-show="item.badge" x-text="item.badge" class="shrink-0 border border-secondary-shade/15 px-2 py-0.5 text-[10px] font-medium text-secondary-shade/70 dark:border-white/15 dark:text-white/50"></span>
                            </a>
                        </template>
                    </div>
                </template>
            </template>
        </div>

        <div class="hidden shrink-0 items-center gap-4 border-t border-secondary-shade/10 px-4 py-2 text-[10px] text-grey/70 dark:border-white/10 dark:text-white/30 sm:flex">
            <span><i class="fa-solid fa-arrow-up"></i> <i class="fa-solid fa-arrow-down"></i> naviguer</span>
            <span><i class="fa-solid fa-turn-down fa-rotate-90"></i> ouvrir</span>
            <span>Échap fermer</span>
        </div>
    </div>
</div>

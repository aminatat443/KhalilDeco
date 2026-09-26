<div
    x-data="{
        open: false,
        unread: 0,
        items: [],
        async load() {
            try {
                const res = await fetch('{{ route('notifications.index') }}', { headers: { Accept: 'application/json' } });
                const data = await res.json();
                this.unread = data.unread_count;
                this.items = data.notifications;
            } catch (e) {}
        },
        async open_() {
            this.open = ! this.open;
            if (this.open) await this.load();
        },
        async visit(item) {
            if (! item.read) {
                await fetch(`/notifications/${item.id}/lu`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                });
                item.read = true;
            }

            // Commande/paiement : ouvre la modale unique plutôt que de naviguer (section 8 du
            // cahier des charges) — les autres types de notification (retours...) gardent la
            // navigation classique.
            if (item.order_id) {
                this.open = false;
                window.dispatchEvent(new CustomEvent('open-order-modal', { detail: { orderId: item.order_id } }));
                return;
            }

            window.location = item.url;
        },
        async markAllRead() {
            await fetch('{{ route('notifications.read-all') }}', {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
            });
            await this.load();
        },
    }"
    x-init="load(); setInterval(() => load(), 5000)"
    class="relative"
    @mouseenter="if (window.matchMedia('(hover: hover)').matches) { open = true; load(); }"
    @mouseleave="if (window.matchMedia('(hover: hover)').matches) { open = false; }"
    @click.outside="open = false"
>
    <button type="button" @click="open_()" class="relative text-secondary-shade transition hover:text-primary dark:text-white/70 dark:hover:text-white" aria-label="Notifications">
        <i class="fa-solid fa-bell text-base"></i>
        <span x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread" class="absolute -right-2 -top-2 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-primary px-1 text-[9px] font-bold text-white"></span>
    </button>

    {{-- pt-3 (plutôt qu'un mt-3 sur la boîte) pour garder la zone de survol continue entre la
         cloche et le panneau — sinon la souris "sort" du survol en traversant l'espace.
         Sur mobile/tablette, le bouton cloche est trop proche du bord droit pour qu'un panneau
         ancré "right-0" de 320px tienne dans l'écran (il débordait à gauche) — on bascule en
         position fixe centrée sous le header ; l'ancrage relatif au bouton ne reprend qu'à
         partir de sm, où l'espace à droite de la cloche est suffisant. --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="fixed left-1/2 top-[120px] z-[100] w-[85vw] max-w-[300px] -translate-x-1/2 sm:absolute sm:left-auto sm:right-0 sm:top-full sm:w-80 sm:max-w-none sm:translate-x-0 sm:pt-3"
    >
        <div class="border border-secondary-shade/10 bg-white shadow-lg dark:border-white/10 dark:bg-[#16201f]">
            <div class="flex items-center justify-between border-b border-secondary-shade/10 px-4 py-3 dark:border-white/10">
                <span class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/80">Notifications</span>
                <button type="button" @click="markAllRead()" class="text-[11px] font-semibold text-primary hover:underline">Tout marquer lu</button>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <template x-if="items.length === 0">
                    <p class="px-4 py-8 text-center text-xs text-grey dark:text-white/40">Aucune notification pour le moment.</p>
                </template>

                <template x-for="item in items" :key="item.id">
                    <button
                        type="button"
                        @click="visit(item)"
                        class="flex w-full items-start gap-3 border-b border-secondary-shade/5 px-4 py-3 text-left transition hover:bg-grey-tint/50 dark:border-white/5 dark:hover:bg-white/5"
                        :class="! item.read && 'bg-primary-tint/30 dark:bg-primary/10'"
                    >
                        <span
                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center text-xs"
                            :class="{
                                'bg-blue-50 text-blue-600': item.tone === 'blue',
                                'bg-amber-50 text-amber-600': item.tone === 'amber',
                                'bg-primary-tint text-primary-shade': item.tone === 'primary',
                                'bg-green-50 text-green-700': item.tone === 'green',
                                'bg-red-50 text-red-600': item.tone === 'red',
                                'bg-grey-tint text-grey': ! item.tone || item.tone === 'neutral',
                            }"
                        >
                            <i class="fa-solid" :class="'fa-' + (item.icon || 'bell').replace('fa-', '')"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-xs font-semibold text-secondary-shade dark:text-white/90" x-text="item.title"></span>
                            <span class="mt-0.5 block truncate text-xs text-grey dark:text-white/50" x-text="item.message"></span>
                            <span class="mt-1 block text-[10px] text-grey/60 dark:text-white/30" x-text="item.created_at"></span>
                        </span>
                        <span x-show="! item.read" class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

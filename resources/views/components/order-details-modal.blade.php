{{-- Modale unique de détail de commande (OrderDetailsModal) — ouverte depuis n'importe quelle
     notification (cloche) via window.dispatchEvent(new CustomEvent('open-order-modal', { detail:
     { orderId } })), jamais par navigation directe. Se rafraîchit toute seule pendant qu'elle
     reste ouverte (polling léger) pour refléter un paiement confirmé sans que l'équipe ait à la
     fermer/rouvrir (section 12 du cahier des charges). --}}
<div
    x-data="{
        open: false,
        loading: false,
        denied: null,
        html: '',
        orderId: null,
        poll: null,
        async load() {
            if (! this.orderId) return;
            try {
                const res = await fetch(`/admin/orders/${this.orderId}/modal`, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (! res.ok) {
                    this.denied = data.message || 'Accès refusé.';
                    this.html = '';
                    return;
                }
                this.denied = null;
                this.html = data.html;
            } catch (e) {}
        },
        async openFor(orderId) {
            this.orderId = orderId;
            this.open = true;
            this.loading = true;
            await this.load();
            this.loading = false;
            clearInterval(this.poll);
            this.poll = setInterval(() => this.load(), 5000);
        },
        close() {
            this.open = false;
            clearInterval(this.poll);
        },
    }"
    x-on:open-order-modal.window="openFor($event.detail.orderId)"
    x-on:keydown.escape.window="close()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-8"
>
    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-secondary-shade/60" @click="close()"></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="relative flex max-h-[85vh] w-full max-w-2xl flex-col overflow-y-auto bg-white p-6 shadow-xl dark:bg-[#16201f]"
    >
        <button type="button" @click="close()" class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center text-grey/60 transition hover:text-secondary-shade dark:text-white/50 dark:hover:text-white" aria-label="Fermer">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>

        <div x-show="loading" class="flex items-center justify-center py-16">
            <i class="fa-solid fa-circle-notch fa-spin text-2xl text-primary"></i>
        </div>

        <p x-show="! loading && denied" x-cloak class="py-10 text-center text-sm text-red-600 dark:text-red-400" x-text="denied"></p>

        <div x-show="! loading && ! denied" x-html="html"></div>
    </div>
</div>

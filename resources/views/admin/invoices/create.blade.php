@extends('layouts.admin-modal')

@php($modalBack = route('admin.invoices.index'))

@section('title', 'Nouvelle facture')

@section('modal-width', 'max-w-3xl')

@section('modal')

<div x-data="{ tab: 'definitive' }">

    <h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">Nouvelle facture</h1>
    <p class="mt-2 text-sm text-grey dark:text-white/40">Retrouvez la facture définitive d'une commande existante, ou générez un devis pro forma libre.</p>

    <div class="mt-6 inline-flex bg-grey-tint p-1 text-xs font-semibold dark:bg-white/5">
        <button type="button" @click="tab = 'definitive'" :class="tab === 'definitive' ? 'bg-secondary-shade text-white' : 'text-grey hover:text-secondary-shade dark:text-white/50 dark:hover:text-white'" class="px-4 py-2 uppercase tracking-[0.08em] transition">Facture définitive</button>
        <button type="button" @click="tab = 'proforma'" :class="tab === 'proforma' ? 'bg-secondary-shade text-white' : 'text-grey hover:text-secondary-shade dark:text-white/50 dark:hover:text-white'" class="px-4 py-2 uppercase tracking-[0.08em] transition">Facture pro forma</button>
    </div>

    {{-- Facture définitive : recherche d'une commande existante --}}
    <div
        x-show="tab === 'definitive'"
        x-data="{
            query: '',
            results: [],
            loading: false,
            searched: false,
            timer: null,
            search() {
                clearTimeout(this.timer);
                const q = this.query.trim();
                if (q.length < 2) { this.results = []; this.searched = false; return; }
                this.timer = setTimeout(async () => {
                    this.loading = true;
                    const response = await fetch(`{{ route('admin.invoices.search-orders') }}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
                    const data = response.ok ? await response.json() : { orders: [] };
                    this.results = data.orders;
                    this.loading = false;
                    this.searched = true;
                }, 300);
            },
            open(order) {
                window.dispatchEvent(new CustomEvent('open-invoice-preview', { detail: { url: order.url, label: order.order_number, phone: order.phone } }));
            },
        }"
        class="mt-8"
    >
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Rechercher une commande</label>
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-grey/50"></i>
            <input
                type="text"
                x-model="query"
                @input="search()"
                placeholder="Nom du client, n° de commande ou produit…"
                autocomplete="off"
                autofocus
                class="w-full border border-secondary-shade/15 bg-white py-2.5 pl-9 pr-9 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white"
            >
            <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-grey/50"></i>
        </div>

        <div class="mt-4 divide-y divide-secondary-shade/10 border border-secondary-shade/10 dark:divide-white/10 dark:border-white/10" x-show="results.length">
            <template x-for="order in results" :key="order.id">
                <button type="button" @click="open(order)" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-grey-tint/50 dark:hover:bg-white/5">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-secondary-shade dark:text-white" x-text="order.order_number + ' — ' + order.customer_name"></span>
                        <span class="text-xs text-grey dark:text-white/40" x-text="order.date"></span>
                    </span>
                    <span class="shrink-0 text-sm font-medium text-secondary-shade dark:text-white" x-text="order.total"></span>
                </button>
            </template>
        </div>

        <p x-show="searched && ! loading && ! results.length" x-cloak class="mt-4 text-sm text-grey dark:text-white/40">Aucune commande ne correspond à cette recherche.</p>
    </div>

    {{-- Facture pro forma : document libre, sans commande --}}
    <div
        x-show="tab === 'proforma'"
        x-cloak
        x-data="{
            submitting: false,
            done: null,
            products: {{ Illuminate\Support\Js::from($products) }},
            lines: [{ description: '', quantity: 1, unit_price: 0, searchOpen: false }],
            addLine() { this.lines.push({ description: '', quantity: 1, unit_price: 0, searchOpen: false }); },
            removeLine(i) { this.lines.splice(i, 1); },
            lineTotal(l) { return (l.quantity || 0) * (l.unit_price || 0); },
            get total() { return this.lines.reduce((sum, l) => sum + this.lineTotal(l), 0); },
            format(n) { return new Intl.NumberFormat('fr-FR').format(n || 0); },
            productMatches(line) {
                const q = (line.description || '').trim().toLowerCase();
                const list = q ? this.products.filter(p => p.name.toLowerCase().includes(q)) : this.products;
                return list.slice(0, 8);
            },
            selectProduct(line, product) {
                line.description = product.name;
                line.unit_price = product.price;
                line.searchOpen = false;
            },
            clientQuery: '',
            clientResults: [],
            clientSearchOpen: false,
            selectedClient: null,
            clientSearchTimer: null,
            searchClients() {
                clearTimeout(this.clientSearchTimer);
                if (this.clientQuery.trim().length < 2) {
                    this.clientResults = [];
                    this.clientSearchOpen = false;
                    return;
                }
                this.clientSearchTimer = setTimeout(async () => {
                    const response = await fetch(`{{ route('admin.orders.clients.search') }}?q=${encodeURIComponent(this.clientQuery.trim())}`, {
                        headers: { Accept: 'application/json' },
                    });
                    this.clientResults = response.ok ? await response.json() : [];
                    this.clientSearchOpen = true;
                }, 300);
            },
            selectClient(client) {
                this.selectedClient = client;
                this.$refs.customerName.value = client.name;
                this.$refs.customerPhone.value = client.phone;
                this.$refs.customerEmail.value = client.email;
                this.$refs.customerAddress.value = client.address;
                this.clientQuery = client.name;
                this.clientSearchOpen = false;
            },
            clearClient() {
                this.selectedClient = null;
                this.clientQuery = '';
                this.$refs.customerName.value = '';
                this.$refs.customerPhone.value = '';
                this.$refs.customerEmail.value = '';
                this.$refs.customerAddress.value = '';
                this.$refs.customerName.focus();
            },
            async submit(e) {
                this.submitting = true;
                this.done = null;
                try {
                    const response = await fetch('{{ route('admin.invoices.proforma.store') }}', {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': window.csrfToken() },
                        body: JSON.stringify({
                            customer_name: this.$refs.customerName.value,
                            customer_phone: this.$refs.customerPhone.value,
                            customer_email: this.$refs.customerEmail.value,
                            customer_address: this.$refs.customerAddress.value,
                            notes: this.$refs.notes.value,
                            items: this.lines.map(l => ({ description: l.description, quantity: l.quantity, unit_price: l.unit_price })),
                        }),
                    });
                    const data = await response.json();
                    if (! response.ok) {
                        alert(data.message || 'Impossible de générer le devis — vérifiez les champs.');
                        return;
                    }
                    window.dispatchEvent(new CustomEvent('open-invoice-preview', { detail: { url: data.url, label: data.reference, phone: this.$refs.customerPhone.value } }));
                    this.done = data.reference;
                    this.lines = [{ description: '', quantity: 1, unit_price: 0, searchOpen: false }];
                    this.clearClient();
                    e.target.reset();
                } catch (err) {
                    alert('Une erreur est survenue.');
                } finally {
                    this.submitting = false;
                }
            },
        }"
        class="mt-8"
    >
        <form @submit.prevent="submit($event)" class="space-y-8">
            <div>
                <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">
                    <span class="flex h-6 w-6 items-center justify-center bg-primary-tint text-primary dark:bg-primary/15"><i class="fa-solid fa-user text-[10px]"></i></span>
                    Client
                </h2>

                {{-- Recherche d'un client déjà inscrit — préremplit les champs ci-dessous sans ressaisie --}}
                <div class="relative mt-4" @click.outside="clientSearchOpen = false">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Rechercher un client existant</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-grey/50"></i>
                        <input
                            type="text"
                            x-model="clientQuery"
                            @input="selectedClient = null; searchClients()"
                            @focus="if (clientResults.length) clientSearchOpen = true"
                            placeholder="Nom ou email du client…"
                            autocomplete="off"
                            class="w-full border border-secondary-shade/15 bg-white py-2.5 pl-9 pr-9 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        >
                        <button type="button" x-show="clientQuery" x-cloak @click="clearClient()" class="absolute right-3 top-1/2 -translate-y-1/2 text-grey/50 hover:text-primary" aria-label="Effacer">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>

                    <div
                        x-show="clientSearchOpen"
                        x-cloak
                        class="absolute z-20 mt-1 w-full border border-secondary-shade/10 bg-white shadow-lg dark:border-white/10 dark:bg-[#1c2826]"
                    >
                        <template x-if="clientResults.length === 0">
                            <p class="px-4 py-3 text-xs text-grey dark:text-white/40">Aucun client trouvé — remplissez les champs manuellement.</p>
                        </template>
                        <template x-for="client in clientResults" :key="client.id">
                            <button
                                type="button"
                                @click="selectClient(client)"
                                class="flex w-full flex-col items-start px-4 py-2.5 text-left text-sm transition hover:bg-grey-tint/50 dark:hover:bg-white/5"
                            >
                                <span class="font-medium text-secondary-shade dark:text-white" x-text="client.name"></span>
                                <span class="text-xs text-grey dark:text-white/40" x-text="[client.email, client.phone].filter(Boolean).join(' · ')"></span>
                            </button>
                        </template>
                    </div>

                    <p x-show="selectedClient" x-cloak class="mt-1.5 flex items-center gap-1.5 text-xs text-primary">
                        <i class="fa-solid fa-circle-check"></i>Client existant sélectionné — coordonnées préremplies.
                    </p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nom complet</label>
                        <input x-ref="customerName" type="text" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Téléphone</label>
                        <input x-ref="customerPhone" type="text" placeholder="77 000 00 00" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Email (optionnel)</label>
                        <input x-ref="customerEmail" type="email" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Adresse (optionnel)</label>
                        <input x-ref="customerAddress" type="text" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">
                        <span class="flex h-6 w-6 items-center justify-center bg-primary-tint text-primary dark:bg-primary/15"><i class="fa-solid fa-basket-shopping text-[10px]"></i></span>
                        Articles
                    </h2>
                    <button type="button" @click="addLine()" class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.1em] text-primary hover:text-primary-shade">
                        <i class="fa-solid fa-plus text-[10px]"></i>Ajouter un article
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <template x-for="(line, index) in lines" :key="index">
                        <div class="grid grid-cols-12 items-start gap-3 border border-secondary-shade/10 p-4 dark:border-white/10">
                            <div class="relative col-span-12 sm:col-span-6" @click.outside="line.searchOpen = false">
                                <input
                                    type="text"
                                    x-model="line.description"
                                    @focus="line.searchOpen = true"
                                    @input="line.searchOpen = true"
                                    placeholder="Désignation… (recherche produit ou texte libre)"
                                    autocomplete="off"
                                    required
                                    class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                >
                                <div
                                    x-show="line.searchOpen && productMatches(line).length"
                                    x-cloak
                                    class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto border border-secondary-shade/10 bg-white shadow-lg dark:border-white/10 dark:bg-[#1c2826]"
                                >
                                    <template x-for="p in productMatches(line)" :key="p.name">
                                        <button
                                            type="button"
                                            @click="selectProduct(line, p)"
                                            class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-grey-tint/50 dark:hover:bg-white/5"
                                        >
                                            <span class="truncate text-secondary-shade dark:text-white" x-text="p.name"></span>
                                            <span class="shrink-0 text-xs text-grey dark:text-white/40" x-text="format(p.price) + ' FCFA'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="col-span-4 sm:col-span-2">
                                <input type="number" x-model.number="line.quantity" min="1" placeholder="Qté" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <input type="number" x-model.number="line.unit_price" min="0" placeholder="Prix unitaire" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                            </div>
                            <div class="col-span-2 sm:col-span-1 flex h-full items-center justify-end gap-3 pt-2 sm:pt-0" x-show="lines.length > 1">
                                <button type="button" @click="removeLine(index)" class="text-secondary-shade/40 hover:text-primary dark:text-white/30" aria-label="Retirer cet article">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </div>
                            <div class="col-span-12 text-right text-xs text-grey dark:text-white/40">
                                <span x-text="format(lineTotal(line))"></span> FCFA
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-secondary-shade/10 pt-5 dark:border-white/10">
                    <span class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Total estimé</span>
                    <span class="text-2xl font-semibold text-secondary-shade dark:text-white"><span x-text="format(total)"></span> FCFA</span>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Notes (optionnel)</label>
                <textarea x-ref="notes" rows="2" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white"></textarea>
            </div>

            <p x-show="done" x-cloak class="flex items-center gap-1.5 text-xs text-primary"><i class="fa-solid fa-circle-check"></i>Devis <span x-text="done"></span> généré — aperçu ouvert ci-dessus.</p>

            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:gap-4">
                <a href="{{ route('admin.invoices.index') }}" class="w-full px-8 py-4 text-center text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70 sm:w-auto">Fermer</a>
                <button type="submit" :disabled="submitting" class="w-full whitespace-nowrap bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md disabled:opacity-50 sm:w-auto">
                    <span x-show="! submitting">Générer le devis</span>
                    <span x-show="submitting" x-cloak><i class="fa-solid fa-circle-notch fa-spin mr-1.5"></i>Génération…</span>
                </button>
            </div>
        </form>
    </div>

</div>

@endsection

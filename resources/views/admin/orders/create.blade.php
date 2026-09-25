@extends('layouts.admin-modal')

@php
    $modalBack = route('admin.orders.index');
@endphp

@section('title', 'Vente en boutique')

@section('modal-width', 'max-w-3xl')

@section('modal')

<h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">Vente en boutique</h1>
<p class="mt-2 text-sm text-grey dark:text-white/40">Enregistrez une vente conclue directement en magasin — le stock est décrémenté immédiatement et une facture est générée comme pour une commande en ligne.</p>

<form
    action="{{ route('admin.orders.store') }}"
    method="POST"
    x-data="{
        products: {{ Illuminate\Support\Js::from($products) }},
        lines: {{ Illuminate\Support\Js::from(old('items') ? collect(old('items'))->map(fn ($i) => ['productId' => $i['product_id'] ?? '', 'variantId' => $i['variant_id'] ?? '', 'quantity' => $i['quantity'] ?? 1, 'query' => $products->firstWhere('id', (int) ($i['product_id'] ?? 0))['name'] ?? '', 'searchOpen' => false])->values()->all() : [['productId' => '', 'variantId' => '', 'quantity' => 1, 'query' => '', 'searchOpen' => false]]) }},
        clientQuery: '',
        clientResults: [],
        clientSearchOpen: false,
        selectedClient: null,
        searchTimer: null,
        searchClients() {
            clearTimeout(this.searchTimer);
            if (this.clientQuery.trim().length < 2) {
                this.clientResults = [];
                this.clientSearchOpen = false;
                return;
            }
            this.searchTimer = setTimeout(async () => {
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
            this.clientQuery = client.name;
            this.clientSearchOpen = false;
        },
        clearClient() {
            this.selectedClient = null;
            this.clientQuery = '';
            this.$refs.customerName.value = '';
            this.$refs.customerPhone.value = '';
            this.$refs.customerEmail.value = '';
            this.$refs.customerName.focus();
        },
        addLine() { this.lines.push({ productId: '', variantId: '', quantity: 1, query: '', searchOpen: false }); },
        removeLine(i) { this.lines.splice(i, 1); },
        product(id) { return this.products.find(p => p.id === Number(id)); },
        productMatches(line) {
            const q = (line.query || '').trim().toLowerCase();
            const list = q ? this.products.filter(p => p.name.toLowerCase().includes(q)) : this.products;
            return list.slice(0, 8);
        },
        selectProduct(line, product) {
            line.productId = product.id;
            line.variantId = '';
            line.query = product.name;
            line.searchOpen = false;
        },
        variant(line) {
            const p = this.product(line.productId);
            return p ? p.variants.find(v => v.id === Number(line.variantId)) : null;
        },
        unitPrice(line) {
            const p = this.product(line.productId);
            if (! p) return 0;
            const v = this.variant(line);
            return (v && v.price !== null) ? v.price : p.price;
        },
        lineTotal(line) { return this.unitPrice(line) * (line.quantity || 0); },
        get total() { return this.lines.reduce((sum, l) => sum + this.lineTotal(l), 0); },
        format(n) { return new Intl.NumberFormat('fr-FR').format(n || 0); },
    }"
    class="mt-8 space-y-8"
>
    @csrf

    <div>
        <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">
            <span class="flex h-6 w-6 items-center justify-center bg-primary-tint text-primary dark:bg-primary/15"><i class="fa-solid fa-user text-[10px]"></i></span>
            Client
        </h2>

        <input type="hidden" name="user_id" :value="selectedClient?.id ?? ''">

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
                <i class="fa-solid fa-circle-check"></i>Client existant sélectionné — commande liée à son compte.
            </p>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nom complet</label>
                <input x-ref="customerName" type="text" name="customer_name" value="{{ old('customer_name') }}" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Téléphone</label>
                <input x-ref="customerPhone" type="text" name="customer_phone" value="{{ old('customer_phone') }}" required placeholder="77 000 00 00" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Email (optionnel)</label>
                <input x-ref="customerEmail" type="email" name="customer_email" value="{{ old('customer_email') }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>
        </div>
    </div>

    <div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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
                    <div class="relative col-span-12 sm:col-span-5" @click.outside="line.searchOpen = false">
                        <input type="hidden" :name="`items[${index}][product_id]`" :value="line.productId">
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-grey/50"></i>
                            <input
                                type="text"
                                x-model="line.query"
                                @input="line.productId = ''; line.variantId = ''; line.searchOpen = true"
                                @focus="line.searchOpen = true"
                                placeholder="Rechercher un produit…"
                                autocomplete="off"
                                class="w-full border border-secondary-shade/15 bg-white py-2.5 pl-9 pr-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white"
                            >
                        </div>

                        <div
                            x-show="line.searchOpen"
                            x-cloak
                            class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto border border-secondary-shade/10 bg-white shadow-lg dark:border-white/10 dark:bg-[#1c2826]"
                        >
                            <template x-if="productMatches(line).length === 0">
                                <p class="px-4 py-3 text-xs text-grey dark:text-white/40">Aucun produit trouvé.</p>
                            </template>
                            <template x-for="p in productMatches(line)" :key="p.id">
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

                    <div class="col-span-8 sm:col-span-4">
                        <template x-if="product(line.productId)?.variants.length">
                            <select
                                :name="`items[${index}][variant_id]`"
                                x-model="line.variantId"
                                required
                                class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white"
                            >
                                <option value="">— Choisir une option —</option>
                                <template x-for="v in product(line.productId).variants" :key="v.id">
                                    <option :value="v.id" :disabled="v.stock <= 0" x-text="v.label + (v.stock <= 0 ? ' (rupture)' : ' — ' + v.stock + ' en stock')"></option>
                                </template>
                            </select>
                        </template>
                    </div>

                    <div class="col-span-4 sm:col-span-2">
                        <input
                            type="number"
                            :name="`items[${index}][quantity]`"
                            x-model.number="line.quantity"
                            min="1"
                            required
                            class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        >
                    </div>

                    <div class="col-span-10 sm:col-span-1 flex h-full items-center justify-end pt-2 text-sm font-medium text-secondary-shade dark:text-white sm:justify-center sm:pt-0">
                        <span x-text="format(lineTotal(line))"></span>
                    </div>

                    <div class="col-span-2 flex items-start justify-end pt-2 sm:col-span-12 sm:justify-end sm:pt-0" x-show="lines.length > 1">
                        <button type="button" @click="removeLine(index)" class="text-secondary-shade/40 hover:text-primary dark:text-white/30" aria-label="Retirer cet article">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-5 flex items-center justify-between border-t border-secondary-shade/10 pt-5 dark:border-white/10">
            <span class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Total</span>
            <span class="text-2xl font-semibold text-secondary-shade dark:text-white"><span x-text="format(total)"></span> FCFA</span>
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:gap-4">
        <a href="{{ route('admin.orders.index') }}" class="w-full px-8 py-4 text-center text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70 sm:w-auto">Annuler</a>
        <button type="submit" class="w-full whitespace-nowrap bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md sm:w-auto">
            Enregistrer la vente
        </button>
    </div>
</form>

@endsection

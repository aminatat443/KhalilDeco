@extends('layouts.admin')

@section('title', 'Produits')

@section('content')

<div
    x-data="{ ...ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }}), promoModal: null }"
>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Produits</h1>
        @can('create', App\Models\Product::class)
            <form action="{{ route('admin.products.create') }}" method="POST">
                @csrf
                <button type="submit" class="bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
                    Ajouter un produit
                </button>
            </form>
        @endcan
    </div>

    <form method="GET" @submit.prevent="submitForm($event)" class="mt-8 flex flex-wrap items-center gap-4">
        <input type="hidden" name="stock" value="{{ request('stock') }}">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un produit…" @input.debounce.500ms="$el.form.requestSubmit()" @if(request()->filled('q')) autofocus @endif class="w-64 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        <select name="category_id" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <option value="">Toutes les catégories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @if(request('stock') === 'out')
            <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
                <i class="fa-solid fa-triangle-exclamation"></i>
                En rupture de stock
                <a href="{{ route('admin.products.index', request()->except('stock', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer ce filtre" title="Retirer ce filtre">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </span>
        @elseif(request('stock') === 'low')
            <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
                <i class="fa-solid fa-bell"></i>
                Stock faible
                <a href="{{ route('admin.products.index', request()->except('stock', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer ce filtre" title="Retirer ce filtre">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </span>
        @endif
        <x-admin-filter-reset :route="route('admin.products.index')" />
        <noscript><button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">Filtrer</button></noscript>
        <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
    </form>

    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-6 transition-opacity">
        @include('admin.products.partials.table')
    </div>

    {{-- Modale promotion --}}
    <div
        x-show="promoModal"
        x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[80] flex items-center justify-center p-6"
    >
        <div @click="promoModal = null" class="absolute inset-0 bg-secondary-shade/40"></div>

        <div
            x-show="promoModal"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="relative w-full max-w-sm border border-secondary-shade/10 bg-white p-8 shadow-sm dark:border-white/10 dark:bg-[#16201f]"
        >
            <button type="button" @click="promoModal = null" class="absolute right-5 top-5 text-secondary-shade/50 transition hover:text-primary dark:text-white/40" aria-label="Fermer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <h2 class="text-2xl font-semibold text-secondary-shade dark:text-white" x-text="promoModal?.name"></h2>
            <p class="mt-1 text-xs text-grey dark:text-white/40">
                Prix actuel : <span x-text="promoModal && new Intl.NumberFormat('fr-FR').format(promoModal.price) + ' FCFA'"></span>
            </p>

            <template x-if="promoModal">
                <form :action="`/admin/products/${promoModal.id}/promo`" method="POST" class="mt-6 space-y-5">
                    @csrf
                    <div class="flex gap-2">
                        <button type="button" @click="promoModal.mode = 'percentage'" :class="promoModal.mode === 'percentage' ? 'bg-primary text-white' : 'border border-secondary-shade/20 text-secondary-shade dark:border-white/20 dark:text-white/70'" class="flex-1 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] transition">
                            % Réduction
                        </button>
                        <button type="button" @click="promoModal.mode = 'price'" :class="promoModal.mode === 'price' ? 'bg-primary text-white' : 'border border-secondary-shade/20 text-secondary-shade dark:border-white/20 dark:text-white/70'" class="flex-1 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] transition">
                            Prix promo
                        </button>
                    </div>

                    <input type="hidden" name="mode" :value="promoModal.mode">

                    <div x-show="promoModal.mode === 'percentage'">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Pourcentage de réduction</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="percentage" x-model.number="promoModal.percentage" min="1" max="90" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                            <span class="text-sm text-grey dark:text-white/40">%</span>
                        </div>
                        <p class="mt-2 text-xs text-grey dark:text-white/40">
                            Nouveau prix : <span x-text="promoModal && new Intl.NumberFormat('fr-FR').format(Math.round((promoModal.oldPrice && promoModal.isPromo ? promoModal.oldPrice : promoModal.price) * (1 - (promoModal.percentage || 0) / 100))) + ' FCFA'"></span>
                        </p>
                    </div>

                    <div x-show="promoModal.mode === 'price'">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nouveau prix (FCFA)</label>
                        <input type="number" name="promo_price" x-model.number="promoModal.promoPrice" min="0" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                    </div>

                    <button type="submit" class="w-full bg-primary py-3.5 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary-shade">
                        Appliquer la promotion
                    </button>
                </form>
            </template>

            <template x-if="promoModal?.isPromo">
                <form :action="`/admin/products/${promoModal.id}/promo`" method="POST" class="mt-3">
                    @csrf
                    <input type="hidden" name="remove" value="1">
                    <button type="submit" class="w-full border border-red-200 py-3 text-xs font-semibold uppercase tracking-[0.1em] text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10">
                        Retirer la promotion
                    </button>
                </form>
            </template>
        </div>
    </div>
</div>

@endsection

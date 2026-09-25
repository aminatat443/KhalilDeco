@extends('layouts.admin')

@section('title', 'Mouvements de stock')

@section('content')

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Mouvements de stock</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Enregistrez les entrées et sorties de stock — chaque mouvement met à jour le stock du produit ou de la variante concernée.</p>

@can('create', App\Models\StockMovement::class)
<div
    class="mt-8 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]"
    x-data="{
        products: {{ Illuminate\Support\Js::from($products->map(fn ($p) => [
            'id' => $p->id,
            'label' => $p->name,
            'variants' => $p->variants->map(fn ($v) => ['id' => $v->id, 'label' => $v->label(), 'stock' => $v->stock])->values(),
        ])) }},
        productId: '',
        variantId: '',
        get selectedProduct() { return this.products.find(p => p.id == this.productId) ?? null; },
    }"
>
    <p class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Enregistrer un mouvement</p>

    <form action="{{ route('admin.stock.store') }}" method="POST" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @csrf

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Produit</label>
            <select name="product_id" x-model="productId" @change="variantId = ''" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="">Sélectionner...</option>
                <template x-for="p in products" :key="p.id">
                    <option :value="p.id" x-text="p.label"></option>
                </template>
            </select>
        </div>

        <div x-show="selectedProduct && selectedProduct.variants.length > 0" x-cloak>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Variante</label>
            <select name="product_variant_id" x-model="variantId" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="">Stock global du produit</option>
                <template x-for="v in (selectedProduct ? selectedProduct.variants : [])" :key="v.id">
                    <option :value="v.id" x-text="v.label + ' (stock : ' + v.stock + ')'"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Type</label>
            <select name="type" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="entree">Entrée</option>
                <option value="sortie">Sortie</option>
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Quantité</label>
            <input type="number" name="quantity" min="1" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>

        <div class="sm:col-span-2 lg:col-span-1">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Motif (optionnel)</label>
            <input type="text" name="reason" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>

        <div class="sm:col-span-2 lg:col-span-3">
            <button type="submit" class="bg-primary px-8 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
                Enregistrer le mouvement
            </button>
        </div>
    </form>
</div>
@endcan

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})" class="mt-8">
    <form method="GET" @submit.prevent="submitForm($event)" class="flex flex-wrap items-center gap-3">
        <input type="hidden" name="date" value="{{ request('date') }}">
        <select name="product_id" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3 py-2 text-xs font-medium uppercase tracking-[0.06em] text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
            <option value="">Tous les produits</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        <select name="type" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3 py-2 text-xs font-medium uppercase tracking-[0.06em] text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
            <option value="">Entrées et sorties</option>
            <option value="entree" @selected(request('type') === 'entree')>Entrées</option>
            <option value="sortie" @selected(request('type') === 'sortie')>Sorties</option>
        </select>
        @if(request()->filled('date'))
            <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
                <i class="fa-solid fa-calendar-day"></i>
                {{ \Illuminate\Support\Carbon::parse(request('date'))->translatedFormat('d M Y') }}
                <a href="{{ route('admin.stock.index', request()->except('date', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer le filtre de date" title="Retirer le filtre de date">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </span>
        @endif
        <x-admin-filter-reset :route="route('admin.stock.index')" />
        <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
    </form>

    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-4 transition-opacity">
        @include('admin.stock.partials.list')
    </div>
</div>

@endsection

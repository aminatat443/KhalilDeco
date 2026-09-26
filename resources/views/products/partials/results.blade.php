{{-- Tri (section 22) --}}
<div class="mb-10 flex justify-end">
    <form method="GET" @submit.prevent="submitForm($event)">
        @foreach(['prix_min', 'prix_max', 'nouveautes', 'promotions'] as $field)
            @if(request()->filled($field))
                <input type="hidden" name="{{ $field }}" value="{{ request($field) }}">
            @endif
        @endforeach
        @foreach((array) request('categories', []) as $categoryId)
            <input type="hidden" name="categories[]" value="{{ $categoryId }}">
        @endforeach

        <select name="tri" @change="$el.form.requestSubmit()" class="rounded-lg border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm text-secondary-shade outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <option value="pertinence" @selected(request('tri', 'pertinence') === 'pertinence')>Pertinence</option>
            <option value="nouveautes" @selected(request('tri') === 'nouveautes')>Nouveautés</option>
            <option value="prix_croissant" @selected(request('tri') === 'prix_croissant')>Prix croissant</option>
            <option value="prix_decroissant" @selected(request('tri') === 'prix_decroissant')>Prix décroissant</option>
        </select>
    </form>
</div>

{{-- Grille (section 20 : 2 col mobile / 3 tablette / 4 desktop) --}}
@if($products->isEmpty())
    <div class="flex flex-col items-center justify-center border border-secondary-shade/10 py-24 text-center">
        <i class="fa-solid fa-basket-shopping mb-4 text-2xl text-secondary-shade/20"></i>
        <p class="text-sm text-grey">Aucun produit ne correspond à ces critères pour le moment.</p>
    </div>
@else
    <div class="grid grid-cols-1 gap-x-6 gap-y-14 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
        @foreach($products as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>

    <div class="mt-16" data-pagination>
        {{ $products->links() }}
    </div>
@endif

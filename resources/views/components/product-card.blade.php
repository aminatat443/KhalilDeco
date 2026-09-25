@props(['product'])

@php
    $price = number_format($product->price, 0, ',', ' ');
    $oldPrice = $product->old_price ? number_format($product->old_price, 0, ',', ' ') : null;
    $discount = $product->old_price ? (int) round((1 - $product->price / $product->old_price) * 100) : null;
    $image = img_url($product->images->first()?->url, 500, 625);

    $displayRating = $product->displayRating($product->reviews_avg_rating ?? null);
    $displayCount = $product->displayReviewsCount($product->reviews_count ?? 0);
@endphp

<div x-data class="group relative">

    {{-- Image + badges + favori --}}
    <div class="relative aspect-[4/5] overflow-hidden bg-grey-tint">

        <a href="{{ route('products.show', $product) }}" class="absolute inset-0 z-0">
            @if($image)
                <img src="{{ $image }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.04]">
            @endif
        </a>

        {{-- Réduction en haut à gauche --}}
        @if($discount)
            <span class="pointer-events-none absolute left-3 top-3 z-10 bg-white px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.08em] text-primary">
                -{{ $discount }}%
            </span>
        @endif

        {{-- Favori en haut à droite --}}
        <div class="absolute right-3 top-3 z-10 flex items-center gap-2">
            <button
                type="button"
                @click="$store.favorites.toggle({ id: {{ $product->id }}, name: @js($product->name), price: {{ $product->price }}, image: @js($image), url: @js(route('products.show', $product)) })"
                aria-label="Ajouter aux favoris"
                class="text-secondary-shade drop-shadow-[0_1px_3px_rgba(0,0,0,0.25)] transition hover:scale-110 hover:text-primary"
            >
                <i :class="$store.favorites.isFavorite({{ $product->id }}) ? 'fa-solid text-primary' : 'fa-regular'" class="fa-heart text-[16px]"></i>
            </button>
        </div>

        {{-- Ajout direct au panier au survol, sans variante — le client choisit la taille/couleur
             au moment de la commande plutôt qu'ici (dans le panier, avant validation). L'équipe
             n'a pas de panier côté boutique (voir header.blade.php, icône panier remplacée par
             le raccourci back-office) — le bouton reste visible pour elle (cohérence visuelle),
             mais le clic ne déclenche rien. --}}
        <form
            action="{{ route('cart.add') }}"
            method="POST"
            @submit.prevent="{{ auth()->check() && auth()->user()->isStaffMember() ? '' : '$store.cart.add($el)' }}"
            class="absolute inset-x-0 bottom-0 z-10 translate-y-0 opacity-100 transition duration-300 ease-out lg:translate-y-full lg:opacity-0 lg:group-hover:translate-y-0 lg:group-hover:opacity-100"
        >
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <button
                type="submit"
                class="flex w-full items-center justify-center gap-1.5 bg-primary px-1 py-2.5 text-[10px] font-semibold uppercase leading-tight tracking-[0.02em] text-white transition hover:bg-primary-shade sm:py-3 sm:text-xs sm:tracking-[0.08em]"
            >
                <i class="fa-solid fa-bag-shopping text-[10px]"></i>
                Ajouter
            </button>
        </form>

    </div>

    {{-- Infos --}}
    <a href="{{ route('products.show', $product) }}" class="mt-3.5 block">
        <h3 class="truncate text-[13px] text-secondary-shade transition-colors group-hover:text-primary">{{ $product->name }}</h3>

        <div class="mt-1 flex items-center gap-1.5">
            <span class="flex gap-0.5">
                @for($i = 1; $i <= 5; $i++)
                    <i class="fa-solid fa-star text-[9px] {{ $i <= round($displayRating) ? 'text-primary' : 'text-grey-tint' }}"></i>
                @endfor
            </span>
            <span class="text-[11px] text-grey">({{ $displayCount }})</span>
        </div>

        <div class="mt-1.5 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
            <span class="whitespace-nowrap text-sm font-semibold text-secondary-shade">{{ $price }} FCFA</span>
            @if($oldPrice)
                <span class="whitespace-nowrap text-xs text-grey/50 line-through">{{ $oldPrice }} FCFA</span>
            @endif
        </div>
    </a>

</div>

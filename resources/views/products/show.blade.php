@extends('layouts.app')

@section('title', $product->name.' — Khalil Déco')

@section('content')

<div class="border-b border-secondary-shade/10">
    <nav class="mx-auto max-w-[1440px] overflow-x-auto whitespace-nowrap px-6 py-5 text-[10px] uppercase tracking-[0.06em] text-grey sm:px-10 lg:text-xs lg:tracking-[0.1em]">
        <a href="{{ route('home') }}" class="hover:text-primary">Accueil</a>
        <span class="mx-1.5 lg:mx-2">/</span>
        <a href="{{ route('catalog.show', $product->category) }}" class="hover:text-primary">{{ $product->category->name }}</a>
        <span class="mx-1.5 lg:mx-2">/</span>
        <span class="text-secondary-shade">{{ $product->name }}</span>
    </nav>
</div>

<div class="mx-auto max-w-[1440px] px-6 py-16 sm:px-10">

    @php
        $galleryImages = $product->images->map(fn ($img) => [
            'main' => img_url($img->url, 800, 560, 'pad', 'auto'),
            'thumb' => img_url($img->url, 160, 160),
            'alt' => $img->alt ?? $product->name,
        ]);
        $favoriteImage = img_url($product->images->first()?->url, 500, 625);
    @endphp

    <div
        x-data="{
            attributes: {{ $selectorAttributes->toJson() }},
            variants: {{ $product->variants->map(fn ($v) => ['id' => $v->id, 'stock' => $v->stock, 'values' => $v->attributeValues->pluck('id')->values()])->toJson() }},
            productStock: {{ $product->stock }},
            selected: {},
            get hasStock() {
                if (this.variants.length === 0) return this.productStock > 0;
                if (this.selectedVariant) return this.selectedVariant.stock > 0;
                return this.variants.some(v => v.stock > 0);
            },
            // Quantité précise seulement quand elle est sans ambiguïté (pas de variante, ou une
            // variante précise choisie) — évite d'afficher un nombre qui ne correspondrait à
            // aucune sélection concrète tant que le client n'a pas choisi sa variante.
            get remainingStock() {
                if (this.variants.length === 0) return this.productStock;
                if (this.selectedVariant) return this.selectedVariant.stock;
                return null;
            },
            toggleValue(attributeId, valueId) {
                this.selected[attributeId] = this.selected[attributeId] === valueId ? null : valueId;
            },
            get selectedValueIds() {
                return Object.values(this.selected).filter(v => v !== null && v !== undefined);
            },
            get selectedVariant() {
                if (this.selectedValueIds.length !== this.attributes.length) return null;
                return this.variants.find(v => v.values.length === this.selectedValueIds.length
                    && this.selectedValueIds.every(id => v.values.includes(id))) ?? null;
            },
            availableValueIds(attributeId) {
                const others = Object.entries(this.selected).filter(([id]) => Number(id) !== attributeId && this.selected[id] !== null);
                if (! others.length) return null;
                return this.variants
                    .filter(v => others.every(([id, valueId]) => v.values.includes(valueId)))
                    .flatMap(v => v.values);
            },
            images: {{ $galleryImages->toJson() }},
            active: 0,
            lightbox: false,
            next() { this.active = (this.active + 1) % this.images.length },
            prev() { this.active = (this.active - 1 + this.images.length) % this.images.length },
        }"
        class="grid gap-20 md:grid-cols-2"
    >
        {{-- Galerie (sections 25-26) --}}
        <div class="space-y-3">
            <div class="group relative aspect-[10/7] overflow-hidden bg-grey-tint">
                <template x-for="(img, i) in images" :key="i">
                    <img
                        x-show="active === i"
                        :src="img.main"
                        :alt="img.alt"
                        @click="lightbox = true"
                        class="h-full w-full cursor-zoom-in object-contain transition duration-500 ease-out group-hover:scale-110"
                    >
                </template>

                <template x-if="images.length > 1">
                    <div>
                        <button
                            type="button"
                            @click.stop="prev()"
                            class="absolute left-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center bg-white/90 text-secondary-shade opacity-100 shadow transition hover:text-primary lg:opacity-0 lg:group-hover:opacity-100"
                            aria-label="Image précédente"
                        >
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </button>
                        <button
                            type="button"
                            @click.stop="next()"
                            class="absolute right-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center bg-white/90 text-secondary-shade opacity-100 shadow transition hover:text-primary lg:opacity-0 lg:group-hover:opacity-100"
                            aria-label="Image suivante"
                        >
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                    </div>
                </template>
            </div>

            @if($product->images->count() > 1)
                <div class="grid grid-cols-4 gap-3">
                    <template x-for="(img, i) in images" :key="i">
                        <button
                            type="button"
                            @click="active = i"
                            :class="active === i ? 'ring-2 ring-primary' : 'opacity-70 hover:opacity-100'"
                            class="aspect-square overflow-hidden bg-grey-tint transition"
                        >
                            <img :src="img.thumb" :alt="img.alt" class="h-full w-full object-cover">
                        </button>
                    </template>
                </div>
            @endif
        </div>

        {{-- Vue agrandie --}}
        <div
            x-show="lightbox"
            x-cloak
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click="lightbox = false"
            @keydown.escape.window="lightbox = false"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-secondary-shade/90 p-6"
        >
            <button type="button" @click="lightbox = false" class="absolute right-6 top-6 text-white transition hover:text-primary" aria-label="Fermer">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>
            <template x-if="images.length > 1">
                <button type="button" @click.stop="prev()" class="absolute left-4 top-1/2 -translate-y-1/2 text-white transition hover:text-primary sm:left-8" aria-label="Image précédente">
                    <i class="fa-solid fa-chevron-left text-xl"></i>
                </button>
            </template>
            <template x-for="(img, i) in images" :key="i">
                <img x-show="active === i" @click.stop :src="img.main" :alt="img.alt" class="max-h-[88vh] max-w-[88vw] object-contain">
            </template>
            <template x-if="images.length > 1">
                <button type="button" @click.stop="next()" class="absolute right-4 top-1/2 -translate-y-1/2 text-white transition hover:text-primary sm:right-8" aria-label="Image suivante">
                    <i class="fa-solid fa-chevron-right text-xl"></i>
                </button>
            </template>
        </div>

        {{-- Infos produit --}}
        <div class="md:pt-4">
            @if($product->is_new || $product->is_promo)
                <p class="mb-4 text-xs font-semibold uppercase tracking-[0.2em] {{ $product->is_promo ? 'text-primary' : 'text-secondary-shade' }}">
                    {{ $product->is_promo ? 'Promo' : 'Nouveau' }}
                </p>
            @endif

            <h1 class="text-balance font-display text-3xl font-normal italic leading-tight text-secondary-shade sm:text-4xl">{{ $product->name }}</h1>

            <div class="mt-5 flex items-baseline gap-3">
                <p class="text-xl font-medium text-secondary-shade">{{ number_format($effectivePrice, 0, ',', ' ') }} FCFA</p>
                @if($product->old_price)
                    <p class="text-sm text-grey/50 line-through">{{ number_format($product->old_price, 0, ',', ' ') }} FCFA</p>
                @endif
            </div>

            <p class="mt-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.1em]" :class="hasStock ? 'text-green-600' : 'text-primary-shade'">
                <i class="fa-solid" :class="hasStock ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                <span x-text="hasStock ? 'En stock' : 'Rupture de stock'"></span>
            </p>
            <template x-if="hasStock && remainingStock !== null && remainingStock < 10">
                <p class="mt-1 text-xs font-medium text-primary-shade" x-text="'Plus que ' + remainingStock + ' en stock'"></p>
            </template>

            @if($product->description)
                <p class="mt-6 max-w-md text-sm leading-6 text-grey">{{ $product->description }}</p>
            @endif

            {{-- L'équipe n'a pas de panier côté boutique (voir header.blade.php, icône panier
                 remplacée par le raccourci back-office) — le bouton reste visible et cliquable
                 pour elle (cohérence visuelle), mais l'ajout au panier ne se déclenche pas. --}}
            @php
                $isStaffBrowsing = auth()->check() && auth()->user()->isStaffMember();
            @endphp
            <form action="{{ route('cart.add') }}" method="POST" @submit.prevent="{{ $isStaffBrowsing ? '' : '$store.cart.add($el)' }}" class="mt-10 space-y-8">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                {{-- Attributs de variante (couleur, puissance, longueur...) — dynamiques selon la
                     catégorie du produit (sections 27-28 du cahier des charges) --}}
                <template x-for="attribute in attributes" :key="attribute.id">
                    <div>
                        <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-grey" x-text="attribute.name"></p>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="value in attribute.values" :key="value.id">
                                <button
                                    type="button"
                                    x-show="! availableValueIds(attribute.id) || availableValueIds(attribute.id).includes(value.id)"
                                    @click="toggleValue(attribute.id, value.id)"
                                    :class="attribute.type === 'color'
                                        ? ['h-9 w-9 rounded-full', selected[attribute.id] === value.id ? 'ring-1 ring-offset-2 ring-secondary-shade' : 'ring-1 ring-secondary-shade/15']
                                        : ['border px-4 py-2 text-sm', selected[attribute.id] === value.id ? 'border-secondary-shade text-secondary-shade' : 'border-secondary-shade/15 text-secondary-shade hover:border-secondary-shade/40']"
                                    :style="attribute.type === 'color' ? `background-color: ${value.colorCode}` : ''"
                                    :title="value.value"
                                    x-text="attribute.type === 'color' ? '' : value.value"
                                ></button>
                            </template>
                        </div>
                    </div>
                </template>

                <input type="hidden" name="variant_id" :value="selectedVariant ? selectedVariant.id : ''">

                <template x-if="selectedVariant && selectedVariant.stock <= 0">
                    <p class="text-sm font-medium text-primary-shade">Rupture de stock pour cette variante.</p>
                </template>

                <div class="flex gap-3">
                    <button
                        type="submit"
                        :disabled="! hasStock"
                        class="flex flex-1 items-center justify-center gap-2 bg-primary py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary-shade disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-primary"
                    >
                        <i class="fa-solid fa-bag-shopping"></i>
                        Ajouter au panier
                    </button>
                    <button
                        type="button"
                        @click="$store.favorites.toggle({ id: {{ $product->id }}, name: @js($product->name), price: {{ (int) $effectivePrice }}, image: @js($favoriteImage), url: @js(route('products.show', $product)) })"
                        aria-label="Ajouter aux favoris"
                        class="flex w-14 shrink-0 items-center justify-center border border-secondary-shade/20 text-secondary-shade transition hover:border-primary hover:text-primary"
                    >
                        <i :class="$store.favorites.isFavorite({{ $product->id }}) ? 'fa-solid text-primary' : 'fa-regular'" class="fa-heart text-lg"></i>
                    </button>
                </div>
            </form>

            <ul class="mt-12 grid grid-cols-2 gap-y-3 text-xs text-grey">
                <li class="flex items-center gap-2.5">
                    <i class="fa-solid fa-certificate text-secondary-shade/40"></i>
                    Produit garanti
                </li>
                <li class="flex items-center gap-2.5">
                    <i class="fa-solid fa-truck text-secondary-shade/40"></i>
                    Livraison Sénégal
                </li>
                <li class="flex items-center gap-2.5">
                    <i class="fa-solid fa-lock text-secondary-shade/40"></i>
                    Paiement sécurisé
                </li>
                <li class="flex items-center gap-2.5">
                    <i class="fa-solid fa-headset text-secondary-shade/40"></i>
                    Service client
                </li>
            </ul>
        </div>
    </div>

</div>

{{-- Avis clients (section 39 du cahier des charges) --}}
<section class="border-t border-secondary-shade/10">
    <div class="mx-auto max-w-[1440px] px-6 py-16 sm:px-10">
        <p class="text-xs font-medium uppercase tracking-[0.35em] text-grey">Retours clients</p>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
            <h2 class="font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">Avis clients</h2>
            @php
                $displayRating = $product->displayRating($averageRating);
                $displayReviewsCount = $product->displayReviewsCount($reviews->count());
            @endphp
            <div class="flex items-center gap-2.5 pb-1">
                <span class="flex gap-0.5">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fa-solid fa-star text-[13px] {{ $i <= round($displayRating) ? 'text-primary' : 'text-secondary-shade/15' }}"></i>
                    @endfor
                </span>
                <span class="text-sm font-semibold text-secondary-shade">{{ $displayRating }}</span>
                <span class="text-sm text-grey">({{ $displayReviewsCount }} avis)</span>
            </div>
        </div>

        @if($reviews->isNotEmpty())
            <div class="mt-6 max-w-md space-y-1.5">
                @for($star = 5; $star >= 1; $star--)
                    @php($starCount = $reviews->where('rating', $star)->count())
                    <div class="flex items-center gap-3 text-xs">
                        <span class="flex w-10 shrink-0 items-center gap-1 text-secondary-shade">
                            {{ $star }} <i class="fa-solid fa-star text-[9px] text-primary"></i>
                        </span>
                        <div class="h-1.5 flex-1 overflow-hidden bg-secondary-shade/10">
                            <div class="h-full bg-primary" style="width: {{ $starCount > 0 ? max(3, round(($starCount / $reviews->count()) * 100)) : 0 }}%"></div>
                        </div>
                        <span class="w-5 shrink-0 text-right text-grey">{{ $starCount }}</span>
                    </div>
                @endfor
            </div>
        @endif

        <div class="mt-10 grid gap-10 lg:grid-cols-[1fr_360px]">

            <div class="lg:order-2 lg:sticky lg:top-24 lg:self-start">
                @auth
                    @if($myReview && ! $canEditReview)
                        <div class="border border-secondary-shade/10 p-6 sm:p-8">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-display text-xl italic text-secondary-shade">Votre avis</p>
                                <div class="flex gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star text-[13px] {{ $i <= $myReview->rating ? 'text-primary' : 'text-secondary-shade/15' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            @if($myReview->comment)
                                <p class="mt-3 text-sm leading-relaxed text-grey">{{ $myReview->comment }}</p>
                            @endif
                            <p class="mt-4 text-xs text-grey">Le délai de 30 minutes pour modifier votre avis est dépassé, mais vous pouvez toujours le supprimer.</p>

                            <form action="{{ route('products.reviews.destroy', $product) }}" method="POST" class="mt-4" onsubmit="return confirm('Supprimer votre avis ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full border border-primary/30 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-primary transition hover:bg-primary hover:text-white">
                                    Supprimer mon avis
                                </button>
                            </form>

                            @if(! $myReview->is_approved)
                                <p class="mt-3 text-xs text-grey">Votre avis est en attente de modération.</p>
                            @endif
                        </div>
                    @else
                    <div
                        x-data="{
                            rating: {{ $myReview->rating ?? 0 }},
                            hover: 0,
                            needsRating: false,
                            labels: ['', 'Décevant', 'Moyen', 'Bien', 'Très bien', 'Excellent'],
                        }"
                        class="border border-secondary-shade/10 p-6 sm:p-8"
                    >
                        <p class="font-display text-xl italic text-secondary-shade">
                            {{ $myReview ? 'Modifier mon avis' : 'Laisser un avis' }}
                        </p>
                        <p class="mt-1 text-xs text-grey">
                            @if($myReview)
                                Modifiable encore {{ $myReview->created_at->addMinutes(30)->diffForHumans(null, true) }}.
                            @else
                                Votre expérience compte — dites-nous ce que vous en pensez.
                            @endif
                        </p>

                        <form
                            action="{{ route('products.reviews.store', $product) }}"
                            method="POST"
                            class="mt-6 space-y-5"
                            @submit="if (rating === 0) { $event.preventDefault(); needsRating = true; }"
                        >
                            @csrf
                            <div>
                                <div class="flex justify-center gap-1">
                                    <template x-for="i in 5" :key="i">
                                        <button
                                            type="button"
                                            @click="rating = i; needsRating = false"
                                            @mouseenter="hover = i"
                                            @mouseleave="hover = 0"
                                            class="flex h-12 w-12 items-center justify-center text-4xl transition-transform hover:scale-110"
                                            :class="i <= (hover || rating) ? 'text-primary' : 'text-secondary-shade/15'"
                                        >
                                            <i class="fa-solid fa-star"></i>
                                        </button>
                                    </template>
                                </div>
                                <p class="mt-2 text-center text-xs font-medium uppercase tracking-[0.1em] text-grey" x-text="labels[hover || rating] || 'Sélectionnez une note'"></p>
                            </div>
                            <p x-show="needsRating" x-cloak class="text-center text-xs font-medium text-primary">Choisissez une note avant d'envoyer.</p>
                            <input type="hidden" name="rating" x-model="rating">
                            <textarea name="comment" rows="3" placeholder="Votre commentaire (optionnel)" class="w-full border border-secondary-shade/15 bg-white px-4 py-3 text-sm text-secondary-shade outline-none focus:border-secondary-shade">{{ old('comment', $myReview->comment ?? '') }}</textarea>
                            <button type="submit" class="w-full bg-secondary-shade py-3.5 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">
                                {{ $myReview ? 'Mettre à jour mon avis' : 'Envoyer mon avis' }}
                            </button>
                        </form>

                        @if($myReview)
                            <form action="{{ route('products.reviews.destroy', $product) }}" method="POST" class="mt-3" onsubmit="return confirm('Supprimer votre avis ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full py-2 text-xs font-medium text-grey underline decoration-grey/40 underline-offset-2 transition hover:text-primary">
                                    Supprimer mon avis
                                </button>
                            </form>
                        @endif

                        @if($myReview && ! $myReview->is_approved)
                            <p class="mt-3 text-xs text-grey">Votre avis est en attente de modération.</p>
                        @endif
                    </div>
                    @endif
                @else
                    <div class="border border-secondary-shade/10 p-6 sm:p-8">
                        <i class="fa-solid fa-star text-xl text-primary"></i>
                        <p class="mt-3 font-display text-xl italic text-secondary-shade">Envie de partager votre avis ?</p>
                        <p class="mt-1 text-sm text-grey">
                            <button type="button" @click="$store.ui.openLogin()" class="font-semibold text-secondary-shade hover:text-primary">Connectez-vous</button>
                            pour laisser un avis sur ce produit.
                        </p>
                    </div>
                @endauth
            </div>

            <div class="lg:order-1 space-y-4">
                @forelse($reviews as $review)
                    <div class="flex gap-4 border border-secondary-shade/10 bg-grey-tint/30 p-5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center bg-secondary-shade text-[11px] font-semibold uppercase text-white">
                            {{ \Illuminate\Support\Str::of($review->user->name)->explode(' ')->map(fn($p) => mb_substr($p, 0, 1))->take(2)->join('') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-secondary-shade">{{ $review->user->name }}</p>
                                <div class="flex shrink-0 gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star text-[10px] {{ $i <= $review->rating ? 'text-primary' : 'text-grey-tint' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            @if($review->comment)
                                <p class="mt-1.5 text-sm leading-relaxed text-grey">{{ $review->comment }}</p>
                            @endif
                            @if($review->admin_reply)
                                <div class="mt-3 border-l-2 border-primary/30 bg-white py-2 pl-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-primary-shade">Réponse de Khalil Déco</p>
                                    <p class="mt-1 text-sm leading-relaxed text-grey">{{ $review->admin_reply }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="border border-secondary-shade/10 bg-grey-tint/30 py-14 text-center">
                        <i class="fa-regular fa-comment-dots mb-3 text-2xl text-secondary-shade/20"></i>
                        <p class="text-sm text-grey">Aucun avis pour le moment. Soyez le premier à donner votre avis.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</section>

@if($relatedProducts->isNotEmpty())
    <section class="border-t border-secondary-shade/10">
        <div class="mx-auto max-w-[1440px] px-6 py-16 sm:px-10">
            <p class="text-xs font-medium uppercase tracking-[0.35em] text-grey">Vous pourriez aimer</p>
            <h2 class="mt-3 font-display text-2xl font-normal italic text-secondary-shade sm:text-3xl">Cela pourrait vous intéresser</h2>

            <div class="mt-10">
                <x-horizontal-scroller>
                    @foreach($relatedProducts as $related)
                        <div class="w-[46vw] shrink-0 sm:w-[220px]">
                            <x-product-card :product="$related" />
                        </div>
                    @endforeach
                </x-horizontal-scroller>
            </div>
        </div>
    </section>
@endif

@endsection

@extends('layouts.app')

@section('title', 'Panier — Khalil Déco')

@section('content')
<div x-data class="mx-auto max-w-3xl px-6 py-16 sm:px-10">

    <h1 class="font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">Mon panier</h1>

    <template x-if="$store.cart.items.length === 0">
        <div class="mt-16 flex flex-col items-center justify-center border border-secondary-shade/10 py-20 text-center">
            <i class="fa-solid fa-bag-shopping mb-4 text-2xl text-secondary-shade/20"></i>
            <p class="text-sm text-grey">Votre panier est vide.</p>
            <a href="{{ route('home') }}" class="mt-6 bg-secondary-shade px-6 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">
                Continuer mes achats
            </a>
        </div>
    </template>

    <template x-if="$store.cart.items.length > 0">
        <div>
            <div class="mt-10 divide-y divide-secondary-shade/10 border-t border-secondary-shade/10">
                <template x-for="item in $store.cart.items" :key="item.product_id + ':' + item.variant_id">
                    <div class="py-6">
                        <div class="flex items-center gap-5">
                            <a :href="item.url" class="h-24 w-20 shrink-0 overflow-hidden bg-grey-tint">
                                <img :src="item.image" :alt="item.name" class="h-full w-full object-cover">
                            </a>

                            <div class="flex-1">
                                <a :href="item.url" class="text-sm font-medium text-secondary-shade hover:text-primary" x-text="item.name"></a>
                                <template x-if="item.variant_label">
                                    <p class="mt-1 text-xs text-grey" x-text="item.variant_label"></p>
                                </template>
                                <p class="mt-1 text-xs text-grey" x-text="'Quantité : ' + item.quantity"></p>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <p class="text-sm font-medium text-secondary-shade" x-text="new Intl.NumberFormat('fr-FR').format(item.subtotal) + ' FCFA'"></p>
                                <button type="button" @click="$store.cart.remove(item.product_id, item.variant_id)" class="text-xs font-semibold uppercase tracking-[0.05em] text-primary hover:underline">
                                    Supprimer
                                </button>
                            </div>
                        </div>

                        {{-- Attributs (couleur, taille...) choisis ici, au moment de la commande, plutôt qu'à l'ajout au panier --}}
                        <template x-if="item.needs_variant">
                            <div
                                x-data="{
                                    selected: {},
                                    toggleValue(attributeId, valueId) {
                                        this.selected[attributeId] = this.selected[attributeId] === valueId ? null : valueId;
                                    },
                                    get selectedValueIds() {
                                        return Object.values(this.selected).filter(v => v !== null && v !== undefined);
                                    },
                                    get matchedVariant() {
                                        if (this.selectedValueIds.length !== item.variant_options.attributes.length) return null;
                                        return item.variant_options.variants.find(v => v.values.length === this.selectedValueIds.length
                                            && this.selectedValueIds.every(id => v.values.includes(id))) ?? null;
                                    },
                                }"
                                class="mt-4 bg-primary-tint/40 p-4"
                            >
                                <p class="mb-3 text-xs font-medium text-primary-shade">
                                    <i class="fa-solid fa-circle-info mr-1.5"></i>Choisissez les options de cet article
                                </p>

                                <template x-for="attribute in item.variant_options.attributes" :key="attribute.id">
                                    <div class="mb-3">
                                        <p class="mb-1.5 text-[11px] font-medium uppercase tracking-[0.08em] text-secondary-shade/70" x-text="attribute.name"></p>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="value in attribute.values" :key="value.id">
                                                <button
                                                    type="button"
                                                    @click="toggleValue(attribute.id, value.id)"
                                                    :class="attribute.type === 'color'
                                                        ? ['h-7 w-7 rounded-full', selected[attribute.id] === value.id ? 'ring-2 ring-offset-1 ring-secondary-shade' : 'ring-1 ring-secondary-shade/20']
                                                        : ['border px-3 py-1.5 text-xs', selected[attribute.id] === value.id ? 'border-secondary-shade text-secondary-shade' : 'border-secondary-shade/20 text-secondary-shade']"
                                                    :style="attribute.type === 'color' ? ('background-color: ' + (value.colorCode || '#ccc')) : ''"
                                                    :title="value.value"
                                                    x-text="attribute.type === 'color' ? '' : value.value"
                                                ></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <button
                                    type="button"
                                    x-show="matchedVariant"
                                    @click="if (matchedVariant) $store.cart.chooseVariant(item.product_id, matchedVariant.id)"
                                    class="w-full bg-secondary-shade py-2.5 text-xs font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-primary sm:w-auto sm:px-8"
                                >
                                    Valider
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="mt-8 flex items-center justify-between border-t border-secondary-shade/10 pt-6">
                <span class="text-sm font-medium uppercase tracking-[0.1em] text-secondary-shade">Sous-total</span>
                <span class="font-display text-2xl italic text-secondary-shade" x-text="new Intl.NumberFormat('fr-FR').format($store.cart.subtotal) + ' FCFA'"></span>
            </div>

            <a
                href="{{ route('checkout.index') }}"
                :class="$store.cart.items.some(i => i.needs_variant) ? 'pointer-events-none cursor-not-allowed bg-grey-tint text-grey/60' : 'bg-primary text-white hover:bg-primary-shade'"
                class="mt-8 block px-6 py-4 text-center text-xs font-semibold uppercase tracking-[0.15em] transition"
            >
                Passer la commande
            </a>
            <template x-if="$store.cart.items.some(i => i.needs_variant)">
                <p class="mt-3 text-center text-xs text-grey">Choisissez une taille et une couleur pour chaque article avant de commander.</p>
            </template>
        </div>
    </template>

</div>
@endsection

@extends('layouts.app')

@section('title', ($query ? 'Résultats pour « '.$query.' »' : 'Rechercher').' — Khalil Déco')

@section('content')
<div class="mx-auto max-w-[1600px] px-6 py-16 sm:px-10">

    <div
        x-data="{
            loading: false,
            filtersOpen: false,
            count: {{ $products->total() }},
            async apply(url) {
                this.loading = true;
                this.filtersOpen = false;
                try {
                    const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
                    const data = await response.json();
                    this.$refs.results.innerHTML = data.html;
                    this.count = data.count;
                    window.history.pushState({}, '', url);
                    window.scrollTo({ top: this.$refs.results.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
                } catch (e) {
                    window.location = url;
                } finally {
                    this.loading = false;
                }
            },
            submitForm(e) {
                const form = e.target;
                const params = new URLSearchParams(new FormData(form));
                this.apply(form.action.split('?')[0] + '?' + params.toString());
            },
            onResultsClick(e) {
                const link = e.target.closest('[data-pagination] a');
                if (! link) return;
                e.preventDefault();
                this.apply(link.href);
            },
        }"
    >

    <h1 class="font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">
        @if($query)
            Résultats pour « {{ $query }} »
        @elseif(request()->boolean('nouveautes') && ! request()->boolean('promotions'))
            Nouveautés
        @elseif(request()->boolean('promotions') && ! request()->boolean('nouveautes'))
            Promotions
        @else
            Nos produits
        @endif
        <span class="font-sans text-sm not-italic text-grey">(<span x-text="count"></span>)</span>
    </h1>

    <button
        type="button"
        @click="filtersOpen = true"
        class="mt-6 mb-6 flex items-center gap-2 border border-secondary-shade/15 px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:border-secondary-shade lg:hidden dark:border-white/15 dark:text-white/80"
    >
        <i class="fa-solid fa-sliders text-primary"></i>
        Filtres
    </button>

    <div class="mt-10 grid gap-16 lg:grid-cols-[260px_1fr]">

        {{-- Filtres (section 21) --}}
        <div
            x-show="filtersOpen"
            x-cloak
            @click="filtersOpen = false"
            class="fixed inset-0 z-[70] bg-secondary-shade/40 lg:hidden"
        ></div>

        <aside
            :class="filtersOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-[75] h-full w-[80%] max-w-xs overflow-y-auto transition-transform duration-300 lg:static lg:z-auto lg:h-fit lg:w-auto lg:max-w-none lg:translate-x-0 lg:transition-none"
        >
            <form
                method="GET"
                x-data="{ active: {{ (request()->boolean('nouveautes') ? 1 : 0) + (request()->boolean('promotions') ? 1 : 0) + (request()->filled('prix_min') || request()->filled('prix_max') ? 1 : 0) }} }"
                @submit.prevent="submitForm($event)"
                :class="loading && 'opacity-50'"
                class="h-full space-y-8 border border-secondary-shade/10 bg-white p-6 shadow-sm transition-opacity lg:h-auto dark:border-white/10 dark:bg-[#16201f]"
            >
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/80">
                        <i class="fa-solid fa-sliders text-primary"></i>Filtres
                    </h2>
                    <div class="flex items-center gap-3">
                        <span x-show="active > 0" x-cloak x-text="active" class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white"></span>
                        <button type="button" @click="filtersOpen = false" class="text-secondary-shade/50 hover:text-primary lg:hidden dark:text-white/40" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div class="border-t border-secondary-shade/10 pt-6 dark:border-white/10">
                    <h3 class="mb-5 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">Prix</h3>
                    <x-price-range-slider :min="$priceFloor" :max="$priceCeil" :selected-min="request('prix_min')" :selected-max="request('prix_max')" />
                </div>

                <div class="space-y-3 border-t border-secondary-shade/10 pt-6 dark:border-white/10">
                    <h3 class="mb-1 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">Collection</h3>

                    <label class="group flex cursor-pointer items-center justify-between py-1">
                        <span class="flex items-center gap-2.5 text-sm text-secondary-shade dark:text-white/80">
                            <i class="fa-solid fa-wand-magic-sparkles text-xs text-grey/50 group-has-[:checked]:text-primary"></i>
                            Nouveautés
                        </span>
                        <input type="checkbox" name="nouveautes" value="1" @checked(request('nouveautes')) @change="$el.form.requestSubmit()" class="peer sr-only">
                        <span class="relative h-5 w-9 shrink-0 rounded-full bg-grey-tint transition peer-checked:bg-primary dark:bg-white/10">
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </span>
                    </label>
                    <label class="group flex cursor-pointer items-center justify-between py-1">
                        <span class="flex items-center gap-2.5 text-sm text-secondary-shade dark:text-white/80">
                            <i class="fa-solid fa-tag text-xs text-grey/50 group-has-[:checked]:text-primary"></i>
                            Promotions
                        </span>
                        <input type="checkbox" name="promotions" value="1" @checked(request('promotions')) @change="$el.form.requestSubmit()" class="peer sr-only">
                        <span class="relative h-5 w-9 shrink-0 rounded-full bg-grey-tint transition peer-checked:bg-primary dark:bg-white/10">
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </span>
                    </label>
                </div>

                @if(request()->filled('q'))
                    <input type="hidden" name="q" value="{{ $query }}">
                @endif
                <input type="hidden" name="tri" value="{{ request('tri') }}">

                @if(request()->filled('prix_min') || request()->filled('prix_max') || request()->boolean('nouveautes') || request()->boolean('promotions'))
                    <a href="{{ route('search', request()->filled('q') ? ['q' => $query] : []) }}" @click.prevent="apply($el.href)" class="block border-t border-secondary-shade/10 pt-5 text-center text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-primary dark:border-white/10 dark:text-white/50">
                        Réinitialiser les filtres
                    </a>
                @endif

                <noscript>
                    <button type="submit" class="w-full bg-secondary-shade px-4 py-3.5 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">
                        Filtrer
                    </button>
                </noscript>
            </form>
        </aside>

        <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="transition-opacity">
            @include('products.partials.search-results')
        </div>

    </div>

    </div>

</div>
@endsection

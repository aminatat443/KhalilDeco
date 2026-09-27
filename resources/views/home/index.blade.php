@extends('layouts.app')

@section('title', 'Khalil Déco — Décoration & Quincaillerie')

@section('content')

{{-- Hero principal (section 12) --}}
<section class="mx-auto grid max-w-[1440px] items-center gap-8 px-6 py-12 sm:gap-10 sm:px-10 sm:py-14 md:justify-items-center md:gap-10 md:py-16 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)] lg:justify-items-stretch lg:gap-12 lg:py-8 lg:h-[calc(100vh-8rem)] lg:min-h-[22rem] xl:gap-14 xl:py-10 2xl:gap-16 2xl:py-14">

    <div class="text-center md:max-w-2xl lg:max-w-none lg:text-left">

        <p class="starting:opacity-0 whitespace-nowrap text-xs font-medium uppercase tracking-[0.15em] text-grey opacity-100 transition-all duration-500 sm:tracking-[0.35em]">
            Décoration & quincaillerie
        </p>

        <h1 class="starting:opacity-0 starting:translate-y-4 mt-6 translate-y-0 font-display text-4xl font-normal italic leading-[1.15] text-secondary-shade opacity-100 transition-all duration-700 delay-100 sm:text-6xl lg:text-[4.25rem] xl:text-[5.5rem] 2xl:text-[6rem]">
            Votre projet,
            <br>
            <span class="md:whitespace-nowrap xl:whitespace-normal">notre <span class="whitespace-nowrap text-primary">savoir-faire.</span></span>
        </h1>

        <p class="starting:opacity-0 starting:translate-y-4 mx-auto mt-5 max-w-sm translate-y-0 text-[15px] leading-7 text-grey opacity-100 transition-all duration-700 delay-200 md:max-w-md lg:mx-0 lg:max-w-sm">
            Faux plafonds, quincaillerie, éclairage et décoration — tout pour vos projets d'aménagement intérieur.
        </p>

        <div class="starting:opacity-0 mt-8 flex flex-col flex-wrap items-center justify-center gap-5 opacity-100 transition-all duration-700 delay-300 lg:items-start lg:justify-start xl:flex-row xl:items-center xl:gap-8">
            <a
                href="{{ $universes->first() ? route('catalog.show', $universes->first()) : '#' }}"
                class="whitespace-nowrap bg-primary px-9 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary-shade"
            >
                Découvrir la collection
            </a>

            <a
                href="{{ route('search', ['nouveautes' => 1]) }}"
                class="group whitespace-nowrap text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade"
            >
                Voir les nouveautés
                <span class="mt-1 block h-px w-0 bg-secondary-shade transition-all duration-300 group-hover:w-full"></span>
            </a>
        </div>

    </div>


    <div class="relative mt-8 h-[20rem] w-full md:mt-4 md:h-[22rem] md:max-w-2xl lg:mt-0 lg:h-full lg:max-w-none lg:self-stretch">

        @if($heroSlides->isNotEmpty())
            <div
                x-data="{
                    slides: {{ $heroSlides->toJson() }},
                    active: 0,
                    timer: null,
                    next() { this.active = (this.active + 1) % this.slides.length },
                    prev() { this.active = (this.active - 1 + this.slides.length) % this.slides.length },
                    start() { this.timer = setInterval(() => this.next(), 5000) },
                    stop() { clearInterval(this.timer) },
                }"
                x-init="start()"
                @mouseenter="stop()"
                @mouseleave="start()"
                class="relative h-full overflow-hidden"
            >
                <template x-for="(slide, i) in slides" :key="i">
                    <a
                        :href="slide.url"
                        x-show="active === i"
                        x-transition:enter="transition ease-out duration-1000"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-500"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute inset-0 block overflow-hidden"
                    >
                        {{-- Arrière-plan flouté (agrandi pour masquer ses propres bords nets) —
                             comble tout le cadre sans jamais rogner la vraie photo au centre, qui
                             elle reste entière (voir le commentaire sur "fit" dans HomeController). --}}
                        <img :src="slide.image" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full scale-125 object-cover object-center blur-2xl brightness-75">
                        <img :src="slide.image" :alt="slide.name" class="relative h-full w-full object-contain">
                        <div class="absolute inset-0 bg-gradient-to-b from-black/35 via-transparent to-transparent"></div>
                    </a>
                </template>

                {{-- Prix, en haut à gauche sur l'image --}}
                <div class="pointer-events-none absolute left-6 top-6 bg-white px-4 py-2.5">
                    <p class="text-xs font-medium text-secondary-shade" x-text="slides[active]?.name"></p>
                    <p class="font-display text-sm italic text-primary" x-text="new Intl.NumberFormat('fr-FR').format(slides[active]?.price ?? 0) + ' FCFA'"></p>
                </div>

                {{-- Flèches --}}
                <button
                    @click.prevent="prev()"
                    class="absolute left-4 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center bg-white/90 text-secondary-shade shadow transition hover:bg-white hover:text-primary"
                    aria-label="Image précédente"
                >
                    <i class="fa-solid fa-chevron-left text-sm"></i>
                </button>
                <button
                    @click.prevent="next()"
                    class="absolute right-4 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center bg-white/90 text-secondary-shade shadow transition hover:bg-white hover:text-primary"
                    aria-label="Image suivante"
                >
                    <i class="fa-solid fa-chevron-right text-sm"></i>
                </button>

                {{-- Points de navigation --}}
                <div class="absolute bottom-5 left-1/2 flex -translate-x-1/2 gap-2">
                    <template x-for="(slide, i) in slides" :key="i">
                        <button
                            @click.prevent="active = i"
                            :class="active === i ? 'w-6 bg-white' : 'w-2 bg-white/50'"
                            class="h-[3px] transition-all duration-300"
                            :aria-label="'Aller à l\'image ' + (i + 1)"
                        ></button>
                    </template>
                </div>
            </div>
        @else
            <div class="h-full bg-primary-tint"></div>
        @endif

    </div>

</section>


{{-- Section catégories (section 13) --}}
@if($universes->isNotEmpty())
<section>
    <div class="mx-auto max-w-[1440px] px-6 py-16 sm:px-10">

        <p class="text-xs font-medium uppercase tracking-[0.35em] text-grey">Univers</p>
        <h2 class="mt-3 font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">Explorez nos collections</h2>

        @php
            $universeIcons = [
                'faux-plafonds' => 'fa-house-chimney-window',
                'quincaillerie' => 'fa-screwdriver-wrench',
                'decoration' => 'fa-palette',
                'eclairage' => 'fa-lightbulb',
                'outils-accessoires' => 'fa-toolbox',
            ];
        @endphp

        <div class="mt-10 grid grid-cols-5 gap-px bg-secondary-shade/10">
            @foreach($universes as $universe)
                <a
                    href="{{ route('catalog.show', $universe) }}"
                    class="group flex flex-col items-center justify-center gap-1.5 bg-white px-2 py-3 text-center outline-none transition-colors duration-300 hover:bg-primary-tint/40 focus-visible:bg-primary-tint/40 sm:gap-3 sm:p-4 lg:gap-4 lg:p-6"
                >
                    <i class="fa-solid {{ $universeIcons[$universe->slug] ?? 'fa-star' }} text-xs text-secondary-shade/30 transition group-hover:text-primary sm:text-base lg:text-2xl"></i>
                    <span class="font-display text-[9px] italic leading-tight text-secondary-shade sm:text-sm lg:text-lg">{{ $universe->name }}</span>
                </a>
            @endforeach
        </div>

    </div>
</section>
@endif


{{-- Section Nouveautés (section 14) --}}
@if($newProducts->isNotEmpty())
<section class="border-t border-secondary-shade/10">
    <div class="mx-auto max-w-[1440px] px-6 py-16 sm:px-10">
        <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-[0.35em] text-grey">Fraîchement arrivé</p>
                <h2 class="mt-3 font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">Nouveautés</h2>
            </div>
            <a href="{{ route('search', ['nouveautes' => 1]) }}" class="group text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">
                Tout voir
                <span class="mt-1 block h-px w-0 bg-secondary-shade transition-all duration-300 group-hover:w-full"></span>
            </a>
        </div>

        <x-horizontal-scroller>
            @foreach($newProducts as $product)
                <div class="w-[46vw] shrink-0 sm:w-[220px]">
                    <x-product-card :product="$product" />
                </div>
            @endforeach
        </x-horizontal-scroller>
    </div>
</section>
@endif


{{-- Section Promotions (section 19) --}}
@if($promoProducts->isNotEmpty())
<section class="border-t border-secondary-shade/10">
    <div class="mx-auto max-w-[1440px] px-6 py-16 sm:px-10">

        <div class="mb-10 bg-primary-tint px-6 py-10 text-center sm:px-10 sm:py-12">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-primary-shade">Sale</p>
            <p class="mt-3 font-display text-4xl font-normal italic text-secondary-shade sm:text-5xl md:text-6xl">Jusqu'à -50%</p>
        </div>

        <x-horizontal-scroller>
            @foreach($promoProducts as $product)
                <div class="w-[46vw] shrink-0 sm:w-[220px]">
                    <x-product-card :product="$product" />
                </div>
            @endforeach
        </x-horizontal-scroller>
    </div>
</section>
@endif


{{-- Sections éditoriales par univers (sections 15 à 18) --}}
@foreach($universes as $index => $universe)
    @php($items = $editorials[$universe->slug] ?? collect())
    @continue($items->isEmpty())

    <section class="border-t border-secondary-shade/10">
        <div class="mx-auto max-w-[1440px] px-6 py-16 sm:px-10">
            <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.35em] text-grey">Collection</p>
                    <h2 class="mt-3 font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">{{ $universe->name }}</h2>
                </div>
                <a href="{{ route('catalog.show', $universe) }}" class="group text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">
                    Découvrir {{ $universe->name }}
                    <span class="mt-1 block h-px w-0 bg-secondary-shade transition-all duration-300 group-hover:w-full"></span>
                </a>
            </div>

            <x-horizontal-scroller>
                @foreach($items as $product)
                    <div class="w-[46vw] shrink-0 sm:w-[220px]">
                        <x-product-card :product="$product" />
                    </div>
                @endforeach
            </x-horizontal-scroller>
        </div>
    </section>
@endforeach

@endsection

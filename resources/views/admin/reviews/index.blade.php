@extends('layouts.admin')

@section('title', 'Avis clients')

@section('content')

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Avis clients</h1>

@php
    $totalRated = $ratingCounts->sum();
    $maxRatingCount = max($ratingCounts->max() ?? 0, 1);
@endphp

<div class="mt-8 bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">
        Répartition des notes <span class="text-grey dark:text-white/40">({{ $totalRated }} avis publié{{ $totalRated > 1 ? 's' : '' }})</span>
    </h2>

    <div class="mt-4 space-y-2">
        @for($star = 5; $star >= 1; $star--)
            @php($count = $ratingCounts[$star] ?? 0)
            <div class="flex items-center gap-3 text-sm">
                <span class="flex w-16 shrink-0 items-center gap-1 text-secondary-shade dark:text-white">
                    {{ $star }} <i class="fa-solid fa-star text-[10px] text-primary"></i>
                </span>
                <div class="h-2 flex-1 overflow-hidden bg-grey-tint dark:bg-white/10">
                    <div class="h-full bg-primary" style="width: {{ $count > 0 ? max(3, round(($count / $maxRatingCount) * 100)) : 0 }}%"></div>
                </div>
                <span class="w-6 shrink-0 text-right text-xs text-grey dark:text-white/40">{{ $count }}</span>
            </div>
        @endfor
    </div>
</div>

<form method="GET" @submit.prevent="submitForm($event)" class="mt-6 flex flex-wrap items-center gap-4">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher par produit ou client…" @input.debounce.500ms="$el.form.requestSubmit()" @if(request()->filled('q')) autofocus @endif class="w-72 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    <x-admin-filter-reset :route="route('admin.reviews.index')" />
    <noscript><button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">Filtrer</button></noscript>
    <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
</form>

<div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-6 transition-opacity">
    @include('admin.reviews.partials.lists')
</div>

</div>

@endsection

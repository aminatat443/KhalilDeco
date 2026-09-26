@extends('layouts.admin')

@section('title', 'Retours')

@section('content')

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Demandes de retour</h1>

@php
    $returnStatusIcons = [
        'demandee' => 'fa-clock', 'acceptee' => 'fa-check', 'refusee' => 'fa-xmark',
        'article_recu' => 'fa-box', 'remboursee' => 'fa-sack-dollar',
    ];
    $toneClasses = [
        'neutral' => 'bg-grey-tint text-grey dark:bg-white/10 dark:text-white/50',
        'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
        'green' => 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-400',
        'red' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
    ];
@endphp

<div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
    @foreach(\App\Models\ProductReturn::STATUS_LABELS as $key => $label)
        <a
            href="{{ route('admin.returns.index', ['status' => $key]) }}"
            class="animate-fade-up bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-[#16201f] {{ request('status') === $key ? 'ring-2 ring-primary' : 'border border-secondary-shade/10 dark:border-white/10' }}"
            style="animation-delay: {{ $loop->index * 60 }}ms"
        >
            <span class="flex h-10 w-10 items-center justify-center {{ $toneClasses[\App\Models\ProductReturn::STATUS_TONES[$key]] }}">
                <i class="fa-solid {{ $returnStatusIcons[$key] }} text-sm"></i>
            </span>
            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">{{ $label }}</p>
            <p class="mt-1 text-xl font-semibold text-secondary-shade dark:text-white">{{ $statusCounts[$key] ?? 0 }}</p>
        </a>
    @endforeach
</div>

<form method="GET" @submit.prevent="submitForm($event)" class="mt-8 flex flex-wrap items-center gap-4">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <input type="hidden" name="flag" value="{{ request('flag') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher par n° de commande ou article…" @input.debounce.500ms="$el.form.requestSubmit()" @if(request()->filled('q')) autofocus @endif class="w-72 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    @if(request('flag') === 'pending')
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-clock"></i>
            Retours à traiter
            <a href="{{ route('admin.returns.index', request()->except('flag', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer ce filtre" title="Retirer ce filtre">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    <x-admin-filter-reset :route="route('admin.returns.index')" />
    <noscript><button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">Filtrer</button></noscript>
    <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
</form>

<div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-6 transition-opacity">
    @include('admin.returns.partials.table')
</div>

</div>

@endsection

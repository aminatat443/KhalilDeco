@extends('layouts.admin')

@section('title', 'Factures')

@section('content')

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Factures</h1>
        <p class="mt-1 text-sm text-grey dark:text-white/40">Registre des factures — une par commande, identique au PDF envoyé au client.</p>
    </div>
    <a href="{{ route('admin.invoices.create') }}" class="block bg-primary px-6 py-3 text-center text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md sm:inline-block">
        <i class="fa-solid fa-plus mr-1.5"></i>Nouvelle facture
    </a>
</div>

<form method="GET" @submit.prevent="submitForm($event)" class="mt-8 flex flex-wrap items-end gap-4">
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Recherche</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="N° de commande ou client…" @input.debounce.500ms="$el.form.requestSubmit()" class="w-64 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Du</label>
        <input type="date" name="from" value="{{ request('from') }}" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Au</label>
        <input type="date" name="to" value="{{ request('to') }}" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>
    <x-admin-filter-reset :route="route('admin.invoices.index')" />
    <noscript><button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">Filtrer</button></noscript>
    <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
</form>

<div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-6 transition-opacity">
    @include('admin.invoices.partials.table')
</div>

</div>

@endsection

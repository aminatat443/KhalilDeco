@extends('layouts.admin')

@section('title', 'Abonnés newsletter')

@section('content')

<a href="{{ route('admin.campaigns.index') }}" class="text-xs text-grey hover:text-primary dark:text-white/40"><i class="fa-solid fa-arrow-left mr-1"></i>Campagnes</a>

<h1 class="mt-3 text-2xl font-semibold text-secondary-shade dark:text-white">Abonnés newsletter</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Destinataires des campagnes Nouveautés, Promotions et Personnalisée — les désinscrits ne reçoivent plus rien.</p>

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">
    <form method="GET" @submit.prevent="submitForm($event)" class="mt-6 flex flex-wrap items-center gap-4">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un email…" @input.debounce.500ms="$el.form.requestSubmit()" class="w-64 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        <select name="status" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <option value="">Tous les statuts</option>
            <option value="active" @selected(request('status') === 'active')>Actif</option>
            <option value="unsubscribed" @selected(request('status') === 'unsubscribed')>Désinscrit</option>
        </select>
        <x-admin-filter-reset :route="route('admin.campaigns.subscribers')" />
        <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
    </form>

    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-6 transition-opacity">
        @include('admin.campaigns.partials.subscribers-table')
    </div>
</div>

@endsection

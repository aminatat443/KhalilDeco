@extends('layouts.admin')

@section('title', 'Historique des envois')

@section('content')

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Campagnes email</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Historique des campagnes envoyées — une ligne par campagne. Ouvrez « Voir les détails » pour la liste des destinataires.</p>

<div class="mt-6 flex flex-wrap gap-2 border-b border-secondary-shade/10 pb-px dark:border-white/10">
    <a href="{{ route('admin.campaigns.index') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Campagnes</a>
    <a href="{{ route('admin.email-templates.index') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Modèles d'emails</a>
    <a href="{{ route('admin.campaigns.automations.edit') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Paramètres</a>
    <a href="{{ route('admin.campaign-sends.index') }}" class="border-b-2 border-primary px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-primary">Historique des envois</a>
</div>

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">
    <form method="GET" @submit.prevent="submitForm($event)" class="mt-8 flex flex-wrap items-center gap-4">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher une campagne, un objet…" @input.debounce.500ms="$el.form.requestSubmit()" class="w-64 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        <select name="type" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <option value="">Tous les types</option>
            @foreach(App\Models\Campaign::CAMPAIGN_TYPES as $value => $label)
                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <option value="">Tous les statuts</option>
            @foreach(App\Models\Campaign::STATUS_LABELS as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ request('date') }}" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        <x-admin-filter-reset :route="route('admin.campaign-sends.index')" />
        <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
    </form>

    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-6 transition-opacity">
        @include('admin.campaigns.partials.sends-table')
    </div>
</div>

@endsection

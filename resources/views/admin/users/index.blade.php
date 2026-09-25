@extends('layouts.admin')

@section('title', 'Utilisateurs')

@section('content')

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Utilisateurs</h1>
    @can('create', [App\Models\User::class, App\Enums\Role::Client])
        <a href="{{ route('admin.users.create') }}" class="block bg-primary px-6 py-3 text-center text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md sm:inline-block">
            Créer un compte
        </a>
    @endcan
</div>

<form method="GET" @submit.prevent="submitForm($event)" class="mt-8 flex flex-wrap items-center gap-4">
    <input type="hidden" name="flag" value="{{ request('flag') }}">
    <input type="hidden" name="repeat_from" value="{{ request('repeat_from') }}">
    <input type="hidden" name="repeat_to" value="{{ request('repeat_to') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher par nom ou email…" @input.debounce.500ms="$el.form.requestSubmit()" @if(request()->filled('q')) autofocus @endif class="w-64 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    <select name="role" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        <option value="">Tous les rôles</option>
        @foreach(['client' => 'Client', 'gestionnaire' => 'Gestionnaire', 'admin' => 'Administrateur', 'super_admin' => 'Super admin'] as $value => $label)
            <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        <option value="">Tous les statuts</option>
        <option value="online" @selected(request('status') === 'online')>En ligne</option>
        <option value="offline" @selected(request('status') === 'offline')>Hors ligne</option>
        <option value="never" @selected(request('status') === 'never')>Jamais connecté</option>
    </select>
    <div class="flex items-center gap-2">
        <label class="text-xs font-semibold uppercase tracking-[0.1em] text-grey dark:text-white/40">Du</label>
        <input type="date" name="from" value="{{ request('from') }}" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        <label class="text-xs font-semibold uppercase tracking-[0.1em] text-grey dark:text-white/40">Au</label>
        <input type="date" name="to" value="{{ request('to') }}" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>
    @if(request('flag') === 'repeat_customers')
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-rotate"></i>
            Clients récurrents
            <a href="{{ route('admin.users.index', request()->except(['flag', 'repeat_from', 'repeat_to', 'page'])) }}" class="hover:text-primary-shade" aria-label="Retirer ce filtre" title="Retirer ce filtre">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    <x-admin-filter-reset :route="route('admin.users.index')" />
    <noscript><button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">Filtrer</button></noscript>
    <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
</form>

<div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-6 transition-opacity">
    @include('admin.users.partials.table')
</div>

</div>

@endsection

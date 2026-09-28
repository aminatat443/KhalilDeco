@extends('layouts.admin')

@section('title', "Journal d'activité")

@section('content')

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Journal d'activité</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Actions importantes effectuées dans le back-office — visible uniquement par le Super Admin.</p>

<form method="GET" @submit.prevent="submitForm($event)" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher..." @input.debounce.500ms="$el.form.requestSubmit()" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white sm:w-64">
    <select name="module" @change="$el.form.requestSubmit()" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white sm:w-56">
        <option value="">Tous les modules</option>
        @foreach($modules as $module)
            <option value="{{ $module }}" @selected(request('module') === $module)>{{ ucfirst($module) }}</option>
        @endforeach
    </select>
    <x-admin-filter-reset :route="route('admin.activity-logs.index')" />
    <noscript><button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">Filtrer</button></noscript>
    <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
</form>

<div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="transition-opacity">
    @include('admin.activity-logs.partials.list')
</div>

</div>

@endsection

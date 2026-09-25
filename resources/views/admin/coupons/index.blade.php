@extends('layouts.admin')

@section('title', 'Codes promo')

@section('content')

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Codes promo</h1>
    @can('create', App\Models\Coupon::class)
        <a href="{{ route('admin.coupons.create') }}" class="block bg-primary px-6 py-3 text-center text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md sm:inline-block">
            Nouveau code
        </a>
    @endcan
</div>

<form method="GET" @submit.prevent="submitForm($event)" class="mt-8 flex flex-wrap items-center gap-4">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un code…" @input.debounce.500ms="$el.form.requestSubmit()" @if(request()->filled('q')) autofocus @endif class="w-64 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    <x-admin-filter-reset :route="route('admin.coupons.index')" />
    <noscript><button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">Filtrer</button></noscript>
    <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
</form>

<div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-6 transition-opacity">
    @include('admin.coupons.partials.table')
</div>

</div>

@endsection

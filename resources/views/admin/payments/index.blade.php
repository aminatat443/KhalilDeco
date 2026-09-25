@extends('layouts.admin')

@section('title', 'Paiements')

@section('content')

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Paiements</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Toutes les transactions enregistrées, quelle que soit la commande.</p>

@if(request()->filled('from') || request()->filled('to'))
    <p class="mt-3 inline-flex items-center gap-2 border border-secondary-shade/15 bg-grey-tint/50 px-3 py-1.5 text-xs text-secondary-shade dark:border-white/10 dark:bg-white/5 dark:text-white/70">
        <i class="fa-solid fa-calendar-days text-primary"></i>
        Période : {{ request('from') ? \Illuminate\Support\Carbon::parse(request('from'))->format('d/m/Y') : '…' }} – {{ request('to') ? \Illuminate\Support\Carbon::parse(request('to'))->format('d/m/Y') : "aujourd'hui" }}
    </p>
@endif

<form method="GET" @submit.prevent="submitForm($event)" class="mt-3 flex flex-wrap items-center gap-3">
    {{-- Bornes de date transmises par une carte de tableau de bord — conservées telles quelles
         tant que l'utilisateur ne clique pas sur "Réinitialiser", même s'il affine avec la
         recherche ou un autre filtre ensuite. --}}
    @if(request()->filled('from'))<input type="hidden" name="from" value="{{ request('from') }}">@endif
    @if(request()->filled('to'))<input type="hidden" name="to" value="{{ request('to') }}">@endif
    @if(request()->filled('date_field'))<input type="hidden" name="date_field" value="{{ request('date_field') }}">@endif

    <input type="text" name="q" value="{{ request('q') }}" placeholder="N° commande, client, référence..." @input.debounce.500ms="$el.form.requestSubmit()" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white sm:w-64">
    <select name="status" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3 py-2 text-xs font-medium uppercase tracking-[0.06em] text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
        <option value="">Tous les statuts</option>
        @foreach(\App\Models\Payment::STATUS_LABELS as $value => $label)
            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="provider" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3 py-2 text-xs font-medium uppercase tracking-[0.06em] text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
        <option value="">Tous les moyens</option>
        @foreach(\App\Models\Order::PAYMENT_METHOD_LABELS as $value => $label)
            <option value="{{ $value }}" @selected(request('provider') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-admin-filter-reset :route="route('admin.payments.index')" />
    <noscript><button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">Filtrer</button></noscript>
    <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
</form>

<div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-6 transition-opacity">
    @include('admin.payments.partials.table')
</div>

</div>

@endsection

@extends('layouts.admin')

@section('title', 'Commandes')

@section('content')

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">

<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Commandes</h1>
    @can('create', App\Models\Order::class)
        <a href="{{ route('admin.orders.create') }}" class="flex items-center gap-2 bg-primary px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
            <i class="fa-solid fa-store"></i>
            Vente en boutique
        </a>
    @endcan
</div>

@php
    $statusLabels = \App\Models\Order::STATUS_LABELS;
    $statusIcons = [
        'en_attente_paiement' => 'fa-hourglass-half',
        'recue' => 'fa-inbox', 'confirmee' => 'fa-check', 'en_preparation' => 'fa-box-open',
        'expediee' => 'fa-truck', 'livree' => 'fa-circle-check', 'annulee' => 'fa-xmark',
    ];
    $toneClasses = [
        'neutral' => 'bg-grey-tint text-grey dark:bg-white/10 dark:text-white/50',
        'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
        'primary' => 'bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary',
        'green' => 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-400',
        'red' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
    ];
@endphp

<div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-4">
    <a
        href="{{ route('admin.orders.index', ['date' => today()->format('Y-m-d')]) }}"
        class="animate-fade-up bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-[#16201f] {{ request('date') === today()->format('Y-m-d') ? 'ring-2 ring-primary' : 'border border-secondary-shade/10 dark:border-white/10' }}"
    >
        <span class="flex h-10 w-10 items-center justify-center bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary">
            <i class="fa-solid fa-calendar-day text-sm"></i>
        </span>
        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Aujourd'hui</p>
        <p class="mt-1 text-xl font-semibold text-secondary-shade dark:text-white">{{ $todayCount }}</p>
    </a>
    @foreach($statusLabels as $key => $label)
        <a
            href="{{ route('admin.orders.index', ['status' => $key]) }}"
            class="animate-fade-up bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-[#16201f] {{ request('status') === $key ? 'ring-2 ring-primary' : 'border border-secondary-shade/10 dark:border-white/10' }}"
            style="animation-delay: {{ ($loop->index + 1) * 60 }}ms"
        >
            <span class="flex h-10 w-10 items-center justify-center {{ $toneClasses[\App\Models\Order::STATUS_TONES[$key]] }}">
                <i class="fa-solid {{ $statusIcons[$key] }} text-sm"></i>
            </span>
            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">{{ $label }}</p>
            <p class="mt-1 text-xl font-semibold text-secondary-shade dark:text-white">{{ $statusCounts[$key] ?? 0 }}</p>
        </a>
    @endforeach
</div>

<form method="GET" @submit.prevent="submitForm($event)" class="mt-8 flex flex-wrap items-center gap-4">
    <input type="hidden" name="date" value="{{ request('date') }}">
    <input type="hidden" name="month" value="{{ request('month') }}">
    <input type="hidden" name="year" value="{{ request('year') }}">
    <input type="hidden" name="payment_method" value="{{ request('payment_method') }}">
    <input type="hidden" name="payment_status" value="{{ request('payment_status') }}">
    <input type="hidden" name="from" value="{{ request('from') }}">
    <input type="hidden" name="to" value="{{ request('to') }}">
    <input type="hidden" name="flag" value="{{ request('flag') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="N° de commande ou client…" @input.debounce.500ms="$el.form.requestSubmit()" @if(request()->filled('q')) autofocus @endif class="w-64 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    <select name="status" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        <option value="">Tous les statuts</option>
        @foreach($statusLabels as $value => $label)
            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @if(request()->filled('date'))
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-calendar-day"></i>
            {{ \Illuminate\Support\Carbon::parse(request('date'))->translatedFormat('d M Y') }}
            <a href="{{ route('admin.orders.index', request()->except('date', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer le filtre de date" title="Retirer le filtre de date">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    @if(request()->filled('month'))
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-calendar-week"></i>
            {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', request('month'))->translatedFormat('F Y') }}
            <a href="{{ route('admin.orders.index', request()->except('month', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer le filtre du mois" title="Retirer le filtre du mois">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    @if(request()->filled('year'))
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-calendar-days"></i>
            {{ request('year') }}
            <a href="{{ route('admin.orders.index', request()->except('year', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer le filtre de l'année" title="Retirer le filtre de l'année">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    @if(request()->filled('payment_method'))
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-credit-card"></i>
            {{ \App\Models\Order::PAYMENT_METHOD_LABELS[request('payment_method')] ?? request('payment_method') }}
            <a href="{{ route('admin.orders.index', request()->except('payment_method', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer le filtre de paiement" title="Retirer le filtre de paiement">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    @if(request()->filled('payment_status'))
        @php($paymentStatusLabels = ['pending' => 'Paiement en attente', 'paid' => 'Payée', 'failed' => 'Paiement échoué', 'refunded' => 'Remboursée'])
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-money-check-dollar"></i>
            {{ $paymentStatusLabels[request('payment_status')] ?? request('payment_status') }}
            <a href="{{ route('admin.orders.index', request()->except('payment_status', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer le filtre de statut de paiement" title="Retirer le filtre de statut de paiement">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    @if(request()->filled('from') || request()->filled('to'))
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-calendar-days"></i>
            {{ request('from') ? \Illuminate\Support\Carbon::parse(request('from'))->format('d/m/Y') : '…' }} – {{ request('to') ? \Illuminate\Support\Carbon::parse(request('to'))->format('d/m/Y') : "aujourd'hui" }}
            <a href="{{ route('admin.orders.index', request()->except(['from', 'to', 'page'])) }}" class="hover:text-primary-shade" aria-label="Retirer le filtre de période" title="Retirer le filtre de période">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    @if(request('flag') === 'overdue_shipping')
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-triangle-exclamation"></i>
            En retard d'expédition (+5j)
            <a href="{{ route('admin.orders.index', request()->except('flag', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer ce filtre" title="Retirer ce filtre">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    @if(request('flag') === 'confirmed_sales')
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-check-double"></i>
            Ventes confirmées
            <a href="{{ route('admin.orders.index', request()->except('flag', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer ce filtre" title="Retirer ce filtre">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    @if(request('flag') === 'unpaid')
        <span class="inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
            <i class="fa-solid fa-user-clock"></i>
            Impayées
            <a href="{{ route('admin.orders.index', request()->except('flag', 'page')) }}" class="hover:text-primary-shade" aria-label="Retirer ce filtre" title="Retirer ce filtre">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </span>
    @endif
    <x-admin-filter-reset :route="route('admin.orders.index')" />
    <noscript><button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">Filtrer</button></noscript>
    <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
</form>

<div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-6 transition-opacity">
    @include('admin.orders.partials.table')
</div>

</div>

@endsection

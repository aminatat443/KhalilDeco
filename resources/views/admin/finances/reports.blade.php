@extends('layouts.admin')

@section('title', $report['title'])

@section('content')

<div x-data="ajaxFilter({{ $period->key !== 'last_7_days' || $type !== 'sales' ? 'true' : 'false' }})">

<div class="print:hidden">
    <a href="{{ route('admin.finances.index') }}" class="text-xs text-grey hover:text-primary dark:text-white/40"><i class="fa-solid fa-arrow-left mr-1"></i>Finances</a>

    <h1 class="mt-3 text-2xl font-semibold text-secondary-shade dark:text-white">Rapports financiers</h1>

    <form method="GET" @submit.prevent="submitForm($event)" class="mt-6 flex flex-wrap items-center gap-3">
        <select name="type" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3 py-2 text-xs font-medium uppercase tracking-[0.06em] text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
            @foreach($types as $key => $label)
                <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="period" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3 py-2 text-xs font-medium uppercase tracking-[0.06em] text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
            @foreach(\App\Support\DashboardPeriod::OPTIONS as $key => $label)
                <option value="{{ $key }}" @selected($period->key === $key)>{{ $label }}</option>
            @endforeach
        </select>

        <input type="date" name="from" value="{{ $period->from }}" class="border border-secondary-shade/15 bg-white px-2.5 py-2 text-xs text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
        <span class="text-xs text-grey dark:text-white/40">au</span>
        <input type="date" name="to" value="{{ $period->to }}" class="border border-secondary-shade/15 bg-white px-2.5 py-2 text-xs text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
        <button type="submit" class="bg-secondary-shade px-6 py-2 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">Appliquer</button>
        <x-admin-filter-reset :route="route('admin.finances.reports')" />
        <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
    </form>
</div>

<div x-ref="results" :class="loading && 'opacity-50 pointer-events-none'" class="mt-8 transition-opacity">
    @include('admin.finances.partials.report-result')
</div>

</div>

@endsection

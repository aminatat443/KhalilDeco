@extends('layouts.admin')

@section('title', 'Dépenses')

@section('content')

<div x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Dépenses</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Charges réellement enregistrées — alimente le calcul de marge et le résultat net dans les rapports financiers.</p>

<form method="GET" @submit.prevent="submitForm($event)" class="mt-6 flex flex-wrap items-center gap-3">
    <select name="period" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3 py-2 text-xs font-medium uppercase tracking-[0.06em] text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
        @foreach(\App\Support\DashboardPeriod::OPTIONS as $key => $label)
            <option value="{{ $key }}" @selected($period->key === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="category" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3 py-2 text-xs font-medium uppercase tracking-[0.06em] text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
        <option value="">Toutes les catégories</option>
        @foreach(\App\Models\Expense::CATEGORIES as $value => $label)
            <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-admin-filter-reset :route="route('admin.expenses.index')" />
    <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
</form>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_1.5fr]">

    @can('create', App\Models\Expense::class)
        <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Enregistrer une dépense</h2>
            <form action="{{ route('admin.expenses.store') }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">Catégorie</label>
                    <select name="category" required class="w-full border border-secondary-shade/15 bg-white px-3 py-2 text-sm outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
                        @foreach(\App\Models\Expense::CATEGORIES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">Libellé</label>
                    <input type="text" name="label" required class="w-full border border-secondary-shade/15 bg-white px-3 py-2 text-sm outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">Montant (FCFA)</label>
                        <input type="number" name="amount" min="1" required class="w-full border border-secondary-shade/15 bg-white px-3 py-2 text-sm outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">Date</label>
                        <input type="date" name="expense_date" value="{{ now()->format('Y-m-d') }}" required class="w-full border border-secondary-shade/15 bg-white px-3 py-2 text-sm outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">Notes (optionnel)</label>
                    <textarea name="notes" rows="2" class="w-full border border-secondary-shade/15 bg-white px-3 py-2 text-sm outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white"></textarea>
                </div>
                <button type="submit" class="w-full bg-primary py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade">
                    Enregistrer
                </button>
            </form>
        </div>
    @endcan

    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="transition-opacity">
        @include('admin.expenses.partials.list')
    </div>

</div>

</div>

@endsection

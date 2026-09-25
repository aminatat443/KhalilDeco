@extends('layouts.admin')

@section('title', 'Sécurité')

@section('content')

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Sécurité</h1>
        <p class="mt-1 text-sm text-grey dark:text-white/40">Surveillance applicative et blocage d'IP.</p>
    </div>
</div>

{{-- Indicateurs --}}
@php
    $isProtected = $blockedCount === 0 && $alertsToday === 0;
@endphp
<div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <span class="flex h-10 w-10 items-center justify-center {{ $isProtected ? 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400' }}">
            <i class="fa-solid fa-shield-halved text-sm"></i>
        </span>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Statut</p>
        <p class="mt-1 text-xl font-semibold text-secondary-shade dark:text-white">{{ $isProtected ? 'Protégé' : 'À surveiller' }}</p>
    </div>
    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <span class="flex h-10 w-10 items-center justify-center bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400">
            <i class="fa-solid fa-triangle-exclamation text-sm"></i>
        </span>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Alertes aujourd'hui</p>
        <p class="mt-1 text-2xl font-semibold text-secondary-shade dark:text-white">{{ $alertsToday }}</p>
    </div>
    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <span class="flex h-10 w-10 items-center justify-center bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
            <i class="fa-solid fa-magnifying-glass text-sm"></i>
        </span>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Activités suspectes</p>
        <p class="mt-1 text-2xl font-semibold text-secondary-shade dark:text-white">{{ $suspiciousToday }}</p>
    </div>
    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <span class="flex h-10 w-10 items-center justify-center bg-secondary-shade/10 text-secondary-shade dark:bg-white/10 dark:text-white">
            <i class="fa-solid fa-ban text-sm"></i>
        </span>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">IP bloquées</p>
        <p class="mt-1 text-2xl font-semibold text-secondary-shade dark:text-white">{{ $blockedCount }}</p>
    </div>
</div>

{{-- IP bloquées --}}
<div class="mt-6 bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">IP bloquées</h2>

    <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
        @forelse($blockedIps as $blocked)
            <div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                <div>
                    <span class="font-medium text-secondary-shade dark:text-white">{{ $blocked->ip_address }}</span>
                    <span class="ml-2 text-xs text-grey dark:text-white/40">{{ $blocked->reason }}</span>
                    <span class="ml-2 text-xs text-grey dark:text-white/40">
                        — {{ $blocked->blocked_by ? 'bloquée par '.$blocked->blockedBy?->name : 'blocage automatique' }}
                        {{ $blocked->blocked_until ? 'jusqu\'au '.$blocked->blocked_until->format('d/m/Y H:i') : '(permanent)' }}
                    </span>
                </div>
                <form action="{{ route('admin.security.unblock', $blocked) }}" method="POST" onsubmit="return confirm('Débloquer cette IP ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-primary hover:underline">Débloquer</button>
                </form>
            </div>
        @empty
            <p class="py-3 text-sm text-grey dark:text-white/40">Aucune IP bloquée actuellement.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $blockedIps->links() }}</div>

    <form action="{{ route('admin.security.block') }}" method="POST" class="mt-6 flex flex-wrap items-end gap-4 border-t border-secondary-shade/10 pt-6 dark:border-white/10">
        @csrf
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Adresse IP</label>
            <input type="text" name="ip_address" required placeholder="203.0.113.42" class="border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div class="flex-1">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Motif</label>
            <input type="text" name="reason" required placeholder="Raison du blocage" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <button type="submit" class="bg-red-600 px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-red-700 hover:shadow-md">Bloquer</button>
    </form>
</div>

{{-- Historique --}}
<div class="mt-6 bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10" x-data="ajaxFilter({{ collect(request()->query())->except('page')->filter()->isNotEmpty() ? 'true' : 'false' }})">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Journal d'événements</h2>
        <form method="GET" @submit.prevent="submitForm($event)" class="flex items-center gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Filtrer par IP…" @input.debounce.500ms="$el.form.requestSubmit()" class="w-48 border border-secondary-shade/15 bg-white px-3.5 py-1.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <x-admin-filter-reset :route="route('admin.security.index')" />
            <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
        </form>
    </div>

    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="mt-4 transition-opacity">
        @include('admin.security.partials.events')
    </div>
</div>

@endsection

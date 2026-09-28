@extends('layouts.admin')

@section('title', 'Caisse')

@section('content')

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Caisse</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Ouverture avec un fonds déclaré, clôture avec le solde théorique (espèces réellement encaissées) comparé au montant compté.</p>

@if($openSession)
    <div class="mt-8 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.1em] text-primary">
                    <span class="h-2 w-2 rounded-full bg-primary"></span>Session ouverte
                </span>
                <p class="mt-1 text-sm text-grey dark:text-white/40">Ouverte le {{ $openSession->opened_at->format('d/m/Y à H:i') }} par {{ $openSession->user->name }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase tracking-[0.1em] text-grey dark:text-white/40">Solde théorique actuel</p>
                <p class="text-2xl font-semibold text-secondary-shade dark:text-white">{{ number_format($expectedNow, 0, ',', ' ') }} FCFA</p>
            </div>
        </div>

        <p class="mt-3 text-xs text-grey dark:text-white/40">Fonds d'ouverture : {{ number_format($openSession->opening_amount, 0, ',', ' ') }} FCFA — calculé à partir des paiements « espèces » réels enregistrés depuis l'ouverture.</p>

        @can('close', $openSession)
            <form action="{{ route('admin.cash.close', $openSession) }}" method="POST" class="mt-6 grid grid-cols-1 gap-4 border-t border-secondary-shade/10 pt-6 dark:border-white/10 sm:grid-cols-[1fr_2fr_auto] sm:items-end">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Montant compté (FCFA)</label>
                    <input type="number" name="closing_declared_amount" min="0" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Commentaire (optionnel)</label>
                    <input type="text" name="comment" placeholder="Motif d'un éventuel écart..." class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <button type="submit" class="bg-primary px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade" data-confirm="Clôturer la caisse avec ce montant ?">
                    Clôturer la caisse
                </button>
            </form>
        @endcan
    </div>
@else
    @can('open', App\Models\CashSession::class)
        <div class="mt-8 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Ouvrir la caisse</h2>
            <form action="{{ route('admin.cash.open') }}" method="POST" class="mt-4 flex flex-wrap items-end gap-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Fonds de caisse (FCFA)</label>
                    <input type="number" name="opening_amount" min="0" required class="w-56 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <button type="submit" class="bg-primary px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade">
                    Ouvrir la caisse
                </button>
            </form>
        </div>
    @else
        <p class="mt-8 text-sm text-grey dark:text-white/40">Aucune session de caisse ouverte actuellement.</p>
    @endcan
@endif

<div class="mt-8" x-data="ajaxFilter(false)">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Historique des clôtures</h2>

    {{-- Pas de formulaire de filtre ici, seule la pagination doit passer par fetch() plutôt que
         par une navigation classique — voir onResultsClick() dans resources/js/app.js, déjà
         utilisé par products/orders et qui ne dépend d'aucun formulaire. --}}
    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="transition-opacity">
        @include('admin.cash.partials.history')
    </div>
</div>

@endsection

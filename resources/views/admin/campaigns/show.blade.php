@extends('layouts.admin')

@section('title', $campaign->subject)

@section('content')

@php
    $statusTones = ['draft' => 'neutral', 'scheduled' => 'blue', 'pending' => 'neutral', 'processing' => 'amber', 'sent' => 'green', 'partial' => 'amber', 'failed' => 'red'];
@endphp

<a href="{{ route('admin.campaigns.index') }}" class="text-xs text-grey hover:text-primary dark:text-white/40"><i class="fa-solid fa-arrow-left mr-1"></i>Campagnes</a>

<div class="mt-3 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">{{ $campaign->subject }}</h1>
            <x-status-pill :tone="$statusTones[$campaign->status] ?? 'neutral'" :label="$campaign->statusLabel()" />
        </div>
        <p class="mt-1 text-sm text-grey dark:text-white/40">
            {{ $campaign->campaignTypeLabel() }} — {{ $campaign->is_automatic ? 'automatique' : 'manuelle' }} — créée le {{ $campaign->created_at->format('d/m/Y à H:i') }}
            @if($campaign->sender) par {{ $campaign->sender->name }} @endif
            @if($campaign->status === 'scheduled' && $campaign->scheduled_at)
                — programmée pour le {{ $campaign->scheduled_at->format('d/m/Y à H:i') }}
            @endif
        </p>
        @if($campaign->subject_template && $campaign->subject_template !== $campaign->subject)
            <p class="mt-1 text-xs text-grey dark:text-white/40">Modèle d'objet : « {{ $campaign->subject_template }} » — un identifiant de date y est ajouté automatiquement pour éviter que Gmail regroupe les envois.</p>
        @endif
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="bg-white p-5 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <p class="text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Destinataires</p>
        <p class="mt-1 text-xl font-semibold text-secondary-shade dark:text-white">{{ $campaign->recipients_count }}</p>
    </div>
    <div class="bg-white p-5 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <p class="text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Envoyés</p>
        <p class="mt-1 text-xl font-semibold text-green-700 dark:text-green-400">{{ $campaign->sent_count }}</p>
    </div>
    <div class="bg-white p-5 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <p class="text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Échecs</p>
        <p class="mt-1 text-xl font-semibold {{ $campaign->failed_count > 0 ? 'text-red-600 dark:text-red-400' : 'text-secondary-shade dark:text-white' }}">{{ $campaign->failed_count }}</p>
    </div>
</div>

@if($campaign->failed_count > 0)
    @php
        $firstError = $campaign->sends()->where('status', 'failed')->whereNotNull('error_message')->value('error_message');
    @endphp
    <div class="mt-6 border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
        <p class="font-semibold">{{ $campaign->failed_count }} {{ Str::plural('échec', $campaign->failed_count) }} sur cette campagne.</p>
        @if($firstError)
            <p class="mt-1 text-xs opacity-90">Exemple d'erreur : {{ $firstError }}</p>
        @endif
        <p class="mt-1 text-xs opacity-75">Voir la colonne « Erreur » ci-dessous pour le détail par destinataire.</p>
    </div>
@endif

{{-- Pas de formulaire de filtre ici, seule la pagination doit passer par fetch() plutôt que
     par une navigation classique — même mécanisme que admin/cash/index.blade.php. --}}
<div class="mt-6" x-data="ajaxFilter(false)">
    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="transition-opacity">
        @include('admin.campaigns.partials.sends-detail-table')
    </div>
</div>

@endsection

@extends('layouts.admin')

@section('title', 'Campagnes email')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Campagnes email</h1>
        <p class="mt-1 text-sm text-grey dark:text-white/40">Panier abandonné, favoris/stock faible et newsletter — historique et automatisation.</p>
    </div>
    @can('create', App\Models\Campaign::class)
        <a href="{{ route('admin.campaigns.create') }}" class="bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
            <i class="fa-solid fa-plus mr-1.5"></i>Nouvelle campagne
        </a>
    @endcan
</div>

{{-- Sous-navigation --}}
<div class="mt-6 flex flex-wrap gap-2 border-b border-secondary-shade/10 pb-px dark:border-white/10">
    <a href="{{ route('admin.campaigns.index') }}" class="border-b-2 border-primary px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-primary">Campagnes</a>
    <a href="{{ route('admin.email-templates.index') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Modèles d'emails</a>
    <a href="{{ route('admin.campaigns.automations.edit') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Paramètres</a>
    <a href="{{ route('admin.campaign-sends.index') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Historique des envois</a>
</div>

{{-- Tableau de bord --}}
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
    <a href="#historique-campagnes" class="block bg-white p-5 border border-secondary-shade/10 transition hover:border-primary/30 dark:bg-[#16201f] dark:border-white/10">
        <span class="flex h-10 w-10 items-center justify-center bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary"><i class="fa-solid fa-envelope-open-text text-sm"></i></span>
        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Campagnes au total</p>
        <p class="mt-1 text-xl font-semibold text-secondary-shade dark:text-white">{{ $stats['total'] }}</p>
    </a>
    <a href="{{ route('admin.campaign-sends.index', ['status' => 'sent', 'month' => now()->format('Y-m')]) }}" class="block bg-white p-5 border border-secondary-shade/10 transition hover:border-primary/30 dark:bg-[#16201f] dark:border-white/10">
        <span class="flex h-10 w-10 items-center justify-center bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-400"><i class="fa-solid fa-paper-plane text-sm"></i></span>
        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Envoyés ce mois-ci</p>
        <p class="mt-1 text-xl font-semibold text-secondary-shade dark:text-white">{{ $stats['sent_this_month'] }}</p>
    </a>
    <a href="{{ route('admin.campaign-sends.index', ['status' => 'sent', 'date' => now()->format('Y-m-d')]) }}" class="block bg-white p-5 border border-secondary-shade/10 transition hover:border-primary/30 dark:bg-[#16201f] dark:border-white/10">
        <span class="flex h-10 w-10 items-center justify-center bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary"><i class="fa-solid fa-calendar-day text-sm"></i></span>
        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Envoyés aujourd'hui</p>
        <p class="mt-1 text-xl font-semibold text-secondary-shade dark:text-white">{{ $stats['sent_today'] }}</p>
    </a>
    <a href="{{ route('admin.campaigns.subscribers') }}" class="block bg-white p-5 border border-secondary-shade/10 transition hover:border-primary/30 dark:bg-[#16201f] dark:border-white/10">
        <span class="flex h-10 w-10 items-center justify-center bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400"><i class="fa-solid fa-users text-sm"></i></span>
        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Abonnés newsletter</p>
        <p class="mt-1 text-xl font-semibold text-secondary-shade dark:text-white">{{ $stats['newsletter_subscribers'] }}</p>
    </a>
</div>

@php
    $statusTones = [
        'draft' => 'neutral', 'scheduled' => 'blue', 'pending' => 'neutral', 'processing' => 'amber', 'sent' => 'green', 'partial' => 'amber', 'failed' => 'red',
    ];
@endphp

{{-- Campagnes automatiques : produits détectés directement depuis le catalogue --}}
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
    @foreach($automaticCampaigns as $auto)
        <div onclick="window.location='{{ route('admin.campaigns.create') }}?type={{ $auto['type'] }}'" class="cursor-pointer bg-white p-6 border border-secondary-shade/10 transition hover:border-primary/30 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-secondary-shade dark:text-white">{{ $auto['label'] }}</h2>
                    <p class="mt-1 text-xs text-grey dark:text-white/40">
                        <span class="font-semibold text-secondary-shade dark:text-white">{{ $auto['products_count'] }}</span> produit(s) concerné(s) ·
                        <span class="font-semibold text-secondary-shade dark:text-white">{{ $auto['subscribers_count'] }}</span> abonné(s)
                    </p>
                    <p class="mt-1 text-xs text-grey dark:text-white/40">
                        Dernier envoi :
                        @if($auto['last'])
                            {{ ($auto['last']->sent_at ?? $auto['last']->created_at)->format('d/m/Y H:i') }}
                            <x-status-pill :tone="$statusTones[$auto['last']->status] ?? 'neutral'" :label="$auto['last']->statusLabel()" />
                        @else
                            jamais envoyé
                        @endif
                    </p>
                </div>
            </div>
            <form action="{{ $auto['send_route'] }}" method="POST" onclick="event.stopPropagation()" data-confirm="Envoyer l'email « {{ $auto['label'] }} » à tous les abonnés actifs de la newsletter ?" class="mt-4">
                @csrf
                <button type="submit" @disabled($auto['products_count'] === 0) class="w-full bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40">
                    Envoyer
                </button>
            </form>
        </div>
    @endforeach
</div>

@if(request()->filled('from') || request()->filled('to'))
    <p class="mt-6 inline-flex items-center gap-2 border border-primary/40 bg-primary-tint px-3 py-1.5 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
        <i class="fa-solid fa-calendar-days"></i>
        Période : {{ request('from') ? \Illuminate\Support\Carbon::parse(request('from'))->format('d/m/Y') : '…' }} – {{ request('to') ? \Illuminate\Support\Carbon::parse(request('to'))->format('d/m/Y') : "aujourd'hui" }}
        <a href="{{ route('admin.campaigns.index') }}" class="hover:text-primary-shade" aria-label="Retirer le filtre de période" title="Retirer le filtre de période">
            <i class="fa-solid fa-xmark"></i>
        </a>
    </p>
@endif

{{-- Cartes en dessous de lg, tableau au-delà — même bascule que products/orders/segments. --}}
<div id="historique-campagnes" class="mt-6 scroll-mt-6">
    <div class="space-y-3 lg:hidden">
        @forelse($campaigns as $campaign)
            <div onclick="window.location='{{ route('admin.campaigns.show', $campaign) }}'" class="cursor-pointer bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
                <div class="flex items-start justify-between gap-3">
                    <p class="min-w-0 truncate font-medium text-secondary-shade dark:text-white">{{ $campaign->subject }}</p>
                    <x-status-pill :tone="$statusTones[$campaign->status] ?? 'neutral'" :label="$campaign->statusLabel()" class="shrink-0" />
                </div>
                <p class="mt-1 text-xs text-grey dark:text-white/50">{{ $campaign->campaignTypeLabel() }} · {{ $campaign->is_automatic ? 'Automatique' : 'Manuelle' }}</p>
                <dl class="mt-3 grid grid-cols-3 gap-x-3 gap-y-2.5 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                    <div>
                        <dt class="text-grey/60 dark:text-white/30">Destinataires</dt>
                        <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $campaign->recipients_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-grey/60 dark:text-white/30">Envoyés</dt>
                        <dd class="mt-0.5 text-green-700 dark:text-green-400">{{ $campaign->sent_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-grey/60 dark:text-white/30">Échecs</dt>
                        <dd class="mt-0.5 {{ $campaign->failed_count > 0 ? 'text-red-600 dark:text-red-400' : 'text-secondary-shade dark:text-white' }}">{{ $campaign->failed_count }}</dd>
                    </div>
                    <div class="col-span-3">
                        <dt class="text-grey/60 dark:text-white/30">Date</dt>
                        <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $campaign->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        @empty
            <div class="flex flex-col items-center gap-3 border border-secondary-shade/10 bg-white px-6 py-14 text-center dark:border-white/10 dark:bg-[#16201f]">
                <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-envelope-open-text"></i></span>
                <p class="text-sm text-grey dark:text-white/40">Aucune campagne pour le moment.</p>
            </div>
        @endforelse
    </div>

    <div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                    <th class="px-6 py-4 font-medium">Type</th>
                    <th class="px-6 py-4 font-medium">Sujet</th>
                    <th class="px-6 py-4 font-medium">Destinataires</th>
                    <th class="px-6 py-4 font-medium">Envoyés</th>
                    <th class="px-6 py-4 font-medium">Échecs</th>
                    <th class="px-6 py-4 font-medium">Statut</th>
                    <th class="px-6 py-4 font-medium">Origine</th>
                    <th class="px-6 py-4 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
                @forelse($campaigns as $campaign)
                    <tr onclick="window.location='{{ route('admin.campaigns.show', $campaign) }}'" class="cursor-pointer transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                        <td class="px-6 py-4 text-grey dark:text-white/50">{{ $campaign->campaignTypeLabel() }}</td>
                        <td class="px-6 py-4 font-medium text-secondary-shade dark:text-white">{{ $campaign->subject }}</td>
                        <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $campaign->recipients_count }}</td>
                        <td class="px-6 py-4 text-green-700 dark:text-green-400">{{ $campaign->sent_count }}</td>
                        <td class="px-6 py-4 {{ $campaign->failed_count > 0 ? 'text-red-600 dark:text-red-400' : 'text-grey dark:text-white/40' }}">{{ $campaign->failed_count }}</td>
                        <td class="px-6 py-4"><x-status-pill :tone="$statusTones[$campaign->status] ?? 'neutral'" :label="$campaign->statusLabel()" /></td>
                        <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $campaign->is_automatic ? 'Automatique' : 'Manuelle' }}</td>
                        <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $campaign->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-10 text-center text-grey dark:text-white/40">Aucune campagne pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6" data-pagination>{{ $campaigns->links() }}</div>

@endsection

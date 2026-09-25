@extends('layouts.admin')

@section('title', $campaign->subject)

@section('content')

@php
    $statusTones = ['draft' => 'neutral', 'scheduled' => 'blue', 'pending' => 'neutral', 'processing' => 'amber', 'sent' => 'green', 'partial' => 'amber', 'failed' => 'red'];
    $sendStatusTones = ['pending' => 'neutral', 'sent' => 'green', 'failed' => 'red'];
    $sendStatusLabels = ['pending' => 'En attente', 'sent' => 'Envoyé', 'failed' => 'Échec'];
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

<div class="mt-6 overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Destinataire</th>
                <th class="px-6 py-4 font-medium">Email</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium">Envoyé le</th>
                <th class="px-6 py-4 font-medium">Ouvert / Cliqué</th>
                <th class="px-6 py-4 font-medium">Erreur</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($sends as $send)
                <tr>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $send->user?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $send->email }}</td>
                    <td class="px-6 py-4"><x-status-pill :tone="$sendStatusTones[$send->status] ?? 'neutral'" :label="$sendStatusLabels[$send->status] ?? $send->status" /></td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $send->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-6 py-4 text-xs">
                        @if($send->status === 'sent')
                            <span class="{{ $send->opened_at ? 'text-primary' : 'text-grey dark:text-white/30' }}"><i class="fa-solid fa-envelope-open"></i></span>
                            <span class="ml-1 {{ $send->clicked_at ? 'text-primary' : 'text-grey dark:text-white/30' }}"><i class="fa-solid fa-arrow-pointer"></i>@if($send->click_count > 1) ×{{ $send->click_count }}@endif</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-4 max-w-xs truncate text-xs text-red-600 dark:text-red-400" title="{{ $send->error_message }}">{{ $send->error_message ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-10 text-center text-grey dark:text-white/40">{{ $campaign->status === 'scheduled' ? "Campagne programmée — en attente de la date d'envoi." : 'Aucun envoi enregistré.' }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $sends->links() }}</div>

@endsection

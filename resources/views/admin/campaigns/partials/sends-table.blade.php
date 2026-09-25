@php
    $statusTones = ['draft' => 'neutral', 'scheduled' => 'blue', 'pending' => 'neutral', 'processing' => 'amber', 'sent' => 'green', 'partial' => 'amber', 'failed' => 'red'];
@endphp

<p class="mb-4 text-xs text-grey dark:text-white/40">{{ $campaigns->total() }} {{ Str::plural('campagne', $campaigns->total()) }}</p>

<div class="overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Campagne</th>
                <th class="px-6 py-4 font-medium">Type</th>
                <th class="px-6 py-4 font-medium">Destinataires</th>
                <th class="px-6 py-4 font-medium">Envoyés</th>
                <th class="px-6 py-4 font-medium">Échecs</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium">Date d'envoi</th>
                <th class="px-6 py-4 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($campaigns as $campaign)
                <tr onclick="window.location='{{ route('admin.campaigns.show', $campaign) }}'" class="cursor-pointer transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="max-w-xs truncate px-6 py-4 font-medium text-secondary-shade dark:text-white" title="{{ $campaign->subject }}">{{ $campaign->subject }}</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $campaign->campaignTypeLabel() }}</td>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $campaign->recipients_count }}</td>
                    <td class="px-6 py-4 text-green-700 dark:text-green-400">{{ $campaign->sent_count }}</td>
                    <td class="px-6 py-4 {{ $campaign->failed_count > 0 ? 'text-red-600 dark:text-red-400' : 'text-grey dark:text-white/40' }}">{{ $campaign->failed_count }}</td>
                    <td class="px-6 py-4"><x-status-pill :tone="$statusTones[$campaign->status] ?? 'neutral'" :label="$campaign->statusLabel()" /></td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">
                        @if($campaign->status === 'scheduled' && $campaign->scheduled_at)
                            {{ $campaign->scheduled_at->format('d/m/Y H:i') }} <span class="text-[10px] uppercase">(programmée)</span>
                        @else
                            {{ ($campaign->sent_at ?? $campaign->created_at)->format('d/m/Y H:i') }}
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-6 py-4">
                        <a href="{{ route('admin.campaigns.show', $campaign) }}" onclick="event.stopPropagation()" class="text-xs font-semibold uppercase tracking-[0.08em] text-secondary-shade underline transition hover:text-primary dark:text-white/70">
                            Voir les détails
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-6 py-10 text-center text-grey dark:text-white/40">Aucune campagne ne correspond à ces critères.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $campaigns->links() }}</div>

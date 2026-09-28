@php
    $statusTones = ['draft' => 'neutral', 'scheduled' => 'blue', 'pending' => 'neutral', 'processing' => 'amber', 'sent' => 'green', 'partial' => 'amber', 'failed' => 'red'];
@endphp

<p class="mb-4 text-xs text-grey dark:text-white/40">{{ $campaigns->total() }} {{ Str::plural('campagne', $campaigns->total()) }}</p>

<div class="space-y-3 lg:hidden">
    @forelse($campaigns as $campaign)
        <div onclick="window.location='{{ route('admin.campaigns.show', $campaign) }}'" class="cursor-pointer bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-start justify-between gap-3">
                <p class="min-w-0 truncate font-medium text-secondary-shade dark:text-white" title="{{ $campaign->subject }}">{{ $campaign->subject }}</p>
                <x-status-pill :tone="$statusTones[$campaign->status] ?? 'neutral'" :label="$campaign->statusLabel()" class="shrink-0" />
            </div>
            <p class="mt-1 text-xs text-grey dark:text-white/50">{{ $campaign->campaignTypeLabel() }}</p>
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
                    <dt class="text-grey/60 dark:text-white/30">Date d'envoi</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">
                        @if($campaign->status === 'scheduled' && $campaign->scheduled_at)
                            {{ $campaign->scheduled_at->format('d/m/Y H:i') }} <span class="text-[10px] uppercase text-grey dark:text-white/40">(programmée)</span>
                        @else
                            {{ ($campaign->sent_at ?? $campaign->created_at)->format('d/m/Y H:i') }}
                        @endif
                    </dd>
                </div>
            </dl>
            <div class="mt-3 flex items-center justify-end gap-4 border-t border-secondary-shade/10 pt-3 dark:border-white/10" onclick="event.stopPropagation()">
                @if($campaign->status === \App\Models\Campaign::STATUS_PENDING)
                    <form action="{{ route('admin.campaigns.retry', $campaign) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs font-semibold uppercase tracking-[0.08em] text-primary hover:text-primary-shade">
                            <i class="fa-solid fa-rotate-right mr-1"></i>Relancer
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.campaigns.show', $campaign) }}" class="text-xs text-grey/70 hover:text-primary dark:text-white/40">
                    <i class="fa-solid fa-eye mr-1"></i>Voir les détails
                </a>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 border border-secondary-shade/10 bg-white px-6 py-14 text-center dark:border-white/10 dark:bg-[#16201f]">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-envelope-open-text"></i></span>
            <p class="text-sm text-grey dark:text-white/40">Aucune campagne ne correspond à ces critères.</p>
        </div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-4 py-4 font-medium">Campagne</th>
                <th class="px-4 py-4 font-medium">Type</th>
                <th class="px-4 py-4 font-medium">Destinataires</th>
                <th class="px-4 py-4 font-medium">Envoyés</th>
                <th class="px-4 py-4 font-medium">Échecs</th>
                <th class="px-4 py-4 font-medium">Statut</th>
                <th class="px-4 py-4 font-medium">Date</th>
                <th class="px-4 py-4 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($campaigns as $campaign)
                <tr onclick="window.location='{{ route('admin.campaigns.show', $campaign) }}'" class="cursor-pointer transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="max-w-xs truncate px-4 py-4 font-medium text-secondary-shade dark:text-white" title="{{ $campaign->subject }}">{{ $campaign->subject }}</td>
                    <td class="px-4 py-4 text-grey dark:text-white/50">{{ $campaign->campaignTypeLabel() }}</td>
                    <td class="px-4 py-4 text-secondary-shade dark:text-white">{{ $campaign->recipients_count }}</td>
                    <td class="px-4 py-4 text-green-700 dark:text-green-400">{{ $campaign->sent_count }}</td>
                    <td class="px-4 py-4 {{ $campaign->failed_count > 0 ? 'text-red-600 dark:text-red-400' : 'text-grey dark:text-white/40' }}">{{ $campaign->failed_count }}</td>
                    <td class="px-4 py-4"><x-status-pill :tone="$statusTones[$campaign->status] ?? 'neutral'" :label="$campaign->statusLabel()" /></td>
                    <td class="px-4 py-4 text-xs text-grey dark:text-white/40">
                        @if($campaign->status === 'scheduled' && $campaign->scheduled_at)
                            {{ $campaign->scheduled_at->format('d/m/Y H:i') }} <span class="text-[10px] uppercase">(programmée)</span>
                        @else
                            {{ ($campaign->sent_at ?? $campaign->created_at)->format('d/m/Y H:i') }}
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-4 text-right" onclick="event.stopPropagation()">
                        {{-- "En attente" = aucun envoi encore traité, presque toujours parce
                             qu'aucun worker de file (`php artisan queue:work`) n'a tourné entre
                             temps (voir CampaignController::retry()) — remettre en file répare
                             ça sans dupliquer les envois déjà faits. --}}
                        @if($campaign->status === \App\Models\Campaign::STATUS_PENDING)
                            <form action="{{ route('admin.campaigns.retry', $campaign) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" title="Relancer" aria-label="Relancer" class="mr-3 text-xs font-semibold uppercase tracking-[0.08em] text-primary hover:text-primary-shade">
                                    <i class="fa-solid fa-rotate-right mr-1"></i>Relancer
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.campaigns.show', $campaign) }}" title="Voir les détails" aria-label="Voir les détails" class="inline-flex h-8 w-8 items-center justify-center text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-eye"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-10 text-center text-grey dark:text-white/40">Aucune campagne ne correspond à ces critères.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $campaigns->links() }}</div>

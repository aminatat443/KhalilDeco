@php
    $sendStatusTones = ['pending' => 'neutral', 'sent' => 'green', 'failed' => 'red'];
    $sendStatusLabels = ['pending' => 'En attente', 'sent' => 'Envoyé', 'failed' => 'Échec'];
@endphp

<div class="space-y-3 lg:hidden">
    @forelse($sends as $send)
        <div class="bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <p class="min-w-0 truncate font-medium text-secondary-shade dark:text-white">{{ $send->user?->name ?? '—' }}</p>
                <x-status-pill :tone="$sendStatusTones[$send->status] ?? 'neutral'" :label="$sendStatusLabels[$send->status] ?? $send->status" class="shrink-0" />
            </div>
            <p class="mt-1 truncate text-xs text-grey dark:text-white/50">{{ $send->email }}</p>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Envoyé le</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $send->sent_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Ouvert / Cliqué</dt>
                    <dd class="mt-0.5">
                        @if($send->status === 'sent')
                            <span class="{{ $send->opened_at ? 'text-primary' : 'text-grey dark:text-white/30' }}"><i class="fa-solid fa-envelope-open"></i></span>
                            <span class="ml-1 {{ $send->clicked_at ? 'text-primary' : 'text-grey dark:text-white/30' }}"><i class="fa-solid fa-arrow-pointer"></i>@if($send->click_count > 1) ×{{ $send->click_count }}@endif</span>
                        @else
                            <span class="text-secondary-shade dark:text-white">—</span>
                        @endif
                    </dd>
                </div>
                @if($send->error_message)
                    <div class="col-span-2">
                        <dt class="text-grey/60 dark:text-white/30">Erreur</dt>
                        <dd class="mt-0.5 truncate text-red-600 dark:text-red-400" title="{{ $send->error_message }}">{{ $send->error_message }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 border border-secondary-shade/10 bg-white px-6 py-14 text-center dark:border-white/10 dark:bg-[#16201f]">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-paper-plane"></i></span>
            <p class="text-sm text-grey dark:text-white/40">{{ $campaign->status === 'scheduled' ? "Campagne programmée — en attente de la date d'envoi." : 'Aucun envoi enregistré.' }}</p>
        </div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
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

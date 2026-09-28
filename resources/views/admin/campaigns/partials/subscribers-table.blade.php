<p class="mb-4 text-xs text-grey dark:text-white/40">{{ $subscribers->total() }} {{ Str::plural('abonné', $subscribers->total()) }}</p>

<div class="space-y-3 lg:hidden">
    @forelse($subscribers as $subscriber)
        <div class="bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <p class="min-w-0 truncate font-medium text-secondary-shade dark:text-white">{{ $subscriber->email }}</p>
                @if($subscriber->isActive())
                    <x-status-pill tone="green" label="Actif" class="shrink-0" />
                @else
                    <x-status-pill tone="neutral" label="Désinscrit" class="shrink-0" />
                @endif
            </div>
            <p class="mt-1 text-xs text-grey dark:text-white/50">{{ $subscriber->user?->name ?? 'Aucun compte lié' }}</p>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Abonné depuis</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $subscriber->subscribed_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Désinscrit le</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $subscriber->unsubscribed_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 border border-secondary-shade/10 bg-white px-6 py-14 text-center dark:border-white/10 dark:bg-[#16201f]">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-users"></i></span>
            <p class="text-sm text-grey dark:text-white/40">Aucun abonné ne correspond à ces critères.</p>
        </div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Email</th>
                <th class="px-6 py-4 font-medium">Compte lié</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium">Abonné depuis</th>
                <th class="px-6 py-4 font-medium">Désinscrit le</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($subscribers as $subscriber)
                <tr>
                    <td class="px-6 py-4 font-medium text-secondary-shade dark:text-white">{{ $subscriber->email }}</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $subscriber->user?->name ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @if($subscriber->isActive())
                            <x-status-pill tone="green" label="Actif" />
                        @else
                            <x-status-pill tone="neutral" label="Désinscrit" />
                        @endif
                    </td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $subscriber->subscribed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $subscriber->unsubscribed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-10 text-center text-grey dark:text-white/40">Aucun abonné ne correspond à ces critères.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $subscribers->links() }}</div>

<p class="mb-4 text-xs text-grey dark:text-white/40">{{ $subscribers->total() }} {{ Str::plural('abonné', $subscribers->total()) }}</p>

<div class="overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
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

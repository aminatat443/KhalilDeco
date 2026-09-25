{{-- Cartes mobile --}}
<div class="mt-6 space-y-3 lg:hidden">
    @forelse($deliveries as $delivery)
        <a href="{{ route('admin.deliveries.edit', $delivery) }}" class="block bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <span class="font-medium text-secondary-shade dark:text-white">{{ $delivery->zone }}</span>
                <x-status-pill :tone="$delivery->is_active ? 'green' : 'neutral'" :label="$delivery->is_active ? 'Actif' : 'Inactif'" />
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Tarif de livraison</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $delivery->fee > 0 ? number_format($delivery->fee, 0, ',', ' ').' FCFA' : 'À discuter' }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Délai estimé</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $delivery->estimated_days ?? '—' }}</dd>
                </div>
            </dl>
        </a>
    @empty
        <div class="bg-white px-6 py-10 text-center text-sm text-grey border border-secondary-shade/10 dark:bg-[#16201f] dark:text-white/40 dark:border-white/10">Aucune zone de livraison pour le moment.</div>
    @endforelse
</div>

{{-- Tableau desktop --}}
<div class="mt-6 hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Zone</th>
                <th class="px-6 py-4 font-medium">Tarif de livraison</th>
                <th class="px-6 py-4 font-medium">Délai estimé</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($deliveries as $delivery)
                <tr onclick="window.location='{{ route('admin.deliveries.edit', $delivery) }}'" class="cursor-pointer transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="px-6 py-4 font-medium text-secondary-shade dark:text-white">{{ $delivery->zone }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-secondary-shade dark:text-white">{{ $delivery->fee > 0 ? number_format($delivery->fee, 0, ',', ' ').' FCFA' : 'À discuter' }}</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $delivery->estimated_days ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <x-status-pill :tone="$delivery->is_active ? 'green' : 'neutral'" :label="$delivery->is_active ? 'Actif' : 'Inactif'" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.deliveries.edit', $delivery) }}" title="Modifier" aria-label="Modifier" class="inline-flex h-8 w-8 items-center justify-center text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-pen"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-10 text-center text-grey dark:text-white/40">Aucune zone de livraison pour le moment.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

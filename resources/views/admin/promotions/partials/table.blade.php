<div class="space-y-3 lg:hidden">
    @forelse($promotions as $promotion)
        <div onclick="window.location='{{ route('admin.promotions.edit', $promotion) }}'" class="cursor-pointer bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <span class="truncate font-medium text-secondary-shade dark:text-white">{{ $promotion->product?->name ?? $promotion->category?->name ?? 'Toutes catégories' }}</span>
                <x-status-pill :tone="$promotion->is_active ? 'green' : 'neutral'" :label="$promotion->is_active ? 'Active' : 'Désactivée'" class="shrink-0" />
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Réduction</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $promotion->type === 'percentage' ? $promotion->value.'%' : number_format($promotion->value, 0, ',', ' ').' FCFA' }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Période</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $promotion->starts_at?->format('d/m/Y') ?? '—' }} → {{ $promotion->ends_at?->format('d/m/Y') ?? '—' }}</dd>
                </div>
            </dl>
            @if($promotion->is_active)
                <form action="{{ route('admin.promotions.send-email', $promotion) }}" method="POST" class="mt-3 border-t border-secondary-shade/10 pt-3 dark:border-white/10" data-confirm="Envoyer un email aux clients ayant mis ce produit en favori ?" onclick="event.stopPropagation()">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 text-xs text-secondary-shade hover:text-primary dark:text-white/70">
                        <i class="fa-solid fa-envelope"></i>Envoyer par email
                    </button>
                </form>
            @endif
        </div>
    @empty
        <div class="bg-white px-6 py-10 text-center text-sm text-grey border border-secondary-shade/10 dark:bg-[#16201f] dark:text-white/40 dark:border-white/10">Aucune promotion pour le moment.</div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Cible</th>
                <th class="px-6 py-4 font-medium">Réduction</th>
                <th class="px-6 py-4 font-medium">Période</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($promotions as $promotion)
                <tr onclick="window.location='{{ route('admin.promotions.edit', $promotion) }}'" class="cursor-pointer transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $promotion->product?->name ?? $promotion->category?->name ?? 'Toutes catégories' }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-secondary-shade dark:text-white">{{ $promotion->type === 'percentage' ? $promotion->value.'%' : number_format($promotion->value, 0, ',', ' ').' FCFA' }}</td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">
                        {{ $promotion->starts_at?->format('d/m/Y') ?? '—' }} → {{ $promotion->ends_at?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="px-6 py-4">
                        <x-status-pill :tone="$promotion->is_active ? 'green' : 'neutral'" :label="$promotion->is_active ? 'Active' : 'Désactivée'" />
                    </td>
                    <td class="px-6 py-4 text-right" onclick="event.stopPropagation()">
                        @if($promotion->is_active)
                            <form action="{{ route('admin.promotions.send-email', $promotion) }}" method="POST" class="inline" data-confirm="Envoyer un email aux clients ayant mis ce produit en favori ?">
                                @csrf
                                <button type="submit" title="Envoyer par email" aria-label="Envoyer par email" class="mr-1 inline-flex h-8 w-8 items-center justify-center text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-envelope"></i></button>
                            </form>
                        @endif
                        <a href="{{ route('admin.promotions.edit', $promotion) }}" title="Modifier" aria-label="Modifier" class="inline-flex h-8 w-8 items-center justify-center text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-pen"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-10 text-center text-grey dark:text-white/40">Aucune promotion pour le moment.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $promotions->links() }}</div>

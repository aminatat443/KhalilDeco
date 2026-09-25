<div class="divide-y divide-secondary-shade/10 border border-secondary-shade/10 bg-white dark:divide-white/10 dark:border-white/10 dark:bg-[#16201f]">
    @forelse($movements as $movement)
        <div class="flex items-center justify-between gap-4 px-5 py-4 text-sm">
            <div class="min-w-0">
                <p class="text-secondary-shade dark:text-white">
                    {{ $movement->product?->name }}
                    @if($movement->variant)
                        <span class="text-grey dark:text-white/40">— {{ $movement->variant->label() }}</span>
                    @endif
                </p>
                <p class="mt-0.5 text-xs text-grey dark:text-white/40">
                    {{ $movement->user?->name ?? 'Système' }} · {{ $movement->created_at->format('d/m/Y à H:i') }}
                    @if($movement->reason) · {{ $movement->reason }} @endif
                </p>
            </div>
            <span class="shrink-0 px-2.5 py-1 text-[11px] font-semibold {{ $movement->type === 'entree' ? 'bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary' : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400' }}">
                {{ $movement->type === 'entree' ? '+' : '-' }}{{ $movement->quantity }}
            </span>
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 px-6 py-14 text-center">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-boxes-stacked"></i></span>
            <p class="text-sm text-grey dark:text-white/40">Aucun mouvement ne correspond à ces critères.</p>
        </div>
    @endforelse
</div>

<div class="mt-6" data-pagination>{{ $movements->links() }}</div>

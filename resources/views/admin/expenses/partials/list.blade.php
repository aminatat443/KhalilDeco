<div class="flex flex-wrap gap-4">
    <div class="border border-secondary-shade/10 bg-white px-4 py-2.5 dark:border-white/10 dark:bg-[#16201f]">
        <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">Total — {{ $period->label() }}</p>
        <p class="text-sm font-semibold text-secondary-shade dark:text-white">{{ number_format($total, 0, ',', ' ') }} FCFA</p>
    </div>
    @foreach($byCategory as $row)
        <div class="border border-secondary-shade/10 bg-white px-4 py-2.5 dark:border-white/10 dark:bg-[#16201f]">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">{{ \App\Models\Expense::CATEGORIES[$row->category] ?? $row->category }}</p>
            <p class="text-sm font-semibold text-secondary-shade dark:text-white">{{ number_format($row->total, 0, ',', ' ') }} FCFA</p>
        </div>
    @endforeach
</div>

<div class="mt-4 divide-y divide-secondary-shade/10 border border-secondary-shade/10 bg-white dark:divide-white/10 dark:border-white/10 dark:bg-[#16201f]">
    @forelse($expenses as $expense)
        <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
            <div class="min-w-0">
                <p class="text-secondary-shade dark:text-white">{{ $expense->label }}</p>
                <p class="text-xs text-grey dark:text-white/40">{{ \App\Models\Expense::CATEGORIES[$expense->category] ?? $expense->category }} · {{ $expense->expense_date->format('d/m/Y') }} · {{ $expense->user?->name ?? '—' }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($expense->amount, 0, ',', ' ') }} FCFA</span>
                @can('delete', $expense)
                    <form action="{{ route('admin.expenses.destroy', $expense) }}" method="POST" data-confirm="Supprimer cette dépense ?">
                        @csrf @method('DELETE')
                        <button class="text-grey/60 hover:text-red-600 dark:text-white/30 dark:hover:text-red-400"><i class="fa-solid fa-trash text-xs"></i></button>
                    </form>
                @endcan
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 px-6 py-14 text-center">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-receipt"></i></span>
            <p class="text-sm text-grey dark:text-white/40">Aucune dépense enregistrée sur cette période.</p>
        </div>
    @endforelse
</div>

<div class="mt-4" data-pagination>{{ $expenses->links() }}</div>

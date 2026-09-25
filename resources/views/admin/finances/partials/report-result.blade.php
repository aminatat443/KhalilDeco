<div class="flex flex-wrap items-center justify-end gap-2 print:hidden">
    <a href="{{ route('admin.finances.reports', array_merge(request()->query(), ['format' => 'csv'])) }}" class="inline-flex items-center gap-2 border border-secondary-shade px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:bg-secondary-shade hover:text-white dark:border-white/30 dark:text-white">
        <i class="fa-solid fa-file-csv"></i>Exporter en CSV
    </a>
    <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 border border-secondary-shade px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:bg-secondary-shade hover:text-white dark:border-white/30 dark:text-white">
        <i class="fa-solid fa-print"></i>Imprimer
    </button>
</div>

<div class="mt-4">
    <h2 class="hidden text-xl font-semibold text-secondary-shade print:block">{{ $report['title'] }}</h2>
    <p class="hidden text-xs text-grey print:block">{{ $period->label() }} — {{ $period->start->format('d/m/Y') }} au {{ $period->end->format('d/m/Y') }}</p>

    @if(! empty($report['description']))
        <p class="mt-1 text-xs italic text-grey dark:text-white/40">{{ $report['description'] }}</p>
    @endif

    @if(! empty($report['totals']))
        <div class="mt-4 flex flex-wrap gap-4 print:mt-2">
            @foreach($report['totals'] as $label => $value)
                <div class="border border-secondary-shade/10 bg-white px-4 py-2.5 dark:border-white/10 dark:bg-[#16201f] print:border-secondary-shade/30">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">{{ $label }}</p>
                    <p class="text-sm font-semibold text-secondary-shade dark:text-white">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-4 overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 print:border-secondary-shade/30">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40 print:text-secondary-shade">
                    @foreach($report['columns'] as $column)
                        <th class="px-4 py-3 font-medium">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
                @forelse($report['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td class="px-4 py-2.5 text-secondary-shade dark:text-white">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($report['columns']) }}" class="px-4 py-14">
                        <div class="flex flex-col items-center gap-3 text-center">
                            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-file-lines"></i></span>
                            <p class="text-sm text-grey dark:text-white/40">Aucune donnée disponible pour cette période.</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

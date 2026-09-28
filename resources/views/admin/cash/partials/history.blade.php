<div class="mt-4 space-y-3 lg:hidden">
    @forelse($history as $session)
        <div class="border border-secondary-shade/10 bg-white p-4 dark:border-white/10 dark:bg-[#16201f]">
            <p class="font-medium text-secondary-shade dark:text-white">{{ $session->user->name }}</p>
            <p class="text-xs text-grey dark:text-white/40">{{ $session->opened_at->format('d/m/Y H:i') }} → {{ $session->closed_at->format('d/m/Y H:i') }}</p>
            <dl class="mt-2 grid grid-cols-2 gap-2 text-xs">
                <div><dt class="text-grey/60 dark:text-white/30">Théorique</dt><dd class="text-secondary-shade dark:text-white">{{ number_format($session->closing_expected_amount, 0, ',', ' ') }} FCFA</dd></div>
                <div><dt class="text-grey/60 dark:text-white/30">Compté</dt><dd class="text-secondary-shade dark:text-white">{{ number_format($session->closing_declared_amount, 0, ',', ' ') }} FCFA</dd></div>
                <div class="col-span-2"><dt class="text-grey/60 dark:text-white/30">Écart</dt><dd class="{{ $session->discrepancy == 0 ? 'text-secondary-shade dark:text-white' : ($session->discrepancy > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') }}">{{ $session->discrepancy > 0 ? '+' : '' }}{{ number_format($session->discrepancy, 0, ',', ' ') }} FCFA</dd></div>
            </dl>
            @if($session->comment)
                <p class="mt-2 text-xs italic text-grey dark:text-white/40">{{ $session->comment }}</p>
            @endif
        </div>
    @empty
        <p class="border border-secondary-shade/10 bg-white p-6 text-center text-sm text-grey dark:border-white/10 dark:bg-[#16201f] dark:text-white/40">Aucune session clôturée pour le moment.</p>
    @endforelse
</div>

<div class="mt-4 hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Utilisateur</th>
                <th class="px-6 py-4 font-medium">Ouverte</th>
                <th class="px-6 py-4 font-medium">Clôturée</th>
                <th class="px-6 py-4 font-medium">Théorique</th>
                <th class="px-6 py-4 font-medium">Compté</th>
                <th class="px-6 py-4 font-medium">Écart</th>
                <th class="px-6 py-4 font-medium">Commentaire</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($history as $session)
                <tr>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $session->user->name }}</td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $session->closed_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ number_format($session->closing_expected_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ number_format($session->closing_declared_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="px-6 py-4 font-medium {{ $session->discrepancy == 0 ? 'text-secondary-shade dark:text-white' : ($session->discrepancy > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $session->discrepancy > 0 ? '+' : '' }}{{ number_format($session->discrepancy, 0, ',', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $session->comment ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-10 text-center text-sm text-grey dark:text-white/40">Aucune session clôturée pour le moment.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $history->links() }}</div>

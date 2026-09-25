<p class="mb-4 text-xs text-grey dark:text-white/40">{{ $clients->count() }} client(s) dans ce segment.</p>

<div class="space-y-3 lg:hidden">
    @forelse($clients as $client)
        <div class="border border-secondary-shade/10 bg-white p-4 dark:border-white/10 dark:bg-[#16201f]">
            <p class="font-medium text-secondary-shade dark:text-white">{{ $client['name'] }}</p>
            <p class="text-xs text-grey dark:text-white/40">{{ $client['email'] }}{{ $client['phone'] ? ' · '.$client['phone'] : '' }}</p>
            <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                <div><dt class="text-grey/60 dark:text-white/30">Compte créé</dt><dd class="text-secondary-shade dark:text-white">{{ $client['account_created_at']?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-grey/60 dark:text-white/30">Dernière commande</dt><dd class="text-secondary-shade dark:text-white">{{ $client['last_order_at'] ? \Illuminate\Support\Carbon::parse($client['last_order_at'])->format('d/m/Y') : '—' }}</dd></div>
                <div><dt class="text-grey/60 dark:text-white/30">Commandes</dt><dd class="text-secondary-shade dark:text-white">{{ $client['orders_count'] }}</dd></div>
                <div><dt class="text-grey/60 dark:text-white/30">Total dépensé</dt><dd class="text-secondary-shade dark:text-white">{{ $client['total_spent'] !== null ? number_format($client['total_spent'], 0, ',', ' ').' FCFA' : '—' }}</dd></div>
            </dl>
            <p class="mt-2 text-xs"><span class="text-grey/60 dark:text-white/30">Newsletter :</span> <span class="text-primary">Actif</span></p>
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 border border-secondary-shade/10 bg-white px-6 py-14 text-center dark:border-white/10 dark:bg-[#16201f]">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-users-viewfinder"></i></span>
            <p class="text-sm text-grey dark:text-white/40">Aucun client ne correspond à ces critères.</p>
        </div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Client</th>
                <th class="px-6 py-4 font-medium">Téléphone</th>
                <th class="px-6 py-4 font-medium">Compte créé</th>
                <th class="px-6 py-4 font-medium">Dernière commande</th>
                <th class="px-6 py-4 font-medium">Commandes</th>
                <th class="px-6 py-4 font-medium">Total dépensé</th>
                <th class="px-6 py-4 font-medium">Newsletter</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($clients as $client)
                <tr class="transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="px-6 py-4">
                        <p class="font-medium text-secondary-shade dark:text-white">{{ $client['name'] }}</p>
                        <p class="text-xs text-grey dark:text-white/40">{{ $client['email'] }}</p>
                    </td>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $client['phone'] ?? '—' }}</td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $client['account_created_at']?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $client['last_order_at'] ? \Illuminate\Support\Carbon::parse($client['last_order_at'])->format('d/m/Y') : '—' }}</td>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $client['orders_count'] }}</td>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $client['total_spent'] !== null ? number_format($client['total_spent'], 0, ',', ' ').' FCFA' : '—' }}</td>
                    <td class="px-6 py-4"><span class="px-2 py-0.5 text-[11px] font-semibold bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary">Actif</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-14">
                    <div class="flex flex-col items-center gap-3 text-center">
                        <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-users-viewfinder"></i></span>
                        <p class="text-sm text-grey dark:text-white/40">Aucun client ne correspond à ces critères.</p>
                    </div>
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

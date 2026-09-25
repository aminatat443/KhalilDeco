<div class="space-y-3 lg:hidden">
    @forelse($returns as $return)
        <div class="bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <span class="truncate font-medium text-secondary-shade dark:text-white">{{ $return->orderItem->product_name }}</span>
                <span class="shrink-0 text-xs text-grey dark:text-white/40">{{ $return->created_at->format('d/m/Y') }}</span>
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Commande</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $return->orderItem->order->order_number }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Motif</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">
                        {{ ['taille' => 'Dimensions', 'defaut' => 'Défaut', 'description' => 'Description', 'autre' => 'Autre'][$return->reason] ?? $return->reason }}
                        @if($return->description)
                            <span class="block text-grey/70 dark:text-white/30">{{ $return->description }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
            <form action="{{ route('admin.returns.update', $return) }}" method="POST" class="mt-3 border-t border-secondary-shade/10 pt-3 dark:border-white/10">
                @csrf
                @method('PUT')
                <select name="status" onchange="this.form.submit()" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-xs outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                    @foreach(['demandee' => 'Demandé', 'acceptee' => 'Accepté', 'refusee' => 'Refusé', 'article_recu' => 'Article reçu', 'remboursee' => 'Remboursé'] as $value => $label)
                        <option value="{{ $value }}" @selected($return->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    @empty
        <div class="bg-white px-6 py-10 text-center text-sm text-grey border border-secondary-shade/10 dark:bg-[#16201f] dark:text-white/40 dark:border-white/10">Aucune demande de retour.</div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Article</th>
                <th class="px-6 py-4 font-medium">Commande</th>
                <th class="px-6 py-4 font-medium">Motif</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($returns as $return)
                <tr>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $return->orderItem->product_name }}</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $return->orderItem->order->order_number }}</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">
                        {{ ['taille' => 'Dimensions', 'defaut' => 'Défaut', 'description' => 'Description', 'autre' => 'Autre'][$return->reason] ?? $return->reason }}
                        @if($return->description)
                            <span class="block text-xs text-grey/70 dark:text-white/30">{{ $return->description }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <form action="{{ route('admin.returns.update', $return) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            @method('PUT')
                            <select name="status" onchange="this.form.submit()" class="border border-secondary-shade/15 bg-white px-3.5 py-1 text-xs outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                                @foreach(['demandee' => 'Demandé', 'acceptee' => 'Accepté', 'refusee' => 'Refusé', 'article_recu' => 'Article reçu', 'remboursee' => 'Remboursé'] as $value => $label)
                                    <option value="{{ $value }}" @selected($return->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-right text-xs text-grey dark:text-white/40">{{ $return->created_at->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-10 text-center text-grey dark:text-white/40">Aucune demande de retour.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $returns->links() }}</div>

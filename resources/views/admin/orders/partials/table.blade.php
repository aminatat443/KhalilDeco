{{-- Cartes (mobile/tablette) — champs en liste verticale label/valeur, plus facile à lire
     qu'un tableau à 7 colonnes en dessous de md. --}}
<div class="space-y-3 lg:hidden">
    @forelse($orders as $order)
        <div onclick="window.location='{{ route('admin.orders.show', $order) }}'" class="cursor-pointer bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <span class="font-medium text-secondary-shade dark:text-white">
                    {{ $order->order_number }}
                    @if($order->is_in_store)
                        <i class="fa-solid fa-store ml-1.5 text-[11px] text-grey/50 dark:text-white/30" title="Vente en boutique"></i>
                    @endif
                </span>
                <x-order-status-badge :order="$order" />
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Client</dt>
                    <dd class="mt-0.5 truncate text-secondary-shade dark:text-white">{{ $order->customer_name }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Total</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ number_format($order->total, 0, ',', ' ') }} FCFA</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Paiement</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $order->payment_status === 'paid' ? 'Payé' : 'En attente' }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Date</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
            <div class="mt-3 flex items-center justify-end gap-4 border-t border-secondary-shade/10 pt-3 dark:border-white/10" onclick="event.stopPropagation()">
                <button type="button" @click.stop="window.dispatchEvent(new CustomEvent('open-invoice-preview', { detail: { url: '{{ $order->invoiceUrl() }}', label: '{{ $order->order_number }}', phone: '{{ $order->customer_phone }}' } }))" class="text-xs text-secondary-shade hover:text-primary dark:text-white/70" aria-label="Facture">
                    <i class="fa-solid fa-file-invoice"></i> Facture
                </button>
                <a href="{{ route('admin.orders.show', $order) }}" title="Voir" aria-label="Voir" class="text-xs text-secondary-shade hover:text-primary dark:text-white/70">
                    <i class="fa-solid fa-eye"></i> Voir
                </a>
            </div>
        </div>
    @empty
        <div class="bg-white px-6 py-10 text-center text-sm text-grey border border-secondary-shade/10 dark:bg-[#16201f] dark:text-white/40 dark:border-white/10">Aucune commande ne correspond à ces critères.</div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">N°</th>
                <th class="px-6 py-4 font-medium">Client</th>
                <th class="px-6 py-4 font-medium">Total</th>
                <th class="px-6 py-4 font-medium">Paiement</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium">Date</th>
                <th class="px-6 py-4 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($orders as $order)
                <tr onclick="window.location='{{ route('admin.orders.show', $order) }}'" class="cursor-pointer transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="px-6 py-4 font-medium text-secondary-shade dark:text-white">
                        {{ $order->order_number }}
                        @if($order->is_in_store)
                            <i class="fa-solid fa-store ml-1.5 text-[11px] text-grey/50 dark:text-white/30" title="Vente en boutique"></i>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $order->customer_name }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-secondary-shade dark:text-white">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $order->payment_status === 'paid' ? 'Payé' : 'En attente' }}</td>
                    <td class="px-6 py-4" onclick="event.stopPropagation()">
                        <x-order-status-badge :order="$order" />
                    </td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-right" onclick="event.stopPropagation()">
                        <button type="button" @click.stop="window.dispatchEvent(new CustomEvent('open-invoice-preview', { detail: { url: '{{ $order->invoiceUrl() }}', label: '{{ $order->order_number }}', phone: '{{ $order->customer_phone }}' } }))" class="mr-4 text-xs text-secondary-shade hover:text-primary dark:text-white/70" aria-label="Facture">
                            <i class="fa-solid fa-file-invoice"></i>
                        </button>
                        <a href="{{ route('admin.orders.show', $order) }}" title="Voir" aria-label="Voir" class="inline-flex h-8 w-8 items-center justify-center text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-eye"></i></a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-grey dark:text-white/40">Aucune commande ne correspond à ces critères.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $orders->links() }}</div>

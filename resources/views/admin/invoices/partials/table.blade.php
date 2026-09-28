<div class="flex items-center justify-between px-1 pb-3 text-xs text-grey dark:text-white/40">
    <span>{{ $orders->total() }} facture(s)</span>
    <span class="font-medium text-secondary-shade dark:text-white">Total période : {{ number_format($periodTotal, 0, ',', ' ') }} FCFA</span>
</div>

<div class="space-y-3 lg:hidden">
    @forelse($orders as $order)
        <div @click="window.dispatchEvent(new CustomEvent('open-invoice-preview', { detail: { url: '{{ $order->invoiceUrl() }}', label: '{{ $order->order_number }}', phone: '{{ $order->customer_phone }}' } }))" class="cursor-pointer bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <span class="font-medium text-secondary-shade dark:text-white">{{ $order->order_number }}</span>
                <span class="flex shrink-0 items-center gap-3">
                    <button type="button" @click.stop="window.dispatchEvent(new CustomEvent('open-invoice-preview', { detail: { url: '{{ $order->invoiceUrl() }}', label: '{{ $order->order_number }}', phone: '{{ $order->customer_phone }}', autoPrint: true } }))" title="Imprimer" aria-label="Imprimer" class="text-secondary-shade hover:text-primary dark:text-white/70">
                        <i class="fa-solid fa-print"></i>
                    </button>
                    <span class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70"><i class="fa-solid fa-file-invoice mr-1"></i>Voir</span>
                </span>
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Client</dt>
                    <dd class="mt-0.5 truncate text-secondary-shade dark:text-white">{{ $order->customer_name }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Date</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $order->created_at->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Paiement</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $order->paymentMethodLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Montant</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ number_format($order->total, 0, ',', ' ') }} FCFA</dd>
                </div>
            </dl>
        </div>
    @empty
        <div class="bg-white px-6 py-10 text-center text-sm text-grey border border-secondary-shade/10 dark:bg-[#16201f] dark:text-white/40 dark:border-white/10">Aucune facture pour cette période.</div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">N° facture</th>
                <th class="px-6 py-4 font-medium">Client</th>
                <th class="px-6 py-4 font-medium">Date</th>
                <th class="px-6 py-4 font-medium">Paiement</th>
                <th class="px-6 py-4 font-medium">Montant</th>
                <th class="px-6 py-4 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($orders as $order)
                <tr>
                    <td class="px-6 py-4 font-medium text-secondary-shade dark:text-white">{{ $order->order_number }}</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $order->customer_name }}</td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $order->created_at->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $order->paymentMethodLabel() }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-secondary-shade dark:text-white">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                    <td class="px-6 py-4 text-right">
                        <button type="button" @click="window.dispatchEvent(new CustomEvent('open-invoice-preview', { detail: { url: '{{ $order->invoiceUrl() }}', label: '{{ $order->order_number }}', phone: '{{ $order->customer_phone }}', autoPrint: true } }))" title="Imprimer" aria-label="Imprimer" class="mr-4 text-secondary-shade hover:text-primary dark:text-white/70">
                            <i class="fa-solid fa-print"></i>
                        </button>
                        <button type="button" @click="window.dispatchEvent(new CustomEvent('open-invoice-preview', { detail: { url: '{{ $order->invoiceUrl() }}', label: '{{ $order->order_number }}', phone: '{{ $order->customer_phone }}' } }))" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">
                            <i class="fa-solid fa-file-invoice mr-1"></i>Voir
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-10 text-center text-grey dark:text-white/40">Aucune facture pour cette période.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $orders->links() }}</div>

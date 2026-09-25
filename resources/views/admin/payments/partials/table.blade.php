@php($paymentLabels = \App\Models\Order::PAYMENT_METHOD_LABELS)
@php($gatewayLabels = ['paytech' => 'PayTech', 'wave' => 'Wave (direct)', 'orange_money' => 'Orange Money (direct)', 'paydunya' => 'PayDunya'])
@php($toneClasses = ['neutral' => 'bg-grey-tint text-grey dark:bg-white/10 dark:text-white/50', 'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400', 'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400', 'green' => 'bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary', 'red' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400'])
@php($statusTones = collect(\App\Models\Payment::STATUS_TONES)->map(fn ($tone) => $toneClasses[$tone])->all())
@php($statusLabels = \App\Models\Payment::STATUS_LABELS)

<p class="mb-4 text-xs text-grey dark:text-white/40">{{ $payments->total() }} {{ Str::plural('paiement', $payments->total()) }}</p>

<div class="space-y-3 lg:hidden">
    @forelse($payments as $payment)
        <div class="border border-secondary-shade/10 bg-white p-4 dark:border-white/10 dark:bg-[#16201f]">
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-medium text-secondary-shade hover:text-primary dark:text-white">{{ $payment->order?->order_number ?? '—' }}</a>
                <span class="px-2 py-0.5 text-[11px] font-semibold {{ $statusTones[$payment->status] }}">{{ $statusLabels[$payment->status] }}</span>
            </div>
            <p class="mt-1 text-xs text-grey dark:text-white/40">{{ $payment->order?->customer_name ?? '—' }}</p>
            <p class="mt-1 text-xs text-grey dark:text-white/40">{{ $paymentLabels[$payment->provider] ?? $payment->provider }}{{ $payment->gateway ? ' · '.($gatewayLabels[$payment->gateway] ?? $payment->gateway) : '' }} · {{ $payment->created_at->format('d/m/Y H:i') }}</p>
            <p class="mt-2 font-medium text-secondary-shade dark:text-white">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</p>
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 border border-secondary-shade/10 bg-white px-6 py-14 text-center dark:border-white/10 dark:bg-[#16201f]">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-money-check-dollar"></i></span>
            <p class="text-sm text-grey dark:text-white/40">Aucun paiement ne correspond à ces critères.</p>
        </div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Commande</th>
                <th class="px-6 py-4 font-medium">Client</th>
                <th class="px-6 py-4 font-medium">Moyen</th>
                <th class="px-6 py-4 font-medium">Prestataire</th>
                <th class="px-6 py-4 font-medium">Référence</th>
                <th class="px-6 py-4 font-medium">Montant</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($payments as $payment)
                <tr class="transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="px-6 py-4"><a href="{{ route('admin.orders.show', $payment->order) }}" class="font-medium text-secondary-shade hover:text-primary dark:text-white">{{ $payment->order?->order_number ?? '—' }}</a></td>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $payment->order?->customer_name ?? '—' }}</td>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $paymentLabels[$payment->provider] ?? $payment->provider }}</td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $payment->gateway ? ($gatewayLabels[$payment->gateway] ?? $payment->gateway) : 'Manuel' }}</td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">
                        {{ $payment->transaction_id ?? '—' }}
                        @if($payment->paytechToken())
                            <span class="block text-[10px] text-grey/70 dark:text-white/30">Jeton : {{ $payment->paytechToken() }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 font-medium text-secondary-shade dark:text-white">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                    <td class="px-6 py-4"><span class="px-2 py-0.5 text-[11px] font-semibold {{ $statusTones[$payment->status] }}">{{ $statusLabels[$payment->status] }}</span></td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-6 py-14">
                    <div class="flex flex-col items-center gap-3 text-center">
                        <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-money-check-dollar"></i></span>
                        <p class="text-sm text-grey dark:text-white/40">Aucun paiement ne correspond à ces critères.</p>
                    </div>
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $payments->links() }}</div>

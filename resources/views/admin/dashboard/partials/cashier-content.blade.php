<div class="flex flex-wrap items-end justify-between gap-4 border-b border-secondary-shade/10 pb-6 dark:border-white/10">
    <div>
        <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-grey dark:text-white/40">
            <span class="h-1.5 w-1.5 bg-primary"></span>
            Caisse — {{ $period->label() }}
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-secondary-shade dark:text-white">Bon retour, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        @can('viewAny', App\Models\CashSession::class)
            <a href="{{ route('admin.cash.index') }}" class="inline-flex items-center gap-2 bg-secondary-shade px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-white transition hover:bg-primary">
                <i class="fa-solid fa-cash-register"></i>Caisse
            </a>
        @endcan
        @include('admin.dashboard.partials.period-filter')
    </div>
</div>

@php
    $rangeParams = ['from' => $period->start->format('Y-m-d'), 'to' => $period->end->format('Y-m-d')];
@endphp
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-dashboard-kpi icon="fa-cash-register" label="Ventes" :value="$salesCount" :href="route('admin.orders.index', ['flag' => 'confirmed_sales'] + $rangeParams)" />
    <x-dashboard-kpi icon="fa-sack-dollar" label="Montant encaissé" :value="number_format($collectedTotal, 0, ',', ' ').' FCFA'" :href="route('admin.payments.index', ['status' => 'success', 'date_field' => 'paid_at'] + $rangeParams)" />
    <x-dashboard-kpi icon="fa-hourglass-half" label="Paiements en attente" :value="$pendingPayments" :href="route('admin.payments.index', ['status' => 'pending', 'date_field' => 'created_at'] + $rangeParams)" />
    <x-dashboard-kpi icon="fa-circle-exclamation" label="Paiements échoués" :value="$failedPayments" :href="route('admin.payments.index', ['status' => 'failed', 'date_field' => 'created_at'] + $rangeParams)" />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Encaissements par moyen de paiement</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @php($paymentLabels = \App\Models\Order::PAYMENT_METHOD_LABELS)
            @forelse($collectedByMethod as $row)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="text-secondary-shade dark:text-white">{{ $paymentLabels[$row->payment_method] ?? $row->payment_method }}</span>
                    <span class="flex items-center gap-3">
                        <span class="text-xs text-grey dark:text-white/40">{{ $row->count }}</span>
                        <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($row->total, 0, ',', ' ') }} FCFA</span>
                    </span>
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucun encaissement sur cette période.</p>
            @endforelse
        </div>
        @if($refunds->isNotEmpty())
            <div class="mt-4 border-t border-secondary-shade/10 pt-3 text-sm dark:border-white/10">
                <div class="flex items-center justify-between">
                    <span class="text-grey dark:text-white/40">Remboursements</span>
                    <span class="font-medium text-red-600 dark:text-red-400">-{{ number_format($refundsTotal, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        @endif
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Commandes à encaisser</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-primary hover:underline">Voir les commandes</a>
        </div>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($toCollect as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                    <span class="min-w-0">
                        <span class="font-medium text-secondary-shade dark:text-white">{{ $order->order_number }}</span>
                        <span class="ml-2 truncate text-xs text-grey dark:text-white/40">{{ $order->customer_name }}</span>
                    </span>
                    <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                </a>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Rien à encaisser pour le moment.</p>
            @endforelse
        </div>
    </div>

</div>

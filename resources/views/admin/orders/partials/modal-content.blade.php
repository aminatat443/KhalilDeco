@php
    $collected = $order->payments->where('status', 'success')->sum('amount');
    $badgeTones = [
        'neutral' => 'border-secondary-shade/15 text-grey dark:border-white/15 dark:text-white/60',
        'blue' => 'border-blue-200 text-blue-700 dark:border-blue-500/30 dark:text-blue-400',
        'amber' => 'border-tertiary-shade/40 text-tertiary-shade dark:border-tertiary/30 dark:text-tertiary',
        'primary' => 'border-primary/40 text-primary-shade dark:border-primary/40 dark:text-primary',
        'green' => 'border-green-300 text-green-700 dark:border-green-500/30 dark:text-green-400',
        'red' => 'border-red-300 text-red-600 dark:border-red-500/30 dark:text-red-400',
    ];
    $badgeToneClass = $badgeTones[\App\Models\Order::STATUS_TONES[$order->status] ?? 'neutral'];
@endphp

<div class="flex items-start justify-between gap-4 border-b border-secondary-shade/10 pb-4 dark:border-white/10">
    <div>
        <h2 class="text-lg font-semibold text-secondary-shade dark:text-white">{{ $order->order_number }}</h2>
        <p class="mt-0.5 text-xs text-grey dark:text-white/40">{{ $order->created_at->translatedFormat('d M Y à H:i') }}</p>
    </div>
    <span class="shrink-0 border px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] {{ $badgeToneClass }}">
        {{ \App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status }}
    </span>
</div>

@if($order->status === 'en_attente_paiement')
    <p class="mt-4 flex items-center gap-2 border border-tertiary/30 bg-tertiary/10 px-4 py-3 text-sm text-tertiary-shade">
        <i class="fa-solid fa-hourglass-half"></i> Cette commande attend la confirmation du paiement.
    </p>
@elseif($order->payment_status === 'paid')
    <p class="mt-4 flex items-center gap-2 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
        <i class="fa-solid fa-circle-check"></i> Paiement confirmé — {{ number_format($collected, 0, ',', ' ') }} FCFA par {{ $order->paymentMethodLabel() }}.
    </p>
@endif

<div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">

    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Client</h3>
        <dl class="mt-2 space-y-1.5 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-grey dark:text-white/40">Nom</dt><dd class="text-right text-secondary-shade dark:text-white">{{ $order->customer_name }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-grey dark:text-white/40">Téléphone</dt><dd class="text-right text-secondary-shade dark:text-white">{{ $order->customer_phone }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-grey dark:text-white/40">Email</dt><dd class="truncate text-right text-secondary-shade dark:text-white">{{ $order->customer_email }}</dd></div>
        </dl>
    </div>

    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Livraison</h3>
        <dl class="mt-2 space-y-1.5 text-sm">
            @if($order->is_in_store)
                <div class="flex justify-between gap-3"><dt class="text-grey dark:text-white/40">Mode</dt><dd class="text-right text-secondary-shade dark:text-white">Retrait en boutique</dd></div>
            @else
                <div class="flex justify-between gap-3"><dt class="text-grey dark:text-white/40">Adresse</dt><dd class="text-right text-secondary-shade dark:text-white">{{ $order->delivery_address }}, {{ $order->delivery_city }}</dd></div>
            @endif
            <div class="flex justify-between gap-3"><dt class="text-grey dark:text-white/40">Paiement</dt><dd class="text-right text-secondary-shade dark:text-white">{{ $order->paymentMethodLabel() }}</dd></div>
            @if($latestReference = $order->payments->last()?->transaction_id)
                <div class="flex justify-between gap-3"><dt class="text-grey dark:text-white/40">Référence</dt><dd class="truncate text-right text-secondary-shade dark:text-white">{{ $latestReference }}</dd></div>
            @endif
        </dl>
    </div>

</div>

<div class="mt-5">
    <h3 class="text-[11px] font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Produits</h3>
    <div class="mt-2 divide-y divide-secondary-shade/10 dark:divide-white/10">
        @foreach($order->items as $item)
            <div class="flex items-center justify-between gap-3 py-2 text-sm">
                <div class="min-w-0">
                    <p class="truncate font-medium text-secondary-shade dark:text-white">{{ $item->product_name }}</p>
                    <p class="text-xs text-grey dark:text-white/40">Qté {{ $item->quantity }}@if($item->variant_label) · {{ $item->variant_label }}@endif</p>
                </div>
                <p class="shrink-0 whitespace-nowrap font-medium text-secondary-shade dark:text-white">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</p>
            </div>
        @endforeach
    </div>
    <div class="mt-2 space-y-1 border-t border-secondary-shade/10 pt-2 text-sm text-grey dark:border-white/10 dark:text-white/50">
        <div class="flex justify-between"><span>Sous-total</span><span>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</span></div>
        <div class="flex justify-between"><span>Livraison</span><span>{{ number_format($order->delivery_fee, 0, ',', ' ') }} FCFA</span></div>
        @if($order->discount > 0)
            <div class="flex justify-between text-primary"><span>Réduction</span><span>-{{ number_format($order->discount, 0, ',', ' ') }} FCFA</span></div>
        @endif
    </div>
    <div class="mt-1 flex justify-between border-t border-secondary-shade/10 pt-2 text-sm font-semibold text-secondary-shade dark:border-white/10 dark:text-white">
        <span>Total</span><span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
    </div>
</div>

@if(! in_array($order->status, ['annulee', 'livree']))
    <div class="mt-5 flex flex-wrap gap-2 border-t border-secondary-shade/10 pt-4 dark:border-white/10">
        @if($order->status === 'recue')
            <form action="{{ route('admin.orders.confirm', $order) }}" method="POST" data-confirm="Confirmer cette commande ? Le stock sera décrémenté.">
                @csrf
                <button type="submit" class="bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-white transition hover:bg-primary-shade">Confirmer</button>
            </form>
        @endif
        @if(! in_array($order->status, ['annulee', 'livree']))
            @can('cancel', $order)
                <form action="{{ route('admin.orders.cancel', $order) }}" method="POST" data-confirm="Annuler cette commande ?">
                    @csrf
                    <button type="submit" class="border border-red-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-red-600 transition hover:bg-red-600 hover:text-white dark:border-red-500/30 dark:text-red-400">Annuler</button>
                </form>
            @endcan
        @endif
        <a href="{{ route('admin.orders.show', $order) }}" class="ml-auto self-center text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">
            Voir la page complète <i class="fa-solid fa-arrow-right ml-1"></i>
        </a>
    </div>
@else
    <div class="mt-5 border-t border-secondary-shade/10 pt-4 text-right dark:border-white/10">
        <a href="{{ route('admin.orders.show', $order) }}" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">
            Voir la page complète <i class="fa-solid fa-arrow-right ml-1"></i>
        </a>
    </div>
@endif

@if($order->paymentEvents->isNotEmpty())
    <div class="mt-5 border-t border-secondary-shade/10 pt-4 dark:border-white/10">
        <h3 class="text-[11px] font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Historique</h3>
        <ul class="mt-2 space-y-1.5 text-xs text-grey dark:text-white/50">
            @foreach($order->paymentEvents as $event)
                <li class="flex justify-between gap-3">
                    <span>{{ $event->label() }}</span>
                    <span>{{ $event->created_at->format('d/m H:i') }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

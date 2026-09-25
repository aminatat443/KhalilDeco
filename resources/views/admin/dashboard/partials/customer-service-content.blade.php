<div class="flex flex-wrap items-end justify-between gap-4 border-b border-secondary-shade/10 pb-6 dark:border-white/10">
    <div>
        <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-grey dark:text-white/40">
            <span class="h-1.5 w-1.5 bg-primary"></span>
            Service client — {{ $period->label() }}
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-secondary-shade dark:text-white">Bon retour, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
    </div>
    @include('admin.dashboard.partials.period-filter')
</div>

<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-dashboard-kpi icon="fa-inbox" label="Commandes reçues" :value="$pendingOrdersCount" :href="route('admin.orders.index', ['status' => 'recue'])" />
    <x-dashboard-kpi icon="fa-truck-fast" label="Non livrées (+5j)" :value="$notDeliveredCount" :href="route('admin.orders.index', ['flag' => 'overdue_shipping'])" />
    <x-dashboard-kpi icon="fa-rotate-left" label="Retours à traiter" :value="$returnsCount" :href="route('admin.returns.index', ['flag' => 'pending'])" />
    <x-dashboard-kpi icon="fa-cart-shopping" label="Paniers abandonnés" :value="$abandonedCartsCount" />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Commandes à traiter</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($pendingOrders as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                    <span class="min-w-0">
                        <span class="font-medium text-secondary-shade dark:text-white">{{ $order->order_number }}</span>
                        <span class="ml-2 truncate text-xs text-grey dark:text-white/40">{{ $order->customer_name }}</span>
                    </span>
                    <span class="text-xs text-grey dark:text-white/40">{{ $order->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Rien à traiter.</p>
            @endforelse
        </div>
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Commandes non livrées depuis 5 jours</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($notDelivered as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                    <span class="min-w-0">
                        <span class="font-medium text-secondary-shade dark:text-white">{{ $order->order_number }}</span>
                        <span class="ml-2 truncate text-xs text-grey dark:text-white/40">{{ $order->customer_name }}</span>
                    </span>
                    <x-status-pill :tone="\App\Models\Order::STATUS_TONES[$order->status] ?? 'neutral'" :label="\App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status" />
                </a>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune commande bloquée.</p>
            @endforelse
        </div>
    </div>

</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Retours / réclamations</h2>
            <a href="{{ route('admin.returns.index') }}" class="text-xs text-primary hover:underline">Tout voir</a>
        </div>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($returns as $return)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="min-w-0 truncate text-secondary-shade dark:text-white">{{ $return->orderItem?->order?->order_number }} — {{ $return->orderItem?->product_name }}</span>
                    <x-status-pill :tone="\App\Models\ProductReturn::STATUS_TONES[$return->status] ?? 'neutral'" :label="\App\Models\ProductReturn::STATUS_LABELS[$return->status] ?? $return->status" />
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucun retour en attente.</p>
            @endforelse
        </div>
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Clients ayant abandonné leur panier</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($abandonedCarts as $cart)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="min-w-0 truncate text-secondary-shade dark:text-white">{{ $cart->user?->name ?? '—' }}</span>
                    <span class="text-xs text-grey dark:text-white/40">{{ $cart->updated_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucun panier abandonné actuellement.</p>
            @endforelse
        </div>
    </div>

</div>

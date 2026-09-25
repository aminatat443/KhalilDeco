<div class="flex flex-wrap items-end justify-between gap-4 border-b border-secondary-shade/10 pb-6 dark:border-white/10">
    <div>
        <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-grey dark:text-white/40">
            <span class="h-1.5 w-1.5 bg-primary"></span>
            Boutique — {{ $period->label() }}
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-secondary-shade dark:text-white">Bon retour, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
    </div>
    @include('admin.dashboard.partials.period-filter')
</div>

<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-dashboard-kpi icon="fa-calendar-day" label="Commandes aujourd'hui" :value="$ordersToday" :href="route('admin.orders.index', ['date' => today()->format('Y-m-d')])" />
    <x-dashboard-kpi icon="fa-box-open" label="À préparer" :value="$toPrepare" :href="route('admin.orders.index', ['status' => 'confirmee'])" />
    <x-dashboard-kpi icon="fa-truck" label="À expédier" :value="$toShip" :href="route('admin.orders.index', ['status' => 'en_preparation'])" />
    <x-dashboard-kpi icon="fa-triangle-exclamation" label="Stock faible" :value="$lowStockCount" :href="route('admin.products.index', ['stock' => 'low'])" />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Ventes récentes</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-primary hover:underline">Tout voir</a>
        </div>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($recentSales as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                    <span class="min-w-0">
                        <span class="font-medium text-secondary-shade dark:text-white">{{ $order->order_number }}</span>
                        <span class="ml-2 truncate text-xs text-grey dark:text-white/40">{{ $order->customer_name }}</span>
                    </span>
                    <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                </a>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune vente récente.</p>
            @endforelse
        </div>
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Activité récente</h2>
        <p class="mt-1 text-xs text-grey dark:text-white/40">{{ $newClients }} nouveau(x) client(s) sur cette période · {{ $delivered }} livrée(s)</p>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($recentActivity as $order)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="min-w-0 truncate text-secondary-shade dark:text-white">{{ $order->order_number }} — {{ $order->customer_name }}</span>
                    <x-status-pill :tone="\App\Models\Order::STATUS_TONES[$order->status] ?? 'neutral'" :label="\App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status" />
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune activité sur cette période.</p>
            @endforelse
        </div>
    </div>

</div>

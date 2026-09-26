<div class="flex flex-wrap items-end justify-between gap-4 border-b border-secondary-shade/10 pb-6 dark:border-white/10">
    <div>
        <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-grey dark:text-white/40">
            <span class="h-1.5 w-1.5 bg-primary"></span>
            Vue globale — {{ $period->label() }}
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-secondary-shade dark:text-white">Bon retour, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
        <p class="mt-1 max-w-md text-sm text-grey dark:text-white/50">Vue d'ensemble de toute l'activité de Khalil Déco.</p>
    </div>
    @include('admin.dashboard.partials.period-filter')
</div>

@php
    $rangeParams = ['from' => $period->start->format('Y-m-d'), 'to' => $period->end->format('Y-m-d')];
@endphp
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-dashboard-kpi icon="fa-sack-dollar" label="Chiffre d'affaires" :value="number_format($revenue, 0, ',', ' ').' FCFA'" :trend="$revenueTrend" :href="route('admin.finances.index')" />
    <x-dashboard-kpi icon="fa-bag-shopping" label="Commandes" :value="$ordersCount" :trend="$ordersTrend" :href="route('admin.orders.index', $rangeParams)" :sub="$confirmedCount.' confirmée(s) — '.$confirmationRate.'%'" />
    <div class="flex items-center gap-4 border border-secondary-shade/10 bg-white p-4 dark:border-white/10 dark:bg-[#16201f] sm:p-6">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center border border-secondary-shade/10 text-secondary-shade dark:border-white/10 dark:text-white/70">
            <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-xs font-semibold uppercase tracking-[0.12em] text-grey dark:text-white/40">Payées / impayées</p>
            <p class="mt-1 text-lg font-semibold text-secondary-shade dark:text-white sm:text-2xl">
                <a href="{{ route('admin.orders.index', ['payment_status' => 'paid'] + $rangeParams) }}" class="hover:text-primary">{{ $paidCount }}</a>
                <span class="text-grey dark:text-white/40">/</span>
                <a href="{{ route('admin.orders.index', ['flag' => 'unpaid'] + $rangeParams) }}" class="hover:text-primary">{{ $unpaidCount }}</a>
            </p>
        </div>
    </div>
    <x-dashboard-kpi icon="fa-users" label="Nouveaux clients" :value="$newClients" :trend="$newClientsTrend" :href="route('admin.users.index', ['role' => 'client'] + $rangeParams)" />
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-dashboard-kpi icon="fa-triangle-exclamation" label="Ruptures de stock" :value="$outOfStockCount" :href="route('admin.products.index', ['stock' => 'out'])" />
    <x-dashboard-kpi icon="fa-cart-shopping" label="Paniers abandonnés" :value="$abandonedCartsCount" :sub="number_format($abandonedCartsValue, 0, ',', ' ').' FCFA (prix actuels)'" />
    <x-dashboard-kpi icon="fa-envelope-open-text" label="Emails envoyés" :value="$emailsSent" :sub="$campaignsFailed.' échec(s)'" :href="route('admin.campaigns.index', $rangeParams).'#historique-campagnes'" />
    <x-dashboard-kpi icon="fa-user-clock" label="Équipe en ligne" :value="$onlineStaffCount" :href="route('admin.users.index', ['status' => 'online'])" />
</div>

{{-- Distinctes du chiffre d'affaires ci-dessus (commandes confirmées uniquement) — jamais
     mélanger "commande créée" et "paiement réellement encaissé" (section 32 du cahier des
     charges commande/paiement). --}}
<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-dashboard-kpi icon="fa-hourglass-half" label="Commandes en attente de paiement" :value="$pendingPaymentCount" :href="route('admin.orders.index', ['status' => 'en_attente_paiement'] + $rangeParams)" />
    {{-- Pas de lien : aucune page ne filtre par événement payment_failed (source de cette
         donnée) — même règle que les autres cartes du tableau de bord (jamais un lien vers une
         liste qui ne reproduirait pas exactement ce compte). --}}
    <x-dashboard-kpi icon="fa-triangle-exclamation" label="Paiements échoués" :value="$failedPaymentCount" />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Paiements par statut</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($paymentsByStatus as $row)
                @php($labels = ['pending' => 'En attente', 'success' => 'Réussi', 'failed' => 'Échoué', 'refunded' => 'Remboursé'])
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="text-secondary-shade dark:text-white">{{ $labels[$row->status] ?? $row->status }}</span>
                    <span class="flex items-center gap-3">
                        <span class="text-xs text-grey dark:text-white/40">{{ $row->count }}</span>
                        <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($row->total, 0, ',', ' ') }} FCFA</span>
                    </span>
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucun paiement sur cette période.</p>
            @endforelse
        </div>
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Codes promo</h2>
        <div class="mt-4 space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-grey dark:text-white/40">Utilisations</span>
                <span class="font-medium text-secondary-shade dark:text-white">{{ $couponUsage }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-grey dark:text-white/40">CA associé</span>
                <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($couponRevenue, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-grey dark:text-white/40">Retours en attente</span>
                <span class="font-medium text-secondary-shade dark:text-white">{{ $pendingReturns }}</span>
            </div>
            <div class="flex items-center justify-between border-t border-secondary-shade/10 pt-3 dark:border-white/10">
                <span class="text-grey dark:text-white/40">Dépenses</span>
                <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($expenses, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-grey dark:text-white/40">Marge brute</span>
                @if($margin['available'])
                    <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($margin['margin'], 0, ',', ' ') }} FCFA <span class="text-[10px] text-grey/60 dark:text-white/30">({{ $margin['coverageRatio'] }}% couvert)</span></span>
                @else
                    <span class="text-xs italic text-grey/60 dark:text-white/30">Donnée non disponible</span>
                @endif
            </div>
            <div class="flex items-center justify-between">
                <span class="text-grey dark:text-white/40">Résultat net</span>
                @if($netResult !== null)
                    <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($netResult, 0, ',', ' ') }} FCFA</span>
                @else
                    <span class="text-xs italic text-grey/60 dark:text-white/30">Donnée non disponible</span>
                @endif
            </div>
        </div>
        @unless($margin['available'])
            <p class="mt-4 text-[11px] italic text-grey/60 dark:text-white/30">Marge/résultat net : aucun produit vendu sur la période n'a de coût d'achat renseigné.</p>
        @endunless
    </div>

</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Commandes récentes</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-primary hover:underline">Tout voir</a>
        </div>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($recentOrders as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between gap-3 py-3 text-sm transition hover:text-primary">
                    <span class="min-w-0">
                        <span class="font-medium text-secondary-shade dark:text-white">{{ $order->order_number }}</span>
                        <span class="ml-2 truncate text-xs text-grey dark:text-white/40">{{ $order->customer_name }}</span>
                    </span>
                    <x-status-pill :tone="\App\Models\Order::STATUS_TONES[$order->status] ?? 'neutral'" :label="\App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status" />
                </a>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune commande.</p>
            @endforelse
        </div>
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Stock faible</h2>
            <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="text-xs text-primary hover:underline">Voir le stock</a>
        </div>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($lowStockVariants as $variant)
                <a href="{{ route('admin.products.edit', $variant->product) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                    <span class="text-secondary-shade dark:text-white">{{ $variant->product->name }} <span class="text-xs text-grey dark:text-white/40">#{{ $variant->sku }}</span></span>
                    <span class="text-xs font-medium text-amber-600 dark:text-amber-400">{{ $variant->stock }} restant(s)</span>
                </a>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune alerte de stock faible.</p>
            @endforelse
        </div>
    </div>

</div>

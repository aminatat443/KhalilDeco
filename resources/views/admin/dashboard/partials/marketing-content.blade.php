<div class="flex flex-wrap items-end justify-between gap-4 border-b border-secondary-shade/10 pb-6 dark:border-white/10">
    <div>
        <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-grey dark:text-white/40">
            <span class="h-1.5 w-1.5 bg-primary"></span>
            Marketing — {{ $period->label() }}
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-secondary-shade dark:text-white">Bon retour, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
    </div>
    @include('admin.dashboard.partials.period-filter')
</div>

@php
    $rangeParams = ['from' => $period->start->format('Y-m-d'), 'to' => $period->end->format('Y-m-d')];
@endphp
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-dashboard-kpi icon="fa-bag-shopping" label="Commandes" :value="$ordersCount" :href="route('admin.orders.index', ['flag' => 'confirmed_sales'] + $rangeParams)" />
    <x-dashboard-kpi icon="fa-sack-dollar" label="CA de la période" :value="number_format($revenue, 0, ',', ' ').' FCFA'" :href="route('admin.finances.index')" />
    <x-dashboard-kpi icon="fa-receipt" label="Panier moyen" :value="number_format($averageOrder, 0, ',', ' ').' FCFA'" />
    <div class="flex items-center gap-4 border border-secondary-shade/10 bg-white p-4 dark:border-white/10 dark:bg-[#16201f] sm:p-6">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center border border-secondary-shade/10 text-secondary-shade dark:border-white/10 dark:text-white/70">
            <i class="fa-solid fa-users text-sm"></i>
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-xs font-semibold uppercase tracking-[0.12em] text-grey dark:text-white/40">Nouveaux / récurrents</p>
            <p class="mt-1 text-lg font-semibold text-secondary-shade dark:text-white sm:text-2xl">
                <a href="{{ route('admin.users.index', ['role' => 'client'] + $rangeParams) }}" class="hover:text-primary">{{ $newClients }}</a>
                <span class="text-grey dark:text-white/40">/</span>
                <a href="{{ route('admin.users.index', ['flag' => 'repeat_customers', 'repeat_from' => $rangeParams['from'], 'repeat_to' => $rangeParams['to']]) }}" class="hover:text-primary">{{ $returningClients }}</a>
            </p>
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Top produits — par quantité vendue</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($topByQuantity as $row)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="min-w-0 truncate text-secondary-shade dark:text-white">{{ $row->product_name }}</span>
                    <span class="shrink-0 font-medium text-secondary-shade dark:text-white">{{ $row->qty }} vendu(s)</span>
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune vente sur cette période.</p>
            @endforelse
        </div>
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Top produits — par chiffre d'affaires</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($topByRevenue as $row)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="min-w-0 truncate text-secondary-shade dark:text-white">{{ $row->product_name }}</span>
                    <span class="shrink-0 font-medium text-secondary-shade dark:text-white">{{ number_format($row->revenue, 0, ',', ' ') }} FCFA</span>
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune vente sur cette période.</p>
            @endforelse
        </div>
    </div>

</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Top catégories</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($topCategories as $row)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="min-w-0 truncate text-secondary-shade dark:text-white">{{ $row->name }}</span>
                    <span class="shrink-0 text-xs text-grey dark:text-white/40">{{ number_format($row->revenue, 0, ',', ' ') }} FCFA</span>
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune donnée.</p>
            @endforelse
        </div>
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Produits favoris</h2>
            <span class="text-xs text-grey dark:text-white/40">{{ $abandonedCartsCount }} panier(s) abandonné(s)</span>
        </div>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($mostFavorited as $row)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="min-w-0 truncate text-secondary-shade dark:text-white">{{ $row->product?->name }}</span>
                    <span class="shrink-0 text-xs text-grey dark:text-white/40">{{ $row->favorites_count }} favori(s)</span>
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucun favori pour le moment.</p>
            @endforelse
        </div>
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Campagnes</h2>
            <a href="{{ route('admin.campaigns.index') }}" class="text-xs text-primary hover:underline">Gérer</a>
        </div>
        <p class="mt-1 text-xs text-grey dark:text-white/40">{{ $campaignsActive }} campagne(s) programmée(s)/en cours</p>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($campaignsByType as $row)
                <div class="py-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="min-w-0 truncate text-secondary-shade dark:text-white">{{ $row['label'] }}</span>
                        <span class="shrink-0 text-xs text-grey dark:text-white/40">{{ $row['sent'] }} envoyé(s), {{ $row['failed'] }} échec(s)</span>
                    </div>
                    @if($row['openRate'] !== null)
                        <p class="mt-1 text-xs text-grey dark:text-white/40">Ouverture {{ $row['openRate'] }}% ({{ $row['opened'] }}) · Clic {{ $row['clickRate'] }}% ({{ $row['clicked'] }})</p>
                    @endif
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune campagne sur cette période.</p>
            @endforelse
        </div>
    </div>

</div>

@unless($openClickTrackingAvailable)
    <p class="mt-4 text-xs italic text-grey/60 dark:text-white/40">Taux d'ouverture/clic non affichés : aucun email de campagne suivi n'a encore été envoyé sur cette période.</p>
@endunless
<p class="mt-1 text-xs italic text-grey/60 dark:text-white/40">Taux de conversion non affiché : aucun suivi de visite du site n'est en place.</p>

<div class="flex flex-wrap items-end justify-between gap-4 border-b border-secondary-shade/10 pb-6 dark:border-white/10">
    <div>
        <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-grey dark:text-white/40">
            <span class="h-1.5 w-1.5 bg-primary"></span>
            Stock — {{ $period->label() }}
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-secondary-shade dark:text-white">Bon retour, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
    </div>
    @include('admin.dashboard.partials.period-filter')
</div>

<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-dashboard-kpi icon="fa-boxes-stacked" label="Stock total (unités)" :value="number_format($totalStockUnits, 0, ',', ' ')" :href="route('admin.products.index')" />
    <x-dashboard-kpi icon="fa-triangle-exclamation" label="Ruptures" :value="$outOfStockCount" :href="route('admin.products.index', ['stock' => 'out'])" />
    <x-dashboard-kpi icon="fa-bell" label="Stock faible (≤ {{ $threshold }})" :value="$lowStockCount" :href="route('admin.products.index', ['stock' => 'low'])" />
    <x-dashboard-kpi icon="fa-dolly" label="Mouvements aujourd'hui" :value="'+'.$entriesToday.' / -'.$exitsToday" :href="route('admin.stock.index', ['date' => today()->format('Y-m-d')])" />
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-dashboard-kpi
        icon="fa-money-bill-wave"
        label="Valeur du stock"
        :available="$stockValueAvailable"
        :value="number_format($stockValue, 0, ',', ' ').' FCFA'"
        :sub="$stockValueAvailable ? 'Coût connu sur '.$stockValueCoverage.'% des produits en stock' : null"
    />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1.3fr_1fr]">

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">À réapprovisionner</h2>
            <a href="{{ route('admin.stock.index') }}" class="text-xs text-primary hover:underline">Enregistrer un mouvement</a>
        </div>
        {{-- Cartes en dessous de lg, tableau au-delà — même bascule que products/orders (voir
             admin/products/partials/table.blade.php), pour rester utilisable au tactile plutôt
             que de forcer un défilement horizontal dans cette carte étroite du dashboard. --}}
        <div class="mt-4 space-y-2 lg:hidden">
            @forelse($restockList as $row)
                <div onclick="window.location='{{ route('admin.products.edit', $row['productId']) }}'" class="cursor-pointer border border-secondary-shade/10 bg-white p-3 dark:border-white/10 dark:bg-[#16201f]">
                    <p class="truncate text-sm text-secondary-shade dark:text-white">{{ $row['label'] }}</p>
                    <dl class="mt-2 grid grid-cols-3 gap-2 text-xs">
                        <div><dt class="text-grey/60 dark:text-white/30">Stock</dt><dd class="font-medium text-amber-600 dark:text-amber-400">{{ $row['stock'] }}</dd></div>
                        <div><dt class="text-grey/60 dark:text-white/30">Vendu (30j)</dt><dd class="text-grey dark:text-white/40">{{ $row['sold30d'] ?? '—' }}</dd></div>
                        <div><dt class="text-grey/60 dark:text-white/30">Recommandé</dt><dd class="text-secondary-shade dark:text-white">{{ $row['recommendedQty'] ?? '—' }}</dd></div>
                    </dl>
                </div>
            @empty
                <p class="py-4 text-sm text-grey dark:text-white/40">Aucun produit sous le seuil.</p>
            @endforelse
        </div>

        <div class="mt-4 hidden overflow-x-auto lg:block">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-secondary-shade/10 text-left text-[11px] uppercase tracking-[0.08em] text-grey dark:border-white/10 dark:text-white/40">
                        <th class="py-2 pr-3 font-medium">Produit</th>
                        <th class="py-2 pr-3 font-medium">Stock</th>
                        <th class="py-2 pr-3 font-medium">Vendu (30j)</th>
                        <th class="py-2 font-medium">Recommandé</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
                    @forelse($restockList as $row)
                        <tr class="cursor-pointer transition hover:bg-grey-tint/40 dark:hover:bg-white/5" onclick="window.location='{{ route('admin.products.edit', $row['productId']) }}'">
                            <td class="py-2.5 pr-3 text-secondary-shade dark:text-white">{{ $row['label'] }}</td>
                            <td class="py-2.5 pr-3 font-medium text-amber-600 dark:text-amber-400">{{ $row['stock'] }}</td>
                            <td class="py-2.5 pr-3 text-grey dark:text-white/40">{{ $row['sold30d'] ?? '—' }}</td>
                            <td class="py-2.5 text-secondary-shade dark:text-white">{{ $row['recommendedQty'] ?? 'Donnée non disponible' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-sm text-grey dark:text-white/40">Aucun produit sous le seuil.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-[11px] italic text-grey/60 dark:text-white/30">Quantité recommandée = vitesse de vente réelle des 30 derniers jours × 14 jours de couverture. "—" si aucune vente récente n'a pu être mesurée.</p>
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Mouvements récents</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($movementsPeriod as $movement)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="min-w-0">
                        <span class="block truncate text-secondary-shade dark:text-white">{{ $movement->product?->name }}</span>
                        <span class="text-xs text-grey dark:text-white/40">{{ $movement->user?->name ?? 'Système' }} · {{ $movement->created_at->format('d/m H:i') }}</span>
                    </span>
                    <span class="shrink-0 px-2 py-0.5 text-[11px] font-semibold {{ $movement->type === 'entree' ? 'bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary' : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400' }}">
                        {{ $movement->type === 'entree' ? '+' : '-' }}{{ $movement->quantity }}
                    </span>
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucun mouvement sur cette période.</p>
            @endforelse
        </div>
    </div>

</div>

@unless($stockValueAvailable)
    <p class="mt-4 text-xs italic text-grey/60 dark:text-white/40">Valeur du stock non disponible : aucun produit en stock n'a de coût d'achat renseigné.</p>
@endunless

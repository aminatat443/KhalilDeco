<div class="flex flex-wrap items-end justify-between gap-4 border-b border-secondary-shade/10 pb-6 dark:border-white/10">
    <div>
        <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-grey dark:text-white/40">
            <span class="h-1.5 w-1.5 bg-primary"></span>
            Comptabilité — {{ $period->label() }}
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-secondary-shade dark:text-white">Tableau de bord financier</h1>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.finances.reports') }}" class="inline-flex items-center gap-2 border border-secondary-shade px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:bg-secondary-shade hover:text-white dark:border-white/30 dark:text-white">
            <i class="fa-solid fa-file-lines"></i>Rapports
        </a>
        <a href="{{ route('admin.finances.reconciliation') }}" class="inline-flex items-center gap-2 border border-secondary-shade px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:bg-secondary-shade hover:text-white dark:border-white/30 dark:text-white">
            <i class="fa-solid fa-code-compare"></i>Rapprochement
        </a>
        @include('admin.dashboard.partials.period-filter')
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @php
        $periodParams = ['period' => $period->key] + ($period->key === 'custom' ? ['from' => $period->from, 'to' => $period->to] : []);
        $rangeParams = ['from' => $period->start->format('Y-m-d'), 'to' => $period->end->format('Y-m-d')];
    @endphp
    <x-dashboard-kpi icon="fa-sack-dollar" label="CA brut" :value="number_format($revenueGross, 0, ',', ' ').' FCFA'" :trend="$revenueTrend" :href="route('admin.finances.index')" />
    <x-dashboard-kpi icon="fa-scale-balanced" label="CA net (remises déduites)" :value="number_format($revenueNet, 0, ',', ' ').' FCFA'" :sub="'-'.number_format($discounts, 0, ',', ' ').' FCFA de remises'" />
    <x-dashboard-kpi icon="fa-money-check-dollar" label="Encaissé" :value="number_format($collected, 0, ',', ' ').' FCFA'" :href="route('admin.payments.index', ['status' => 'success', 'date_field' => 'paid_at'] + $rangeParams)" />
    <x-dashboard-kpi icon="fa-hourglass-half" label="En attente" :value="number_format($pending, 0, ',', ' ').' FCFA'" :href="route('admin.payments.index', ['status' => 'pending', 'date_field' => 'created_at'] + $rangeParams)" />
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-dashboard-kpi icon="fa-rotate-left" label="Remboursé" :value="number_format($refunded, 0, ',', ' ').' FCFA'" :href="route('admin.payments.index', ['status' => 'refunded', 'date_field' => 'updated_at'] + $rangeParams)" />
    <x-dashboard-kpi icon="fa-circle-exclamation" label="Paiements échoués" :value="$failedPaymentsCount" :href="route('admin.payments.index', ['status' => 'failed', 'date_field' => 'created_at'] + $rangeParams)" />
    <x-dashboard-kpi icon="fa-user-clock" label="Créances clients (impayés)" :value="number_format($unpaidTotal, 0, ',', ' ').' FCFA'" :sub="count($unpaidOrders).' commande(s)'" :href="route('admin.finances.reports', ['type' => 'unpaid'] + $periodParams)" />
    <x-dashboard-kpi icon="fa-tags" label="Codes promo utilisés" :value="$couponUsageCount" :href="route('admin.coupons.index')" />
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-dashboard-kpi icon="fa-receipt" label="Dépenses" :value="number_format($expenses, 0, ',', ' ').' FCFA'" :href="route('admin.expenses.index', $periodParams)" />
    <x-dashboard-kpi
        icon="fa-chart-pie"
        label="Marge brute"
        :available="$margin['available']"
        :value="number_format($margin['margin'], 0, ',', ' ').' FCFA'"
        :sub="'Coût connu sur '.$margin['coverageRatio'].'% des articles vendus ('.$margin['itemsWithCost'].'/'.$margin['itemsTotal'].')'"
    />
    <x-dashboard-kpi
        icon="fa-scale-unbalanced"
        label="Résultat net (marge - dépenses)"
        :available="$netResult !== null"
        :value="number_format($netResult ?? 0, 0, ',', ' ').' FCFA'"
    />
    <div class="flex items-center gap-4 border border-secondary-shade/10 bg-white p-4 dark:border-white/10 dark:bg-[#16201f] sm:p-6">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center border border-secondary-shade/10 text-secondary-shade dark:border-white/10 dark:text-white/70">
            <i class="fa-solid fa-boxes-stacked text-sm"></i>
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-xs font-semibold uppercase tracking-[0.12em] text-grey dark:text-white/40">Coût d'achat</p>
            <a href="{{ route('admin.products.index') }}" class="mt-1 block text-xs text-primary hover:underline">Renseigner sur les produits →</a>
        </div>
    </div>
</div>

@unless($margin['available'])
    <p class="mt-4 text-xs italic text-grey/60 dark:text-white/40">Marge et résultat net indisponibles : aucun produit vendu sur cette période n'a de coût d'achat renseigné. Ajoutez-le sur la fiche produit pour débloquer ce calcul.</p>
@endunless

<div class="mt-6 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]"
    x-data="{
        chart: null,
        days: {{ Illuminate\Support\Js::from(collect($series)->map(fn ($d) => ['label' => $d['date']->translatedFormat('d M'), 'revenue' => $d['revenue'], 'collected' => $d['collected']])) }},
        colors() {
            return document.documentElement.classList.contains('dark')
                ? { grid: 'rgba(255,255,255,0.08)', text: 'rgba(255,255,255,0.45)' }
                : { grid: 'rgba(33,55,55,0.06)', text: 'rgba(33,55,55,0.5)' };
        },
        buildChart() {
            const c = this.colors();
            if (this.chart) this.chart.destroy();
            const scale = window.KhalilCharts.niceScale(this.days.flatMap(d => [d.revenue, d.collected]));
            this.chart = new Chart(this.$refs.canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: this.days.map(d => d.label),
                    datasets: [
                        { label: 'CA déclaré', data: this.days.map(d => d.revenue), borderColor: '#263F3A', backgroundColor: '#263F3A', tension: 0.3 },
                        { label: 'Encaissé', data: this.days.map(d => d.collected), borderColor: '#C8A875', backgroundColor: '#C8A875', tension: 0.3 },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: c.text, font: { family: 'Montserrat', size: 11 } } },
                        tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ' : ' + window.KhalilCharts.formatFCFA(ctx.parsed.y) } },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.text, font: { size: 10, family: 'Montserrat' } } },
                        y: {
                            grid: { color: c.grid },
                            beginAtZero: true,
                            max: scale.max,
                            ticks: {
                                stepSize: scale.stepSize,
                                color: c.text,
                                font: { size: 10, family: 'Montserrat' },
                                callback: (v) => window.KhalilCharts.formatFCFA(v, false),
                            },
                        },
                    },
                },
            });
        },
    }"
    x-init="$nextTick(() => buildChart()); new MutationObserver(() => buildChart()).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });"
>
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Revenus déclarés vs encaissés — 14 derniers jours</h2>
    <template x-if="days.length === 0">
        <p class="mt-6 py-10 text-center text-sm text-grey dark:text-white/40">Aucune donnée disponible pour cette période.</p>
    </template>
    <div class="relative mt-6 h-56" x-show="days.length > 0"><canvas x-ref="canvas"></canvas></div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Répartition par moyen de paiement</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @php($paymentLabels = \App\Models\Order::PAYMENT_METHOD_LABELS)
            @forelse($paymentsByMethod as $row)
                <a href="{{ route('admin.orders.index', ['payment_method' => $row->payment_method, 'flag' => 'confirmed_sales'] + $rangeParams) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                    <span class="text-secondary-shade dark:text-white">{{ $paymentLabels[$row->payment_method] ?? $row->payment_method }}</span>
                    <span class="flex items-center gap-3">
                        <span class="text-xs text-grey dark:text-white/40">{{ $row->count }}</span>
                        <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($row->total, 0, ',', ' ') }} FCFA</span>
                    </span>
                </a>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune donnée sur cette période.</p>
            @endforelse
        </div>
    </div>

    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Commandes impayées</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-primary hover:underline">Voir les commandes</a>
        </div>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($unpaidOrders->take(8) as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                    <span class="min-w-0">
                        <span class="font-medium text-secondary-shade dark:text-white">{{ $order->order_number }}</span>
                        <span class="ml-2 truncate text-xs text-grey dark:text-white/40">{{ $order->customer_name }}</span>
                    </span>
                    <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                </a>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucun impayé sur cette période.</p>
            @endforelse
        </div>
    </div>

</div>

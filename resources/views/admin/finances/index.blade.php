@extends('layouts.admin')

@section('title', 'Finances')

@section('content')

@php
    $paymentLabels = \App\Models\Order::PAYMENT_METHOD_LABELS;
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Finances</h1>
        <p class="mt-1 text-sm text-grey dark:text-white/40">Chiffre d'affaires des commandes confirmées et suivantes.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.finances.reports') }}" class="inline-flex items-center gap-2 border border-secondary-shade px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:bg-secondary-shade hover:text-white dark:border-white/30 dark:text-white">
            <i class="fa-solid fa-file-lines"></i>Rapports
        </a>
        <a href="{{ route('admin.finances.reconciliation') }}" class="inline-flex items-center gap-2 border border-secondary-shade px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:bg-secondary-shade hover:text-white dark:border-white/30 dark:text-white">
            <i class="fa-solid fa-code-compare"></i>Rapprochement des paiements
        </a>
        <span class="inline-flex items-center gap-2 bg-grey-tint px-3 py-1.5 text-[11px] font-medium text-grey dark:bg-white/5 dark:text-white/40">
            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
            Mis à jour {{ now()->translatedFormat('d M Y à H:i') }}
        </span>
    </div>
</div>

{{-- Indicateurs clés --}}
<div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
    <a href="{{ route('admin.orders.index') }}" class="animate-fade-up block overflow-hidden bg-white p-6 border border-secondary-shade/10 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-[#16201f] dark:border-white/10">
        <span class="flex h-11 w-11 items-center justify-center bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary">
            <i class="fa-solid fa-sack-dollar text-sm"></i>
        </span>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Chiffre d'affaires total</p>
        <p class="mt-1 whitespace-nowrap text-xl font-semibold xl:text-2xl text-secondary-shade dark:text-white">{{ number_format($revenue, 0, ',', ' ') }} FCFA</p>
    </a>
    <a href="{{ route('admin.orders.index', ['month' => now()->format('Y-m')]) }}" class="animate-fade-up block overflow-hidden bg-white p-6 border border-secondary-shade/10 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-[#16201f] dark:border-white/10" style="animation-delay: 60ms">
        <div class="flex items-center justify-between gap-2">
            <span class="flex h-11 w-11 items-center justify-center bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                <i class="fa-solid fa-calendar-day text-sm"></i>
            </span>
            <span class="inline-flex shrink-0 items-center gap-1 text-[11px] font-semibold {{ $monthTrend >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                <i class="fa-solid {{ $monthTrend >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                {{ abs($monthTrend) }}%
            </span>
        </div>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Ce mois-ci</p>
        <p class="mt-1 whitespace-nowrap text-xl font-semibold xl:text-2xl text-secondary-shade dark:text-white">{{ number_format($thisMonth, 0, ',', ' ') }} FCFA</p>
    </a>
    <a href="{{ route('admin.orders.index', ['year' => now()->year]) }}" class="animate-fade-up block overflow-hidden bg-white p-6 border border-secondary-shade/10 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-[#16201f] dark:border-white/10" style="animation-delay: 120ms">
        <div class="flex items-center justify-between gap-2">
            <span class="flex h-11 w-11 items-center justify-center bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary">
                <i class="fa-solid fa-calendar-days text-sm"></i>
            </span>
            <span class="inline-flex shrink-0 items-center gap-1 text-[11px] font-semibold {{ $yearTrend >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                <i class="fa-solid {{ $yearTrend >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                {{ abs($yearTrend) }}%
            </span>
        </div>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Cette année</p>
        <p class="mt-1 whitespace-nowrap text-xl font-semibold xl:text-2xl text-secondary-shade dark:text-white">{{ number_format($thisYear, 0, ',', ' ') }} FCFA</p>
    </a>
    <a href="{{ route('admin.orders.index') }}" class="animate-fade-up block overflow-hidden bg-white p-6 border border-secondary-shade/10 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-[#16201f] dark:border-white/10" style="animation-delay: 180ms">
        <span class="flex h-11 w-11 items-center justify-center bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-400">
            <i class="fa-solid fa-basket-shopping text-sm"></i>
        </span>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Panier moyen</p>
        <p class="mt-1 whitespace-nowrap text-xl font-semibold xl:text-2xl text-secondary-shade dark:text-white">{{ number_format($averageOrder, 0, ',', ' ') }} FCFA</p>
    </a>
    <a href="{{ route('admin.coupons.index') }}" class="animate-fade-up block overflow-hidden bg-white p-6 border border-secondary-shade/10 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-[#16201f] dark:border-white/10" style="animation-delay: 240ms">
        <span class="flex h-11 w-11 items-center justify-center bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
            <i class="fa-solid fa-tag text-sm"></i>
        </span>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.15em] text-grey dark:text-white/40">Remises accordées</p>
        <p class="mt-1 whitespace-nowrap text-xl font-semibold xl:text-2xl text-secondary-shade dark:text-white">{{ number_format($totalDiscounts, 0, ',', ' ') }} FCFA</p>
    </a>
</div>

{{-- Évolution du chiffre d'affaires — jour / mois / année --}}
<div
    x-data="{
        period: 'day',
        series: {
            day: {{ Illuminate\Support\Js::from($daily) }},
            month: {{ Illuminate\Support\Js::from($monthly) }},
            year: {{ Illuminate\Support\Js::from($yearly) }},
        },
        chart: null,
        fmt(n) { return window.KhalilCharts.formatFCFA(n); },
        isDark() { return document.documentElement.classList.contains('dark'); },
        colors() {
            return this.isDark()
                ? { grid: 'rgba(255,255,255,0.08)', text: 'rgba(255,255,255,0.45)', bar: '#263F3A' }
                : { grid: 'rgba(33,55,55,0.06)', text: 'rgba(33,55,55,0.5)', bar: '#263F3A' };
        },
        buildChart() {
            const c = this.colors();
            const data = this.series[this.period];
            const ctx = this.$refs.canvas.getContext('2d');
            const scale = window.KhalilCharts.niceScale(data.map(d => d.revenue));

            if (this.chart) this.chart.destroy();

            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        data: data.map(d => d.revenue),
                        backgroundColor: c.bar,
                        hoverBackgroundColor: '#1D302C',
                        borderRadius: 3,
                        maxBarThickness: this.period === 'day' ? 18 : 40,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: (evt, elements) => {
                        if (! elements.length) return;
                        const point = data[elements[0].index];
                        if (! point?.date) return;
                        const param = this.period === 'day' ? 'date' : this.period;
                        window.location.href = '{{ route('admin.orders.index') }}?' + param + '=' + point.date;
                    },
                    onHover: (evt, elements) => {
                        evt.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1D302C',
                            padding: 10,
                            titleFont: { family: 'Montserrat' },
                            bodyFont: { family: 'Montserrat' },
                            callbacks: {
                                label: (ctx) => this.fmt(ctx.parsed.y),
                                afterLabel: () => 'Voir les commandes',
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: c.text, font: { size: 10, family: 'Montserrat' }, maxRotation: 0, autoSkip: true },
                        },
                        y: {
                            grid: { color: c.grid },
                            border: { display: false },
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
        switchPeriod(p) {
            this.period = p;
            this.buildChart();
        },
    }"
    x-init="
        $nextTick(() => buildChart());
        new MutationObserver(() => buildChart()).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    "
    class="mt-6 animate-fade-up bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10"
    style="animation-delay: 300ms"
>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Évolution du chiffre d'affaires</h2>

        <div class="inline-flex bg-grey-tint p-1 text-xs font-semibold dark:bg-white/5">
            <button type="button" @click="switchPeriod('day')" :class="period === 'day' ? 'bg-secondary-shade text-white' : 'text-grey hover:text-secondary-shade dark:text-white/50 dark:hover:text-white'" class="px-4 py-1.5 uppercase tracking-[0.08em] transition">Jour</button>
            <button type="button" @click="switchPeriod('month')" :class="period === 'month' ? 'bg-secondary-shade text-white' : 'text-grey hover:text-secondary-shade dark:text-white/50 dark:hover:text-white'" class="px-4 py-1.5 uppercase tracking-[0.08em] transition">Mois</button>
            <button type="button" @click="switchPeriod('year')" :class="period === 'year' ? 'bg-secondary-shade text-white' : 'text-grey hover:text-secondary-shade dark:text-white/50 dark:hover:text-white'" class="px-4 py-1.5 uppercase tracking-[0.08em] transition">Année</button>
        </div>
    </div>

    <template x-if="series[period].length === 0">
        <p class="mt-6 py-16 text-center text-sm text-grey dark:text-white/40">Aucune donnée disponible pour cette période.</p>
    </template>
    <div class="relative mt-6 h-72" x-show="series[period].length > 0">
        <canvas x-ref="canvas"></canvas>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_1.1fr]">

    {{-- Répartition par moyen de paiement --}}
    <div class="animate-fade-up bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10" style="animation-delay: 360ms">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Répartition par moyen de paiement</h2>

        @if($byPaymentMethod->isNotEmpty())
            <div
                x-data="{
                    chart: null,
                    labels: {{ Illuminate\Support\Js::from($byPaymentMethod->map(fn ($r) => $paymentLabels[$r->payment_method] ?? $r->payment_method)) }},
                    values: {{ Illuminate\Support\Js::from($byPaymentMethod->pluck('total')) }},
                    methods: {{ Illuminate\Support\Js::from($byPaymentMethod->pluck('payment_method')) }},
                    palette: ['#263F3A', '#C8A875', '#6B6B67', '#1D302C', '#A8B7B0'],
                    buildChart() {
                        if (this.chart) this.chart.destroy();
                        this.chart = new Chart(this.$refs.donut.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: this.labels,
                                datasets: [{ data: this.values, backgroundColor: this.palette, borderWidth: 3, borderColor: document.documentElement.classList.contains('dark') ? '#16201f' : '#ffffff' }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '68%',
                                onClick: (evt, elements) => {
                                    if (! elements.length) return;
                                    const method = this.methods[elements[0].index];
                                    if (! method) return;
                                    window.location.href = '{{ route('admin.orders.index') }}?payment_method=' + method;
                                },
                                onHover: (evt, elements) => {
                                    evt.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                                },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: '#1D302C',
                                        padding: 8,
                                        titleFont: { family: 'Montserrat', size: 11 },
                                        bodyFont: { family: 'Montserrat', size: 11 },
                                        callbacks: { label: (ctx) => new Intl.NumberFormat('fr-FR').format(ctx.parsed) + ' FCFA' },
                                    },
                                },
                            },
                        });
                    },
                }"
                x-init="
                    $nextTick(() => buildChart());
                    new MutationObserver(() => buildChart()).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                "
                class="mt-4 flex flex-col items-center gap-6 sm:flex-row"
            >
                <div class="relative h-40 w-40 shrink-0">
                    <canvas x-ref="donut"></canvas>
                </div>
                <div class="w-full min-w-0 divide-y divide-secondary-shade/10 dark:divide-white/10">
                    @foreach($byPaymentMethod as $i => $row)
                        <a href="{{ route('admin.orders.index', ['payment_method' => $row->payment_method]) }}" class="flex items-center gap-3 py-2.5 text-sm transition hover:bg-grey-tint dark:hover:bg-white/5">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ ['#263F3A', '#C8A875', '#6B6B67', '#1D302C', '#A8B7B0'][$i % 5] }}"></span>
                            <span class="min-w-0 flex-1 truncate text-secondary-shade dark:text-white">{{ $paymentLabels[$row->payment_method] ?? $row->payment_method }}</span>
                            <span class="shrink-0 text-right">
                                <span class="block font-medium text-secondary-shade dark:text-white">{{ number_format($row->total, 0, ',', ' ') }} FCFA</span>
                                <span class="block text-xs text-grey dark:text-white/40">{{ $row->orders_count }} commande(s)</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <p class="mt-4 py-3 text-sm text-grey dark:text-white/40">Aucune donnée pour le moment.</p>
        @endif
    </div>

    {{-- Résumé par période --}}
    <div class="animate-fade-up min-w-0 bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10" style="animation-delay: 420ms">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Meilleures périodes</h2>

        @php
            $bestDay = collect($daily)->sortByDesc('revenue')->first();
            $bestMonth = collect($monthly)->sortByDesc('revenue')->first();
            $bestYear = collect($yearly)->sortByDesc('revenue')->first();
        @endphp

        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-3 text-sm">
                <span class="flex min-w-0 flex-1 items-center gap-2 text-secondary-shade dark:text-white">
                    <i class="fa-solid fa-calendar-day w-4 shrink-0 text-center text-grey dark:text-white/40"></i>
                    <span class="min-w-0 truncate">Meilleur jour <span class="text-xs text-grey dark:text-white/40">({{ $bestDay['label'] ?? '—' }})</span></span>
                </span>
                <span class="shrink-0 whitespace-nowrap font-medium text-secondary-shade dark:text-white">{{ number_format($bestDay['revenue'] ?? 0, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-3 text-sm">
                <span class="flex min-w-0 flex-1 items-center gap-2 text-secondary-shade dark:text-white">
                    <i class="fa-solid fa-calendar-week w-4 shrink-0 text-center text-grey dark:text-white/40"></i>
                    <span class="min-w-0 truncate">Meilleur mois <span class="text-xs text-grey dark:text-white/40">({{ $bestMonth['label'] ?? '—' }})</span></span>
                </span>
                <span class="shrink-0 whitespace-nowrap font-medium text-secondary-shade dark:text-white">{{ number_format($bestMonth['revenue'] ?? 0, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-3 text-sm">
                <span class="flex min-w-0 flex-1 items-center gap-2 text-secondary-shade dark:text-white">
                    <i class="fa-solid fa-calendar-days w-4 shrink-0 text-center text-grey dark:text-white/40"></i>
                    <span class="min-w-0 truncate">Meilleure année <span class="text-xs text-grey dark:text-white/40">({{ $bestYear['label'] ?? '—' }})</span></span>
                </span>
                <span class="shrink-0 whitespace-nowrap font-medium text-secondary-shade dark:text-white">{{ number_format($bestYear['revenue'] ?? 0, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-3 text-sm">
                <span class="flex min-w-0 flex-1 items-center gap-2 text-secondary-shade dark:text-white">
                    <i class="fa-solid fa-bag-shopping w-4 shrink-0 text-center text-grey dark:text-white/40"></i>
                    <span class="min-w-0 truncate">Commandes confirmées</span>
                </span>
                <span class="shrink-0 whitespace-nowrap font-medium text-secondary-shade dark:text-white">{{ $ordersCount }}</span>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>
@endpush

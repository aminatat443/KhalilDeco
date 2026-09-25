@extends('layouts.admin')

@section('title', 'Rapprochement des paiements')

@section('content')

<a href="{{ route('admin.finances.index') }}" class="text-xs text-grey hover:text-primary dark:text-white/40"><i class="fa-solid fa-arrow-left mr-1"></i>Finances</a>

<h1 class="mt-3 text-2xl font-semibold text-secondary-shade dark:text-white">Rapprochement des paiements</h1>
<p class="mt-1 max-w-2xl text-sm text-grey dark:text-white/50">Comparaison automatique entre les commandes, leur statut de paiement et les transactions réellement enregistrées — pour repérer les incohérences avant qu'elles ne s'accumulent.</p>

@php
    $totalAnomalies = $paidWithoutPayment->count() + $paymentWithoutPaidStatus->count() + $amountMismatch->count() + $duplicates->count() + $stalePending->count() + $orphanPayments->count();
@endphp

<div class="mt-6 border border-secondary-shade/10 bg-white p-5 dark:border-white/10 dark:bg-[#16201f]">
    @if($totalAnomalies === 0)
        <p class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400"><i class="fa-solid fa-circle-check"></i>Aucune anomalie détectée sur l'ensemble des commandes et paiements.</p>
    @else
        <p class="flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400"><i class="fa-solid fa-triangle-exclamation"></i>{{ $totalAnomalies }} anomalie(s) à vérifier, réparties ci-dessous.</p>
    @endif
</div>

{{-- Commande payée sans paiement réussi enregistré --}}
<div class="mt-6 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Commande marquée payée sans paiement enregistré</h2>
    <p class="mt-1 text-xs text-grey dark:text-white/40">Le statut de paiement a probablement été changé manuellement sans passer par un encaissement réel.</p>
    <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
        @forelse($paidWithoutPayment as $order)
            <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                <span class="min-w-0"><span class="font-medium text-secondary-shade dark:text-white">{{ $order->order_number }}</span><span class="ml-2 truncate text-xs text-grey dark:text-white/40">{{ $order->customer_name }}</span></span>
                <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
            </a>
        @empty
            <p class="py-3 text-sm text-grey dark:text-white/40">Aucune anomalie de ce type.</p>
        @endforelse
    </div>
</div>

{{-- Paiement réussi sans statut payé --}}
<div class="mt-6 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Paiement réussi, commande non marquée payée</h2>
    <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
        @forelse($paymentWithoutPaidStatus as $order)
            <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                <span class="min-w-0"><span class="font-medium text-secondary-shade dark:text-white">{{ $order->order_number }}</span><span class="ml-2 truncate text-xs text-grey dark:text-white/40">{{ $order->customer_name }}</span></span>
                <span class="text-xs text-grey dark:text-white/40">Statut : {{ $order->payment_status }}</span>
            </a>
        @empty
            <p class="py-3 text-sm text-grey dark:text-white/40">Aucune anomalie de ce type.</p>
        @endforelse
    </div>
</div>

{{-- Différence de montant --}}
<div class="mt-6 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Différence de montant</h2>
    <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
        @forelse($amountMismatch as $row)
            <a href="{{ route('admin.orders.show', $row['order']) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                <span class="min-w-0"><span class="font-medium text-secondary-shade dark:text-white">{{ $row['order']->order_number }}</span><span class="ml-2 truncate text-xs text-grey dark:text-white/40">{{ $row['order']->customer_name }}</span></span>
                <span class="text-xs text-grey dark:text-white/40">Commande {{ number_format($row['order']->total, 0, ',', ' ') }} FCFA — Payé {{ number_format($row['paidAmount'], 0, ',', ' ') }} FCFA</span>
            </a>
        @empty
            <p class="py-3 text-sm text-grey dark:text-white/40">Aucune anomalie de ce type.</p>
        @endforelse
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Doublons --}}
    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Doublons de paiement</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($duplicates as $row)
                <a href="{{ route('admin.orders.show', $row['order']) }}" class="flex items-center justify-between py-3 text-sm transition hover:text-primary">
                    <span class="text-secondary-shade dark:text-white">{{ $row['order']->order_number }}</span>
                    <span class="text-xs text-grey dark:text-white/40">{{ $row['count'] }} paiements réussis</span>
                </a>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucun doublon détecté.</p>
            @endforelse
        </div>
    </div>

    {{-- Paiements en attente prolongée --}}
    <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Paiements en attente depuis plus de 24h</h2>
        <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($stalePending as $payment)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="min-w-0">
                        <span class="text-secondary-shade dark:text-white">{{ $payment->order?->order_number ?? '—' }}</span>
                        <span class="ml-2 text-xs text-grey dark:text-white/40">{{ $payment->provider }}</span>
                    </span>
                    <span class="text-xs text-grey dark:text-white/40">{{ $payment->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucun paiement bloqué.</p>
            @endforelse
        </div>
    </div>

</div>

@if($orphanPayments->isNotEmpty())
    <div class="mt-6 border border-red-200 bg-red-50 p-6 dark:border-red-500/20 dark:bg-red-500/10">
        <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-red-700 dark:text-red-400">Paiements sans commande associée</h2>
        <div class="mt-4 divide-y divide-red-200 dark:divide-red-500/20">
            @foreach($orphanPayments as $payment)
                <div class="flex items-center justify-between py-3 text-sm text-red-700 dark:text-red-400">
                    <span>#{{ $payment->id }} — {{ $payment->provider }}</span>
                    <span>{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

@endsection

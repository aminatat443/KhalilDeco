@extends('layouts.app')

@section('title', $order->order_number.' — Khalil Déco')

@section('content')

@php
    $steps = [
        'recue' => 'Reçue',
        'confirmee' => 'Confirmée',
        'en_preparation' => 'En préparation',
        'expediee' => 'Expédiée',
        'livree' => 'Livrée',
    ];
    $currentIndex = array_search($order->status, array_keys($steps));
@endphp

<div class="mx-auto max-w-5xl px-6 py-16 sm:px-10">

    <a href="{{ route('account.orders') }}" class="text-xs text-grey hover:text-primary"><i class="fa-solid fa-arrow-left mr-1"></i>Mes commandes</a>

    <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-display text-2xl font-normal italic text-secondary-shade sm:text-3xl lg:text-4xl">{{ $order->order_number }}</h1>
                @php($returnInfo = $order->returnStatusInfo())
                <x-status-pill :tone="$returnInfo['tone'] ?? (\App\Models\Order::STATUS_TONES[$order->status] ?? 'neutral')" :label="$returnInfo['label'] ?? (\App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status)" />
            </div>
            <p class="mt-1 text-xs text-grey">Passée le {{ $order->created_at->format('d/m/Y à H:i') }}</p>
        </div>
        <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="inline-flex w-full items-center justify-center gap-2 border border-secondary-shade/20 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:border-secondary-shade sm:w-auto">
            <i class="fa-solid fa-file-invoice"></i>Télécharger la facture
        </a>
    </div>

    {{-- Suivi de commande (section 38 du cahier des charges) --}}
    @if($order->status === 'annulee')
        <div class="mt-10 border border-red-200 bg-red-50 px-6 py-5 text-sm text-red-700">
            <i class="fa-solid fa-circle-xmark mr-2"></i>Cette commande a été annulée.
        </div>
    @elseif($order->status === 'en_attente_paiement')
        <div class="mt-10 border border-tertiary-shade/40 bg-tertiary-shade/5 px-6 py-6 text-center text-sm text-secondary-shade">
            <i class="fa-solid fa-hourglass-half mr-2 text-tertiary-shade"></i>
            @if(in_array($latestPayment?->status, ['cancelled', 'failed'], true))
                Le paiement n'a pas été effectué. Votre commande est toujours en attente de paiement.
            @else
                Votre commande a été enregistrée. Il ne reste plus qu'à finaliser le paiement pour qu'elle soit confirmée.
            @endif
            <div class="mt-4">
                <a href="{{ route('payment.retry', $order) }}" class="inline-block bg-secondary-shade px-8 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">
                    Payer maintenant
                </a>
            </div>
        </div>
    @else
        <div class="mt-12 overflow-x-auto border border-secondary-shade/10 bg-white p-5 shadow-sm sm:p-8">
            <div class="flex items-start justify-between gap-1 sm:gap-0">
                @foreach($steps as $value => $label)
                    @php($stepIndex = array_search($value, array_keys($steps)))
                    <div class="flex w-[88px] shrink-0 flex-col items-center text-center sm:w-auto sm:flex-1 sm:shrink">
                        <div class="flex w-full items-center">
                            <div class="h-px flex-1 {{ $stepIndex === 0 ? 'bg-transparent' : ($stepIndex <= $currentIndex ? 'bg-primary' : 'bg-grey-tint') }}"></div>
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold {{ $stepIndex <= $currentIndex ? 'bg-primary text-white' : 'bg-grey-tint text-grey' }}">
                                @if($stepIndex < $currentIndex || $order->status === 'livree')
                                    <i class="fa-solid fa-check text-[9px]"></i>
                                @else
                                    {{ $stepIndex + 1 }}
                                @endif
                            </span>
                            <div class="h-px flex-1 {{ $stepIndex === count($steps) - 1 ? 'bg-transparent' : ($stepIndex < $currentIndex ? 'bg-primary' : 'bg-grey-tint') }}"></div>
                        </div>
                        <p class="mt-2 text-[10px] uppercase tracking-[0.05em] {{ $stepIndex <= $currentIndex ? 'text-secondary-shade' : 'text-grey' }}">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_320px]">

        {{-- Articles --}}
        <div class="space-y-3">
            @foreach($order->items as $item)
                <div class="border border-secondary-shade/10 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <div class="min-w-0">
                            <p class="break-words font-medium text-secondary-shade">{{ $item->product_name }}</p>
                            @if($item->variant_label)
                                <p class="text-xs text-grey">{{ $item->variant_label }}</p>
                            @endif
                            <p class="mt-0.5 text-xs text-grey">Qté : {{ $item->quantity }}</p>
                        </div>
                        <p class="shrink-0 whitespace-nowrap font-medium text-secondary-shade">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</p>
                    </div>

                    {{-- Demande de retour — 7 jours à partir de la date de la commande --}}
                    @if($order->status !== 'annulee')
                        @if($existingReturn = $item->returns->first())
                            <p class="mt-3 border-t border-secondary-shade/10 pt-3 text-xs text-grey">
                                <i class="fa-solid fa-rotate-left mr-1.5"></i>
                                Retour {{ \App\Models\ProductReturn::STATUS_LABELS[$existingReturn->status] ?? $existingReturn->status }}
                            </p>
                        @elseif($order->created_at->diffInDays(now()) <= 7)
                            <div x-data="{ open: false, reason: '' }" class="mt-3 border-t border-secondary-shade/10 pt-3">
                                <button type="button" @click="open = !open" class="text-xs font-semibold text-primary hover:underline">
                                    Demander un retour
                                </button>

                                <form x-show="open" x-cloak action="{{ route('account.returns.store') }}" method="POST" class="mt-3 space-y-3 border border-secondary-shade/10 bg-grey-tint/40 p-4">
                                    @csrf
                                    <input type="hidden" name="order_item_id" value="{{ $item->id }}">
                                    <select name="reason" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm outline-none focus:border-primary">
                                        <option value="">Motif du retour</option>
                                        <option value="taille">Dimensions incorrectes</option>
                                        <option value="defaut">Article défectueux</option>
                                        <option value="description">Ne correspond pas à la description</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                    <textarea name="description" rows="2" placeholder="Précisez (optionnel)" class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm outline-none focus:border-primary"></textarea>
                                    <button type="submit" class="bg-secondary-shade px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-white transition hover:bg-primary">
                                        Envoyer la demande
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Résumé --}}
        <div class="space-y-4">
            <div class="border border-secondary-shade/10 bg-white p-6 shadow-sm">
                <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Résumé</h2>
                <div class="mt-4 space-y-1.5 text-sm text-grey">
                    <div class="flex justify-between"><span>Sous-total</span><span>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</span></div>
                    <div class="flex justify-between"><span>Livraison{{ $order->delivery_zone ? ' ('.$order->delivery_zone.')' : '' }}</span><span>{{ number_format($order->delivery_fee, 0, ',', ' ') }} FCFA</span></div>
                    @if($order->discount > 0)
                        <div class="flex justify-between text-primary"><span>Réduction</span><span>-{{ number_format($order->discount, 0, ',', ' ') }} FCFA</span></div>
                    @endif
                </div>
                <div class="mt-3 flex justify-between border-t border-secondary-shade/10 pt-3">
                    <span class="text-sm font-medium uppercase tracking-[0.1em] text-secondary-shade">Total</span>
                    <span class="font-display text-xl italic text-secondary-shade">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>

            <div class="border border-secondary-shade/10 bg-white p-6 text-sm text-grey shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Livraison</p>
                @if($order->is_in_store)
                    <p class="mt-2">Retrait en boutique</p>
                @else
                    <p class="mt-2">{{ $order->delivery_address }}{{ $order->delivery_quartier ? ', '.$order->delivery_quartier : '' }}</p>
                    <p>{{ $order->delivery_city }}, {{ $order->delivery_region }}</p>
                @endif
                <p class="mt-4 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Paiement</p>
                <p class="mt-2">{{ $order->paymentMethodLabel() }}</p>
                <p class="mt-1 text-xs font-medium {{ $order->payment_status === 'paid' ? 'text-primary' : 'text-tertiary-shade' }}">
                    @if($order->payment_status === 'paid')
                        <i class="fa-solid fa-circle-check mr-1"></i>Payé
                    @else
                        <i class="fa-solid fa-hourglass-half mr-1"></i>En attente de paiement
                    @endif
                </p>
            </div>
        </div>

    </div>

</div>
@endsection

@extends('layouts.app')

@php
    // Ni le clic sur "Payer", ni la redirection vers le prestataire, ni le retour sur cette
    // page ne valent confirmation de paiement (règle absolue de cette intégration) — l'en-tête
    // reflète donc l'état réel en base, jamais un succès supposé du simple fait d'être ici.
    $isAwaitingPayment = $order->status === 'en_attente_paiement';
@endphp

@section('title', ($isAwaitingPayment ? 'Paiement en attente' : 'Commande confirmée').' — Khalil Déco')

@section('content')
<div class="mx-auto max-w-2xl px-6 py-20 sm:px-10">

    <div class="text-center">
        @if($isAwaitingPayment)
            <i class="fa-solid fa-hourglass-half text-3xl text-tertiary-shade"></i>
            <h1 class="mt-6 font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">Presque terminé, {{ $order->customer_name }}.</h1>
            <p class="mt-3 text-sm text-grey">
                Votre commande <span class="font-medium text-secondary-shade">{{ $order->order_number }}</span> a été enregistrée — il reste à finaliser le paiement.
            </p>
        @else
            <i class="fa-solid fa-circle-check text-3xl text-primary"></i>
            <h1 class="mt-6 font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">Merci {{ $order->customer_name }} !</h1>
            <p class="mt-3 text-sm text-grey">
                Votre commande <span class="font-medium text-secondary-shade">{{ $order->order_number }}</span> a bien été reçue.
            </p>
        @endif
    </div>

    <div class="mt-12 border border-secondary-shade/10 p-8">
        <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-grey">Récapitulatif</h2>

        <div class="mt-4 divide-y divide-secondary-shade/10">
            @foreach($order->items as $item)
                <div class="flex items-center justify-between py-3">
                    <div>
                        <p class="text-sm font-medium text-secondary-shade">{{ $item->product_name }}</p>
                        @if($item->variant_label)
                            <p class="text-xs text-grey">{{ $item->variant_label }}</p>
                        @endif
                        <p class="text-xs text-grey">Qté : {{ $item->quantity }}</p>
                    </div>
                    <p class="text-sm font-medium text-secondary-shade">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</p>
                </div>
            @endforeach
        </div>

        <div class="mt-4 space-y-1.5 border-t border-secondary-shade/10 pt-4 text-sm text-grey">
            <div class="flex justify-between">
                <span>Sous-total</span>
                <span>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex justify-between">
                <span>Livraison{{ $order->delivery_zone ? ' ('.$order->delivery_zone.')' : '' }}</span>
                <span>{{ number_format($order->delivery_fee, 0, ',', ' ') }} FCFA</span>
            </div>
            @if($order->discount > 0)
                <div class="flex justify-between text-primary">
                    <span>Réduction</span>
                    <span>-{{ number_format($order->discount, 0, ',', ' ') }} FCFA</span>
                </div>
            @endif
        </div>

        <div class="mt-3 flex justify-between border-t border-secondary-shade/10 pt-3">
            <span class="text-sm font-medium uppercase tracking-[0.1em] text-secondary-shade">Total</span>
            <span class="font-display text-xl italic text-secondary-shade">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
        </div>
    </div>

    <div class="mt-6 border border-secondary-shade/10 p-6 text-sm text-grey">
        <p><span class="font-medium text-secondary-shade">Livraison à :</span> {{ $order->delivery_address }}, {{ $order->delivery_quartier }} {{ $order->delivery_city }}, {{ $order->delivery_region }}</p>
        <p class="mt-2"><span class="font-medium text-secondary-shade">Moyen de paiement :</span> {{ $order->paymentMethodLabel() }}</p>
        <p class="mt-2">
            <span class="font-medium text-secondary-shade">Commande :</span>
            @if($order->status === 'annulee')
                Annulée
            @elseif($isAwaitingPayment)
                En attente de paiement
            @else
                Confirmée
            @endif
        </p>
        <p class="mt-2">
            <span class="font-medium text-secondary-shade">Paiement :</span>
            @if($order->payment_method === 'cod')
                À régler à la livraison
            @elseif($order->payment_status === 'paid')
                Payé
            @else
                En attente
            @endif
        </p>
    </div>

    @php
        $needsAction = $latestPayment === null || in_array($latestPayment->status, ['cancelled', 'failed'], true);
    @endphp
    @if($isAwaitingPayment)
        <div class="mt-6 border border-tertiary-shade/40 bg-tertiary-shade/5 p-6 text-sm text-secondary-shade">
            @if($needsAction)
                @php
                    $paytechAvailable = (bool) config('services.paytech.key');
                    $retryMethods = [
                        'wave' => ['label' => 'Wave', 'available' => $paytechAvailable, 'logo' => 'wave.svg'],
                        'orange_money' => ['label' => 'Orange Money', 'available' => $paytechAvailable, 'logo' => 'orange-money.svg'],
                        'carte' => ['label' => 'Carte bancaire', 'available' => $paytechAvailable, 'logo' => 'carte.svg'],
                        'cod' => ['label' => 'Paiement à la livraison', 'available' => true, 'logo' => null],
                    ];
                @endphp
                <p class="text-center">Le paiement n'a pas été effectué. Votre commande est toujours en attente de paiement.</p>

                <form action="{{ route('payment.retry', $order) }}" method="POST" class="mt-5">
                    @csrf
                    <p class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Choisir un moyen de paiement</p>
                    <div class="mt-3 space-y-2">
                        @foreach($retryMethods as $value => $method)
                            @continue(! $method['available'])
                            <label class="flex cursor-pointer items-center gap-3 border border-secondary-shade/15 bg-white px-4 py-3 has-[:checked]:border-secondary-shade">
                                <input type="radio" name="payment_method" value="{{ $value }}" required class="h-4 w-4 shrink-0 text-primary focus:ring-primary" @checked($order->payment_method === $value)>
                                @if($method['logo'])
                                    <img src="{{ asset('images/payment-logos/'.$method['logo']) }}" alt="{{ $method['label'] }}" class="h-6 w-6 shrink-0 object-contain">
                                @else
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center bg-secondary-shade text-white"><i class="fa-solid fa-truck text-[11px]"></i></span>
                                @endif
                                <span class="text-sm text-secondary-shade">{{ $method['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="mt-4 w-full bg-secondary-shade px-8 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary sm:w-auto">
                        Continuer
                    </button>
                </form>
            @else
                <p class="text-center">Paiement en cours de vérification — vous recevrez un email dès sa confirmation. Ne fermez pas cette page si vous venez de payer.</p>
            @endif
        </div>
    @elseif($order->payment_method !== 'cod')
        <div class="mt-6 border border-primary/30 bg-primary-tint/40 p-6 text-center text-sm text-secondary-shade">
            <p class="font-medium">Paiement confirmé</p>
            <p class="mt-1">Votre commande a été confirmée.</p>
        </div>
    @endif

    <div class="mt-10 flex flex-wrap items-center justify-center gap-6">
        <a href="{{ route('home') }}" class="inline-block bg-secondary-shade px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">
            Retour à la boutique
        </a>
        <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary">
            <i class="fa-solid fa-file-invoice mr-1.5"></i>Télécharger la facture
        </a>
    </div>

</div>
@endsection

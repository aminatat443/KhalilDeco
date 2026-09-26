@extends('layouts.app')

@php
    // Ni le clic sur "Payer", ni la redirection vers le prestataire, ni le retour sur cette
    // page ne valent confirmation de paiement (règle absolue de cette intégration) — l'en-tête
    // reflète donc l'état réel en base, jamais un succès supposé du simple fait d'être ici.
    $isAwaitingPayment = $order->status === 'en_attente_paiement';
@endphp

@section('title', ($isAwaitingPayment ? 'Paiement en attente' : 'Commande confirmée').' — Khalil Déco')

@section('content')
<div
    x-data="{
        orderId: {{ $order->id }},
        isAwaiting: @js($isAwaitingPayment),
        showBackAlert: false,
        poll: null,
        async checkStatus() {
            try {
                const res = await fetch(`/commande/${this.orderId}/statut`, { headers: { Accept: 'application/json' } });
                if (! res.ok) return false;
                const data = await res.json();
                if (data.status !== 'en_attente_paiement') {
                    location.reload();
                    return true;
                }
            } catch (e) {}
            return false;
        },
    }"
    x-init="
        if (isAwaiting) { poll = setInterval(() => checkStatus(), 5000); }
        window.addEventListener('pageshow', (e) => { if (e.persisted && isAwaiting) { checkStatus().then((confirmed) => { if (! confirmed) showBackAlert = true; }); } });
    "
    class="mx-auto max-w-2xl px-6 py-20 sm:px-10"
>
    {{-- Alerte au retour navigateur (bouton "Retour" du navigateur restaurant cette page depuis
         le cache) sur une commande toujours en attente de paiement — purement informative,
         aucun blocage de la navigation (section 20-21 du cahier des charges). --}}
    <div x-show="showBackAlert" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center bg-secondary-shade/60 p-4" @click.self="showBackAlert = false">
        <div class="w-full max-w-sm bg-white p-6 text-center shadow-xl">
            <i class="fa-solid fa-triangle-exclamation text-2xl text-tertiary-shade"></i>
            <p class="mt-4 text-sm text-secondary-shade">Votre commande n'est pas encore confirmée.<br>Veuillez effectuer le paiement pour confirmer votre commande.</p>
            <div class="mt-5 flex flex-col gap-2">
                <form action="{{ route('payment.retry', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary-shade">Payer maintenant</button>
                </form>
                <button type="button" @click="showBackAlert = false" class="w-full border border-secondary-shade/15 px-6 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:border-secondary-shade/30">Retourner à la commande</button>
            </div>
        </div>
    </div>

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
                @if(str_contains((string) $order->admin_notes, 'Délai de paiement'))
                    Annulée — le délai de paiement est expiré
                @else
                    Annulée
                @endif
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
            <div
                x-data="{ method: '{{ $order->payment_method }}' }"
                class="mt-6 border border-tertiary-shade/40 bg-tertiary-shade/5 p-6 text-secondary-shade"
            >
                <div class="flex items-start gap-3 sm:items-center">
                    <i class="fa-solid fa-hourglass-half mt-0.5 text-lg text-tertiary-shade sm:mt-0"></i>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Paiement en attente</p>
                        <p class="mt-1 text-sm text-grey">Votre commande n'est pas encore confirmée. Choisissez votre moyen de paiement et réglez pour la confirmer.</p>
                    </div>
                </div>

                <form action="{{ route('payment.retry', $order) }}" method="POST" class="mt-6">
                    @csrf
                    <p class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Choisir un moyen de paiement</p>

                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($retryMethods as $value => $method)
                            @continue(! $method['available'])
                            <label class="group relative flex cursor-pointer flex-col items-center gap-2 border border-secondary-shade/15 bg-white px-4 py-6 text-center transition hover:border-secondary-shade/30 hover:bg-grey-tint/30 has-[:checked]:border-primary has-[:checked]:bg-primary-tint/40 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary/40">
                                <input type="radio" name="payment_method" value="{{ $value }}" x-model="method" required class="peer sr-only" @checked($order->payment_method === $value)>
                                <span class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center border border-secondary-shade/20 text-transparent transition peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white">
                                    <i class="fa-solid fa-check text-[9px]"></i>
                                </span>
                                @if($method['logo'])
                                    <img src="{{ asset('images/payment-logos/'.$method['logo']) }}" alt="{{ $method['label'] }}" class="h-11 w-11 shrink-0 object-contain">
                                @else
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center bg-secondary-shade text-white"><i class="fa-solid fa-truck text-base"></i></span>
                                @endif
                                <span class="mt-1 text-sm font-medium text-secondary-shade peer-checked:text-primary-shade">{{ $method['label'] }}</span>
                                <span class="text-[11px] text-grey">{{ $value === 'cod' ? 'Réglé à la réception' : 'Paiement en ligne' }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-6 flex flex-col gap-4 border-t border-secondary-shade/10 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p x-show="method !== 'cod'" class="text-sm text-secondary-shade"><i class="fa-solid fa-lock mr-1.5 text-primary"></i>Paiement en ligne — vous serez redirigé vers la page sécurisée du prestataire.</p>
                            <p x-show="method === 'cod'" x-cloak class="text-sm text-secondary-shade"><i class="fa-solid fa-truck mr-1.5 text-primary"></i>Vous réglerez votre commande lors de la livraison.</p>
                            <p class="mt-1 text-xs uppercase tracking-[0.1em] text-grey">Total à payer <span class="font-semibold text-secondary-shade">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span></p>
                        </div>
                        <button type="submit" :disabled="! method" class="w-full shrink-0 bg-secondary-shade px-8 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto">
                            <span x-show="method === 'cod'" x-cloak>Confirmer la commande</span>
                            <span x-show="method !== 'cod'">Payer {{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="mt-6 border border-tertiary-shade/40 bg-tertiary-shade/5 p-6 text-sm text-secondary-shade">
                <p class="text-center">Paiement en cours de vérification — vous recevrez un email dès sa confirmation. Ne fermez pas cette page si vous venez de payer.</p>
            </div>
        @endif
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

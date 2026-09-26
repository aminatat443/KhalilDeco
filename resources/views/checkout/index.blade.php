@extends('layouts.app')

@section('title', 'Commande — Khalil Déco')

@section('content')
<div
    class="mx-auto max-w-2xl px-6 py-16 sm:px-10"
    x-data="{
        step: {{ $errors->has('payment_method') || $errors->has('coupon_code') ? 3 : (auth()->check() && $defaultAddress && $matchedDelivery ? 3 : (auth()->check() && $defaultAddress ? 2 : 1)) }},
        deliveryId: {{ old('delivery_id', $matchedDelivery->id ?? 'null') }},
        deliveryFee: {{ old('delivery_id') ? ($deliveries->firstWhere('id', old('delivery_id'))?->fee ?? 0) : ($matchedDelivery->fee ?? 0) }},
        paymentMethod: '{{ old('payment_method', 'cod') }}',
        zones: @js($deliveries->map(fn ($d) => ['id' => $d->id, 'zone' => $d->zone, 'fee' => $d->fee])->values()),
        locating: false,
        locationError: null,
        addressError: null,
        next() { if (this.step < 4) this.step++; window.scrollTo({ top: 0, behavior: 'smooth' }); },
        prev() { if (this.step > 1) this.step--; window.scrollTo({ top: 0, behavior: 'smooth' }); },
        matchZone(city, region) {
            const haystack = (city + ' ' + region).toLowerCase();
            return this.zones.find((z) => z.zone !== 'Dakar' && haystack.includes(z.zone.toLowerCase()))
                ?? this.zones.find((z) => haystack.includes(z.zone.toLowerCase()));
        },
        detectLocation() {
            this.locationError = null;
            if (! navigator.geolocation) {
                this.locationError = 'La géolocalisation n\'est pas disponible sur cet appareil.';
                return;
            }
            this.locating = true;
            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    try {
                        const { latitude, longitude } = pos.coords;
                        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}&addressdetails=1&zoom=18&accept-language=fr`);
                        const data = await response.json();
                        const addr = data.address || {};
                        const region = addr.state || addr.region || '';
                        // La ville retient l'échelon le plus précis (quartier/commune),
                        // bien plus utile qu'un simple 'Dakar' pour la livraison.
                        const city = addr.suburb || addr.neighbourhood || addr.quarter || addr.city_district
                            || addr.city || addr.town || addr.village || addr.county || '';
                        // Localisation précise : numéro de rue si disponible.
                        const street = [addr.house_number, addr.road].filter(Boolean).join(' ');

                        if (this.$refs.regionInput && region) this.$refs.regionInput.value = region;
                        if (this.$refs.cityInput && city) this.$refs.cityInput.value = city;
                        if (this.$refs.addressInput && street) this.$refs.addressInput.value = street;

                        // Dès que l'adresse est déterminée automatiquement, on affiche
                        // immédiatement le tarif de livraison correspondant.
                        const match = this.matchZone(city, region);
                        if (match) {
                            this.deliveryId = match.id;
                            this.deliveryFee = match.fee;
                        }

                        if (! region && ! city) {
                            this.locationError = 'Adresse introuvable à partir de votre position. Renseignez-la manuellement.';
                        }
                    } catch (e) {
                        this.locationError = 'Impossible de déterminer votre adresse. Renseignez-la manuellement.';
                    } finally {
                        this.locating = false;
                    }
                },
                () => {
                    this.locationError = 'Localisation refusée ou indisponible. Renseignez votre adresse manuellement.';
                    this.locating = false;
                },
                { timeout: 10000 }
            );
        },
    }"
>
    <h1 class="font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">Finaliser ma commande</h1>

    {{-- Fil de progression (section 34 du cahier des charges) --}}
    <div class="mt-8 flex items-center">
        <template x-for="n in 4" :key="n">
            <div class="flex items-center">
                <span
                    :class="[step >= n ? 'bg-primary text-white' : 'bg-grey-tint text-grey', step === n ? 'ring-2 ring-primary/20 ring-offset-2' : '']"
                    class="flex h-7 w-7 shrink-0 items-center justify-center text-xs font-semibold transition-colors"
                    x-text="n"
                ></span>
                <div x-show="n < 4" :class="step > n ? 'bg-primary' : 'bg-grey-tint'" class="h-px w-10 transition-colors sm:w-16"></div>
            </div>
        </template>
    </div>

    @if($errors->any())
        <div x-data="{ show: true }" x-show="show" class="mt-6 flex items-start justify-between gap-4 border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" @click="show = false" aria-label="Fermer" class="shrink-0 text-red-700/60 transition hover:text-red-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <form action="{{ route('checkout.store') }}" method="POST" class="mt-10">
        @csrf

        {{-- Étape 1 — Informations personnelles (nom/email déjà connus si connecté, section 34) --}}
        <div x-show="step === 1">
            <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-grey">Étape 1 — {{ auth()->check() ? 'Coordonnées' : 'Informations personnelles' }}</h2>

            <div class="mt-6 space-y-6">
                @auth
                    <input type="hidden" name="customer_name" value="{{ auth()->user()->name }}">
                @else
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Nom complet</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                    </div>
                @endauth

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Téléphone</label>
                    <input type="tel" name="customer_phone" value="{{ old('customer_phone', $defaultAddress->phone ?? '') }}" required placeholder="77 000 00 00" class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                </div>

                @auth
                    <input type="hidden" name="customer_email" value="{{ auth()->user()->email }}">
                @else
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Email</label>
                        <input type="email" name="customer_email" value="{{ old('customer_email') }}" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                    </div>
                @endauth
            </div>

            <button type="button" @click="next()" class="mt-10 bg-secondary-shade px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">
                Continuer
            </button>
        </div>

        {{-- Étape 2 — Adresse et livraison (combinées) --}}
        <div x-show="step === 2" x-cloak>
            <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-grey">Étape 2 — Adresse et livraison</h2>

            <button
                type="button"
                @click="detectLocation()"
                :disabled="locating"
                class="mt-4 inline-flex items-center gap-2 border border-secondary-shade/20 px-4 py-2.5 text-[10px] font-semibold uppercase tracking-[0.06em] text-secondary-shade transition hover:border-secondary-shade disabled:opacity-50 lg:text-xs lg:tracking-[0.1em]"
            >
                <i class="fa-solid" :class="locating ? 'fa-spinner fa-spin' : 'fa-location-crosshairs'"></i>
                <span x-text="locating ? 'Localisation…' : 'Utiliser ma position actuelle'"></span>
            </button>
            <template x-if="locationError">
                <p class="mt-2 text-xs text-red-600" x-text="locationError"></p>
            </template>

            <div class="mt-6 space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Région</label>
                        <input
                            x-ref="regionInput"
                            type="text"
                            name="delivery_region"
                            value="{{ old('delivery_region', $defaultAddress->region ?? '') }}"
                            @input="const m = matchZone($refs.cityInput.value, $event.target.value); if (m) { deliveryId = m.id; deliveryFee = m.fee; }"
                            required
                            class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary"
                        >
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Ville</label>
                        <input
                            x-ref="cityInput"
                            type="text"
                            name="delivery_city"
                            value="{{ old('delivery_city', $defaultAddress?->quartier ?: ($defaultAddress?->city ?? '')) }}"
                            @input="const m = matchZone($event.target.value, $refs.regionInput.value); if (m) { deliveryId = m.id; deliveryFee = m.fee; }"
                            required
                            class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary"
                        >
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Complément d'adresse (optionnel)</label>
                    <input x-ref="addressInput" type="text" name="delivery_address" value="{{ old('delivery_address', $defaultAddress->address ?? '') }}" placeholder="Numéro de rue, villa, repère…" class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Instructions (optionnel)</label>
                    <textarea name="delivery_instructions" rows="2" class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">{{ old('delivery_instructions', $defaultAddress->instructions ?? '') }}</textarea>
                </div>
            </div>

            {{-- Zone de livraison — le tarif s'affiche et se met à jour automatiquement dès que
                 l'adresse est renseignée (manuellement ou via la géolocalisation). --}}
            <h3 class="mt-10 text-xs font-semibold uppercase tracking-[0.2em] text-grey">Zone de livraison</h3>

            <div class="mt-4">
                <select
                    name="delivery_id"
                    x-model.number="deliveryId"
                    @change="const d = zones.find((z) => z.id === deliveryId); if (d) deliveryFee = d.fee;"
                    required
                    class="w-full border-b border-secondary-shade/20 bg-transparent py-2.5 text-sm text-secondary-shade outline-none focus:border-primary"
                >
                    <option value="" disabled>— Choisir une zone —</option>
                    @forelse($deliveries as $delivery)
                        <option value="{{ $delivery->id }}">
                            {{ $delivery->zone }} — {{ $delivery->estimated_days }}{{ $delivery->fee > 0 ? ' — '.number_format($delivery->fee, 0, ',', ' ').' FCFA' : '' }}
                        </option>
                    @empty
                        <option value="" disabled>Aucune zone de livraison configurée pour le moment.</option>
                    @endforelse
                </select>
            </div>

            {{-- Récapitulatif du tarif, mis à jour en temps réel --}}
            <div class="mt-4 flex items-center justify-between border border-secondary-shade/10 bg-grey-tint px-5 py-4">
                <span class="text-sm font-medium uppercase tracking-[0.08em] text-secondary-shade">Frais de livraison</span>
                <span class="text-sm font-semibold text-secondary-shade" x-text="deliveryFee > 0 ? (new Intl.NumberFormat('fr-FR').format(deliveryFee) + ' FCFA') : 'À discuter sur WhatsApp'"></span>
            </div>

            <template x-if="addressError">
                <p class="mt-4 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="addressError"></p>
            </template>

            <div class="mt-10 flex gap-4">
                <button type="button" @click="prev()" class="px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary">Retour</button>
                <button
                    type="button"
                    @click="
                        if (! $refs.regionInput.value.trim() || ! $refs.cityInput.value.trim()) {
                            addressError = 'Merci de renseigner la région et la ville avant de continuer.';
                        } else if (! deliveryId) {
                            addressError = 'Merci de choisir une zone de livraison avant de continuer.';
                        } else {
                            addressError = null;
                            next();
                        }
                    "
                    class="bg-secondary-shade px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary"
                >Continuer</button>
            </div>
        </div>

        {{-- Étape 3 — Paiement --}}
        <div x-show="step === 3" x-cloak>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-grey">Étape 3</p>
            <h2 class="mt-1 font-display text-2xl font-normal italic text-secondary sm:text-3xl">Paiement</h2>

            <div class="mt-7 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @php
                    // Wave/Orange Money/Carte passent par PayTech (voir PaymentDispatcher).
                    // Djamo/Free Money (PayDunya) restent fonctionnels côté serveur mais ne sont
                    // plus affichés ici — retirer ces deux lignes suffit à les faire réapparaître.
                    $paytechAvailable = (bool) config('services.paytech.key');
                    $onlineMethods = [
                        'wave' => ['label' => 'Wave', 'available' => $paytechAvailable, 'logo' => 'logo_wave.png'],
                        'orange_money' => ['label' => 'Orange Money', 'available' => $paytechAvailable, 'logo' => 'logo_orange_money.png'],
                        'carte' => ['label' => 'Carte bancaire', 'available' => $paytechAvailable, 'logo' => 'logo_carte.jpeg'],
                    ];
                @endphp
                @foreach($onlineMethods as $value => $method)
                    @if($method['available'])
                        <label class="group relative flex cursor-pointer flex-col items-center justify-center gap-1.5 border border-[#E5E2DA] bg-white px-4 py-5 text-center transition-all duration-200 hover:border-tertiary/60 hover:bg-grey-tint/40 has-[:checked]:border-primary has-[:checked]:bg-primary-tint has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary/30">
                            <input type="radio" name="payment_method" value="{{ $value }}" x-model="paymentMethod" required class="peer sr-only">
                            <span class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center border border-[#E5E2DA] text-transparent transition peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white">
                                <i class="fa-solid fa-check text-[9px]"></i>
                            </span>
                            <img src="{{ asset('images/'.$method['logo']) }}" alt="{{ $method['label'] }}" class="h-9 w-auto max-w-[110px] shrink-0 object-contain">
                        </label>
                    @else
                        <div class="relative flex cursor-not-allowed flex-col items-center justify-center gap-1.5 border border-[#E5E2DA] bg-white px-4 py-5 text-center opacity-40">
                            <img src="{{ asset('images/'.$method['logo']) }}" alt="{{ $method['label'] }}" class="h-9 w-auto max-w-[110px] shrink-0 object-contain grayscale">
                            <span class="mt-1 text-[10px] uppercase tracking-[0.08em] text-grey">Bientôt disponible</span>
                        </div>
                    @endif
                @endforeach

                <label class="group relative flex cursor-pointer flex-col items-center justify-center gap-1.5 border border-[#E5E2DA] bg-white px-4 py-5 text-center transition-all duration-200 hover:border-tertiary/60 hover:bg-grey-tint/40 has-[:checked]:border-primary has-[:checked]:bg-primary-tint has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary/30">
                    <input type="radio" name="payment_method" value="cod" x-model="paymentMethod" required class="peer sr-only">
                    <span class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center border border-[#E5E2DA] text-transparent transition peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white">
                        <i class="fa-solid fa-check text-[9px]"></i>
                    </span>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center bg-secondary text-white" title="Paiement à la livraison" aria-label="Paiement à la livraison">
                        <i class="fa-solid fa-truck text-base"></i>
                    </span>
                </label>
            </div>

            <div class="mt-8">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary">Code promo (optionnel)</label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input type="text" name="coupon_code" value="{{ old('coupon_code') }}" placeholder="Entrez votre code promo" class="w-full border border-[#E5E2DA] bg-white px-4 py-3 text-sm text-secondary outline-none transition focus:border-primary sm:flex-1">
                    <button type="button" class="shrink-0 border border-[#E5E2DA] px-6 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-secondary transition hover:border-secondary/40">Appliquer</button>
                </div>
            </div>

            <div class="mt-10 flex gap-4">
                <button type="button" @click="prev()" class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-grey transition hover:text-secondary">Retour</button>
                <button type="button" @click="next()" class="flex flex-1 items-center justify-center gap-2 bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary-shade sm:flex-none">
                    Continuer
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>
        </div>

        {{-- Étape 4 — Confirmation --}}
        <div x-show="step === 4" x-cloak>
            <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-grey">Étape 4 — Récapitulatif</h2>

            <div class="mt-6 divide-y divide-secondary-shade/10 border-y border-secondary-shade/10">
                <template x-for="item in $store.cart.items" :key="item.product_id + ':' + item.variant_id">
                    <div class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-medium text-secondary-shade" x-text="item.name"></p>
                            <template x-if="item.variant_label">
                                <p class="text-xs text-grey" x-text="item.variant_label"></p>
                            </template>
                            <p class="text-xs text-grey" x-text="'Qté : ' + item.quantity"></p>
                        </div>
                        <p class="text-sm font-medium text-secondary-shade" x-text="new Intl.NumberFormat('fr-FR').format(item.subtotal) + ' FCFA'"></p>
                    </div>
                </template>
            </div>

            <div class="mt-4 space-y-1.5 text-sm text-grey">
                <div class="flex justify-between">
                    <span>Sous-total</span>
                    <span x-text="new Intl.NumberFormat('fr-FR').format($store.cart.subtotal) + ' FCFA'"></span>
                </div>
                <div class="flex justify-between">
                    <span>Livraison</span>
                    <span x-text="deliveryFee > 0 ? (new Intl.NumberFormat('fr-FR').format(deliveryFee) + ' FCFA') : 'À discuter sur WhatsApp'"></span>
                </div>
            </div>

            <div class="mt-3 flex justify-between border-t border-secondary-shade/10 pt-3">
                <span class="text-sm font-medium uppercase tracking-[0.1em] text-secondary-shade">Total</span>
                <span class="font-display text-xl italic text-secondary-shade" x-text="new Intl.NumberFormat('fr-FR').format($store.cart.subtotal + deliveryFee) + ' FCFA' + (deliveryFee === 0 ? ' + livraison' : '')"></span>
            </div>

            <template x-if="paymentMethod === 'cod'">
                <p class="mt-4 text-xs text-grey">Paiement à la livraison. Vous recevrez un appel de confirmation avant l'expédition.</p>
            </template>
            <template x-if="paymentMethod !== 'cod'">
                <p class="mt-4 text-xs text-grey">Vous serez redirigé vers la page de paiement sécurisée pour finaliser votre commande. Elle ne sera confirmée qu'une fois le paiement effectivement reçu.</p>
            </template>
            <template x-if="deliveryFee === 0">
                <p class="mt-2 text-xs text-primary">
                    <i class="fa-brands fa-whatsapp mr-1"></i>Nous vous contacterons sur WhatsApp pour confirmer le tarif et le délai de livraison.
                </p>
            </template>

            <div class="mt-10 flex gap-4">
                <button type="button" @click="prev()" class="px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary">Retour</button>
                <button
                    type="submit"
                    class="bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary-shade"
                    x-text="paymentMethod === 'cod' ? 'Confirmer la commande' : ('Payer ' + new Intl.NumberFormat('fr-FR').format($store.cart.subtotal + deliveryFee) + ' FCFA')"
                ></button>
            </div>
        </div>

    </form>
</div>
@endsection

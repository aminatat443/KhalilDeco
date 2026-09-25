@extends('layouts.app')

@section('title', 'Mon profil — Khalil Déco')

@section('content')
<div class="mx-auto max-w-4xl px-6 py-16 sm:px-10">

    <div class="flex items-center gap-5">
        <div class="flex h-16 w-16 shrink-0 items-center justify-center bg-secondary-shade font-display text-2xl italic text-white">
            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h1 class="font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">{{ $user->name }}</h1>
            <p class="mt-0.5 text-sm text-grey">{{ $user->email }}</p>
        </div>
    </div>

    @unless($user->isStaffMember())
        <div class="mt-10">
            <x-account-nav active="profil" />
        </div>
    @endunless

    {{-- Le staff n'achète pas sur la boutique, pas besoin d'adresse de livraison. --}}
    @unless($user->isStaffMember())
        <div id="adresse" class="mt-10 scroll-mt-24">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-location-dot text-sm text-primary"></i>
                <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade">Adresse de livraison</h2>
            </div>
            <p class="mt-2 text-sm text-grey">
                Enregistrez votre adresse une fois pour toutes : elle sera proposée automatiquement à chaque commande, pour aller plus vite.
            </p>

            <form
                action="{{ route('account.address.update') }}"
                method="POST"
                class="mt-6 space-y-6 border border-secondary-shade/10 bg-white p-6 shadow-sm sm:p-8"
                x-data="{
                    locating: false,
                    locationError: null,
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
                                    const city = addr.suburb || addr.neighbourhood || addr.quarter || addr.city_district
                                        || addr.city || addr.town || addr.village || addr.county || '';
                                    const street = [addr.house_number, addr.road].filter(Boolean).join(' ');

                                    if (this.$refs.regionInput && region) this.$refs.regionInput.value = region;
                                    if (this.$refs.cityInput && city) this.$refs.cityInput.value = city;
                                    if (this.$refs.addressInput && street) this.$refs.addressInput.value = street;

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
                @csrf
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Nom complet</label>
                        <input type="text" name="full_name" value="{{ old('full_name', $defaultAddress->full_name ?? $user->name) }}" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Téléphone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $defaultAddress->phone ?? '') }}" required placeholder="77 000 00 00" class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                    </div>
                </div>

                <div>
                    <button
                        type="button"
                        @click="detectLocation()"
                        :disabled="locating"
                        class="inline-flex items-center gap-2 border border-secondary-shade/20 px-4 py-2.5 text-[10px] font-semibold uppercase tracking-[0.06em] text-secondary-shade transition hover:border-secondary-shade disabled:opacity-50 lg:text-xs lg:tracking-[0.1em]"
                    >
                        <i class="fa-solid" :class="locating ? 'fa-spinner fa-spin' : 'fa-location-crosshairs'"></i>
                        <span x-text="locating ? 'Localisation…' : 'Utiliser ma position actuelle'"></span>
                    </button>
                    <template x-if="locationError">
                        <p class="mt-2 text-xs text-red-600" x-text="locationError"></p>
                    </template>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Région</label>
                        <input x-ref="regionInput" type="text" name="region" value="{{ old('region', $defaultAddress->region ?? '') }}" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Ville</label>
                        <input x-ref="cityInput" type="text" name="city" value="{{ old('city', $defaultAddress?->quartier ?: ($defaultAddress?->city ?? '')) }}" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Complément d'adresse</label>
                    <input x-ref="addressInput" type="text" name="address" value="{{ old('address', $defaultAddress->address ?? '') }}" placeholder="Numéro de rue, villa, repère…" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Instructions (optionnel)</label>
                    <textarea name="instructions" rows="2" class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">{{ old('instructions', $defaultAddress->instructions ?? '') }}</textarea>
                </div>

                <button type="submit" class="w-full bg-secondary-shade px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary sm:w-auto">
                    Enregistrer mon adresse
                </button>
            </form>
        </div>
    @endunless

    {{-- Le staff (Gestionnaire/Admin/Super Admin) n'achète pas sur la boutique : pas d'historique
         de commandes pour ces rôles, seul le profil est pertinent. --}}
    @unless($user->isStaffMember())
        <div class="mt-12 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-bag-shopping text-sm text-primary"></i>
                <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade">Commandes récentes</h2>
            </div>
            <a href="{{ route('account.orders') }}" class="text-xs font-semibold text-primary hover:underline">Tout voir</a>
        </div>

        <div class="mt-4 space-y-3">
            @forelse($recentOrders as $order)
                <a href="{{ route('account.orders.show', $order) }}" class="flex flex-col gap-3 border border-secondary-shade/10 bg-white p-4 text-sm shadow-sm transition hover:border-primary/40 hover:shadow-md sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-5">
                    <div class="min-w-0">
                        <span class="font-medium text-secondary-shade">{{ $order->order_number }}</span>
                        <span class="block text-xs text-grey sm:ml-2 sm:inline">{{ $order->created_at->format('d/m/Y') }} · {{ $order->items_count }} article(s)</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 sm:justify-end sm:gap-4">
                        @php($returnInfo = $order->returnStatusInfo())
                        <x-status-pill :tone="$returnInfo['tone'] ?? (\App\Models\Order::STATUS_TONES[$order->status] ?? 'neutral')" :label="$returnInfo['label'] ?? (\App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status)" />
                        <span class="whitespace-nowrap text-xs font-medium text-secondary-shade">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                        <i class="hidden shrink-0 fa-solid fa-chevron-right text-[10px] text-grey/50 sm:block"></i>
                    </div>
                </a>
            @empty
                <div class="flex flex-col items-center justify-center border border-dashed border-secondary-shade/15 py-16 text-center">
                    <i class="fa-solid fa-bag-shopping mb-4 text-2xl text-secondary-shade/20"></i>
                    <p class="text-sm text-grey">Aucune commande pour le moment.</p>
                </div>
            @endforelse
        </div>
    @endunless

    {{-- Suppression du compte — confirmation par alerte (pas de mot de passe demandé : les
         comptes connectés uniquement via Google n'en ont pas). --}}
    <div class="mt-16 border border-red-200 bg-red-50 p-6">
        <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-red-700">Zone dangereuse</h2>
        <p class="mt-2 text-sm text-grey">
            La suppression de votre compte est définitive et ne peut pas être annulée.
        </p>

        <form
            action="{{ route('account.destroy') }}"
            method="POST"
            class="mt-4"
            onsubmit="return confirm('Supprimer définitivement votre compte Khalil Déco ? Cette action est irréversible.');"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-600 px-6 py-3 text-xs font-semibold uppercase tracking-[0.1em] text-white transition hover:bg-red-700">
                Supprimer mon compte
            </button>
        </form>
    </div>

</div>
@endsection

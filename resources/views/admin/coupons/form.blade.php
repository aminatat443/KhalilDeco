@extends('layouts.admin-modal')

@php($modalBack = route('admin.coupons.index'))

@section('title', $coupon->exists ? 'Modifier le code promo' : 'Nouveau code promo')

@section('modal')

<h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">
    {{ $coupon->exists ? 'Modifier « '.$coupon->code.' »' : 'Nouveau code promo' }}
</h1>

<form
    action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}"
    method="POST"
    class="mt-8 space-y-6"
>
    @csrf
    @if($coupon->exists) @method('PUT') @endif

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Code</label>
        <input type="text" name="code" value="{{ old('code', $coupon->code) }}" required placeholder="KHALIL20" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm uppercase outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Type</label>
            <select name="type" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="percentage" @selected(old('type', $coupon->type) === 'percentage')>Pourcentage</option>
                <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Montant fixe</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Valeur</label>
            <input type="number" name="value" value="{{ old('value', $coupon->value) }}" required min="1" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Montant minimum (optionnel)</label>
            <input type="number" name="min_amount" value="{{ old('min_amount', $coupon->min_amount) }}" min="0" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Limite d'utilisation (optionnel)</label>
            <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" min="1" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Début (optionnel)</label>
            <input type="date" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d')) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Expiration (optionnel)</label>
            <input type="date" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d')) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    <label class="flex items-center gap-2.5 text-sm text-secondary-shade dark:text-white">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true)) class="h-4 w-4 text-primary focus:ring-primary">
        Code actif
    </label>

    <div class="flex gap-4 pt-2">
        <a href="{{ route('admin.coupons.index') }}" class="px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70">Annuler</a>
        <button type="submit" class="bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
            {{ $coupon->exists ? 'Enregistrer' : 'Créer le code' }}
        </button>
    </div>
</form>

@if($coupon->exists)
    <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="mt-6" onsubmit="return confirm('Supprimer ce code promo ?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-xs font-semibold uppercase tracking-[0.15em] text-red-600 hover:underline dark:text-red-400">Supprimer ce code</button>
    </form>
@endif

@endsection

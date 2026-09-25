@extends('layouts.admin')

@section('title', 'Paramètres')

@section('content')

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Campagnes email</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Panier abandonné, favoris/stock faible et newsletter — historique et automatisation.</p>

<div class="mt-6 flex flex-wrap gap-2 border-b border-secondary-shade/10 pb-px dark:border-white/10">
    <a href="{{ route('admin.campaigns.index') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Campagnes</a>
    <a href="{{ route('admin.email-templates.index') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Modèles d'emails</a>
    <a href="{{ route('admin.campaigns.automations.edit') }}" class="border-b-2 border-primary px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-primary">Paramètres</a>
    <a href="{{ route('admin.campaign-sends.index') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Historique des envois</a>
</div>

<form action="{{ route('admin.campaigns.automations.update') }}" method="POST" class="mt-8 max-w-2xl space-y-6">
    @csrf
    @method('PUT')

    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-sm font-semibold text-secondary-shade dark:text-white">
                    <span class="h-1.5 w-1.5 {{ $settings->abandoned_cart_enabled ? 'bg-primary' : 'bg-grey/40' }}"></span>
                    Panier abandonné
                </h2>
                <p class="mt-1 text-xs text-grey dark:text-white/40">Relance automatique d'un client connecté ayant laissé des articles dans son panier.</p>
            </div>
            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                <input type="checkbox" name="abandoned_cart_enabled" value="1" @checked($settings->abandoned_cart_enabled) class="peer sr-only">
                <div class="h-6 w-11 border border-secondary-shade/20 bg-grey-tint transition peer-checked:bg-primary dark:border-white/10 dark:bg-white/10"></div>
                <div class="absolute left-1 h-4 w-4 bg-white shadow-sm transition peer-checked:translate-x-5"></div>
            </label>
        </div>
        <div class="mt-4">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Délai avant relance (jours)</label>
            <input type="number" name="abandoned_cart_delay_days" value="{{ old('abandoned_cart_delay_days', $settings->abandoned_cart_delay_days) }}" min="1" max="30" required class="w-32 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-sm font-semibold text-secondary-shade dark:text-white">
                    <span class="h-1.5 w-1.5 {{ $settings->low_stock_favorite_enabled ? 'bg-primary' : 'bg-grey/40' }}"></span>
                    Favoris / stock faible
                </h2>
                <p class="mt-1 text-xs text-grey dark:text-white/40">Alerte automatique quand un produit mis en favori passe sous le seuil de stock.</p>
            </div>
            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                <input type="checkbox" name="low_stock_favorite_enabled" value="1" @checked($settings->low_stock_favorite_enabled) class="peer sr-only">
                <div class="h-6 w-11 border border-secondary-shade/20 bg-grey-tint transition peer-checked:bg-primary dark:border-white/10 dark:bg-white/10"></div>
                <div class="absolute left-1 h-4 w-4 bg-white shadow-sm transition peer-checked:translate-x-5"></div>
            </label>
        </div>
        <div class="mt-4">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Seuil de stock faible (unités)</label>
            <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $settings->low_stock_threshold) }}" min="0" max="100" required class="w-32 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <p class="mt-1.5 text-xs text-grey dark:text-white/40">Stock ≤ ce seuil → considéré comme bientôt épuisé. Même valeur utilisée pour l'alerte "stock faible" du tableau de bord.</p>
        </div>
    </div>

    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-sm font-semibold text-secondary-shade dark:text-white">
                    <span class="h-1.5 w-1.5 {{ $settings->promotion_favorite_enabled ? 'bg-primary' : 'bg-grey/40' }}"></span>
                    Promotion sur favoris
                </h2>
                <p class="mt-1 text-xs text-grey dark:text-white/40">Alerte automatique quand un produit mis en favori passe « En promotion ».</p>
            </div>
            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                <input type="checkbox" name="promotion_favorite_enabled" value="1" @checked($settings->promotion_favorite_enabled) class="peer sr-only">
                <div class="h-6 w-11 border border-secondary-shade/20 bg-grey-tint transition peer-checked:bg-primary dark:border-white/10 dark:bg-white/10"></div>
                <div class="absolute left-1 h-4 w-4 bg-white shadow-sm transition peer-checked:translate-x-5"></div>
            </label>
        </div>
    </div>

    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-sm font-semibold text-secondary-shade dark:text-white">
                    <span class="h-1.5 w-1.5 {{ $settings->newsletter_enabled ? 'bg-primary' : 'bg-grey/40' }}"></span>
                    Newsletter
                </h2>
                <p class="mt-1 text-xs text-grey dark:text-white/40">Autorise la création et l'envoi de campagnes newsletter aux abonnés actifs.</p>
            </div>
            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                <input type="checkbox" name="newsletter_enabled" value="1" @checked($settings->newsletter_enabled) class="peer sr-only">
                <div class="h-6 w-11 border border-secondary-shade/20 bg-grey-tint transition peer-checked:bg-primary dark:border-white/10 dark:bg-white/10"></div>
                <div class="absolute left-1 h-4 w-4 bg-white shadow-sm transition peer-checked:translate-x-5"></div>
            </label>
        </div>
    </div>

    <button type="submit" class="bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
        Enregistrer
    </button>
</form>

@endsection

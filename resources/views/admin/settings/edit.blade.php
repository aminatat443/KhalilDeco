@extends('layouts.admin')

@section('title', 'Configuration')

@section('content')

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Configuration</h1>
<p class="mt-2 text-sm text-grey dark:text-white/40">Coordonnées de la boutique et visuels affichés sur la facture PDF envoyée aux clients.</p>
<p class="mt-3 inline-block bg-grey-tint px-3 py-1.5 text-xs text-grey dark:bg-white/10 dark:text-white/40">
    Stockage des images : <strong class="text-secondary-shade dark:text-white">{{ App\Models\Setting::mediaDisk() === 'cloudinary' ? 'Cloudinary' : 'Local' }}</strong>
    @if(App\Models\Setting::mediaDisk() !== 'cloudinary')
        — ajoutez <code>CLOUDINARY_URL</code> dans le fichier .env pour basculer automatiquement sur Cloudinary.
    @endif
</p>

<div class="mt-8 max-w-2xl">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-grey dark:text-white/40">Livraison</p>

    <div class="mt-4 bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">
                    <span class="flex h-6 w-6 items-center justify-center bg-primary-tint text-primary dark:bg-primary/15"><i class="fa-solid fa-truck text-[10px]"></i></span>
                    Zones et tarifs de livraison
                </h2>
                <p class="mt-1 text-xs text-grey dark:text-white/40">Source unique des tarifs utilisés au panier, au checkout et sur les commandes — {{ $deliveriesCount }} zone(s) enregistrée(s).</p>
            </div>
            <a href="{{ route('admin.deliveries.index') }}" class="whitespace-nowrap bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
                Gérer les zones
            </a>
        </div>
    </div>
</div>

<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="mt-8 max-w-2xl space-y-10">
    @csrf

    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">
            <span class="flex h-6 w-6 items-center justify-center bg-primary-tint text-primary dark:bg-primary/15"><i class="fa-solid fa-address-card text-[10px]"></i></span>
            Coordonnées (émetteur de la facture)
        </h2>

        <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nom de la boutique</label>
                <input type="text" name="shop_name" value="{{ old('shop_name', $settings->shop_name) }}" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm text-secondary-shade outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Téléphone</label>
                <input type="text" name="shop_phone" value="{{ old('shop_phone', $settings->shop_phone) }}" placeholder="+221 77 000 00 00" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm text-secondary-shade outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Adresse</label>
                <input type="text" name="shop_address" value="{{ old('shop_address', $settings->shop_address) }}" placeholder="Dakar, Sénégal" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm text-secondary-shade outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Email</label>
                <input type="email" name="shop_email" value="{{ old('shop_email', $settings->shop_email) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm text-secondary-shade outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>
        </div>
    </div>

    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">
            <span class="flex h-6 w-6 items-center justify-center bg-primary-tint text-primary dark:bg-primary/15"><i class="fa-solid fa-stamp text-[10px]"></i></span>
            Informations légales
        </h2>
        <p class="mt-1 text-xs text-grey dark:text-white/40">Identifiants affichés sur la facture, sous les coordonnées de la boutique.</p>

        <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">NINEA</label>
                <input type="text" name="ninea" value="{{ old('ninea', $settings->ninea) }}" placeholder="000000000" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm text-secondary-shade outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">RCCM</label>
                <input type="text" name="rccm" value="{{ old('rccm', $settings->rccm) }}" placeholder="SN DKR 2024 A 0000" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm text-secondary-shade outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>
        </div>
    </div>

    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">
            <span class="flex h-6 w-6 items-center justify-center bg-primary-tint text-primary dark:bg-primary/15"><i class="fa-solid fa-image text-[10px]"></i></span>
            Logo de la facture
        </h2>
        <p class="mt-1 text-xs text-grey dark:text-white/40">Affiché en haut à gauche de chaque facture PDF. Sans envoi ici, le logo officiel Khalil Déco est utilisé par défaut.</p>

        <div class="mt-4 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:gap-6">
            <img src="{{ $settings->mediaUrl('invoice_logo') ?? asset('images/logo_khalil_deco_full.png') }}" alt="Logo" class="h-16 w-auto shrink-0 border border-secondary-shade/10 bg-grey-tint p-2 dark:border-white/10 dark:bg-white/5">
            <input type="file" name="invoice_logo" accept="image/*" class="w-full min-w-0 max-w-full text-sm text-secondary-shade file:mr-4 file:border-0 file:bg-grey-tint file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-[0.1em] dark:text-white dark:file:bg-white/10">
        </div>
    </div>

    <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
        <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">
            <span class="flex h-6 w-6 items-center justify-center bg-primary-tint text-primary dark:bg-primary/15"><i class="fa-solid fa-signature text-[10px]"></i></span>
            Signature &amp; cachet
        </h2>
        <p class="mt-1 text-xs text-grey dark:text-white/40">Photo de la signature et du cachet, affichée en bas de la facture PDF.</p>

        <div class="mt-4 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:gap-6">
            @if($signatureUrl = $settings->mediaUrl('invoice_signature'))
                <img src="{{ $signatureUrl }}" alt="Signature et cachet" class="h-20 w-auto shrink-0 border border-secondary-shade/10 bg-grey-tint p-2 dark:border-white/10 dark:bg-white/5">
            @endif
            <input type="file" name="invoice_signature" accept="image/*" class="w-full min-w-0 max-w-full text-sm text-secondary-shade file:mr-4 file:border-0 file:bg-grey-tint file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-[0.1em] dark:text-white dark:file:bg-white/10">
        </div>
    </div>

    <button type="submit" class="bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
        Enregistrer
    </button>
</form>

@endsection

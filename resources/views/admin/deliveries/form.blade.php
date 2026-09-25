@extends('layouts.admin-modal')

@php($modalBack = route('admin.deliveries.index'))

@section('title', $delivery->exists ? 'Modifier la zone de livraison' : 'Nouvelle zone de livraison')

@section('modal')

<h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">
    {{ $delivery->exists ? 'Modifier « '.$delivery->zone.' »' : 'Nouvelle zone de livraison' }}
</h1>

<form
    action="{{ $delivery->exists ? route('admin.deliveries.update', $delivery) : route('admin.deliveries.store') }}"
    method="POST"
    class="mt-8 space-y-6"
>
    @csrf
    @if($delivery->exists) @method('PUT') @endif

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nom de la zone</label>
        <input type="text" name="zone" value="{{ old('zone', $delivery->zone) }}" required placeholder="Pikine" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Tarif de livraison (FCFA)</label>
            <input type="number" name="fee" value="{{ old('fee', $delivery->fee) }}" required min="0" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <p class="mt-1.5 text-xs text-grey dark:text-white/40">Laisser à 0 pour « tarif à discuter sur WhatsApp ».</p>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Délai estimé (optionnel)</label>
            <input type="text" name="estimated_days" value="{{ old('estimated_days', $delivery->estimated_days) }}" placeholder="Moins de 24h" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    <label class="flex items-center gap-2.5 text-sm text-secondary-shade dark:text-white">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $delivery->is_active ?? true)) class="h-4 w-4 text-primary focus:ring-primary">
        Zone active — visible dans la liste des zones au checkout
    </label>

    @if($delivery->exists && $delivery->orders()->exists())
        <p class="text-xs text-grey dark:text-white/40">Cette zone a déjà été utilisée dans des commandes : son nom et son tarif y restent figés tels qu'ils étaient au moment de chaque commande, même après une modification ici.</p>
    @endif

    <div class="flex gap-4 pt-2">
        <a href="{{ route('admin.deliveries.index') }}" class="px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70">Annuler</a>
        <button type="submit" class="bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
            {{ $delivery->exists ? 'Enregistrer' : 'Créer la zone' }}
        </button>
    </div>
</form>

@if($delivery->exists)
    @if($delivery->orders()->exists())
        <p class="mt-6 text-xs text-grey dark:text-white/40">Suppression indisponible — cette zone a déjà été utilisée dans des commandes. Désactivez-la ci-dessus pour qu'elle disparaisse du checkout tout en conservant l'historique.</p>
    @else
        <form action="{{ route('admin.deliveries.destroy', $delivery) }}" method="POST" class="mt-6" onsubmit="return confirm('Supprimer définitivement cette zone de livraison ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs font-semibold uppercase tracking-[0.15em] text-red-600 hover:underline dark:text-red-400">Supprimer cette zone</button>
        </form>
    @endif
@endif

@endsection

@extends('layouts.admin-modal')

@php($modalBack = route('admin.promotions.index'))

@section('title', $promotion->exists ? 'Modifier la promotion' : 'Nouvelle promotion')

@section('modal')

<h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">
    {{ $promotion->exists ? 'Modifier la promotion' : 'Nouvelle promotion' }}
</h1>

<form
    action="{{ $promotion->exists ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}"
    method="POST"
    class="mt-8 space-y-6"
>
    @csrf
    @if($promotion->exists) @method('PUT') @endif

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Catégorie ciblée (optionnel)</label>
        <select name="category_id" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <option value="">— Aucune —</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $promotion->category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Produit ciblé (optionnel, prioritaire sur la catégorie)</label>
        <select name="product_id" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <option value="">— Aucun —</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected(old('product_id', $promotion->product_id) == $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Type</label>
            <select name="type" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="percentage" @selected(old('type', $promotion->type) === 'percentage')>Pourcentage</option>
                <option value="fixed" @selected(old('type', $promotion->type) === 'fixed')>Montant fixe</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Valeur</label>
            <input type="number" name="value" value="{{ old('value', $promotion->value) }}" required min="1" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Début (optionnel)</label>
            <input type="date" name="starts_at" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d')) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Fin (optionnel)</label>
            <input type="date" name="ends_at" value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d')) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    <label class="flex items-center gap-2.5 text-sm text-secondary-shade dark:text-white">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion->is_active ?? true)) class="h-4 w-4 text-primary focus:ring-primary">
        Promotion active
    </label>

    <div class="flex gap-4 pt-2">
        <a href="{{ route('admin.promotions.index') }}" class="px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70">Annuler</a>
        <button type="submit" class="bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
            {{ $promotion->exists ? 'Enregistrer' : 'Créer la promotion' }}
        </button>
    </div>
</form>

@if($promotion->exists)
    <form action="{{ route('admin.promotions.destroy', $promotion) }}" method="POST" class="mt-6" onsubmit="return confirm('Supprimer cette promotion ?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-xs font-semibold uppercase tracking-[0.15em] text-red-600 hover:underline dark:text-red-400">Supprimer cette promotion</button>
    </form>
@endif

@endsection

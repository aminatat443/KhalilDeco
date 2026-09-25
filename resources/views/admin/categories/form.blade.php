@extends('layouts.admin-modal')

@php($modalBack = route('admin.categories.index'))

@section('title', $category->exists ? 'Modifier la catégorie' : 'Nouvelle catégorie')

@section('modal')

<h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">
    {{ $category->exists ? 'Modifier « '.$category->name.' »' : 'Nouvelle catégorie' }}
</h1>

<form
    action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
    method="POST"
    class="mt-8 space-y-6"
>
    @csrf
    @if($category->exists) @method('PUT') @endif

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nom</label>
        <input type="text" name="name" value="{{ old('name', $category->name) }}" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Catégorie parente</label>
        <select name="parent_id" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <option value="">— Univers (aucun parent) —</option>
            @foreach($universes as $universe)
                <option value="{{ $universe->id }}" @selected(old('parent_id', $category->parent_id) == $universe->id)>{{ $universe->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Description (optionnel)</label>
        <textarea name="description" rows="3" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">{{ old('description', $category->description) }}</textarea>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Image (URL, optionnel)</label>
        <input type="text" name="image" value="{{ old('image', $category->image) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Ordre d'affichage</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <label class="flex items-center gap-2.5 text-sm text-secondary-shade dark:text-white">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true)) class="h-4 w-4 text-primary focus:ring-primary">
        Catégorie active
    </label>

    @if($attributes->isNotEmpty())
        @php($selected = old('attribute_ids', $selectedAttributeIds ?? []))
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Attributs disponibles pour cette catégorie</label>
            <p class="mb-3 text-xs text-grey dark:text-white/40">Ils apparaîtront lors de la création d'un produit dans cette catégorie.</p>
            <div class="flex flex-wrap gap-2">
                @foreach($attributes as $attribute)
                    <label class="flex cursor-pointer items-center gap-2 border border-secondary-shade/20 px-3.5 py-2 text-sm text-secondary-shade transition hover:border-primary/50 has-[:checked]:border-primary has-[:checked]:bg-primary has-[:checked]:text-white dark:border-white/20 dark:text-white/70 dark:has-[:checked]:border-primary dark:has-[:checked]:bg-primary dark:has-[:checked]:text-white">
                        <input type="checkbox" name="attribute_ids[]" value="{{ $attribute->id }}" @checked(in_array($attribute->id, $selected)) class="hidden">
                        {{ $attribute->name }}
                    </label>
                @endforeach
            </div>
            <a href="{{ route('admin.attributes.index') }}" class="mt-3 inline-block text-xs font-semibold text-primary hover:underline">
                Gérer les attributs
            </a>
        </div>
    @endif

    <div class="flex gap-4 pt-2">
        <a href="{{ route('admin.categories.index') }}" class="px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70">Annuler</a>
        <button type="submit" class="bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
            {{ $category->exists ? 'Enregistrer' : 'Créer la catégorie' }}
        </button>
    </div>
</form>

@if($category->exists)
    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="mt-6" onsubmit="return confirm('Supprimer cette catégorie ?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-xs font-semibold uppercase tracking-[0.15em] text-red-600 hover:underline dark:text-red-400">
            Supprimer cette catégorie
        </button>
    </form>
@endif

@endsection

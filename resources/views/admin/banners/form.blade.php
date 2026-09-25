@extends('layouts.admin-modal')

@php($modalBack = route('admin.banners.index'))

@section('title', $banner->exists ? 'Modifier la bannière' : 'Nouvelle bannière')

@section('modal')

<h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">
    {{ $banner->exists ? 'Modifier la bannière' : 'Nouvelle bannière' }}
</h1>

<form
    action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}"
    method="POST"
    enctype="multipart/form-data"
    class="mt-8 space-y-6"
>
    @csrf
    @if($banner->exists) @method('PUT') @endif

    @if($banner->exists)
        <div class="aspect-video overflow-hidden bg-grey-tint dark:bg-white/10">
            <img src="{{ $banner->image }}" alt="" class="h-full w-full object-cover">
        </div>
    @endif

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">
            Image {{ $banner->exists ? '(laisser vide pour conserver l\'actuelle)' : '' }}
        </label>
        <input type="file" name="image_file" accept="image/*" {{ $banner->exists ? '' : 'required' }} class="w-full text-sm text-secondary-shade file:mr-4 file:border-0 file:bg-grey-tint file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-[0.1em] dark:text-white dark:file:bg-white/10">
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Emplacement</label>
        <select name="placement" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            @foreach(['hero' => 'Hero (accueil)', 'categorie' => 'Section catégorie', 'promo' => 'Section promotions'] as $value => $label)
                <option value="{{ $value }}" @selected(old('placement', $banner->placement) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Titre (optionnel)</label>
        <input type="text" name="title" value="{{ old('title', $banner->title) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Sous-titre (optionnel)</label>
        <input type="text" name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Lien (optionnel)</label>
            <input type="text" name="link_url" value="{{ old('link_url', $banner->link_url) }}" placeholder="/femme" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Texte du bouton (optionnel)</label>
            <input type="text" name="button_text" value="{{ old('button_text', $banner->button_text) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Ordre d'affichage</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <label class="flex items-center gap-2.5 text-sm text-secondary-shade dark:text-white">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active ?? true)) class="h-4 w-4 text-primary focus:ring-primary">
        Bannière active
    </label>

    <div class="flex gap-4 pt-2">
        <a href="{{ route('admin.banners.index') }}" class="px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70">Annuler</a>
        <button type="submit" class="bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
            {{ $banner->exists ? 'Enregistrer' : 'Créer la bannière' }}
        </button>
    </div>
</form>

@if($banner->exists)
    <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="mt-6" onsubmit="return confirm('Supprimer cette bannière ?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-xs font-semibold uppercase tracking-[0.15em] text-red-600 hover:underline dark:text-red-400">Supprimer cette bannière</button>
    </form>
@endif

@endsection

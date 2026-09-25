@extends('layouts.admin')

@section('title', 'Catégories')

@section('content')

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Catégories</h1>
    @can('create', App\Models\Category::class)
        <a href="{{ route('admin.categories.create') }}" class="block bg-primary px-6 py-3 text-center text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md sm:inline-block">
            Ajouter une catégorie
        </a>
    @endcan
</div>

{{-- Cartes (mobile/tablette) --}}
<div class="mt-8 space-y-3 lg:hidden">
    @forelse($categories as $universe)
        <div onclick="window.location='{{ route('admin.categories.edit', $universe) }}'" class="cursor-pointer bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <span class="min-w-0 flex-1 truncate font-medium text-secondary-shade dark:text-white">{{ $universe->name }}</span>
                <div class="flex shrink-0 items-center gap-3">
                    <span class="text-xs text-grey dark:text-white/40">{{ $universe->products_count }} produit(s)</span>
                    <x-status-pill :tone="$universe->is_active ? 'green' : 'neutral'" :label="$universe->is_active ? 'Active' : 'Désactivée'" />
                    <a href="{{ route('admin.categories.edit', $universe) }}" title="Modifier" aria-label="Modifier" class="text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-pen"></i></a>
                </div>
            </div>
        </div>
        @foreach($universe->children as $child)
            <div onclick="window.location='{{ route('admin.categories.edit', $child) }}'" class="ml-4 cursor-pointer bg-grey-tint/40 p-4 border border-secondary-shade/10 dark:bg-white/5 dark:border-white/10">
                <div class="flex items-center justify-between gap-3">
                    <span class="min-w-0 flex-1 truncate text-secondary-shade dark:text-white"><i class="fa-solid fa-turn-up fa-rotate-90 mr-2 text-[10px] text-grey dark:text-white/40"></i>{{ $child->name }}</span>
                    <div class="flex shrink-0 items-center gap-3">
                        <span class="text-xs text-grey dark:text-white/40">{{ $child->products_count }} produit(s)</span>
                        <x-status-pill :tone="$child->is_active ? 'green' : 'neutral'" :label="$child->is_active ? 'Active' : 'Désactivée'" />
                        <a href="{{ route('admin.categories.edit', $child) }}" title="Modifier" aria-label="Modifier" class="text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-pen"></i></a>
                    </div>
                </div>
            </div>
        @endforeach
    @empty
        <div class="flex flex-col items-center gap-3 border border-secondary-shade/10 bg-white px-6 py-14 text-center dark:border-white/10 dark:bg-[#16201f]">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-sitemap"></i></span>
            <p class="text-sm text-grey dark:text-white/40">Aucune catégorie pour le moment.</p>
        </div>
    @endforelse
</div>

<div class="mt-8 hidden bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Nom</th>
                <th class="px-6 py-4 font-medium">Produits</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($categories as $universe)
                <tr onclick="window.location='{{ route('admin.categories.edit', $universe) }}'" class="cursor-pointer transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="px-6 py-4 font-medium text-secondary-shade dark:text-white">{{ $universe->name }}</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $universe->products_count }}</td>
                    <td class="px-6 py-4">
                        <x-status-pill :tone="$universe->is_active ? 'green' : 'neutral'" :label="$universe->is_active ? 'Active' : 'Désactivée'" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.categories.edit', $universe) }}" title="Modifier" aria-label="Modifier" class="inline-flex h-8 w-8 items-center justify-center text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-pen"></i></a>
                    </td>
                </tr>
                @foreach($universe->children as $child)
                    <tr onclick="window.location='{{ route('admin.categories.edit', $child) }}'" class="cursor-pointer bg-grey-tint/40 transition hover:bg-grey-tint dark:bg-white/5 dark:hover:bg-white/10">
                        <td class="px-6 py-3 pl-12 text-secondary-shade dark:text-white">
                            <i class="fa-solid fa-turn-up fa-rotate-90 mr-2 text-[10px] text-grey dark:text-white/40"></i>{{ $child->name }}
                        </td>
                        <td class="px-6 py-3 text-grey dark:text-white/50">{{ $child->products_count }}</td>
                        <td class="px-6 py-3">
                            <x-status-pill :tone="$child->is_active ? 'green' : 'neutral'" :label="$child->is_active ? 'Active' : 'Désactivée'" />
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('admin.categories.edit', $child) }}" title="Modifier" aria-label="Modifier" class="inline-flex h-8 w-8 items-center justify-center text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-pen"></i></a>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-14">
                        <div class="flex flex-col items-center gap-3 text-center">
                            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-sitemap"></i></span>
                            <p class="text-sm text-grey dark:text-white/40">Aucune catégorie pour le moment.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@extends('layouts.admin')

@section('title', 'Accueil / Bannières')

@section('content')

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Accueil & bannières</h1>
    @can('create', App\Models\Banner::class)
        <a href="{{ route('admin.banners.create') }}" class="block bg-primary px-6 py-3 text-center text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md sm:inline-block">
            Nouvelle bannière
        </a>
    @endcan
</div>

<div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-4">
    @forelse($banners as $banner)
        <a href="{{ route('admin.banners.edit', $banner) }}" class="group block overflow-hidden bg-white border border-secondary-shade/10 transition hover:shadow-md dark:bg-[#16201f] dark:border-white/10">
            <div class="aspect-video overflow-hidden bg-grey-tint dark:bg-white/10">
                <img src="{{ $banner->image }}" alt="{{ $banner->title }}" class="h-full w-full object-cover">
            </div>
            <div class="p-4">
                <p class="text-xs uppercase tracking-[0.1em] text-grey dark:text-white/40">{{ $banner->placement }}</p>
                <p class="mt-1 text-sm font-medium text-secondary-shade group-hover:text-primary dark:text-white">{{ $banner->title ?? 'Sans titre' }}</p>
                <x-status-pill :tone="$banner->is_active ? 'green' : 'neutral'" :label="$banner->is_active ? 'Active' : 'Désactivée'" class="mt-1" />
            </div>
        </a>
    @empty
        <p class="col-span-full py-10 text-center text-grey dark:text-white/40">Aucune bannière pour le moment.</p>
    @endforelse
</div>

@endsection

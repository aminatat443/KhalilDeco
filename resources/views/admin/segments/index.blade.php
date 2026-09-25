@extends('layouts.admin')

@section('title', 'Segments clients')

@section('content')

<a href="{{ route('admin.campaigns.index') }}" class="text-xs text-grey hover:text-primary dark:text-white/40"><i class="fa-solid fa-arrow-left mr-1"></i>Campagnes</a>

<h1 class="mt-3 text-2xl font-semibold text-secondary-shade dark:text-white">Segments clients</h1>
<p class="mt-1 max-w-2xl text-sm text-grey dark:text-white/50">Calculés en direct à partir des abonnés newsletter réellement liés à un compte et de leur historique de commandes. Cliquez sur un segment pour voir les clients concernés.</p>

<div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($segments as $key => $label)
        @continue($key === 'category_buyers')
        <a
            href="{{ route('admin.segments.show', $key) }}"
            class="group block border border-secondary-shade/10 bg-white p-6 transition hover:border-primary/40 hover:bg-grey-tint/30 dark:border-white/10 dark:bg-[#16201f] dark:hover:bg-white/5"
        >
            <p class="text-3xl font-semibold text-secondary-shade dark:text-white">{{ $counts[$key] }}</p>
            <p class="mt-1 text-sm font-medium text-secondary-shade dark:text-white">{{ $label }}</p>
            <p class="mt-1 text-xs text-grey dark:text-white/40">{{ \App\Services\CustomerSegmentService::DESCRIPTIONS[$key] }}</p>
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.08em] text-primary opacity-70 transition group-hover:opacity-100">
                Voir les clients <i class="fa-solid fa-arrow-right ml-0.5 text-[10px] transition group-hover:translate-x-0.5"></i>
            </p>
        </a>
    @endforeach

    {{-- Segment catégorie : nécessite une sélection avant de pouvoir être consulté --}}
    <div class="border border-dashed border-secondary-shade/20 bg-white p-6 dark:border-white/20 dark:bg-[#16201f]" x-data="{ categoryId: '' }">
        <p class="text-sm font-medium text-secondary-shade dark:text-white">{{ $segments['category_buyers'] }}</p>
        <p class="mt-1 text-xs text-grey dark:text-white/40">{{ \App\Services\CustomerSegmentService::DESCRIPTIONS['category_buyers'] }}</p>
        <div class="mt-4 flex gap-2">
            <select x-model="categoryId" class="w-full border border-secondary-shade/15 bg-white px-3 py-2 text-xs outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="">Choisir une catégorie…</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <button
                type="button"
                :disabled="! categoryId"
                @click="window.location.href = '{{ route('admin.segments.show', 'category_buyers') }}?category_id=' + categoryId"
                class="shrink-0 bg-secondary-shade px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-primary disabled:cursor-not-allowed disabled:opacity-40"
            >
                Voir
            </button>
        </div>
    </div>
</div>

@can('create', App\Models\Campaign::class)
    <a href="{{ route('admin.campaigns.create') }}" class="mt-8 inline-flex items-center gap-2 bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade">
        <i class="fa-solid fa-paper-plane"></i>Créer une campagne
    </a>
@endcan

@endsection

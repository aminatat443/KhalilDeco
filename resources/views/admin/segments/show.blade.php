@extends('layouts.admin')

@section('title', $label)

@section('content')

<a href="{{ route('admin.segments.index') }}" class="text-xs text-grey hover:text-primary dark:text-white/40"><i class="fa-solid fa-arrow-left mr-1"></i>Segments clients</a>

<div class="mt-3 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">{{ $label }}</h1>
        <p class="mt-1 text-sm text-grey dark:text-white/50">{{ $description }}</p>
    </div>

    @can('create', App\Models\Campaign::class)
        @if(! $needsCategory)
            <a
                href="{{ route('admin.campaigns.create', ['segment' => $segment] + ($categoryId ? ['segment_category_id' => $categoryId] : [])) }}"
                class="inline-flex items-center gap-2 bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade"
            >
                <i class="fa-solid fa-paper-plane"></i>Créer une campagne pour ce segment
            </a>
        @endif
    @endcan
</div>

@if($needsCategory)
    <div class="mt-8 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]" x-data="{ categoryId: '' }">
        <p class="text-sm text-secondary-shade dark:text-white">Choisissez une catégorie pour calculer ce segment.</p>
        <div class="mt-4 flex gap-2">
            <select x-model="categoryId" class="w-full max-w-xs border border-secondary-shade/15 bg-white px-3 py-2 text-sm outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="">Choisir une catégorie…</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <button type="button" :disabled="! categoryId" @click="window.location.href = '{{ route('admin.segments.show', 'category_buyers') }}?category_id=' + categoryId" class="shrink-0 bg-secondary-shade px-5 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-white transition hover:bg-primary disabled:cursor-not-allowed disabled:opacity-40">
                Voir
            </button>
        </div>
    </div>
@else

    <div x-data="ajaxFilter({{ request()->filled('q') ? 'true' : 'false' }})">
        @if($segment === 'category_buyers')
            <form method="GET" @submit.prevent="submitForm($event)" class="mt-6 flex flex-wrap items-center gap-3">
                <label class="text-xs font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">Catégorie</label>
                <select name="category_id" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3 py-2 text-xs outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) $categoryId === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        <form method="GET" @submit.prevent="submitForm($event)" class="mt-6 flex items-center gap-3">
            @if($categoryId)<input type="hidden" name="category_id" value="{{ $categoryId }}">@endif
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un client (nom, email)..." @input.debounce.400ms="$el.form.requestSubmit()" class="w-full max-w-sm border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <x-admin-filter-reset :route="route('admin.segments.show', $segment)" />
            <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
        </form>

        <div x-ref="results" :class="loading && 'opacity-50 pointer-events-none'" class="mt-4 transition-opacity">
            @include('admin.segments.partials.clients')
        </div>
    </div>
@endif

@endsection

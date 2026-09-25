@extends('layouts.admin')

@section('title', "Journal d'activité")

@section('content')

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Journal d'activité</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Actions importantes effectuées dans le back-office — visible uniquement par le Super Admin.</p>

<form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher..." class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white sm:w-64">
    <select name="module" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white sm:w-56">
        <option value="">Tous les modules</option>
        @foreach($modules as $module)
            <option value="{{ $module }}" @selected(request('module') === $module)>{{ ucfirst($module) }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-secondary-shade px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">Filtrer</button>
</form>

<div class="mt-8 divide-y divide-secondary-shade/10 border border-secondary-shade/10 bg-white dark:divide-white/10 dark:border-white/10 dark:bg-[#16201f]">
    @forelse($logs as $log)
        <div class="flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm text-secondary-shade dark:text-white">{{ $log->description }}</p>
                <p class="mt-0.5 text-xs text-grey dark:text-white/40">
                    <span class="uppercase tracking-[0.08em]">{{ $log->module }}</span> · {{ $log->created_at->format('d/m/Y à H:i') }}
                </p>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 px-6 py-14 text-center">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-clock-rotate-left"></i></span>
            <p class="text-sm text-grey dark:text-white/40">Aucune activité enregistrée pour ces critères.</p>
        </div>
    @endforelse
</div>

<div class="mt-6" data-pagination>{{ $logs->links() }}</div>

@endsection

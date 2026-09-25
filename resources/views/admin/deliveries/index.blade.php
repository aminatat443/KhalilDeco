@extends('layouts.admin')

@section('title', 'Zones et tarifs de livraison')

@section('content')

<a href="{{ route('admin.settings.edit') }}" class="text-xs text-grey hover:text-primary dark:text-white/40"><i class="fa-solid fa-arrow-left mr-1"></i>Configuration</a>

<div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Zones et tarifs de livraison</h1>
        <p class="mt-1 text-sm text-grey dark:text-white/40">Source unique des tarifs — modifiez un tarif ici, il s'applique immédiatement au panier, au checkout et aux nouvelles commandes.</p>
    </div>
    @can('create', App\Models\Delivery::class)
        <a href="{{ route('admin.deliveries.create') }}" class="block bg-primary px-6 py-3 text-center text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md sm:inline-block">
            Ajouter une zone
        </a>
    @endcan
</div>

@include('admin.deliveries.partials.table')

@endsection

@extends('layouts.admin')

@section('title', 'Gestion de boutique')

@section('content')

<div x-data="ajaxFilter({{ $period->key !== 'last_7_days' ? 'true' : 'false' }})">
    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="transition-opacity">
        @include('admin.dashboard.partials.store-manager-content')
    </div>
</div>

@endsection

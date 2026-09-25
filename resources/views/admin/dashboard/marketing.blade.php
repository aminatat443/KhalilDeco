@extends('layouts.admin')

@section('title', 'Tableau de bord marketing')

@section('content')

<div x-data="ajaxFilter({{ $period->key !== 'last_7_days' ? 'true' : 'false' }})">
    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="transition-opacity">
        @include('admin.dashboard.partials.marketing-content')
    </div>
</div>

@endsection

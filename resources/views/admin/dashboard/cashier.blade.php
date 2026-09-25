@extends('layouts.admin')

@section('title', 'Caisse')

@section('content')

<div x-data="ajaxFilter({{ $period->key !== 'last_7_days' ? 'true' : 'false' }})">
    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="transition-opacity">
        @include('admin.dashboard.partials.cashier-content')
    </div>
</div>

@endsection

@extends('layouts.admin')

@section('title', 'Tableau de bord financier')

@section('content')

<div x-data="ajaxFilter({{ $period->key !== 'last_7_days' ? 'true' : 'false' }})">
    <div x-ref="results" @click="onResultsClick($event)" :class="loading && 'opacity-50 pointer-events-none'" class="transition-opacity">
        @include('admin.dashboard.partials.accounting-content')
    </div>
</div>

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>
@endpush

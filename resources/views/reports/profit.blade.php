@extends('layouts.app') 

@section('title', 'Profit & Loss Report') 

@section('content')
<div class="flex justify-between items-center mb-5">
    <div>
        <h1 class="font-serif text-3xl text-ink">Profit & Loss Report</h1>
        <p class="text-sm text-slate-500">View overall business performance</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="document.getElementById('hidden-export').value='pdf'; document.getElementById('filter-form').submit(); document.getElementById('hidden-export').value='';" class="btn-white">
            <i class="ti ti-printer"></i> Print / PDF
        </button>
    </div>
</div>

@component('reports.filters')
    <input type="hidden" name="export" id="hidden-export" value="">
@endcomponent

@include('reports.profit-table')
@endsection

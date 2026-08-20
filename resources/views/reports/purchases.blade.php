@extends('layouts.app') 

@section('title', 'Purchase Report') 

@section('content')
<div class="flex justify-between items-center mb-5">
    <div>
        <h1 class="font-serif text-3xl text-ink">Purchase Report</h1>
        <p class="text-sm text-slate-500">View all inventory purchases</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="document.getElementById('hidden-export').value='pdf'; document.getElementById('filter-form').submit(); document.getElementById('hidden-export').value='';" class="btn-white">
            <i class="ti ti-printer"></i> Print / PDF
        </button>
    </div>
</div>

@component('reports.filters', ['ignore' => ['supplier_id']])
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Supplier</label>
        <select name="supplier_id" class="rounded-xl border-slate-200 py-2.5 px-3 w-48 bg-white" onchange="document.getElementById('filter-form').submit()">
            <option value="">All Suppliers</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <input type="hidden" name="export" id="hidden-export" value="">
@endcomponent

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        @include('reports.purchases-table')
    </div>
</div>
@endsection

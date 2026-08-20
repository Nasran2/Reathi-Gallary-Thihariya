@extends('layouts.app') 

@section('title', 'Stock Valuation Report') 

@section('content')
<div class="flex justify-between items-center mb-5">
    <div>
        <h1 class="font-serif text-3xl text-ink">Stock Report</h1>
        <p class="text-sm text-slate-500">View current inventory valuation based on average cost and selling price</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="document.getElementById('hidden-export').value='pdf'; document.getElementById('filter-form').submit(); document.getElementById('hidden-export').value='';" class="btn-white">
            <i class="ti ti-printer"></i> Print / PDF
        </button>
    </div>
</div>

@component('reports.filters', ['ignore' => ['type', 'category_id', 'base_unit_id']])
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Inventory Type</label>
        <select name="type" class="rounded-xl border-slate-200 py-2.5 px-3 w-48 bg-white" onchange="document.getElementById('filter-form').submit()">
            <option value="combined" {{ request('type') == 'combined' ? 'selected' : '' }}>Combined</option>
            <option value="main" {{ request('type') == 'main' ? 'selected' : '' }}>Main Only</option>
            <option value="remnant" {{ request('type') == 'remnant' ? 'selected' : '' }}>Remnant Only</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Category</label>
        <select name="category_id" class="rounded-xl border-slate-200 py-2.5 px-3 w-48 bg-white" onchange="document.getElementById('filter-form').submit()">
            <option value="">All Categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Base Unit</label>
        <select name="base_unit_id" class="rounded-xl border-slate-200 py-2.5 px-3 w-48 bg-white" onchange="document.getElementById('filter-form').submit()">
            <option value="">All Units</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" {{ request('base_unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->symbol }})</option>
            @endforeach
        </select>
    </div>
    <input type="hidden" name="export" id="hidden-export" value="">
    <div class="w-full text-xs text-slate-500 mt-2 italic">
        * Note: Stock valuation represents real-time data. Date filters do not affect stock totals.
    </div>
@endcomponent

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        @include('reports.valuation-table')
    </div>
</div>
@endsection

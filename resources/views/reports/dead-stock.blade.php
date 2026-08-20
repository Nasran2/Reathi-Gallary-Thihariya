@extends('layouts.app') 

@section('title', 'Dead Stock Report') 

@section('content')
<div class="flex justify-between items-center mb-5">
    <div>
        <h1 class="font-serif text-3xl text-ink">Dead Stock Report</h1>
        <p class="text-sm text-slate-500">View products that have not been sold recently</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="document.getElementById('hidden-export').value='pdf'; document.getElementById('filter-form').submit(); document.getElementById('hidden-export').value='';" class="btn-white">
            <i class="ti ti-printer"></i> Print / PDF
        </button>
    </div>
</div>

@component('reports.filters', ['ignore' => ['category_id', 'days']])
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Not Sold In (Days)</label>
        <input type="number" name="days" value="{{ request('days', 90) }}" class="rounded-xl border-slate-200 py-2.5 px-3 w-32" onchange="document.getElementById('filter-form').submit()">
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
    <input type="hidden" name="export" id="hidden-export" value="">
    
    <div class="w-full text-xs text-slate-500 mt-2 italic">
        * Note: Date range filters apply to the 'Not Sold In' calculation, but it is recommended to just use the Days input.
    </div>
@endcomponent

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        @include('reports.dead-stock-table')
    </div>
</div>
@endsection

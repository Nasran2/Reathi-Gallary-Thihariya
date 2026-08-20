@extends('layouts.app') 

@section('title', 'Sales Report') 

@section('content')
<div class="flex justify-between items-center mb-5">
    <div>
        <h1 class="font-serif text-3xl text-ink">Sales Report</h1>
        <p class="text-sm text-slate-500">View all service, job card, spare parts, and other sales</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="document.getElementById('hidden-export').value='pdf'; document.getElementById('filter-form').submit(); document.getElementById('hidden-export').value='';" class="btn-white">
            <i class="ti ti-printer"></i> Print / PDF
        </button>
        <!-- hidden field for export is injected via js or filters -->
    </div>
</div>

@component('reports.filters', ['ignore' => ['customer_id', 'type']])
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Customer</label>
        <select name="customer_id" class="rounded-xl border-slate-200 py-2.5 px-3 w-48 bg-white" onchange="document.getElementById('filter-form').submit()">
            <option value="">All Customers</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Type</label>
        <select name="type" class="rounded-xl border-slate-200 py-2.5 px-3 w-36 bg-white" onchange="document.getElementById('filter-form').submit()">
            <option value="">All Types</option>
            <option value="main" {{ request('type') == 'main' ? 'selected' : '' }}>Main</option>
            <option value="remnant" {{ request('type') == 'remnant' ? 'selected' : '' }}>Remnant</option>
        </select>
    </div>
    <input type="hidden" name="export" id="hidden-export" value="">
@endcomponent

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        @include('reports.sales-table')
    </div>
</div>
@endsection

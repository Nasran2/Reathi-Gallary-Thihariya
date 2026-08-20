@extends('layouts.app') 

@section('title', 'Due Bills Report') 

@section('content')
<div class="flex justify-between items-center mb-5">
    <div>
        <h1 class="font-serif text-3xl text-ink">Due Bills Report</h1>
        <p class="text-sm text-slate-500">View unpaid or partially paid sales invoices</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="document.getElementById('hidden-export').value='pdf'; document.getElementById('filter-form').submit(); document.getElementById('hidden-export').value='';" class="btn-white">
            <i class="ti ti-printer"></i> Print / PDF
        </button>
    </div>
</div>

@component('reports.filters', ['ignore' => ['customer_id']])
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Customer</label>
        <select name="customer_id" class="rounded-xl border-slate-200 py-2.5 px-3 w-48 bg-white" onchange="document.getElementById('filter-form').submit()">
            <option value="">All Customers</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
            @endforeach
        </select>
    </div>
    <input type="hidden" name="export" id="hidden-export" value="">
@endcomponent

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        @include('reports.due-bills-table')
    </div>
</div>
@endsection

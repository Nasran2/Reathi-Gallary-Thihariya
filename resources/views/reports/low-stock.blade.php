@extends('layouts.app')
@section('title', 'Low Stock Report')
@section('content')
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div><h1 class="font-serif text-3xl text-ink">Low Stock Report</h1><p class="text-sm text-slate-500">Products currently below their minimum stock or reorder level.</p></div>
    <div class="flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn-soft" target="_blank">Export PDF</a>
    </div>
</div>

<div class="card mb-6 p-4">
    <form class="flex flex-wrap items-end gap-4" method="GET" x-data x-ref="form" id="filterForm">
        @include('reports.filters', ['ignore' => ['from', 'to']])
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-sm text-slate-500">Supplier</label>
            <select name="supplier_id" class="w-full sm:w-48" @change="$refs.form.submit()">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-teal">Filter</button>
            <a href="{{ route('reports.low-stock') }}" class="btn-soft">Clear</a>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        @include('reports.low-stock-table')
    </div>
    @if(method_exists($products, 'links'))
    <div class="border-t border-slate-100 p-4">
        {{ $products->links() }}
    </div>
    @endif
</div>
@endsection

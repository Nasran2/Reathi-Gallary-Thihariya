@extends('layouts.app')
@section('title', 'Supplier Due Report')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div><h1 class="font-serif text-3xl text-ink">Supplier Due Report</h1><p class="text-sm text-slate-500">Current cleared outstanding balances, excluding payments still pending.</p></div><a class="btn-soft" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}">Export PDF</a></div>
<form class="card mb-5 flex flex-wrap items-end gap-3 p-4"><div><label class="mb-1 block text-sm font-semibold">Supplier</label><select name="supplier_id"><option value="">All suppliers</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div><button class="btn-primary">Filter</button><a class="btn-soft" href="{{ route('reports.supplier-due') }}">Reset</a></form>
<div class="card overflow-x-auto">@include('reports.supplier-due-table')</div>
@endsection

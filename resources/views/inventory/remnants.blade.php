@extends('layouts.app')
@section('title','Remnants')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="font-serif text-3xl text-ink">Remnant & cut-piece lots</h1><p class="text-sm text-slate-500">Search and analyse individually traceable irregular pieces.</p></div>
    @can('inventory.remnant_transfer')<a class="btn bg-amber-500 text-white" href="{{ route('remnants.transfer') }}">+ Transfer from main</a>@endcan
</div>
<form class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
    <input class="sm:col-span-2" name="q" value="{{ request('q') }}" placeholder="Remnant ID, product, SKU or barcode">
    <select name="product_id"><option value="">All products</option>@foreach($products as $item)<option value="{{ $item->id }}" @selected(request('product_id') == $item->id)>{{ $item->name }}</option>@endforeach</select>
    <select name="category_id"><option value="">All categories</option>@foreach($categories as $item)<option value="{{ $item->id }}" @selected(request('category_id') == $item->id)>{{ $item->name }}</option>@endforeach</select>
    <select name="status"><option value="">All statuses</option>@foreach(['available' => 'Available', 'partially_sold' => 'Partially sold', 'sold' => 'Sold'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}" title="Transferred from">
    <input type="date" name="to" value="{{ request('to') }}" title="Transferred to">
    <input type="number" step="0.000001" min="0" name="min_qty" value="{{ request('min_qty') }}" placeholder="Minimum remaining qty">
    <input type="number" step="0.000001" min="0" name="max_qty" value="{{ request('max_qty') }}" placeholder="Maximum remaining qty">
    <input type="number" step="0.0001" min="0" name="min_price" value="{{ request('min_price') }}" placeholder="Minimum remnant price">
    <input type="number" step="0.0001" min="0" name="max_price" value="{{ request('max_price') }}" placeholder="Maximum remnant price">
    @if($canViewCost)<label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3"><input type="checkbox" name="below_cost" value="1" @checked(request()->boolean('below_cost'))> Below cost only</label>@endif
    <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-4 xl:col-span-6"><button class="btn-primary">Apply filters</button><a class="btn-soft" href="{{ route('remnants.index') }}">Reset</a><button class="btn-soft" name="export" value="csv">Export Excel / CSV</button></div>
</form>
<div class="card overflow-x-auto"><table class="min-w-[1150px]"><thead><tr><th>Remnant</th><th>Product</th><th>Original</th><th>Remaining</th><th>Unit</th><th>Normal price</th><th>Remnant price</th>@if($canViewCost)<th>Cost / base</th><th>Potential P/L</th>@endif<th>Status</th><th>Transfer date</th></tr></thead><tbody>
@forelse($remnants as $remnant)
@php $potential = (float)$remnant->remnant_price - (float)$remnant->cost_per_base_unit; @endphp
<tr><td><div class="font-semibold text-amber-700">{{ $remnant->remnant_no }}</div><div class="text-xs text-slate-400">{{ $remnant->barcode }}</div></td><td><div class="font-semibold">{{ $remnant->product->name }}</div><div class="text-xs text-slate-400">{{ $remnant->product->sku }}</div></td><td>{{ $remnant->original_quantity + 0 }}</td><td class="font-semibold">{{ $remnant->remaining_quantity + 0 }}</td><td>{{ $remnant->unit->symbol }}</td><td>Rs. {{ number_format((float)$remnant->normal_price,2) }}</td><td>Rs. {{ number_format((float)$remnant->remnant_price,2) }}</td>@if($canViewCost)<td>Rs. {{ number_format((float)$remnant->cost_per_base_unit,4) }}</td><td class="font-semibold {{ $potential < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $potential < 0 ? 'Loss' : 'Profit' }} Rs. {{ number_format(abs($potential),2) }}</td>@endif<td><span class="badge bg-amber-50 text-amber-700">{{ str($remnant->status)->replace('_',' ')->title() }}</span></td><td>{{ $remnant->created_at->format('d M Y') }}</td></tr>
@empty<tr><td colspan="{{ $canViewCost ? 11 : 9 }}" class="py-12 text-center text-slate-400">No remnants match these filters.</td></tr>@endforelse
</tbody></table></div><div class="mt-5">{{ $remnants->links() }}</div>
@endsection

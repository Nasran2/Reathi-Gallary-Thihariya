@extends('layouts.app')
@section('title', ucfirst($type).' Inventory')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="font-serif text-3xl text-ink">{{ $type === 'remnant' ? 'Remnant Inventory' : 'Main Inventory' }}</h1><p class="text-sm text-slate-500">Search, filter, value and export traceable stock balances.</p></div>
    <div class="flex flex-wrap gap-2">
        @can('inventory.adjust')<a class="btn-soft" href="{{ route('inventory.adjust') }}">Stock adjustment</a>@endcan
        @can('inventory.remnant_transfer')<a class="btn bg-amber-100 text-amber-800" href="{{ route('remnants.transfer') }}">Transfer to remnant</a>@endcan
    </div>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    <a class="btn {{ $type === 'main' ? 'bg-ink text-white' : 'bg-white' }}" href="{{ route('inventory.index', ['type' => 'main']) }}">Main</a>
    <a class="btn {{ $type === 'remnant' ? 'bg-amber-500 text-white' : 'bg-white' }}" href="{{ route('inventory.index', ['type' => 'remnant']) }}">Remnant balances</a>
    <a class="btn-soft" href="{{ route('remnants.index') }}">Cut-piece lots</a>
    <a class="btn-soft" href="{{ route('inventory.movements') }}">Stock ledger</a>
</div>

<form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
    <input type="hidden" name="type" value="{{ $type }}">
    <input class="sm:col-span-2" name="q" value="{{ request('q') }}" placeholder="Name, SKU, barcode, category, brand or supplier">
    <select name="category_id"><option value="">All categories</option>@foreach($categories as $item)<option value="{{ $item->id }}" @selected(request('category_id') == $item->id)>{{ $item->name }}</option>@endforeach</select>
    <select name="brand_id"><option value="">All brands</option>@foreach($brands as $item)<option value="{{ $item->id }}" @selected(request('brand_id') == $item->id)>{{ $item->name }}</option>@endforeach</select>
    <select name="supplier_id"><option value="">All suppliers</option>@foreach($suppliers as $item)<option value="{{ $item->id }}" @selected(request('supplier_id') == $item->id)>{{ $item->name }}</option>@endforeach</select>
    <select name="unit_id"><option value="">All base units</option>@foreach($units as $item)<option value="{{ $item->id }}" @selected(request('unit_id') == $item->id)>{{ $item->name }} ({{ $item->symbol }})</option>@endforeach</select>
    <select name="stock_status"><option value="">All stock statuses</option>@foreach(['in_stock' => 'In stock', 'low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock', 'negative' => 'Negative stock'] as $value => $label)<option value="{{ $value }}" @selected(request('stock_status') === $value)>{{ $label }}</option>@endforeach</select>
    <select name="product_status"><option value="">Active & inactive</option><option value="active" @selected(request('product_status') === 'active')>Active products</option><option value="inactive" @selected(request('product_status') === 'inactive')>Inactive products</option></select>
    <input type="number" step="0.000001" name="min_qty" value="{{ request('min_qty') }}" placeholder="Minimum quantity">
    <input type="number" step="0.000001" name="max_qty" value="{{ request('max_qty') }}" placeholder="Maximum quantity">
    @if($canViewCost)
        <input type="number" step="0.0001" min="0" name="min_cost" value="{{ request('min_cost') }}" placeholder="Minimum cost">
        <input type="number" step="0.0001" min="0" name="max_cost" value="{{ request('max_cost') }}" placeholder="Maximum cost">
    @endif
    <input type="number" step="0.0001" min="0" name="min_price" value="{{ request('min_price') }}" placeholder="Minimum selling price">
    <input type="number" step="0.0001" min="0" name="max_price" value="{{ request('max_price') }}" placeholder="Maximum selling price">
    <select name="last_sale"><option value="">Any last sale</option>@foreach(['30_days' => 'Last 30 days', '3_months' => 'Last 3 months', '6_months' => 'Last 6 months', '12_months' => 'Last 12 months', 'never' => 'Never sold'] as $value => $label)<option value="{{ $value }}" @selected(request('last_sale') === $value)>{{ $label }}</option>@endforeach</select>
    <select name="sort"><option value="product" @selected(request('sort', 'product') === 'product')>Sort: Product</option><option value="sku" @selected(request('sort') === 'sku')>Sort: SKU</option><option value="quantity" @selected(request('sort') === 'quantity')>Sort: Quantity</option>@if($canViewCost)<option value="average_cost" @selected(request('sort') === 'average_cost')>Sort: Cost</option>@endif<option value="main_selling_price" @selected(request('sort') === 'main_selling_price')>Sort: Price</option></select>
    <select name="direction"><option value="asc" @selected(request('direction') !== 'desc')>Ascending</option><option value="desc" @selected(request('direction') === 'desc')>Descending</option></select>
    <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-4 xl:col-span-6">
        <button class="btn-primary">Apply filters</button>
        <a class="btn-soft" href="{{ route('inventory.index', ['type' => $type]) }}">Reset</a>
        <button name="export" value="csv" class="btn-soft">Export Excel / CSV</button>
        <button name="export" value="pdf" class="btn-soft">Export PDF</button>
    </div>
</form>

<div class="card overflow-x-auto">
    <table class="min-w-[1200px]">
        <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Base unit</th><th>Stock</th>@if($canViewCost)<th>Average cost</th><th>Stock cost value</th>@endif<th>Selling price</th><th>Selling value</th><th>Minimum</th><th>Status</th><th>Last sale</th><th></th></tr></thead>
        <tbody>
        @forelse($balances as $balance)
            @php $product = $balance->product; $status = $balance->quantity < 0 ? 'Negative' : ($balance->quantity == 0 ? 'Out of stock' : ($balance->quantity <= $product->reorder_level ? 'Low stock' : 'In stock')); @endphp
            <tr>
                <td class="font-semibold">{{ $product->name }}</td><td>{{ $product->sku }}</td><td>{{ $product->category?->name ?? '—' }}</td><td>{{ $product->baseUnit?->symbol }}</td>
                <td>{{ number_format((float) $balance->quantity, 4) }}</td>
                @if($canViewCost)<td>Rs. {{ number_format((float) $product->average_cost, 4) }}</td><td>Rs. {{ number_format((float) $balance->quantity * (float) $product->average_cost, 2) }}</td>@endif
                <td>Rs. {{ number_format((float) $product->main_selling_price, 2) }}</td><td>Rs. {{ number_format((float) $balance->quantity * (float) $product->main_selling_price, 2) }}</td>
                <td>{{ number_format((float) $product->minimum_stock, 3) }}</td>
                <td><span class="badge {{ in_array($status, ['Negative', 'Out of stock']) ? 'bg-red-50 text-red-700' : ($status === 'Low stock' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}">{{ $status }}</span></td>
                <td>{{ $product->last_sale_at ? \Illuminate\Support\Carbon::parse($product->last_sale_at)->format('d M Y') : 'Never' }}</td>
                <td><a class="text-sm font-semibold text-teal" href="{{ route('products.show', $product) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="{{ $canViewCost ? 13 : 11 }}" class="py-12 text-center text-slate-400">No balances match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $balances->links() }}</div>
@endsection

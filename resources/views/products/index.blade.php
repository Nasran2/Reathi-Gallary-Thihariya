@extends('layouts.app')
@section('title', 'Products')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="font-serif text-3xl text-ink">Products</h1>
        <p class="text-sm text-slate-500">Units, conversion rates and separate prices in one catalogue.</p>
    </div>
    @can('products.create')
        <a href="{{ route('products.create') }}" class="btn-teal">+ Add product</a>
    @endcan
</div>

<form class="mb-5">
    <input class="w-full max-w-md" name="q" value="{{ request('q') }}" placeholder="Search name, SKU or barcode…">
</form>

<div class="card overflow-x-auto">
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Units & prices</th>
                <th>Main stock</th>
                <th>Remnant</th>
                <th>Avg cost</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $p)
                <tr>
                    <td>
                        <a href="{{ route('products.show', $p) }}" class="font-semibold text-teal hover:underline">{{ $p->name }}</a>
                        <div class="text-xs text-slate-400">{{ $p->sku }} · {{ $p->colour }}</div>
                    </td>
                    <td>{{ $p->category?->name ?? '—' }}</td>
                    <td>
                        @foreach($p->productUnits as $pu)
                            <span class="mr-1 inline-block rounded-lg bg-slate-100 px-2 py-1 text-xs">
                                {{ $pu->unit->symbol }} Rs.{{ number_format($p->main_selling_price * $pu->conversion_rate, 2) }}
                            </span>
                        @endforeach
                    </td>
                    <td>{{ number_format($p->balances->where('inventory_type', 'main')->sum('quantity'), 3) }} {{ $p->baseUnit->symbol }}</td>
                    <td>{{ number_format($p->balances->where('inventory_type', 'remnant')->sum('quantity'), 3) }} {{ $p->baseUnit->symbol }}</td>
                    <td>
                        @can('products.view_cost')
                            Rs. {{ number_format($p->average_cost, 4) }}
                        @else
                            <span class="text-slate-300">Restricted</span>
                        @endcan
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                            <a class="btn-soft !px-3 !py-1.5" href="{{ route('products.show', $p) }}">View</a>
                            @can('products.edit')
                                <a class="btn-soft !px-3 !py-1.5 text-teal" href="{{ route('products.edit', $p) }}">Edit</a>
                            @endcan
                            @can('products.delete')
                                <form method="POST" action="{{ route('products.destroy', $p) }}" data-confirm="Delete this product? This cannot be undone.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn !bg-red-50 !px-3 !py-1.5 text-red-700 hover:!bg-red-100">Delete</button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-400">No products yet. Add your first fabric or item.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $products->links() }}</div>
@endsection

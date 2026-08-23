@extends('layouts.app') 
@section('title','Adjustment #'.$adjustment->id) 
@section('content')

<div class="mx-auto max-w-5xl">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <a class="text-sm text-slate-500 hover:text-teal-600 transition" href="{{ route('inventory.adjustments.index') }}">← Adjustments History</a>
            <h1 class="mt-2 font-serif text-3xl text-ink">Adjustment #{{ $adjustment->id }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                Posted by {{ $adjustment->user->name ?? 'System' }} on {{ $adjustment->created_at->format('d M Y, H:i') }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.adjustments.edit', $adjustment) }}" class="btn bg-amber-100 text-amber-800 shrink-0">Edit</a>
            <form action="{{ route('inventory.adjustments.destroy', $adjustment) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this adjustment? This will rollback all associated stock changes.');">
                @csrf @method('DELETE')
                <button type="submit" class="btn bg-red-100 text-red-800">Delete</button>
            </form>
        </div>
    </div>

    @if($adjustment->notes)
    <div class="bg-blue-50 border border-blue-100 text-blue-800 p-4 rounded-xl mb-6">
        <h4 class="font-medium text-sm mb-1 text-blue-900">Transaction Notes</h4>
        <p class="text-sm">{{ $adjustment->notes }}</p>
    </div>
    @endif

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <th class="px-4 py-3 font-medium">Product</th>
                    <th class="px-4 py-3 font-medium">Store & Inventory</th>
                    <th class="px-4 py-3 font-medium">Movement</th>
                    <th class="px-4 py-3 font-medium">Reason</th>
                    <th class="px-4 py-3 font-medium">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustment->items as $item)
                <tr class="border-b border-slate-100 last:border-0">
                    <td class="px-4 py-3 align-top">
                        <div class="font-medium text-ink">{{ $item->product->name }}</div>
                        <div class="text-xs text-slate-500">SKU: {{ $item->product->sku }}</div>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div class="font-medium">{{ $item->store->name }}</div>
                        <div class="text-xs text-slate-500 capitalize">{{ $item->inventory_type }} inventory</div>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div class="font-medium {{ $item->direction === 'increase' ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $item->direction === 'increase' ? '+' : '-' }}{{ (float) $item->quantity }} {{ $item->unit->symbol }}
                        </div>
                    </td>
                    <td class="px-4 py-3 align-top">{{ $item->reason }}</td>
                    <td class="px-4 py-3 align-top text-slate-500 max-w-xs truncate" title="{{ $item->notes }}">{{ $item->notes ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

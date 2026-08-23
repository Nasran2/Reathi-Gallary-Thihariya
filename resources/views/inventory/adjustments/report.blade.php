@extends('layouts.app') 
@section('title','Stock Adjustments Report') 
@section('content')

<div class="mx-auto max-w-5xl">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-3xl text-ink">Adjustments Report</h1>
            <p class="text-sm text-slate-500 mt-1">Detailed item-level report of all stock adjustments.</p>
        </div>
        <a href="{{ route('inventory.adjustments.index') }}" class="btn bg-white border border-slate-200 text-slate-700 shrink-0">
            View History
        </a>
    </div>

    <form method="GET" class="card p-4 mb-6 grid grid-cols-1 sm:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-sm border-slate-200 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">End Date</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-sm border-slate-200 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Store</label>
            <select name="store_id" class="w-full text-sm border-slate-200 rounded-lg">
                <option value="">All Stores</option>
                @foreach($stores as $store)
                <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">User</label>
            <select name="user_id" class="w-full text-sm border-slate-200 rounded-lg">
                <option value="">All Users</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn bg-slate-100 text-slate-700 hover:bg-slate-200 flex-1">Filter</button>
            <a href="{{ route('inventory.adjustments.report') }}" class="btn bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 shrink-0">Clear</a>
        </div>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <th class="px-4 py-3 font-medium">Date & ID</th>
                    <th class="px-4 py-3 font-medium">Product</th>
                    <th class="px-4 py-3 font-medium">Store & Inventory</th>
                    <th class="px-4 py-3 font-medium">Movement</th>
                    <th class="px-4 py-3 font-medium">Reason</th>
                    <th class="px-4 py-3 font-medium">User</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50">
                    <td class="px-4 py-3 align-top">
                        <div class="font-medium text-ink">{{ $item->adjustment->created_at->format('d M Y, H:i') }}</div>
                        <a href="{{ route('inventory.adjustments.show', $item->adjustment_id) }}" class="text-xs text-teal hover:underline">Adj #{{ $item->adjustment_id }}</a>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div class="font-medium text-ink">{{ $item->product->name }}</div>
                        <div class="text-xs text-slate-500">SKU: {{ $item->product->sku }}</div>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div class="font-medium">{{ $item->store->name }}</div>
                        <div class="text-xs text-slate-500 capitalize">{{ $item->inventory_type }}</div>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div class="font-medium {{ $item->direction === 'increase' ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $item->direction === 'increase' ? '+' : '-' }}{{ (float) $item->quantity }} {{ $item->unit->symbol }}
                        </div>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div>{{ $item->reason }}</div>
                        <div class="text-xs text-slate-500 truncate max-w-[150px]" title="{{ $item->notes }}">{{ $item->notes }}</div>
                    </td>
                    <td class="px-4 py-3 align-top">{{ $item->adjustment->user->name ?? 'System' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                        No adjustment items found matching the criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $items->links() }}
    </div>
</div>

@endsection

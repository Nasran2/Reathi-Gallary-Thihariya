@extends('layouts.app') 
@section('title','Stock Adjustments History') 
@section('content')

<div class="mx-auto max-w-5xl">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-3xl text-ink">Stock Adjustments</h1>
            <p class="text-sm text-slate-500 mt-1">History of stock adjustments and their items.</p>
        </div>
        <a href="{{ route('inventory.adjust') }}" class="btn-teal shrink-0">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            New Adjustment
        </a>
    </div>

    <form method="GET" class="card p-4 mb-6 grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-sm border-slate-200 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">End Date</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-sm border-slate-200 rounded-lg">
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
            <a href="{{ route('inventory.adjustments.index') }}" class="btn bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 shrink-0">Clear</a>
        </div>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <th class="px-4 py-3 font-medium">ID</th>
                    <th class="px-4 py-3 font-medium">Date</th>
                    <th class="px-4 py-3 font-medium">User</th>
                    <th class="px-4 py-3 font-medium">Items</th>
                    <th class="px-4 py-3 font-medium max-w-[200px]">Notes</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($adjustments as $adj)
                <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50">
                    <td class="px-4 py-3 font-medium text-ink">#{{ $adj->id }}</td>
                    <td class="px-4 py-3">{{ $adj->created_at->format('d M Y, H:i') }}</td>
                    <td class="px-4 py-3">{{ $adj->user->name ?? 'System' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                            {{ $adj->items_count }} products
                        </span>
                    </td>
                    <td class="px-4 py-3 truncate max-w-[200px] text-slate-500" title="{{ $adj->notes }}">{{ $adj->notes ?: '-' }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('inventory.adjustments.show', $adj) }}" class="text-teal hover:text-teal-600 font-medium">View</a>
                            <a href="{{ route('inventory.adjustments.edit', $adj) }}" class="text-amber-600 hover:text-amber-700 font-medium">Edit</a>
                            <form action="{{ route('inventory.adjustments.destroy', $adj) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this adjustment? This will rollback all associated stock changes.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-600 font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                        No stock adjustments found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $adjustments->links() }}
    </div>
</div>

@endsection

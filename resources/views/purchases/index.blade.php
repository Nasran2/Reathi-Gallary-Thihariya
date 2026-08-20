@extends('layouts.app') @section('title','Purchases') @section('content')
<div class="mb-6 flex items-center justify-between"><div><h1 class="font-serif text-3xl text-ink">Purchases</h1><p class="text-sm text-slate-500">Only received purchases change stock.</p></div>
    <div class="flex gap-2">
        <a class="btn-soft" href="{{ route('purchases.returns.create') }}">Purchase Returns / Exchange</a>
        @can('purchases.create')<a class="btn-teal" href="{{ route('purchases.create') }}">+ Receive purchase</a>@endcan
    </div>
</div>

<div class="mb-5 flex flex-wrap items-center gap-3">
    <!-- Supplier Filter -->
    <form id="filterForm" method="GET" class="flex items-center">
        @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
        @if(request('start')) <input type="hidden" name="start" value="{{ request('start') }}"> @endif
        @if(request('end')) <input type="hidden" name="end" value="{{ request('end') }}"> @endif
        
        <select name="supplier_id" class="text-sm border-slate-200 rounded-md py-1.5 pl-3 pr-8 bg-white" onchange="this.form.submit()">
            <option value="">All Suppliers</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="w-px h-6 bg-slate-200 mx-1"></div> <!-- Divider -->

    @php
        $filters = [
            '' => 'All Time', 'today' => 'Today', 'yesterday' => 'Yesterday', 'this_week' => 'This Week',
            'last_week' => 'Last Week', 'this_month' => 'This Month', 'last_month' => 'Last Month',
            'this_year' => 'This Year', 'custom' => 'Custom'
        ];
        $current = request('filter', '');
        $supplierParam = request('supplier_id') ? ['supplier_id' => request('supplier_id')] : [];
    @endphp
    @foreach($filters as $key => $label)
        <a href="{{ route('purchases.index', array_merge(['filter' => $key], $supplierParam)) }}" class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors {{ $current === $key ? 'bg-teal-50 border-teal-200 text-teal-800' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            {{ $label }}
        </a>
    @endforeach

    @if($current === 'custom')
    <form class="ml-2 flex items-center gap-2 bg-white p-1 rounded-lg border border-slate-200" method="GET">
        <input type="hidden" name="filter" value="custom">
        @if(request('supplier_id')) <input type="hidden" name="supplier_id" value="{{ request('supplier_id') }}"> @endif
        <input type="date" name="start" value="{{ request('start') }}" class="text-sm border-0 focus:ring-0" required>
        <span class="text-slate-400 text-sm">to</span>
        <input type="date" name="end" value="{{ request('end') }}" class="text-sm border-0 focus:ring-0" required>
        <button type="submit" class="btn-soft text-xs py-1.5">Apply</button>
    </form>
    @endif
</div>

<div class="card overflow-x-auto"><table><thead><tr><th>Purchase</th><th>Date</th><th>Supplier</th><th>Supplier invoice</th><th>Invoice cost</th><th>Due</th><th>Status</th><th class="text-right">Actions</th></tr></thead><tbody>@forelse($purchases as $p)<tr><td><a class="font-semibold text-teal" href="{{ route('purchases.show',$p) }}">{{ $p->purchase_no }}</a></td><td>{{ $p->purchase_date->format('d M Y') }}</td><td>{{ $p->supplier->name }}</td><td>{{ $p->supplier_invoice_no??'—' }}</td><td>Rs. {{ number_format($p->supplier_total,2) }}</td><td>Rs. {{ number_format($p->due_total,2) }}</td><td><span class="badge bg-emerald-50 text-emerald-700">{{ ucfirst($p->status) }}</span></td><td class="text-right space-x-3"><a class="text-sm text-slate-500 hover:text-teal-600" href="{{ route('purchases.show', $p) }}">View</a>@can('purchases.cancel')<form action="{{ route('purchases.destroy', $p) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this purchase? This will reverse stock and ledger entries. Purchases with payments cannot be deleted.')">@csrf @method('DELETE')<button type="submit" class="text-sm text-red-500 hover:text-red-700">Delete</button></form>@endcan</td></tr>@empty<tr><td colspan="8" class="py-12 text-center text-slate-400">No purchases recorded.</td></tr>@endforelse</tbody></table></div><div class="mt-5">{{ $purchases->links() }}</div>@endsection

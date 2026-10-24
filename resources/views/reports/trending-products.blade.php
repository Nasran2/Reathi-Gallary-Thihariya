@extends('layouts.app') 

@section('title', 'Trending Sales Products') 

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="font-serif text-3xl text-ink">Trending Sales Products</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                🔥 Selling Trends
            </span>
        </div>
        <p class="text-sm text-slate-500 mt-1">Identify best-selling products, sales velocity, and product movement trends</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="document.getElementById('hidden-export').value='pdf'; document.getElementById('filter-form').submit(); document.getElementById('hidden-export').value='';" class="btn-white">
            <i class="ti ti-printer"></i> Print / PDF
        </button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
    <form method="GET" id="filter-form" class="space-y-4">
        <input type="hidden" name="export" id="hidden-export" value="">

        {{-- Quick Date Range Buttons --}}
        <div class="flex flex-wrap items-center gap-2 pb-4 border-b border-slate-100">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mr-2">Quick Periods:</span>
            @php
                $todayStr = now()->format('Y-m-d');
                $weekStart = now()->startOfWeek()->format('Y-m-d');
                $weekEnd = now()->endOfWeek()->format('Y-m-d');
                $monthStart = now()->startOfMonth()->format('Y-m-d');
                $monthEnd = now()->endOfMonth()->format('Y-m-d');
                $d30Start = now()->subDays(30)->format('Y-m-d');
                $d90Start = now()->subDays(90)->format('Y-m-d');
                
                $fromStr = $from ? $from->format('Y-m-d') : '';
                $toStr = $to ? $to->format('Y-m-d') : '';
            @endphp
            <a href="{{ route('reports.trending', array_merge(request()->except(['from', 'to']), ['from' => $todayStr, 'to' => $todayStr])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ ($fromStr === $todayStr && $toStr === $todayStr) ? 'bg-teal text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Today
            </a>
            <a href="{{ route('reports.trending', array_merge(request()->except(['from', 'to']), ['from' => $weekStart, 'to' => $weekEnd])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ ($fromStr === $weekStart && $toStr === $weekEnd) ? 'bg-teal text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                This Week
            </a>
            <a href="{{ route('reports.trending', array_merge(request()->except(['from', 'to']), ['from' => $monthStart, 'to' => $monthEnd])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ ($fromStr === $monthStart && $toStr === $monthEnd) ? 'bg-teal text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                This Month
            </a>
            <a href="{{ route('reports.trending', array_merge(request()->except(['from', 'to']), ['from' => $d30Start, 'to' => $todayStr])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ ($fromStr === $d30Start && $toStr === $todayStr) ? 'bg-teal text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Last 30 Days
            </a>
            <a href="{{ route('reports.trending', array_merge(request()->except(['from', 'to']), ['from' => $d90Start, 'to' => $todayStr])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ ($fromStr === $d90Start && $toStr === $todayStr) ? 'bg-teal text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Last 90 Days
            </a>
            <a href="{{ route('reports.trending', array_merge(request()->except(['from', 'to']), ['from' => '', 'to' => ''])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ (empty($fromStr) && empty($toStr)) ? 'bg-teal text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All Time
            </a>
        </div>

        {{-- Filter Inputs Row --}}
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">From Date</label>
                <input type="date" name="from" value="{{ $fromStr }}" class="rounded-xl border-slate-200 py-2 px-3 text-sm w-36">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">To Date</label>
                <input type="date" name="to" value="{{ $toStr }}" class="rounded-xl border-slate-200 py-2 px-3 text-sm w-36">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Selling Trend Metric</label>
                <select name="sort_by" class="rounded-xl border-slate-200 py-2 px-3 text-sm bg-white min-w-[170px]">
                    <option value="quantity" {{ ($sortBy ?? 'quantity') === 'quantity' ? 'selected' : '' }}>🔥 Most Sold (Quantity)</option>
                    <option value="revenue" {{ ($sortBy ?? '') === 'revenue' ? 'selected' : '' }}>💰 Highest Revenue (Sales)</option>
                    <option value="profit" {{ ($sortBy ?? '') === 'profit' ? 'selected' : '' }}>📈 Highest Profit Generated</option>
                    <option value="orders" {{ ($sortBy ?? '') === 'orders' ? 'selected' : '' }}>🛒 Most Frequent (Orders)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Category</label>
                <select name="category_id" class="rounded-xl border-slate-200 py-2 px-3 text-sm bg-white min-w-[150px]">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Display Limit</label>
                <select name="limit" class="rounded-xl border-slate-200 py-2 px-3 text-sm bg-white w-28">
                    <option value="10" {{ ($limit ?? '') == '10' ? 'selected' : '' }}>Top 10</option>
                    <option value="25" {{ ($limit ?? '') == '25' ? 'selected' : '' }}>Top 25</option>
                    <option value="50" {{ ($limit ?? '50') == '50' ? 'selected' : '' }}>Top 50</option>
                    <option value="all" {{ ($limit ?? '') == 'all' ? 'selected' : '' }}>All Items</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Search Product</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, SKU, Barcode..." class="rounded-xl border-slate-200 py-2 px-3 text-sm w-44">
            </div>

            <button type="submit" class="bg-teal text-white font-bold px-5 py-2 text-sm rounded-xl shadow-xs hover:bg-teal-700 transition">
                Apply Filter
            </button>
            <a href="{{ route('reports.trending') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 py-2 px-2">
                Reset
            </a>
        </div>
    </form>
</div>

<div class="card overflow-hidden p-6">
    @include('reports.trending-products-table')
</div>
@endsection

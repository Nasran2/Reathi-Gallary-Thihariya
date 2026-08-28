@extends('layouts.app') 

@section('title', 'Daily Ledger Report') 

@section('content')
<div class="flex justify-between items-center mb-5">
    <div>
        <h1 class="font-serif text-3xl text-ink">Daily Ledger Report</h1>
        <p class="text-sm text-slate-500">Chronological ledger of all incoming and outgoing transactions</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="document.getElementById('hidden-export').value='pdf'; document.getElementById('filter-form').submit(); document.getElementById('hidden-export').value='';" class="btn-white">
            <i class="ti ti-printer"></i> Print / PDF
        </button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
    <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">From Date</label>
            <input type="date" name="from" value="{{ $from ? $from->format('Y-m-d') : '' }}" class="rounded-xl border-slate-200 py-2.5 px-3 w-40">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">To Date</label>
            <input type="date" name="to" value="{{ $to ? $to->format('Y-m-d') : '' }}" class="rounded-xl border-slate-200 py-2.5 px-3 w-40">
        </div>
        
        <input type="hidden" name="export" id="hidden-export" value="">
        
        <button type="submit" class="bg-teal text-white font-bold px-6 py-2.5 rounded-xl shadow-sm hover:bg-teal-700 transition">Filter</button>
        <div class="flex rounded-xl bg-slate-100 p-1">
            <a href="{{ route('reports.ledger', ['from' => now()->format('Y-m-d'), 'to' => now()->format('Y-m-d')]) }}" class="px-4 py-1.5 text-sm font-medium rounded-lg {{ ($from && $from->isToday() && $to && $to->isToday()) ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700' }}">Today</a>
            <a href="{{ route('reports.ledger', ['from' => now()->startOfWeek()->format('Y-m-d'), 'to' => now()->endOfWeek()->format('Y-m-d')]) }}" class="px-4 py-1.5 text-sm font-medium rounded-lg {{ ($from && $from->isStartOfWeek() && $to && $to->isEndOfWeek()) ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700' }}">This Week</a>
            <a href="{{ route('reports.ledger', ['from' => now()->startOfMonth()->format('Y-m-d'), 'to' => now()->endOfMonth()->format('Y-m-d')]) }}" class="px-4 py-1.5 text-sm font-medium rounded-lg {{ ($from && $from->isStartOfMonth() && $to && $to->isEndOfMonth()) ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700' }}">This Month</a>
        </div>
        <a href="{{ route('reports.ledger') }}" class="text-sm font-medium text-slate-500 hover:text-ink ml-auto">Clear Filters</a>
    </form>
</div>

<div class="card overflow-hidden p-6">
    @include('reports.ledger-table')
</div>
@endsection

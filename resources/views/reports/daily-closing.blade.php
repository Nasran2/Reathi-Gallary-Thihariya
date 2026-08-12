@extends('layouts.app') 

@section('title', 'Daily Closing Report') 

@section('content')
<div class="flex justify-between items-center mb-5">
    <div>
        <h1 class="font-serif text-3xl text-ink">Daily Closing Report</h1>
        <p class="text-sm text-slate-500">Summary of the day's cash flow, sales, purchases, and expenses</p>
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
            <label class="block text-sm font-semibold text-slate-700 mb-1">Select Date</label>
            <input type="date" name="from" value="{{ $date->format('Y-m-d') }}" class="rounded-xl border-slate-200 py-2.5 px-3 w-48">
            <input type="hidden" name="to" value="{{ $date->format('Y-m-d') }}">
        </div>
        
        <input type="hidden" name="export" id="hidden-export" value="">
        
        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold px-6 py-2.5 rounded-xl shadow-sm transition">View Closing</button>
        <a href="{{ route('reports.daily-closing') }}" class="bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold px-6 py-2.5 rounded-xl transition">Today</a>
    </form>
</div>

<div class="card overflow-hidden p-6">
    @include('reports.daily-closing-table')
</div>
@endsection

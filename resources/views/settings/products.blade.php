@extends('layouts.app') 
@section('title', 'Product Settings') 
@section('content')

<div class="mb-6">
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-600 transition">Dashboard</a> 
        <span>/</span>
        <span>Settings</span>
        <span>/</span>
        <span class="text-slate-600">Product Settings</span>
    </div>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-serif text-3xl text-ink">Product Settings</h1>
            <p class="text-sm text-slate-500 mt-1">Manage units of measurement and product categories.</p>
        </div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-blue-50 text-blue-500">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-lg">Units</h2>
                <p class="text-xs text-slate-500 mt-0.5">Measurement units for products</p>
            </div>
        </div>
        
        <div class="p-6">
            <form method="post" action="{{ route('settings.units.store') }}" class="flex flex-wrap items-end gap-3 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                @csrf
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Name</label>
                    <input class="w-full rounded-lg border-slate-200 py-2" name="name" placeholder="Yard" required>
                </div>
                <div class="w-24">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Symbol</label>
                    <input class="w-full rounded-lg border-slate-200 py-2" name="symbol" placeholder="yd" required>
                </div>
                <label class="flex items-center gap-2 normal-case py-2 cursor-pointer">
                    <input type="checkbox" name="allows_decimal" value="1" checked class="rounded text-blue-600 focus:ring-blue-500"> 
                    <span class="text-sm font-semibold text-slate-700">Decimal</span>
                </label>
                <button class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 h-[42px]">Add</button>
            </form>
            
            <div class="flex flex-wrap gap-2">
                @foreach($units as $u)
                <span class="rounded-lg bg-white border border-slate-200 shadow-sm px-3 py-2 text-sm flex items-center gap-2">
                    {{ $u->name }} 
                    <strong class="bg-slate-100 text-slate-600 px-1.5 rounded text-xs">{{ $u->symbol }}</strong>
                </span>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-blue-50 text-blue-500">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-lg">Categories</h2>
                <p class="text-xs text-slate-500 mt-0.5">Product classifications</p>
            </div>
        </div>
        
        <div class="p-6">
            <form method="post" action="{{ route('settings.categories.store') }}" class="flex flex-wrap items-end gap-3 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                @csrf
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Name</label>
                    <input class="w-full rounded-lg border-slate-200 py-2" name="name" placeholder="Cotton fabrics" required>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Parent</label>
                    <select class="w-full rounded-lg border-slate-200 py-2" name="parent_id">
                        <option value="">None</option>
                        @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 h-[42px]">Add</button>
            </form>
            
            <div class="flex flex-wrap gap-2">
                @foreach($categories as $c)
                <span class="rounded-lg bg-white border border-slate-200 shadow-sm px-3 py-2 text-sm font-medium text-slate-700">
                    {{ $c->name }}
                </span>
                @endforeach
            </div>
        </div>
    </section>
</div>

@endsection

@extends('layouts.app') 
@section('title', 'Units') 
@section('content')

<div class="mb-6">
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-600 transition">Dashboard</a> 
        <span>/</span>
        <a href="{{ route('products.index') }}" class="hover:text-slate-600 transition">Products</a> 
        <span>/</span>
        <span class="text-slate-600">Units</span>
    </div>
    <h1 class="font-serif text-3xl text-ink">Units</h1>
    <p class="text-sm text-slate-500 mt-1">Manage measurement units for products and stock.</p>
</div>

<div class="max-w-4xl" x-data="{ editUnit: null, deleteUnit: null }">
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-600">
                <i class="ti ti-ruler text-xl"></i>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-lg">Measurement Units</h2>
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
                    <input type="hidden" name="allows_decimal" value="0">
                    <input type="checkbox" name="allows_decimal" value="1" checked class="rounded text-teal-600 focus:ring-teal-500"> 
                    <span class="text-sm font-semibold text-slate-700">Allow Decimal</span>
                </label>
                <button class="px-5 py-2 text-sm font-semibold text-white bg-teal rounded-lg shadow-sm hover:bg-teal/90 h-[42px]">Add Unit</button>
            </form>
            
            <div class="flex flex-col gap-2">
                @foreach($units as $u)
                <div class="flex items-center justify-between rounded-lg bg-white border border-slate-200 shadow-sm px-4 py-3 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-800">{{ $u->name }}</span>
                        <strong class="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded text-xs">{{ $u->symbol }}</strong>
                        @if($u->allows_decimal)
                        <span class="text-[10px] bg-teal-50 text-teal-600 font-bold px-1.5 py-0.5 rounded uppercase">Decimals Allowed</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="editUnit = {{ $u->toJson() }}" class="text-blue-500 hover:text-blue-700 font-medium">Edit</button>
                        <button type="button" @click="deleteUnit = {{ $u->id }}" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Edit Unit Modal -->
    <div x-show="editUnit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="editUnit = null" class="w-full max-w-sm rounded-2xl bg-white shadow-xl overflow-hidden p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold text-slate-800">Edit Unit</h3>
                <button @click="editUnit = null" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form :action="`{{ url('settings/units') }}/${editUnit?.id}`" method="post" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
                    <input class="w-full rounded-lg border-slate-200 py-2" name="name" :value="editUnit?.name" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Symbol</label>
                    <input class="w-full rounded-lg border-slate-200 py-2" name="symbol" :value="editUnit?.symbol" required>
                </div>
                <label class="flex items-center gap-2 normal-case py-1 cursor-pointer">
                    <input type="hidden" name="allows_decimal" value="0">
                    <input type="checkbox" name="allows_decimal" value="1" :checked="editUnit?.allows_decimal" class="rounded text-teal-600 focus:ring-teal-500"> 
                    <span class="text-sm font-semibold text-slate-700">Allow Decimal</span>
                </label>
                <div class="flex gap-3 justify-end pt-4 border-t border-slate-100">
                    <button type="button" @click="editUnit = null" class="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                    <button class="px-5 py-2 font-semibold text-white bg-teal rounded-lg shadow-sm hover:bg-teal/90">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div x-show="deleteUnit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="deleteUnit = null" class="w-full max-w-sm rounded-2xl bg-white shadow-xl overflow-hidden p-6 text-center">
            <h3 class="text-lg font-bold text-slate-800 mb-2">Delete Unit?</h3>
            <p class="text-slate-500 text-sm mb-6">This action cannot be undone.</p>
            <form :action="`{{ url('settings/units') }}/${deleteUnit}`" method="post" class="flex gap-3 justify-center">
                @csrf @method('DELETE')
                <button type="button" @click="deleteUnit = null" class="px-4 py-2 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancel</button>
                <button class="px-5 py-2 font-semibold text-white bg-red-600 rounded-lg shadow-sm hover:bg-red-700">Delete</button>
            </form>
        </div>
    </div>
</div>

@endsection

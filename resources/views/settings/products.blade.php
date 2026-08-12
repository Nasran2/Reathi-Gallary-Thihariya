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

<div class="grid gap-6 lg:grid-cols-2" x-data="{ editUnit: null, deleteUnit: null, editCategory: null, deleteCategory: null }">
    <!-- Units Section -->
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
                    <input type="hidden" name="allows_decimal" value="0">
                    <input type="checkbox" name="allows_decimal" value="1" checked class="rounded text-blue-600 focus:ring-blue-500"> 
                    <span class="text-sm font-semibold text-slate-700">Decimal</span>
                </label>
                <button class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 h-[42px]">Add</button>
            </form>
            
            <div class="flex flex-col gap-2">
                @foreach($units as $u)
                <div class="flex items-center justify-between rounded-lg bg-white border border-slate-200 shadow-sm px-3 py-2 text-sm">
                    <div class="flex items-center gap-2">
                        {{ $u->name }} 
                        <strong class="bg-slate-100 text-slate-600 px-1.5 rounded text-xs">{{ $u->symbol }}</strong>
                        @if($u->allows_decimal)
                        <span class="text-[10px] bg-blue-50 text-blue-600 font-bold px-1.5 rounded uppercase">Dec</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="editUnit = {{ $u->toJson() }}" class="text-blue-500 hover:text-blue-700 font-medium">Edit</button>
                        <button type="button" @click="deleteUnit = {{ $u->id }}" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Categories Section -->
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
            
            <div class="flex flex-col gap-2">
                @foreach($categories as $c)
                <div class="flex items-center justify-between rounded-lg bg-white border border-slate-200 shadow-sm px-3 py-2 text-sm font-medium text-slate-700">
                    <div>
                        {{ $c->name }}
                        @if($c->parent_id)
                        <span class="text-xs text-slate-400 font-normal block">Child of: {{ $categories->firstWhere('id', $c->parent_id)?->name }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="editCategory = {{ $c->toJson() }}" class="text-blue-500 hover:text-blue-700 font-medium">Edit</button>
                        <button type="button" @click="deleteCategory = {{ $c->id }}" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
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
                    <input type="checkbox" name="allows_decimal" value="1" :checked="editUnit?.allows_decimal" class="rounded text-blue-600 focus:ring-blue-500"> 
                    <span class="text-sm font-semibold text-slate-700">Allow Decimal</span>
                </label>
                <div class="flex gap-3 justify-end pt-4 border-t border-slate-100">
                    <button type="button" @click="editUnit = null" class="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                    <button class="px-5 py-2 font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div x-show="editCategory" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="editCategory = null" class="w-full max-w-sm rounded-2xl bg-white shadow-xl overflow-hidden p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold text-slate-800">Edit Category</h3>
                <button @click="editCategory = null" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form :action="`{{ url('settings/categories') }}/${editCategory?.id}`" method="post" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
                    <input class="w-full rounded-lg border-slate-200 py-2" name="name" :value="editCategory?.name" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Parent Category</label>
                    <select class="w-full rounded-lg border-slate-200 py-2" name="parent_id" :value="editCategory?.parent_id">
                        <option value="">None</option>
                        @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 justify-end pt-4 border-t border-slate-100">
                    <button type="button" @click="editCategory = null" class="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                    <button class="px-5 py-2 font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modals -->
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

    <div x-show="deleteCategory" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="deleteCategory = null" class="w-full max-w-sm rounded-2xl bg-white shadow-xl overflow-hidden p-6 text-center">
            <h3 class="text-lg font-bold text-slate-800 mb-2">Delete Category?</h3>
            <p class="text-slate-500 text-sm mb-6">This action cannot be undone.</p>
            <form :action="`{{ url('settings/categories') }}/${deleteCategory}`" method="post" class="flex gap-3 justify-center">
                @csrf @method('DELETE')
                <button type="button" @click="deleteCategory = null" class="px-4 py-2 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancel</button>
                <button class="px-5 py-2 font-semibold text-white bg-red-600 rounded-lg shadow-sm hover:bg-red-700">Delete</button>
            </form>
        </div>
    </div>
</div>

@endsection

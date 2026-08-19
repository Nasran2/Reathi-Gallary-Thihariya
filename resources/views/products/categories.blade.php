@extends('layouts.app') 
@section('title', 'Categories') 
@section('content')

<div class="mb-6">
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-600 transition">Dashboard</a> 
        <span>/</span>
        <a href="{{ route('products.index') }}" class="hover:text-slate-600 transition">Products</a> 
        <span>/</span>
        <span class="text-slate-600">Categories</span>
    </div>
    <h1 class="font-serif text-3xl text-ink">Categories</h1>
    <p class="text-sm text-slate-500 mt-1">Manage product classifications and hierarchies.</p>
</div>

<div class="max-w-4xl" x-data="{ editCategory: null, deleteCategory: null }">
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-blue-50 text-blue-500">
                <i class="ti ti-tags text-xl"></i>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-lg">Product Categories</h2>
            </div>
        </div>
        
        <div class="p-6">
            <form method="post" action="{{ route('settings.categories.store') }}" class="flex flex-wrap items-end gap-3 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                @csrf
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Name</label>
                    <input class="w-full rounded-lg border-slate-200 py-2" name="name" placeholder="E.g. Cotton fabrics" required>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Parent Category</label>
                    <select class="w-full rounded-lg border-slate-200 py-2" name="parent_id">
                        <option value="">None (Top Level)</option>
                        @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 h-[42px]">Add Category</button>
            </form>
            
            <div class="flex flex-col gap-2">
                @foreach($categories as $c)
                <div class="flex items-center justify-between rounded-lg bg-white border border-slate-200 shadow-sm px-4 py-3 text-sm font-medium text-slate-700">
                    <div>
                        {{ $c->name }}
                        @if($c->parent_id)
                        <span class="text-xs text-slate-400 font-normal block mt-0.5 flex items-center gap-1">
                            <i class="ti ti-corner-down-right"></i> Child of: {{ $categories->firstWhere('id', $c->parent_id)?->name }}
                        </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="editCategory = {{ $c->toJson() }}" class="text-blue-500 hover:text-blue-700 font-medium">Edit</button>
                        <button type="button" @click="deleteCategory = {{ $c->id }}" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Edit Category Modal -->
    <div x-show="editCategory" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="editCategory = null" class="w-full max-w-sm rounded-2xl bg-white shadow-xl overflow-hidden p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold text-slate-800">Edit Category</h3>
                <button @click="editCategory = null" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form :action="editCategory ? '{{ url('settings/categories') }}/' + editCategory.id : ''" method="post" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
                    <input class="w-full rounded-lg border-slate-200 py-2" name="name" :value="editCategory ? editCategory.name : ''" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Parent Category</label>
                    <select class="w-full rounded-lg border-slate-200 py-2" name="parent_id" :value="editCategory ? editCategory.parent_id : ''">
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

    <!-- Delete Modal -->
    <div x-show="deleteCategory" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="deleteCategory = null" class="w-full max-w-sm rounded-2xl bg-white shadow-xl overflow-hidden p-6 text-center">
            <h3 class="text-lg font-bold text-slate-800 mb-2">Delete Category?</h3>
            <p class="text-slate-500 text-sm mb-6">This action cannot be undone.</p>
            <form :action="'{{ url('settings/categories') }}/' + deleteCategory" method="post" class="flex gap-3 justify-center">
                @csrf @method('DELETE')
                <button type="button" @click="deleteCategory = null" class="px-4 py-2 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancel</button>
                <button class="px-5 py-2 font-semibold text-white bg-red-600 rounded-lg shadow-sm hover:bg-red-700">Delete</button>
            </form>
        </div>
    </div>
</div>

@endsection

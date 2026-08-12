@extends('layouts.app') 
@section('title', 'Brands') 
@section('content')

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-600 transition">Dashboard</a> 
            <span>/</span>
            <a href="{{ route('products.index') }}" class="hover:text-slate-600 transition">Products</a> 
            <span>/</span>
            <span class="text-slate-600">Brands</span>
        </div>
        <h1 class="font-serif text-3xl text-ink">Brands</h1>
        <p class="text-sm text-slate-500 mt-1">Manage product brands and their details.</p>
    </div>
    <button type="button" @click="$dispatch('open-brand-modal')" class="btn-indigo flex items-center gap-2">
        <i class="ti ti-plus text-lg"></i> Add Brand
    </button>
</div>

<div class="card overflow-hidden">
    @if($brands->isEmpty())
        <div class="p-10 text-center bg-slate-50 border-b border-slate-100">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-4">
                <i class="ti ti-tag text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-700">No brands found</h3>
            <p class="text-slate-500 mt-1 mb-6 max-w-md mx-auto">Get started by creating your first product brand.</p>
            <button type="button" @click="$dispatch('open-brand-modal')" class="btn-indigo">Create Brand</button>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-700">Brand Name</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-700">Description</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700 w-24">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($brands as $brand)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800">{{ $brand->name }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ $brand->description ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="$dispatch('open-brand-modal', {{ $brand->toJson() }})" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Edit">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <button type="button" @click="$dispatch('delete-brand', {{ $brand->id }})" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Modal Logic -->
<div x-data="brandManager()" x-cloak>
    <!-- Add/Edit Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm" @open-brand-modal.window="openModal($event.detail)" @keydown.escape.window="showModal=false">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @click.away="showModal=false">
            <form :action="formAction" method="post">
                @csrf
                <template x-if="isEditing">
                    @method('PUT')
                </template>
                
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                    <h2 class="text-xl font-bold text-slate-800" x-text="isEditing ? 'Edit Brand' : 'Add Brand'"></h2>
                    <button type="button" @click="showModal=false" class="text-slate-400 hover:text-slate-600 bg-white rounded-lg p-1.5 shadow-sm border border-slate-200">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Brand Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" x-model="form.name" class="w-full rounded-xl border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea name="description" x-model="form.description" class="w-full rounded-xl border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-indigo-500" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="button" @click="showModal=false" class="px-5 py-2 font-semibold text-slate-600 hover:bg-slate-200 bg-slate-100 rounded-xl transition">Cancel</button>
                    <button type="submit" class="btn-indigo">Save Brand</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form id="delete-brand-form" :action="deleteAction" method="post" style="display:none;" @delete-brand.window="confirmDelete($event.detail)">
        @csrf @method('DELETE')
    </form>
</div>

@push('scripts')
<script>
function brandManager() {
    return {
        showModal: false,
        isEditing: false,
        brandId: null,
        form: { name: '', description: '' },
        
        get formAction() {
            return this.isEditing ? `/settings/brands/${this.brandId}` : `/settings/brands`;
        },
        
        deleteAction: '',
        
        openModal(brand) {
            if (brand && brand.id) {
                this.isEditing = true;
                this.brandId = brand.id;
                this.form.name = brand.name;
                this.form.description = brand.description || '';
            } else {
                this.isEditing = false;
                this.brandId = null;
                this.form.name = '';
                this.form.description = '';
            }
            this.showModal = true;
        },
        
        confirmDelete(id) {
            if (confirm('Are you sure you want to delete this brand?')) {
                this.deleteAction = `/settings/brands/${id}`;
                setTimeout(() => document.getElementById('delete-brand-form').submit(), 50);
            }
        }
    }
}
</script>
@endpush
@endsection

@extends('layouts.app') 
@section('title','Stock Adjustment') 
@section('content')
@php 
$catalog = $products->map(fn($p) => [
    'id' => $p->id,
    'name' => $p->name,
    'sku' => $p->sku,
    'units' => $p->productUnits->map(fn($u) => ['id' => $u->unit_id, 'label' => $u->unit->name.' ('.$u->unit->symbol.')'])->values()
])->values(); 
@endphp
<div class="mx-auto max-w-5xl" x-data='adjust(@json($catalog))'>
    <a class="text-sm text-slate-500 hover:text-teal-600 transition" href="{{ route('inventory.index') }}">← Inventory</a>
    <h1 class="mt-2 font-serif text-3xl text-ink">Stock adjustment</h1>
    <p class="mb-6 text-sm text-slate-500">Add multiple products to adjust their stock levels simultaneously.</p>

    <form class="card p-6" method="post" action="{{ route('inventory.adjust.store') }}">
        @csrf

        <div class="mb-6">
            <label class="block text-sm font-medium mb-1">Add Product</label>
            <div class="flex gap-2">
                <div class="flex-1" wire:ignore>
                    <select class="w-full" x-model.number="searchProduct" x-init="initTomSelect($el, {onChange: function(val) { $data.searchProduct = val; $data.addItem(); }})">
                        <option value="">Select a product to add...</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} · {{ $p->sku }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto bg-slate-50 rounded-lg border border-slate-200 mb-6">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-100/50 border-b border-slate-200 text-slate-500">
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">Store & Inventory</th>
                        <th class="px-4 py-3 font-medium">Direction</th>
                        <th class="px-4 py-3 font-medium w-48">Unit & Qty</th>
                        <th class="px-4 py-3 font-medium min-w-[200px]">Reason & Notes</th>
                        <th class="px-4 py-3 font-medium w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in items" :key="item.key">
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-ink" x-text="item.name"></div>
                                <div class="text-xs text-slate-500" x-text="'SKU: ' + item.sku"></div>
                                <input type="hidden" :name="`items[${index}][product_id]`" :value="item.id">
                            </td>
                            <td class="px-4 py-3 space-y-2">
                                <select class="w-full text-sm py-1.5" :name="`items[${index}][store_id]`" required>
                                    @foreach($stores as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                <select class="w-full text-sm py-1.5" :name="`items[${index}][inventory_type]`" required>
                                    <option value="main">Main inventory</option>
                                    <option value="remnant">Remnant inventory</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <select class="w-full text-sm py-1.5" :name="`items[${index}][direction]`" x-model="item.direction" required>
                                    <option value="increase">Increase (+)</option>
                                    <option value="decrease">Decrease (-)</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 space-y-2">
                                <select class="w-full text-sm py-1.5" :name="`items[${index}][unit_id]`" required>
                                    <template x-for="u in item.units">
                                        <option :value="u.id" x-text="u.label"></option>
                                    </template>
                                </select>
                                <input class="w-full text-sm py-1.5" type="number" min="0.001" step="0.001" :name="`items[${index}][quantity]`" x-model="item.quantity" placeholder="Qty" required>
                            </td>
                            <td class="px-4 py-3 space-y-2">
                                <select class="w-full text-sm py-1.5" :name="`items[${index}][reason]`" required>
                                    <option>Opening Stock</option>
                                    <option>Damage</option>
                                    <option>Missing</option>
                                    <option>Measurement Difference</option>
                                    <option>Correction</option>
                                    <option>Write-Off</option>
                                    <option>Other</option>
                                </select>
                                <input class="w-full text-sm py-1.5" type="text" :name="`items[${index}][notes]`" placeholder="Notes (Optional)">
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition" title="Remove">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                            No products added yet. Search and select a product above.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn-teal px-8" :disabled="items.length === 0">Post Adjustments</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function adjust(catalog) {
    return {
        catalog: catalog,
        searchProduct: '',
        items: [],
        addItem() {
            if (!this.searchProduct) return;
            let product = this.catalog.find(p => p.id == this.searchProduct);
            if (product) {
                this.items.push({
                    key: Date.now() + Math.random(),
                    id: product.id,
                    name: product.name,
                    sku: product.sku,
                    units: product.units,
                    direction: 'increase',
                    quantity: 1
                });
            }
            // Clear tom-select selection gracefully
            let sel = document.querySelector('select[name="product_id"]');
            if(sel && sel.tomselect) {
                sel.tomselect.clear(true);
            }
            this.searchProduct = '';
        },
        removeItem(index) {
            this.items.splice(index, 1);
        }
    }
}
</script>
@endpush
@endsection

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

$initialItems = $adjustment ? $adjustment->items->map(function($i) use ($catalog) {
    $p = $catalog->firstWhere('id', $i->product_id);
    return [
        'key' => uniqid(),
        'product_id' => $i->product_id,
        'name' => $p['name'],
        'sku' => $p['sku'],
        'units' => $p['units'],
        'store_id' => $i->store_id,
        'inventory_type' => $i->inventory_type,
        'direction' => $i->direction,
        'unit_id' => $i->unit_id,
        'quantity' => (float) $i->quantity,
        'reason' => $i->reason,
        'notes' => (string) $i->notes,
    ];
})->values() : [];
@endphp
<div class="mx-auto max-w-5xl" x-data='adjust(@json($catalog), @json($initialItems))'>
    <a class="text-sm text-slate-500 hover:text-teal-600 transition" href="{{ route('inventory.adjustments.index') }}">← Adjustments History</a>
    <h1 class="mt-2 font-serif text-3xl text-ink">{{ $adjustment ? 'Edit Adjustment #'.$adjustment->id : 'New Stock Adjustment' }}</h1>
    <p class="mb-6 text-sm text-slate-500">{{ $adjustment ? 'Modify an existing stock adjustment transaction.' : 'Add multiple products to adjust their stock levels simultaneously.' }}</p>

    <form class="card p-6" method="post" action="{{ $adjustment ? route('inventory.adjustments.update', $adjustment) : route('inventory.adjust.store') }}">
        @csrf
        @if($adjustment)
        @method('PUT')
        @endif
        <input type="hidden" name="items_json" :value="itemsJson">

        <div class="mb-6">
            <label class="block text-sm font-medium mb-1">Transaction Notes (Optional)</label>
            <textarea name="notes" class="w-full text-sm py-2 px-3 border border-slate-200 rounded-lg" rows="2" placeholder="Overall reason or reference for this adjustment...">{{ $adjustment->notes ?? old('notes') }}</textarea>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-1">Add Product</label>
            <div class="flex gap-2">
                <div class="flex-1" wire:ignore>
                    <select class="w-full" x-init="initSelect($el)">
                        <option value="">Select a product to add...</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}" data-search="{{ $p->name }} {{ $p->sku }} {{ $p->barcode }}">{{ $p->name }} · {{ $p->sku }}</option>
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
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium text-ink" x-text="item.name"></div>
                                <div class="text-xs text-slate-500" x-text="'SKU: ' + item.sku"></div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex flex-col gap-2">
                                    <select class="w-full text-sm py-1.5" x-model="item.store_id" required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                    <select class="w-full text-sm py-1.5" x-model="item.inventory_type" required>
                                        <option value="main">Main inventory</option>
                                        <option value="remnant">Remnant inventory</option>
                                    </select>
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <select class="w-full text-sm py-1.5" x-model="item.direction" required>
                                    <option value="increase">Increase (+)</option>
                                    <option value="decrease">Decrease (-)</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex flex-col gap-2">
                                    <select class="w-full text-sm py-1.5" x-model="item.unit_id" required>
                                        <option value="">Select Unit</option>
                                        <template x-for="u in item.units">
                                            <option :value="u.id" x-text="u.label"></option>
                                        </template>
                                    </select>
                                    <input class="w-full text-sm py-1.5" type="number" min="0.001" step="0.001" x-model.number="item.quantity" placeholder="Qty" required>
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex flex-col gap-2">
                                    <select class="w-full text-sm py-1.5" x-model="item.reason" required>
                                        <option>Opening Stock</option>
                                        <option>Damage</option>
                                        <option>Missing</option>
                                        <option>Measurement Difference</option>
                                        <option>Correction</option>
                                        <option>Write-Off</option>
                                        <option>Other</option>
                                    </select>
                                    <input class="w-full text-sm py-1.5" type="text" x-model="item.notes" placeholder="Notes (Optional)">
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top text-right">
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
            <button type="submit" class="btn-teal px-8" :disabled="isInvalid">{{ $adjustment ? 'Save Changes' : 'Post Adjustments' }}</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function adjust(catalog, initialItems) {
    return {
        catalog: catalog,
        searchProduct: '',
        items: initialItems || [],
        
        get itemsJson() {
            return JSON.stringify(this.items);
        },
        
        get isInvalid() {
            return this.items.length === 0 || this.items.some(i => !i.store_id || !i.unit_id || i.quantity <= 0);
        },
        
        initSelect(el) {
            new window.TomSelect(el, {
                searchField: ['text', 'search'],
                onChange: (val) => {
                    this.searchProduct = val;
                    this.addItem();
                }
            });
        },
        
        addItem() {
            if (!this.searchProduct) return;
            let product = this.catalog.find(p => p.id == this.searchProduct);
            if (product) {
                this.items.push({
                    key: Date.now() + Math.random(),
                    product_id: product.id,
                    name: product.name,
                    sku: product.sku,
                    units: product.units,
                    store_id: '{{ $stores->first()->id ?? '' }}',
                    inventory_type: 'main',
                    direction: 'increase',
                    unit_id: product.units.length ? product.units[0].id : '',
                    quantity: 1,
                    reason: 'Opening Stock',
                    notes: ''
                });
            }
            // Clear tom-select selection gracefully
            let sel = document.querySelector('select[x-init="initSelect($el)"]');
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

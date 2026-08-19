@extends('layouts.app') @section('title',$product->exists?'Edit product':'New product') @section('content')
@php 
$existing = old('units', $product->exists ? $product->productUnits->map(fn($u)=>['unit_id'=>$u->unit_id,'base_quantity'=>(float)$u->base_quantity,'unit_quantity'=>(float)$u->unit_quantity,'can_purchase'=>(bool)$u->can_purchase,'can_sell'=>(bool)$u->can_sell])->values()->all() : []); 
$existingExtras = [];
$baseUnitId = old('base_unit_id', $product->base_unit_id);
foreach($existing as $u) {
    if($u['unit_id'] == $baseUnitId && $u['base_quantity'] == 1 && $u['unit_quantity'] == 1) continue;
    $existingExtras[] = ['unit_id' => $u['unit_id'], 'unit_quantity' => $u['unit_quantity']];
}
@endphp
<div class="mb-6"><a class="text-sm text-slate-500" href="{{ route('products.index') }}">← Products</a><h1 class="mt-2 font-serif text-3xl text-ink">{{ $product->exists?'Edit '.$product->name:'Create a product' }}</h1><p class="text-sm text-slate-500">The conversion rate is the quantity in the selected base stock unit.</p></div>
<form method="post" action="{{ $product->exists?route('products.update',$product):route('products.store') }}" x-data='{rows:@json($existingExtras)}'>@csrf @if($product->exists)@method('PUT')@endif
<section class="card p-6" x-data="{ showAddCategory: false, newCatName: '', newCatParent: '' }">
    <h2 class="font-serif text-xl text-ink">Product identity</h2>
    <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="xl:col-span-2"><label>Product name *</label><input class="w-full" name="name" value="{{ old('name',$product->name) }}" required></div>
        <div class="xl:col-span-2"><label>SKU / Barcode *</label><input class="w-full" name="sku" value="{{ old('sku',$product->sku) }}" required></div>
        <div>
            <div class="flex justify-between items-end mb-1.5"><label class="mb-0">Category</label><button type="button" @click="showAddCategory = true" class="text-[10px] uppercase font-bold text-teal hover:text-teal/80 bg-teal/10 px-1.5 py-0.5 rounded">+ Add</button></div>
            <select class="w-full" name="category_id" id="category_select">
                <option value="">Uncategorised</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id',$product->category_id)==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label>Brand</label><select class="w-full" name="brand_id"><option value="">-- No Brand --</option>@foreach($brands as $b)<option value="{{ $b->id }}" {{ old('brand_id', $product->brand_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>@endforeach</select></div>
        <div><label>Fabric type</label><input class="w-full" name="fabric_type" value="{{ old('fabric_type',$product->fabric_type) }}" placeholder="Cotton, linen…"></div>
        <div><label>Material</label><input class="w-full" name="material" value="{{ old('material',$product->material) }}"></div>
        <div><label>Colour</label><input class="w-full" name="colour" value="{{ old('colour',$product->colour) }}"></div>
        <div><label>Pattern / design</label><input class="w-full" name="pattern" value="{{ old('pattern',$product->pattern) }}"></div>
        <div><label>Width</label><input class="w-full" name="width" value="{{ old('width',$product->width) }}"></div>
        <div>
            <label>Default supplier</label>
            <select class="w-full" name="default_supplier_id">
                <option value="">None</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" @selected(old('default_supplier_id',$product->default_supplier_id)==$s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="xl:col-span-4"><label>Description</label><textarea class="w-full" name="description" rows="2">{{ old('description',$product->description) }}</textarea></div>
    </div>

    <!-- Add Category Modal -->
    <div x-show="showAddCategory" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="showAddCategory = false" class="w-full max-w-sm rounded-2xl bg-white shadow-xl overflow-hidden p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold text-slate-800">Add Category</h3>
                <button type="button" @click="showAddCategory = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
                    <input class="w-full rounded-lg border-slate-200 py-2" x-model="newCatName" placeholder="E.g. Linen" required>
                </div>
                <div class="hidden">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Parent Category</label>
                    <select class="w-full rounded-lg border-slate-200 py-2" x-model="newCatParent">
                        <option value="">None</option>
                        @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 justify-end pt-4 border-t border-slate-100">
                    <button type="button" @click="showAddCategory = false" class="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                    <button type="button" @click="
                        if(!newCatName) return;
                        fetch('{{ route('settings.categories.store') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ name: newCatName, parent_id: newCatParent })
                        }).then(res => res.json()).then(data => {
                            if(data.id) {
                                const opt = new Option(data.name, data.id, false, true);
                                document.getElementById('category_select').add(opt);
                                showAddCategory = false;
                                newCatName = '';
                                newCatParent = '';
                            }
                        })
                    " class="px-5 py-2 font-semibold text-white bg-teal rounded-lg shadow-sm hover:bg-teal/90">Save</button>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="card mt-5 p-6"><h2 class="font-serif text-xl text-ink">Stock unit & controls</h2><div class="mt-5 grid gap-5 md:grid-cols-3 md:grid-cols-4"><div><label>Base stock unit *</label><select class="w-full" name="base_unit_id" id="base_unit_id" x-ref="baseUnitSelect" @change="$dispatch('base-unit-changed')" required><option value="">Select</option>@foreach($units as $u)<option value="{{ $u->id }}" @selected(old('base_unit_id',$product->base_unit_id)==$u->id)>{{ $u->name }} ({{ $u->symbol }})</option>@endforeach</select></div><div><label>Cost Price</label><div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">Rs.</span><input class="w-full pl-9" type="number" step="0.0001" min="0" name="average_cost" value="{{ old('average_cost', $product->average_cost ?? 0) }}" required></div></div><div><label>Main Selling Price</label><div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">Rs.</span><input class="w-full pl-9" type="number" step="0.0001" min="0" name="main_selling_price" value="{{ old('main_selling_price', $product->main_selling_price ?? 0) }}" required></div></div><div><label>Remnant Selling Price</label><div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">Rs.</span><input class="w-full pl-9" type="number" step="0.0001" min="0" name="remnant_selling_price" value="{{ old('remnant_selling_price', $product->remnant_selling_price ?? 0) }}" required></div></div><div><label>Minimum stock</label><input class="w-full" type="number" step="0.001" name="minimum_stock" value="{{ old('minimum_stock',$product->minimum_stock??0) }}" required></div><div><label>Reorder level</label><input class="w-full" type="number" step="0.001" name="reorder_level" value="{{ old('reorder_level',$product->reorder_level??0) }}" required></div><div class="hidden"><label>Tax rate %</label><input class="w-full" type="number" step="0.0001" name="tax_rate" value="{{ old('tax_rate',$product->tax_rate??0) }}"></div></div><div class="mt-4 flex gap-6"><label class="flex items-center gap-2 normal-case"><input type="checkbox" name="track_rolls" value="1" @checked(old('track_rolls',$product->track_rolls))> Track rolls/batches</label><label class="flex items-center gap-2 normal-case"><input type="checkbox" name="active" value="1" @checked(old('active',$product->exists?$product->active:true))> Active</label></div></section>

<!-- Auto-injected base unit conversion -->
<input type="hidden" name="units[0][unit_id]" x-bind:value="$refs.baseUnitSelect?.value">
<input type="hidden" name="units[0][base_quantity]" value="1">
<input type="hidden" name="units[0][unit_quantity]" value="1">
<input type="hidden" name="units[0][can_purchase]" value="1">
<input type="hidden" name="units[0][can_sell]" value="1">

<section class="card mt-5 overflow-hidden" x-data="{ baseName: '', presets: @json($unitPresets ?? []), applyPreset(presetId) {
    if(!presetId) return;
    const preset = this.presets.find(p => p.id == presetId);
    if(preset && preset.conversions) {
        this.rows = preset.conversions.map(c => ({
            unit_id: c.unit_id,
            unit_quantity: c.unit_quantity
        }));
    }
} }" @base-unit-changed.window="baseName = $refs.baseUnitSelect?.options[$refs.baseUnitSelect.selectedIndex]?.text" x-init="setTimeout(() => $dispatch('base-unit-changed'), 100)">
    <div class="flex items-center justify-between p-6">
        <div>
            <h2 class="font-serif text-xl text-ink">Additional units & conversions</h2>
            <p class="text-sm text-slate-500">Example: 1 Meter = 1.09361 Yard</p>
        </div>
        <div class="flex items-center gap-3">
            <select class="rounded-lg border-slate-200 text-sm py-1.5" @change="applyPreset($event.target.value); $event.target.value = ''">
                <option value="">-- Load from preset --</option>
                <template x-for="p in presets" :key="p.id">
                    <option :value="p.id" x-show="p.base_unit_id == $refs.baseUnitSelect?.value" x-text="p.name"></option>
                </template>
            </select>
            <button type="button" class="btn-soft" @click="rows.push({unit_id:'',unit_quantity:1})">+ Add conversion</button>
        </div>
    </div>
    <div class="overflow-x-auto" x-show="rows.length > 0">
        <table>
            <thead>
                <tr>
                    <th class="w-40">Base Unit</th>
                    <th class="text-center w-8"></th>
                    <th class="w-32">Equivalent</th>
                    <th>Converted Unit</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row,i) in rows" :key="i">
                    <tr>
                        <td class="font-semibold text-slate-700 bg-slate-50 rounded-l-lg border-y border-l">1 <span x-text="baseName || 'Base'"></span></td>
                        <td class="text-center font-bold text-slate-400 border-y">=</td>
                        <td class="border-y">
                            <input type="hidden" :name="`units[${i+1}][base_quantity]`" value="1">
                            <input type="hidden" :name="`units[${i+1}][can_purchase]`" value="1">
                            <input type="hidden" :name="`units[${i+1}][can_sell]`" value="1">
                            <input class="w-full" type="number" min="0.00001" step="0.00001" :name="`units[${i+1}][unit_quantity]`" x-model="row.unit_quantity" required>
                        </td>
                        <td class="border-y border-r rounded-r-lg">
                            <select class="w-full" :name="`units[${i+1}][unit_id]`" x-model="row.unit_id" required>
                                <option value="">Select unit</option>
                                @foreach($units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td><button type="button" class="text-red-500 hover:text-red-700 font-medium px-3" @click="rows.splice(i,1)">Remove</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div x-show="rows.length === 0" class="p-8 text-center text-slate-400 border-t border-slate-100">
        No extra conversions added. Only the base unit will be used.
    </div>
</section>

<div class="mt-6 flex justify-between items-center"><div x-data="{ confirmingDelete: false }">@if($product->exists)<button type="button" class="text-red-600 hover:text-red-800 font-semibold px-4 py-2 bg-red-50 hover:bg-red-100 rounded-lg transition" x-show="!confirmingDelete" @click="confirmingDelete = true">Delete Product</button><div x-show="confirmingDelete" class="flex items-center gap-3"><span class="text-sm font-bold text-slate-600">Are you sure?</span><button type="button" class="text-slate-500 hover:text-slate-700 font-semibold" @click="confirmingDelete = false">Cancel</button><button type="submit" form="delete-form" class="text-red-600 font-bold hover:underline">Yes, delete</button></div>@endif</div><div class="flex gap-3"><a class="btn-soft" href="{{ route('products.index') }}">Cancel</a><button class="btn-teal" type="submit">{{ $product->exists?'Save changes':'Create product' }}</button></div></div></form>@if($product->exists)<form id="delete-form" method="POST" action="{{ route('products.destroy', $product) }}" class="hidden">@csrf @method('DELETE')</form>@endif @endsection

@extends('layouts.app') @section('title',$product->exists?'Edit product':'New product') @section('content')
@php 
$existing = old('units', $product->exists ? $product->productUnits->map(fn($u)=>['unit_id'=>(string)$u->unit_id,'base_quantity'=>(float)$u->base_quantity,'unit_quantity'=>(float)$u->unit_quantity,'main_price'=>$u->main_price,'remnant_price'=>$u->remnant_price,'can_purchase'=>(bool)$u->can_purchase,'can_sell'=>(bool)$u->can_sell])->values()->all() : []);
$baseUnitId = old('base_unit_id', $product->base_unit_id);
$existingExtras = collect($existing)
    ->reject(fn ($unit) => (string) $unit['unit_id'] === (string) $baseUnitId)
    ->map(fn ($unit) => $unit + ['group' => (float) $unit['base_quantity'] !== 1.0])
    ->values()
    ->all();
@endphp
<div class="mb-6"><a class="text-sm text-slate-500" href="{{ route('products.index') }}">← Products</a><h1 class="mt-2 font-serif text-3xl text-ink">{{ $product->exists?'Edit '.$product->name:'Create a product' }}</h1><p class="text-sm text-slate-500">The conversion rate is the quantity in the selected base stock unit.</p></div>
<script type="application/json" id="product-form-data">{!! json_encode([
    'rows' => $existingExtras,
    'baseUnitId' => (string) $baseUnitId,
    'presets' => $unitPresets,
    'mainPrice' => number_format((float) old('main_selling_price', $product->main_selling_price ?? 0), 3, '.', ''),
    'remnantPrice' => number_format((float) old('remnant_selling_price', $product->remnant_selling_price ?? 0), 3, '.', ''),
    'categoryStoreUrl' => route('settings.categories.store'),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<form method="post" action="{{ $product->exists?route('products.update',$product):route('products.store') }}" x-data="productForm">@csrf @if($product->exists)@method('PUT')@endif
<section class="card p-6">
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
                    <input class="w-full rounded-lg border-slate-200 py-2" x-model="newCatName" placeholder="E.g. Linen">
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
                    <button type="button" @click="addCategory()" class="px-5 py-2 font-semibold text-white bg-teal rounded-lg shadow-sm hover:bg-teal/90">Save</button>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="card mt-5 p-6">
    <h2 class="font-serif text-xl text-ink">Stock unit & controls</h2>
    <div class="mt-5 grid gap-5 md:grid-cols-3 md:grid-cols-4">
        <div>
            <label>Base stock unit *</label>
            <select class="w-full" name="base_unit_id" id="base_unit_id" x-model="baseUnitId" @change="baseChanged()" required>
                <option value="">Select</option>
                @foreach($units as $u)
                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Cost Price / Base Unit</label>
            <div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">Rs.</span><input class="w-full pl-9" type="number" step="0.001" min="0" name="average_cost" value="{{ old('average_cost', number_format((float)($product->average_cost ?? 0), 3, '.', '')) }}" required></div>
        </div>
        <div>
            <label>Main Selling Price / Base Unit</label>
            <div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">Rs.</span><input class="w-full pl-9" type="number" step="0.001" min="0" name="main_selling_price" x-model="mainPrice" required></div>
        </div>
        <div>
            <label>Remnant Selling Price / Base Unit</label>
            <div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">Rs.</span><input class="w-full pl-9" type="number" step="0.001" min="0" name="remnant_selling_price" x-model="remnantPrice" required></div>
        </div>
        <div>
            <label>Opening Stock (Main Store)</label>
            <input class="w-full" type="number" step="0.001" min="0" name="opening_stock" value="{{ old('opening_stock', '0.000') }}" placeholder="Enter opening balance">
        </div>
        <div>
            <label>Minimum stock</label>
            <input class="w-full" type="number" step="0.001" min="0" name="minimum_stock" value="{{ old('minimum_stock', number_format((float)($product->minimum_stock ?? 0), 3, '.', '')) }}" required>
        </div>
        <div>
            <label>Reorder level</label>
            <input class="w-full" type="number" step="0.001" min="0" name="reorder_level" value="{{ old('reorder_level', number_format((float)($product->reorder_level ?? 0), 3, '.', '')) }}" required>
        </div>
        <div class="hidden">
            <label>Tax rate %</label>
            <input class="w-full" type="number" step="0.001" name="tax_rate" value="{{ old('tax_rate', $product->tax_rate ?? 0) }}">
        </div>
    </div>
    <div class="mt-4 flex gap-6">
        <label class="flex items-center gap-2 normal-case"><input type="checkbox" name="track_rolls" value="1" @checked(old('track_rolls',$product->track_rolls))> Track rolls/batches</label>
        <label class="flex items-center gap-2 normal-case"><input type="checkbox" name="active" value="1" @checked(old('active',$product->exists?$product->active:true))> Active</label>
    </div>
</section>

<section class="card mt-5 overflow-hidden" x-show="baseUnitId" x-cloak>
    <div class="flex items-center justify-between p-6">
        <div>
            <h2 class="font-serif text-xl text-ink">Additional units & conversions</h2>
            <p class="text-sm text-slate-500">Load a matching preset, then customize these conversions for this product.</p>
        </div>
        <div class="flex items-center gap-3">
            <label class="sr-only" for="unit_preset">Load Multiple Unit Preset</label>
            <select id="unit_preset" x-model="selectedPreset" class="rounded-lg border-slate-200 py-1.5 text-sm" @change="applyPreset()" :disabled="!filteredPresets.length">
                <option value="" x-text="filteredPresets.length ? 'Select Preset' : 'No matching presets'"></option>
                <template x-for="p in filteredPresets" :key="p.id">
                    <option :value="p.id" x-text="p.name"></option>
                </template>
            </select>
            <button type="button" class="btn-soft" @click="addConversion()">+ Add Conversion</button>
        </div>
    </div>
    <div class="overflow-x-auto" x-show="rows.length > 0">
        <table>
            <thead>
                <tr>
                    <th>Base quantity & unit</th>
                    <th class="text-center w-8"></th>
                    <th>Converted quantity</th>
                    <th>Converted Unit</th>
                    <th>Calculated price</th>
                    <th>Style</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row,i) in rows" :key="i">
                    <tr>
                        <td>
                            <div class="flex min-w-56 items-center gap-2">
                                <template x-if="row.group">
                                    <input class="w-28" type="number" min="0.00000001" step="0.00000001" :name="fieldName(i, 'base_quantity')" x-model="row.base_quantity" required>
                                </template>
                                <template x-if="!row.group">
                                    <div><input type="hidden" :name="fieldName(i, 'base_quantity')" value="1"><span class="inline-flex h-10 w-28 items-center rounded-lg bg-slate-100 px-3 font-semibold">1</span></div>
                                </template>
                                <span class="font-semibold text-slate-700" x-text="baseName"></span>
                            </div>
                        </td>
                        <td class="text-center font-bold text-slate-400 border-y">=</td>
                        <td class="border-y">
                            <input type="hidden" :name="fieldName(i, 'can_purchase')" value="1">
                            <input type="hidden" :name="fieldName(i, 'can_sell')" value="1">
                            <input class="min-w-36" type="number" min="0.00000001" step="0.00000001" :name="fieldName(i, 'unit_quantity')" x-model="row.unit_quantity" required>
                        </td>
                        <td class="border-y border-r rounded-r-lg">
                            <select class="min-w-52" :name="fieldName(i, 'unit_id')" x-model="row.unit_id" required>
                                <option value="">Select unit</option>
                                @foreach($units as $u)
                                <option value="{{ $u->id }}" :disabled="isBaseUnit('{{ $u->id }}')">{{ $u->name }} ({{ $u->symbol }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-sm">
                            <div class="mb-1 flex items-center gap-2">
                                <span class="w-14 text-slate-500">Main</span>
                                <input type="number" min="0" step="0.0001" class="w-28 !py-1 text-xs" :name="fieldName(i, 'main_price')" x-model="row.main_price" :placeholder="'Auto: ' + convertedPrice(row, mainPrice).toFixed(2)">
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-14 text-slate-500">Remnant</span>
                                <input type="number" min="0" step="0.0001" class="w-28 !py-1 text-xs" :name="fieldName(i, 'remnant_price')" x-model="row.remnant_price" :placeholder="'Auto: ' + convertedPrice(row, remnantPrice).toFixed(2)">
                            </div>
                        </td>
                        <td><label class="flex items-center gap-2 whitespace-nowrap normal-case"><input type="checkbox" x-model="row.group" @change="normalizeGroup(row)"> Group conversion</label></td>
                        <td><button type="button" class="text-red-500 hover:text-red-700 font-medium px-3" @click="removeConversion(i)">Remove</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div x-show="rows.length === 0" class="p-8 text-center text-slate-400 border-t border-slate-100">
        No extra conversions added. Only the base unit will be used.
    </div>
</section>

<div class="mt-6 flex justify-between items-center"><div x-data="{ confirmingDelete: false }">@if($product->exists)<button type="button" class="text-red-600 hover:text-red-800 font-semibold px-4 py-2 bg-red-50 hover:bg-red-100 rounded-lg transition" x-show="!confirmingDelete" @click="confirmingDelete = true">Delete Product</button><div x-show="confirmingDelete" class="flex items-center gap-3"><span class="text-sm font-bold text-slate-600">Are you sure?</span><button type="button" class="text-slate-500 hover:text-slate-700 font-semibold" @click="confirmingDelete = false">Cancel</button><button type="submit" form="delete-form" class="text-red-600 font-bold hover:underline">Yes, delete</button></div>@endif</div><div class="flex gap-3"><a class="btn-soft" href="{{ route('products.index') }}">Cancel</a><button class="btn-teal" type="submit">{{ $product->exists?'Save changes':'Create product' }}</button></div></div></form>@if($product->exists)<form id="delete-form" method="POST" action="{{ route('products.destroy', $product) }}" class="hidden">@csrf @method('DELETE')</form>@endif

@endsection

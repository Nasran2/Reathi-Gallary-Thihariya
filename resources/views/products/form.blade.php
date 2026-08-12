@extends('layouts.app') @section('title',$product->exists?'Edit product':'New product') @section('content')
@php $existing=old('units',$product->exists?$product->productUnits->map(fn($u)=>['unit_id'=>$u->unit_id,'conversion_rate'=>$u->conversion_rate,'main_selling_price'=>$u->main_selling_price,'remnant_selling_price'=>$u->remnant_selling_price,'can_purchase'=>(bool)$u->can_purchase,'can_sell'=>(bool)$u->can_sell])->values()->all():[['unit_id'=>'','conversion_rate'=>1,'main_selling_price'=>0,'remnant_selling_price'=>0,'can_purchase'=>true,'can_sell'=>true]]); @endphp
<div class="mb-6"><a class="text-sm text-slate-500" href="{{ route('products.index') }}">← Products</a><h1 class="mt-2 font-serif text-3xl text-ink">{{ $product->exists?'Edit '.$product->name:'Create a product' }}</h1><p class="text-sm text-slate-500">The conversion rate is the quantity in the selected base stock unit.</p></div>
<form method="post" action="{{ $product->exists?route('products.update',$product):route('products.store') }}" x-data='{rows:@json($existing)}'>@csrf @if($product->exists)@method('PUT')@endif
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
                <div>
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
<section class="card mt-5 p-6"><h2 class="font-serif text-xl text-ink">Stock unit & controls</h2><div class="mt-5 grid gap-5 md:grid-cols-3"><div><label>Base stock unit *</label><select class="w-full" name="base_unit_id" required><option value="">Select</option>@foreach($units as $u)<option value="{{ $u->id }}" @selected(old('base_unit_id',$product->base_unit_id)==$u->id)>{{ $u->name }} ({{ $u->symbol }})</option>@endforeach</select></div><div><label>Default purchase unit</label><select class="w-full" name="default_purchase_unit_id"><option value="">Select</option>@foreach($units as $u)<option value="{{ $u->id }}" @selected(old('default_purchase_unit_id',$product->default_purchase_unit_id)==$u->id)>{{ $u->name }}</option>@endforeach</select></div><div><label>Default selling unit</label><select class="w-full" name="default_selling_unit_id"><option value="">Select</option>@foreach($units as $u)<option value="{{ $u->id }}" @selected(old('default_selling_unit_id',$product->default_selling_unit_id)==$u->id)>{{ $u->name }}</option>@endforeach</select></div><div><label>Minimum stock</label><input class="w-full" type="number" step="0.000001" name="minimum_stock" value="{{ old('minimum_stock',$product->minimum_stock??0) }}" required></div><div><label>Reorder level</label><input class="w-full" type="number" step="0.000001" name="reorder_level" value="{{ old('reorder_level',$product->reorder_level??0) }}" required></div><div><label>Tax rate %</label><input class="w-full" type="number" step="0.0001" name="tax_rate" value="{{ old('tax_rate',$product->tax_rate??0) }}"></div></div><div class="mt-4 flex gap-6"><label class="flex items-center gap-2 normal-case"><input type="checkbox" name="track_rolls" value="1" @checked(old('track_rolls',$product->track_rolls))> Track rolls/batches</label><label class="flex items-center gap-2 normal-case"><input type="checkbox" name="active" value="1" @checked(old('active',$product->exists?$product->active:true))> Active</label></div></section>
<section class="card mt-5 overflow-hidden"><div class="flex items-center justify-between p-6"><div><h2 class="font-serif text-xl text-ink">Allowed units & independent prices</h2><p class="text-sm text-slate-500">Example: Yard rate 0.9144 when the base is metre.</p></div><button type="button" class="btn-soft" @click="rows.push({unit_id:'',conversion_rate:1,main_selling_price:0,remnant_selling_price:0,can_purchase:true,can_sell:true})">+ Add unit</button></div><div class="overflow-x-auto"><table><thead><tr><th>Unit</th><th>Base conversion</th><th>Main price</th><th>Remnant price</th><th>Use</th><th></th></tr></thead><tbody><template x-for="(row,i) in rows" :key="i"><tr><td><select class="min-w-40" :name="`units[${i}][unit_id]`" x-model="row.unit_id" required><option value="">Select</option>@foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>@endforeach</select></td><td><input class="w-36" type="number" min="0.00000001" step="0.00000001" :name="`units[${i}][conversion_rate]`" x-model="row.conversion_rate" required></td><td><input class="w-36" type="number" min="0" step="0.0001" :name="`units[${i}][main_selling_price]`" x-model="row.main_selling_price" required></td><td><input class="w-36" type="number" min="0" step="0.0001" :name="`units[${i}][remnant_selling_price]`" x-model="row.remnant_selling_price" required></td><td><input type="hidden" :name="`units[${i}][can_purchase]`" value="1"><input type="hidden" :name="`units[${i}][can_sell]`" value="1"><span class="text-xs text-slate-500">Buy + sell</span></td><td><button type="button" class="text-red-500" @click="rows.splice(i,1)" :disabled="rows.length===1">Remove</button></td></tr></template></tbody></table></div></section><div class="mt-6 flex justify-end gap-3"><a class="btn-soft" href="{{ route('products.index') }}">Cancel</a><button class="btn-teal" type="submit">{{ $product->exists?'Save changes':'Create product' }}</button></div></form>@endsection

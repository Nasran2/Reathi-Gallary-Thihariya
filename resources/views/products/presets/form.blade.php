@extends('layouts.app') 
@section('title', isset($unitPreset) ? 'Edit Preset' : 'New Preset') 
@section('content')
@php 
$existing = old('conversions', isset($unitPreset) ? $unitPreset->conversions->map(fn($c)=>['unit_id'=>$c->unit_id, 'unit_quantity'=>(float)$c->unit_quantity])->toArray() : []);
@endphp
<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <a class="text-sm text-slate-500" href="{{ route('unit-presets.index') }}">← Multiple Units</a>
        <h1 class="mt-2 font-serif text-3xl text-ink">{{ isset($unitPreset) ? 'Edit Preset' : 'New Preset' }}</h1>
        <p class="text-sm text-slate-500">Define a preset group of unit conversions.</p>
    </div>

    <form method="post" action="{{ isset($unitPreset) ? route('unit-presets.update', $unitPreset) : route('unit-presets.store') }}" class="card p-6" x-data='{rows:@json($existing)}'>
        @csrf 
        @if(isset($unitPreset)) @method('PUT') @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label>Preset Name *</label>
                <input class="w-full" name="name" value="{{ old('name', $unitPreset->name ?? '') }}" placeholder="e.g. Fabric Standards" required>
            </div>
            <div>
                <label>Base Stock Unit *</label>
                <select class="w-full" name="base_unit_id" id="base_unit_id" x-ref="baseUnitSelect" @change="$dispatch('base-unit-changed')" required>
                    <option value="">Select</option>
                    @foreach($units as $u)
                    <option value="{{ $u->id }}" @selected(old('base_unit_id', $unitPreset->base_unit_id ?? '') == $u->id)>{{ $u->name }} ({{ $u->symbol }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-8" x-data="{ baseName: '' }" @base-unit-changed.window="baseName = $refs.baseUnitSelect?.options[$refs.baseUnitSelect.selectedIndex]?.text" x-init="setTimeout(() => $dispatch('base-unit-changed'), 100)">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800">Conversions</h3>
                <button type="button" class="btn-soft text-sm py-1.5" @click="rows.push({unit_id:'', unit_quantity:1})">+ Add conversion</button>
            </div>

            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="p-3 font-medium">Base Unit</th>
                            <th class="w-8 text-center"></th>
                            <th class="p-3 font-medium">Equivalent</th>
                            <th class="p-3 font-medium">Converted Unit</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row,i) in rows" :key="i">
                            <tr class="border-t border-slate-200">
                                <td class="p-3 font-semibold text-slate-700 bg-slate-50">1 <span x-text="baseName || 'Base'"></span></td>
                                <td class="text-center font-bold text-slate-400">=</td>
                                <td class="p-3">
                                    <input class="w-full rounded-md border-slate-300" type="number" min="0.00001" step="0.00001" :name="`conversions[${i}][unit_quantity]`" x-model="row.unit_quantity" required>
                                </td>
                                <td class="p-3">
                                    <select class="w-full rounded-md border-slate-300" :name="`conversions[${i}][unit_id]`" x-model="row.unit_id" required>
                                        <option value="">Select unit</option>
                                        @foreach($units as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-3 text-right">
                                    <button type="button" class="text-red-500 hover:text-red-700 font-medium" @click="rows.splice(i,1)">Remove</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="rows.length === 0">
                            <td colspan="5" class="p-6 text-center text-slate-400">No conversions added yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 flex justify-between items-center border-t border-slate-100 pt-5">
            <div x-data="{ confirmingDelete: false }">
                @if(isset($unitPreset))
                <button type="button" class="text-red-600 hover:text-red-800 font-semibold px-4 py-2 bg-red-50 hover:bg-red-100 rounded-lg transition" x-show="!confirmingDelete" @click="confirmingDelete = true">Delete Preset</button>
                <div x-show="confirmingDelete" class="flex items-center gap-3">
                    <span class="text-sm font-bold text-slate-600">Are you sure?</span>
                    <button type="button" class="text-slate-500 hover:text-slate-700 font-semibold" @click="confirmingDelete = false">Cancel</button>
                    <button type="submit" form="delete-form" class="text-red-600 font-bold hover:underline">Yes, delete</button>
                </div>
                @endif
            </div>
            <div class="flex gap-3">
                <a class="btn-soft" href="{{ route('unit-presets.index') }}">Cancel</a>
                <button class="btn-teal" type="submit">{{ isset($unitPreset) ? 'Save changes' : 'Create preset' }}</button>
            </div>
        </div>
    </form>

    @if(isset($unitPreset))
    <form id="delete-form" method="POST" action="{{ route('unit-presets.destroy', $unitPreset) }}" class="hidden">
        @csrf @method('DELETE')
    </form>
    @endif
</div>
@endsection

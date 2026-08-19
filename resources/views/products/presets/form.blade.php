@extends('layouts.app')

@section('title', isset($unitPreset) ? 'Edit Multiple Unit Preset' : 'New Multiple Unit Preset')

@section('content')
@php
    $selectedBaseUnit = (string) old('base_unit_id', $unitPreset->base_unit_id ?? '');
    $existing = old('conversions', isset($unitPreset)
        ? $unitPreset->conversions->map(fn ($conversion) => [
            'unit_id' => (string) $conversion->unit_id,
            'base_quantity' => (float) $conversion->base_quantity,
            'unit_quantity' => (float) $conversion->unit_quantity,
            'group' => (float) $conversion->base_quantity !== 1.0,
        ])->values()->all()
        : []);
@endphp

<div class="mx-auto max-w-5xl">
    <script type="application/json" id="unit-preset-form-data">{!! json_encode([
        'rows' => $existing,
        'baseUnitId' => $selectedBaseUnit,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <div class="mb-6">
        <a class="text-sm text-slate-500" href="{{ route('unit-presets.index') }}">← Multiple Unit</a>
        <h1 class="mt-2 font-serif text-3xl text-ink">{{ isset($unitPreset) ? 'Edit preset' : 'Create a preset' }}</h1>
        <p class="text-sm text-slate-500">Save a reusable group of exact unit conversions for products with the same base stock unit.</p>
    </div>

    <form method="post"
          action="{{ isset($unitPreset) ? route('unit-presets.update', $unitPreset) : route('unit-presets.store') }}"
          class="card overflow-hidden"
          x-data="unitPresetForm">
        @csrf
        @if(isset($unitPreset)) @method('PUT') @endif

        <div class="grid gap-5 p-6 md:grid-cols-2">
            <div>
                <label>Preset Name *</label>
                <input class="w-full" name="name" value="{{ old('name', $unitPreset->name ?? '') }}" placeholder="e.g. Meter Standard" required>
            </div>
            <div>
                <label>Base Stock Unit *</label>
                <select class="w-full" name="base_unit_id" x-ref="baseUnit" x-model="baseUnitId"
                        @change="baseChanged($event)" required>
                    <option value="">Select base unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Changing the base unit clears conversions that belong to the previous base.</p>
            </div>
        </div>

        <section x-show="baseUnitId" x-cloak class="border-t border-slate-100">
            <div class="flex flex-wrap items-center justify-between gap-3 p-6">
                <div>
                    <h2 class="font-serif text-xl text-ink">Conversions</h2>
                    <p class="text-sm text-slate-500">Use a standard 1-to-many conversion, or enable Group conversion for cases such as 12 Pieces = 1 Dozen.</p>
                </div>
                <button type="button" class="btn-soft" @click="addConversion()">+ Add Conversion</button>
            </div>

            <div class="overflow-x-auto border-t border-slate-100" x-show="rows.length">
                <table>
                    <thead>
                        <tr>
                            <th>Base quantity & unit</th>
                            <th class="w-10 text-center"></th>
                            <th>Converted quantity</th>
                            <th>Converted unit</th>
                            <th>Style</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="index">
                            <tr>
                                <td>
                                    <div class="flex min-w-56 items-center gap-2">
                                        <template x-if="row.group">
                                            <input class="w-28" type="number" min="0.00000001" step="0.00000001"
                                                   :name="fieldName(index, 'base_quantity')" x-model="row.base_quantity" required>
                                        </template>
                                        <template x-if="!row.group">
                                            <div>
                                                <input type="hidden" :name="fieldName(index, 'base_quantity')" value="1">
                                                <span class="inline-flex h-10 w-28 items-center rounded-lg bg-slate-100 px-3 font-semibold text-slate-700">1</span>
                                            </div>
                                        </template>
                                        <span class="font-semibold text-slate-700" x-text="baseName"></span>
                                    </div>
                                </td>
                                <td class="text-center text-lg font-bold text-slate-400">=</td>
                                <td>
                                    <input class="min-w-36" type="number" min="0.00000001" step="0.00000001"
                                           :name="fieldName(index, 'unit_quantity')" x-model="row.unit_quantity" required>
                                </td>
                                <td>
                                    <select class="min-w-52" :name="fieldName(index, 'unit_id')" x-model="row.unit_id" required>
                                        <option value="">Select unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" :disabled="isBaseUnit('{{ $unit->id }}')">{{ $unit->name }} ({{ $unit->symbol }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <label class="flex items-center gap-2 whitespace-nowrap normal-case">
                                        <input type="checkbox" x-model="row.group" @change="normalizeGroup(row)">
                                        Group conversion
                                    </label>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="font-semibold text-red-500 hover:text-red-700" @click="removeConversion(index)">Remove</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="!rows.length" class="border-t border-slate-100 p-10 text-center text-sm text-slate-400">
                Add at least one conversion to save this preset.
            </div>
        </section>

        <div class="flex items-center justify-between border-t border-slate-100 p-6">
            <div x-data="{ confirmingDelete: false }">
                @if(isset($unitPreset))
                    <button type="button" class="rounded-lg bg-red-50 px-4 py-2 font-semibold text-red-600 hover:bg-red-100" x-show="!confirmingDelete" @click="confirmingDelete = true">Delete Preset</button>
                    <div x-show="confirmingDelete" class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-slate-600">Delete this preset?</span>
                        <button type="button" class="font-semibold text-slate-500" @click="confirmingDelete = false">Cancel</button>
                        <button type="submit" form="delete-form" class="font-bold text-red-600">Yes, delete</button>
                    </div>
                @endif
            </div>
            <div class="flex gap-3">
                <a class="btn-soft" href="{{ route('unit-presets.index') }}">Cancel</a>
                <button class="btn-teal" type="submit" :disabled="!baseUnitId || !rows.length">{{ isset($unitPreset) ? 'Save changes' : 'Create preset' }}</button>
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

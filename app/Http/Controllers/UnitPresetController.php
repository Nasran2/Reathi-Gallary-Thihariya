<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UnitPresetController extends Controller
{
    public function index()
    {
        $presets = \App\Models\UnitPreset::with('baseUnit', 'conversions.unit')->get();
        return view('products.presets.index', compact('presets'));
    }

    public function create()
    {
        $units = \App\Models\Unit::all();
        return view('products.presets.form', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'base_unit_id' => 'required|exists:units,id',
            'conversions' => 'array',
            'conversions.*.unit_id' => 'required|exists:units,id',
            'conversions.*.unit_quantity' => 'required|numeric|gt:0',
        ]);

        $preset = \App\Models\UnitPreset::create($request->only('name', 'base_unit_id'));

        if ($request->has('conversions')) {
            foreach ($request->conversions as $c) {
                $preset->conversions()->create($c);
            }
        }

        return redirect()->route('unit-presets.index')->with('success', 'Preset created successfully.');
    }

    public function edit(\App\Models\UnitPreset $unitPreset)
    {
        $units = \App\Models\Unit::all();
        $unitPreset->load('conversions');
        return view('products.presets.form', compact('unitPreset', 'units'));
    }

    public function update(Request $request, \App\Models\UnitPreset $unitPreset)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'base_unit_id' => 'required|exists:units,id',
            'conversions' => 'array',
            'conversions.*.unit_id' => 'required|exists:units,id',
            'conversions.*.unit_quantity' => 'required|numeric|gt:0',
        ]);

        $unitPreset->update($request->only('name', 'base_unit_id'));
        $unitPreset->conversions()->delete();

        if ($request->has('conversions')) {
            foreach ($request->conversions as $c) {
                $unitPreset->conversions()->create($c);
            }
        }

        return redirect()->route('unit-presets.index')->with('success', 'Preset updated successfully.');
    }

    public function destroy(\App\Models\UnitPreset $unitPreset)
    {
        $unitPreset->delete();
        return redirect()->route('unit-presets.index')->with('success', 'Preset deleted.');
    }
}

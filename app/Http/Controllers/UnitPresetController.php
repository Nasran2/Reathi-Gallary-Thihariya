<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitPreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UnitPresetController extends Controller
{
    public function index()
    {
        $presets = UnitPreset::with(['baseUnit', 'conversions.unit'])->orderBy('name')->get();

        return view('products.presets.index', compact('presets'));
    }

    public function create()
    {
        return view('products.presets.form', [
            'units' => Unit::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('unit_presets.manage');
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $preset = UnitPreset::create([
                'name' => $data['name'],
                'base_unit_id' => $data['base_unit_id'],
            ]);
            $preset->conversions()->createMany($data['conversions']);
        });

        return redirect()->route('unit-presets.index')->with('success', 'Multiple unit preset created.');
    }

    public function edit(UnitPreset $unitPreset)
    {
        $unitPreset->load('conversions');

        return view('products.presets.form', [
            'unitPreset' => $unitPreset,
            'units' => Unit::where('active', true)
                ->orWhereIn('id', $unitPreset->conversions->pluck('unit_id')->push($unitPreset->base_unit_id))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, UnitPreset $unitPreset)
    {
        $this->authorize('unit_presets.manage');
        $data = $this->validated($request, $unitPreset);

        DB::transaction(function () use ($data, $unitPreset) {
            $unitPreset->update([
                'name' => $data['name'],
                'base_unit_id' => $data['base_unit_id'],
            ]);
            $unitPreset->conversions()->delete();
            $unitPreset->conversions()->createMany($data['conversions']);
        });

        return redirect()->route('unit-presets.index')->with('success', 'Multiple unit preset updated.');
    }

    public function destroy(UnitPreset $unitPreset)
    {
        $this->authorize('unit_presets.manage');
        $unitPreset->delete();

        return redirect()->route('unit-presets.index')->with('success', 'Multiple unit preset deleted. Product conversions were not changed.');
    }

    private function validated(Request $request, ?UnitPreset $preset = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('unit_presets', 'name')->ignore($preset)],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'conversions' => ['required', 'array', 'min:1'],
            'conversions.*.base_quantity' => ['required', 'numeric', 'gt:0'],
            'conversions.*.unit_quantity' => ['required', 'numeric', 'gt:0'],
            'conversions.*.unit_id' => ['required', 'integer', 'distinct', 'exists:units,id'],
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ($request->input('conversions', []) as $index => $conversion) {
                if (isset($conversion['unit_id']) && (int) $conversion['unit_id'] === (int) $request->input('base_unit_id')) {
                    $validator->errors()->add("conversions.$index.unit_id", 'The converted unit must be different from the base stock unit.');
                }
            }
        });

        return $validator->validate();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    private function permissionFor(string $section): string
    {
        return match ($section) {
            'business-profile' => 'settings.business',
            'invoice' => 'settings.invoice',
            'pos' => 'settings.pos',
            'products', 'barcode' => 'settings.product',
            'stock' => 'settings.stock',
            'purchase' => 'settings.purchase',
            'sales' => 'settings.sales',
            'customers' => 'settings.customer',
            'suppliers' => 'settings.supplier',
            'accounts', 'expenses', 'taxes' => 'settings.account',
            'payments' => 'settings.payment_methods',
            'sms' => 'settings.sms',
            'reports' => 'settings.report',
            'backups' => 'settings.backup',
            'security' => 'settings.security',
            'audit' => 'settings.audit',
            default => 'settings.general',
        };
    }

    public function show($section = 'general')
    {
        $this->authorize($this->permissionFor($section));
        $config = config("settings.{$section}");
        
        if (!$config) {
            abort(404);
        }

        if ($config === 'custom') {
            return view("settings.{$section}", [
                'settings' => BusinessSetting::pluck('value', 'key'),
                'units' => Unit::orderBy('name')->get(),
                'categories' => Category::orderBy('name')->get(),
                'methods' => PaymentMethod::orderBy('sort_order')->get()
            ]);
        }

        return view('settings.dynamic', [
            'section' => $section,
            'config' => $config,
            'settings' => BusinessSetting::pluck('value', 'key'),
        ]);
    }

    public function update(Request $r)
    {
        $section = (string) $r->input('_section', 'general');
        $this->authorize($this->permissionFor($section));
        
        $data = $r->except(['_token', '_method']);
        
        if ($r->hasFile('business_logo')) {
            $path = $r->file('business_logo')->store('public/logos');
            $data['business_logo'] = str_replace('public/', 'storage/', $path);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) continue; // skip arrays if any
            BusinessSetting::write($key, $value, str_contains($key, 'password'));
        }

        // Handle un-checked checkboxes (they are missing from the request)
        if ($section && is_array(config("settings.{$section}.sections"))) {
            foreach (config("settings.{$section}.sections") as $sec) {
                foreach ($sec['fields'] as $field) {
                    if ($field['type'] === 'checkbox' && !$r->has($field['name'])) {
                        BusinessSetting::write($field['name'], '0');
                    }
                }
            }
        }

        return back()->with('success', 'Settings saved.');
    }

    public function unit(Request $r)
    {
        $this->authorize('units.manage');
        Unit::create($r->validate(['name' => 'required|max:60', 'symbol' => 'required|max:16|unique:units,symbol']) + ['allows_decimal' => $r->boolean('allows_decimal'), 'active' => true]);

        return back()->with('success', 'Unit added.');
    }

    public function updateUnit(Request $r, Unit $unit)
    {
        $this->authorize('units.manage');
        $unit->update($r->validate(['name' => 'required|max:60', 'symbol' => 'required|max:16|unique:units,symbol,'.$unit->id]) + ['allows_decimal' => $r->boolean('allows_decimal')]);
        return back()->with('success', 'Unit updated.');
    }

    public function destroyUnit(Unit $unit)
    {
        $this->authorize('units.manage');
        $unit->delete();
        return back()->with('success', 'Unit deleted.');
    }

    public function category(Request $r)
    {
        $this->authorize('categories.manage');
        $d = $r->validate(['name' => 'required|max:100', 'parent_id' => 'nullable|exists:categories,id']);
        $cat = Category::create($d + ['slug' => Str::slug($d['name']).'-'.Str::lower(Str::random(4)), 'active' => true]);

        if ($r->wantsJson()) {
            return response()->json($cat);
        }

        return back()->with('success', 'Category added.');
    }

    public function updateCategory(Request $r, Category $category)
    {
        $this->authorize('categories.manage');
        $d = $r->validate(['name' => 'required|max:100', 'parent_id' => 'nullable|exists:categories,id']);
        $category->update($d);
        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(Category $category)
    {
        $this->authorize('categories.manage');
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function paymentMethod(Request $r)
    {
        $this->authorize('settings.payment_methods');
        $d = $r->validate(['name' => 'required|max:80|unique:payment_methods,name', 'code' => 'required|max:50|unique:payment_methods,code', 'requires_reference' => 'boolean', 'bank_charge_percentage' => 'nullable|numeric|min:0|max:100']);
        PaymentMethod::create($d + ['active' => true, 'sort_order' => PaymentMethod::max('sort_order') + 1]);

        return back()->with('success', 'Payment method added.');
    }

    public function updatePaymentMethod(Request $r, PaymentMethod $method)
    {
        $this->authorize('settings.payment_methods');
        $d = $r->validate(['name' => 'required|max:80|unique:payment_methods,name,'.$method->id, 'code' => 'required|max:50|unique:payment_methods,code,'.$method->id, 'requires_reference' => 'boolean', 'active' => 'boolean', 'bank_charge_percentage' => 'nullable|numeric|min:0|max:100']);
        $method->update($d);
        return back()->with('success', 'Payment method updated.');
    }

    public function destroyPaymentMethod(PaymentMethod $method)
    {
        $this->authorize('settings.payment_methods');
        $method->delete();
        return back()->with('success', 'Payment method deleted.');
    }

    public function brand(Request $r)
    {
        $this->authorize('brands.manage');
        $d = $r->validate(['name' => 'required|max:100|unique:brands,name', 'description' => 'nullable']);
        $brand = \App\Models\Brand::create($d + ['active' => true]);

        if ($r->wantsJson()) {
            return response()->json($brand);
        }

        return back()->with('success', 'Brand added.');
    }

    public function updateBrand(Request $r, \App\Models\Brand $brand)
    {
        $this->authorize('brands.manage');
        $d = $r->validate(['name' => 'required|max:100|unique:brands,name,'.$brand->id, 'description' => 'nullable']);
        $brand->update($d);
        return back()->with('success', 'Brand updated.');
    }

    public function destroyBrand(\App\Models\Brand $brand)
    {
        $this->authorize('brands.manage');
        $brand->delete();
        return back()->with('success', 'Brand deleted.');
    }
}

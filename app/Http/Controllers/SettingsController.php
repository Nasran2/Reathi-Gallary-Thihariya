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
    public function index()
    {
        return view('settings.index', ['settings' => BusinessSetting::pluck('value', 'key'), 'units' => Unit::orderBy('name')->get(), 'categories' => Category::orderBy('name')->get(), 'methods' => PaymentMethod::orderBy('sort_order')->get()]);
    }

    public function update(Request $r)
    {
        $this->authorize('manage-settings');
        $data = $r->validate(['business_name' => 'required|max:150', 'currency_symbol' => 'required|max:10', 'money_decimals' => 'required|integer|min:0|max:4', 'quantity_decimals' => 'required|integer|min:0|max:6', 'allow_negative_stock' => 'boolean', 'block_main_below_cost' => 'boolean', 'remnant_partial_sale' => 'boolean', 'sms_enabled' => 'boolean', 'sms_gateway_url' => 'nullable|url', 'sms_textit_id' => 'nullable|max:150', 'sms_password' => 'nullable|max:255', 'sms_timeout' => 'nullable|integer|min:1|max:60', 'sms_template' => 'nullable|max:1000', 'invoice_link_expiry' => 'required|in:never,30_days,90_days,1_year', 'primary_colour' => 'nullable|max:20']);
        foreach ($data as $key => $value) {
            BusinessSetting::write($key, $value, $key === 'sms_password');
        }

return back()->with('success', 'Settings saved.');
    }

    public function unit(Request $r)
    {
        $this->authorize('manage-settings');
        Unit::create($r->validate(['name' => 'required|max:60', 'symbol' => 'required|max:16|unique:units,symbol']) + ['allows_decimal' => $r->boolean('allows_decimal'), 'active' => true]);

        return back()->with('success', 'Unit added.');
    }

    public function category(Request $r)
    {
        $this->authorize('manage-settings');
        $d = $r->validate(['name' => 'required|max:100', 'parent_id' => 'nullable|exists:categories,id']);
        Category::create($d + ['slug' => Str::slug($d['name']).'-'.Str::lower(Str::random(4)), 'active' => true]);

        return back()->with('success', 'Category added.');
    }

    public function paymentMethod(Request $r)
    {
        $this->authorize('manage-settings');
        $d = $r->validate(['name' => 'required|max:80|unique:payment_methods,name', 'code' => 'required|max:50|unique:payment_methods,code', 'requires_reference' => 'boolean']);
        PaymentMethod::create($d + ['active' => true, 'sort_order' => PaymentMethod::max('sort_order') + 1]);

        return back()->with('success', 'Payment method added.');
    }
}

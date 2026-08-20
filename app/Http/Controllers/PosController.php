<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Remnant;
use App\Models\Store;
use App\Services\SaleService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index(string $type = 'main')
    {
        $this->authorize($type === 'remnant' ? 'pos.remnant.access' : 'pos.main.access');
        $store = Store::where('is_default', 1)->firstOrFail();
        $customers = Customer::where('active', 1)->orderBy('is_walk_in', 'desc')->orderBy('name')->get();
        $methods = PaymentMethod::where('active', 1)->orderBy('sort_order')->get();
        $categories = Category::where('active', 1)->get();
        if ($type === 'remnant') {
            $items = Remnant::with(['product', 'unit'])->where('store_id', $store->id)->whereIn('status', ['available', 'partially_sold'])->latest()->get();
        } else {
            $items = Product::with(['productUnits.unit', 'balances' => fn ($q) => $q->where('store_id', $store->id)->where('inventory_type', 'main')])->where('active', 1)->get();
        }

        $saleSuccess = null;
        if (session('sale_success')) {
            $saleSuccess = \App\Models\Sale::with(['customer', 'items.product', 'items.unit', 'payments.method', 'publicToken'])->find(session('sale_success'));
        }

        return view('pos.index', compact('type', 'store', 'customers', 'methods', 'categories', 'items', 'saleSuccess'));
    }

    public function checkout(Request $r, SaleService $service, SmsService $sms)
    {
        $payload = $r->validate(['sale_type' => 'required|in:main,remnant', 'store_id' => 'required|exists:stores,id', 'customer_id' => 'nullable|exists:customers,id', 'idempotency_key' => 'required|string|max:100', 'notes' => 'nullable', 'send_sms' => 'boolean', 'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:products,id', 'items.*.unit_id' => 'required|exists:units,id', 'items.*.remnant_id' => 'nullable|exists:remnants,id', 'items.*.quantity' => 'required|numeric|gt:0', 'items.*.unit_price' => 'required|numeric|min:0', 'items.*.discount_amount' => 'nullable|numeric|min:0', 'items.*.tax_amount' => 'nullable|numeric|min:0', 'items.*.notes' => 'nullable', 'payments' => 'nullable|array', 'payments.*.payment_method_id' => 'required|exists:payment_methods,id', 'payments.*.amount' => 'required|numeric|min:0', 'payments.*.reference' => 'nullable|string|max:150', 'payments.*.cheque_number' => 'nullable|string|max:80', 'payments.*.bank' => 'nullable|string|max:120', 'payments.*.cheque_date' => 'nullable|date']);
        $this->authorize($payload['sale_type'] === 'remnant' ? 'pos.remnant.sell' : 'pos.main.sell');
        $sale = $service->checkout($payload, $r->user()->id);
        if ($r->boolean('send_sms')) {
            $sms->sendInvoice($sale, $r->user()->id);
        }

        return redirect()->route('pos.' . $payload['sale_type'])->with('sale_success', $sale->id);
    }
}

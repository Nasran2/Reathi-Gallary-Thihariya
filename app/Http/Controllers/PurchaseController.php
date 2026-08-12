<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('purchases.index', ['purchases' => Purchase::with('supplier')->latest()->paginate(20)]);
    }

    public function create()
    {
        return view('purchases.form', ['suppliers' => Supplier::where('active', 1)->get(), 'stores' => Store::where('active', 1)->get(), 'products' => Product::with('productUnits.unit')->where('active', 1)->get()]);
    }

    public function store(Request $r, PurchaseService $service)
    {
        $this->authorize('manage-purchases');
        $data = $r->validate(['supplier_id' => 'required|exists:suppliers,id', 'store_id' => 'required|exists:stores,id', 'supplier_invoice_no' => 'nullable|max:100', 'reference_no' => 'nullable|max:100', 'purchase_date' => 'required|date', 'due_date' => 'nullable|date', 'extra_cost_total' => 'nullable|numeric|min:0', 'notes' => 'nullable', 'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:products,id', 'items.*.unit_id' => 'required|exists:units,id', 'items.*.quantity' => 'required|numeric|gt:0', 'items.*.supplier_unit_cost' => 'required|numeric|min:0', 'items.*.discount_amount' => 'nullable|numeric|min:0', 'items.*.tax_amount' => 'nullable|numeric|min:0']);
        $purchase = $service->receive($data, $r->user()->id);

        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase received and stock updated.');
    }

    public function show(Purchase $purchase)
    {
        return view('purchases.show', ['purchase' => $purchase->load('supplier', 'store', 'items.product', 'items.unit')]);
    }
}

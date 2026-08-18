<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function sales(Request $r)
    {
        $sale = $r->sale_id ? Sale::with('items.product', 'items.unit', 'items.returnItems', 'customer')->find($r->sale_id) : ($r->invoice ? Sale::with('items.product', 'items.unit', 'items.returnItems', 'customer')->where('invoice_no', $r->invoice)->first() : null);

        return view('returns.sales', ['sale' => $sale, 'returns' => SaleReturn::with('sale', 'customer')->latest('return_date')->paginate(20)]);
    }

    public function storeSale(Request $r, ReturnService $service)
    {
        $data = $r->validate(['sale_id' => 'required|exists:sales,id', 'return_date' => 'required|date', 'settlement' => 'required|in:customer_credit,cash_refund', 'reason' => 'required|max:255', 'notes' => 'nullable', 'items' => 'required|array', 'items.*' => 'nullable|numeric|min:0']);
        $return = $service->sale($data, $r->user()->id);

        return redirect()->route('sales.returns', ['sale_id' => $return->sale_id])->with('success', 'Sales return posted; stock, COGS, profit and customer ledger were updated.');
    }

    public function purchases(Request $r)
    {
        $purchase = $r->purchase_id ? Purchase::with('items.product', 'items.unit', 'items.returnItems', 'supplier')->find($r->purchase_id) : ($r->purchase_no ? Purchase::with('items.product', 'items.unit', 'items.returnItems', 'supplier')->where('purchase_no', $r->purchase_no)->first() : null);

        return view('returns.purchases', ['purchase' => $purchase, 'returns' => PurchaseReturn::with('purchase', 'supplier')->latest('return_date')->paginate(20)]);
    }

    public function storePurchase(Request $r, ReturnService $service)
    {
        $data = $r->validate(['purchase_id' => 'required|exists:purchases,id', 'return_date' => 'required|date', 'reason' => 'required|max:255', 'notes' => 'nullable', 'items' => 'required|array', 'items.*' => 'nullable|numeric|min:0']);
        $return = $service->purchase($data, $r->user()->id);

        return redirect()->route('purchases.returns', ['purchase_id' => $return->purchase_id])->with('success', 'Purchase return posted; stock and supplier ledger were updated.');
    }
}

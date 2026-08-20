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

    public function storePurchase(Request $r, ReturnService $service, \App\Services\PurchaseService $purchaseService)
    {
        $data = $r->validate([
            'purchase_id' => 'required|exists:purchases,id', 
            'return_date' => 'required|date', 
            'reason' => 'required|max:255', 
            'notes' => 'nullable', 
            'items' => 'required|array', 
            'items.*' => 'nullable|numeric|min:0',
            'settlement' => 'nullable|in:deduct,exchange',
            'exchange_items' => 'nullable|array',
            'exchange_items.*.product_id' => 'required_with:exchange_items|exists:products,id',
            'exchange_items.*.unit_id' => 'required_with:exchange_items|exists:units,id',
            'exchange_items.*.quantity' => 'required_with:exchange_items|numeric|min:0.001',
            'exchange_items.*.cost' => 'required_with:exchange_items|numeric|min:0',
            'exchange_items.*.system_cost' => 'required_with:exchange_items|numeric|min:0',
        ]);
        
        $return = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $r, $service, $purchaseService) {
            $return = $service->purchase($data, $r->user()->id);

            if (($data['settlement'] ?? 'deduct') === 'exchange' && !empty($data['exchange_items'])) {
                $purchaseData = [
                    'supplier_id' => $return->purchase->supplier_id,
                    'store_id' => $return->purchase->store_id,
                    'purchase_date' => $data['return_date'],
                    'supplier_invoice_no' => 'EXC-'.$return->return_no,
                    'reference_no' => 'Exchange for '.$return->purchase->purchase_no,
                    'notes' => 'Exchange from return: '.$return->reason,
                    'items' => collect($data['exchange_items'])->map(function($ex) {
                        return [
                            'product_id' => $ex['product_id'],
                            'unit_id' => $ex['unit_id'],
                            'quantity' => $ex['quantity'],
                            'supplier_unit_cost' => $ex['cost'],
                            'system_unit_cost' => $ex['system_cost'],
                            'discount_amount' => 0,
                            'tax_amount' => 0
                        ];
                    })->toArray(),
                ];
                $purchaseService->receive($purchaseData, $r->user()->id);
            }
            
            return $return;
        });

        if ($r->wantsJson()) {
            return response()->json(['redirect' => route('purchases.returns', ['purchase_id' => $return->purchase_id])]);
        }

        return redirect()->route('purchases.returns', ['purchase_id' => $return->purchase_id])->with('success', 'Purchase return posted; stock and supplier ledger were updated.');
    }
    public function createPurchaseReturn(Request $r)
    {
        $suppliers = \App\Models\Supplier::all();
        $products = \App\Models\Product::with('productUnits.unit')->get();
        return view('returns.purchases_create', compact('suppliers', 'products'));
    }

    public function getSupplierPurchases($supplier_id)
    {
        $purchases = Purchase::where('supplier_id', $supplier_id)->latest()->get(['id', 'purchase_no', 'purchase_date', 'supplier_total', 'supplier_invoice_no']);
        return response()->json($purchases);
    }

    public function getPurchaseItems($purchase_id)
    {
        $purchase = Purchase::with(['items.product', 'items.unit', 'items.returnItems'])->find($purchase_id);
        if (!$purchase) return response()->json([]);
        
        $items = $purchase->items->map(function ($item) {
            $returned = $item->returnItems->sum('quantity');
            $available = max(0, $item->quantity - $returned);
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_sku' => $item->product->sku,
                'unit_symbol' => $item->unit->symbol,
                'purchased_qty' => $item->quantity,
                'returned_qty' => $returned,
                'available_qty' => $available,
                'cost' => $item->supplier_unit_cost,
                'system_cost' => $item->system_unit_cost,
            ];
        })->filter(fn($i) => $i['available_qty'] > 0)->values();

        return response()->json($items);
    }
}

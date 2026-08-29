<?php

namespace App\Http\Controllers;

use App\Models\Sale;

class SaleController extends Controller
{
    public function index()
    {
        return view('sales.index', ['sales' => Sale::with('customer')->latest('sold_at')->paginate(25)]);
    }

    public function show(Sale $sale)
    {
        return view('sales.show', ['sale' => $sale->load('items.product', 'items.unit', 'payments.method', 'customer', 'store', 'publicToken')]);
    }
    
    public function print(Sale $sale)
    {
        return view('sales.print', ['sale' => $sale->load('items.product', 'items.unit', 'payments.method', 'customer', 'store')]);
    }
    
    public function pdf(Sale $sale)
    {
        $sale->load('items.product', 'items.unit', 'items.remnant', 'payments.method', 'customer', 'store', 'user');
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf', compact('sale'));
        $pdf->setPaper('a4', 'portrait');
        
        // Return download response
        return $pdf->download('Invoice-' . $sale->invoice_no . '.pdf');
    }
    
    public function updateCustomer(\Illuminate\Http\Request $request, Sale $sale)
    {
        if ($request->has('attach_customer_id')) {
            $sale->update(['customer_id' => $request->attach_customer_id]);
        } elseif ($request->has('new_customer_name')) {
            $customer = \App\Models\Customer::create([
                'name' => $request->new_customer_name,
                'mobile' => $request->new_customer_phone,
                'active' => 1,
                'is_walk_in' => 0
            ]);
            $sale->update(['customer_id' => $customer->id]);
        } elseif ($request->has('update_phone') && $sale->customer_id) {
            $sale->customer->update(['mobile' => $request->update_phone]);
        }
        return response()->json(['success' => true]);
    }
    
    public function sms(Sale $sale, \App\Services\SmsService $sms)
    {
        $sale->load('customer', 'publicToken');
        if (!$sale->customer || !$sale->customer->mobile) {
            return response()->json(['error' => 'No valid customer phone number'], 400);
        }
        $sms->sendInvoice($sale, auth()->id());
        return response()->json(['success' => true]);
    }

    public function destroy(Sale $sale, \App\Services\SaleService $saleService)
    {
        try {
            $saleService->deleteSale($sale);
            return redirect()->route('sales.index')->with('success', 'Sale deleted successfully. Stock has been returned to inventory.');
        } catch (\Exception $e) {
            return redirect()->route('sales.index')->with('error', 'Failed to delete sale: ' . $e->getMessage());
        }
    }

    public function edit(Sale $sale, \App\Services\SaleService $saleService)
    {
        try {
            // Determine if it was a remnant sale based on invoice number or items
            $isRemnant = str_starts_with($sale->invoice_no, 'RPOS');
            
            // Build the cart state to restore
            $cart = [];
            foreach ($sale->items as $item) {
                $product = $item->product;
                if (!$product) continue;
                
                $cartItem = [
                    'lineKey' => 'edit_' . uniqid(),
                    'product_id' => $item->product_id,
                    'remnant_id' => $item->remnant_id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit_id' => $item->unit_id,
                    'quantity' => (float)$item->quantity,
                    'price' => (float)$item->unit_price,
                    'discount_value' => (float)$item->discount_amount,
                    'discount_type' => 'fixed',
                ];
                
                if ($item->remnant_id) {
                    $remnant = \App\Models\Remnant::find($item->remnant_id);
                    $cartItem['remnant_no'] = $remnant ? $remnant->remnant_no : null;
                    $cartItem['maxDisplay'] = $remnant ? (float)$remnant->remaining_quantity + (float)$item->quantity : (float)$item->quantity;
                    $cartItem['units'] = [['unit_id' => $item->unit_id, 'symbol' => $item->unit->symbol ?? '']];
                } else {
                    $cartItem['units'] = $product->productUnits->map(fn($u) => ['unit_id' => $u->unit_id, 'symbol' => $u->unit->symbol])->toArray();
                    $cartItem['maxDisplay'] = 999999;
                }
                
                $cart[] = $cartItem;
            }

            // Delete the old sale first so stock is returned
            $saleService->deleteSale($sale);
            
            // Redirect to POS with the cart preloaded in session
            session()->flash('edit_cart', $cart);
            session()->flash('edit_customer', $sale->customer_id);
            
            return redirect()->route($isRemnant ? 'pos.remnant' : 'pos.main')
                ->with('success', 'Sale voided. You can now edit the items and checkout again.');
                
        } catch (\Exception $e) {
            return redirect()->route('sales.index')->with('error', 'Failed to edit sale: ' . $e->getMessage());
        }
    }
}

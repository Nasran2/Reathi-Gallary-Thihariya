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
}

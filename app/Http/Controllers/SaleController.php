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
}

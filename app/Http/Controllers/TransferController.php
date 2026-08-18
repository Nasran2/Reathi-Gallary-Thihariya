<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Services\StockTransferService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function index()
    {
        return view('transfers.index', ['transfers' => StockTransfer::with('product', 'unit', 'fromStore', 'toStore', 'user')->latest('transfer_date')->paginate(25), 'products' => Product::with('productUnits.unit')->where('active', true)->orderBy('name')->get(), 'stores' => Store::where('active', true)->orderBy('name')->get()]);
    }

    public function store(Request $r, StockTransferService $service)
    {
        $data = $r->validate(['product_id' => 'required|exists:products,id', 'unit_id' => 'required|exists:units,id', 'from_store_id' => 'required|exists:stores,id', 'to_store_id' => 'required|exists:stores,id', 'from_type' => 'required|in:main,remnant', 'to_type' => 'required|in:main,remnant', 'quantity' => 'required|numeric|gt:0', 'transfer_date' => 'required|date', 'reason' => 'required|max:150', 'notes' => 'nullable']);
        $transfer = $service->transfer($data, $r->user()->id);

        return back()->with('success', "{$transfer->transfer_no} completed without duplicating stock.");
    }
}

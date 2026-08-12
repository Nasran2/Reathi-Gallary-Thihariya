<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('contacts.index', ['type' => 'suppliers', 'records' => Supplier::withCount('purchases')->latest()->paginate(20)]);
    }

    public function store(Request $r)
    {
        Supplier::create($r->validate(['name' => 'required|max:150', 'company' => 'nullable|max:150', 'phone' => 'nullable|max:30', 'whatsapp' => 'nullable|max:30', 'email' => 'nullable|email', 'address' => 'nullable', 'tax_number' => 'nullable|max:100', 'opening_balance' => 'nullable|numeric', 'notes' => 'nullable']) + ['active' => true]);

        return back()->with('success', 'Supplier added.');
    }
}

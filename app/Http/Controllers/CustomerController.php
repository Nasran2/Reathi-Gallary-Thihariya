<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return view('contacts.index', ['type' => 'customers', 'records' => Customer::withCount('sales')->latest()->paginate(20)]);
    }

    public function store(Request $r)
    {
        Customer::create($r->validate(['name' => 'required|max:150', 'mobile' => 'nullable|max:30', 'whatsapp' => 'nullable|max:30', 'email' => 'nullable|email', 'address' => 'nullable', 'group' => 'nullable|max:80', 'credit_limit' => 'nullable|numeric|min:0', 'opening_balance' => 'nullable|numeric', 'notes' => 'nullable']) + ['active' => true]);

        return back()->with('success', 'Customer added.');
    }
}

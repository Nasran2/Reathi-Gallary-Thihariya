<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Store;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index()
    {
        return view('expenses.index', ['expenses' => Expense::with('category', 'paymentMethod')->latest('expense_date')->paginate(20), 'categories' => ExpenseCategory::where('active', 1)->get(), 'methods' => PaymentMethod::where('active', 1)->get(), 'stores' => Store::where('active', 1)->get()]);
    }

    public function store(Request $r)
    {
        Expense::create($r->validate(['expense_category_id' => 'required|exists:expense_categories,id', 'payment_method_id' => 'required|exists:payment_methods,id', 'store_id' => 'required|exists:stores,id', 'expense_date' => 'required|date', 'amount' => 'required|numeric|gt:0', 'reference' => 'nullable|max:100', 'description' => 'nullable']) + ['user_id' => $r->user()->id]);

        return back()->with('success', 'Expense recorded.');
    }
}

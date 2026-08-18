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

    public function categories()
    {
        return view('expenses.categories', ['categories' => ExpenseCategory::withCount('expenses')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $r)
    {
        ExpenseCategory::create($r->validate(['name' => 'required|max:120|unique:expense_categories,name']) + ['active' => true]);

        return back()->with('success', 'Expense category added.');
    }

    public function updateCategory(Request $r, ExpenseCategory $category)
    {
        $category->update($r->validate(['name' => 'required|max:120|unique:expense_categories,name,'.$category->id, 'active' => 'required|boolean']));

        return back()->with('success', 'Expense category updated.');
    }

    public function destroyCategory(ExpenseCategory $category)
    {
        if ($category->expenses()->exists()) {
            return back()->withErrors(['category' => 'Used categories cannot be deleted. Deactivate this category instead.']);
        }
        $category->delete();

        return back()->with('success', 'Expense category deleted.');
    }
}

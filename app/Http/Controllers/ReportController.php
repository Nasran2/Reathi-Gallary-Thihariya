<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function dates(Request $r): array
    {
        if ($r->has('from') && !$r->filled('from') && !$r->filled('to')) {
            return [null, null];
        }
        return [$r->date('from') ?? now()->startOfDay(), $r->date('to') ?? now()->endOfDay()];
    }

    private function render(Request $r, string $view, array $data, string $pdfTitle, string $orientation = 'portrait')
    {
        if ($r->get('export') === 'pdf') {
            $period = 'All Time';
            if (isset($data['from']) && isset($data['to']) && $data['from'] && $data['to']) {
                $period = $data['from']->format('d M Y') . ' to ' . $data['to']->format('d M Y');
            }

            $pdf = Pdf::loadView('reports.pdf', [
                'title' => $pdfTitle,
                'period' => $period,
                'content' => view($view.'-table', $data)->render(),
                'extraCss' => $data['extraCss'] ?? '',
            ])->setPaper('a4', $orientation);

            return $pdf->download(str_replace(' ', '_', $pdfTitle).'_'.now()->format('Ymd').'.pdf');
        }

        return view($view, $data);
    }

    public function lowStock(Request $r)
    {
        $this->authorize('view-reports');

        $query = Product::with(['defaultSupplier', 'baseUnit'])
            ->withSum('balances as total_stock', 'quantity')
            ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM inventory_balances WHERE inventory_balances.product_id = products.id) <= products.minimum_stock OR (SELECT COALESCE(SUM(quantity), 0) FROM inventory_balances WHERE inventory_balances.product_id = products.id) <= products.reorder_level')
            ->where('active', 1);

        if ($r->filled('supplier_id')) {
            $query->where('default_supplier_id', $r->supplier_id);
        }

        $products = $query->paginate(30)->withQueryString();
        $suppliers = Supplier::where('active', 1)->orderBy('name')->get();

        return $this->render($r, 'reports.low-stock', [
            'products' => $products,
            'suppliers' => $suppliers,
        ], 'Low Stock Report', 'landscape');
    }

    public function sales(Request $r)
    {
        $this->authorize('view-reports');
        [$from, $to] = $this->dates($r);

        $query = Sale::with(['customer', 'payments.method']);
        if ($from && $to) {
            $query->whereBetween('sold_at', [$from->startOfDay(), $to->endOfDay()]);
        }

        if ($r->filled('customer_id')) {
            $query->where('customer_id', $r->customer_id);
        }

        if ($r->filled('type')) {
            $query->where('sale_type', $r->type);
        }

        $sales = $query->orderBy('sold_at', 'desc')->get();
        $customers = Customer::orderBy('name')->get();

        return $this->render($r, 'reports.sales', compact('sales', 'customers', 'from', 'to'), 'Sales Report', 'landscape');
    }

    public function purchases(Request $r)
    {
        $this->authorize('view-reports');
        [$from, $to] = $this->dates($r);

        $query = Purchase::with('supplier');
        if ($from && $to) {
            $query->whereBetween('purchase_date', [$from->startOfDay(), $to->endOfDay()]);
        }

        if ($r->filled('supplier_id')) {
            $query->where('supplier_id', $r->supplier_id);
        }

        $purchases = $query->orderBy('purchase_date', 'desc')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return $this->render($r, 'reports.purchases', compact('purchases', 'suppliers', 'from', 'to'), 'Purchase Report', 'landscape');
    }

    public function expenses(Request $r)
    {
        $this->authorize('view-reports');
        [$from, $to] = $this->dates($r);

        $query = Expense::with('category');
        if ($from && $to) {
            $query->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);
        }

        if ($r->filled('category_id')) {
            $query->where('expense_category_id', $r->category_id);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();
        $categories = ExpenseCategory::orderBy('name')->get();

        return $this->render($r, 'reports.expenses', compact('expenses', 'categories', 'from', 'to'), 'Expense Report');
    }

    public function dueBills(Request $r)
    {
        $this->authorize('view-reports');
        [$from, $to] = $this->dates($r);

        $query = Sale::with('customer')->where('due_total', '>', 0);

        if ($r->filled('customer_id')) {
            $query->where('customer_id', $r->customer_id);
        }

        $sales = $query->orderBy('sold_at', 'desc')->get();
        $customers = Customer::orderBy('name')->get();

        return $this->render($r, 'reports.due-bills', compact('sales', 'customers', 'from', 'to'), 'Due Bills Report');
    }

    public function customerDue(Request $r)
    {
        $this->authorize('view-reports');

        $balanceSql = "COALESCE((SELECT balance_after FROM customer_ledger WHERE customer_id = customers.id ORDER BY id DESC LIMIT 1), opening_balance) - COALESCE((SELECT SUM(amount) FROM customer_payments WHERE customer_id = customers.id AND status = 'pending'),0)";
        $query = Customer::select('customers.*')->selectRaw("CASE WHEN ({$balanceSql}) > 0 THEN ({$balanceSql}) ELSE 0 END AS sales_sum_due_total")->whereRaw("({$balanceSql}) > 0");

        if ($r->filled('customer_id')) {
            $query->where('id', $r->customer_id);
        }

        $customers_due = $query->orderByDesc('sales_sum_due_total')->get();
        $customers = Customer::orderBy('name')->get();

        // Customer due doesn't need date filters usually as it's a current snapshot,
        // but we pass dummy dates to satisfy the filter component if needed.
        $from = now();
        $to = now();

        return $this->render($r, 'reports.customer-due', compact('customers_due', 'customers', 'from', 'to'), 'Customer Due Report');
    }

    public function supplierDue(Request $r)
    {
        $this->authorize('reports.supplier_due');

        $balanceSql = "COALESCE((SELECT balance_after FROM supplier_ledger WHERE supplier_id = suppliers.id ORDER BY id DESC LIMIT 1), opening_balance) - COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_id = suppliers.id AND status = 'pending'),0)";
        $query = Supplier::select('suppliers.*')
            ->selectRaw("CASE WHEN ({$balanceSql}) > 0 THEN ({$balanceSql}) ELSE 0 END AS due_total")
            ->whereRaw("({$balanceSql}) > 0");

        if ($r->filled('supplier_id')) {
            $query->where('id', $r->integer('supplier_id'));
        }

        $suppliersDue = $query->orderByDesc('due_total')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $from = now();
        $to = now();

        return $this->render($r, 'reports.supplier-due', compact('suppliersDue', 'suppliers', 'from', 'to'), 'Supplier Due Report');
    }

    public function dailyClosing(Request $r)
    {
        $this->authorize('view-reports');
        $date = $r->date('from') ?? now();
        $from = clone $date;
        $to = clone $date;

        $sales = Sale::whereBetween('sold_at', [$date->startOfDay(), $date->endOfDay()])->get();
        $purchases = Purchase::whereBetween('purchase_date', [$date->startOfDay(), $date->endOfDay()])->get();
        $expenses = Expense::whereDate('expense_date', $date->toDateString())->get();

        $data = compact('sales', 'purchases', 'expenses', 'date', 'from', 'to');

        return $this->render($r, 'reports.daily-closing', $data, 'Daily Closing Report - '.$date->format('Y-m-d'));
    }

    public function ledger(Request $r)
    {
        $this->authorize('reports.ledger');
        [$from, $to] = $this->dates($r);

        $salesQuery = Sale::with(['customer', 'payments.method']);
        $expensesQuery = Expense::with(['category', 'paymentMethod']);

        if ($from && $to) {
            $salesQuery->whereBetween('sold_at', [$from->startOfDay(), $to->endOfDay()]);
            // Assuming expense_date is date or datetime
            $expensesQuery->whereBetween('expense_date', [$from->startOfDay(), $to->endOfDay()]);
        }
        
        $returnsQuery = SaleReturn::with('customer')->where('refund_amount', '>', 0);
        if ($from && $to) {
            $returnsQuery->whereBetween('return_date', [$from->startOfDay(), $to->endOfDay()]);
        }
        $returnsList = $returnsQuery->get();
        $salesList = $salesQuery->get();
        $expensesList = $expensesQuery->get();

        $sales = $salesList->map(function($sale) {
            $methods = collect($sale->payments)->map(function($p) {
                if ($p->method && strtolower($p->method->code) !== 'cash') {
                    return (object)['code' => $p->method->code, 'name' => $p->method->name];
                }
                return null;
            })->filter()->unique('code')->values()->all();
            
            return (object)[
                'date' => $sale->sold_at instanceof \Carbon\Carbon ? $sale->sold_at : \Carbon\Carbon::parse($sale->sold_at),
                'type' => 'Sale',
                'reference' => $sale->invoice_no,
                'description' => 'Sale to ' . ($sale->customer->name ?? 'Walk-in'),
                'incoming' => (float) $sale->grand_total,
                'outgoing' => 0,
                'methods' => $methods,
            ];
        });

        $expenses = $expensesList->map(function($expense) {
            $methods = [];
            if ($expense->paymentMethod && strtolower($expense->paymentMethod->code) !== 'cash') {
                $methods[] = (object)['code' => $expense->paymentMethod->code, 'name' => $expense->paymentMethod->name];
            }
            return (object)[
                'date' => $expense->expense_date instanceof \Carbon\Carbon ? $expense->expense_date : \Carbon\Carbon::parse($expense->expense_date),
                'type' => 'Expense',
                'reference' => 'EXP-'.str_pad($expense->id, 5, '0', STR_PAD_LEFT),
                'description' => $expense->category->name ?? 'Expense',
                'incoming' => 0,
                'outgoing' => (float) $expense->amount,
                'methods' => $methods,
            ];
        });

        $refunds = $returnsList->map(function($return) {
            return (object)[
                'date' => $return->return_date instanceof \Carbon\Carbon ? $return->return_date : \Carbon\Carbon::parse($return->return_date),
                'type' => 'Cash Refund',
                'reference' => $return->return_no,
                'description' => 'Return Refund ' . ($return->is_manual ? '(Manual) ' : '') . 'to ' . ($return->customer->name ?? 'Walk-in'),
                'incoming' => 0,
                'outgoing' => (float) $return->refund_amount,
                'methods' => [], // Default Cash
            ];
        });

        // 1. Calculate Card Fees / Bank Charges from sales payments
        $salesCardFeeTotal = 0;
        foreach ($salesList as $sale) {
            foreach ($sale->payments as $payment) {
                if ($payment->method && $payment->method->bank_charge_percentage > 0) {
                    $salesCardFeeTotal += (float) $payment->amount * ((float) $payment->method->bank_charge_percentage / 100);
                }
            }
        }

        // 2. Identify Bank Charges already recorded in Expenses
        $isBankCharge = function($expense) {
            $catName = strtolower($expense->category->name ?? '');
            $desc = strtolower($expense->description ?? '');
            return $catName === 'bank charges' || str_contains($desc, 'card fee') || str_contains($desc, 'bank charge') || str_contains($desc, 'bank fee');
        };

        $bankChargesInExpenses = $expensesList->filter($isBankCharge);
        $recordedBankCharges = (float) $bankChargesInExpenses->sum('amount');
        $cardFeeTotal = max($salesCardFeeTotal, $recordedBankCharges);
        $totalBankCharges = $cardFeeTotal;

        // Only push unrecorded bank charges to the ledger if they haven't been stored in expenses table
        $missingBankFee = $salesCardFeeTotal - $recordedBankCharges;
        if ($missingBankFee > 0.01) {
            $expenses->push((object)[
                'date' => $to ? $to->endOfDay() : now(),
                'type' => 'Expense',
                'reference' => 'FEE-'.now()->format('Ymd'),
                'description' => 'Payment Gateway / Card Fees',
                'incoming' => 0,
                'outgoing' => $missingBankFee,
                'methods' => [(object)['code' => 'card', 'name' => 'Card']],
            ]);
        }

        // Operational Expenses without bank charges
        $expensesWithoutBankCharges = (float) $expensesList->reject($isBankCharge)->sum('amount');

        $ledger = $sales->concat($expenses)->concat($refunds)->sortBy('date')->values();
        
        $totalIncoming = (float) $ledger->sum('incoming');
        $totalOutgoing = (float) $ledger->sum('outgoing');
        $netTotal = $totalIncoming - $totalOutgoing;
        
        $totalDue = (float) $salesList->sum('due_total');
        $cardTotal = 0;
        $bankTotal = 0;
        $cashCollection = 0;
        $chequeTotal = 0;
        
        foreach ($salesList as $sale) {
            foreach ($sale->payments as $payment) {
                $code = strtolower(optional($payment->method)->code ?? '');
                $amount = (float) $payment->amount;
                if ($code === 'card') {
                    $cardTotal += $amount;
                } elseif ($code === 'bank_transfer' || $code === 'bank') {
                    $bankTotal += $amount;
                } elseif ($code === 'cash') {
                    $cashCollection += $amount;
                } elseif (in_array($code, ['cheque', 'own_cheque', 'endorsed_cheque'])) {
                    $chequeTotal += $amount;
                }
            }
        }

        // Cash drawer deductions: Cash Expenses & Cash Refunds
        $cashExpenses = (float) $expensesList->filter(function($expense) {
            $code = strtolower(optional($expense->paymentMethod)->code ?? '');
            return $code === 'cash';
        })->sum('amount');

        $cashRefunds = (float) $returnsList->sum('refund_amount');

        // Final Cash in Hand / Cash Balance: Cash Collection minus Cash Expenses and Cash Refunds
        $cashBalance = $cashCollection - $cashExpenses - $cashRefunds;
        $cashTotal = $cashBalance; // Keep backward compatibility

        $extraCss = '
            .summary-grid { display: block; width: 100%; margin-bottom: 20px; }
            .summary-card { float: left; width: 22.8%; margin: 1%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; background: #f8fafc; font-family: sans-serif; }
            .summary-clearfix:after { content: ""; display: table; clear: both; }
            .card-title { font-size: 8px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; color: #475569; }
            .card-note { font-size: 7.5px; margin-bottom: 4px; color: #64748b; }
            .card-val { font-size: 13px; font-weight: bold; color: #0f172a; }
        ';

        return $this->render($r, 'reports.ledger', compact(
            'ledger',
            'totalIncoming',
            'totalOutgoing',
            'netTotal',
            'totalDue',
            'cardTotal',
            'bankTotal',
            'chequeTotal',
            'cashCollection',
            'cashExpenses',
            'cashRefunds',
            'cashBalance',
            'cashTotal',
            'cardFeeTotal',
            'totalBankCharges',
            'expensesWithoutBankCharges',
            'from',
            'to',
            'extraCss'
        ), 'Daily Ledger Report');
    }

    public function profit(Request $r)
    {
        $this->authorize('view-reports');
        [$from, $to] = $this->dates($r);
        $base = Sale::where('status', 'completed');
        if ($from && $to) {
            $base->whereBetween('sold_at', [$from->startOfDay(), $to->endOfDay()]);
        }

        $main = clone $base;
        $mainData = $main->where('sale_type', 'main')->selectRaw('COALESCE(SUM(grand_total),0) sales, COALESCE(SUM(cost_total),0) cogs, COALESCE(SUM(profit_total),0) profit')->first();

        $remnant = clone $base;
        $remnantData = $remnant->where('sale_type', 'remnant')->selectRaw('COALESCE(SUM(grand_total),0) sales, COALESCE(SUM(cost_total),0) cogs, COALESCE(SUM(profit_total),0) profit')->first();
        foreach (['main' => $mainData, 'remnant' => $remnantData] as $saleType => $row) {
            $returnsQuery = SaleReturn::join('sales', 'sales.id', '=', 'sale_returns.sale_id')->where('sales.sale_type', $saleType);
            if ($from && $to) {
                $returnsQuery->whereBetween('sale_returns.return_date', [$from->toDateString(), $to->toDateString()]);
            }
            $returns = $returnsQuery->selectRaw('COALESCE(SUM(return_total),0) revenue, COALESCE(SUM(sale_returns.cost_total),0) cost')->first();
            $row->sales -= $returns->revenue;
            $row->cogs -= $returns->cost;
            $row->profit -= ($returns->revenue - $returns->cost);
        }

        $expensesQuery = Expense::query();
        if ($from && $to) {
            $expensesQuery->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);
        }
        $expenses = $expensesQuery->sum('amount');

        return $this->render($r, 'reports.profit', [
            'main' => $mainData,
            'remnant' => $remnantData,
            'expenses' => $expenses,
            'from' => $from,
            'to' => $to,
        ], 'Profit & Loss Report');
    }

    public function valuation(Request $r)
    {
        $this->authorize('view-reports');
        $type = $r->get('type', 'combined');

        $query = Product::with(['category', 'baseUnit', 'balances', 'productUnits'])->whereHas('balances');

        if ($r->filled('category_id')) {
            $query->where('category_id', $r->category_id);
        }

        if ($r->filled('base_unit_id')) {
            $query->where('base_unit_id', $r->base_unit_id);
        }

        $rows = $query->get()->map(function ($p) use ($type) {
            $main = $p->balances->where('inventory_type', 'main')->sum('quantity');
            $remnant = $p->balances->where('inventory_type', 'remnant')->sum('quantity');
            $qty = $type === 'main' ? $main : ($type === 'remnant' ? $remnant : $main + $remnant);
            $mainPrice = $p->main_selling_price ?? 0;
            $remnantPrice = $p->remnant_selling_price ?? 0;

            if ($type === 'main') {
                $sellingValue = $main * $mainPrice;
            } elseif ($type === 'remnant') {
                $sellingValue = $remnant * $remnantPrice;
            } else {
                $sellingValue = ($main * $mainPrice) + ($remnant * $remnantPrice);
            }

            return compact('p', 'main', 'remnant', 'qty', 'mainPrice', 'remnantPrice') + [
                'costValue' => $qty * $p->average_cost,
                'sellingValue' => $sellingValue,
            ];
        })->filter(function ($row) {
            return $row['qty'] > 0;
        });

        $categories = Category::orderBy('name')->get();
        $units = \App\Models\Unit::orderBy('name')->get();
        $from = now();
        $to = now();

        return $this->render($r, 'reports.valuation', compact('rows', 'type', 'categories', 'units', 'from', 'to'), 'Stock Valuation Report');
    }

    public function deadStock(Request $r)
    {
        $this->authorize('view-reports');
        $days = (int) $r->get('days', 90);

        $query = Product::with(['category', 'baseUnit', 'balances', 'productUnits'])
            ->addSelect(['last_sale_at' => SaleItem::selectRaw('MAX(sales.sold_at)')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereColumn('sale_items.product_id', 'products.id')
                ->where('sales.status', 'completed'),
            ])
            ->whereDoesntHave('saleItems.sale', fn ($sale) => $sale->where('status', 'completed')->where('sold_at', '>=', now()->subDays($days)));

        if ($r->filled('category_id')) {
            $query->where('products.category_id', $r->category_id);
        }

        $products = $query->get();
        $categories = Category::orderBy('name')->get();
        $from = now();
        $to = now();

        return $this->render($r, 'reports.dead-stock', compact('products', 'days', 'categories', 'from', 'to'), 'Dead Stock Report');
    }

    public function trendingProducts(Request $r)
    {
        if (!auth()->user()->can('reports.trending') && !auth()->user()->can('reports.sales') && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        [$from, $to] = $this->dates($r);
        // Default to last 30 days if no custom dates are specified
        if (!$r->has('from') && !$r->has('to')) {
            $from = now()->subDays(30)->startOfDay();
            $to = now()->endOfDay();
        }

        $sortBy = $r->get('sort_by', 'quantity');
        $sortDir = strtolower($r->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $limit = $r->get('limit', '50');

        $query = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('units', 'units.id', '=', 'products.base_unit_id')
            ->where('sales.status', 'completed');

        if ($from && $to) {
            $query->whereBetween('sales.sold_at', [$from->startOfDay(), $to->endOfDay()]);
        }

        if ($r->filled('category_id')) {
            $query->where('products.category_id', $r->category_id);
        }

        if ($r->filled('search')) {
            $search = trim($r->search);
            $query->where(function($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.sku', 'like', "%{$search}%")
                  ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        $query->selectRaw('
            products.id as product_id,
            products.name as product_name,
            products.sku,
            products.barcode,
            products.main_selling_price,
            products.average_cost,
            categories.name as category_name,
            units.name as unit_name,
            COALESCE((SELECT SUM(quantity) FROM inventory_balances WHERE inventory_balances.product_id = products.id), 0) as current_stock,
            SUM(sale_items.quantity) as total_qty_sold,
            SUM(sale_items.base_quantity) as total_base_qty_sold,
            SUM(sale_items.net_revenue) as total_revenue,
            SUM(sale_items.cost_total) as total_cost,
            SUM(sale_items.profit) as total_profit,
            COUNT(DISTINCT sales.id) as orders_count,
            MAX(sales.sold_at) as last_sold_at
        ')
        ->groupBy(
            'products.id',
            'products.name',
            'products.sku',
            'products.barcode',
            'products.main_selling_price',
            'products.average_cost',
            'categories.name',
            'units.name'
        );

        if ($sortBy === 'revenue') {
            $query->orderBy('total_revenue', $sortDir);
        } elseif ($sortBy === 'profit') {
            $query->orderBy('total_profit', $sortDir);
        } elseif ($sortBy === 'orders') {
            $query->orderBy('orders_count', $sortDir);
        } else {
            $query->orderBy('total_qty_sold', $sortDir);
        }

        $daysInPeriod = ($from && $to) ? max(1, $from->diffInDays($to) + 1) : 30;

        $allTrending = $query->get();

        $totalUnitsSold = (float) $allTrending->sum('total_qty_sold');
        $totalRevenue = (float) $allTrending->sum('total_revenue');
        $totalProfit = (float) $allTrending->sum('total_profit');
        $overallMargin = $totalRevenue > 0 ? (($totalProfit / $totalRevenue) * 100) : 0;
        $topProduct = $allTrending->first();

        $trendingProducts = ($limit !== 'all' && is_numeric($limit)) 
            ? $allTrending->take((int) $limit) 
            : $allTrending;

        $trendingProducts = $trendingProducts->map(function($item, $index) use ($daysInPeriod, $totalUnitsSold) {
            $item->rank = $index + 1;
            $item->daily_velocity = $daysInPeriod > 0 ? ((float) $item->total_qty_sold / $daysInPeriod) : (float) $item->total_qty_sold;
            $item->profit_margin = (float) $item->total_revenue > 0 ? (((float) $item->total_profit / (float) $item->total_revenue) * 100) : 0;
            $item->avg_price = (float) $item->total_qty_sold > 0 ? ((float) $item->total_revenue / (float) $item->total_qty_sold) : 0;
            $item->volume_share = $totalUnitsSold > 0 ? (((float) $item->total_qty_sold / $totalUnitsSold) * 100) : 0;
            return $item;
        });

        $categories = Category::where('active', 1)->orderBy('name')->get();

        $extraCss = '
            .summary-table { width: 100%; border-collapse: separate; border-spacing: 6px; margin-bottom: 15px; }
            .summary-table td { padding: 10px; border-radius: 6px; vertical-align: top; }
        ';

        return $this->render($r, 'reports.trending-products', compact(
            'trendingProducts',
            'categories',
            'from',
            'to',
            'sortBy',
            'sortDir',
            'limit',
            'totalUnitsSold',
            'totalRevenue',
            'totalProfit',
            'overallMargin',
            'topProduct',
            'daysInPeriod',
            'extraCss'
        ), 'Trending Sales Products Report', 'landscape');
    }
}

@php
    $totalSales = $sales->sum('grand_total');
    $totalPaid = $sales->sum('paid_total');
    $totalDue = $sales->sum('due_total');
    
    $totalPurchases = $purchases->sum('total_amount');
    $totalExpenses = $expenses->sum('amount');
    
    $netCash = $totalPaid - ($totalPurchases + $totalExpenses);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-indigo-50 border border-indigo-100 p-5 rounded-xl">
        <h4 class="text-sm font-semibold text-indigo-800 uppercase tracking-wider mb-2">Total Sales</h4>
        <div class="text-2xl font-bold text-indigo-900">Rs. {{ number_format($totalSales, 2) }}</div>
        <div class="text-sm text-indigo-600 mt-1">{{ $sales->count() }} Invoices</div>
    </div>
    <div class="bg-emerald-50 border border-emerald-100 p-5 rounded-xl">
        <h4 class="text-sm font-semibold text-emerald-800 uppercase tracking-wider mb-2">Sales Collection</h4>
        <div class="text-2xl font-bold text-emerald-900">Rs. {{ number_format($totalPaid, 2) }}</div>
        <div class="text-sm text-emerald-600 mt-1">Due: Rs. {{ number_format($totalDue, 2) }}</div>
    </div>
    <div class="bg-rose-50 border border-rose-100 p-5 rounded-xl">
        <h4 class="text-sm font-semibold text-rose-800 uppercase tracking-wider mb-2">Total Outward</h4>
        <div class="text-2xl font-bold text-rose-900">Rs. {{ number_format($totalPurchases + $totalExpenses, 2) }}</div>
        <div class="text-sm text-rose-600 mt-1">Purchases + Expenses</div>
    </div>
    <div class="bg-slate-800 border border-slate-900 p-5 rounded-xl text-white">
        <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-2">Net Cash Flow</h4>
        <div class="text-2xl font-bold {{ $netCash >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">Rs. {{ number_format($netCash, 2) }}</div>
        <div class="text-sm text-slate-400 mt-1">For {{ $date->format('Y-m-d') }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div>
        <h3 class="font-bold text-lg text-slate-800 mb-3 border-b border-slate-200 pb-2">Sales Breakdown</h3>
        <table class="w-full text-sm">
            @forelse($sales as $sale)
                <tr class="border-b border-slate-100">
                    <td class="py-2">{{ $sale->invoice_no }}</td>
                    <td class="py-2 text-right">Rs. {{ number_format($sale->paid_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="py-4 text-center text-slate-500">No sales today</td></tr>
            @endforelse
        </table>
    </div>
    
    <div>
        <h3 class="font-bold text-lg text-slate-800 mb-3 border-b border-slate-200 pb-2">Purchases</h3>
        <table class="w-full text-sm">
            @forelse($purchases as $purchase)
                <tr class="border-b border-slate-100">
                    <td class="py-2">{{ $purchase->reference_no ?? 'Ref' }}</td>
                    <td class="py-2 text-right text-rose-600">Rs. {{ number_format($purchase->total_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="py-4 text-center text-slate-500">No purchases today</td></tr>
            @endforelse
        </table>
    </div>
    
    <div>
        <h3 class="font-bold text-lg text-slate-800 mb-3 border-b border-slate-200 pb-2">Expenses</h3>
        <table class="w-full text-sm">
            @forelse($expenses as $expense)
                <tr class="border-b border-slate-100">
                    <td class="py-2">{{ $expense->category?->name ?? 'Misc' }}</td>
                    <td class="py-2 text-right text-rose-600">Rs. {{ number_format($expense->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="py-4 text-center text-slate-500">No expenses today</td></tr>
            @endforelse
        </table>
    </div>
</div>

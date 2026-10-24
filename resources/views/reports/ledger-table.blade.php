<div class="summary-grid summary-clearfix grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    {{-- Row 1: Overall Financial Overview --}}
    <div class="summary-card bg-emerald-50 border border-emerald-100 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-emerald-800 uppercase tracking-wider">Total Incoming (Sales)</h4>
            <i class="ti ti-arrow-down-left text-emerald-600 text-base"></i>
        </div>
        <div class="card-note text-xs text-emerald-600/80 mb-2">Total sales revenue</div>
        <div class="card-val text-2xl font-bold text-emerald-950">Rs. {{ number_format($totalIncoming, 2) }}</div>
    </div>

    <div class="summary-card bg-rose-50 border border-rose-100 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-rose-800 uppercase tracking-wider">Total Expenses</h4>
            <i class="ti ti-receipt text-rose-600 text-base"></i>
        </div>
        <div class="card-note text-xs text-rose-600 font-medium mb-2">Without Bank Charges</div>
        <div class="card-val text-2xl font-bold text-rose-950">Rs. {{ number_format($expensesWithoutBankCharges ?? $totalOutgoing, 2) }}</div>
    </div>

    <div class="summary-card bg-red-50 border border-red-100 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-red-800 uppercase tracking-wider">Bank Charges / Card Fee</h4>
            <i class="ti ti-credit-card-refund text-red-600 text-base"></i>
        </div>
        <div class="card-note text-xs text-red-600 font-medium mb-2">Bank Fee Expense</div>
        <div class="card-val text-2xl font-bold text-red-950">Rs. {{ number_format($totalBankCharges ?? $cardFeeTotal, 2) }}</div>
    </div>

    <div class="summary-card bg-slate-900 border border-slate-800 p-4 rounded-xl text-white shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-slate-300 uppercase tracking-wider">Net Balance (In - Out)</h4>
            <i class="ti ti-scale text-slate-400 text-base"></i>
        </div>
        <div class="card-note text-xs text-slate-400 mb-2">Incoming − All Outgoing</div>
        <div class="card-val text-2xl font-bold {{ $netTotal >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">Rs. {{ number_format($netTotal, 2) }}</div>
    </div>

    {{-- Row 2: Cash Reconciliation / Cash in Hand --}}
    <div class="summary-card bg-amber-50 border border-amber-100 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-amber-800 uppercase tracking-wider">Cash Collection</h4>
            <i class="ti ti-cash text-amber-600 text-base"></i>
        </div>
        <div class="card-note text-xs text-amber-600 font-medium mb-2">Sales Cash Received</div>
        <div class="card-val text-2xl font-bold text-amber-950">Rs. {{ number_format($cashCollection ?? $cashTotal, 2) }}</div>
    </div>

    <div class="summary-card bg-rose-50 border border-rose-200/60 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-rose-800 uppercase tracking-wider">Cash Expenses</h4>
            <i class="ti ti-cash-off text-rose-600 text-base"></i>
        </div>
        <div class="card-note text-xs text-rose-600 font-medium mb-2">Paid from Cash Drawer</div>
        <div class="card-val text-2xl font-bold text-rose-900">- Rs. {{ number_format($cashExpenses ?? 0, 2) }}</div>
    </div>

    <div class="summary-card bg-slate-50 border border-slate-200 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-slate-700 uppercase tracking-wider">Cash Refunds</h4>
            <i class="ti ti-arrow-back-up text-slate-500 text-base"></i>
        </div>
        <div class="card-note text-xs text-slate-500 mb-2">Customer Returns Refunded</div>
        <div class="card-val text-2xl font-bold text-slate-800">- Rs. {{ number_format($cashRefunds ?? 0, 2) }}</div>
    </div>

    <div class="summary-card bg-emerald-600 border border-emerald-700 p-4 rounded-xl text-white shadow-md shadow-emerald-600/15">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-emerald-100 uppercase tracking-wider">Final Cash in Hand</h4>
            <i class="ti ti-wallet text-white text-base"></i>
        </div>
        <div class="card-note text-xs text-emerald-100 font-medium mb-2">Cash Balance in Drawer</div>
        <div class="card-val text-2xl font-black text-white">Rs. {{ number_format($cashBalance ?? $cashTotal, 2) }}</div>
    </div>

    {{-- Row 3: Other Channels & Outgoing Overview --}}
    <div class="summary-card bg-blue-50 border border-blue-100 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-blue-800 uppercase tracking-wider">Card Collection</h4>
            <i class="ti ti-credit-card text-blue-600 text-base"></i>
        </div>
        <div class="card-note text-xs text-blue-600/80 mb-2">Credit / Debit Card</div>
        <div class="card-val text-2xl font-bold text-blue-950">Rs. {{ number_format($cardTotal, 2) }}</div>
    </div>

    <div class="summary-card bg-purple-50 border border-purple-100 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-purple-800 uppercase tracking-wider">Bank Collection</h4>
            <i class="ti ti-building-bank text-purple-600 text-base"></i>
        </div>
        <div class="card-note text-xs text-purple-600/80 mb-2">Direct Bank Transfers</div>
        <div class="card-val text-2xl font-bold text-purple-950">Rs. {{ number_format($bankTotal ?? 0, 2) }}</div>
    </div>

    <div class="summary-card bg-amber-50 border border-amber-100 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-amber-800 uppercase tracking-wider">Total Due</h4>
            <i class="ti ti-clock-dollar text-amber-600 text-base"></i>
        </div>
        <div class="card-note text-xs text-amber-600/80 mb-2">Pending Customer Credit</div>
        <div class="card-val text-2xl font-bold text-amber-950">Rs. {{ number_format($totalDue, 2) }}</div>
    </div>

    <div class="summary-card bg-rose-100/60 border border-rose-200 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-rose-900 uppercase tracking-wider">Total Outgoing (All)</h4>
            <i class="ti ti-arrow-up-right text-rose-700 text-base"></i>
        </div>
        <div class="card-note text-xs text-rose-700/80 mb-2">Expenses + Fees + Refunds</div>
        <div class="card-val text-2xl font-bold text-rose-950">Rs. {{ number_format($totalOutgoing, 2) }}</div>
    </div>
</div>
<div style="clear: both;"></div>

<h3 class="font-bold text-lg text-slate-800 mb-3 border-b border-slate-200 pb-2">Ledger Transactions</h3>
<table class="w-full text-sm text-left">
    <thead>
        <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider">
            <th class="py-3 font-semibold">Date & Time</th>
            <th class="py-3 font-semibold">Type</th>
            <th class="py-3 font-semibold">Reference</th>
            <th class="py-3 font-semibold">Description</th>
            <th class="py-3 text-right font-semibold">Incoming (Rs.)</th>
            <th class="py-3 text-right font-semibold">Outgoing (Rs.)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ledger as $row)
        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors {{ collect($row->methods)->contains('code', 'card') ? 'bg-blue-50/50' : '' }}">
            <td class="py-2.5">{{ ($row->date instanceof \Carbon\Carbon ? $row->date : \Carbon\Carbon::parse($row->date))->format('Y-m-d h:i A') }}</td>
            <td class="py-2.5 flex flex-wrap gap-1 items-center">
                @php
                    $typeClass = match($row->type) {
                        'Sale' => 'bg-emerald-100 text-emerald-800',
                        'Cash Refund' => 'bg-amber-100 text-amber-800',
                        default => 'bg-rose-100 text-rose-800'
                    };
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeClass }}">
                    {{ $row->type }}
                </span>
                @foreach($row->methods as $m)
                    @php
                        $colorClass = match(strtolower($m->code)) {
                            'card' => 'bg-blue-100 text-blue-800',
                            'bank_transfer' => 'bg-purple-100 text-purple-800',
                            'cheque' => 'bg-orange-100 text-orange-800',
                            default => 'bg-slate-200 text-slate-800'
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $colorClass }}">
                        {{ $m->name }}
                    </span>
                @endforeach
            </td>
            <td class="py-2.5 font-medium">{{ $row->reference }}</td>
            <td class="py-2.5 text-slate-600">{{ $row->description }}</td>
            <td class="py-2.5 text-right font-medium {{ $row->incoming > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                {{ $row->incoming > 0 ? number_format($row->incoming, 2) : '-' }}
            </td>
            <td class="py-2.5 text-right font-medium {{ $row->outgoing > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                {{ $row->outgoing > 0 ? number_format($row->outgoing, 2) : '-' }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="py-8 text-center text-slate-500">
                <i class="ti ti-receipt text-3xl mb-2 opacity-50 block"></i>
                No ledger transactions found for the selected period.
            </td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="bg-slate-50 font-bold border-t-2 border-slate-200 text-slate-800">
            <td colspan="4" class="py-3 text-right">Total:</td>
            <td class="py-3 text-right text-emerald-600">Rs. {{ number_format($totalIncoming, 2) }}</td>
            <td class="py-3 text-right text-rose-600">Rs. {{ number_format($totalOutgoing, 2) }}</td>
        </tr>
    </tfoot>
</table>
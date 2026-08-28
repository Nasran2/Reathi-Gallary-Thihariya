<div class="grid grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-emerald-50 border border-emerald-100 p-5 rounded-xl">
        <h4 class="text-sm font-semibold text-emerald-800 uppercase tracking-wider mb-2">Total Incoming (Sales)</h4>
        <div class="text-2xl font-bold text-emerald-900">Rs. {{ number_format($totalIncoming, 2) }}</div>
    </div>
    <div class="bg-rose-50 border border-rose-100 p-5 rounded-xl">
        <h4 class="text-sm font-semibold text-rose-800 uppercase tracking-wider mb-2">Total Outgoing (Expenses)</h4>
        <div class="text-2xl font-bold text-rose-900">Rs. {{ number_format($totalOutgoing, 2) }}</div>
    </div>
    <div class="bg-blue-50 border border-blue-100 p-5 rounded-xl">
        <h4 class="text-sm font-semibold text-blue-800 uppercase tracking-wider mb-2">Bank Collection</h4>
        <div class="text-2xl font-bold text-blue-900">Rs. {{ number_format($bankTotal, 2) }}</div>
    </div>
    <div class="bg-amber-50 border border-amber-100 p-5 rounded-xl">
        <h4 class="text-sm font-semibold text-amber-800 uppercase tracking-wider mb-2">Total Due</h4>
        <div class="text-2xl font-bold text-amber-900">Rs. {{ number_format($totalDue, 2) }}</div>
    </div>
    <div class="bg-slate-800 border border-slate-900 p-5 rounded-xl text-white">
        <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-2">Net Balance (In - Out)</h4>
        <div class="text-2xl font-bold {{ $netTotal >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">Rs. {{ number_format($netTotal, 2) }}</div>
    </div>
</div>

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
            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                <td class="py-2.5">{{ $row->date->format('Y-m-d h:i A') }}</td>
                <td class="py-2.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $row->type === 'Sale' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $row->type }}
                    </span>
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

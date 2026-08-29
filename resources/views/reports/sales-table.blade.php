<table class="w-full text-left text-sm text-slate-500">
    <thead class="bg-slate-50 text-xs uppercase text-slate-700">
        <tr>
            <th class="px-6 py-3">Date</th>
            <th class="px-6 py-3">Invoice No</th>
            <th class="px-6 py-3">Type</th>
            <th class="px-6 py-3">Customer</th>
            <th class="px-6 py-3">Payment Method</th>
            <th class="px-6 py-3 text-right">Total Amount</th>
            <th class="px-6 py-3 text-right">Paid Amount</th>
            <th class="px-6 py-3 text-right">Due Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sales as $sale)
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-6 py-4 whitespace-nowrap">{{ $sale->sold_at->format('Y-m-d') }}</td>
                <td class="px-6 py-4 font-bold text-slate-800">{{ $sale->invoice_no }}</td>
                <td class="px-6 py-4 uppercase text-xs">{{ $sale->sale_type }}</td>
                <td class="px-6 py-4">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</td>
                <td class="px-6 py-4">
                    {{ $sale->payments->map(fn($p) => $p->method?->name)->filter()->unique()->implode(', ') ?: '-' }}
                </td>
                <td class="px-6 py-4 text-right font-semibold text-slate-800">Rs. {{ number_format($sale->grand_total, 2) }}</td>
                <td class="px-6 py-4 text-right text-emerald-600">Rs. {{ number_format($sale->paid_total, 2) }}</td>
                <td class="px-6 py-4 text-right text-amber-600">Rs. {{ number_format($sale->due_total, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-6 py-12 text-center">
                    <h3 class="text-lg font-bold text-slate-700 mb-1">No records found for this period.</h3>
                    <p class="text-slate-500">Try changing the date range or filters.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
    @if($sales->count() > 0)
    <tfoot class="bg-slate-50 font-bold text-slate-800">
        <tr>
            <td colspan="5" class="px-6 py-4 text-right uppercase text-xs">Total</td>
            <td class="px-6 py-4 text-right">Rs. {{ number_format($sales->sum('grand_total'), 2) }}</td>
            <td class="px-6 py-4 text-right text-emerald-600">Rs. {{ number_format($sales->sum('paid_total'), 2) }}</td>
            <td class="px-6 py-4 text-right text-amber-600">Rs. {{ number_format($sales->sum('due_total'), 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

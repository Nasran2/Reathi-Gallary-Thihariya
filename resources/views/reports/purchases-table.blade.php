<table class="w-full text-left text-sm text-slate-500">
    <thead class="bg-slate-50 text-xs uppercase text-slate-700">
        <tr>
            <th class="px-6 py-3">Date</th>
            <th class="px-6 py-3">Reference</th>
            <th class="px-6 py-3">Supplier</th>
            <th class="px-6 py-3 text-right">Total Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse($purchases as $purchase)
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-6 py-4 whitespace-nowrap">{{ $purchase->purchase_date?->format('Y-m-d') }}</td>
                <td class="px-6 py-4 font-bold text-slate-800">{{ $purchase->reference_no ?? 'N/A' }}</td>
                <td class="px-6 py-4">{{ $purchase->supplier?->name ?? 'N/A' }}</td>
                <td class="px-6 py-4 text-right font-semibold text-slate-800">Rs. {{ number_format($purchase->supplier_total, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-12 text-center">
                    <h3 class="text-lg font-bold text-slate-700 mb-1">No records found for this period.</h3>
                    <p class="text-slate-500">Try changing the date range or filters.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
    @if($purchases->count() > 0)
    <tfoot class="bg-slate-50 font-bold text-slate-800">
        <tr>
            <td colspan="3" class="px-6 py-4 text-right uppercase text-xs">Total</td>
            <td class="px-6 py-4 text-right">Rs. {{ number_format($purchases->sum('supplier_total'), 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

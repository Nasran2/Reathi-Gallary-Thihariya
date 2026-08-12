<table class="w-full text-left text-sm text-slate-500">
    <thead class="bg-slate-50 text-xs uppercase text-slate-700">
        <tr>
            <th class="px-6 py-3">Customer Name</th>
            <th class="px-6 py-3">Phone</th>
            <th class="px-6 py-3 text-right text-amber-600">Total Due Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse($customers_due as $customer)
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-6 py-4 font-bold text-slate-800">{{ $customer->name }}</td>
                <td class="px-6 py-4">{{ $customer->mobile ?? 'N/A' }}</td>
                <td class="px-6 py-4 text-right font-bold text-amber-600">Rs. {{ number_format($customer->sales_sum_due_total, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="px-6 py-12 text-center">
                    <h3 class="text-lg font-bold text-slate-700 mb-1">No outstanding balances found.</h3>
                    <p class="text-slate-500">All customers have paid their bills.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
    @if($customers_due->count() > 0)
    <tfoot class="bg-slate-50 font-bold text-slate-800">
        <tr>
            <td colspan="2" class="px-6 py-4 text-right uppercase text-xs">Total Outstanding</td>
            <td class="px-6 py-4 text-right text-amber-600">Rs. {{ number_format($customers_due->sum('sales_sum_due_total'), 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

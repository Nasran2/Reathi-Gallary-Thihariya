<table class="w-full text-left text-sm text-slate-500">
    <thead class="bg-slate-50 text-xs uppercase text-slate-700">
        <tr>
            <th class="px-6 py-3">Product Name</th>
            <th class="px-6 py-3">Category</th>
            <th class="px-6 py-3">Base Unit</th>
            <th class="px-6 py-3 text-right">Avg. Cost</th>
            <th class="px-6 py-3 text-right">Stock Qty</th>
            <th class="px-6 py-3 text-right">Stock Cost Value</th>
            <th class="px-6 py-3 text-right">Potential Sales Value</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-6 py-4 font-bold text-slate-800">{{ $row['p']->name }}</td>
                <td class="px-6 py-4">{{ $row['p']->category?->name ?? 'Uncategorized' }}</td>
                <td class="px-6 py-4">{{ $row['p']->baseUnit?->symbol ?? 'Units' }}</td>
                <td class="px-6 py-4 text-right">Rs. {{ number_format($row['p']->average_cost, 2) }}</td>
                <td class="px-6 py-4 text-right font-bold text-slate-800">{{ number_format($row['qty'], 2) }}</td>
                <td class="px-6 py-4 text-right font-semibold text-rose-600">Rs. {{ number_format($row['costValue'], 2) }}</td>
                <td class="px-6 py-4 text-right font-semibold text-emerald-600">Rs. {{ number_format($row['sellingValue'], 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center">
                    <h3 class="text-lg font-bold text-slate-700 mb-1">No stock records found.</h3>
                    <p class="text-slate-500">Try changing the category filter.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
    @if($rows->count() > 0)
    <tfoot class="bg-slate-50 font-bold text-slate-800">
        <tr>
            <td colspan="4" class="px-6 py-4 text-right uppercase text-xs">Total Valuation</td>
            <td class="px-6 py-4 text-right">{{ number_format($rows->sum('qty'), 2) }}</td>
            <td class="px-6 py-4 text-right text-rose-600">Rs. {{ number_format($rows->sum('costValue'), 2) }}</td>
            <td class="px-6 py-4 text-right text-emerald-600">Rs. {{ number_format($rows->sum('sellingValue'), 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

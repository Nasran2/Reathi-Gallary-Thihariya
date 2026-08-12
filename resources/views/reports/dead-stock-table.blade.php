<table class="w-full text-left text-sm text-slate-500">
    <thead class="bg-slate-50 text-xs uppercase text-slate-700">
        <tr>
            <th class="px-6 py-3">Product Name</th>
            <th class="px-6 py-3">Category</th>
            <th class="px-6 py-3">Last Sale Date</th>
            <th class="px-6 py-3">Days Since Sale</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $product)
            @php
                $lastSale = $product->last_sale_at ? \Carbon\Carbon::parse($product->last_sale_at) : null;
                $diffDays = $lastSale ? $lastSale->diffInDays(now()) : 'Never Sold';
            @endphp
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-6 py-4 font-bold text-slate-800">{{ $product->name }}</td>
                <td class="px-6 py-4">{{ $product->category?->name ?? 'Uncategorized' }}</td>
                <td class="px-6 py-4">{{ $lastSale ? $lastSale->format('Y-m-d') : 'N/A' }}</td>
                <td class="px-6 py-4 font-bold {{ $diffDays === 'Never Sold' ? 'text-red-600' : 'text-amber-600' }}">
                    {{ is_numeric($diffDays) ? $diffDays . ' days' : $diffDays }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-12 text-center">
                    <h3 class="text-lg font-bold text-slate-700 mb-1">No dead stock found.</h3>
                    <p class="text-slate-500">All products have been sold recently.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

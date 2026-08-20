<table class="w-full text-left text-sm">
    <thead>
        <tr class="bg-slate-50 text-slate-500">
            <th class="p-4 font-medium">Product / SKU</th>
            <th class="p-4 font-medium">Category</th>
            <th class="p-4 font-medium text-right">Current Stock</th>
            <th class="p-4 font-medium text-right">Min Stock</th>
            <th class="p-4 font-medium text-right">Reorder Level</th>
            <th class="p-4 font-medium">Default Supplier</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
        @forelse($products as $p)
        <tr class="hover:bg-slate-50">
            <td class="p-4 text-ink">
                <div class="font-semibold">{{ $p->name }}</div>
                <div class="text-xs text-slate-400">{{ $p->sku }}</div>
            </td>
            <td class="p-4">{{ $p->category?->name ?? '-' }}</td>
            <td class="p-4 text-right">
                <span class="font-semibold @if($p->total_stock <= $p->minimum_stock) text-red-600 @elseif($p->total_stock <= $p->reorder_level) text-amber-500 @endif">{{ (float)$p->total_stock }}</span>
                <span class="text-xs text-slate-500">{{ $p->baseUnit?->symbol }}</span>
            </td>
            <td class="p-4 text-right">{{ (float)$p->minimum_stock }}</td>
            <td class="p-4 text-right">{{ (float)$p->reorder_level }}</td>
            <td class="p-4 text-slate-500">{{ $p->defaultSupplier?->name ?? 'None' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="p-8 text-center text-slate-400">No low stock products found.</td>
        </tr>
        @endforelse
    </tbody>
</table>

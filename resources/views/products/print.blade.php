<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Products List</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #000;
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.4;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            font-family: serif;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .right {
            text-align: right;
        }
        .text-muted {
            color: #555;
            font-size: 10px;
        }
        @media print {
            body { background: transparent; }
            .container { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <div class="logo">{{ strtoupper(\App\Models\BusinessSetting::read('business_name', 'Store Name')) }}</div>
            <div class="title">Products List</div>
            @if(request('q'))
            <div>Search: {{ request('q') }}</div>
            @endif
        </div>
        
        <table>
            <thead>
                <tr>
                    @if(in_array('product', $cols)) <th>Product Name</th> @endif
                    @if(in_array('barcode', $cols)) <th>Barcode/SKU</th> @endif
                    @if(in_array('units', $cols)) <th>Units & Prices</th> @endif
                    @if(in_array('main_stock', $cols)) <th class="right">Main Stock</th> @endif
                    @if(in_array('cost', $cols)) <th class="right">Cost</th> @endif
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                <tr>
                    @if(in_array('product', $cols))
                    <td>
                        <strong>{{ $p->name }}</strong><br>
                        <span class="text-muted">{{ $p->category?->name ?? 'Uncategorized' }}</span>
                    </td>
                    @endif
                    @if(in_array('barcode', $cols))
                    <td>
                        {{ $p->barcode ?? $p->sku }}<br>
                        @if($p->barcode && $p->barcode !== $p->sku)
                            <span class="text-muted">SKU: {{ $p->sku }}</span>
                        @endif
                    </td>
                    @endif
                    @if(in_array('units', $cols))
                    <td>
                        @foreach($p->productUnits as $pu)
                            {{ $pu->unit->symbol }} Rs.{{ number_format($p->main_selling_price * $pu->conversion_rate, 2) }}<br>
                        @endforeach
                    </td>
                    @endif
                    @if(in_array('main_stock', $cols))
                    <td class="right">{{ number_format($p->balances->where('inventory_type', 'main')->sum('quantity'), 3) }} {{ $p->baseUnit->symbol }}</td>
                    @endif
                    @if(in_array('cost', $cols))
                    <td class="right">
                        @can('products.view_cost')
                            Rs. {{ number_format($p->average_cost, 4) }}
                        @else
                            <span style="color: #999;">Restricted</span>
                        @endcan
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($cols) }}" style="text-align: center; padding: 20px;">No products found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div style="margin-top: 20px; text-align: center; font-size: 10px; color: #666;">
            Generated on {{ now()->format('Y-m-d h:i A') }} by {{ auth()->user()->name }}
        </div>
    </div>
</body>
</html>

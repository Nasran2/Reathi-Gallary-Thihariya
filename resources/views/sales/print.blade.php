<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $sale->invoice_no }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #000;
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.4;
        }
        @page {
            margin: 0;
        }
        .receipt-container {
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            background: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .logo {
            font-family: serif;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .address {
            font-size: 10px;
            color: #444;
            margin-bottom: 10px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            text-align: left;
            border-bottom: 1px dashed #000;
            padding-bottom: 4px;
        }
        th.right, td.right {
            text-align: right;
        }
        td {
            padding: 4px 0;
            vertical-align: top;
        }
        .item-name {
            font-weight: bold;
            font-size: 11px;
        }
        .item-meta {
            font-size: 9px;
            color: #555;
        }
        .totals {
            margin-top: 10px;
            padding-top: 5px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        .totals-row.grand-total {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 6px 0;
            margin: 6px 0;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
        }
        @media print {
            body { background: transparent; }
            .receipt-container { margin: 0; padding: 2mm; width: auto; max-width: 80mm; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <div class="logo">{{ strtoupper(\App\Models\BusinessSetting::read('legal_name', 'Store Name')) }}</div>
            <div class="address">
                Main Store<br>
                {{ \App\Models\BusinessSetting::read('business_phone', '+94 77 123 4567') }}
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="info">
            <span>Invoice:</span>
            <span><strong>{{ $sale->invoice_no }}</strong></span>
        </div>
        <div class="info">
            <span>Date:</span>
            <span>{{ $sale->sold_at->format('Y-m-d h:i A') }}</span>
        </div>
        <div class="info">
            <span>Customer:</span>
            <span>{{ $sale->customer?->name ?? 'Walk-in Customer' }}</span>
        </div>
        <div class="info">
            <span>Cashier:</span>
            <span>{{ $sale->user?->name ?? 'Admin' }}</span>
        </div>
        
        <div class="divider"></div>
        
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="right">Qty</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ Str::limit($item->product->name, 20) }}</div>
                        <div class="item-meta">
                            {{ $item->quantity+0 }} {{ $item->unit->symbol }} @ Rs {{ number_format($item->unit_price, 2) }}
                            @if($item->discount_amount > 0)
                            <br>Disc: -Rs {{ number_format($item->discount_amount, 2) }}
                            @endif
                        </div>
                    </td>
                    <td class="right" style="vertical-align: middle;">{{ $item->quantity+0 }}</td>
                    <td class="right" style="vertical-align: middle;">{{ number_format($item->net_revenue, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="totals">
            <div class="totals-row">
                <span>Subtotal:</span>
                <span>Rs {{ number_format($sale->subtotal, 2) }}</span>
            </div>
            @if($sale->discount_total > 0)
            <div class="totals-row">
                <span>Discount:</span>
                <span>-Rs {{ number_format($sale->discount_total, 2) }}</span>
            </div>
            @endif
            <div class="totals-row grand-total">
                <span>TOTAL:</span>
                <span>Rs {{ number_format($sale->grand_total, 2) }}</span>
            </div>
            
            <div class="totals-row">
                <span>Paid:</span>
                <span>Rs {{ number_format($sale->paid_total, 2) }}</span>
            </div>
            @if($sale->due_total > 0)
            <div class="totals-row">
                <span><strong>Due:</strong></span>
                <span><strong>Rs {{ number_format($sale->due_total, 2) }}</strong></span>
            </div>
            @endif
        </div>
        
        <div class="divider"></div>
        
        <div style="font-size: 10px; margin-top: 10px;">
            <strong>Payments:</strong><br>
            @foreach($sale->payments as $p)
                {{ $p->method->name }}: Rs {{ number_format($p->amount, 2) }}<br>
            @endforeach
        </div>
        
        <div class="divider"></div>
        
        <div class="footer">
            Thank you for shopping with us!<br>
            <br>
            {!! \App\Support\QrCodeRenderer::render(route('invoice.public', $sale->publicToken->token), 80) !!}<br>
            Scan to view digital invoice<br>
            <span style="display:block;margin-top:8px;color:#666;">Software powered by <strong>{{ env('developername', 'Twinsofte.com') }}{{ env('developer_phone') ? ' | ' . env('developer_phone') : '' }}</strong></span>
        </div>
    </div>
</body>
</html>

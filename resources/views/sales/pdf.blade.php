<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $sale->invoice_no }}</title>
    <style>
        @page { margin: 18mm 16mm 25mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #17233c;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }
        table { border-collapse: collapse; width: 100%; }
        .brand-bar {
            height: 6px;
            margin: -18mm -16mm 14mm;
            background: #16877f;
        }
        .header td { vertical-align: top; }
        .business-name {
            margin: 0 0 4px;
            color: #17233c;
            font-size: 24px;
            font-weight: 700;
        }
        .muted { color: #71809b; }
        .invoice-label {
            color: #16877f;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
            text-align: right;
        }
        .invoice-meta {
            margin-top: 7px;
            color: #43516a;
            font-size: 9.5px;
            text-align: right;
        }
        .invoice-meta strong { color: #17233c; }
        .divider {
            height: 2px;
            margin: 13px 0 14px;
            background: #16877f;
        }
        .info-grid {
            margin-bottom: 16px;
            table-layout: fixed;
        }
        .info-grid td {
            width: 50%;
            padding: 12px 14px;
            vertical-align: top;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .info-grid td + td { border-left: 5px solid #fff; }
        .section-label {
            margin-bottom: 5px;
            color: #71809b;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
        }
        .customer-name {
            margin-bottom: 3px;
            color: #17233c;
            font-size: 14px;
            font-weight: 700;
        }
        .sale-type {
            display: inline-block;
            margin-bottom: 5px;
            padding: 3px 7px;
            color: #126c66;
            background: #e6f5f3;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .items {
            margin-bottom: 16px;
            table-layout: fixed;
        }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .items th {
            padding: 8px 7px;
            color: #fff;
            background: #17233c;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .35px;
            text-align: left;
            text-transform: uppercase;
        }
        .items td {
            padding: 9px 7px;
            color: #43516a;
            border-bottom: 1px solid #e7edf4;
            vertical-align: top;
        }
        .items tbody tr:nth-child(even) td { background: #fbfcfe; }
        .items .right { text-align: right; }
        .product-name {
            color: #17233c;
            font-weight: 700;
        }
        .product-code {
            margin-top: 2px;
            color: #8b98ad;
            font-size: 8px;
        }
        .summary-layout {
            table-layout: fixed;
            page-break-inside: avoid;
        }
        .summary-layout > tbody > tr > td {
            width: 50%;
            vertical-align: top;
        }
        .summary-layout > tbody > tr > td:first-child { padding-right: 10px; }
        .summary-layout > tbody > tr > td:last-child { padding-left: 10px; }
        .payment-box {
            padding: 12px;
            border: 1px solid #dfe7ef;
            background: #f8fafc;
        }
        .payment-table td {
            padding: 5px 0;
            border-bottom: 1px solid #e7edf4;
        }
        .payment-table tr:last-child td { border-bottom: 0; }
        .payment-table .amount {
            color: #17233c;
            font-weight: 700;
            text-align: right;
        }
        .totals td {
            padding: 6px 9px;
            color: #43516a;
        }
        .totals .value {
            color: #17233c;
            font-weight: 700;
            text-align: right;
        }
        .totals .discount .value { color: #dc3f4a; }
        .totals .grand td {
            padding-top: 10px;
            padding-bottom: 10px;
            color: #16877f;
            background: #edf8f7;
            border-top: 2px solid #16877f;
            border-bottom: 2px solid #16877f;
            font-size: 13px;
            font-weight: 700;
        }
        .totals .due td {
            color: #9a5a08;
            background: #fff8e6;
            font-weight: 700;
        }
        .note {
            margin-top: 14px;
            padding: 9px 11px;
            color: #5d6a81;
            background: #f8fafc;
            border-left: 3px solid #16877f;
            page-break-inside: avoid;
        }
        .footer {
            position: fixed;
            right: 0;
            bottom: -17mm;
            left: 0;
            padding-top: 7px;
            color: #8996aa;
            border-top: 1px solid #dfe7ef;
            font-size: 8px;
            text-align: center;
        }
        .footer .thanks {
            margin-bottom: 3px;
            color: #5d6a81;
            font-weight: 700;
        }
        .powered {
            margin-top: 5px;
            color: #71809b;
            font-size: 8px;
        }
        .powered a {
            color: #16877f;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="brand-bar"></div>

    <table class="header">
        <tr>
            <td style="width: 58%;">
                <div class="business-name">{{ \App\Models\BusinessSetting::read('business_name', 'Store Name') }}</div>
                <div class="muted"><strong>{{ $sale->store->name ?? 'Main Store' }}</strong></div>
                @if(\App\Models\BusinessSetting::read('address'))<div class="muted">{{ \App\Models\BusinessSetting::read('address') }}</div>@endif
                @if(\App\Models\BusinessSetting::read('phone'))<div class="muted">Tel: {{ \App\Models\BusinessSetting::read('phone') }}</div>@endif
            </td>
            <td style="width: 42%;">
                <div class="invoice-label">INVOICE</div>
                <div class="invoice-meta">
                    <strong>Invoice:</strong> {{ $sale->invoice_no }}<br>
                    <strong>Date:</strong> {{ $sale->sold_at->format('d M Y') }}<br>
                    <strong>Time:</strong> {{ $sale->sold_at->format('h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="info-grid">
        <tr>
            <td>
                <div class="section-label">Billed to</div>
                <div class="customer-name">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</div>
                @if($sale->customer?->mobile)<div class="muted">Phone: {{ $sale->customer->mobile }}</div>@endif
                @if($sale->customer?->email)<div class="muted">Email: {{ $sale->customer->email }}</div>@endif
                @if($sale->customer?->address)<div class="muted">Address: {{ $sale->customer->address }}</div>@endif
            </td>
            <td>
                <div class="section-label">Sale information</div>
                <div class="sale-type">{{ $sale->sale_type }} inventory</div>
                <div><strong>Cashier:</strong> {{ $sale->user?->name ?? 'System' }}</div>
                <div><strong>Items:</strong> {{ $sale->items->count() }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 34%;">Item description</th>
                <th style="width: 10%;">Unit</th>
                <th style="width: 11%;" class="right">Qty</th>
                <th style="width: 15%;" class="right">Unit price</th>
                <th style="width: 14%;" class="right">Discount</th>
                <th style="width: 16%;" class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>
                        <div class="product-name">{{ $item->product->name }}</div>
                        <div class="product-code">{{ $item->product->sku }}@if($item->remnant) / {{ $item->remnant->remnant_no }}@endif</div>
                    </td>
                    <td>{{ $item->unit->symbol }}</td>
                    <td class="right">{{ $item->quantity + 0 }}</td>
                    <td class="right">Rs. {{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">{{ $item->discount_amount > 0 ? 'Rs. '.number_format($item->discount_amount, 2) : '-' }}</td>
                    <td class="right"><strong>Rs. {{ number_format($item->net_revenue, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-layout">
        <tr>
            <td>
                <div class="payment-box">
                    <div class="section-label">Payment summary</div>
                    @if($sale->payments->isNotEmpty())
                        <table class="payment-table">
                            @foreach($sale->payments as $payment)
                                <tr>
                                    <td>
                                        <strong>{{ $payment->method->name }}</strong>
                                        @if($payment->reference)<div class="muted">{{ $payment->reference }}</div>@endif
                                    </td>
                                    <td class="amount">Rs. {{ number_format($payment->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <div class="muted">No payment received - sale recorded as due.</div>
                    @endif
                </div>
            </td>
            <td>
                <table class="totals">
                    <tr><td>Subtotal</td><td class="value">Rs. {{ number_format($sale->subtotal, 2) }}</td></tr>
                    <tr class="discount"><td>Discount</td><td class="value">- Rs. {{ number_format($sale->discount_total, 2) }}</td></tr>
                    <tr class="grand"><td>TOTAL</td><td class="value">Rs. {{ number_format($sale->grand_total, 2) }}</td></tr>
                    <tr><td>Paid</td><td class="value">Rs. {{ number_format($sale->paid_total, 2) }}</td></tr>
                    @if($sale->due_total > 0)
                        <tr class="due"><td>AMOUNT DUE</td><td class="value">Rs. {{ number_format($sale->due_total, 2) }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if($sale->notes)
        <div class="note"><strong>Note:</strong> {{ $sale->notes }}</div>
    @endif

    <div class="footer">
        <div class="thanks">Thank you for your business.</div>
        Goods once sold can only be returned according to the store return policy.
        <div class="powered">Software powered by <strong>{{ env('developername', 'Twinsofte.com') }}{{ env('developer_phone') ? ' | ' . env('developer_phone') : '' }}</strong></div>
    </div>
</body>
</html>

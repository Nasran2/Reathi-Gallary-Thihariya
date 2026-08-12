<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $sale->invoice_no }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 13px;
            line-height: 1.5;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #14b8a6; /* Teal color */
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header table {
            width: 100%;
            border: none;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 5px;
        }
        .company-details {
            color: #64748b;
            font-size: 12px;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #14b8a6;
            text-align: right;
            text-transform: uppercase;
        }
        .invoice-details {
            text-align: right;
            font-size: 13px;
        }
        .invoice-details strong {
            color: #334155;
        }
        
        .customer-section {
            width: 100%;
            margin-bottom: 30px;
        }
        .customer-section table {
            width: 100%;
            border: none;
        }
        .bill-to-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 5px;
        }
        .customer-name {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
            text-align: left;
            padding: 12px 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        .items-table th.right, .items-table td.right {
            text-align: right;
        }
        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .product-name {
            font-weight: bold;
            color: #0f172a;
            display: block;
        }
        .product-sku {
            font-size: 11px;
            color: #94a3b8;
        }
        
        .totals-section {
            width: 100%;
            margin-bottom: 40px;
        }
        .totals-table {
            width: 350px;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 15px;
            color: #475569;
        }
        .totals-table td.label {
            text-align: left;
            font-weight: normal;
        }
        .totals-table td.value {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }
        .totals-table tr.grand-total td {
            background-color: #f8fafc;
            border-top: 2px solid #14b8a6;
            border-bottom: 2px solid #14b8a6;
            font-size: 16px;
            padding: 12px 15px;
            color: #14b8a6;
        }
        .totals-table tr.due td {
            background-color: #fffbeb;
            color: #b45309;
        }
        .clear {
            clear: both;
        }
        
        .footer {
            margin-top: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
        .payment-info {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 15px;
            width: 50%;
            float: left;
            margin-top: -100px; /* aligns with totals */
        }
        .payment-info h4 {
            margin: 0 0 10px 0;
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
        }
        .payment-line {
            font-size: 12px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="company-name">{{ $sale->store->name ?? 'Reathi Gallery' }}</div>
                    <div class="company-details">
                        {{ $sale->store->address ?? 'Main Branch' }}<br>
                        {{ $sale->store->phone ?? '' }}<br>
                        {{ $sale->store->email ?? '' }}
                    </div>
                </td>
                <td style="width: 50%;" class="invoice-details">
                    <div class="invoice-title">INVOICE</div>
                    <div style="margin-top: 10px;">
                        <strong>Invoice Number:</strong> {{ $sale->invoice_no }}<br>
                        <strong>Date:</strong> {{ $sale->sold_at->format('d M Y') }}<br>
                        <strong>Time:</strong> {{ $sale->sold_at->format('H:i A') }}<br>
                        <strong>Type:</strong> {{ strtoupper($sale->sale_type) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="customer-section">
        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="bill-to-title">Billed To</div>
                    <div class="customer-name">{{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</div>
                    @if($sale->customer)
                        <div class="company-details" style="margin-top: 5px;">
                            {{ $sale->customer->mobile ? 'Phone: ' . $sale->customer->mobile : '' }}<br>
                            {{ $sale->customer->email ? 'Email: ' . $sale->customer->email : '' }}<br>
                            {{ $sale->customer->address ? 'Address: ' . $sale->customer->address : '' }}
                        </div>
                    @endif
                </td>
                <td style="width: 50%;">
                    <!-- Could add barcode/qr code here if needed -->
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th style="width: 10%;">Unit</th>
                <th style="width: 10%;" class="right">Qty</th>
                <th style="width: 15%;" class="right">Price</th>
                <th style="width: 15%;" class="right">Discount</th>
                <th style="width: 15%;" class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>
                    <span class="product-name">{{ $item->product->name }}</span>
                    <span class="product-sku">{{ $item->product->sku }} {{ $item->remnant ? '· ' . $item->remnant->remnant_no : '' }}</span>
                </td>
                <td>{{ $item->unit->symbol }}</td>
                <td class="right">{{ $item->quantity + 0 }}</td>
                <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ number_format($item->discount_amount, 2) }}</td>
                <td class="right"><strong>{{ number_format($item->net_revenue, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        @if($sale->payments->count() > 0)
        <div class="payment-info">
            <h4>Payment Received</h4>
            @foreach($sale->payments as $payment)
            <div class="payment-line">
                <strong>{{ $payment->method->name }}</strong> 
                @if($payment->reference) ({{ $payment->reference }}) @endif
                <span style="float: right;">Rs. {{ number_format($payment->amount, 2) }}</span>
            </div>
            @endforeach
        </div>
        @endif

        <table class="totals-table">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">Rs. {{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            @if($sale->discount_total > 0)
            <tr>
                <td class="label">Total Discount</td>
                <td class="value" style="color: #ef4444;">- Rs. {{ number_format($sale->discount_total, 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td class="label"><strong>TOTAL</strong></td>
                <td class="value"><strong>Rs. {{ number_format($sale->grand_total, 2) }}</strong></td>
            </tr>
            
            @if($sale->due_total > 0)
            <tr class="due">
                <td class="label"><strong>DUE AMOUNT</strong></td>
                <td class="value"><strong>Rs. {{ number_format($sale->due_total, 2) }}</strong></td>
            </tr>
            @endif
        </table>
        <div class="clear"></div>
    </div>

    <div class="footer">
        Thank you for your business!<br>
        Goods once sold cannot be returned without a valid receipt.
        @if($sale->notes)
            <div style="margin-top: 10px; font-style: italic;">Note: {{ $sale->notes }}</div>
        @endif
    </div>
</body>
</html>

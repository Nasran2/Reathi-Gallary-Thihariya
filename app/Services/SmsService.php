<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Sale;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;

class SmsService
{
    public function sendInvoice(Sale $sale, ?int $userId = null): SmsLog
    {
        $sale->loadMissing(['customer', 'publicToken', 'items.product']);
        $template = BusinessSetting::read('sms_template', "Thank you for shopping at {business_name}.\nInvoice: {invoice_no}\nItems:\n{items}\nTotal: {total}\nPaid: {paid}\nDue: {due}\nView Bill: {invoice_url}");
        $url = route('invoice.public', $sale->publicToken->token);
        
        $itemsText = $sale->items->map(function ($item) {
            return ($item->product?->name ?? 'Unknown') . ' x ' . floatval($item->quantity) . ' = ' . number_format($item->subtotal, 2, '.', '');
        })->implode("\n");

        $businessName = BusinessSetting::read('legal_name') ?: BusinessSetting::read('business_name', 'Reathi Gallery');

        $message = strtr($template, [
            '{customer_name}' => $sale->customer?->name ?? 'Customer', '{business_name}' => $businessName,
            '{invoice_no}' => $sale->invoice_no, '{invoice_date}' => $sale->sold_at->format('Y-m-d'), '{total}' => number_format($sale->grand_total, 2, '.', ''),
            '{paid}' => number_format($sale->paid_total, 2, '.', ''), '{due}' => number_format($sale->due_total, 2, '.', ''), 
            '{items}' => $itemsText, '{invoice_url}' => $url,
        ]);
        $log = SmsLog::create(['sale_id' => $sale->id, 'customer_id' => $sale->customer_id, 'sent_by' => $userId, 'phone' => $sale->customer?->mobile ?? '', 'message' => $message, 'status' => 'pending']);
        if (! filter_var(BusinessSetting::read('sms_enabled', false), FILTER_VALIDATE_BOOL) || ! $sale->customer?->mobile) {
            $log->update(['status' => 'failed', 'gateway_response' => 'SMS disabled or customer phone unavailable.']);

            return $log;
        }
        try {
            $gatewayUrl = rtrim(BusinessSetting::read('sms_gateway_url', 'https://www.textit.biz/sendmsg'), '/');
            $response = Http::timeout((int) BusinessSetting::read('sms_timeout', 10))->get($gatewayUrl . '/', [
                'id' => BusinessSetting::read('sms_textit_id'), 'pw' => BusinessSetting::read('sms_password'), 'to' => $sale->customer->mobile, 'text' => $message,
            ]);
            $body = trim($response->body());
            $isSuccess = str_starts_with($body, 'OK');
            $log->update(['status' => $isSuccess ? 'sent' : 'failed', 'gateway_response' => $body]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'gateway_response' => $e->getMessage()]);
        }

        return $log;
    }
}

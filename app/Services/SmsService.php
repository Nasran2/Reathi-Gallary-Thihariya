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
        $sale->loadMissing('customer', 'publicToken');
        $template = BusinessSetting::read('sms_template', 'Thank you for shopping at {business_name}. Invoice: {invoice_no} Total: {total} Paid: {paid} Due: {due} View Bill: {invoice_url}');
        $url = route('invoice.public', $sale->publicToken->token);
        $message = strtr($template, [
            '{customer_name}' => $sale->customer?->name ?? 'Customer', '{business_name}' => BusinessSetting::read('business_name', 'Reathi Gallery'),
            '{invoice_no}' => $sale->invoice_no, '{invoice_date}' => $sale->sold_at->format('Y-m-d'), '{total}' => $sale->grand_total,
            '{paid}' => $sale->paid_total, '{due}' => $sale->due_total, '{invoice_url}' => $url,
        ]);
        $log = SmsLog::create(['sale_id' => $sale->id, 'customer_id' => $sale->customer_id, 'sent_by' => $userId, 'phone' => $sale->customer?->mobile ?? '', 'message' => $message, 'status' => 'pending']);
        if (! filter_var(BusinessSetting::read('sms_enabled', false), FILTER_VALIDATE_BOOL) || ! $sale->customer?->mobile) {
            $log->update(['status' => 'failed', 'gateway_response' => 'SMS disabled or customer phone unavailable.']);

            return $log;
        }
        try {
            $response = Http::timeout((int) BusinessSetting::read('sms_timeout', 10))->asForm()->post(BusinessSetting::read('sms_gateway_url', 'https://www.textit.biz/sendmsg'), [
                'id' => BusinessSetting::read('sms_textit_id'), 'pw' => BusinessSetting::read('sms_password'), 'to' => $sale->customer->mobile, 'text' => $message,
            ]);
            $log->update(['status' => $response->successful() ? 'sent' : 'failed', 'gateway_response' => $response->body()]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'gateway_response' => $e->getMessage()]);
        }

        return $log;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PublicInvoiceToken;

class PublicInvoiceController extends Controller
{
    public function __invoke(string $token)
    {
        $record = PublicInvoiceToken::where('token', $token)->firstOrFail();
        if ($record->expires_at?->isPast()) {
            abort(410, 'This invoice link has expired.');
        }

return view('invoice.public', ['sale' => $record->sale->load('items.product', 'items.unit', 'payments.method', 'customer', 'store')]);
    }
}

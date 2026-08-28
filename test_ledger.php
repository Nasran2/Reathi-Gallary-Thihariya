<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('username', 'admin')->first();
auth()->login($user);
Illuminate\Support\Facades\View::share('errors', new Illuminate\Support\MessageBag());

$request = Illuminate\Http\Request::create('/reports/ledger', 'GET', ['from' => now()->format('Y-m-d'), 'to' => now()->format('Y-m-d')]);
$app->instance('request', $request);

$controller = app()->make(App\Http\Controllers\ReportController::class);
$response = $controller->ledger($request);

if (method_exists($response, 'render')) {
    $html = $response->render();
} else {
    $html = $response->getContent();
}

if (strpos($html, 'Daily Ledger Report') !== false) {
    echo "SUCCESS: Ledger rendered\n";
    // echo "\n" . substr($html, strpos($html, 'Ledger Transactions'), 500);
} else {
    echo "FAIL: Could not find title\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$user = App\Models\User::where('username', 'sale')->first();
auth()->login($user);
Illuminate\Support\Facades\View::share('errors', new Illuminate\Support\MessageBag());
$html = view('layouts.app')->render();
if (strpos($html, 'Reports') !== false) {
    echo "SUCCESS: Reports menu is present\n";
} else {
    echo "FAIL: Reports menu is NOT present\n";
}

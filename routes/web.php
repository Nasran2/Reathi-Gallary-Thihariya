<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/invoice/view/{token}', PublicInvoiceController::class)->name('invoice.public')->middleware('throttle:60,1');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt')->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/pos/main', [PosController::class, 'index'])->defaults('type', 'main')->name('pos.main');
    Route::get('/pos/remnant', [PosController::class, 'index'])->defaults('type', 'remnant')->name('pos.remnant');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::resource('products', ProductController::class)->except(['show', 'destroy']);
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('sales', SaleController::class)->only(['index', 'show']);
    Route::resource('customers', CustomerController::class)->only(['index', 'store']);
    Route::resource('suppliers', SupplierController::class)->only(['index', 'store']);
    Route::resource('expenses', ExpenseController::class)->only(['index', 'store']);
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::get('/inventory/adjust', [InventoryController::class, 'adjustmentForm'])->name('inventory.adjust');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust.store');
    Route::get('/remnants', [InventoryController::class, 'remnants'])->name('remnants.index');
    Route::get('/remnants/transfer', [InventoryController::class, 'transferForm'])->name('remnants.transfer');
    Route::post('/remnants/transfer', [InventoryController::class, 'transfer'])->name('remnants.transfer.store');
    Route::get('/reports/profit-loss', [ReportController::class, 'profit'])->name('reports.profit');
    Route::get('/reports/stock-valuation', [ReportController::class, 'valuation'])->name('reports.valuation');
    Route::get('/reports/dead-stock', [ReportController::class, 'deadStock'])->name('reports.dead');
    Route::get('/settings/{section?}', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/units', [SettingsController::class, 'unit'])->name('settings.units.store');
    Route::post('/settings/categories', [SettingsController::class, 'category'])->name('settings.categories.store');
    Route::post('/settings/payment-methods',[SettingsController::class, 'paymentMethod'])->name('settings.payment-methods.store');
    Route::post('/settings/payment-methods',[SettingsController::class, 'paymentMethod'])->name('settings.payment-methods.store');

    // Placeholder routes for new sidebar structure
    Route::get('/users', fn() => 'Users Page')->name('users.index');
    Route::get('/roles', fn() => 'Roles Page')->name('roles.index');
    Route::get('/categories', fn() => 'Categories Page')->name('categories.index');
    Route::get('/units', fn() => 'Units Page')->name('units.index');
    Route::get('/brands', fn() => 'Brands Page')->name('brands.index');
    Route::get('/purchases/returns', fn() => 'Purchase Returns Page')->name('purchases.returns');
    Route::get('/sales/returns', fn() => 'Sales Returns Page')->name('sales.returns');
    Route::get('/distribution/vehicles', fn() => 'Vehicles Page')->name('distribution.vehicles');
    Route::get('/distribution/drivers', fn() => 'Drivers Page')->name('distribution.drivers');
    Route::get('/distribution/routes', fn() => 'Routes Page')->name('distribution.routes');
    Route::get('/transfers', fn() => 'Transfers Page')->name('transfers.index');
    Route::get('/expenses/categories', fn() => 'Expense Categories Page')->name('expenses.categories');
    Route::get('/accounting/accounts', fn() => 'Accounts Page')->name('accounting.accounts');
    Route::get('/accounting/deposits', fn() => 'Deposits Page')->name('accounting.deposits');
    Route::get('/accounting/transfers', fn() => 'Accounting Transfers Page')->name('accounting.transfers');
    Route::get('/cheques/received', fn() => 'Received Cheques Page')->name('cheques.received');
    Route::get('/cheques/issued', fn() => 'Issued Cheques Page')->name('cheques.issued');
    Route::get('/taxes/rates', fn() => 'Tax Rates Page')->name('taxes.rates');
    Route::get('/taxes/reports', fn() => 'Tax Reports Page')->name('taxes.reports');
    Route::get('/reports/sales', fn() => 'Sales Report Page')->name('reports.sales');
    Route::get('/reports/purchases', fn() => 'Purchase Report Page')->name('reports.purchases');
    Route::get('/reports/stock', fn() => 'Stock Report Page')->name('reports.stock');
});

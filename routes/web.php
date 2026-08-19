<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use Illuminate\Http\Request;
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
    Route::resource('products', ProductController::class);
    Route::get('/purchases/returns', [ReturnController::class, 'purchases'])->name('purchases.returns');
    Route::post('/purchases/returns', [ReturnController::class, 'storePurchase'])->name('purchases.returns.store');
    Route::get('/sales/returns', [ReturnController::class, 'sales'])->name('sales.returns');
    Route::post('/sales/returns', [ReturnController::class, 'storeSale'])->name('sales.returns.store');
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::resource('brands', \App\Http\Controllers\BrandController::class)->except(['show']);
    Route::resource('unit-presets', \App\Http\Controllers\UnitPresetController::class)->except(['show']);
    Route::resource('sales', SaleController::class)->only(['index', 'show']);
    Route::get('/sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
    Route::get('/sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
    Route::post('/sales/{sale}/update-customer', [SaleController::class, 'updateCustomer'])->name('sales.update-customer');
    Route::post('/sales/{sale}/sms', [SaleController::class, 'sms'])->name('sales.sms');
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('/customers/{customer}/toggle', [CustomerController::class, 'toggle'])->name('customers.toggle');
    Route::post('/customers/{customer}/pay-due', [CustomerController::class, 'payDue'])->name('customers.pay-due');
    Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('/suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->name('suppliers.toggle');
    Route::post('/suppliers/{supplier}/pay', [SupplierController::class, 'pay'])->name('suppliers.pay');
    Route::resource('expenses', ExpenseController::class)->only(['index', 'store']);
    Route::get('/expenses/categories', [ExpenseController::class, 'categories'])->name('expenses.categories');
    Route::post('/expenses/categories', [ExpenseController::class, 'storeCategory'])->name('expenses.categories.store');
    Route::put('/expenses/categories/{category}', [ExpenseController::class, 'updateCategory'])->name('expenses.categories.update');
    Route::delete('/expenses/categories/{category}', [ExpenseController::class, 'destroyCategory'])->name('expenses.categories.destroy');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::get('/inventory/adjust', [InventoryController::class, 'adjustmentForm'])->name('inventory.adjust');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust.store');
    Route::get('/remnants', [InventoryController::class, 'remnants'])->name('remnants.index');
    Route::get('/remnants/transfer', [InventoryController::class, 'transferForm'])->name('remnants.transfer');
    Route::post('/remnants/transfer', [InventoryController::class, 'transfer'])->name('remnants.transfer.store');
    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('/cheques', [ChequeController::class, 'dashboard'])->name('cheques.dashboard');
    Route::get('/cheques/all', [ChequeController::class, 'index'])->name('cheques.index');
    Route::get('/cheques/received', fn (Request $r) => app(ChequeController::class)->index($r, 'received'))->name('cheques.received');
    Route::get('/cheques/issued', fn (Request $r) => app(ChequeController::class)->index($r, 'issued'))->name('cheques.issued');
    Route::get('/cheques/endorsed', fn (Request $r) => app(ChequeController::class)->index($r->merge(['type' => 'endorsed'])))->name('cheques.endorsed');
    Route::get('/cheques/returned', fn (Request $r) => app(ChequeController::class)->index($r->merge(['status' => 'returned'])))->name('cheques.returned');
    Route::get('/cheques/history', [ChequeController::class, 'index'])->name('cheques.history');
    Route::get('/cheques/{cheque}', [ChequeController::class, 'show'])->name('cheques.show');
    Route::post('/cheques/{cheque}/deposit', [ChequeController::class, 'deposit'])->name('cheques.deposit');
    Route::post('/cheques/{cheque}/pass', [ChequeController::class, 'pass'])->name('cheques.pass');
    Route::post('/cheques/{cheque}/return', [ChequeController::class, 'returnCheque'])->name('cheques.return');
    Route::post('/cheques/{cheque}/endorse', [ChequeController::class, 'endorse'])->name('cheques.endorse');
    Route::post('/cheques/{cheque}/cancel', [ChequeController::class, 'cancel'])->name('cheques.cancel');
    Route::get('/reports/profit-loss', [ReportController::class, 'profit'])->name('reports.profit');
    Route::get('/reports/stock-valuation', [ReportController::class, 'valuation'])->name('reports.valuation');
    Route::get('/reports/dead-stock', [ReportController::class, 'deadStock'])->name('reports.dead');
    Route::get('/settings/index', [SettingsController::class, 'show'])->name('settings.index');
    Route::get('/settings/{section?}', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::post('/settings/units', [SettingsController::class, 'unit'])->name('settings.units.store');
    Route::put('/settings/units/{unit}', [SettingsController::class, 'updateUnit'])->name('settings.units.update');
    Route::delete('/settings/units/{unit}', [SettingsController::class, 'destroyUnit'])->name('settings.units.destroy');

    Route::post('/settings/categories', [SettingsController::class, 'category'])->name('settings.categories.store');
    Route::put('/settings/categories/{category}', [SettingsController::class, 'updateCategory'])->name('settings.categories.update');
    Route::delete('/settings/categories/{category}', [SettingsController::class, 'destroyCategory'])->name('settings.categories.destroy');

    Route::post('/settings/brands', [SettingsController::class, 'brand'])->name('settings.brands.store');
    Route::put('/settings/brands/{brand}', [SettingsController::class, 'updateBrand'])->name('settings.brands.update');
    Route::delete('/settings/brands/{brand}', [SettingsController::class, 'destroyBrand'])->name('settings.brands.destroy');

    Route::post('/settings/payment-methods', [SettingsController::class, 'paymentMethod'])->name('settings.payment-methods.store');
    Route::put('/settings/payment-methods/{method}', [SettingsController::class, 'updatePaymentMethod'])->name('settings.payment-methods.update');
    Route::delete('/settings/payment-methods/{method}', [SettingsController::class, 'destroyPaymentMethod'])->name('settings.payment-methods.destroy');

    // Modules not enabled in this installation resolve to their real configuration/report screens.
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::get('/categories', [ProductController::class, 'categories'])->name('categories.index');
    Route::get('/units', [ProductController::class, 'units'])->name('units.index');
    Route::get('/brands', [ProductController::class, 'brands'])->name('brands.index');
    Route::redirect('/distribution/vehicles', '/settings')->name('distribution.vehicles');
    Route::redirect('/distribution/drivers', '/settings')->name('distribution.drivers');
    Route::redirect('/distribution/routes', '/settings')->name('distribution.routes');
    Route::redirect('/accounting/accounts', '/reports/daily-closing')->name('accounting.accounts');
    Route::redirect('/accounting/deposits', '/cheques')->name('accounting.deposits');
    Route::redirect('/accounting/transfers', '/transfers')->name('accounting.transfers');
    Route::redirect('/taxes/rates', '/settings')->name('taxes.rates');
    Route::redirect('/taxes/reports', '/reports/sales')->name('taxes.reports');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
    Route::get('/reports/stock', [ReportController::class, 'valuation'])->name('reports.stock'); // Mapping stock to valuation
    Route::get('/reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
    Route::get('/reports/due-bills', [ReportController::class, 'dueBills'])->name('reports.due-bills');
    Route::get('/reports/customer-due', [ReportController::class, 'customerDue'])->name('reports.customer-due');
    Route::get('/reports/daily-closing', [ReportController::class, 'dailyClosing'])->name('reports.daily-closing');
});

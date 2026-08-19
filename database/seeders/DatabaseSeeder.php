<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@reathi.test'], ['username' => 'admin', 'name' => 'Reathi Administrator', 'password' => Hash::make('password'), 'active' => true]);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin->syncRoles([$superAdminRole]);
        $store = Store::firstOrCreate(['code' => 'MAIN'], ['name' => 'Main Store', 'address' => 'Colombo, Sri Lanka', 'is_default' => true, 'active' => true]);
        $meter = Unit::firstOrCreate(['symbol' => 'm'], ['name' => 'Meter', 'allows_decimal' => true]);
        $yard = Unit::firstOrCreate(['symbol' => 'yd'], ['name' => 'Yard', 'allows_decimal' => true]);
        $piece = Unit::firstOrCreate(['symbol' => 'pc'], ['name' => 'Piece', 'allows_decimal' => true]);
        $dozen = Unit::firstOrCreate(['symbol' => 'doz'], ['name' => 'Dozen', 'allows_decimal' => true]);
        $roll = Unit::firstOrCreate(['symbol' => 'roll'], ['name' => 'Roll', 'allows_decimal' => true]);
        $cotton = Category::firstOrCreate(['slug' => 'cotton-fabrics'], ['name' => 'Cotton Fabrics', 'active' => true]);
        $linen = Category::firstOrCreate(['slug' => 'linen-blends'], ['name' => 'Linen & Blends', 'active' => true]);
        $accessories = Category::firstOrCreate(['slug' => 'accessories'], ['name' => 'Accessories', 'active' => true]);
        $walkIn = Customer::firstOrCreate(['is_walk_in' => true], ['name' => 'Walk-in Customer', 'mobile' => null, 'credit_limit' => 0, 'opening_balance' => 0, 'active' => true]);
        $customer = Customer::firstOrCreate(['mobile' => '0771234567'], ['name' => 'Nadeesha Perera', 'whatsapp' => '0771234567', 'credit_limit' => 25000, 'opening_balance' => 0, 'active' => true]);
        $supplier = Supplier::firstOrCreate(['phone' => '0112345678'], ['name' => 'Serendib Textiles', 'company' => 'Serendib Textile Imports', 'opening_balance' => 0, 'active' => true]);
        foreach ([['Cash', 'cash', false], ['Card', 'card', true], ['Bank Transfer', 'bank_transfer', true], ['Credit / Due', 'credit_due', false], ['Cheque', 'cheque', true], ['Own Cheque', 'own_cheque', true], ['Customer Cheque / Endorsed', 'endorsed_cheque', true]] as $i => $m) {
            PaymentMethod::firstOrCreate(['code' => $m[1]], ['name' => $m[0], 'requires_reference' => $m[2], 'active' => true, 'sort_order' => $i]);
        }
        foreach (['Rent', 'Electricity', 'Salary', 'Transport', 'Delivery', 'Loading', 'Meals', 'Internet', 'Maintenance', 'Other'] as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name], ['active' => true]);
        }
        foreach (['business_name' => 'Reathi Gallery', 'currency_symbol' => 'Rs.', 'money_decimals' => '2', 'quantity_decimals' => '3', 'allow_negative_stock' => 'false', 'block_main_below_cost' => 'false', 'remnant_partial_sale' => 'true', 'invoice_link_expiry' => 'never', 'sms_enabled' => 'false', 'sms_timeout' => '10', 'primary_colour' => '#177d75', 'card_fee_percentage' => '0'] as $key => $value) {
            BusinessSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $products = [
            ['sku' => 'FAB-COT-001', 'name' => 'Premium Cotton Poplin', 'category' => $cotton, 'base' => $meter, 'avg' => 520, 'main_price' => 850, 'remnant_price' => 620, 'stock' => 85.75, 'attrs' => ['colour' => 'Ivory', 'fabric_type' => 'Poplin', 'material' => '100% Cotton', 'width' => '58 in'], 'units' => [[$meter, 1], [$yard, .9144], [$roll, 50]]],
            ['sku' => 'FAB-LIN-002', 'name' => 'Washed Linen Blend', 'category' => $linen, 'base' => $meter, 'avg' => 710, 'main_price' => 1150, 'remnant_price' => 800, 'stock' => 48.5, 'attrs' => ['colour' => 'Sage', 'fabric_type' => 'Linen blend', 'material' => 'Linen / Viscose', 'width' => '60 in'], 'units' => [[$meter, 1], [$yard, .9144]]],
            ['sku' => 'ACC-BTN-001', 'name' => 'Pearl Shirt Buttons', 'category' => $accessories, 'base' => $piece, 'avg' => 35, 'main_price' => 75, 'remnant_price' => 50, 'stock' => 240, 'attrs' => ['colour' => 'Pearl'], 'units' => [[$piece, 1], [$dozen, 12]]],
        ];
        $inventory = app(InventoryService::class);
        foreach ($products as $data) {
            $p = Product::firstOrCreate(['sku' => $data['sku']], ['name' => $data['name'], 'category_id' => $data['category']->id, 'base_unit_id' => $data['base']->id, 'default_purchase_unit_id' => $data['base']->id, 'default_selling_unit_id' => $data['base']->id, 'default_supplier_id' => $supplier->id, 'average_cost' => $data['avg'], 'main_selling_price' => $data['main_price'], 'remnant_selling_price' => $data['remnant_price'], 'minimum_stock' => 5, 'reorder_level' => 10, 'active' => true] + $data['attrs']);
            foreach ($data['units'] as [$unit,$rate]) {
                $p->productUnits()->updateOrCreate(['unit_id' => $unit->id], [
                    'base_quantity' => $rate < 1 ? 1 : $rate,
                    'unit_quantity' => $rate < 1 ? round(1 / $rate, 8) : 1,
                    'conversion_rate' => $rate,
                    'can_purchase' => true,
                    'can_sell' => true,
                ]);
            }
            if (! $p->balances()->where(['store_id' => $store->id, 'inventory_type' => 'main'])->exists()) {
                $inventory->move($p, $store->id, 'main', 'opening_stock', $data['stock'], 0, $data['avg'], null, $admin->id, 'Demo opening stock');
            }
        }
    }
}

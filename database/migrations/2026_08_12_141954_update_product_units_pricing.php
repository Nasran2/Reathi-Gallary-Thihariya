<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('main_selling_price', 12, 4)->default(0)->after('average_cost');
            $table->decimal('remnant_selling_price', 12, 4)->default(0)->after('main_selling_price');
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('base_quantity', 12, 6)->default(1)->after('unit_id');
            $table->decimal('unit_quantity', 12, 6)->default(1)->after('base_quantity');
            $table->dropColumn(['main_selling_price', 'remnant_selling_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropColumn(['base_quantity', 'unit_quantity']);
            $table->decimal('main_selling_price', 12, 4)->default(0)->after('conversion_rate');
            $table->decimal('remnant_selling_price', 12, 4)->default(0)->after('main_selling_price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['main_selling_price', 'remnant_selling_price']);
        });
    }
};

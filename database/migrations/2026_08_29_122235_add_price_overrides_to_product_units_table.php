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
        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('main_price', 12, 4)->nullable()->after('conversion_rate');
            $table->decimal('remnant_price', 12, 4)->nullable()->after('main_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropColumn(['main_price', 'remnant_price']);
        });
    }
};

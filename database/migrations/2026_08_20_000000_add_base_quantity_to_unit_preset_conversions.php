<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_preset_conversions', function (Blueprint $table) {
            $table->decimal('base_quantity', 18, 8)->default(1)->after('unit_id');
            $table->decimal('unit_quantity', 18, 8)->change();
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('base_quantity', 18, 8)->default(1)->change();
            $table->decimal('unit_quantity', 18, 8)->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('base_quantity', 12, 6)->default(1)->change();
            $table->decimal('unit_quantity', 12, 6)->default(1)->change();
        });

        Schema::table('unit_preset_conversions', function (Blueprint $table) {
            $table->decimal('unit_quantity', 15, 5)->change();
            $table->dropColumn('base_quantity');
        });
    }
};

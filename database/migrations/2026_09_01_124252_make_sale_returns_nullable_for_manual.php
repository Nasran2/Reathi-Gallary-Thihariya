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
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_id')->nullable()->change();
            $table->boolean('is_manual')->default(false)->after('sale_id');
        });

        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_item_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_item_id')->nullable(false)->change();
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropColumn('is_manual');
            $table->unsignedBigInteger('sale_id')->nullable(false)->change();
        });
    }
};

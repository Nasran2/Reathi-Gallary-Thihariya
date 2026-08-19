<?php

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_units')
            ->where('base_quantity', 1)
            ->where('unit_quantity', 1)
            ->where('conversion_rate', '!=', 1)
            ->orderBy('id')
            ->each(function ($productUnit) {
                $rate = BigDecimal::of((string) $productUnit->conversion_rate);

                if ($rate->isLessThan(1)) {
                    DB::table('product_units')->where('id', $productUnit->id)->update([
                        'base_quantity' => 1,
                        'unit_quantity' => (string) BigDecimal::one()->dividedBy($rate, 8, RoundingMode::HalfUp),
                    ]);
                } else {
                    DB::table('product_units')->where('id', $productUnit->id)->update([
                        'base_quantity' => (string) $rate->toScale(8, RoundingMode::HalfUp),
                        'unit_quantity' => 1,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // The original conversion_rate remains unchanged, so no data rollback is needed.
    }
};

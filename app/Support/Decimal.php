<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class Decimal
{
    public static function of(mixed $value): BigDecimal
    {
        return BigDecimal::of((string) ($value ?? 0));
    }

    public static function mul(mixed $a, mixed $b, int $scale = 8): string
    {
        return self::of($a)->multipliedBy(self::of($b))->toScale($scale, RoundingMode::HalfUp)->__toString();
    }

    public static function div(mixed $a, mixed $b, int $scale = 8): string
    {
        return self::of($a)->dividedBy(self::of($b), $scale, RoundingMode::HalfUp)->__toString();
    }

    public static function add(mixed $a, mixed $b, int $scale = 8): string
    {
        return self::of($a)->plus(self::of($b))->toScale($scale, RoundingMode::HalfUp)->__toString();
    }

    public static function sub(mixed $a, mixed $b, int $scale = 8): string
    {
        return self::of($a)->minus(self::of($b))->toScale($scale, RoundingMode::HalfUp)->__toString();
    }

    public static function money(mixed $value): string
    {
        return self::of($value)->toScale(4, RoundingMode::HalfUp)->__toString();
    }

    public static function qty(mixed $value): string
    {
        return self::of($value)->toScale(6, RoundingMode::HalfUp)->__toString();
    }
}

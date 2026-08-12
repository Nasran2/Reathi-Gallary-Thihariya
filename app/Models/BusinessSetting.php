<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class BusinessSetting extends Model
{
    protected $fillable = ['key', 'value', 'encrypted'];

    protected function casts(): array
    {
        return ['encrypted' => 'boolean'];
    }

    public static function read(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }
        $value = $setting->encrypted && $setting->value ? Crypt::decryptString($setting->value) : $setting->value;

        return match ($value) {
            'true' => true, 'false' => false, default => $value
        };
    }

    public static function write(string $key, mixed $value, bool $encrypted = false): void
    {
        $stored = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        if ($encrypted && $stored !== '') {
            $stored = Crypt::encryptString($stored);
        }
        static::updateOrCreate(['key' => $key], ['value' => $stored, 'encrypted' => $encrypted]);
    }
}

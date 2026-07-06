<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    public $timestamps = true;

    /** Ambil nilai setting dengan fallback aman jika tabel belum dimigrate. */
    public static function value(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('system_settings')) {
                return $default;
            }

            $setting = static::query()->where('key', $key)->first();
            return $setting?->value ?? $default;
        } catch (Throwable) {
            return $default;
        }
    }

    /** Simpan setting dengan aman. */
    public static function putValue(string $key, mixed $value, string $type = 'text'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }

    /** URL asset untuk file yang disimpan di storage/public. */
    public static function assetUrl(string $key, ?string $default = null): ?string
    {
        $value = static::value($key);

        if (! $value) {
            return $default;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        try {
            return Storage::disk('public')->url($value);
        } catch (Throwable) {
            return $default;
        }
    }
}

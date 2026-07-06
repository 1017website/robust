<?php

namespace App\Services;

use Illuminate\Database\Eloquent\SoftDeletes;

class CodeGenerator
{
    /**
     * Generate kode berurutan, contoh: prefix DR -> DR-026 atau Q-2026-0001.
     */
    public static function next(string $modelClass, string $prefix, int $pad = 3, bool $withYear = false): string
    {
        $year = date('Y');
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
        $count = $usesSoftDeletes ? $modelClass::withTrashed()->count() + 1 : $modelClass::count() + 1;

        if ($withYear) {
            return sprintf('%s-%s-%s', $prefix, $year, str_pad((string) $count, 4, '0', STR_PAD_LEFT));
        }

        return sprintf('%s-%s', $prefix, str_pad((string) $count, $pad, '0', STR_PAD_LEFT));
    }
}

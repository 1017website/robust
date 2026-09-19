<?php

namespace App\Services;

use App\Models\PurchaseOrderRequest;
use App\Models\SystemSetting;
use Illuminate\Support\Carbon;

/**
 * Nomor PO berformat CCMMYY, contoh 010926 = urutan 01, bulan 09, tahun 2026.
 *
 * Counter direset setiap bulan. Nomor awal dapat diatur sekali lewat System Settings
 * untuk menyambung penomoran yang sudah berjalan di Accurate; setelah terpakai, nomor
 * awal tidak berlaku lagi sampai nilainya diubah kembali oleh Administrator.
 */
class PurchaseOrderNumberGenerator
{
    public const START_KEY = 'po_number_start_counter';

    public const APPLIED_KEY = 'po_number_start_applied';

    /** Nomor berikutnya, sekaligus menandai nomor awal sudah terpakai. */
    public function next(?Carbon $date = null): string
    {
        return $this->generate($date, true);
    }

    /** Nomor berikutnya tanpa mengubah apa pun. Dipakai untuk pratinjau di Settings. */
    public function preview(?Carbon $date = null): string
    {
        return $this->generate($date, false);
    }

    public static function format(int $counter, string $period): string
    {
        return str_pad((string) max($counter, 1), 2, '0', STR_PAD_LEFT).$period;
    }

    /** Periode MMYY, misal 0926 untuk September 2026. */
    public static function period(?Carbon $date = null): string
    {
        return ($date ?: Carbon::now())->format('my');
    }

    public function startCounter(): int
    {
        return max((int) SystemSetting::value(self::START_KEY, 1), 1);
    }

    public function startApplied(): bool
    {
        return (bool) SystemSetting::value(self::APPLIED_KEY, false);
    }

    protected function generate(?Carbon $date, bool $consumeStart): string
    {
        $period = self::period($date);
        $counter = $this->highestCounter($period) + 1;

        if (! $this->startApplied()) {
            $counter = max($counter, $this->startCounter());
        }

        // Nomor manual bisa menyerobot urutan, jadi tetap dipastikan unik.
        while (PurchaseOrderRequest::where('code', self::format($counter, $period))->exists()) {
            $counter++;
        }

        if ($consumeStart && ! $this->startApplied()) {
            SystemSetting::putValue(self::APPLIED_KEY, '1', 'boolean');
        }

        return self::format($counter, $period);
    }

    /** Counter tertinggi yang sudah dipakai pada periode berjalan. */
    protected function highestCounter(string $period): int
    {
        $highest = 0;

        PurchaseOrderRequest::query()
            ->where('code', 'like', '%'.$period)
            ->pluck('code')
            ->each(function ($code) use ($period, &$highest) {
                if (preg_match('/^(\d+)'.preg_quote($period, '/').'$/', (string) $code, $matches)) {
                    $highest = max($highest, (int) $matches[1]);
                }
            });

        return $highest;
    }
}

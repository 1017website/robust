<?php

namespace App\Support;

class Format
{
    public static function rupiah($value, bool $withPrefix = true): string
    {
        $num = number_format((float) $value, 0, ',', '.');
        return $withPrefix ? 'Rp '.$num : $num;
    }

    public static function rupiahShort($value): string
    {
        $value = (float) $value;
        if ($value >= 1_000_000_000) return 'Rp '.rtrim(rtrim(number_format($value / 1_000_000_000, 1, ',', '.'), '0'), ',').' M';
        if ($value >= 1_000_000) return 'Rp '.rtrim(rtrim(number_format($value / 1_000_000, 1, ',', '.'), '0'), ',').' Jt';
        if ($value >= 1_000) return 'Rp '.number_format($value / 1_000, 0, ',', '.').' Rb';
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    public static function badgeClass(string $status): string
    {
        return match (strtolower($status)) {
            'draft', 'new', 'baru' => 'secondary',
            'assigned', 'scheduled', 'terjadwal', 'identify' => 'info',
            'waiting_acceptance', 'pending', 'menunggu', 'drafting', 'approaching' => 'warning',
            'accepted', 'completed', 'selesai', 'won', 'won_closing', 'won / closing', 'aktif', 'paid', 'follow_up' => 'success',
            'rejected', 'lost', 'cancelled', 'overdue', 'terlambat' => 'danger',
            'costing', 'review', 'negotiation', 'maintaining' => 'primary',
            default => 'secondary',
        };
    }
}

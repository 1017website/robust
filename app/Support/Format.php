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
            'assigned', 'scheduled', 'terjadwal', 'identify', 'sent_to_customer', 'processing_accurate' => 'info',
            'waiting_acceptance', 'waiting_approval', 'pending', 'menunggu', 'drafting', 'approaching', 'revision', 'submitted', 'stock' => 'warning',
            'accepted', 'approved', 'completed', 'selesai', 'won', 'won_closing', 'won / closing', 'customer_accepted', 'request_po_created', 'po_created', 'aktif', 'paid', 'follow_up', 'production_finished' => 'success',
            'rejected', 'customer_rejected', 'lost', 'cancelled', 'overdue', 'terlambat' => 'danger',
            'costing', 'review', 'reviewed', 'negotiation', 'maintaining', 'production' => 'primary',
            default => 'secondary',
        };
    }
}

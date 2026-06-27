<?php

namespace App\Services;

use App\Models\Quotation;

class QuotationCalculator
{
    /**
     * Hitung ulang subtotal, diskon, pajak, biaya tambahan, dan grand total.
     * Dipanggil setiap kali item/harga berubah.
     */
    public static function recalculate(Quotation $quotation): Quotation
    {
        $subtotal = $quotation->items->sum('total');

        // Diskon
        $discountAmount = $quotation->discount_type === 'percent'
            ? $subtotal * ((float) $quotation->discount_value / 100)
            : (float) $quotation->discount_value;
        $discountAmount = min($discountAmount, $subtotal);

        $afterDiscount = $subtotal - $discountAmount;

        // Biaya tambahan (pengiriman, instalasi, dll) - di luar PPN baris sendiri
        $additionalTotal = 0;
        foreach ((array) $quotation->additional_costs as $cost) {
            $additionalTotal += (float) ($cost['amount'] ?? 0);
        }

        // PPN dihitung dari (after discount + additional non-tax). Di sini sederhana: dari afterDiscount.
        $taxAmount = $afterDiscount * ((float) $quotation->tax_percent / 100);

        $grandTotal = $afterDiscount + $taxAmount + $additionalTotal;

        $quotation->subtotal = $subtotal;
        $quotation->discount_amount = $discountAmount;
        $quotation->tax_amount = $taxAmount;
        $quotation->additional_total = $additionalTotal;
        $quotation->grand_total = $grandTotal;

        return $quotation;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menormalkan input nominal rupiah dari front-end.
 * Field bertanda data-rupiah dikirim dengan pemisah ribuan titik (mis. "1.500.000").
 * Middleware ini menghapus titik ribuan agar nilai yang masuk ke controller berupa angka murni.
 *
 * Hanya field yang namanya cocok daftar di bawah (atau diakhiri _value/_price/_amount/cost_*)
 * yang dinormalkan, sehingga teks biasa tidak ikut terpangkas.
 */
class NormalizeCurrencyInput
{
    /** Nama field eksak yang dinormalkan. */
    protected array $exact = [
        'est_value_min', 'est_value_max', 'project_value', 'tax_amount',
        'discount_value', 'subtotal', 'grand_total', 'target_margin',
        'probability',
    ];

    /** Pola akhiran nama field yang dinormalkan. */
    protected array $suffixes = ['_value', '_price', '_amount'];

    /** Pola awalan nama field yang dinormalkan. */
    protected array $prefixes = ['cost_', 'biaya_'];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $this->walk($input);
        $request->merge($input);

        return $next($request);
    }

    /** Telusuri rekursif (mendukung array seperti items[][unit_price] / additional_costs[][amount]). */
    protected function walk(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->walk($value);
                continue;
            }
            if (is_string($value) && $this->shouldNormalize((string) $key)) {
                $value = $this->clean($value);
            }
        }
    }

    protected function shouldNormalize(string $key): bool
    {
        if (in_array($key, $this->exact, true)) {
            return true;
        }
        foreach ($this->suffixes as $s) {
            if (str_ends_with($key, $s)) {
                return true;
            }
        }
        foreach ($this->prefixes as $p) {
            if (str_starts_with($key, $p)) {
                return true;
            }
        }
        return false;
    }

    /** Hapus pemisah ribuan titik; pertahankan koma desimal -> titik. */
    protected function clean(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }
        // Buang semua kecuali digit, titik, koma, minus
        $value = preg_replace('/[^0-9.,\-]/', '', $value);
        // Format Indonesia: titik = ribuan, koma = desimal
        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);   // hapus ribuan
            $value = str_replace(',', '.', $value);  // koma -> titik desimal
        } else {
            $value = str_replace('.', '', $value);   // hanya ribuan -> hapus
        }
        return $value;
    }
}

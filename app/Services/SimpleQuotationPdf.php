<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SimpleQuotationPdf
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;
    private const LEFT = 40.0;
    private const RIGHT = 555.28;

    private ?array $logo = null;

    public function make(Quotation $quotation): string
    {
        $quotation->loadMissing('items', 'sales', 'approvedBy', 'customer');
        $this->logo = $this->loadLogo();

        $pages = $this->buildPages($quotation);

        foreach ($pages as $index => &$page) {
            $page .= $this->footer($index + 1, count($pages));
        }

        return $this->renderPdf($pages);
    }

    protected function buildPages(Quotation $quotation): array
    {
        $pages = [];
        [$page, $y] = $this->startPage($quotation, false, true);
        $rowIndex = 0;

        foreach ($quotation->items as $index => $item) {
            $nameLines = $this->wrap((string) $item->name, 30);
            $specLines = $this->wrap((string) $item->specification, 44);
            $rowHeight = max(42, 16 + count($nameLines) * 10 + count($specLines) * 8);

            if ($y - $rowHeight < 145) {
                $pages[] = $page;
                [$page, $y] = $this->startPage($quotation, true, true);
                $rowIndex = 0;
            }

            $page .= $this->itemRow($y, $rowHeight, $index + 1, $item, $nameLines, $specLines, $rowIndex % 2 === 1);
            $y -= $rowHeight;
            $rowIndex++;
        }

        if ($y < 275) {
            $pages[] = $page;
            [$page, $y] = $this->startPage($quotation, true, false);
        } else {
            $y -= 18;
        }

        $page .= $this->summarySection($quotation, $y);
        $pages[] = $page;

        return $pages;
    }

    protected function startPage(Quotation $quotation, bool $continued, bool $withTable): array
    {
        $content = '';
        $bandHeight = $continued ? 64 : 94;
        $bandBottom = self::PAGE_HEIGHT - $bandHeight;
        $content .= $this->rect(0, $bandBottom, self::PAGE_WIDTH, $bandHeight, [0.055, 0.12, 0.23]);
        $content .= $this->rect(0, $bandBottom, 8, $bandHeight, [0.11, 0.43, 0.88]);

        if ($this->logo) {
            $ratio = $this->logo['width'] / max(1, $this->logo['height']);
            $height = $continued ? 28 : 36;
            $width = min(120, $height * $ratio);
            $content .= sprintf("q %.2F 0 0 %.2F %.2F %.2F cm /Logo Do Q\n", $width, $height, self::LEFT, self::PAGE_HEIGHT - ($continued ? 47 : 62));
        } else {
            $content .= $this->text(self::LEFT, self::PAGE_HEIGHT - ($continued ? 37 : 45), 'ROBUST', $continued ? 18 : 22, true, [1, 1, 1]);
            $content .= $this->text(self::LEFT + ($continued ? 78 : 95), self::PAGE_HEIGHT - ($continued ? 33 : 40), 'CRM', 8, true, [0.38, 0.64, 1]);
        }

        if (! $continued) {
            $tagline = SystemSetting::value('company_tagline', 'Laboratory Furniture & Equipment');
            $content .= $this->text(self::LEFT, self::PAGE_HEIGHT - 68, (string) $tagline, 8.5, false, [0.72, 0.79, 0.88]);
        }

        $headerRight = self::RIGHT - 40;
        $content .= $this->text($headerRight, self::PAGE_HEIGHT - ($continued ? 31 : 38), $continued ? 'LANJUTAN PENAWARAN' : 'PENAWARAN HARGA', $continued ? 10 : 15, true, [1, 1, 1], 'right');
        $content .= $this->text($headerRight, self::PAGE_HEIGHT - ($continued ? 47 : 59), $quotation->code ?: '-', 9, false, [0.72, 0.79, 0.88], 'right');

        if ($continued) {
            $y = $bandBottom - 18;
        } else {
            $y = $bandBottom - 18;
            $content .= $this->metaCard($quotation, $y);
            $y -= 92;
        }

        if ($withTable) {
            $content .= $this->tableHeader($y);
            $y -= 24;
        }

        return [$content, $y];
    }

    protected function metaCard(Quotation $quotation, float $top): string
    {
        $bottom = $top - 74;
        $content = $this->rect(self::LEFT, $bottom, self::RIGHT - self::LEFT, 74, [0.965, 0.978, 1], [0.86, 0.90, 0.96]);
        $columns = [self::LEFT + 14, self::LEFT + 190, self::LEFT + 366];
        $content .= $this->labelValue($columns[0], $top - 17, 'CUSTOMER', $quotation->customer_name ?: '-');
        $content .= $this->labelValue($columns[1], $top - 17, 'PROJECT', $quotation->project_name ?: '-');
        $content .= $this->labelValue($columns[2], $top - 17, 'TANGGAL', optional($quotation->quote_date)->format('d/m/Y') ?: '-');
        $content .= $this->labelValue($columns[0], $top - 50, 'PIC / CONTACT PERSON', $quotation->pic_name ?: '-');
        $content .= $this->labelValue($columns[1], $top - 50, 'SALES', $quotation->sales?->name ?: '-');
        $content .= $this->labelValue($columns[2], $top - 50, 'BERLAKU SAMPAI', optional($quotation->valid_until)->format('d/m/Y') ?: '-');

        return $content;
    }

    protected function labelValue(float $x, float $y, string $label, string $value): string
    {
        return $this->text($x, $y, $label, 6.5, true, [0.38, 0.45, 0.58])
            .$this->text($x, $y - 13, $this->truncate($value, 28), 8.5, true, [0.07, 0.15, 0.29]);
    }

    protected function tableHeader(float $top): string
    {
        $bottom = $top - 24;
        $content = $this->rect(self::LEFT, $bottom, self::RIGHT - self::LEFT, 24, [0.11, 0.43, 0.88]);
        $headers = [
            [54, 'NO', 'center'], [78, 'ITEM & SPESIFIKASI', 'left'], [281, 'QTY', 'center'],
            [327, 'UNIT', 'center'], [430, 'HARGA SATUAN', 'right'], [548, 'TOTAL', 'right'],
        ];
        foreach ($headers as [$x, $label, $align]) {
            $content .= $this->text($x, $bottom + 8, $label, 7, true, [1, 1, 1], $align);
        }

        return $content;
    }

    protected function itemRow(float $top, float $height, int $number, object $item, array $nameLines, array $specLines, bool $alternate): string
    {
        $bottom = $top - $height;
        $fill = $alternate ? [0.975, 0.983, 0.996] : [1, 1, 1];
        $content = $this->rect(self::LEFT, $bottom, self::RIGHT - self::LEFT, $height, $fill, [0.88, 0.91, 0.95]);
        foreach ([68, 258, 303, 351, 441] as $x) {
            $content .= $this->line($x, $bottom, $x, $top, [0.90, 0.93, 0.97], 0.45);
        }

        $content .= $this->text(54, $top - 18, (string) $number, 8, true, [0.20, 0.28, 0.40], 'center');
        $textY = $top - 15;
        foreach ($nameLines as $line) {
            $content .= $this->text(76, $textY, $line, 8.3, true, [0.06, 0.14, 0.27]);
            $textY -= 10;
        }
        foreach ($specLines as $line) {
            $content .= $this->text(76, $textY, $line, 7.2, false, [0.40, 0.47, 0.58]);
            $textY -= 8;
        }

        $content .= $this->text(280.5, $top - 18, $this->quantity((float) $item->qty), 8, false, [0.12, 0.20, 0.34], 'center');
        $content .= $this->text(327, $top - 18, $this->truncate($item->unit ?: 'Unit', 9), 8, false, [0.12, 0.20, 0.34], 'center');
        $content .= $this->text(433, $top - 18, $this->money((float) $item->unit_price), 8, false, [0.12, 0.20, 0.34], 'right');
        $content .= $this->text(548, $top - 18, $this->money((float) $item->total), 8, true, [0.06, 0.14, 0.27], 'right');

        return $content;
    }

    protected function summarySection(Quotation $quotation, float $top): string
    {
        $content = '';
        $leftWidth = 260;
        $rightX = 322;
        $rightWidth = self::RIGHT - $rightX;

        $content .= $this->text(self::LEFT, $top, 'CATATAN & KETENTUAN', 8, true, [0.11, 0.43, 0.88]);
        $noteTop = $top - 12;
        $note = $quotation->customer_note ?: 'Harga berlaku sesuai periode penawaran. Perubahan spesifikasi dapat memengaruhi harga dan waktu pengerjaan.';
        $noteLines = array_slice($this->wrap($note, 54), 0, 7);
        $noteHeight = max(72, 26 + count($noteLines) * 10);
        $content .= $this->rect(self::LEFT, $noteTop - $noteHeight, $leftWidth, $noteHeight, [0.975, 0.983, 0.996], [0.88, 0.91, 0.95]);
        $lineY = $noteTop - 17;
        foreach ($noteLines as $line) {
            $content .= $this->text(self::LEFT + 12, $lineY, $line, 7.6, false, [0.30, 0.38, 0.50]);
            $lineY -= 10;
        }

        $summaryHeight = 126;
        $content .= $this->rect($rightX, $top - $summaryHeight, $rightWidth, $summaryHeight, [1, 1, 1], [0.82, 0.87, 0.94]);
        $content .= $this->rect($rightX, $top - 27, $rightWidth, 27, [0.055, 0.12, 0.23]);
        $content .= $this->text($rightX + 12, $top - 18, 'RINGKASAN HARGA', 8, true, [1, 1, 1]);
        $rows = [
            ['Subtotal', (float) $quotation->subtotal, false],
            ['Diskon', -((float) $quotation->discount_amount), false],
            ['PPN '.$this->quantity((float) $quotation->tax_percent).'%', (float) $quotation->tax_amount, false],
            ['Biaya Tambahan', (float) $quotation->additional_total, false],
            ['GRAND TOTAL', (float) $quotation->grand_total, true],
        ];
        $rowY = $top - 43;
        foreach ($rows as [$label, $amount, $bold]) {
            if ($bold) {
                $content .= $this->line($rightX + 12, $rowY + 6, self::RIGHT - 12, $rowY + 6, [0.80, 0.85, 0.92], 0.8);
            }
            $content .= $this->text($rightX + 12, $rowY, $label, $bold ? 8.5 : 7.5, $bold, $bold ? [0.055, 0.12, 0.23] : [0.38, 0.45, 0.56]);
            $display = $amount < 0 ? '- '.$this->money(abs($amount)) : $this->money($amount);
            $content .= $this->text(self::RIGHT - 12, $rowY, $display, $bold ? 9.5 : 8, $bold, $bold ? [0.11, 0.43, 0.88] : [0.10, 0.18, 0.31], 'right');
            $rowY -= $bold ? 20 : 16;
        }

        $approvalTop = min($noteTop - $noteHeight, $top - $summaryHeight) - 18;
        $content .= $this->rect(self::LEFT, $approvalTop - 62, self::RIGHT - self::LEFT, 62, [0.94, 0.98, 0.96], [0.75, 0.90, 0.82]);
        $content .= $this->text(self::LEFT + 14, $approvalTop - 19, 'DOKUMEN TELAH DISETUJUI', 8.5, true, [0.05, 0.48, 0.28]);
        $content .= $this->text(self::LEFT + 14, $approvalTop - 36, 'Approved by: '.($quotation->approvedBy?->name ?: '-'), 7.5, false, [0.25, 0.40, 0.33]);
        $content .= $this->text(self::LEFT + 14, $approvalTop - 49, 'Tanggal approval: '.(optional($quotation->approved_at)->format('d/m/Y H:i') ?: '-'), 7.5, false, [0.25, 0.40, 0.33]);
        $content .= $this->text(self::RIGHT - 14, $approvalTop - 28, 'APPROVED SPV', 10, true, [0.05, 0.48, 0.28], 'right');
        $content .= $this->text(self::RIGHT - 14, $approvalTop - 44, 'Dokumen diterbitkan oleh ROBUST CRM', 7, false, [0.35, 0.50, 0.42], 'right');

        return $content;
    }

    protected function footer(int $page, int $total): string
    {
        $content = $this->line(self::LEFT, 34, self::RIGHT, 34, [0.84, 0.88, 0.93], 0.6);
        $content .= $this->text(self::LEFT, 20, 'ROBUST - Laboratory Furniture & Equipment', 6.7, false, [0.45, 0.52, 0.62]);
        $content .= $this->text(self::PAGE_WIDTH / 2, 20, 'Generated '.now()->format('d/m/Y H:i'), 6.7, false, [0.55, 0.61, 0.70], 'center');
        $content .= $this->text(self::RIGHT, 20, 'Halaman '.$page.' / '.$total, 6.7, true, [0.35, 0.43, 0.55], 'right');

        return $content;
    }

    protected function renderPdf(array $pages): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $nextObj = 5;
        $logoObject = null;

        if ($this->logo) {
            $logoObject = $nextObj++;
            $bytes = $this->logo['data'];
            $objects[$logoObject] = '<< /Type /XObject /Subtype /Image /Width '.$this->logo['width'].' /Height '.$this->logo['height'].' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($bytes).">>\nstream\n".$bytes."\nendstream";
        }

        $pageRefs = [];
        foreach ($pages as $stream) {
            $pageObj = $nextObj++;
            $contentObj = $nextObj++;
            $pageRefs[] = $pageObj.' 0 R';
            $xObject = $logoObject ? ' /XObject << /Logo '.$logoObject.' 0 R >>' : '';
            $objects[$pageObj] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>'.$xObject.' >> /Contents '.$contentObj.' 0 R >>';
            $objects[$contentObj] = '<< /Length '.strlen($stream).">>\nstream\n".$stream."\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageRefs).'] /Count '.count($pageRefs).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        $maxObject = max(array_keys($objects));
        for ($i = 1; $i <= $maxObject; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i." 0 obj\n".$objects[$i]."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".($maxObject + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= $maxObject; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= 'trailer << /Size '.($maxObject + 1).' /Root 1 0 R >>'."\nstartxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    protected function loadLogo(): ?array
    {
        try {
            $stored = SystemSetting::value('company_logo');
            if (! $stored || str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://')) {
                return null;
            }

            $path = Storage::disk('public')->path($stored);
            if (! is_file($path)) {
                return null;
            }

            $info = @getimagesize($path);
            if (! $info) {
                return null;
            }

            if (($info['mime'] ?? '') === 'image/jpeg') {
                return ['data' => file_get_contents($path), 'width' => $info[0], 'height' => $info[1]];
            }

            $image = @imagecreatefromstring(file_get_contents($path));
            if (! $image) {
                return null;
            }
            $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
            imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            ob_start();
            imagejpeg($canvas, null, 92);
            $bytes = ob_get_clean();
            imagedestroy($image);
            imagedestroy($canvas);

            return ['data' => $bytes, 'width' => $info[0], 'height' => $info[1]];
        } catch (Throwable) {
            return null;
        }
    }

    protected function rect(float $x, float $y, float $width, float $height, array $fill, ?array $stroke = null): string
    {
        $command = sprintf("q %.3F %.3F %.3F rg ", ...$fill);
        if ($stroke) {
            $command .= sprintf("%.3F %.3F %.3F RG 0.6 w ", ...$stroke);
        }
        $command .= sprintf("%.2F %.2F %.2F %.2F re %s Q\n", $x, $y, $width, $height, $stroke ? 'B' : 'f');

        return $command;
    }

    protected function line(float $x1, float $y1, float $x2, float $y2, array $color, float $width): string
    {
        return sprintf("q %.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S Q\n", $color[0], $color[1], $color[2], $width, $x1, $y1, $x2, $y2);
    }

    protected function text(float $x, float $y, string $value, float $size, bool $bold, array $color, string $align = 'left'): string
    {
        $encoded = $this->pdfString($value);
        $estimatedWidth = mb_strlen($value) * $size * 0.50;
        if ($align === 'right') {
            $x -= $estimatedWidth;
        } elseif ($align === 'center') {
            $x -= $estimatedWidth / 2;
        }

        return sprintf("BT /%s %.2F Tf %.3F %.3F %.3F rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n", $bold ? 'F2' : 'F1', $size, $color[0], $color[1], $color[2], $x, $y, $encoded);
    }

    protected function pdfString(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? '';
        $value = str_replace(["\u{2013}", "\u{2014}", "\u{2022}"], ['-', '-', '-'], $value);
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: $value;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }

    protected function wrap(string $value, int $width): array
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' ? [] : explode("\n", wordwrap($value, $width, "\n", true));
    }

    protected function truncate(string $value, int $length): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length - 1).'...' : $value;
    }

    protected function money(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    protected function quantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }
}

<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\SystemSetting;

class DeliveryOrderPdf extends SimpleQuotationPdf
{
    private const WIDTH = 595.28;
    private const HEIGHT = 841.89;
    private const LEFT = 40.0;
    private const RIGHT = 555.28;
    private const CONTENT_WIDTH = self::RIGHT - self::LEFT;

    public function makeDeliveryOrder(DeliveryOrder $deliveryOrder): string
    {
        $deliveryOrder->loadMissing([
            'creator',
            'project.customer',
            'project.projectManager',
            'project.quotation.purchaseOrderRequest',
        ]);
        $this->logo = $this->loadLogo();

        $pages = [];
        [$page, $y] = $this->startDeliveryPage($deliveryOrder, false, true);

        foreach (array_values($deliveryOrder->items ?? []) as $index => $item) {
            $nameLines = array_slice($this->wrap((string) ($item['name'] ?? '-'), 66), 0, 4);
            $rowHeight = max(38, 18 + count($nameLines) * 10);
            if ($y - $rowHeight < 230) {
                $pages[] = $page;
                [$page, $y] = $this->startDeliveryPage($deliveryOrder, true, true);
            }

            $page .= $this->deliveryItemRow($y, $rowHeight, $index + 1, $item, $nameLines, $index % 2 === 1);
            $y -= $rowHeight;
        }

        if ($y < 285) {
            $pages[] = $page;
            [$page, $y] = $this->startDeliveryPage($deliveryOrder, true, false);
        } else {
            $y -= 20;
        }

        $page .= $this->notesAndSignatures($deliveryOrder, $y);
        $pages[] = $page;

        $total = count($pages);
        foreach ($pages as $index => &$pageContent) {
            $pageContent .= $this->deliveryFooter($index + 1, $total, $deliveryOrder);
        }

        return $this->renderPdf($pages);
    }

    private function startDeliveryPage(DeliveryOrder $deliveryOrder, bool $continued, bool $withTable): array
    {
        $page = $this->rect(0, self::HEIGHT - 6, self::WIDTH, 6, [0.035, 0.086, 0.165]);
        $page .= $this->rect(self::LEFT, self::HEIGHT - 6, 92, 6, [0.055, 0.45, 0.92]);

        if ($this->logo) {
            $ratio = $this->logo['width'] / max(1, $this->logo['height']);
            $logoHeight = $continued ? 25 : 31;
            $logoWidth = min(130, $logoHeight * $ratio);
            $page .= sprintf(
                "q %.2F 0 0 %.2F %.2F %.2F cm /Logo Do Q\n",
                $logoWidth,
                $logoHeight,
                self::LEFT,
                self::HEIGHT - ($continued ? 55 : 59),
            );
        } else {
            $page .= $this->text(self::LEFT, self::HEIGHT - 40, 'ROBUST', $continued ? 18 : 22, true, [0.035, 0.086, 0.165]);
            $page .= $this->rect(self::LEFT + ($continued ? 78 : 95), self::HEIGHT - 30, 5, 5, [0.055, 0.45, 0.92]);
        }

        if (! $continued) {
            $tagline = SystemSetting::value('company_tagline', 'Laboratory Furniture & Equipment');
            $page .= $this->text(self::LEFT, self::HEIGHT - 61, (string) $tagline, 7.5, false, [0.42, 0.48, 0.57]);
        }

        $headerRight = self::RIGHT - 38;
        $title = $continued ? 'DELIVERY ORDER - LANJUTAN' : 'DELIVERY ORDER';
        $page .= $this->text($headerRight, self::HEIGHT - 39, $title, $continued ? 10 : 16, true, [0.035, 0.086, 0.165], 'right');
        $page .= $this->text($headerRight, self::HEIGHT - 59, $deliveryOrder->code, 9, true, [0.055, 0.45, 0.92], 'right');
        $dividerY = self::HEIGHT - 82;
        $page .= $this->line(self::LEFT, $dividerY, self::RIGHT, $dividerY, [0.78, 0.83, 0.89], 0.8);
        $page .= $this->rect(self::LEFT, $dividerY - 1, 62, 2, [0.055, 0.45, 0.92]);
        $y = $dividerY - 18;

        if (! $continued) {
            $page .= $this->metaPanel($deliveryOrder, $y);
            $y -= 104;
            $page .= $this->addressPanel($deliveryOrder, $y);
            $y -= 112;
        }

        if ($withTable) {
            $page .= $this->deliveryTableHeader($y);
            $y -= 26;
        }

        return [$page, $y];
    }

    private function metaPanel(DeliveryOrder $deliveryOrder, float $top): string
    {
        $project = $deliveryOrder->project;
        $purchaseOrder = $project?->quotation?->purchaseOrderRequest;
        $height = 86;
        $columnWidth = self::CONTENT_WIDTH / 3;
        $fields = [
            ['CUSTOMER', $project?->customer?->name],
            ['PROJECT', $project?->name],
            ['NOMOR PROJECT', $project?->code],
            ['TANGGAL KIRIM', $deliveryOrder->delivery_date?->format('d/m/Y')],
            ['REFERENSI PO', $purchaseOrder?->accurate_po_number ?: $purchaseOrder?->customer_po_number],
            ['DIBUAT OLEH', $deliveryOrder->creator?->name],
        ];

        $content = $this->rect(self::LEFT, $top - $height, self::CONTENT_WIDTH, $height, [0.982, 0.986, 0.992]);
        $content .= $this->line(self::LEFT, $top, self::RIGHT, $top, [0.84, 0.88, 0.92], 0.6);
        $content .= $this->line(self::LEFT, $top - $height, self::RIGHT, $top - $height, [0.84, 0.88, 0.92], 0.6);

        foreach ($fields as $index => [$label, $value]) {
            $column = $index % 3;
            $row = intdiv($index, 3);
            $x = self::LEFT + $column * $columnWidth + 14;
            $y = $top - 18 - $row * 38;
            if ($column > 0) {
                $lineX = self::LEFT + $column * $columnWidth;
                $content .= $this->line($lineX, $top - $height + 12, $lineX, $top - 12, [0.86, 0.90, 0.95], 0.55);
            }
            $content .= $this->text($x, $y, $label, 6.4, true, [0.39, 0.47, 0.59]);
            $content .= $this->text($x, $y - 13, $this->truncate((string) ($value ?: '-'), 35), 8.1, true, [0.05, 0.12, 0.23]);
        }

        return $content;
    }

    private function addressPanel(DeliveryOrder $deliveryOrder, float $top): string
    {
        $height = 94;
        $mid = self::LEFT + self::CONTENT_WIDTH * 0.59;
        $content = $this->rect(self::LEFT, $top - $height, self::CONTENT_WIDTH, $height, [1, 1, 1]);
        $content .= $this->line(self::LEFT, $top, self::RIGHT, $top, [0.82, 0.86, 0.91], 0.7);
        $content .= $this->line(self::LEFT, $top - $height, self::RIGHT, $top - $height, [0.82, 0.86, 0.91], 0.7);
        $content .= $this->line($mid, $top - $height + 10, $mid, $top - 10, [0.87, 0.91, 0.95], 0.5);
        $content .= $this->text(self::LEFT + 14, $top - 18, 'ALAMAT PENGIRIMAN', 6.5, true, [0.42, 0.49, 0.60]);
        $lineY = $top - 34;
        foreach (array_slice($this->wrap($deliveryOrder->delivery_address, 64), 0, 5) as $line) {
            $content .= $this->text(self::LEFT + 14, $lineY, $line, 8, false, [0.06, 0.13, 0.24]);
            $lineY -= 11;
        }

        $rightX = $mid + 14;
        $content .= $this->text($rightX, $top - 18, 'PIC PENERIMA', 6.5, true, [0.42, 0.49, 0.60]);
        $content .= $this->text($rightX, $top - 34, $this->truncate($deliveryOrder->recipient_name ?: '-', 31), 8.1, true, [0.06, 0.13, 0.24]);
        $content .= $this->text($rightX, $top - 48, $deliveryOrder->recipient_phone ?: '-', 7.5, false, [0.31, 0.39, 0.51]);
        $content .= $this->text($rightX, $top - 66, 'PENGEMUDI / KENDARAAN', 6.5, true, [0.42, 0.49, 0.60]);
        $content .= $this->text($rightX, $top - 81, $this->truncate(trim(($deliveryOrder->driver_name ?: '-').' / '.($deliveryOrder->vehicle_number ?: '-')), 35), 7.7, true, [0.06, 0.13, 0.24]);

        return $content;
    }

    private function deliveryTableHeader(float $top): string
    {
        $bottom = $top - 26;
        $content = $this->rect(self::LEFT, $bottom, self::CONTENT_WIDTH, 26, [0.035, 0.086, 0.165]);
        $content .= $this->rect(self::LEFT, $bottom, 4, 26, [0.055, 0.45, 0.92]);
        foreach ([[54, 'NO', 'center'], [78, 'DESKRIPSI BARANG', 'left'], [458, 'QTY', 'center'], [535, 'SATUAN', 'center']] as [$x, $label, $align]) {
            $content .= $this->text($x, $bottom + 9, $label, 7, true, [1, 1, 1], $align);
        }

        return $content;
    }

    private function deliveryItemRow(float $top, float $height, int $number, array $item, array $nameLines, bool $alternate): string
    {
        $bottom = $top - $height;
        $content = $this->rect(self::LEFT, $bottom, self::CONTENT_WIDTH, $height, $alternate ? [0.985, 0.988, 0.993] : [1, 1, 1]);
        $content .= $this->line(self::LEFT, $bottom, self::RIGHT, $bottom, [0.84, 0.88, 0.92], 0.55);
        foreach ([68, 420, 494] as $x) {
            $content .= $this->line($x, $bottom, $x, $top, [0.90, 0.93, 0.97], 0.45);
        }
        $content .= $this->text(54, $top - 21, (string) $number, 8, true, [0.20, 0.28, 0.40], 'center');
        foreach ($nameLines as $index => $line) {
            $content .= $this->text(78, $top - 18 - $index * 10, $line, 8.1, $index === 0, [0.06, 0.14, 0.27]);
        }
        $content .= $this->text(458, $top - 21, $this->quantity((float) ($item['qty'] ?? 0)), 8.2, true, [0.12, 0.20, 0.34], 'center');
        $content .= $this->text(535, $top - 21, $this->truncate((string) ($item['unit'] ?? 'Unit'), 12), 8, false, [0.12, 0.20, 0.34], 'center');

        return $content;
    }

    private function notesAndSignatures(DeliveryOrder $deliveryOrder, float $top): string
    {
        $content = $this->text(self::LEFT, $top, 'CATATAN PENGIRIMAN', 8, true, [0.035, 0.086, 0.165]);
        $noteTop = $top - 12;
        $content .= $this->rect(self::LEFT, $noteTop - 58, self::CONTENT_WIDTH, 58, [0.985, 0.988, 0.993]);
        $content .= $this->line(self::LEFT, $noteTop, self::RIGHT, $noteTop, [0.055, 0.45, 0.92], 1.1);
        $lineY = $noteTop - 18;
        foreach (array_slice($this->wrap($deliveryOrder->notes ?: 'Barang diterima dalam kondisi baik dan lengkap.', 105), 0, 4) as $line) {
            $content .= $this->text(self::LEFT + 12, $lineY, $line, 7.6, false, [0.30, 0.38, 0.50]);
            $lineY -= 10;
        }

        $signatureTop = $noteTop - 84;
        $boxWidth = (self::CONTENT_WIDTH - 20) / 3;
        foreach (['DISIAPKAN OLEH', 'PENGEMUDI', 'DITERIMA OLEH'] as $index => $label) {
            $x = self::LEFT + $index * ($boxWidth + 10);
            $content .= $this->rect($x, $signatureTop - 94, $boxWidth, 94, [1, 1, 1], [0.82, 0.86, 0.91]);
            $content .= $this->text($x + $boxWidth / 2, $signatureTop - 17, $label, 6.8, true, [0.38, 0.45, 0.56], 'center');
            $content .= $this->line($x + 18, $signatureTop - 69, $x + $boxWidth - 18, $signatureTop - 69, [0.50, 0.56, 0.65], 0.5);
            $name = match ($index) {
                0 => $deliveryOrder->creator?->name ?: '-',
                1 => $deliveryOrder->driver_name ?: '-',
                default => $deliveryOrder->recipient_name ?: '-',
            };
            $content .= $this->text($x + $boxWidth / 2, $signatureTop - 84, $this->truncate($name, 28), 7.2, true, [0.08, 0.15, 0.26], 'center');
        }

        return $content;
    }

    private function deliveryFooter(int $page, int $total, DeliveryOrder $deliveryOrder): string
    {
        $content = $this->line(self::LEFT, 34, self::RIGHT, 34, [0.84, 0.88, 0.93], 0.6);
        $content .= $this->text(self::LEFT, 20, 'ROBUST - Laboratory Furniture & Equipment', 6.7, false, [0.42, 0.49, 0.59]);
        $content .= $this->text(self::WIDTH / 2, 20, $deliveryOrder->code.' - Generated '.now()->format('d/m/Y H:i'), 6.5, false, [0.53, 0.59, 0.68], 'center');
        $content .= $this->text(self::RIGHT, 20, 'Halaman '.$page.' / '.$total, 6.7, true, [0.34, 0.41, 0.52], 'right');

        return $content;
    }
}

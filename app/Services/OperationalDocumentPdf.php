<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceTerm;
use App\Models\PurchaseOrderRequest;
use App\Support\StructuredSpecification;

class OperationalDocumentPdf extends SimpleQuotationPdf
{
    private const WIDTH = 595.28;
    private const HEIGHT = 841.89;
    private const LEFT = 40.0;
    private const RIGHT = 555.28;
    private const CONTENT_WIDTH = self::RIGHT - self::LEFT;

    private string $documentLabel = '';

    public function makeRequestPo(PurchaseOrderRequest $requestPo): string
    {
        $requestPo->loadMissing([
            'quotation.items',
            'quotation.sales',
            'quotation.customer.primaryPic',
            'requester',
            'invoice',
        ]);
        $this->logo = $this->loadLogo();
        $this->documentLabel = 'REQUEST PURCHASE ORDER';

        $pages = $this->buildRequestPoPages($requestPo);

        return $this->finish($pages);
    }

    public function makeInvoice(Invoice $invoice): string
    {
        $invoice->loadMissing([
            'terms',
            'creator',
            'purchaseOrderRequest.requester',
            'purchaseOrderRequest.quotation.items',
            'purchaseOrderRequest.quotation.sales',
            'purchaseOrderRequest.quotation.customer.primaryPic',
        ]);
        $this->logo = $this->loadLogo();
        $this->documentLabel = 'INVOICE';

        $pages = $this->buildInvoicePages($invoice);

        return $this->finish($pages);
    }

    private function buildRequestPoPages(PurchaseOrderRequest $requestPo): array
    {
        $pages = [];
        [$page, $y] = $this->documentHeader(
            'REQUEST PURCHASE ORDER',
            $requestPo->code,
            'Dokumen administrasi pemrosesan purchase order',
            PurchaseOrderRequest::statuses()[$requestPo->status] ?? str($requestPo->status)->headline(),
        );

        $quotation = $requestPo->quotation;
        $page .= $this->metaGrid($y, [
            ['CUSTOMER', $requestPo->customer_name ?: $quotation?->customer_name],
            ['PROJECT', $quotation?->project_name],
            ['NOMOR PROYEK', $requestPo->project_number],
            ['TANGGAL REQUEST', $requestPo->request_date?->format('d/m/Y')],
            ['SALES', $quotation?->sales?->name],
            ['DIBUAT OLEH', $requestPo->requester?->name],
        ]);
        $y -= 104;

        $page .= $this->sectionTitle($y, 'INFORMASI ORDER & ACCURATE', '01');
        $y -= 22;
        $page .= $this->detailPanel($y, 140, [
            ['No. PO Customer', $requestPo->customer_po_number],
            ['No. PO Accurate', $requestPo->accurate_po_number],
            ['Tanggal PO Accurate', $requestPo->accurate_po_date?->format('d/m/Y')],
            ['Area / Lokasi', $requestPo->customer_area],
            ['Divisi Customer', $requestPo->customer_division],
            ['Estimasi Pengiriman', $requestPo->expected_delivery_date?->format('d/m/Y')],
            ['Termin Pembayaran', $requestPo->payment_term],
            ['Nilai Penawaran', $this->money((float) ($quotation?->grand_total ?? 0))],
        ], 2);
        $y -= 156;

        $page .= $this->sectionTitle($y, 'PENGIRIMAN & BILLING', '02');
        $y -= 22;
        $page .= $this->addressPanel($y, $requestPo);
        $y -= 108;

        $page .= $this->sectionTitle($y, 'CHECKLIST KELENGKAPAN', '03');
        $y -= 22;
        $checklistHeight = 28 + count(PurchaseOrderRequest::checklistItems()) * 18;
        if ($y - $checklistHeight < 58) {
            $pages[] = $page;
            [$page, $y] = $this->documentHeader(
                'REQUEST PURCHASE ORDER',
                $requestPo->code,
                'Checklist dan item penawaran - lanjutan',
                PurchaseOrderRequest::statuses()[$requestPo->status] ?? str($requestPo->status)->headline(),
                true,
            );
        }
        $page .= $this->checklistPanel($y, $requestPo);
        $y -= $checklistHeight + 18;

        if ($y < 170) {
            $pages[] = $page;
            [$page, $y] = $this->documentHeader(
                'REQUEST PURCHASE ORDER',
                $requestPo->code,
                'Item penawaran - lanjutan',
                PurchaseOrderRequest::statuses()[$requestPo->status] ?? str($requestPo->status)->headline(),
                true,
            );
        }

        $page .= $this->sectionTitle($y, 'ITEM PENAWARAN', '04');
        $y -= 22;
        $page .= $this->requestItemHeader($y);
        $y -= 24;

        foreach ($quotation?->items ?? [] as $index => $item) {
            $specLines = $this->structuredSpecificationLines((string) $item->specification, 59);
            if (! $specLines) {
                $specLines = ['Tidak ada spesifikasi tambahan.'];
            }

            $firstChunk = true;
            while ($firstChunk || $specLines) {
                $available = $y - 74;
                $maxLines = max(1, (int) floor(($available - 48) / 8));
                if ($maxLines < 4) {
                    $pages[] = $page;
                    [$page, $y] = $this->documentHeader(
                        'REQUEST PURCHASE ORDER',
                        $requestPo->code,
                        'Item penawaran - lanjutan',
                        PurchaseOrderRequest::statuses()[$requestPo->status] ?? str($requestPo->status)->headline(),
                        true,
                    );
                    $page .= $this->requestItemHeader($y);
                    $y -= 24;
                    $maxLines = max(4, (int) floor(($y - 122) / 8));
                }

                $chunk = array_splice($specLines, 0, min($maxLines, count($specLines)));
                $rowHeight = max(58, 42 + count($chunk) * 8);
                $page .= $this->requestItemRow(
                    $y,
                    $rowHeight,
                    $index + 1,
                    $item,
                    $chunk,
                    ! $firstChunk,
                    $index % 2 === 1,
                );
                $y -= $rowHeight;
                $firstChunk = false;
            }
        }

        if ($y < 170) {
            $pages[] = $page;
            [$page, $y] = $this->documentHeader(
                'REQUEST PURCHASE ORDER',
                $requestPo->code,
                'Ringkasan - lanjutan',
                PurchaseOrderRequest::statuses()[$requestPo->status] ?? str($requestPo->status)->headline(),
                true,
            );
        } else {
            $y -= 18;
        }

        $page .= $this->requestPoSummary($y, $requestPo);
        $pages[] = $page;

        return $pages;
    }

    private function buildInvoicePages(Invoice $invoice): array
    {
        $pages = [];
        [$page, $y] = $this->documentHeader(
            'INVOICE',
            $invoice->code,
            'Tagihan resmi proyek dan termin pembayaran',
            Invoice::statuses()[$invoice->status] ?? str($invoice->status)->headline(),
        );

        $requestPo = $invoice->purchaseOrderRequest;
        $quotation = $requestPo?->quotation;
        $page .= $this->metaGrid($y, [
            ['DITAGIHKAN KEPADA', $invoice->customer_name],
            ['PROJECT', $invoice->project_name],
            ['NOMOR PROYEK', $invoice->project_number],
            ['TANGGAL INVOICE', $invoice->invoice_date?->format('d/m/Y')],
            ['REQUEST PO', $requestPo?->code],
            ['SALES', $quotation?->sales?->name],
        ]);
        $y -= 104;

        $page .= $this->invoiceMetricCards($y, $invoice);
        $y -= 82;

        $page .= $this->sectionTitle($y, 'RINCIAN TAGIHAN', '01');
        $y -= 22;
        $page .= $this->invoiceItemHeader($y);
        $y -= 24;

        foreach ($quotation?->items ?? [] as $index => $item) {
            $nameLines = $this->wrap(
                trim($item->name.($item->variant ? ' - '.$item->variant : '')),
                42,
            );
            $rowHeight = max(42, 20 + count($nameLines) * 10);
            if ($y - $rowHeight < 90) {
                $pages[] = $page;
                [$page, $y] = $this->documentHeader(
                    'INVOICE',
                    $invoice->code,
                    'Rincian tagihan - lanjutan',
                    Invoice::statuses()[$invoice->status] ?? str($invoice->status)->headline(),
                    true,
                );
                $page .= $this->invoiceItemHeader($y);
                $y -= 24;
            }
            $page .= $this->invoiceItemRow($y, $rowHeight, $index + 1, $item, $nameLines, $index % 2 === 1);
            $y -= $rowHeight;
        }

        if ($y < 280) {
            $pages[] = $page;
            [$page, $y] = $this->documentHeader(
                'INVOICE',
                $invoice->code,
                'Ringkasan dan termin pembayaran',
                Invoice::statuses()[$invoice->status] ?? str($invoice->status)->headline(),
                true,
            );
        } else {
            $y -= 18;
        }

        $page .= $this->invoiceSummary($y, $invoice);
        $y -= 158;
        $page .= $this->sectionTitle($y, 'TERMIN PEMBAYARAN', '02');
        $y -= 22;
        $page .= $this->invoiceTerms($y, $invoice);
        $termHeight = 28 + max(1, $invoice->terms->count()) * 28;
        $y -= $termHeight + 18;

        if ($y > 92) {
            $page .= $this->invoiceNote($y, $invoice);
        }
        $pages[] = $page;

        return $pages;
    }

    private function documentHeader(
        string $title,
        string $code,
        string $subtitle,
        string $status,
        bool $continued = false,
    ): array {
        $content = $this->rect(0, self::HEIGHT - 6, self::WIDTH, 6, [0.035, 0.086, 0.165]);
        $content .= $this->rect(self::LEFT, self::HEIGHT - 6, 92, 6, [0.055, 0.45, 0.92]);

        if ($this->logo) {
            $ratio = $this->logo['width'] / max(1, $this->logo['height']);
            $logoHeight = $continued ? 25 : 31;
            $logoWidth = min(135, $logoHeight * $ratio);
            $content .= sprintf(
                "q %.2F 0 0 %.2F %.2F %.2F cm /Logo Do Q\n",
                $logoWidth,
                $logoHeight,
                self::LEFT,
                self::HEIGHT - ($continued ? 55 : 59),
            );
        } else {
            $content .= $this->text(self::LEFT, self::HEIGHT - 40, 'ROBUST', $continued ? 18 : 22, true, [0.035, 0.086, 0.165]);
            $content .= $this->rect(self::LEFT + ($continued ? 78 : 95), self::HEIGHT - 30, 5, 5, [0.055, 0.45, 0.92]);
            if (! $continued) {
                $content .= $this->text(self::LEFT, self::HEIGHT - 61, 'Laboratory Furniture & Equipment', 7.5, false, [0.42, 0.48, 0.57]);
            }
        }

        $titleY = self::HEIGHT - 39;
        $headerRight = self::RIGHT - 38;
        $titleSize = $continued ? 9.2 : (mb_strlen($title) > 18 ? 13 : 17);
        $content .= $this->text($headerRight, $titleY, $continued ? $title.' - LANJUTAN' : $title, $titleSize, true, [0.035, 0.086, 0.165], 'right');
        $content .= $this->text($headerRight, $titleY - 20, $code ?: '-', 9, true, [0.055, 0.45, 0.92], 'right');
        if (! $continued) {
            $content .= $this->text(self::LEFT, self::HEIGHT - 76, $subtitle, 7, false, [0.42, 0.48, 0.57]);
        }

        $statusWidth = min(150, max(70, mb_strlen($status) * 5.4 + 24));
        $content .= $this->rect(self::RIGHT - $statusWidth, self::HEIGHT - 82, $statusWidth, 18, [0.92, 0.97, 0.94]);
        $content .= $this->text(self::RIGHT - 10, self::HEIGHT - 76, strtoupper($status), 6.6, true, [0.05, 0.47, 0.28], 'right');
        $dividerY = self::HEIGHT - 92;
        $content .= $this->line(self::LEFT, $dividerY, self::RIGHT, $dividerY, [0.78, 0.83, 0.89], 0.8);
        $content .= $this->rect(self::LEFT, $dividerY - 1, 62, 2, [0.055, 0.45, 0.92]);

        return [$content, $dividerY - 18];
    }

    private function metaGrid(float $top, array $fields): string
    {
        $height = 86;
        $content = $this->rect(self::LEFT, $top - $height, self::CONTENT_WIDTH, $height, [0.982, 0.986, 0.992]);
        $content .= $this->line(self::LEFT, $top, self::RIGHT, $top, [0.84, 0.88, 0.92], 0.6);
        $content .= $this->line(self::LEFT, $top - $height, self::RIGHT, $top - $height, [0.84, 0.88, 0.92], 0.6);
        $columnWidth = self::CONTENT_WIDTH / 3;

        foreach (array_values($fields) as $index => [$label, $value]) {
            $column = $index % 3;
            $row = intdiv($index, 3);
            $x = self::LEFT + $column * $columnWidth + 14;
            $y = $top - 18 - $row * 38;
            if ($column > 0) {
                $lineX = self::LEFT + $column * $columnWidth;
                $content .= $this->line($lineX, $top - $height + 12, $lineX, $top - 12, [0.86, 0.90, 0.95], 0.55);
            }
            $content .= $this->text($x, $y, $label, 6.4, true, [0.39, 0.47, 0.59]);
            $displayValue = (string) ($value ?: '-');
            $valueFontSize = mb_strlen($displayValue) > 31 ? 7.2 : 8.4;
            $content .= $this->text($x, $y - 13, $this->truncate($displayValue, 39), $valueFontSize, true, [0.05, 0.12, 0.23]);
        }

        return $content;
    }

    private function sectionTitle(float $y, string $label, string $number): string
    {
        return $this->rect(self::LEFT, $y - 14, 3, 17, [0.055, 0.45, 0.92])
            .$this->text(self::LEFT + 11, $y - 9, $number, 6.6, true, [0.055, 0.45, 0.92])
            .$this->text(self::LEFT + 33, $y - 9, $label, 8.2, true, [0.035, 0.086, 0.165])
            .$this->line(self::LEFT + 180, $y - 7, self::RIGHT, $y - 7, [0.84, 0.88, 0.93], 0.6);
    }

    private function detailPanel(float $top, float $height, array $fields, int $columns): string
    {
        $content = $this->rect(self::LEFT, $top - $height, self::CONTENT_WIDTH, $height, [1, 1, 1]);
        $content .= $this->line(self::LEFT, $top, self::RIGHT, $top, [0.82, 0.86, 0.91], 0.7);
        $content .= $this->line(self::LEFT, $top - $height, self::RIGHT, $top - $height, [0.82, 0.86, 0.91], 0.7);
        $columnWidth = self::CONTENT_WIDTH / $columns;
        $rows = (int) ceil(count($fields) / $columns);
        $rowHeight = $height / max(1, $rows);

        foreach (array_values($fields) as $index => [$label, $value]) {
            $column = $index % $columns;
            $row = intdiv($index, $columns);
            $x = self::LEFT + $column * $columnWidth + 14;
            $y = $top - 17 - $row * $rowHeight;
            if ($column > 0) {
                $dividerX = self::LEFT + $column * $columnWidth;
                $content .= $this->line($dividerX, $top - $height + 10, $dividerX, $top - 10, [0.89, 0.92, 0.96], 0.45);
            }
            if ($row > 0) {
                $dividerY = $top - $row * $rowHeight;
                $content .= $this->line(self::LEFT + 10, $dividerY, self::RIGHT - 10, $dividerY, [0.91, 0.94, 0.97], 0.45);
            }
            $content .= $this->text($x, $y, strtoupper($label), 6.2, true, [0.44, 0.50, 0.60]);
            $content .= $this->text($x, $y - 13, $this->truncate((string) ($value ?: '-'), 48), 8.1, true, [0.06, 0.13, 0.24]);
        }

        return $content;
    }

    private function addressPanel(float $top, PurchaseOrderRequest $requestPo): string
    {
        $height = 92;
        $mid = self::LEFT + self::CONTENT_WIDTH * 0.56;
        $content = $this->rect(self::LEFT, $top - $height, self::CONTENT_WIDTH, $height, [0.985, 0.988, 0.993]);
        $content .= $this->line(self::LEFT, $top, self::RIGHT, $top, [0.82, 0.86, 0.91], 0.7);
        $content .= $this->line(self::LEFT, $top - $height, self::RIGHT, $top - $height, [0.82, 0.86, 0.91], 0.7);
        $content .= $this->line($mid, $top - $height + 10, $mid, $top - 10, [0.87, 0.91, 0.95], 0.5);

        $content .= $this->text(self::LEFT + 14, $top - 17, 'ALAMAT PENGIRIMAN / LOKASI PROJECT', 6.3, true, [0.42, 0.49, 0.60]);
        $addressLines = array_slice($this->wrap((string) ($requestPo->delivery_address ?: '-'), 60), 0, 4);
        $lineY = $top - 32;
        foreach ($addressLines as $line) {
            $content .= $this->text(self::LEFT + 14, $lineY, $line, 8, false, [0.06, 0.13, 0.24]);
            $lineY -= 11;
        }

        $rightX = $mid + 14;
        $content .= $this->text($rightX, $top - 17, 'PIC PENERIMA', 6.3, true, [0.42, 0.49, 0.60]);
        $content .= $this->text($rightX, $top - 31, $this->truncate((string) ($requestPo->delivery_pic_name ?: '-'), 34), 8, true, [0.06, 0.13, 0.24]);
        $content .= $this->text($rightX, $top - 44, (string) ($requestPo->delivery_pic_phone ?: '-'), 7.5, false, [0.31, 0.39, 0.51]);
        $content .= $this->text($rightX, $top - 60, 'NPWP / BILLING', 6.3, true, [0.42, 0.49, 0.60]);
        $content .= $this->text($rightX, $top - 73, $this->truncate((string) ($requestPo->npwp_name ?: '-'), 39), 7, false, [0.12, 0.20, 0.32]);
        $content .= $this->text($rightX, $top - 84, (string) ($requestPo->npwp_number ?: '-'), 7, true, [0.12, 0.20, 0.32]);

        return $content;
    }

    private function checklistPanel(float $top, PurchaseOrderRequest $requestPo): string
    {
        $items = PurchaseOrderRequest::checklistItems();
        $height = 28 + count($items) * 18;
        $content = $this->rect(self::LEFT, $top - $height, self::CONTENT_WIDTH, $height, [0.985, 0.988, 0.993]);
        $content .= $this->line(self::LEFT, $top, self::RIGHT, $top, [0.82, 0.86, 0.91], 0.7);
        $progress = $requestPo->checklistProgress();
        $content .= $this->text(self::LEFT + 14, $top - 18, $progress['done'].' / '.$progress['total'].' DOKUMEN LENGKAP', 7.5, true, [0.055, 0.45, 0.92]);
        $content .= $this->rect(self::LEFT + 170, $top - 20, self::CONTENT_WIDTH - 184, 6, [0.89, 0.92, 0.96]);
        $content .= $this->rect(self::LEFT + 170, $top - 20, (self::CONTENT_WIDTH - 184) * $progress['percent'] / 100, 6, [0.055, 0.45, 0.92]);

        foreach (array_values($items) as $index => $label) {
            $checked = ! empty(($requestPo->checklist ?? [])[array_keys($items)[$index]]);
            $rowY = $top - 42 - $index * 18;
            $color = $checked ? [0.05, 0.55, 0.31] : [0.67, 0.72, 0.80];
            $content .= $this->rect(self::LEFT + 14, $rowY - 1, 9, 9, $checked ? [0.86, 0.97, 0.90] : [0.94, 0.95, 0.97], $color);
            if ($checked) {
                $content .= $this->text(self::LEFT + 18.5, $rowY + 1, 'v', 6.5, true, $color, 'center');
            }
            $content .= $this->text(self::LEFT + 30, $rowY, $label, 7.2, $checked, $checked ? [0.08, 0.27, 0.19] : [0.42, 0.48, 0.57]);
        }

        return $content;
    }

    private function requestItemHeader(float $top): string
    {
        $bottom = $top - 24;
        $content = $this->rect(self::LEFT, $bottom, self::CONTENT_WIDTH, 24, [0.055, 0.18, 0.34]);
        $content .= $this->rect(self::LEFT, $bottom, 4, 24, [0.055, 0.45, 0.92]);
        foreach ([
            [54, 'NO', 'center'],
            [78, 'ITEM & SPESIFIKASI', 'left'],
            [360, 'QTY', 'center'],
            [441, 'HARGA', 'right'],
            [548, 'TOTAL', 'right'],
        ] as [$x, $label, $align]) {
            $content .= $this->text($x, $bottom + 8, $label, 7, true, [1, 1, 1], $align);
        }

        return $content;
    }

    private function requestItemRow(
        float $top,
        float $height,
        int $number,
        object $item,
        array $specLines,
        bool $continued,
        bool $alternate,
    ): string {
        $bottom = $top - $height;
        $content = $this->rect(
            self::LEFT,
            $bottom,
            self::CONTENT_WIDTH,
            $height,
            $alternate ? [0.985, 0.988, 0.993] : [1, 1, 1],
        );
        $content .= $this->line(self::LEFT, $bottom, self::RIGHT, $bottom, [0.84, 0.88, 0.92], 0.55);
        foreach ([68, 335, 382, 454] as $x) {
            $content .= $this->line($x, $bottom, $x, $top, [0.89, 0.92, 0.96], 0.45);
        }

        $content .= $this->text(54, $top - 18, $continued ? '-' : (string) $number, 8, true, [0.25, 0.32, 0.43], 'center');
        $content .= $this->text(76, $top - 15, $this->truncate($item->name.($continued ? ' (lanjutan)' : ''), 50), 8.2, true, [0.05, 0.12, 0.23]);
        if (! $continued && $item->variant) {
            $content .= $this->text(76, $top - 27, $this->truncate($item->variant, 45), 6.8, true, [0.055, 0.45, 0.92]);
        }
        $lineY = $top - ($continued || ! $item->variant ? 29 : 39);
        foreach ($specLines as $line) {
            $isSection = str_starts_with($line, '[') && str_ends_with($line, ']');
            $displayLine = $isSection ? trim($line, '[]') : $line;
            $content .= $this->text(
                $isSection ? 76 : 84,
                $lineY,
                $displayLine,
                $isSection ? 6.9 : 6.8,
                $isSection,
                $isSection ? [0.055, 0.35, 0.70] : [0.31, 0.37, 0.46],
            );
            $lineY -= 8;
        }

        if (! $continued) {
            $content .= $this->text(359, $top - 18, $this->quantity((float) $item->qty).' '.($item->unit ?: 'Unit'), 7.6, false, [0.10, 0.18, 0.30], 'center');
            $content .= $this->text(446, $top - 18, $this->money((float) $item->unit_price), 7.6, false, [0.10, 0.18, 0.30], 'right');
            $content .= $this->text(548, $top - 18, $this->money((float) $item->total), 8, true, [0.05, 0.12, 0.23], 'right');
        }

        return $content;
    }

    private function requestPoSummary(float $top, PurchaseOrderRequest $requestPo): string
    {
        $quotation = $requestPo->quotation;
        $content = $this->rect(self::LEFT, $top - 116, 300, 116, [0.985, 0.988, 0.993]);
        $content .= $this->line(self::LEFT, $top, self::LEFT + 300, $top, [0.055, 0.45, 0.92], 1.2);
        $content .= $this->text(self::LEFT + 14, $top - 20, 'CATATAN ADMINISTRASI', 7.5, true, [0.035, 0.086, 0.165]);
        $noteLines = array_slice($this->wrap((string) ($requestPo->admin_note ?: 'Tidak ada catatan tambahan.'), 62), 0, 6);
        $lineY = $top - 38;
        foreach ($noteLines as $line) {
            $content .= $this->text(self::LEFT + 14, $lineY, $line, 7.4, false, [0.30, 0.38, 0.49]);
            $lineY -= 11;
        }
        if ($requestPo->accurate_note) {
            $content .= $this->text(self::LEFT + 14, $top - 90, 'ACCURATE: '.$this->truncate($requestPo->accurate_note, 58), 6.8, true, [0.08, 0.35, 0.62]);
        }

        $x = self::LEFT + 316;
        $width = self::RIGHT - $x;
        $content .= $this->rect($x, $top - 116, $width, 116, [1, 1, 1]);
        $content .= $this->line($x, $top, self::RIGHT, $top, [0.035, 0.086, 0.165], 1.4);
        $content .= $this->text($x, $top - 18, 'RINGKASAN NILAI', 8, true, [0.035, 0.086, 0.165]);
        $rows = [
            ['Subtotal', (float) ($quotation?->subtotal ?? 0)],
            ['PPN', (float) ($quotation?->tax_amount ?? 0)],
            ['Biaya Tambahan', (float) ($quotation?->additional_total ?? 0)],
        ];
        $rowY = $top - 47;
        foreach ($rows as [$label, $amount]) {
            $content .= $this->text($x, $rowY, $label, 7, false, [0.39, 0.45, 0.55]);
            $content .= $this->text(self::RIGHT - 14, $rowY, $this->money($amount), 7.5, true, [0.10, 0.18, 0.30], 'right');
            $rowY -= 16;
        }
        $content .= $this->line($x + 14, $rowY + 5, self::RIGHT - 14, $rowY + 5, [0.80, 0.85, 0.92], 0.7);
        $content .= $this->text($x, $rowY - 5, 'GRAND TOTAL', 8.2, true, [0.035, 0.086, 0.165]);
        $content .= $this->text(self::RIGHT - 14, $rowY - 5, $this->money((float) ($quotation?->grand_total ?? 0)), 9.5, true, [0.055, 0.45, 0.92], 'right');

        return $content;
    }

    private function invoiceMetricCards(float $top, Invoice $invoice): string
    {
        $width = self::CONTENT_WIDTH / 3;
        $metrics = [
            ['TOTAL TAGIHAN', (float) $invoice->grand_total, [0.055, 0.45, 0.92]],
            ['SUDAH DIBAYAR', (float) $invoice->paid_total, [0.05, 0.55, 0.31]],
            ['SISA TAGIHAN', $invoice->balance(), $invoice->balance() > 0 ? [0.88, 0.31, 0.18] : [0.05, 0.55, 0.31]],
        ];

        $content = $this->rect(self::LEFT, $top - 58, self::CONTENT_WIDTH, 58, [0.985, 0.988, 0.993]);
        $content .= $this->line(self::LEFT, $top, self::RIGHT, $top, [0.82, 0.86, 0.91], 0.7);
        $content .= $this->line(self::LEFT, $top - 58, self::RIGHT, $top - 58, [0.82, 0.86, 0.91], 0.7);
        foreach ($metrics as $index => [$label, $amount, $accent]) {
            $x = self::LEFT + $index * $width;
            if ($index > 0) {
                $content .= $this->line($x, $top - 47, $x, $top - 11, [0.86, 0.89, 0.93], 0.55);
            }
            $content .= $this->text($x + 14, $top - 18, $label, 6.4, true, [0.42, 0.49, 0.59]);
            $content .= $this->text($x + 14, $top - 40, $this->money($amount), 10.5, true, $accent);
        }

        return $content;
    }

    private function invoiceItemHeader(float $top): string
    {
        $bottom = $top - 24;
        $content = $this->rect(self::LEFT, $bottom, self::CONTENT_WIDTH, 24, [0.055, 0.18, 0.34]);
        $content .= $this->rect(self::LEFT, $bottom, 4, 24, [0.055, 0.45, 0.92]);
        foreach ([
            [54, 'NO', 'center'],
            [78, 'DESKRIPSI ITEM', 'left'],
            [331, 'QTY', 'center'],
            [429, 'HARGA SATUAN', 'right'],
            [548, 'JUMLAH', 'right'],
        ] as [$x, $label, $align]) {
            $content .= $this->text($x, $bottom + 8, $label, 7, true, [1, 1, 1], $align);
        }

        return $content;
    }

    private function invoiceItemRow(float $top, float $height, int $number, object $item, array $nameLines, bool $alternate): string
    {
        $bottom = $top - $height;
        $content = $this->rect(self::LEFT, $bottom, self::CONTENT_WIDTH, $height, $alternate ? [0.985, 0.988, 0.993] : [1, 1, 1]);
        $content .= $this->line(self::LEFT, $bottom, self::RIGHT, $bottom, [0.84, 0.88, 0.92], 0.55);
        foreach ([68, 305, 356, 452] as $x) {
            $content .= $this->line($x, $bottom, $x, $top, [0.89, 0.92, 0.96], 0.45);
        }
        $content .= $this->text(54, $top - 18, (string) $number, 8, true, [0.25, 0.32, 0.43], 'center');
        $lineY = $top - 16;
        foreach ($nameLines as $line) {
            $content .= $this->text(76, $lineY, $line, 8, true, [0.05, 0.12, 0.23]);
            $lineY -= 10;
        }
        if ($item->category) {
            $content .= $this->text(76, $lineY - 1, strtoupper($this->truncate($item->category, 38)), 6.3, true, [0.055, 0.45, 0.92]);
        }
        $content .= $this->text(330, $top - 18, $this->quantity((float) $item->qty).' '.($item->unit ?: 'Unit'), 7.6, false, [0.10, 0.18, 0.30], 'center');
        $content .= $this->text(447, $top - 18, $this->money((float) $item->unit_price), 7.6, false, [0.10, 0.18, 0.30], 'right');
        $content .= $this->text(548, $top - 18, $this->money((float) $item->total), 8, true, [0.05, 0.12, 0.23], 'right');

        return $content;
    }

    private function invoiceSummary(float $top, Invoice $invoice): string
    {
        $leftWidth = 280;
        $content = $this->rect(self::LEFT, $top - 138, $leftWidth, 138, [0.985, 0.988, 0.993]);
        $content .= $this->line(self::LEFT, $top, self::LEFT + $leftWidth, $top, [0.055, 0.45, 0.92], 1.2);
        $content .= $this->text(self::LEFT + 14, $top - 20, 'INFORMASI PEMBAYARAN', 7.6, true, [0.035, 0.086, 0.165]);
        $requestPo = $invoice->purchaseOrderRequest;
        $info = [
            ['No. PO Customer', $requestPo?->customer_po_number],
            ['No. PO Accurate', $requestPo?->accurate_po_number],
            ['Termin', $requestPo?->payment_term],
            ['Diterbitkan oleh', $invoice->creator?->name],
        ];
        $rowY = $top - 42;
        foreach ($info as [$label, $value]) {
            $content .= $this->text(self::LEFT + 14, $rowY, strtoupper($label), 6.2, true, [0.45, 0.51, 0.60]);
            $content .= $this->text(self::LEFT + 112, $rowY, $this->truncate((string) ($value ?: '-'), 34), 7.4, true, [0.08, 0.15, 0.26]);
            $rowY -= 20;
        }
        if ($invoice->note) {
            $content .= $this->text(self::LEFT + 14, $top - 126, 'CATATAN: '.$this->truncate($invoice->note, 55), 6.7, false, [0.31, 0.39, 0.50]);
        }

        $x = self::LEFT + $leftWidth + 16;
        $width = self::RIGHT - $x;
        $content .= $this->rect($x, $top - 138, $width, 138, [1, 1, 1]);
        $content .= $this->line($x, $top, self::RIGHT, $top, [0.035, 0.086, 0.165], 1.4);
        $content .= $this->text($x, $top - 18, 'RINGKASAN TAGIHAN', 8, true, [0.035, 0.086, 0.165]);
        $rows = [
            ['Subtotal', (float) $invoice->subtotal, false],
            ['PPN', (float) $invoice->tax_amount, false],
            ['Instalasi / Tambahan', (float) $invoice->installation_amount, false],
            ['GRAND TOTAL', (float) $invoice->grand_total, true],
            ['SISA TAGIHAN', $invoice->balance(), true],
        ];
        $rowY = $top - 48;
        foreach ($rows as [$label, $amount, $bold]) {
            if ($bold) {
                $content .= $this->line($x + 14, $rowY + 6, self::RIGHT - 14, $rowY + 6, [0.80, 0.85, 0.92], 0.65);
            }
            $content .= $this->text($x, $rowY, $label, $bold ? 7.7 : 7, $bold, $bold ? [0.035, 0.086, 0.165] : [0.39, 0.45, 0.55]);
            $content .= $this->text(self::RIGHT - 14, $rowY, $this->money($amount), $bold ? 8.8 : 7.5, true, $bold ? [0.055, 0.45, 0.92] : [0.10, 0.18, 0.30], 'right');
            $rowY -= $bold ? 21 : 17;
        }

        return $content;
    }

    private function invoiceTerms(float $top, Invoice $invoice): string
    {
        $terms = $invoice->terms;
        $height = 28 + max(1, $terms->count()) * 28;
        $content = $this->rect(self::LEFT, $top - $height, self::CONTENT_WIDTH, $height, [1, 1, 1]);
        $content .= $this->rect(self::LEFT, $top - 24, self::CONTENT_WIDTH, 24, [0.965, 0.974, 0.986]);
        $content .= $this->line(self::LEFT, $top, self::RIGHT, $top, [0.82, 0.86, 0.91], 0.7);
        $content .= $this->line(self::LEFT, $top - $height, self::RIGHT, $top - $height, [0.82, 0.86, 0.91], 0.7);
        foreach ([
            [54, 'NO', 'center'],
            [79, 'DESKRIPSI', 'left'],
            [310, 'JATUH TEMPO', 'center'],
            [427, 'NILAI', 'right'],
            [548, 'STATUS', 'right'],
        ] as [$x, $label, $align]) {
            $content .= $this->text($x, $top - 16, $label, 6.6, true, [0.24, 0.35, 0.50], $align);
        }

        if ($terms->isEmpty()) {
            $content .= $this->text(self::LEFT + 14, $top - 46, 'Belum ada termin pembayaran.', 7.4, false, [0.48, 0.54, 0.63]);
            return $content;
        }

        foreach ($terms as $index => $term) {
            $rowY = $top - 43 - $index * 28;
            $content .= $this->line(self::LEFT + 10, $rowY + 10, self::RIGHT - 10, $rowY + 10, [0.91, 0.93, 0.96], 0.4);
            $content .= $this->text(54, $rowY, (string) $term->term_number, 7.2, true, [0.27, 0.34, 0.44], 'center');
            $content .= $this->text(78, $rowY, $this->truncate($term->description ?: 'Termin '.$term->term_number, 40), 7.2, false, [0.10, 0.18, 0.30]);
            $content .= $this->text(310, $rowY, $term->due_date?->format('d/m/Y') ?: '-', 7.2, false, [0.10, 0.18, 0.30], 'center');
            $content .= $this->text(427, $rowY, $this->money((float) $term->amount), 7.2, true, [0.10, 0.18, 0.30], 'right');
            $label = InvoiceTerm::statuses()[$term->status] ?? str($term->status)->headline();
            $content .= $this->text(548, $rowY, strtoupper($label), 6.6, true, $term->status === 'paid' ? [0.05, 0.55, 0.31] : [0.78, 0.46, 0.08], 'right');
        }

        return $content;
    }

    private function invoiceNote(float $top, Invoice $invoice): string
    {
        $content = $this->rect(self::LEFT, $top - 52, self::CONTENT_WIDTH, 52, [0.965, 0.985, 0.975]);
        $content .= $this->rect(self::LEFT, $top - 52, 4, 52, [0.05, 0.48, 0.28]);
        $content .= $this->text(self::LEFT + 14, $top - 19, $invoice->status === 'paid' ? 'INVOICE TELAH LUNAS' : 'INFORMASI TAGIHAN', 8.5, true, [0.05, 0.48, 0.28]);
        $content .= $this->text(self::LEFT + 14, $top - 36, 'Dokumen resmi ROBUST CRM. Verifikasi menggunakan nomor invoice di bagian atas.', 7.2, false, [0.25, 0.40, 0.33]);
        $content .= $this->text(self::RIGHT - 14, $top - 27, strtoupper(Invoice::statuses()[$invoice->status] ?? $invoice->status), 9, true, [0.05, 0.48, 0.28], 'right');

        return $content;
    }

    private function structuredSpecificationLines(string $specification, int $width): array
    {
        $sections = StructuredSpecification::parse($specification);
        if (! $sections) {
            return $this->wrap($specification, $width);
        }

        $lines = [];
        foreach ($sections as $section) {
            $lines[] = strtoupper('['.$section['title'].']');
            foreach ($section['rows'] as $row) {
                if ($row['type'] === 'breakdown') {
                    $value = trim(($row['label'] ?: 'Rincian').' - '.$this->quantity((float) $row['qty']).' '.$row['unit']);
                    if ($row['unit_price'] !== null) {
                        $value .= ' x '.$this->money((float) $row['unit_price']);
                    }
                } else {
                    $value = trim(($row['label'] ? $row['label'].': ' : '').($row['value'] ?: '-'));
                }
                foreach ($this->wrap($value, $width) as $line) {
                    $lines[] = $line;
                }
            }
        }

        return $lines;
    }

    private function finish(array $pages): string
    {
        $total = count($pages);
        foreach ($pages as $index => &$page) {
            $page .= $this->operationalFooter($index + 1, $total);
        }

        return $this->renderPdf($pages);
    }

    private function operationalFooter(int $page, int $total): string
    {
        $content = $this->line(self::LEFT, 34, self::RIGHT, 34, [0.84, 0.88, 0.93], 0.6);
        $content .= $this->text(self::LEFT, 20, 'ROBUST - Laboratory Furniture & Equipment', 6.7, false, [0.42, 0.49, 0.59]);
        $content .= $this->text(self::WIDTH / 2, 20, $this->documentLabel.' - Generated '.now()->format('d/m/Y H:i'), 6.5, false, [0.53, 0.59, 0.68], 'center');
        $content .= $this->text(self::RIGHT, 20, 'Halaman '.$page.' / '.$total, 6.7, true, [0.34, 0.41, 0.52], 'right');

        return $content;
    }
}

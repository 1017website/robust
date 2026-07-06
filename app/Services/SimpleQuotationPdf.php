<?php

namespace App\Services;

use App\Models\Quotation;

class SimpleQuotationPdf
{
    public function make(Quotation $quotation): string
    {
        $quotation->loadMissing('items', 'sales', 'approvedBy');

        $lines = $this->buildLines($quotation);
        $pages = array_chunk($lines, 45);

        return $this->renderPdf($pages);
    }

    protected function buildLines(Quotation $quotation): array
    {
        $lines = [];
        $add = function (string $line = '') use (&$lines) {
            $lines[] = $this->sanitize($line);
        };

        $add('ROBUST - LABORATORY FURNITURE & EQUIPMENT');
        $add('PENAWARAN HARGA');
        $add(str_repeat('=', 88));
        $add('No Penawaran : '.($quotation->code ?: '-'));
        $add('Tanggal       : '.optional($quotation->quote_date)->format('d/m/Y'));
        $add('Berlaku s/d   : '.optional($quotation->valid_until)->format('d/m/Y'));
        $add('Customer      : '.$quotation->customer_name);
        $add('PIC           : '.($quotation->pic_name ?: '-'));
        $add('Project       : '.$quotation->project_name);
        $add('Sales         : '.($quotation->sales?->name ?: '-'));
        $add('Approved By   : '.($quotation->approvedBy?->name ?: '-').' / '.optional($quotation->approved_at)->format('d/m/Y H:i'));
        $add(str_repeat('-', 88));
        $add(sprintf('%-4s %-34s %8s %-8s %15s %15s', 'No', 'Item', 'Qty', 'Unit', 'Harga', 'Total'));
        $add(str_repeat('-', 88));

        foreach ($quotation->items as $index => $item) {
            $name = $this->wrap($item->name, 34);
            $spec = $this->wrap((string) $item->specification, 74);
            foreach ($name as $row => $text) {
                if ($row === 0) {
                    $add(sprintf(
                        '%-4s %-34s %8s %-8s %15s %15s',
                        $index + 1,
                        $text,
                        rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ','),
                        $item->unit ?: 'Unit',
                        $this->money((float) $item->unit_price),
                        $this->money((float) $item->total)
                    ));
                } else {
                    $add(sprintf('%-4s %-34s %8s %-8s %15s %15s', '', $text, '', '', '', ''));
                }
            }
            foreach ($spec as $text) {
                if ($text !== '') {
                    $add('     Spesifikasi: '.$text);
                }
            }
        }

        $add(str_repeat('-', 88));
        $add(sprintf('%68s %18s', 'Subtotal', $this->money((float) $quotation->subtotal)));
        $add(sprintf('%68s %18s', 'Diskon', '- '.$this->money((float) $quotation->discount_amount)));
        $add(sprintf('%68s %18s', 'PPN '.rtrim(rtrim(number_format((float) $quotation->tax_percent, 2, ',', '.'), '0'), ',').'%', $this->money((float) $quotation->tax_amount)));
        $add(sprintf('%68s %18s', 'Biaya Tambahan', $this->money((float) $quotation->additional_total)));
        $add(sprintf('%68s %18s', 'GRAND TOTAL', $this->money((float) $quotation->grand_total)));
        $add('');

        if (is_array($quotation->additional_costs) && count($quotation->additional_costs)) {
            $add('Rincian biaya tambahan:');
            foreach ($quotation->additional_costs as $cost) {
                $add('- '.($cost['label'] ?? 'Biaya Tambahan').': '.$this->money((float) ($cost['amount'] ?? 0)));
            }
            $add('');
        }

        if ($quotation->customer_note) {
            $add('Catatan untuk Customer:');
            foreach ($this->wrap($quotation->customer_note, 88) as $text) {
                $add($text);
            }
            $add('');
        }

        $add('Status dokumen: APPROVED SPV. PDF ini hanya diterbitkan setelah penawaran disetujui.');
        $add('Dicetak otomatis dari ROBUST CRM pada '.now()->format('d/m/Y H:i'));

        return $lines;
    }

    protected function renderPdf(array $pages): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
        ];

        $pageRefs = [];
        $contentObjects = [];
        $nextObj = 4;
        foreach ($pages as $page) {
            $pageObj = $nextObj++;
            $contentObj = $nextObj++;
            $pageRefs[] = $pageObj.' 0 R';
            $contentObjects[$pageObj] = [$contentObj, $page];
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageRefs).'] /Count '.count($pageRefs).' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        foreach ($contentObjects as $pageObj => [$contentObj, $pageLines]) {
            $objects[$pageObj] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentObj.' 0 R >>';
            $stream = $this->contentStream($pageLines);
            $objects[$contentObj] = '<< /Length '.strlen($stream).' >>' . "\nstream\n" . $stream . "\nendstream";
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i." 0 obj\n".$obj."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    protected function contentStream(array $lines): string
    {
        $content = "BT\n/F1 9 Tf\n50 800 Td\n12 TL\n";
        foreach ($lines as $line) {
            $content .= '(' . $this->escapePdfString($line) . ") Tj\nT*\n";
        }
        $content .= "ET";
        return $content;
    }

    protected function wrap(string $text, int $width): array
    {
        $text = trim($this->sanitize($text));
        if ($text === '') {
            return [''];
        }
        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    protected function sanitize(string $text): string
    {
        $text = str_replace(['–', '—', '•'], ['-', '-', '-'], $text);
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
        return $text;
    }

    protected function money(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    protected function escapePdfString(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}

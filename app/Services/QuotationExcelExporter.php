<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Support\StructuredSpecification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class QuotationExcelExporter
{
    private array $merges = [];

    private array $images = [];

    private array $mainTotalCells = [];

    public function download(Quotation $quotation): BinaryFileResponse
    {
        $quotation->loadMissing('items', 'sales', 'approvedBy', 'customer');
        $path = tempnam(sys_get_temp_dir(), 'robust-quotation-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $worksheet = $this->worksheet($quotation);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet);

        if ($this->images) {
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $this->worksheetRelationships());
            $zip->addFromString('xl/drawings/drawing1.xml', $this->drawing());
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $this->drawingRelationships());
            foreach ($this->images as $index => $image) {
                $zip->addFromString('xl/media/image'.($index + 1).'.png', $image['bytes']);
            }
        }

        $zip->close();

        $filename = str($quotation->code ?: 'penawaran')
            ->replace(['/', '\\'], '-')
            ->slug('-')
            ->append('-detail.xlsx')
            ->toString();

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function worksheet(Quotation $quotation): string
    {
        $this->merges = [];
        $this->images = [];
        $this->mainTotalCells = [];
        $rows = [];
        $row = 1;

        $this->merges[] = "A{$row}:K{$row}";
        $rows[] = $this->row($row, [
            $this->textCell("A{$row}", 'PENAWARAN HARGA', 8),
        ], 30);
        $row++;

        $this->merges[] = "A{$row}:B{$row}";
        $this->merges[] = "C{$row}:F{$row}";
        $this->merges[] = "G{$row}:H{$row}";
        $this->merges[] = "I{$row}:K{$row}";
        $rows[] = $this->row($row, [
            $this->textCell("A{$row}", 'No. Penawaran', 9),
            $this->textCell("C{$row}", $quotation->code ?: '-', 9),
            $this->textCell("G{$row}", 'Tanggal', 9),
            $this->textCell("I{$row}", optional($quotation->quote_date)->format('d/m/Y') ?: '-', 9),
        ], 21);
        $row++;

        foreach ([
            ['Customer', $quotation->customer_name ?: '-', 'PIC', $quotation->pic_name ?: '-'],
            ['Project', $quotation->project_name ?: '-', 'Sales', $quotation->sales?->name ?: '-'],
            ['Berlaku Sampai', optional($quotation->valid_until)->format('d/m/Y') ?: '-', 'Status', $quotation->statusLabel()],
        ] as [$leftLabel, $leftValue, $rightLabel, $rightValue]) {
            $this->merges[] = "A{$row}:B{$row}";
            $this->merges[] = "C{$row}:F{$row}";
            $this->merges[] = "G{$row}:H{$row}";
            $this->merges[] = "I{$row}:K{$row}";
            $rows[] = $this->row($row, [
                $this->textCell("A{$row}", $leftLabel, 9),
                $this->textCell("C{$row}", $leftValue, 9),
                $this->textCell("G{$row}", $rightLabel, 9),
                $this->textCell("I{$row}", $rightValue, 9),
            ], 21);
            $row++;
        }

        $row++;
        $headerRow = $row;
        $this->merges[] = "B{$row}:G{$row}";
        $rows[] = $this->row($row, [
            $this->textCell("A{$row}", 'NO', 1),
            $this->textCell("B{$row}", 'DESCRIPTION', 1),
            $this->textCell("H{$row}", 'QTY', 1),
            $this->textCell("I{$row}", 'UoM', 1),
            $this->textCell("J{$row}", 'PRICE ( Rp )', 1),
            $this->textCell("K{$row}", 'SUB TOTAL ( Rp )', 1),
        ], 24);
        $row++;

        $items = $quotation->items->sortBy(fn (QuotationItem $item) => [$item->is_optional ? 1 : 0, $item->sort_order]);
        $optionalStarted = false;
        $number = 1;

        foreach ($items as $item) {
            if ($item->is_optional && ! $optionalStarted) {
                $optionalStarted = true;
                $this->merges[] = "A{$row}:K{$row}";
                $rows[] = $this->row($row, [$this->textCell("A{$row}", 'OPTIONAL', 11)], 24);
                $row++;
            }

            $startRow = $row;
            $this->merges[] = "B{$row}:G{$row}";
            $rows[] = $this->row($row, [
                $this->textCell("A{$row}", (string) $number++, 2),
                $this->textCell("B{$row}", strtoupper(trim(($item->category ? $item->category.' - ' : '').$item->name)), 2),
                $this->numberCell("H{$row}", (float) $item->qty, 14),
                $this->textCell("I{$row}", $item->unit ?: 'Unit', 2),
                $this->numberCell("J{$row}", (float) $item->unit_price, 15),
                $this->formulaCell("K{$row}", "H{$row}*J{$row}", (float) $item->total, 15),
            ], 22);
            if (! $item->is_optional) {
                $this->mainTotalCells[] = "K{$row}";
            }
            $row++;

            if ($item->variant) {
                $this->merges[] = "B{$row}:G{$row}";
                $rows[] = $this->borderedRow($row, [
                    $this->textCell("B{$row}", $item->variant, 4),
                ], 21);
                $row++;
            }

            $specRows = $this->specificationRows($item->specification);
            foreach ($specRows as $spec) {
                if ($spec['type'] === 'section') {
                    $this->merges[] = "B{$row}:G{$row}";
                    $rows[] = $this->borderedRow($row, [
                        $this->textCell("B{$row}", $spec['title'], 4),
                    ], 20);
                } elseif ($spec['type'] === 'breakdown') {
                    $cells = [
                        $this->textCell("C{$row}", $spec['label'], 5),
                        $this->numberCell("D{$row}", $spec['qty'], 7),
                        $this->textCell("E{$row}", $spec['unit'], 7),
                    ];
                    if ($spec['unit_price'] !== null) {
                        $cells[] = $this->numberCell("F{$row}", $spec['unit_price'], 6);
                        $cells[] = $this->formulaCell(
                            "G{$row}",
                            "D{$row}*F{$row}",
                            round($spec['qty'] * $spec['unit_price'], 2),
                            6
                        );
                    }
                    $rows[] = $this->borderedRow($row, $cells, 20);
                } else {
                    $this->merges[] = "D{$row}:G{$row}";
                    $rows[] = $this->borderedRow($row, [
                        $this->textCell("C{$row}", $spec['label'], 5),
                        $this->textCell("D{$row}", $spec['value'], 5),
                    ], max(20, 15 * max(1, substr_count($spec['value'], "\n") + 1)));
                }
                $row++;
            }

            $minimumEnd = $item->quotation_image_path ? $startRow + 12 : $startRow + 2;
            while ($row <= $minimumEnd) {
                $rows[] = $this->borderedRow($row, [], 20);
                $row++;
            }

            if ($item->quotation_image_path) {
                $imageEndRow = $row - 1;
                $imageStartRow = max($startRow + 1, $imageEndRow - 22);
                $this->addImage($item, $imageStartRow, $imageEndRow);
            }
        }

        $row++;
        $summaryStart = $row;
        $sumFormula = $this->mainTotalCells ? implode(',', $this->mainTotalCells) : '0';
        $rows[] = $this->summaryRow($row, 'SUBTOTAL', "SUM({$sumFormula})", (float) $quotation->subtotal);
        $subtotalRow = $row++;

        $discountLabel = $quotation->discount_type === 'percent'
            ? 'DISKON '.rtrim(rtrim(number_format((float) $quotation->discount_value, 2), '0'), '.').'%'
            : 'DISKON';
        $discountFormula = $quotation->discount_type === 'percent'
            ? "K{$subtotalRow}*".((float) $quotation->discount_value).'/100'
            : (string) ((float) $quotation->discount_amount);
        $rows[] = $this->summaryRow($row, $discountLabel, $discountFormula, (float) $quotation->discount_amount);
        $discountRow = $row++;

        $taxLabel = 'PPN '.rtrim(rtrim(number_format((float) $quotation->tax_percent, 2), '0'), '.').'%';
        $rows[] = $this->summaryRow(
            $row,
            $taxLabel,
            "(K{$subtotalRow}-K{$discountRow})*".((float) $quotation->tax_percent).'/100',
            (float) $quotation->tax_amount
        );
        $taxRow = $row++;

        $additionalRows = [];
        foreach ((array) $quotation->additional_costs as $cost) {
            if (blank($cost['label'] ?? null) && empty($cost['amount'])) {
                continue;
            }
            $rows[] = $this->summaryRow($row, strtoupper($cost['label'] ?? 'BIAYA TAMBAHAN'), (string) ((float) ($cost['amount'] ?? 0)), (float) ($cost['amount'] ?? 0));
            $additionalRows[] = "K{$row}";
            $row++;
        }

        $grandParts = ["K{$subtotalRow}", "-K{$discountRow}", "+K{$taxRow}"];
        foreach ($additionalRows as $cell) {
            $grandParts[] = "+{$cell}";
        }
        $this->merges[] = "A{$row}:J{$row}";
        $rows[] = $this->row($row, [
            $this->textCell("A{$row}", 'GRAND TOTAL', 10),
            $this->formulaCell("K{$row}", implode('', $grandParts), (float) $quotation->grand_total, 10),
        ], 26);
        $row++;

        if ($quotation->customer_note) {
            $row++;
            $this->merges[] = "A{$row}:K{$row}";
            $rows[] = $this->row($row, [$this->textCell("A{$row}", 'CATATAN & KETENTUAN', 4)], 22);
            $row++;
            $this->merges[] = "A{$row}:K{$row}";
            $rows[] = $this->row($row, [$this->textCell("A{$row}", $quotation->customer_note, 12)], 44);
            $row++;
        }

        $this->merges[] = "A{$row}:K{$row}";
        $approval = $quotation->approvedBy
            ? 'Approved by '.$quotation->approvedBy->name.' pada '.optional($quotation->approved_at)->format('d/m/Y H:i')
            : 'Dokumen preview — belum disetujui SPV';
        $rows[] = $this->row($row, [$this->textCell("A{$row}", $approval, 13)], 24);

        $mergeXml = $this->merges
            ? '<mergeCells count="'.count($this->merges).'">'.collect($this->merges)->map(fn ($range) => '<mergeCell ref="'.$range.'"/>')->implode('').'</mergeCells>'
            : '';
        $drawingXml = $this->images ? '<drawing r:id="rId1"/>' : '';
        $dimension = "A1:K{$row}";

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<dimension ref="'.$dimension.'"/>'
            .'<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="'.$headerRow.'" topLeftCell="A'.($headerRow + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="20"/>'
            .'<cols><col min="1" max="1" width="6" customWidth="1"/><col min="2" max="2" width="18" customWidth="1"/><col min="3" max="3" width="24" customWidth="1"/><col min="4" max="7" width="18" customWidth="1"/><col min="8" max="8" width="9" customWidth="1"/><col min="9" max="9" width="10" customWidth="1"/><col min="10" max="11" width="18" customWidth="1"/></cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .$mergeXml
            .'<pageMargins left="0.25" right="0.25" top="0.4" bottom="0.4" header="0.2" footer="0.2"/>'
            .'<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            .$drawingXml
            .'</worksheet>';
    }

    private function specificationRows(?string $specification): array
    {
        return collect(StructuredSpecification::flatten($specification))
            ->map(function (array $row): array {
                if ($row['type'] === 'detail') {
                    $row['value'] = wordwrap($row['value'], 76, "\n", true);
                }

                return $row;
            })
            ->all();
    }

    private function addImage(QuotationItem $item, int $fromRow, int $toRow): void
    {
        if (! Storage::disk('public')->exists($item->quotation_image_path)) {
            return;
        }
        $source = Storage::disk('public')->get($item->quotation_image_path);
        $image = @imagecreatefromstring($source);
        if (! $image) {
            return;
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        $this->images[] = [
            'bytes' => $bytes,
            'name' => $item->quotation_image_name ?: $item->name,
            'fromRow' => max(0, $fromRow - 1),
            'toRow' => max($fromRow, $toRow),
        ];
    }

    private function borderedRow(int $row, array $cells, int $height): string
    {
        $filled = [];
        foreach (range('A', 'K') as $column) {
            $filled[$column] = $this->textCell("{$column}{$row}", '', 3);
        }
        foreach ($cells as $cell) {
            if (preg_match('/r="([A-K])\d+"/', $cell, $matches)) {
                $filled[$matches[1]] = $cell;
            }
        }

        return $this->row($row, array_values($filled), $height);
    }

    private function summaryRow(int $row, string $label, string $formula, float $cached): string
    {
        $this->merges[] = "A{$row}:J{$row}";

        return $this->row($row, [
            $this->textCell("A{$row}", $label, 9),
            $this->formulaCell("K{$row}", $formula, $cached, 6),
        ], 22);
    }

    private function row(int $number, array $cells, int $height): string
    {
        return '<row r="'.$number.'" ht="'.$height.'" customHeight="1">'.implode('', $cells).'</row>';
    }

    private function textCell(string $reference, string $value, int $style = 0): string
    {
        return '<c r="'.$reference.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.$this->escape($value).'</t></is></c>';
    }

    private function numberCell(string $reference, float $value, int $style = 0): string
    {
        return '<c r="'.$reference.'" s="'.$style.'"><v>'.$value.'</v></c>';
    }

    private function formulaCell(string $reference, string $formula, float $cached, int $style = 0): string
    {
        return '<c r="'.$reference.'" s="'.$style.'"><f>'.$this->escape($formula).'</f><v>'.$cached.'</v></c>';
    }

    private function drawing(): string
    {
        $anchors = [];
        foreach ($this->images as $index => $image) {
            $id = $index + 1;
            $anchors[] = '<xdr:twoCellAnchor editAs="oneCell">'
                .'<xdr:from><xdr:col>7</xdr:col><xdr:colOff>80000</xdr:colOff><xdr:row>'.$image['fromRow'].'</xdr:row><xdr:rowOff>80000</xdr:rowOff></xdr:from>'
                .'<xdr:to><xdr:col>11</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>'.$image['toRow'].'</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>'
                .'<xdr:pic><xdr:nvPicPr><xdr:cNvPr id="'.$id.'" name="'.$this->escape($image['name']).'"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>'
                .'<xdr:blipFill><a:blip r:embed="rId'.$id.'"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
                .'<xdr:spPr><a:xfrm/><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></xdr:spPr></xdr:pic>'
                .'<xdr:clientData/></xdr:twoCellAnchor>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .implode('', $anchors)
            .'</xdr:wsDr>';
    }

    private function drawingRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .collect($this->images)->keys()->map(fn ($index) => '<Relationship Id="rId'.($index + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image'.($index + 1).'.png"/>')->implode('')
            .'</Relationships>';
    }

    private function worksheetRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts>'
            .'<fonts count="4"><font><sz val="10"/><name val="Century Gothic"/></font><font><b/><sz val="10"/><name val="Century Gothic"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Century Gothic"/></font><font><i/><color rgb="FF4B5563"/><sz val="9"/><name val="Century Gothic"/></font></fonts>'
            .'<fills count="6"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFC6D9F1"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF66FFFF"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17365D"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEAF2F8"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="3"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FF000000"/></left><right style="thin"><color rgb="FF000000"/></right><top style="thin"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border><border><left style="medium"><color rgb="FF000000"/></left><right style="medium"><color rgb="FF000000"/></right><top style="medium"><color rgb="FF000000"/></top><bottom style="medium"><color rgb="FF000000"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="16">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="2" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="4" borderId="2" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="1" fillId="2" borderId="2" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="2" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="5" borderId="2" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .($this->images ? '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>' : '')
            .'</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Penawaran Detail" sheetId="1" r:id="rId1"/></sheets><calcPr calcId="191029" fullCalcOnLoad="1" forceFullCalc="1"/></workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

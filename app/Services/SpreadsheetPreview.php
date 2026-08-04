<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class SpreadsheetPreview
{
    public function rows(string $path, int $maxRows = 60, int $maxColumns = 24): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->csvRows($path, $maxRows, $maxColumns);
        }

        if ($extension !== 'xlsx') {
            throw new RuntimeException('Preview isi tersedia untuk file XLSX atau CSV.');
        }

        return $this->xlsxRows($path, $maxRows, $maxColumns);
    }

    private function csvRows(string $path, int $maxRows, int $maxColumns): array
    {
        $rows = [];
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException('File tidak dapat dibaca.');
        }

        try {
            while (count($rows) < $maxRows && ($row = fgetcsv($handle, 0, ';')) !== false) {
                if (count($row) === 1 && str_contains($row[0] ?? '', ',')) {
                    $row = str_getcsv($row[0], ',');
                }
                $rows[] = array_slice($row, 0, $maxColumns);
            }
        } finally {
            fclose($handle);
        }

        return ['sheet' => 'CSV', 'rows' => $rows, 'truncated' => count($rows) >= $maxRows];
    }

    private function xlsxRows(string $path, int $maxRows, int $maxColumns): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Workbook XLSX tidak dapat dibuka.');
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheetPath = $this->firstSheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                throw new RuntimeException('Worksheet tidak ditemukan.');
            }

            $xml = $this->xml($sheetXml);
            $rows = [];
            foreach ($xml->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $row) {
                if (count($rows) >= $maxRows) {
                    break;
                }
                $values = [];
                foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                    $reference = (string) $cell['r'];
                    $column = $this->columnIndex($reference);
                    if ($column < 0 || $column >= $maxColumns) {
                        continue;
                    }
                    $values[$column] = $this->cellValue($cell, $sharedStrings);
                }
                $lastColumn = $values ? max(array_keys($values)) : 0;
                $normalized = [];
                for ($index = 0; $index <= $lastColumn; $index++) {
                    $normalized[] = $values[$index] ?? '';
                }
                $rows[] = $normalized;
            }

            return ['sheet' => 'Sheet 1', 'rows' => $rows, 'truncated' => count($rows) >= $maxRows];
        } finally {
            $zip->close();
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');
        if ($content === false) {
            return [];
        }

        $xml = $this->xml($content);
        return array_map(
            fn (SimpleXMLElement $node) => implode('', array_map('strval', $node->xpath('.//*[local-name()="t"]') ?: [])),
            $xml->xpath('//*[local-name()="si"]') ?: [],
        );
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbookContent = $zip->getFromName('xl/workbook.xml');
        $relationsContent = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookContent === false || $relationsContent === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = $this->xml($workbookContent);
        $workbook->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheet = ($workbook->xpath('//x:sheets/x:sheet') ?: [])[0] ?? null;
        $relationshipId = $sheet?->attributes('r', true)?->id;
        if (! $relationshipId) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relations = $this->xml($relationsContent);
        foreach ($relations->xpath('//*[local-name()="Relationship"]') ?: [] as $relationship) {
            if ((string) $relationship['Id'] === (string) $relationshipId) {
                return 'xl/'.ltrim((string) $relationship['Target'], '/');
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];
        if ($type === 'inlineStr') {
            return implode('', array_map('strval', $cell->xpath('.//*[local-name()="t"]') ?: []));
        }

        $valueNodes = $cell->xpath('./*[local-name()="v"]') ?: [];
        $value = isset($valueNodes[0]) ? (string) $valueNodes[0] : '';

        return match ($type) {
            's' => (string) ($sharedStrings[(int) $value] ?? ''),
            'b' => $value === '1' ? 'TRUE' : 'FALSE',
            default => $value,
        };
    }

    private function columnIndex(string $reference): int
    {
        if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return -1;
        }

        $number = 0;
        foreach (str_split(strtoupper($matches[1])) as $character) {
            $number = ($number * 26) + (ord($character) - 64);
        }

        return $number - 1;
    }

    private function xml(string $content): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
            if (! $xml) {
                throw new RuntimeException('Struktur file tidak valid.');
            }
            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}

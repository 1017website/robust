<?php

namespace App\Support;

final class StructuredSpecification
{
    public static function parse(?string $specification): array
    {
        $lines = preg_split('/\R/u', trim((string) $specification)) ?: [];
        $sections = [];
        $currentSection = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\[(.+)]$/u', $line, $matches)) {
                $sections[] = [
                    'title' => trim($matches[1]),
                    'rows' => [],
                ];
                $currentSection = array_key_last($sections);

                continue;
            }

            if ($currentSection === null) {
                $sections[] = [
                    'title' => 'Spesifikasi',
                    'rows' => [],
                ];
                $currentSection = array_key_last($sections);
            }

            if (str_starts_with($line, '@')) {
                $breakdown = self::breakdownRow(substr($line, 1));
                if ($breakdown) {
                    $sections[$currentSection]['rows'][] = $breakdown;

                    continue;
                }
            }

            [$label, $value] = str_contains($line, ':')
                ? array_map('trim', explode(':', $line, 2))
                : ['', $line];
            $sections[$currentSection]['rows'][] = [
                'type' => 'detail',
                'label' => $label,
                'value' => $value,
            ];
        }

        return array_values(array_filter(
            $sections,
            fn (array $section): bool => filled($section['title']) || count($section['rows']) > 0
        ));
    }

    public static function flatten(?string $specification): array
    {
        $rows = [];

        foreach (self::parse($specification) as $section) {
            $rows[] = [
                'type' => 'section',
                'title' => $section['title'],
            ];
            array_push($rows, ...$section['rows']);
        }

        if ($rows === []) {
            return [
                ['type' => 'section', 'title' => 'Spesifikasi'],
                ['type' => 'detail', 'label' => '', 'value' => '-'],
            ];
        }

        return $rows;
    }

    private static function breakdownRow(string $line): ?array
    {
        $parts = array_map('trim', explode('|', trim($line)));

        if (count($parts) === 3 && self::isNumericInput($parts[0])) {
            [$qty, $unit, $unitPrice] = $parts;
            $label = '';
        } elseif (count($parts) === 3 && self::isNumericInput($parts[1])) {
            [$label, $qty, $unit] = $parts;
            $unitPrice = null;
        } elseif (count($parts) === 4 && self::isNumericInput($parts[1])) {
            [$label, $qty, $unit, $unitPrice] = $parts;
        } else {
            return null;
        }

        return [
            'type' => 'breakdown',
            'label' => $label,
            'qty' => self::numericValue($qty),
            'unit' => $unit,
            'unit_price' => filled($unitPrice) ? self::numericValue($unitPrice) : null,
        ];
    }

    private static function isNumericInput(string $value): bool
    {
        return (bool) preg_match('/^-?[\d.,\s]+$/', trim($value));
    }

    private static function numericValue(string $value): float
    {
        $normalized = preg_replace('/[^\d,.\-]/u', '', $value) ?? '0';
        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');

        if ($hasComma && $hasDot) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif ($hasComma) {
            $normalized = preg_match('/,\d{3}$/', $normalized)
                ? str_replace(',', '', $normalized)
                : str_replace(',', '.', $normalized);
        } elseif ($hasDot && preg_match('/\.\d{3}$/', $normalized)) {
            $normalized = str_replace('.', '', $normalized);
        }

        return (float) $normalized;
    }
}

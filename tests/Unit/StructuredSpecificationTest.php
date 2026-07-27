<?php

namespace Tests\Unit;

use App\Support\StructuredSpecification;
use PHPUnit\Framework\TestCase;

class StructuredSpecificationTest extends TestCase
{
    public function test_it_parses_sections_details_and_price_breakdowns(): void
    {
        $sections = StructuredSpecification::parse(
            "[General]\nType: WBF-200-S\n[Utilities]\nElectrical Socket: IP55\n@ 4 | pcs | 500000\n@ Duct Length | 6 | m"
        );

        $this->assertCount(2, $sections);
        $this->assertSame('General', $sections[0]['title']);
        $this->assertSame([
            'type' => 'detail',
            'label' => 'Type',
            'value' => 'WBF-200-S',
        ], $sections[0]['rows'][0]);
        $this->assertSame([
            'type' => 'breakdown',
            'label' => '',
            'qty' => 4.0,
            'unit' => 'pcs',
            'unit_price' => 500000.0,
        ], $sections[1]['rows'][1]);
        $this->assertSame([
            'type' => 'breakdown',
            'label' => 'Duct Length',
            'qty' => 6.0,
            'unit' => 'm',
            'unit_price' => null,
        ], $sections[1]['rows'][2]);
    }

    public function test_it_keeps_legacy_plain_text_compatible(): void
    {
        $sections = StructuredSpecification::parse('Top phenolic resin, rangka steel powder coating');

        $this->assertSame('Spesifikasi', $sections[0]['title']);
        $this->assertSame('', $sections[0]['rows'][0]['label']);
        $this->assertSame('Top phenolic resin, rangka steel powder coating', $sections[0]['rows'][0]['value']);
    }

    public function test_it_parses_and_flattens_nested_subdetails(): void
    {
        $specification = implode("\n", [
            '[Construction & Materials]',
            'Drawer / Door Panel: Galvanized steel plate',
            '> Thickness: 1.2 mm',
            '> Finishing: Chemical-resistant epoxy powder coating',
        ]);

        $sections = StructuredSpecification::parse($specification);

        $this->assertSame('Drawer / Door Panel', $sections[0]['rows'][0]['label']);
        $this->assertSame([
            'type' => 'subdetail',
            'label' => 'Thickness',
            'value' => '1.2 mm',
        ], $sections[0]['rows'][0]['children'][0]);

        $flattened = StructuredSpecification::flatten($specification);
        $this->assertSame([
            'type' => 'subdetail',
            'label' => 'Thickness',
            'value' => '1.2 mm',
            'parent_label' => 'Drawer / Door Panel',
        ], $flattened[2]);
    }

    public function test_flatten_provides_export_fallback_for_empty_specification(): void
    {
        $this->assertSame([
            ['type' => 'section', 'title' => 'Spesifikasi'],
            ['type' => 'detail', 'label' => '', 'value' => '-'],
        ], StructuredSpecification::flatten(null));
    }
}

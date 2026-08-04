<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class QuotationWorkflowSimplificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sales_can_upload_and_preview_an_xlsx_quotation_without_spv_approval(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales', 'name' => 'Sales A']);
        $spv = User::factory()->create(['role' => 'sales_spv']);

        $response = $this->actingAs($sales)->post(route('sales.quotations.store'), [
            'customer_name' => 'PT Preview Excel',
            'project_name' => 'Lab Preview',
            'delivery_method' => 'email',
            'quote_date' => today()->format('Y-m-d'),
            'valid_until' => today()->addMonth()->format('Y-m-d'),
            'priority' => 'medium',
            'currency' => 'IDR',
            'quotation_mode' => 'upload',
            'quotation_file' => UploadedFile::fake()->createWithContent('penawaran-a.xlsx', $this->xlsxFixture()),
            'action' => 'publish',
        ]);

        $quotation = Quotation::where('project_name', 'Lab Preview')->firstOrFail();
        $response->assertRedirect(route('sales.quotations.show', $quotation));
        $this->assertSame('ready', $quotation->status);
        $this->assertSame('upload', $quotation->creation_mode);
        $this->assertCount(0, $quotation->items);
        $document = $quotation->documents()->where('category', 'quotation_file')->firstOrFail();

        $this->actingAs($sales)->get(route('documents.preview', $document))
            ->assertOk()
            ->assertSee('Wall Bench')
            ->assertSee('1250000');

        $this->actingAs($spv)->get(route('spv.quotation-approvals.show', $quotation))
            ->assertOk()
            ->assertSee('Sales A')
            ->assertSee('Tidak diperlukan approval SPV')
            ->assertDontSee('1250000');
        $this->actingAs($spv)->get(route('documents.preview', $document))->assertForbidden();
        $this->actingAs($spv)->get(route('sales.quotations.index'))->assertForbidden();
    }

    public function test_builder_quotation_is_ready_immediately_and_hpp_input_is_not_exposed(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $customer = Customer::create(['name' => 'PT Builder', 'pipeline_stage' => 'follow_up', 'sales_id' => $sales->id]);

        $this->actingAs($sales)->post(route('sales.quotations.store'), [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'project_name' => 'Builder Komersial',
            'delivery_method' => 'email',
            'quote_date' => today()->format('Y-m-d'),
            'valid_until' => today()->addMonth()->format('Y-m-d'),
            'priority' => 'medium',
            'currency' => 'IDR',
            'quotation_mode' => 'builder',
            'discount_type' => 'percent',
            'discount_value' => 0,
            'tax_percent' => 11,
            'items' => [[
                'name' => 'Wall Bench',
                'qty' => 1,
                'unit' => 'Unit',
                'unit_price' => 1250000,
                'cost_price' => 999999999,
            ]],
            'action' => 'publish',
        ])->assertRedirect();

        $quotation = Quotation::where('project_name', 'Builder Komersial')->firstOrFail();
        $this->assertSame('ready', $quotation->status);
        $this->assertSame(0.0, (float) $quotation->items()->firstOrFail()->cost_price);
        $this->actingAs($sales)->get(route('sales.quotations.edit', $quotation))
            ->assertOk()
            ->assertDontSee('HPP')
            ->assertDontSee('Total HPP')
            ->assertDontSee('999999999');
        $this->actingAs($sales)->get(route('sales.quotations.pdf', $quotation))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_production_can_slide_progress_and_upload_evidence_without_seeing_prices(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales']);
        $production = User::factory()->create(['role' => 'production']);
        $quotation = Quotation::create([
            'code' => 'Q-PROGRESS-001',
            'customer_name' => 'PT Progress',
            'project_name' => 'Produksi Progress',
            'sales_id' => $sales->id,
            'creation_mode' => 'builder',
            'status' => 'customer_accepted',
            'grand_total' => 5000000,
        ]);
        $quotation->items()->create([
            'name' => 'Cabinet', 'qty' => 1, 'unit' => 'Unit', 'cost_price' => 3000000,
            'unit_price' => 5000000, 'total' => 5000000, 'sort_order' => 0,
        ]);
        $project = Project::create([
            'code' => 'PRJ-PROGRESS-001', 'name' => 'Produksi Progress', 'quotation_id' => $quotation->id,
            'status' => 'ongoing', 'project_value' => 5000000, 'total_value' => 5000000,
        ]);

        $this->actingAs($production)->put(route('project-workflow.production', $project), [
            'production_status' => 'production',
            'production_progress' => 65,
            'production_note' => 'Rangka selesai, masuk finishing.',
            'progress_files' => [UploadedFile::fake()->image('progress-65.jpg')],
        ])->assertRedirect();

        $this->assertSame(65, $project->workflow()->firstOrFail()->production_progress);
        $this->assertDatabaseHas('documents', [
            'documentable_type' => Project::class,
            'documentable_id' => $project->id,
            'category' => 'production_progress',
        ]);
        $this->actingAs($production)->get(route('project-workspace.show', $project))
            ->assertOk()
            ->assertSee('65%')
            ->assertSee('progress-65')
            ->assertDontSee('Rp 5.000.000')
            ->assertDontSee('Nilai Project');
    }

    public function test_spv_monitoring_pages_do_not_expose_commercial_prices(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $spv = User::factory()->create(['role' => 'sales_spv']);
        $customer = Customer::create([
            'name' => 'PT Harga Terbatas',
            'pipeline_stage' => 'follow_up',
            'sales_id' => $sales->id,
        ]);
        $quotation = Quotation::create([
            'code' => 'Q-PRIVATE-PRICE',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'project_name' => 'Project Harga Terbatas',
            'sales_id' => $sales->id,
            'creation_mode' => 'builder',
            'status' => 'customer_accepted',
            'subtotal' => 9876543,
            'grand_total' => 9876543,
        ]);
        $project = Project::create([
            'code' => 'PRJ-PRIVATE-PRICE',
            'name' => 'Project Harga Terbatas',
            'customer_id' => $customer->id,
            'quotation_id' => $quotation->id,
            'status' => 'ongoing',
            'project_value' => 9876543,
            'total_value' => 9876543,
        ]);

        foreach ([
            route('sales.customers.index', ['customer' => $customer->id]),
            route('activities.index'),
            route('sales.projects.index'),
            route('project-workspace.show', $project),
        ] as $url) {
            $this->actingAs($spv)->get($url)
                ->assertOk()
                ->assertDontSee('Rp 9.876.543')
                ->assertDontSee('9876543');
        }

        $this->actingAs($spv)->get(route('sales.projects.create'))->assertForbidden();
        $this->actingAs($spv)->get(route('sales.leads.create'))->assertForbidden();
        $this->actingAs($spv)->get(route('admin.purchase-order-requests.index'))->assertForbidden();
    }

    private function xlsxFixture(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'quotation-preview-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Penawaran" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>Item</t></is></c><c r="B1" t="inlineStr"><is><t>Harga</t></is></c></row><row r="2"><c r="A2" t="inlineStr"><is><t>Wall Bench</t></is></c><c r="B2"><v>1250000</v></c></row></sheetData></worksheet>');
        $zip->close();
        $content = file_get_contents($path);
        unlink($path);

        return $content;
    }
}

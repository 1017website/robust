<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OperationalPdfExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_request_po_pdf_is_modern_branded_and_scoped_to_the_sales_owner(): void
    {
        [$sales, $otherSales, $admin, $requestPo] = $this->makeDocuments();

        $this->actingAs($sales)
            ->get(route('admin.purchase-order-requests.show', $requestPo))
            ->assertOk()
            ->assertSee(route('admin.purchase-order-requests.pdf', $requestPo), false)
            ->assertSee('Export PDF');

        $response = $this->actingAs($sales)
            ->get(route('admin.purchase-order-requests.pdf', $requestPo));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('rpo-pdf-modern.pdf');
        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
        $this->assertStringContainsString('REQUEST PURCHASE ORDER', $response->getContent());
        $this->assertStringContainsString('ROBUST', $response->getContent());
        $this->assertStringContainsString('WALL BENCH - STEEL STRUCTURE', $response->getContent());
        $this->assertStringContainsString('CHECKLIST KELENGKAPAN', $response->getContent());

        $this->actingAs($otherSales)
            ->get(route('admin.purchase-order-requests.pdf', $requestPo))
            ->assertForbidden();
        $this->actingAs($admin)
            ->get(route('admin.purchase-order-requests.pdf', $requestPo))
            ->assertOk();
    }

    public function test_invoice_pdf_is_modern_branded_and_restricted_to_admin_roles(): void
    {
        [$sales, $otherSales, $admin, $requestPo, $invoice] = $this->makeDocuments();

        $this->actingAs($admin)
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee(route('admin.invoices.pdf', $invoice), false)
            ->assertSee('Export PDF');

        $response = $this->actingAs($admin)
            ->get(route('admin.invoices.pdf', $invoice));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('inv-pdf-modern.pdf');
        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
        $this->assertStringContainsString('INVOICE', $response->getContent());
        $this->assertStringContainsString('ROBUST', $response->getContent());
        $this->assertStringContainsString('RINGKASAN TAGIHAN', $response->getContent());
        $this->assertStringContainsString('TERMIN PEMBAYARAN', $response->getContent());

        $this->actingAs($sales)
            ->get(route('admin.invoices.pdf', $invoice))
            ->assertForbidden();
        $this->actingAs($otherSales)
            ->get(route('admin.invoices.pdf', $invoice))
            ->assertForbidden();
    }

    private function makeDocuments(): array
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $otherSales = User::factory()->create(['role' => 'sales']);
        $admin = User::factory()->create(['role' => 'sales_admin']);
        $customer = Customer::create([
            'code' => 'CUST-PDF-MODERN',
            'name' => 'PT Customer PDF Modern',
            'pipeline_stage' => 'won_closing',
            'sales_id' => $sales->id,
        ]);
        $quotation = Quotation::create([
            'code' => 'Q-PDF-MODERN',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'pic_name' => 'PIC PDF Modern',
            'project_name' => 'Project PDF Modern',
            'sales_id' => $sales->id,
            'quote_date' => today(),
            'valid_until' => today()->addMonth(),
            'subtotal' => 4000000,
            'discount_amount' => 0,
            'tax_percent' => 11,
            'tax_amount' => 440000,
            'additional_total' => 250000,
            'grand_total' => 4690000,
            'status' => 'request_po_created',
        ]);
        $quotation->items()->create([
            'category' => 'Furniture',
            'name' => 'WALL BENCH - STEEL STRUCTURE',
            'variant' => 'WBF-PDF-200',
            'specification' => <<<'SPEC'
[General]
Type: WBF-PDF-200
Model: Laboratory bench with floor-mounted steel cabinet
Manufacturer: PT. Robust Multilab Solusindo

[Dimensions (W x D x H, mm)]
Overall Dimension: 2000 x 700 x 850

[Construction & Materials]
Main Structure: Steel plate powder coating
Worktop: Phenolic resin 16 mm
SPEC,
            'qty' => 2,
            'unit' => 'Unit',
            'cost_price' => 1600000,
            'unit_price' => 2000000,
            'margin' => 20,
            'total' => 4000000,
            'sort_order' => 0,
        ]);
        $requestPo = PurchaseOrderRequest::create([
            'code' => 'RPO-PDF-MODERN',
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'project_number' => 'PRJ-PDF-MODERN',
            'customer_name' => $customer->name,
            'customer_area' => 'Bandung',
            'customer_division' => 'Laboratorium',
            'requested_by' => $sales->id,
            'request_date' => today(),
            'customer_po_number' => 'PO-CUST-PDF-001',
            'delivery_address' => 'Jl. Contoh PDF Modern No. 1, Bandung',
            'delivery_pic_name' => 'PIC PDF Modern',
            'delivery_pic_phone' => '081234567890',
            'npwp_name' => $customer->name,
            'npwp_number' => '01.234.567.8-999.000',
            'payment_term' => '100% setelah invoice',
            'expected_delivery_date' => today()->addMonth(),
            'checklist' => collect(PurchaseOrderRequest::checklistItems())
                ->mapWithKeys(fn ($label, $key) => [$key => true])
                ->all(),
            'checklist_completed_at' => now(),
            'accurate_po_number' => 'ACC-PO-PDF-001',
            'accurate_po_date' => today(),
            'accurate_note' => 'Sudah dibuat di Accurate.',
            'admin_note' => 'Dokumen pengujian PDF modern.',
            'status' => 'paid',
        ]);
        $invoice = Invoice::create([
            'code' => 'INV-PDF-MODERN',
            'purchase_order_request_id' => $requestPo->id,
            'invoice_date' => today(),
            'customer_name' => $customer->name,
            'project_number' => $requestPo->project_number,
            'project_name' => $quotation->project_name,
            'subtotal' => $quotation->subtotal,
            'tax_amount' => $quotation->tax_amount,
            'installation_amount' => $quotation->additional_total,
            'grand_total' => $quotation->grand_total,
            'paid_total' => $quotation->grand_total,
            'status' => 'paid',
            'note' => 'Terima kasih atas kepercayaan Anda.',
            'created_by' => $admin->id,
        ]);
        $invoice->terms()->create([
            'term_number' => 1,
            'description' => 'Pelunasan 100%',
            'percentage' => 100,
            'amount' => $invoice->grand_total,
            'due_date' => today()->addDays(14),
            'issued_date' => today(),
            'accurate_invoice_number' => 'ACC-INV-PDF-001',
            'paid_amount' => $invoice->grand_total,
            'paid_date' => today(),
            'status' => 'paid',
        ]);

        return [$sales, $otherSales, $admin, $requestPo, $invoice];
    }
}

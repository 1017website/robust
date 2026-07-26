<?php

namespace Tests\Manual;

use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Audit manual terhadap database lokal aktif.
 *
 * Test ini sengaja tidak menggunakan DatabaseTransactions/RefreshDatabase:
 * data hasil simulasi harus tetap tersedia untuk pemeriksaan melalui web.
 * File ditempatkan di tests/Manual agar tidak ikut test suite reguler.
 */
class LiveWorkflowAuditTest extends TestCase
{
    private array $audit = [];

    public function test_complete_live_workflow_and_role_access(): void
    {
        $administrator = User::where('email', 'superadmin@robust.test')->firstOrFail();
        $salesAdmin = User::where('email', 'admin@robust.test')->firstOrFail();
        $sales = User::where('email', 'sales@robust.test')->firstOrFail();
        $otherSales = User::where('email', 'sales2@robust.test')->firstOrFail();
        $spv = User::where('email', 'spv@robust.test')->firstOrFail();
        $drafter = User::where('email', 'drafter@robust.test')->firstOrFail();
        $production = User::where('email', 'production@robust.test')->firstOrFail();
        $administration = User::where('email', 'administration@robust.test')->firstOrFail();
        $qc = User::where('email', 'qc@robust.test')->firstOrFail();
        $delivery = User::where('email', 'delivery@robust.test')->firstOrFail();

        $this->record('accounts', 'passed', [
            'administrator' => $administrator->email,
            'sales_admin' => $salesAdmin->email,
            'sales_owner' => $sales->email,
            'sales_other' => $otherSales->email,
            'sales_spv' => $spv->email,
            'drafter' => $drafter->email,
            'production' => $production->email,
            'administration' => $administration->email,
            'qc' => $qc->email,
            'delivery' => $delivery->email,
        ]);

        // Akses awal per role.
        $this->actingAs($administrator)->get(route('dashboard'))->assertOk();
        $this->actingAs($salesAdmin)->get(route('admin.pra-leads.index'))->assertOk();
        $this->actingAs($sales)->get(route('admin.pra-leads.index'))->assertForbidden();
        $this->actingAs($spv)->get(route('spv.quotation-approvals.index'))->assertOk();
        $this->actingAs($drafter)->get(route('drafter.design-requests.index'))->assertOk();
        $this->actingAs($production)->get(route('drafter.design-requests.index'))->assertOk();
        $this->actingAs($administration)->get(route('administration.project-monitoring.index'))->assertOk();
        $this->actingAs($qc)->get(route('drafter.projects.index'))->assertOk();
        $this->actingAs($delivery)->get(route('drafter.projects.index'))->assertOk();
        $this->actingAs($sales)->get(route('admin.invoices.index'))->assertForbidden();
        $this->record('role_boundary_initial', 'passed');

        // 1. Sales Admin menginput Pra Lead sebagai draft.
        $this->actingAs($salesAdmin)->post(route('admin.pra-leads.store'), [
            'instansi' => 'PT Audit Laboratorium Nusantara',
            'pic_name' => 'Bapak Ahmad Audit',
            'pic_position' => 'Kepala Laboratorium',
            'phone' => '081234560726',
            'email' => 'ahmad.audit@example.test',
            'source' => 'website',
            'lab_type' => 'Laboratorium Kimia',
            'location' => 'Bandung',
            'initial_need' => 'Wall bench dan fume hood untuk laboratorium kimia.',
            'admin_note' => 'Data simulasi audit alur lintas role pada 26 Juli 2026.',
            'est_value_min' => 100000000,
            'est_value_max' => 175000000,
            'priority' => 'high',
            'action' => 'save',
        ])->assertRedirect(route('admin.pra-leads.index'));

        $praLead = PraLead::where('instansi', 'PT Audit Laboratorium Nusantara')->firstOrFail();
        $this->assertSame('draft', $praLead->status);

        // 2. Assignment ke Sales owner dan kirim menjadi Request Masuk.
        $this->actingAs($salesAdmin)->put(route('admin.pra-leads.update', $praLead), [
            'instansi' => $praLead->instansi,
            'pic_name' => $praLead->pic_name,
            'pic_position' => $praLead->pic_position,
            'phone' => $praLead->phone,
            'email' => $praLead->email,
            'source' => $praLead->source,
            'lab_type' => $praLead->lab_type,
            'location' => $praLead->location,
            'initial_need' => $praLead->initial_need,
            'admin_note' => $praLead->admin_note,
            'est_value_min' => $praLead->est_value_min,
            'est_value_max' => $praLead->est_value_max,
            'priority' => $praLead->priority,
            'assigned_sales_id' => $sales->id,
            'action' => 'send',
        ])->assertRedirect(route('admin.pra-leads.index'));

        $this->actingAs($salesAdmin)->get(route('admin.assignment.index'))->assertOk();
        $this->assertSame('waiting_acceptance', $praLead->fresh()->status);
        $this->record('pra_lead_assignment', 'passed', [
            'pra_lead_id' => $praLead->id,
            'code' => $praLead->code,
            'assigned_to' => $sales->email,
        ]);

        // 3. Sales lain tidak dapat menerima request milik Sales owner.
        $this->actingAs($otherSales)
            ->post(route('sales.request-masuk.accept', $praLead))
            ->assertForbidden();
        $this->actingAs($sales)->get(route('sales.request-masuk.index'))->assertOk();
        $this->actingAs($sales)
            ->post(route('sales.request-masuk.accept', $praLead))
            ->assertRedirect(route('sales.leads.index'));

        $lead = Lead::where('pra_lead_id', $praLead->id)->firstOrFail();
        $customer = Customer::findOrFail($lead->customer_id);
        $this->assertSame('accepted', $praLead->fresh()->status);
        $this->assertSame($sales->id, $lead->sales_id);
        $this->assertSame($sales->id, $customer->sales_id);

        // Idempotensi: klik terima kedua tidak membuat Lead/Customer ganda.
        $this->actingAs($sales)->post(route('sales.request-masuk.accept', $praLead->fresh()))->assertRedirect();
        $this->assertSame(1, Lead::where('pra_lead_id', $praLead->id)->count());
        $this->assertSame(1, Customer::where('name', $praLead->instansi)->count());
        $this->record('request_masuk_to_lead_customer', 'passed', [
            'lead_id' => $lead->id,
            'lead_code' => $lead->code,
            'customer_id' => $customer->id,
            'customer_code' => $customer->code,
        ]);

        // 4. Assignment Lead: Sales A -> Sales B -> Sales A dan cek isolasi owner.
        $this->actingAs($otherSales)->get(route('sales.leads.show', $lead))->assertForbidden();
        $this->actingAs($salesAdmin)->post(route('admin.assignment.reassign'), [
            'lead_id' => $lead->id,
            'to_sales_id' => $otherSales->id,
        ])->assertRedirect();
        $this->actingAs($sales)->get(route('sales.leads.show', $lead->fresh()))->assertForbidden();
        $this->actingAs($otherSales)->get(route('sales.leads.show', $lead->fresh()))->assertOk();
        $this->actingAs($sales)->get(route('sales.customers.show', $customer->fresh()))->assertForbidden();
        $this->actingAs($otherSales)->get(route('sales.customers.show', $customer->fresh()))->assertOk();
        $this->assertSame($otherSales->id, $customer->fresh()->sales_id);
        $this->actingAs($salesAdmin)->post(route('admin.assignment.reassign'), [
            'lead_id' => $lead->id,
            'to_sales_id' => $sales->id,
        ])->assertRedirect();
        $this->actingAs($sales)->get(route('sales.leads.show', $lead->fresh()))->assertOk();
        $this->actingAs($sales)->get(route('sales.customers.show', $customer->fresh()))->assertOk();
        $this->assertSame($sales->id, $customer->fresh()->sales_id);
        $this->record('lead_reassignment_owner_isolation', 'passed');

        // 5. Sales membuat Design Request untuk Drafter.
        $this->actingAs($sales)->post(route('sales.design-requests.store'), [
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'pic_name' => $lead->pic_name,
            'project_name' => 'Audit E2E Laboratorium Kimia 2026',
            'request_date' => '2026-07-26',
            'deadline' => '2026-08-09',
            'priority' => 'high',
            'short_description' => 'Desain wall bench dan fume hood sesuai kebutuhan customer.',
            'lab_type' => 'Laboratorium Kimia',
            'capacity' => '12 pengguna',
            'detail_need' => 'Layout, rendering 3D, BOQ, spesifikasi terstruktur, dan estimasi biaya.',
            'scope_checklist' => ['Wall Bench', 'Fume Hood'],
            'outputs' => ['rendering_3d', 'boq', 'cost_estimation'],
            'extra_note' => 'Gunakan standar Robust 2026.',
            'production_pic_id' => $drafter->id,
            'production_note' => 'Prioritaskan layout utilitas air dan listrik.',
            'action' => 'send',
        ])->assertRedirect(route('sales.design-requests.index'));

        $designRequest = DesignRequest::where('project_name', 'Audit E2E Laboratorium Kimia 2026')->firstOrFail();
        $this->assertSame('assigned', $designRequest->status);
        $this->assertSame('design_request', $lead->fresh()->stage);
        $this->actingAs($otherSales)->get(route('sales.design-requests.show', $designRequest))->assertForbidden();
        $this->actingAs($drafter)->get(route('drafter.design-requests.show', $designRequest))->assertOk();

        $specification = <<<'SPEC'
[General]
Type: WBF-AUDIT-200
Model: Laboratory bench with floor-mounted steel under-bench cabinet
Manufacturer: PT. Robust Multilab Solusindo
Standards Compliance: ISO 9001:2015 & ISO 14001:2015

[Dimensions (W x D x H, mm)]
Overall Dimension: 2000 x 700 x 850

[Construction & Materials]
Main Structure: Steel plate structure, thickness 1.2 mm, epoxy powder coating
Worktop: Phenolic resin worktop, thickness 16 mm

[Utilities & Accessories]
Electrical Socket: Single electric socket, IP55, laboratory grade
@ 2 | pcs | 500000
SPEC;

        // 6. Drafter mengisi spesifikasi per bagian + gambar. Drafter tidak boleh finalisasi HPP.
        $this->actingAs($drafter)->post(route('drafter.design-requests.feedback', $designRequest), [
            'dimensions' => [
                ['item' => 'Wall Bench', 'size' => '2000 x 700 x 850 mm'],
            ],
            'materials' => [
                ['item' => 'Main Structure', 'material' => 'Steel plate 1.2 mm', 'finish' => 'Epoxy powder coating'],
            ],
            'accessories' => ['Electrical socket IP55'],
            'technical_note' => 'Layout dan gambar penawaran selesai untuk review produksi.',
            'items' => [[
                'category' => 'Furniture',
                'name' => 'WALL BENCH - STEEL STRUCTURE',
                'variant' => 'WBF-AUDIT-200',
                'specification' => $specification,
                'qty' => 2,
                'unit' => 'Unit',
                'unit_price' => 999999999,
                'quotation_image' => UploadedFile::fake()->image('wall-bench-audit.png', 900, 700),
            ]],
            'action' => 'review',
        ])->assertRedirect(route('drafter.design-requests.index'));

        $designRequest->refresh();
        $designItem = $designRequest->items()->firstOrFail();
        $this->assertSame('review', $designRequest->status);
        $this->assertSame(0.0, (float) $designItem->unit_price);
        $this->assertNotNull($designItem->quotation_image_path);
        $this->assertTrue(Storage::disk('public')->exists($designItem->quotation_image_path));

        $this->actingAs($drafter)->post(route('drafter.design-requests.feedback', $designRequest), [
            'items' => [[
                'id' => $designItem->id,
                'category' => $designItem->category,
                'name' => $designItem->name,
                'specification' => $specification,
                'qty' => 2,
                'unit' => 'Unit',
                'unit_price' => 1750000,
            ]],
            'action' => 'submit',
        ])->assertSessionHasErrors('action');

        // 7. Produksi menetapkan HPP dan submit final.
        $this->actingAs($production)->post(route('drafter.design-requests.feedback', $designRequest), [
            'cost_material' => 2200000,
            'cost_production' => 900000,
            'cost_installation' => 400000,
            'technical_note' => 'HPP sudah diverifikasi Produksi.',
            'items' => [[
                'id' => $designItem->id,
                'category' => $designItem->category,
                'name' => $designItem->name,
                'variant' => $designItem->variant,
                'specification' => $specification,
                'qty' => 2,
                'unit' => 'Unit',
                'unit_price' => 1750000,
            ]],
            'action' => 'submit',
        ])->assertRedirect(route('drafter.design-requests.index'));

        $designRequest->refresh();
        $designItem = $designRequest->items()->firstOrFail();
        $this->assertSame('completed', $designRequest->status);
        $this->assertSame(3500000.0, (float) $designRequest->cost_total);
        $this->assertSame(1750000.0, (float) $designItem->unit_price);
        $this->record('design_request_drafter_production', 'passed', [
            'design_request_id' => $designRequest->id,
            'code' => $designRequest->code,
            'item_id' => $designItem->id,
            'image' => $designItem->quotation_image_path,
            'hpp_total' => (float) $designRequest->cost_total,
        ]);

        // 8. Sales membuat draft penawaran dari hasil DR.
        $quoteData = [
            'design_request_id' => $designRequest->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'pic_name' => $lead->pic_name,
            'project_name' => $designRequest->project_name,
            'delivery_method' => 'email',
            'quote_date' => '2026-07-26',
            'valid_until' => '2026-08-25',
            'priority' => 'high',
            'currency' => 'IDR',
            'discount_type' => 'percent',
            'discount_value' => 5,
            'discount_reason' => 'Diskon proyek audit',
            'tax_percent' => 11,
            'target_margin' => 20,
            'additional_costs' => [
                ['label' => 'Biaya instalasi', 'amount' => 400000],
            ],
            'items' => [[
                'source_design_request_item_id' => $designItem->id,
                'category' => $designItem->category,
                'name' => $designItem->name,
                'variant' => $designItem->variant,
                'specification' => $designItem->specification,
                'qty' => 2,
                'unit' => 'Unit',
                'cost_price' => 1750000,
                'margin' => 20,
            ]],
        ];

        $this->actingAs($sales)->post(route('sales.quotations.store'), $quoteData + [
            'action' => 'draft',
        ])->assertRedirect();

        $quotation = Quotation::where('design_request_id', $designRequest->id)->latest()->firstOrFail();
        $quoteItem = $quotation->items()->firstOrFail();
        $this->assertSame('draft', $quotation->status);
        $this->assertSame(2187500.0, (float) $quoteItem->unit_price);
        $this->assertNotNull($quoteItem->quotation_image_path);
        $this->assertTrue(Storage::disk('public')->exists($quoteItem->quotation_image_path));
        $this->actingAs($otherSales)->get(route('sales.quotations.show', $quotation))->assertForbidden();
        $this->actingAs($spv)->get(route('sales.quotations.show', $quotation))->assertForbidden();

        $this->actingAs($sales)
            ->post(route('sales.quotations.submit-approval', $quotation))
            ->assertRedirect();
        $this->assertSame('waiting_approval', $quotation->fresh()->status);

        // Sales tidak dapat memakai endpoint approval SPV.
        $this->actingAs($sales)
            ->post(route('spv.quotation-approvals.approve', $quotation), ['approval_note' => 'Tidak sah'])
            ->assertForbidden();

        // 9. SPV review dan approval.
        $this->actingAs($spv)->get(route('spv.quotation-approvals.show', $quotation))->assertOk();
        $this->actingAs($spv)->post(route('spv.quotation-approvals.approve', $quotation), [
            'approval_note' => 'Harga, margin, spesifikasi, dan gambar telah diperiksa.',
        ])->assertRedirect(route('spv.quotation-approvals.show', $quotation));
        $this->assertSame('approved', $quotation->fresh()->status);

        // 10. Sales mengirim penawaran dan mencatat persetujuan customer.
        $this->actingAs($sales)->post(route('sales.quotations.sent-to-customer', $quotation))->assertRedirect();
        $this->actingAs($sales)->post(route('sales.quotations.won', $quotation), [
            'note' => 'Customer menyetujui penawaran simulasi.',
        ])->assertRedirect();
        $quotation->refresh();
        $this->assertSame('customer_accepted', $quotation->status);
        $this->assertSame('won', $lead->fresh()->stage);
        $this->record('quotation_sales_spv_customer', 'passed', [
            'quotation_id' => $quotation->id,
            'code' => $quotation->code,
            'subtotal' => (float) $quotation->subtotal,
            'grand_total' => (float) $quotation->grand_total,
            'status' => $quotation->status,
        ]);

        // 11. Sales membuat Project dari penawaran yang dimenangkan.
        $this->actingAs($sales)->post(route('sales.projects.store'), [
            'quotation_id' => $quotation->id,
            'name' => 'Project Audit Laboratorium Kimia 2026',
            'description' => 'Project live audit lintas role.',
            'category' => 'Laboratory Furniture',
            'type' => 'Supply & Installation',
            'priority' => 'high',
            'status' => 'planning',
            'start_date' => '2026-07-27',
            'target_date' => '2026-09-15',
            'work_method' => 'Produksi dan instalasi',
            'location' => 'Bandung',
            'scope_of_work' => 'Wall bench, utilitas, pengiriman, dan instalasi.',
            'payment_scheme' => '100% setelah invoice',
            'project_manager_id' => $sales->id,
            'internal_team' => [$drafter->id, $production->id],
        ])->assertRedirect();

        $project = Project::where('quotation_id', $quotation->id)->firstOrFail();
        $this->actingAs($otherSales)->get(route('sales.projects.show', $project))->assertForbidden();
        $this->actingAs($sales)->get(route('sales.projects.show', $project))->assertOk();

        // 12. Sales membuat Request PO; Sales tidak boleh memproses status Accurate.
        $this->actingAs($sales)->post(route('admin.purchase-order-requests.store'), [
            'quotation_id' => $quotation->id,
            'project_number' => $project->code,
            'customer_name' => $customer->name,
            'customer_area' => 'Bandung',
            'customer_division' => 'Laboratorium Kimia',
            'request_date' => '2026-07-26',
            'customer_po_number' => 'PO-CUST-AUDIT-2026-001',
            'delivery_address' => 'Jl. Audit Sistem No. 26, Bandung',
            'delivery_pic_name' => $lead->pic_name,
            'delivery_pic_phone' => $lead->phone,
            'npwp_name' => $customer->name,
            'npwp_number' => '01.234.567.8-999.000',
            'payment_term' => '100% setelah invoice',
            'expected_delivery_date' => '2026-09-15',
            'checklist' => [
                'quotation_approved' => 1,
                'customer_po' => 1,
                'customer_data' => 1,
                'delivery_address' => 1,
                'pic_contact' => 1,
                'payment_term' => 1,
            ],
            'admin_note' => 'Mohon proses ke Accurate.',
        ])->assertRedirect();

        $poRequest = PurchaseOrderRequest::where('quotation_id', $quotation->id)->firstOrFail();
        $this->actingAs($otherSales)->get(route('admin.purchase-order-requests.show', $poRequest))->assertForbidden();
        $this->actingAs($sales)->put(route('admin.purchase-order-requests.update', $poRequest), [
            'status' => 'po_created',
        ])->assertForbidden();

        // Gambar fabrikasi hanya boleh diunggah setelah Request PO tersedia.
        $this->actingAs($drafter)->post(route('documents.store'), [
            'documentable_type' => DesignRequest::class,
            'documentable_id' => $designRequest->id,
            'name' => 'Gambar Fabrikasi Audit',
            'category' => 'fabrication_drawing',
            'description' => 'Gambar fabrikasi sesudah Request PO.',
            'file' => UploadedFile::fake()->create('fabrikasi-audit.pdf', 40, 'application/pdf'),
        ])->assertRedirect();
        $fabrication = Document::where('name', 'Gambar Fabrikasi Audit')->firstOrFail();

        // Sales Admin melengkapi dan memproses PO di Accurate.
        $this->actingAs($salesAdmin)->put(route('admin.purchase-order-requests.update', $poRequest), [
            'status' => 'po_created',
            'accurate_po_number' => 'ACC-PO-AUDIT-2026-001',
            'accurate_po_date' => '2026-07-26',
            'accurate_note' => 'Berhasil dibuat di Accurate.',
            'delivery_address' => $poRequest->delivery_address,
            'delivery_pic_name' => $poRequest->delivery_pic_name,
            'delivery_pic_phone' => $poRequest->delivery_pic_phone,
            'npwp_name' => $poRequest->npwp_name,
            'npwp_number' => $poRequest->npwp_number,
            'payment_term' => $poRequest->payment_term,
            'expected_delivery_date' => '2026-09-15',
            'checklist' => collect(PurchaseOrderRequest::checklistItems())
                ->mapWithKeys(fn ($label, $key) => [$key => 1])
                ->all(),
        ])->assertRedirect();

        $poRequest->refresh();
        $this->assertSame('po_created', $poRequest->status);
        $this->assertTrue($poRequest->isChecklistComplete());
        $this->assertTrue(Storage::disk('public')->exists($fabrication->file_path));
        $this->record('project_and_request_po', 'passed', [
            'project_id' => $project->id,
            'project_code' => $project->code,
            'request_po_id' => $poRequest->id,
            'request_po_code' => $poRequest->code,
            'status' => $poRequest->status,
        ]);

        // 13. Sales Admin menerbitkan invoice, lalu Administrator mencatat lunas.
        $this->actingAs($sales)->get(route('admin.invoices.create', [
            'request_po' => $poRequest->id,
        ]))->assertForbidden();
        $this->actingAs($salesAdmin)->get(route('admin.invoices.create', [
            'request_po' => $poRequest->id,
        ]))->assertOk();

        $this->actingAs($salesAdmin)->post(route('admin.invoices.store'), [
            'purchase_order_request_id' => $poRequest->id,
            'invoice_date' => '2026-07-26',
            'note' => 'Invoice simulasi audit live.',
            'terms' => [[
                'description' => 'Pelunasan 100%',
                'percentage' => 100,
                'amount' => (float) $quotation->grand_total,
                'due_date' => '2026-08-09',
            ]],
        ])->assertRedirect();

        $invoice = Invoice::where('purchase_order_request_id', $poRequest->id)->firstOrFail();
        $term = $invoice->terms()->firstOrFail();
        $this->assertSame('issued', $invoice->status);
        $this->assertSame('invoicing', $poRequest->fresh()->status);

        $this->actingAs($administrator)->put(route('admin.invoices.terms.update', [$invoice, $term]), [
            'status' => 'paid',
            'issued_date' => '2026-07-26',
            'accurate_invoice_number' => 'ACC-INV-AUDIT-2026-001',
            'paid_date' => '2026-07-26',
            'note' => 'Pembayaran simulasi diterima penuh.',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame((float) $invoice->grand_total, (float) $invoice->paid_total);
        $this->assertSame('paid', $poRequest->fresh()->status);
        $this->actingAs($administrator)->get(route('admin.invoices.show', $invoice))->assertOk();
        $this->record('invoice_payment', 'passed', [
            'invoice_id' => $invoice->id,
            'invoice_code' => $invoice->code,
            'grand_total' => (float) $invoice->grand_total,
            'paid_total' => (float) $invoice->paid_total,
            'status' => $invoice->status,
        ]);

        $this->record('final_state', 'passed', [
            'pra_lead' => $praLead->fresh()->status,
            'lead' => $lead->fresh()->status,
            'lead_stage' => $lead->fresh()->stage,
            'design_request' => $designRequest->fresh()->status,
            'quotation' => $quotation->fresh()->status,
            'project' => $project->fresh()->status,
            'request_po' => $poRequest->fresh()->status,
            'invoice' => $invoice->fresh()->status,
        ]);
        $this->writeReport();
    }

    private function record(string $step, string $status, array $details = []): void
    {
        $this->audit[] = [
            'step' => $step,
            'status' => $status,
            'details' => $details,
            'recorded_at' => now()->toIso8601String(),
        ];
    }

    private function writeReport(): void
    {
        $directory = storage_path('app/private/audits');
        File::ensureDirectoryExists($directory);
        File::put(
            $directory.'/live-workflow-audit-20260726.json',
            json_encode([
                'generated_at' => now()->toIso8601String(),
                'environment' => app()->environment(),
                'database' => config('database.default'),
                'result' => 'passed',
                'steps' => $this->audit,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}

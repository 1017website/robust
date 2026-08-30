<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\User;
use App\Support\IndonesianRegions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class August2026CrmRevisionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sales_admin_is_no_longer_a_selectable_role(): void
    {
        $this->assertArrayNotHasKey('sales_admin', User::roles());
        $this->assertSame('Sales', User::roles()['sales']);
    }

    public function test_sales_inherits_the_former_sales_admin_capabilities(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);

        $this->assertFalse(method_exists($sales, 'isSalesAdmin'));
        $this->assertArrayNotHasKey('sales_admin', User::roles());
        $this->assertTrue($sales->canManageBackOffice());
        $this->assertTrue($sales->canManageProjectAdministration());
        // isAdminLevel tetap khusus Administrator: dipakai untuk hak lihat lintas sales.
        $this->assertFalse($sales->isAdminLevel());

        $this->actingAs($sales)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Administrator (Legacy)');

        foreach ([
            'pipeline.index',
            'admin.pra-leads.index',
            'admin.invoices.index',
            'admin.users.index',
            'administration.project-monitoring.index',
            'admin.purchase-order-requests.index',
        ] as $route) {
            $this->actingAs($sales)->get(route($route))->assertOk();
        }

        // Yang tidak ikut diwariskan ke Sales.
        $this->actingAs($sales)->get(route('admin.system-settings.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.item-masters.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.assignment.index'))->assertForbidden();

        // Role operasional lain tetap tidak mendapat kewenangan ini.
        $drafter = User::factory()->create(['role' => 'drafter']);
        $this->assertFalse($drafter->canManageBackOffice());
        $this->actingAs($drafter)->get(route('admin.pra-leads.index'))->assertForbidden();
    }

    public function test_assignment_is_limited_to_administrator_and_sales_spv(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $spv = User::factory()->create(['role' => 'sales_spv']);
        $firstSales = User::factory()->create(['role' => 'sales', 'is_active' => true]);
        $secondSales = User::factory()->create(['role' => 'sales', 'is_active' => true]);

        $lead = Lead::create([
            'code' => 'LEAD-ASSIGN-'.str()->random(4),
            'instansi' => 'Customer Uji Assignment',
            'pic_name' => 'PIC Assignment',
            'phone' => '081200001111',
            'location' => 'Surabaya',
            'city' => 'Surabaya',
            'instansi_type' => 'Industri',
            'source' => 'distributor',
            'lab_name' => 'Lab Uji Assignment',
            'priority' => 'medium',
            'sales_id' => $firstSales->id,
            'status' => 'aktif',
            'stage' => 'lead',
        ]);

        // Sales tidak lagi dapat membuka maupun memindahkan kepemilikan lead.
        $this->actingAs($firstSales)->get(route('admin.assignment.index'))->assertForbidden();
        $this->actingAs($secondSales)->post(route('admin.assignment.reassign'), [
            'lead_id' => $lead->id,
            'to_sales_id' => $secondSales->id,
        ])->assertForbidden();
        $this->assertSame($firstSales->id, $lead->fresh()->sales_id);

        // Administrator dan SPV Sales tetap dapat menjalankannya.
        foreach ([$administrator, $spv] as $supervisor) {
            $this->actingAs($supervisor)->get(route('admin.assignment.index'))->assertOk();
        }

        $this->actingAs($spv)->post(route('admin.assignment.reassign'), [
            'lead_id' => $lead->id,
            'to_sales_id' => $secondSales->id,
        ])->assertRedirect();

        $this->assertSame($secondSales->id, $lead->fresh()->sales_id);
    }

    public function test_administration_role_can_fill_project_administration_columns(): void
    {
        $administration = User::factory()->create(['role' => 'administration']);
        $sales = User::factory()->create(['role' => 'sales']);
        $project = Project::create([
            'code' => 'PRJ-ADM-ACCESS',
            'name' => 'Project Akses Administration',
            'project_manager_id' => $sales->id,
            'status' => 'ongoing',
            'total_value' => 15000000,
        ]);

        $this->actingAs($administration)->get(route('administration.project-monitoring.index'))
            ->assertOk()
            ->assertSee('name="administration_comment"', false);

        $this->actingAs($administration)->put(route('administration.project-monitoring.update', $project), [
            'administration_comment' => 'Bukti potong sudah diterima.',
            'withholding_tax_receipt_completed' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('project_workflows', [
            'project_id' => $project->id,
            'administration_comment' => 'Bukti potong sudah diterima.',
            'withholding_tax_receipt_completed' => 1,
            'administration_updated_by' => $administration->id,
        ]);
    }

    public function test_manage_user_exposes_a_delete_action_for_every_account(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $target = User::factory()->create(['role' => 'sales']);

        $this->actingAs($administrator)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.users.destroy', $target), false)
            ->assertSee('bi-trash', false);

        $this->actingAs($administrator)->delete(route('admin.users.destroy', $target))->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_external_purchase_order_creates_a_connected_non_crm_record(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $sales = User::factory()->create(['role' => 'sales']);

        $response = $this->actingAs($administrator)->post(route('admin.purchase-order-requests.store'), [
            'purchase_source' => 'external',
            'external_project_name' => 'Renovasi Lab Non-CRM',
            'external_quotation_number' => 'EXT-QUO-0088',
            'external_order_value' => 88000000,
            'external_sales_id' => $sales->id,
            'project_number' => 'PRJ-EXT-0088',
            'customer_name' => 'PT Customer Existing',
            'request_date' => now()->toDateString(),
            'customer_po_number' => 'PO-CUST-0088',
            'delivery_address' => 'Jakarta Selatan',
        ]);

        $requestPo = PurchaseOrderRequest::where('project_number', 'PRJ-EXT-0088')->firstOrFail();
        $quotation = $requestPo->quotation;

        $response->assertRedirect(route('admin.purchase-order-requests.show', $requestPo));
        $this->assertTrue($quotation->isExternal());
        $this->assertSame($sales->id, $quotation->sales_id);
        $this->assertSame('request_po_created', $quotation->status);
        $this->assertSame(88000000.0, (float) $quotation->grand_total);
        $this->assertStringContainsString('EXT-QUO-0088', (string) $quotation->internal_note);

        $this->actingAs($administrator)->put(route('admin.purchase-order-requests.update', $requestPo), [
            'status' => 'po_created',
            'accurate_po_number' => 'ACC-PO-0088',
            'accurate_po_date' => now()->toDateString(),
            'delivery_address' => 'Jakarta Selatan',
        ])->assertRedirect();

        $project = Project::where('quotation_id', $quotation->id)->firstOrFail();
        $this->assertSame('Renovasi Lab Non-CRM', $project->name);
        $this->assertSame(88000000.0, (float) $project->total_value);
    }

    public function test_request_po_required_fields_are_starred(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($administrator)->get(route('admin.purchase-order-requests.create'))
            ->assertOk()
            ->assertSee('Nomor Proyek <span class="text-danger">*</span>', false)
            ->assertSee('Nama Customer <span class="text-danger">*</span>', false)
            ->assertSee('Tanggal Request <span class="text-danger">*</span>', false)
            ->assertSee('Kolom bertanda');
    }

    public function test_request_po_checklist_items_can_be_deleted_and_added(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $sales = User::factory()->create(['role' => 'sales']);

        // Form baru menampilkan checklist bawaan lengkap dengan ikon hapus per item.
        $this->actingAs($administrator)->get(route('admin.purchase-order-requests.create'))
            ->assertOk()
            ->assertSee('Checklist Kelengkapan')
            ->assertSee('data-checklist-remove', false)
            ->assertSee('data-checklist-add', false)
            ->assertSee('Penawaran final sudah siap dikirim');

        // Simpan Request PO hanya dengan sebagian item, plus satu item buatan sendiri.
        $this->actingAs($sales)->post(route('admin.purchase-order-requests.store'), [
            'purchase_source' => 'external',
            'external_project_name' => 'Order Checklist Kustom',
            'external_order_value' => 12000000,
            'project_number' => 'PRJ-CHECKLIST-001',
            'customer_name' => 'PT Checklist Kustom',
            'request_date' => now()->toDateString(),
            'checklist_present' => 1,
            'checklist' => [
                ['key' => 'customer_po', 'label' => 'PO customer / bukti order sudah dilampirkan', 'checked' => 1],
                ['key' => '', 'label' => 'Berita acara serah terima sudah disiapkan', 'checked' => 0],
            ],
        ])->assertRedirect();

        $requestPo = PurchaseOrderRequest::where('project_number', 'PRJ-CHECKLIST-001')->firstOrFail();
        $items = $requestPo->checklistItems();

        $this->assertCount(2, $items);
        $this->assertSame(['customer_po', 'berita_acara_serah_terima_sudah_disiapkan'], array_column($items, 'key'));
        $this->assertTrue($items[0]['checked']);
        $this->assertFalse($items[1]['checked']);
        $this->assertSame(['done' => 1, 'total' => 2, 'percent' => 50, 'complete' => false], $requestPo->checklistProgress());

        // Item yang tidak diperlukan dihapus lewat form checklist tersendiri —
        // tersedia untuk setiap akun yang berhak atas Request PO ini.
        $this->actingAs($sales)->get(route('admin.purchase-order-requests.show', $requestPo))
            ->assertOk()
            ->assertSee('Checklist Kelengkapan')
            ->assertSee('data-checklist-remove', false);

        $this->actingAs($sales)->put(route('admin.purchase-order-requests.checklist', $requestPo), [
            'checklist_present' => 1,
            'checklist' => [
                ['key' => 'customer_po', 'label' => 'PO customer / bukti order sudah dilampirkan', 'checked' => 1],
            ],
        ])->assertRedirect();

        $requestPo->refresh();
        $this->assertCount(1, $requestPo->checklistItems());
        $this->assertTrue($requestPo->isChecklistComplete());
        $this->assertNotNull($requestPo->checklist_completed_at);

        // Seluruh item boleh dihapus sampai habis.
        $this->actingAs($administrator)->put(route('admin.purchase-order-requests.checklist', $requestPo), [
            'checklist_present' => 1,
        ])->assertRedirect();

        $requestPo->refresh();
        $this->assertSame([], $requestPo->checklistItems());
        $this->assertFalse($requestPo->isChecklistComplete());
        $this->assertNull($requestPo->checklist_completed_at);

        // Sales lain tidak boleh mengubah checklist Request PO milik orang.
        $otherSales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($otherSales)->put(route('admin.purchase-order-requests.checklist', $requestPo), [
            'checklist_present' => 1,
        ])->assertForbidden();
    }

    public function test_legacy_checklist_data_is_still_readable(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $requestPo = PurchaseOrderRequest::create([
            'code' => 'RPO-LEGACY-CHECKLIST',
            'project_number' => 'PRJ-LEGACY-CHK',
            'customer_name' => 'PT Data Lama',
            'requested_by' => $sales->id,
            'request_date' => today(),
            'status' => 'submitted',
            'checklist' => ['quotation_approved' => true, 'customer_po' => false],
        ]);

        $items = $requestPo->checklistItems();

        $this->assertCount(2, $items);
        $this->assertSame('Penawaran final sudah siap dikirim', $items[0]['label']);
        $this->assertTrue($items[0]['checked']);
        $this->assertFalse($items[1]['checked']);
        $this->assertSame(50, $requestPo->checklistProgress()['percent']);
    }

    public function test_request_po_can_be_saved_as_a_pending_draft_and_submitted_later(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $sales = User::factory()->create(['role' => 'sales']);

        // Draf boleh disimpan meskipun data belum lengkap.
        $this->actingAs($administrator)->post(route('admin.purchase-order-requests.store'), [
            'purchase_source' => 'external',
            'external_project_name' => 'Order Menunggu Data',
            'customer_name' => 'PT Draft Pending',
            'action' => 'draft',
        ])->assertRedirect();

        $draft = PurchaseOrderRequest::where('customer_name', 'PT Draft Pending')->firstOrFail();
        $this->assertTrue($draft->isDraft());
        $this->assertNull($draft->quotation_id);
        $this->assertNull($draft->project_number);
        $this->assertFalse($draft->canCreateInvoice());
        $this->assertArrayHasKey('draft', PurchaseOrderRequest::statuses());
        $this->assertArrayNotHasKey('draft', PurchaseOrderRequest::processStatuses());

        // Draf belum boleh diproses ke Accurate atau diekspor.
        $this->actingAs($administrator)->put(route('admin.purchase-order-requests.update', $draft), [
            'status' => 'processing_accurate',
        ])->assertForbidden();
        $this->actingAs($administrator)->get(route('admin.purchase-order-requests.pdf', $draft))->assertForbidden();

        $this->actingAs($administrator)->get(route('admin.purchase-order-requests.show', $draft))
            ->assertOk()
            ->assertSee('Draf Belum Diajukan')
            ->assertDontSee('Update Accurate');

        $this->actingAs($administrator)->get(route('admin.purchase-order-requests.edit', $draft))
            ->assertOk()
            ->assertSee('Simpan Draf (Pending)');

        // Setelah dilengkapi, draf yang sama diajukan tanpa membuat record baru.
        $this->actingAs($administrator)->put(route('admin.purchase-order-requests.draft', $draft), [
            'purchase_source' => 'external',
            'external_project_name' => 'Order Menunggu Data',
            'external_order_value' => 45000000,
            'external_sales_id' => $sales->id,
            'project_number' => 'PRJ-DRAFT-0001',
            'customer_name' => 'PT Draft Pending',
            'request_date' => now()->toDateString(),
            'action' => 'submit',
        ])->assertRedirect(route('admin.purchase-order-requests.show', $draft));

        $draft->refresh();
        $this->assertSame('submitted', $draft->status);
        $this->assertSame('PRJ-DRAFT-0001', $draft->project_number);
        $this->assertNotNull($draft->quotation_id);
        $this->assertSame(45000000.0, (float) $draft->quotation->grand_total);
        $this->assertSame(1, PurchaseOrderRequest::where('customer_name', 'PT Draft Pending')->count());

        $this->actingAs($administrator)->get(route('admin.purchase-order-requests.show', $draft))
            ->assertOk()
            ->assertSee('Update Accurate')
            ->assertDontSee('Draf Belum Diajukan');
        $this->actingAs($administrator)->get(route('admin.purchase-order-requests.index'))
            ->assertOk()
            ->assertSee('PRJ-DRAFT-0001');

        // Request PO yang sudah diajukan tidak lagi bisa diubah lewat jalur draf.
        $this->actingAs($administrator)->get(route('admin.purchase-order-requests.edit', $draft))->assertForbidden();
    }

    public function test_request_po_submission_still_requires_the_starred_fields(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($administrator)->post(route('admin.purchase-order-requests.store'), [
            'purchase_source' => 'crm',
            'action' => 'submit',
        ])->assertSessionHasErrors(['quotation_id', 'project_number', 'customer_name']);
    }

    public function test_ready_quotation_is_selectable_on_the_request_po_form(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $sales = User::factory()->create(['role' => 'sales']);

        $quotation = Quotation::create([
            'code' => 'Q-READY-RPO',
            'customer_name' => 'PT Siap Dikirim',
            'project_name' => 'Lab Siap Dikirim',
            'sales_id' => $sales->id,
            'quote_date' => now()->toDateString(),
            'currency' => 'IDR',
            'subtotal' => 20000000,
            'grand_total' => 20000000,
            'status' => 'ready',
            'created_by' => $sales->id,
        ]);

        // Status 'ready' diizinkan model, jadi harus ikut tampil pada form Request PO.
        $this->assertTrue($quotation->canCreatePurchaseOrderRequest());
        $this->actingAs($administrator)->get(route('admin.purchase-order-requests.create'))
            ->assertOk()
            ->assertSee('Q-READY-RPO');

        $this->actingAs($administrator)->post(route('admin.purchase-order-requests.store'), [
            'purchase_source' => 'crm',
            'quotation_id' => $quotation->id,
            'project_number' => 'PRJ-READY-0001',
            'customer_name' => 'PT Siap Dikirim',
            'request_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('purchase_order_requests', [
            'quotation_id' => $quotation->id,
            'status' => 'submitted',
        ]);
    }

    public function test_invoice_can_only_be_issued_after_delivery_is_completed(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $sales = User::factory()->create(['role' => 'sales']);

        $quotation = Quotation::create([
            'code' => 'Q-INVOICE-GUARD',
            'customer_name' => 'PT Penjagaan Invoice',
            'project_name' => 'Lab Penjagaan Invoice',
            'sales_id' => $sales->id,
            'quote_date' => now()->toDateString(),
            'currency' => 'IDR',
            'subtotal' => 30000000,
            'grand_total' => 30000000,
            'status' => 'sent_to_customer',
            'created_by' => $sales->id,
        ]);
        $requestPo = PurchaseOrderRequest::create([
            'code' => 'RPO-INVOICE-GUARD',
            'quotation_id' => $quotation->id,
            'customer_name' => $quotation->customer_name,
            'requested_by' => $sales->id,
            'request_date' => today(),
            'status' => 'submitted',
        ]);

        // Belum ada Project sama sekali: belum boleh ditagihkan.
        $this->assertFalse($requestPo->fresh()->canCreateInvoice());
        $this->actingAs($administrator)
            ->get(route('admin.invoices.create', ['request_po' => $requestPo->id]))
            ->assertSessionHasErrors('request_po');

        $project = Project::create([
            'code' => 'PRJ-INVOICE-GUARD',
            'quotation_id' => $quotation->id,
            'name' => 'Lab Penjagaan Invoice',
            'project_manager_id' => $sales->id,
            'status' => 'ongoing',
            'total_value' => 30000000,
        ]);
        $workflow = $project->workflow()->firstOrCreate();

        // Project ada tetapi pengiriman belum selesai: tetap belum boleh.
        $workflow->update(['delivery_status' => 'in_transit']);
        $this->assertFalse($requestPo->fresh()->canCreateInvoice());

        // Delivery selesai: baru boleh ditagihkan.
        $workflow->update(['delivery_status' => 'completed']);
        $this->assertTrue($requestPo->fresh()->canCreateInvoice());
        $this->actingAs($administrator)
            ->get(route('admin.invoices.create', ['request_po' => $requestPo->id]))
            ->assertOk();
    }

    public function test_sales_can_only_change_pra_leads_they_own(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $owner = User::factory()->create(['role' => 'sales']);
        $otherSales = User::factory()->create(['role' => 'sales']);

        $praLead = PraLead::create([
            'code' => 'PL-OWNERSHIP-'.str()->random(4),
            'instansi' => 'PT Kepemilikan Pra Lead',
            'pic_name' => 'PIC Pra Lead',
            'phone' => '081200002222',
            'source' => 'distributor',
            'status' => 'waiting_acceptance',
            'assigned_sales_id' => $owner->id,
            'created_by' => $administrator->id,
        ]);

        $payload = [
            'instansi' => 'PT Kepemilikan Pra Lead',
            'pic_name' => 'PIC Pra Lead',
            'phone' => '081200002222',
            'source' => 'distributor',
        ];

        // Sales lain tidak boleh mengubah maupun menghapus.
        $this->actingAs($otherSales)->put(route('admin.pra-leads.update', $praLead), $payload)->assertForbidden();
        $this->actingAs($otherSales)->delete(route('admin.pra-leads.destroy', $praLead))->assertForbidden();
        $this->assertDatabaseHas('pra_leads', ['id' => $praLead->id, 'deleted_at' => null]);

        // Sales yang ditugaskan dan Administrator tetap boleh.
        $this->actingAs($owner)->put(route('admin.pra-leads.update', $praLead), $payload + [
            'admin_note' => 'Diperbarui oleh sales yang ditugaskan.',
        ])->assertRedirect();
        $this->actingAs($administrator)->delete(route('admin.pra-leads.destroy', $praLead))->assertRedirect();
        $this->assertSoftDeleted('pra_leads', ['id' => $praLead->id]);
    }

    public function test_design_request_can_be_saved_as_draft_and_sent_later(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales']);
        $drafter = User::factory()->create(['role' => 'drafter']);

        $this->actingAs($sales)->get(route('sales.design-requests.create'))
            ->assertOk()
            ->assertSee('Simpan Draf')
            // Tombol bernama "action" membayangi properti form.action pada browser,
            // sehingga URL tujuan XHR harus dibaca lewat getAttribute.
            ->assertSee("designRequestForm.getAttribute('action')", false)
            ->assertDontSee("xhr.open('POST', designRequestForm.action", false);

        $this->actingAs($sales)->post(route('sales.design-requests.store'), [
            'customer_name' => 'Customer Draf Design',
            'project_name' => 'Project Draf Design',
            'request_date' => now()->toDateString(),
            'deadline' => now()->addDays(5)->toDateString(),
            'priority' => 'normal',
            'short_description' => 'Menunggu sketsa dari customer.',
            'detail_need' => 'Detail menyusul setelah survey lokasi.',
            'production_pic_id' => $drafter->id,
            'action' => 'save',
        ])->assertRedirect(route('sales.design-requests.index'));

        $designRequest = DesignRequest::where('project_name', 'Project Draf Design')->firstOrFail();
        $this->assertSame('draft', $designRequest->status);

        $this->actingAs($sales)->get(route('sales.design-requests.edit', $designRequest))
            ->assertOk()
            ->assertSee('Lanjutkan Draf')
            ->assertSee('Customer Draf Design');

        $this->actingAs($sales)->put(route('sales.design-requests.update', $designRequest), [
            'customer_name' => 'Customer Draf Design',
            'project_name' => 'Project Draf Design',
            'request_date' => now()->toDateString(),
            'deadline' => now()->addDays(5)->toDateString(),
            'priority' => 'urgent',
            'short_description' => 'Sketsa sudah lengkap.',
            'detail_need' => 'Wall bench dan fume hood sesuai hasil survey.',
            'production_pic_id' => $drafter->id,
            'action' => 'send',
        ])->assertRedirect(route('sales.design-requests.index'));

        $designRequest->refresh();
        $this->assertSame('assigned', $designRequest->status);
        $this->assertSame('urgent', $designRequest->priority);
        $this->assertSame(1, DesignRequest::where('project_name', 'Project Draf Design')->count());

        // Sudah dikirim: jalur draf ditutup.
        $this->actingAs($sales)->get(route('sales.design-requests.edit', $designRequest))->assertForbidden();

        // Draf milik sales lain tidak dapat dibuka.
        $otherSales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($otherSales)->get(route('sales.design-requests.edit', $designRequest))->assertForbidden();
    }

    public function test_lead_accepts_a_manually_typed_city_and_only_revised_sources(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);

        $this->assertSame([
            'distributor' => 'Distributor',
            'supplier' => 'Supplier',
            'loops_lab_nusantara' => 'Loops LabNusantara',
            'robust_multilab_solusindo' => 'Robust Multilab Solusindo',
            'robust_indonesia_sinar_lab' => 'Robust Indonesia - Sinar Lab',
            'mec' => 'MEC',
        ], PraLead::sources());

        $this->actingAs($sales)->post(route('sales.leads.store'), [
            'instansi' => 'Customer Kota Manual',
            'pic_name' => 'PIC Customer',
            'phone' => '081234567890',
            'location' => 'Kabupaten yang belum ada pada saran',
            'city' => 'Kabupaten Bolaang Mongondow Timur',
            'instansi_type' => 'Industri',
            'source' => 'supplier',
            'lab_name' => 'Lab Pengujian',
            'priority' => 'medium',
        ])->assertRedirect(route('sales.leads.create'));

        $this->assertDatabaseHas('leads', [
            'instansi' => 'Customer Kota Manual',
            'city' => 'Kabupaten Bolaang Mongondow Timur',
            'source' => 'supplier',
        ]);

        $this->actingAs($sales)->post(route('sales.leads.store'), [
            'instansi' => 'Sumber Lama Ditolak',
            'pic_name' => 'PIC Customer',
            'phone' => '081234567890',
            'location' => 'Jakarta',
            'city' => 'Jakarta',
            'instansi_type' => 'Industri',
            'source' => 'website',
            'lab_name' => 'Lab Pengujian',
            'priority' => 'medium',
        ])->assertSessionHasErrors('source');
    }

    public function test_city_suggestions_cover_every_indonesian_city_and_regency(): void
    {
        $cities = IndonesianRegions::cities();

        $this->assertCount(38, IndonesianRegions::provinces());
        $this->assertCount(514, $cities);
        $this->assertSame('Jawa Barat', $cities['Bandung']);
        $this->assertSame('Jawa Barat', $cities['Kabupaten Bandung']);
        $this->assertSame('Papua Pegunungan', $cities['Kabupaten Jayawijaya']);

        $sales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($sales)->get(route('sales.leads.create'))
            ->assertOk()
            ->assertSee('<option value="Sorong" label="Papua Barat Daya">', false)
            ->assertSee('<option value="Kabupaten Sumba Tengah" label="Nusa Tenggara Timur">', false)
            ->assertSee('<option value="Balikpapan" label="Kalimantan Timur">', false);

        $lead = Lead::create([
            'code' => 'LEAD-CITY-'.str()->random(4),
            'instansi' => 'Lead Saran Kota',
            'pic_name' => 'PIC Saran Kota',
            'phone' => '081234500000',
            'location' => 'Balikpapan',
            'city' => 'Balikpapan',
            'instansi_type' => 'Industri',
            'source' => 'distributor',
            'lab_name' => 'Lab Saran Kota',
            'priority' => 'medium',
            'sales_id' => $sales->id,
            'status' => 'aktif',
            'stage' => 'lead',
        ]);

        $this->actingAs($sales)->get(route('sales.leads.edit', $lead))
            ->assertOk()
            ->assertSee('<option value="Kabupaten Bandung" label="Jawa Barat">', false);
        $this->actingAs($sales)->get(route('sales.customers.create'))
            ->assertOk()
            ->assertSee('<option value="Kabupaten Bandung" label="Jawa Barat">', false);
    }

    public function test_lead_instansi_type_includes_bumn_and_bumd(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);

        $this->assertContains('BUMN', Lead::instansiTypes());
        $this->assertContains('BUMD', Lead::instansiTypes());
        $this->assertContains('BUMN', Customer::categories());
        $this->assertContains('BUMD', Customer::categories());

        $this->actingAs($sales)->get(route('sales.leads.create'))
            ->assertOk()
            ->assertSee('>BUMN<', false)
            ->assertSee('>BUMD<', false);

        $this->actingAs($sales)->post(route('sales.leads.store'), [
            'instansi' => 'Perusahaan Daerah Air Minum',
            'pic_name' => 'PIC BUMD',
            'phone' => '081234567891',
            'location' => 'Surabaya',
            'city' => 'Surabaya',
            'instansi_type' => 'BUMD',
            'source' => 'distributor',
            'lab_name' => 'Lab Kualitas Air',
            'priority' => 'medium',
        ])->assertRedirect(route('sales.leads.create'));

        $this->assertDatabaseHas('leads', [
            'instansi' => 'Perusahaan Daerah Air Minum',
            'instansi_type' => 'BUMD',
        ]);
    }

    public function test_design_request_number_can_be_filled_manually(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales']);
        $drafter = User::factory()->create(['role' => 'drafter']);

        $payload = fn (array $override = []) => array_merge([
            'customer_name' => 'Customer Nomor Manual',
            'project_name' => 'Project Nomor Manual',
            'request_date' => now()->toDateString(),
            'deadline' => now()->addDay()->toDateString(),
            'priority' => 'normal',
            'short_description' => 'Pengujian penomoran manual Design Request.',
            'detail_need' => 'Membutuhkan desain laboratorium dengan nomor internal sendiri.',
            'production_pic_id' => $drafter->id,
            'action' => 'save',
        ], $override);

        $this->actingAs($sales)->get(route('sales.design-requests.create'))
            ->assertOk()
            ->assertSee('Nomor Design Request')
            ->assertSee('name="code"', false);

        $this->actingAs($sales)->post(route('sales.design-requests.store'), $payload([
            'code' => 'RBS/DR/VIII/2026-014',
        ]))->assertRedirect(route('sales.design-requests.index'));

        $this->assertDatabaseHas('design_requests', [
            'code' => 'RBS/DR/VIII/2026-014',
            'project_name' => 'Project Nomor Manual',
        ]);

        // Nomor yang sama tidak boleh dipakai dua kali.
        $this->actingAs($sales)->post(route('sales.design-requests.store'), $payload([
            'code' => 'RBS/DR/VIII/2026-014',
            'project_name' => 'Project Nomor Duplikat',
        ]))->assertSessionHasErrors('code');

        // Dikosongkan tetap mendapat nomor otomatis.
        $this->actingAs($sales)->post(route('sales.design-requests.store'), $payload([
            'project_name' => 'Project Nomor Otomatis',
        ]))->assertRedirect(route('sales.design-requests.index'));

        $auto = DesignRequest::where('project_name', 'Project Nomor Otomatis')->firstOrFail();
        $this->assertMatchesRegularExpression('/^DR-\d{3,}$/', $auto->code);
    }

    public function test_automatic_design_request_number_skips_codes_already_taken_manually(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales']);
        $drafter = User::factory()->create(['role' => 'drafter']);

        // Nomor otomatis berikutnya dipakai lebih dulu secara manual.
        $reserved = 'DR-'.str_pad((string) (DesignRequest::withTrashed()->count() + 1), 3, '0', STR_PAD_LEFT);

        $payload = fn (array $override) => array_merge([
            'customer_name' => 'Customer Tabrakan Nomor',
            'request_date' => now()->toDateString(),
            'deadline' => now()->addDay()->toDateString(),
            'priority' => 'normal',
            'short_description' => 'Pengujian tabrakan penomoran otomatis.',
            'detail_need' => 'Nomor manual dan otomatis tidak boleh bertabrakan.',
            'production_pic_id' => $drafter->id,
            'action' => 'save',
        ], $override);

        $this->actingAs($sales)->post(route('sales.design-requests.store'), $payload([
            'code' => $reserved,
            'project_name' => 'Project Nomor Direbut',
        ]))->assertRedirect(route('sales.design-requests.index'));

        $this->actingAs($sales)->post(route('sales.design-requests.store'), $payload([
            'project_name' => 'Project Nomor Otomatis Setelahnya',
        ]))->assertRedirect(route('sales.design-requests.index'));

        $auto = DesignRequest::where('project_name', 'Project Nomor Otomatis Setelahnya')->firstOrFail();
        $this->assertNotSame($reserved, $auto->code);
        $this->assertSame(1, DesignRequest::withTrashed()->where('code', $auto->code)->count());
    }

    public function test_design_request_accepts_an_eighty_megabyte_attachment(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales']);
        $drafter = User::factory()->create(['role' => 'drafter']);

        $this->actingAs($sales)->post(route('sales.design-requests.store'), [
            'customer_name' => 'Customer Lampiran Besar',
            'project_name' => 'Project Lampiran 80 MB',
            'request_date' => now()->toDateString(),
            'deadline' => now()->addDay()->toDateString(),
            'priority' => 'normal',
            'short_description' => 'Pengujian batas lampiran Design Request.',
            'detail_need' => 'Membutuhkan desain laboratorium dan lampiran referensi besar.',
            'production_pic_id' => $drafter->id,
            'attachments' => [UploadedFile::fake()->create('referensi-80mb.pdf', 81920, 'application/pdf')],
            'action' => 'save',
        ])->assertRedirect(route('sales.design-requests.index'));

        $designRequest = DesignRequest::where('project_name', 'Project Lampiran 80 MB')->firstOrFail();
        $this->assertDatabaseHas('documents', [
            'documentable_type' => DesignRequest::class,
            'documentable_id' => $designRequest->id,
            'category' => 'sales_sketch',
        ]);
    }

    public function test_design_request_upload_has_progress_ui_and_returns_json_for_xhr(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales']);
        $drafter = User::factory()->create(['role' => 'drafter']);

        $this->actingAs($sales)
            ->get(route('sales.design-requests.create'))
            ->assertOk()
            ->assertSee('id="designUploadProgress"', false)
            ->assertSee('id="designUploadPercent"', false)
            ->assertSee("xhr.upload.addEventListener('progress'", false);

        $response = $this->actingAs($sales)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('sales.design-requests.store'), [
                'customer_name' => 'Customer Upload XHR',
                'project_name' => 'Project Upload XHR',
                'request_date' => now()->toDateString(),
                'deadline' => now()->addDay()->toDateString(),
                'priority' => 'normal',
                'short_description' => 'Pengujian response upload berbasis XHR.',
                'detail_need' => 'Membutuhkan desain laboratorium dengan indikator progres upload.',
                'production_pic_id' => $drafter->id,
                'attachments' => [UploadedFile::fake()->create('referensi-xhr.pdf', 100, 'application/pdf')],
                'action' => 'send',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('redirect', route('sales.design-requests.index'));

        $this->assertDatabaseHas('design_requests', [
            'project_name' => 'Project Upload XHR',
            'status' => 'assigned',
        ]);
    }

    public function test_quotation_sourced_request_po_draft_reserves_the_quotation_without_locking_its_status(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = Quotation::create([
            'code' => 'Q-DRAFT-RESERVE',
            'customer_name' => 'PT Reserve Draft',
            'project_name' => 'Lab Reserve Draft',
            'sales_id' => $sales->id,
            'quote_date' => now()->toDateString(),
            'currency' => 'IDR',
            'subtotal' => 10000000,
            'grand_total' => 10000000,
            'status' => 'sent_to_customer',
            'created_by' => $sales->id,
        ]);

        $this->actingAs($administrator)->post(route('admin.purchase-order-requests.store'), [
            'purchase_source' => 'crm',
            'quotation_id' => $quotation->id,
            'customer_name' => 'PT Reserve Draft',
            'action' => 'draft',
        ])->assertRedirect();

        $draft = PurchaseOrderRequest::where('quotation_id', $quotation->id)->firstOrFail();
        $this->assertTrue($draft->isDraft());
        $this->assertSame('sent_to_customer', $quotation->fresh()->status);

        $this->actingAs($administrator)->put(route('admin.purchase-order-requests.draft', $draft), [
            'purchase_source' => 'crm',
            'quotation_id' => $quotation->id,
            'project_number' => 'PRJ-RESERVE-0001',
            'customer_name' => 'PT Reserve Draft',
            'request_date' => now()->toDateString(),
            'action' => 'submit',
        ])->assertRedirect();

        $this->assertSame('submitted', $draft->fresh()->status);
        $this->assertSame('request_po_created', $quotation->fresh()->status);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Project;
use App\Models\PraLead;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrmFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_primary_menus_render_for_every_role(): void
    {
        $menus = [
            'administrator' => [
                'dashboard', 'pipeline.index', 'admin.pra-leads.index', 'admin.assignment.index',
                'sales.request-masuk.index', 'sales.leads.index', 'sales.design-requests.index',
                'sales.quotations.index', 'spv.quotation-approvals.index',
                'admin.purchase-order-requests.index', 'sales.customers.index', 'sales.projects.index',
                'activities.index', 'calendar.index', 'documents.index', 'reports.index',
                'admin.users.index', 'admin.system-settings.index',
            ],
            'sales_admin' => [
                'dashboard', 'pipeline.index', 'admin.pra-leads.index', 'admin.assignment.index',
                'admin.purchase-order-requests.index', 'sales.customers.index', 'activities.index',
                'calendar.index', 'reports.index', 'admin.users.index',
            ],
            'sales' => [
                'dashboard', 'sales.request-masuk.index', 'sales.leads.index',
                'sales.design-requests.index', 'sales.quotations.index', 'sales.customers.index',
                'sales.projects.index', 'activities.index', 'calendar.index', 'reports.index', 'profile.edit',
            ],
            'sales_spv' => [
                'dashboard', 'spv.quotation-approvals.index', 'activities.index',
                'calendar.index', 'reports.index', 'sales.customers.index',
            ],
            'drafter' => [
                'dashboard', 'drafter.design-requests.index', 'drafter.projects.index',
                'drafter.tasks.index', 'documents.index', 'drafter.calendar.index',
                'drafter.reports.index', 'profile.edit',
            ],
        ];

        foreach ($menus as $role => $routes) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            foreach ($routes as $routeName) {
                $this->actingAs($user)->get(route($routeName))->assertSuccessful();
            }
        }
    }

    public function test_breadcrumb_and_back_button_render_on_list_create_and_detail_pages(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $customer = Customer::create([
            'name' => 'Customer Breadcrumb Test',
            'pipeline_stage' => 'identify',
            'sales_id' => $sales->id,
        ]);

        $this->actingAs($sales)->get(route('dashboard'))
            ->assertSuccessful()
            ->assertDontSee('class="context-nav"', false);

        $this->actingAs($sales)->get(route('sales.customers.index'))
            ->assertSuccessful()
            ->assertSee('class="context-nav"', false)
            ->assertSee('Kembali')
            ->assertSee('Dashboard')
            ->assertSee('Customers');

        $this->actingAs($sales)->get(route('sales.customers.create'))
            ->assertSuccessful()
            ->assertSee('Tambah Customers')
            ->assertSee(route('sales.customers.index'), false);

        $this->actingAs($sales)->get(route('sales.customers.show', $customer))
            ->assertSuccessful()
            ->assertSee('Detail Customers')
            ->assertSee(route('sales.customers.index'), false);
    }

    public function test_calendar_inputs_are_safe_and_drafter_only_sees_assigned_work(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $firstDrafter = User::factory()->create(['role' => 'drafter']);
        $secondDrafter = User::factory()->create(['role' => 'drafter']);

        DesignRequest::create([
            'code' => 'DR-CALENDAR-OWN',
            'customer_name' => 'Customer Calendar Own',
            'project_name' => 'Project Calendar Milik Sendiri',
            'sales_id' => $sales->id,
            'production_pic_id' => $firstDrafter->id,
            'request_date' => now(),
            'deadline' => now()->endOfMonth(),
            'status' => 'assigned',
        ]);
        DesignRequest::create([
            'code' => 'DR-CALENDAR-OTHER',
            'customer_name' => 'Customer Calendar Other',
            'project_name' => 'Project Calendar Milik Drafter Lain',
            'sales_id' => $sales->id,
            'production_pic_id' => $secondDrafter->id,
            'request_date' => now(),
            'deadline' => now()->endOfMonth(),
            'status' => 'assigned',
        ]);

        $this->actingAs($firstDrafter)
            ->get(route('drafter.calendar.index', ['month' => now()->month, 'year' => now()->year]))
            ->assertSuccessful()
            ->assertSee('Project Calendar Milik Sendiri')
            ->assertDontSee('Project Calendar Milik Drafter Lain')
            ->assertSee('calendar-day-dot', false);

        $this->actingAs($sales)
            ->get(route('calendar.index', ['month' => 99, 'year' => 0]))
            ->assertSuccessful()
            ->assertSee('calendar-day-dot', false);

        $this->actingAs($sales)
            ->get(route('activities.index', ['cal_month' => -4, 'cal_year' => 9999]))
            ->assertSuccessful();
    }

    public function test_remaining_administration_and_profile_forms_submit_cleanly(): void
    {
        Storage::fake('public');
        $administrator = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($administrator)->post(route('admin.users.store'), [
            'name' => 'Sales Form Test',
            'email' => 'sales-form-test@example.test',
            'role' => 'sales',
            'job_title' => 'Sales Engineer',
            'phone' => '081234567899',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $sales = User::where('email', 'sales-form-test@example.test')->firstOrFail();
        $this->actingAs($administrator)->put(route('admin.users.update', $sales), [
            'name' => 'Sales Form Updated',
            'email' => $sales->email,
            'role' => 'sales',
            'job_title' => 'Senior Sales Engineer',
            'phone' => '081234567899',
            'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Sales Form Updated', $sales->fresh()->name);

        $this->actingAs($administrator)->put(route('admin.users.toggle', $sales))->assertSessionHasNoErrors();
        $this->assertFalse($sales->fresh()->is_active);

        $this->actingAs($administrator)->put(route('profile.update'), [
            'name' => 'Administrator Form Updated',
            'email' => $administrator->email,
            'phone' => '081200001234',
            'job_title' => 'Administrator',
        ])->assertSessionHasNoErrors();

        $this->actingAs($administrator)->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('password-baru', $administrator->fresh()->password));

        $this->actingAs($administrator)->put(route('admin.system-settings.branding'), [
            'company_name' => 'ROBUST Test',
            'company_tagline' => 'Transactional form test',
            'sales_monthly_target' => 250000000,
        ])->assertSessionHasNoErrors();
        $this->assertSame('ROBUST Test', SystemSetting::value('company_name'));
    }

    public function test_remaining_request_assignment_and_quotation_actions_submit_cleanly(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $firstSales = User::factory()->create(['role' => 'sales', 'is_active' => true]);
        $secondSales = User::factory()->create(['role' => 'sales', 'is_active' => true]);
        $spv = User::factory()->create(['role' => 'sales_spv']);

        $praLead = PraLead::create([
            'code' => 'PRA-FORM-TEST',
            'instansi' => 'Pra Lead Update Test',
            'pic_name' => 'PIC Form Test',
            'source' => 'website',
            'priority' => 'medium',
            'status' => 'draft',
            'created_by' => $administrator->id,
        ]);
        $this->actingAs($administrator)->put(route('admin.pra-leads.update', $praLead), [
            'instansi' => 'Pra Lead Update Test',
            'pic_name' => 'PIC Form Updated',
            'phone' => '081211112222',
            'source' => 'website',
            'initial_need' => 'Pengujian update dan reject.',
            'priority' => 'high',
            'assigned_sales_id' => $firstSales->id,
            'action' => 'send',
        ])->assertSessionHasNoErrors();

        $this->actingAs($firstSales)->post(route('sales.request-masuk.reject', $praLead->fresh()), [
            'reject_reason' => 'Wilayah tidak sesuai pembagian sales.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $praLead->fresh()->status);

        $lead = Lead::create([
            'code' => 'LD-ASSIGN-FORM',
            'instansi' => 'Lead Reassign Form Test',
            'pic_name' => 'PIC Assignment',
            'phone' => '081299998888',
            'location' => 'Surabaya',
            'city' => 'Surabaya',
            'instansi_type' => 'Industri',
            'source' => 'website',
            'lab_name' => 'Lab Assignment',
            'priority' => 'medium',
            'stage' => 'lead',
            'status' => 'aktif',
            'sales_id' => $firstSales->id,
            'created_by' => $administrator->id,
        ]);
        $this->actingAs($administrator)->post(route('admin.assignment.reassign'), [
            'lead_id' => $lead->id,
            'to_sales_id' => $secondSales->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame($secondSales->id, $lead->fresh()->sales_id);

        $revisionQuote = Quotation::create([
            'code' => 'Q-REVISION-TEST',
            'customer_name' => 'Customer Revision',
            'project_name' => 'Project Revision',
            'status' => 'waiting_approval',
            'sales_id' => $firstSales->id,
            'grand_total' => 1000000,
        ]);
        $this->actingAs($spv)->post(route('spv.quotation-approvals.revision', $revisionQuote), [
            'revision_note' => 'Mohon perbaiki margin.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('revision', $revisionQuote->fresh()->status);

        $this->actingAs($spv)->post(route('spv.quotation-approvals.reject', $revisionQuote->fresh()), [
            'rejection_note' => 'Nilai belum dapat disetujui.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $revisionQuote->fresh()->status);

        $lostQuote = Quotation::create([
            'code' => 'Q-LOST-TEST',
            'customer_name' => 'Customer Lost',
            'project_name' => 'Project Lost',
            'status' => 'approved',
            'sales_id' => $firstSales->id,
            'grand_total' => 2000000,
        ]);
        $this->actingAs($firstSales)->post(route('sales.quotations.lost', $lostQuote), [
            'note' => 'Customer memilih vendor lain.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('customer_rejected', $lostQuote->fresh()->status);
    }

    public function test_sales_can_create_update_and_soft_delete_core_records(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);

        $this->actingAs($sales)->post(route('sales.customers.store'), [
            'name' => 'Customer Test Flow',
            'category' => 'Industri',
            'email' => 'flow.customer@example.test',
            'phone' => '081234567890',
            'pipeline_stage' => 'identify',
            'probability' => 10,
            'pic_name' => 'PIC Test',
        ])->assertRedirect();

        $customer = Customer::where('name', 'Customer Test Flow')->firstOrFail();
        $this->assertSame($sales->id, $customer->sales_id);
        $this->assertSame('PIC Test', $customer->primaryPic?->name);

        $this->actingAs($sales)->put(route('sales.customers.update', $customer), [
            'name' => 'Customer Test Updated',
            'category' => 'Industri',
            'email' => 'flow.customer@example.test',
            'phone' => '081234567890',
            'pipeline_stage' => 'approaching',
            'probability' => 35,
            'pic_name' => 'PIC Updated',
        ])->assertRedirect(route('sales.customers.show', $customer));

        $this->assertSame('PIC Updated', $customer->fresh()->primaryPic?->name);

        $this->actingAs($sales)->post(route('activities.store'), [
            'customer_id' => $customer->id,
            'type' => 'call',
            'title' => 'Follow up test',
            'activity_date' => now()->format('Y-m-d'),
            'status' => 'scheduled',
        ])->assertRedirect(route('activities.index'));

        $activity = Activity::where('title', 'Follow up test')->firstOrFail();
        $this->actingAs($sales)->put(route('activities.status', $activity), [
            'status' => 'completed',
            'result' => 'Selesai diuji',
        ])->assertRedirect();
        $this->assertSame('completed', $activity->fresh()->status);

        $this->actingAs($sales)->post(route('sales.leads.store'), [
            'instansi' => 'Lead Test Flow',
            'pic_name' => 'PIC Lead',
            'phone' => '081200001111',
            'location' => 'Surabaya',
            'city' => 'Surabaya',
            'instansi_type' => 'Industri',
            'source' => 'website',
            'lab_name' => 'Lab Test',
            'priority' => 'medium',
        ])->assertRedirect();

        $lead = Lead::where('instansi', 'Lead Test Flow')->firstOrFail();
        $this->actingAs($sales)->delete(route('sales.leads.destroy', $lead))->assertRedirect(route('sales.leads.index'));
        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }

    public function test_user_and_document_deletes_are_recoverable(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'administrator']);
        $target = User::factory()->create(['role' => 'sales']);
        $customer = Customer::create([
            'name' => 'Document Customer',
            'pipeline_stage' => 'identify',
            'sales_id' => $target->id,
        ]);

        $this->actingAs($admin)->post(route('documents.store'), [
            'documentable_type' => Customer::class,
            'documentable_id' => $customer->id,
            'name' => 'Dokumen Test',
            'category' => 'lainnya',
            'file' => UploadedFile::fake()->create('dokumen.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $document = Document::where('name', 'Dokumen Test')->firstOrFail();
        Storage::disk('public')->assertExists($document->file_path);

        $this->actingAs($admin)->delete(route('documents.destroy', $document))->assertRedirect();
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
        Storage::disk('public')->assertExists($document->file_path);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $target))->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_drafter_cannot_change_another_drafters_assignment(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $assigned = User::factory()->create(['role' => 'drafter']);
        $other = User::factory()->create(['role' => 'drafter']);
        $request = DesignRequest::create([
            'code' => 'DR-TEST-'.str()->random(6),
            'customer_name' => 'Customer Drafter Test',
            'project_name' => 'Project Drafter Test',
            'sales_id' => $sales->id,
            'production_pic_id' => $assigned->id,
            'status' => 'assigned',
            'progress' => 0,
        ]);

        $this->actingAs($other)->put(route('drafter.design-requests.progress', $request), [
            'status' => 'drafting',
            'progress' => 25,
        ])->assertForbidden();

        $this->actingAs($assigned)->put(route('drafter.design-requests.progress', $request), [
            'status' => 'drafting',
            'progress' => 25,
        ])->assertRedirect();

        $this->assertSame(25, $request->fresh()->progress);
    }

    public function test_drafter_document_list_is_scoped_to_its_assignments(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $firstDrafter = User::factory()->create(['role' => 'drafter']);
        $secondDrafter = User::factory()->create(['role' => 'drafter']);

        $firstRequest = DesignRequest::create([
            'code' => 'DR-DOC-FIRST-'.str()->random(4),
            'customer_name' => 'Customer First',
            'project_name' => 'Project First',
            'sales_id' => $sales->id,
            'production_pic_id' => $firstDrafter->id,
            'status' => 'assigned',
        ]);
        $secondRequest = DesignRequest::create([
            'code' => 'DR-DOC-SECOND-'.str()->random(4),
            'customer_name' => 'Customer Second',
            'project_name' => 'Project Second',
            'sales_id' => $sales->id,
            'production_pic_id' => $secondDrafter->id,
            'status' => 'assigned',
        ]);

        Document::create([
            'documentable_type' => DesignRequest::class,
            'documentable_id' => $firstRequest->id,
            'name' => 'Dokumen Milik Drafter Pertama',
            'file_path' => 'documents/first.pdf',
            'uploaded_by' => $firstDrafter->id,
        ]);
        Document::create([
            'documentable_type' => DesignRequest::class,
            'documentable_id' => $secondRequest->id,
            'name' => 'Dokumen Milik Drafter Kedua',
            'file_path' => 'documents/second.pdf',
            'uploaded_by' => $secondDrafter->id,
        ]);

        $this->actingAs($firstDrafter)
            ->get(route('documents.index'))
            ->assertSuccessful()
            ->assertSee('Dokumen Milik Drafter Pertama')
            ->assertDontSee('Dokumen Milik Drafter Kedua');
    }

    public function test_assignment_export_returns_real_csv(): void
    {
        $admin = User::factory()->create(['role' => 'sales_admin']);
        $sales = User::factory()->create(['role' => 'sales', 'name' => 'Sales Export Test', 'is_active' => true]);
        Lead::create([
            'code' => 'LEAD-EXPORT-'.str()->random(4),
            'instansi' => 'Lead Export Test',
            'pic_name' => 'PIC Export Test',
            'phone' => '081200009999',
            'location' => 'Surabaya',
            'city' => 'Surabaya',
            'instansi_type' => 'Industri',
            'source' => 'website',
            'lab_name' => 'Lab Export Test',
            'priority' => 'medium',
            'sales_id' => $sales->id,
            'status' => 'qualified',
            'stage' => 'lead',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.assignment.index', ['export' => 'csv']));

        $response->assertSuccessful();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('Sales Export Test', $response->streamedContent());
    }

    public function test_sales_document_and_search_results_respect_ownership(): void
    {
        $firstSales = User::factory()->create(['role' => 'sales']);
        $secondSales = User::factory()->create(['role' => 'sales']);
        $firstCustomer = Customer::create(['name' => 'Customer Dokumen Pertama', 'pipeline_stage' => 'identify', 'sales_id' => $firstSales->id]);
        $secondCustomer = Customer::create(['name' => 'Customer Dokumen Kedua', 'pipeline_stage' => 'identify', 'sales_id' => $secondSales->id]);

        Document::create([
            'documentable_type' => Customer::class,
            'documentable_id' => $firstCustomer->id,
            'name' => 'Ownership File Pertama',
            'file_path' => 'documents/ownership-first.pdf',
            'uploaded_by' => $firstSales->id,
        ]);
        Document::create([
            'documentable_type' => Customer::class,
            'documentable_id' => $secondCustomer->id,
            'name' => 'Ownership File Kedua',
            'file_path' => 'documents/ownership-second.pdf',
            'uploaded_by' => $secondSales->id,
        ]);

        $this->actingAs($firstSales)->get(route('documents.index'))
            ->assertSuccessful()
            ->assertSee('Ownership File Pertama')
            ->assertDontSee('Ownership File Kedua');

        $this->actingAs($firstSales)->get(route('global-search.index', ['q' => 'Ownership File']))
            ->assertSuccessful()
            ->assertSee('Ownership File Pertama')
            ->assertDontSee('Ownership File Kedua');
    }

    public function test_sales_admin_cannot_manage_administrator_accounts(): void
    {
        $salesAdmin = User::factory()->create(['role' => 'sales_admin']);
        $administrator = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($salesAdmin)->get(route('admin.users.index'))
            ->assertSuccessful()
            ->assertDontSee($administrator->email);

        $this->actingAs($salesAdmin)->put(route('admin.users.update', $administrator), [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'role' => 'sales_admin',
            'is_active' => 1,
        ])->assertForbidden();

        $this->actingAs($salesAdmin)->post(route('admin.users.store'), [
            'name' => 'Administrator Tidak Sah',
            'email' => 'invalid-admin-'.str()->random(5).'@example.test',
            'role' => 'administrator',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_active' => 1,
        ])->assertSessionHasErrors('role');
    }

    public function test_design_quotation_project_and_purchase_order_flow(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $drafter = User::factory()->create(['role' => 'drafter']);
        $spv = User::factory()->create(['role' => 'sales_spv']);
        $admin = User::factory()->create(['role' => 'sales_admin']);
        $customer = Customer::create([
            'name' => 'Customer End To End',
            'pipeline_stage' => 'follow_up',
            'sales_id' => $sales->id,
        ]);

        $this->actingAs($sales)->post(route('sales.design-requests.store'), [
            'customer_name' => $customer->name,
            'pic_name' => 'PIC End To End',
            'project_name' => 'Lab End To End',
            'request_date' => now()->format('Y-m-d'),
            'deadline' => now()->addDays(7)->format('Y-m-d'),
            'priority' => 'high',
            'short_description' => 'Design laboratorium untuk pengujian alur.',
            'detail_need' => 'Layout, BOQ, dan estimasi biaya.',
            'production_pic_id' => $drafter->id,
            'action' => 'send',
        ])->assertRedirect(route('sales.design-requests.index'));

        $designRequest = DesignRequest::where('project_name', 'Lab End To End')->firstOrFail();
        $this->actingAs($drafter)->post(route('drafter.design-requests.feedback', $designRequest), [
            'cost_material' => 1000000,
            'cost_production' => 500000,
            'cost_installation' => 250000,
            'technical_note' => 'Sudah direview.',
            'items' => [[
                'category' => 'Furniture',
                'name' => 'Meja Lab',
                'qty' => 2,
                'unit' => 'Unit',
                'unit_price' => 1500000,
                'margin' => 20,
            ]],
            'action' => 'submit',
        ])->assertRedirect(route('drafter.design-requests.index'));
        $this->assertSame('completed', $designRequest->fresh()->status);

        $quoteData = [
            'design_request_id' => $designRequest->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'pic_name' => 'PIC End To End',
            'project_name' => 'Lab End To End',
            'delivery_method' => 'email',
            'quote_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(30)->format('Y-m-d'),
            'priority' => 'high',
            'currency' => 'IDR',
            'discount_type' => 'percent',
            'discount_value' => 5,
            'tax_percent' => 11,
            'target_margin' => 20,
            'items' => [[
                'category' => 'Furniture',
                'name' => 'Meja Lab',
                'qty' => 2,
                'unit' => 'Unit',
                'unit_price' => 2000000,
                'margin' => 20,
            ]],
        ];

        $this->actingAs($sales)->post(route('sales.quotations.store'), $quoteData + ['action' => 'draft'])->assertRedirect();
        $quotation = Quotation::where('project_name', 'Lab End To End')->latest()->firstOrFail();
        $this->assertSame('draft', $quotation->status);

        $this->actingAs($sales)->put(route('sales.quotations.update', $quotation), $quoteData + ['action' => 'submit_approval'])->assertRedirect();
        $this->assertSame('waiting_approval', $quotation->fresh()->status);

        $this->actingAs($spv)->post(route('spv.quotation-approvals.approve', $quotation), [
            'approval_note' => 'Disetujui dari automated flow test.',
        ])->assertRedirect(route('spv.quotation-approvals.show', $quotation));

        $this->actingAs($sales)->post(route('sales.quotations.sent-to-customer', $quotation))->assertRedirect();
        $this->actingAs($sales)->post(route('sales.quotations.won', $quotation), ['note' => 'Customer setuju'])->assertRedirect();
        $this->assertSame('customer_accepted', $quotation->fresh()->status);

        $this->actingAs($sales)->post(route('sales.projects.store'), [
            'quotation_id' => $quotation->id,
            'name' => 'Project End To End',
            'priority' => 'high',
            'status' => 'planning',
            'start_date' => now()->format('Y-m-d'),
            'target_date' => now()->addMonth()->format('Y-m-d'),
            'project_manager_id' => $sales->id,
            'internal_team' => [$drafter->id],
        ])->assertRedirect();
        $this->assertDatabaseHas('projects', ['name' => 'Project End To End', 'quotation_id' => $quotation->id]);

        $this->actingAs($admin)->post(route('admin.purchase-order-requests.store'), [
            'quotation_id' => $quotation->id,
            'request_date' => now()->format('Y-m-d'),
            'customer_po_number' => 'PO-CUSTOMER-TEST',
            'checklist' => [
                'quotation_approved' => 1,
                'customer_po' => 1,
                'customer_data' => 1,
            ],
        ])->assertRedirect();

        $poRequest = PurchaseOrderRequest::where('quotation_id', $quotation->id)->firstOrFail();
        $this->actingAs($admin)->put(route('admin.purchase-order-requests.update', $poRequest), [
            'status' => 'po_created',
            'accurate_po_number' => 'ACC-PO-TEST',
            'accurate_po_date' => now()->format('Y-m-d'),
            'checklist' => collect(PurchaseOrderRequest::checklistItems())->mapWithKeys(fn ($label, $key) => [$key => 1])->all(),
        ])->assertRedirect();

        $this->assertSame('po_created', $poRequest->fresh()->status);
        $this->assertSame('request_po_created', $quotation->fresh()->status);
        $this->assertNotNull(Project::where('quotation_id', $quotation->id)->first());
    }

    public function test_pra_lead_assignment_and_acceptance_does_not_duplicate_lead(): void
    {
        $admin = User::factory()->create(['role' => 'sales_admin']);
        $sales = User::factory()->create(['role' => 'sales']);

        $payload = [
            'instansi' => 'Pra Lead Flow Test',
            'pic_name' => 'PIC Pra Lead',
            'phone' => '081299991111',
            'source' => 'website',
            'initial_need' => 'Kebutuhan pengujian alur.',
            'priority' => 'medium',
            'assigned_sales_id' => $sales->id,
            'action' => 'send',
        ];

        $this->actingAs($admin)->post(route('admin.pra-leads.store'), $payload)->assertRedirect(route('admin.pra-leads.index'));
        $praLead = PraLead::where('instansi', 'Pra Lead Flow Test')->firstOrFail();
        $this->assertSame('waiting_acceptance', $praLead->status);

        $this->actingAs($sales)->post(route('sales.request-masuk.accept', $praLead))->assertRedirect(route('sales.leads.index'));
        $this->assertSame(1, Lead::where('pra_lead_id', $praLead->id)->count());

        $this->actingAs($sales)->post(route('sales.request-masuk.accept', $praLead->fresh()))->assertRedirect();
        $this->assertSame(1, Lead::where('pra_lead_id', $praLead->id)->count());

        $this->actingAs($admin)->delete(route('admin.pra-leads.destroy', $praLead->fresh()))->assertRedirect();
        $this->assertSoftDeleted('pra_leads', ['id' => $praLead->id]);
    }
}

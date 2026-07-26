<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\DesignRevision;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RichQuotationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

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
                'dashboard', 'sales.request-masuk.index', 'sales.leads.index',
                'sales.design-requests.index', 'sales.quotations.index',
                'spv.quotation-approvals.index', 'admin.purchase-order-requests.index',
                'sales.customers.index', 'sales.projects.index', 'activities.index',
                'calendar.index', 'reports.index', 'profile.edit',
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

    public function test_project_operational_workflow_monitoring_and_design_revision_history(): void
    {
        Storage::fake('public');

        $sales = User::factory()->create(['role' => 'sales']);
        $production = User::factory()->create(['role' => 'production']);
        $qc = User::factory()->create(['role' => 'qc']);
        $delivery = User::factory()->create(['role' => 'delivery']);
        $drafter = User::factory()->create(['role' => 'drafter']);
        $administration = User::factory()->create(['role' => 'administration']);
        $customer = Customer::create(['name' => 'Customer Workflow', 'pipeline_stage' => 'identify', 'sales_id' => $sales->id]);
        $project = Project::create([
            'code' => 'PRJ-WORKFLOW-001',
            'name' => 'Project Workflow Test',
            'customer_id' => $customer->id,
            'project_manager_id' => $sales->id,
            'internal_team' => [$drafter->id],
            'status' => 'ongoing',
            'total_value' => 100000000,
        ]);
        Document::create([
            'documentable_type' => Project::class,
            'documentable_id' => $project->id,
            'name' => 'Gambar Fabrikasi Workflow',
            'category' => 'fabrication_drawing',
            'file_path' => 'documents/fabrikasi-workflow.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'version' => 'v1.0',
            'revision_number' => 1,
            'is_current' => true,
            'uploaded_by' => $drafter->id,
        ]);

        $this->actingAs($production)->put(route('project-workflow.production', $project), [
            'production_status' => 'production_finished',
            'production_report_completed' => 1,
            'production_report' => UploadedFile::fake()->create('checklist-produksi.pdf', 30, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($qc)->put(route('project-workflow.qc', $project), [
            'qc_completed' => 1,
            'qc_document' => UploadedFile::fake()->create('checklist-qc.pdf', 30, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($delivery)->put(route('project-workflow.delivery', $project), [
            'delivery_status' => 'completed',
            'delivery_scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'pod' => UploadedFile::fake()->image('pod-customer.jpg'),
            'customer_receiver_name' => 'Budi Customer',
            'customer_received_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'delivery_note' => 'Barang diterima lengkap.',
        ])->assertRedirect();

        $workflow = ProjectWorkflow::where('project_id', $project->id)->firstOrFail();
        $this->assertSame('production_finished', $workflow->production_status);
        $this->assertTrue($workflow->production_report_completed);
        $this->assertTrue($workflow->qc_completed);
        $this->assertTrue($workflow->delivery_out_completed);
        $this->assertTrue($workflow->delivery_returned_completed);
        $this->assertSame('completed', $workflow->delivery_status);
        $this->assertSame('Budi Customer', $workflow->customer_receiver_name);

        $this->actingAs($drafter)->post(route('design-revisions.store', $project), [
            'revision_date' => now()->format('Y-m-d'),
            'notes' => 'Perubahan layout meja dan jalur utility.',
            'revision_file' => UploadedFile::fake()->create('revision-1.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $revision = DesignRevision::where('project_id', $project->id)->firstOrFail();
        $this->assertSame(1, $revision->revision_number);
        $this->assertSame('submitted', $revision->status);

        $this->actingAs($drafter)->put(route('design-revisions.status', [$project, $revision]), [
            'status' => 'reviewed',
        ])->assertRedirect();
        $this->assertSame('reviewed', $revision->fresh()->status);

        $this->actingAs($administration)->get(route('administration.project-monitoring.index'))
            ->assertSuccessful()
            ->assertSee('Project Workflow Test')
            ->assertSee('Produksi Selesai');

        $this->actingAs($sales)->get(route('project-workspace.show', $project))
            ->assertSuccessful()
            ->assertSee('Revision 1')
            ->assertSee('QC Selesai');
    }

    public function test_administrator_can_submit_every_workspace_form_exposed_by_the_ui(): void
    {
        Storage::fake('public');

        $administrator = User::factory()->create(['role' => 'administrator']);
        $project = Project::create([
            'code' => 'PRJ-ADMIN-WORKSPACE-001',
            'name' => 'Project Administrator Workspace',
            'status' => 'ongoing',
        ]);
        Document::create([
            'documentable_type' => Project::class,
            'documentable_id' => $project->id,
            'name' => 'Gambar Fabrikasi Administrator',
            'category' => 'fabrication_drawing',
            'file_path' => 'documents/fabrikasi-administrator.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'version' => 'v1.0',
            'revision_number' => 1,
            'is_current' => true,
            'uploaded_by' => $administrator->id,
        ]);

        $this->actingAs($administrator)->put(route('project-workflow.production', $project), [
            'production_status' => 'production_finished',
            'production_report_completed' => 1,
            'production_report' => UploadedFile::fake()->create('checklist-produksi-admin.pdf', 30, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($administrator)->put(route('project-workflow.qc', $project), [
            'qc_completed' => 1,
            'qc_document' => UploadedFile::fake()->create('checklist-qc-admin.pdf', 30, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($administrator)->put(route('project-workflow.delivery', $project), [
            'delivery_status' => 'completed',
            'delivery_scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'pod' => UploadedFile::fake()->image('pod-admin.jpg'),
            'customer_receiver_name' => 'Penerima Administrator',
            'customer_received_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->actingAs($administrator)->post(route('design-revisions.store', $project), [
            'revision_date' => now()->format('Y-m-d'),
            'notes' => 'Revisi yang dibuat oleh administrator.',
            'revision_file' => UploadedFile::fake()->create('revision-admin.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $workflow = ProjectWorkflow::where('project_id', $project->id)->firstOrFail();
        $revision = DesignRevision::where('project_id', $project->id)->firstOrFail();

        $this->assertSame('production_finished', $workflow->production_status);
        $this->assertTrue($workflow->production_report_completed);
        $this->assertTrue($workflow->qc_completed);
        $this->assertTrue($workflow->delivery_out_completed);
        $this->assertTrue($workflow->delivery_returned_completed);
        $this->assertSame('completed', $workflow->delivery_status);
        $this->assertSame($administrator->id, $workflow->production_updated_by);
        $this->assertSame($administrator->id, $workflow->qc_updated_by);
        $this->assertSame($administrator->id, $workflow->delivery_updated_by);
        $this->assertSame($administrator->id, $revision->created_by);

        $this->actingAs($administrator)->put(route('design-revisions.status', [$project, $revision]), [
            'status' => 'approved',
        ])->assertRedirect();

        $revision->refresh();
        $this->assertSame('approved', $revision->status);
        $this->assertSame($administrator->id, $revision->status_updated_by);

        $sales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($sales)->put(route('project-workflow.production', $project), [
            'production_status' => 'stock',
        ])->assertForbidden();
        $this->actingAs($sales)->put(route('project-workflow.qc', $project))->assertForbidden();
        $this->actingAs($sales)->put(route('project-workflow.delivery', $project))->assertForbidden();
        $this->actingAs($sales)->post(route('design-revisions.store', $project))->assertForbidden();
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

    public function test_only_production_can_change_design_request_progress(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $assigned = User::factory()->create(['role' => 'drafter']);
        $other = User::factory()->create(['role' => 'drafter']);
        $production = User::factory()->create(['role' => 'production']);
        $qc = User::factory()->create(['role' => 'qc']);
        $delivery = User::factory()->create(['role' => 'delivery']);
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
        ])->assertForbidden();

        $this->actingAs($production)->put(route('drafter.design-requests.progress', $request), [
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

    public function test_assignment_export_returns_real_excel_workbook(): void
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

        $response = $this->actingAs($admin)->get(route('admin.assignment.index', ['export' => 'excel']));

        $response->assertSuccessful();
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('Sales Export Test', $sheet);
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
        $production = User::factory()->create(['role' => 'production']);
        $qc = User::factory()->create(['role' => 'qc']);
        $delivery = User::factory()->create(['role' => 'delivery']);
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
        Storage::fake('public');
        $this->actingAs($drafter)->post(route('documents.store'), [
            'documentable_type' => DesignRequest::class, 'documentable_id' => $designRequest->id,
            'name' => 'Gambar Request', 'category' => 'request_drawing',
            'file' => UploadedFile::fake()->create('request.pdf', 20, 'application/pdf'),
        ])->assertRedirect();
        $requestDrawing = Document::where('name', 'Gambar Request')->firstOrFail();
        $this->actingAs($drafter)->post(route('documents.store'), [
            'documentable_type' => DesignRequest::class, 'documentable_id' => $designRequest->id,
            'name' => 'Gambar Request Rev 2', 'category' => 'request_drawing',
            'replaces_document_id' => $requestDrawing->id, 'revision_note' => 'Update layout',
            'file' => UploadedFile::fake()->create('request-r2.pdf', 20, 'application/pdf'),
        ])->assertRedirect();
        $this->assertFalse($requestDrawing->fresh()->is_current);
        $this->assertDatabaseHas('documents', ['parent_document_id' => $requestDrawing->id, 'revision_number' => 2, 'is_current' => 1]);

        $this->actingAs($drafter)->post(route('documents.store'), [
            'documentable_type' => DesignRequest::class, 'documentable_id' => $designRequest->id,
            'name' => 'Gambar Fabrikasi', 'category' => 'fabrication_drawing',
            'file' => UploadedFile::fake()->create('fabrikasi.pdf', 20, 'application/pdf'),
        ])->assertStatus(422);
        $this->actingAs($production)->post(route('drafter.design-requests.feedback', $designRequest), [
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

        $this->actingAs($sales)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Penawaran disetujui SPV')
            ->assertSeeInOrder([
                'bi bi-file-earmark-text',
                'Penawaran',
                'side-badge',
            ], false);

        $this->actingAs($sales)->post(route('sales.quotations.sent-to-customer', $quotation))->assertRedirect();
        $this->actingAs($sales)->post(route('sales.quotations.won', $quotation), ['note' => 'Customer setuju'])->assertRedirect();
        $this->assertSame('customer_accepted', $quotation->fresh()->status);

        $this->assertDatabaseMissing('projects', ['quotation_id' => $quotation->id]);

        $this->actingAs($admin)->post(route('admin.purchase-order-requests.store'), [
            'quotation_id' => $quotation->id,
            'project_number' => 'PRJ-MANUAL-001',
            'customer_name' => $customer->name,
            'customer_area' => 'Area Pengujian',
            'customer_division' => 'Laboratorium',
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
        $project = Project::where('quotation_id', $quotation->id)->firstOrFail();
        $this->assertSame('PRJ-MANUAL-001', $project->code);
        $this->assertSame($drafter->id, $project->project_manager_id);

        $this->actingAs($admin)->post(route('admin.invoices.store'), [
            'purchase_order_request_id' => $poRequest->id,
            'invoice_date' => now()->format('Y-m-d'),
            'terms' => [[
                'description' => 'Pelunasan',
                'percentage' => 100,
                'amount' => (float) $quotation->fresh()->grand_total,
            ]],
        ])->assertSessionHasErrors('purchase_order_request_id');
        $this->assertDatabaseMissing('invoices', ['purchase_order_request_id' => $poRequest->id]);

        $this->actingAs($drafter)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Gambar fabrikasi diperlukan');

        $this->actingAs($drafter)->post(route('documents.store'), [
            'documentable_type' => Project::class,
            'documentable_id' => $project->id,
            'name' => 'Gambar Fabrikasi',
            'category' => 'fabrication_drawing',
            'file' => UploadedFile::fake()->create('fabrikasi.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($production)->get(route('drafter.projects.index'))
            ->assertOk()
            ->assertSee('Gambar fabrikasi siap');

        $this->actingAs($production)->put(route('project-workflow.production', $project), [
            'production_status' => 'production_finished',
            'production_report_completed' => 1,
            'production_report' => UploadedFile::fake()->create('checklist-produksi.pdf', 30, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($qc)->get(route('drafter.projects.index'))
            ->assertOk()
            ->assertSee('Project menunggu QC');

        $qcChecklist = collect(ProjectWorkflow::qcChecklistDefinition($project))
            ->flatMap(fn (array $item) => collect($item['checks'])->pluck('key'))
            ->mapWithKeys(fn (string $key) => [$key => 1])
            ->all();
        $this->actingAs($qc)->put(route('project-workflow.qc', $project), [
            'qc_completed' => 1,
            'qc_checklist' => $qcChecklist,
            'qc_note' => 'Seluruh spesifikasi sesuai penawaran.',
            'qc_document' => UploadedFile::fake()->create('checklist-qc.pdf', 30, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($delivery)->get(route('drafter.projects.index'))
            ->assertOk()
            ->assertSee('Project siap dikirim');

        $this->actingAs($delivery)->put(route('project-workflow.delivery', $project), [
            'delivery_status' => 'completed',
            'delivery_scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'pod' => UploadedFile::fake()->image('pod-end-to-end.jpg'),
            'customer_receiver_name' => 'PIC End To End',
            'customer_received_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'delivery_note' => 'Barang diterima customer.',
        ])->assertRedirect();
        $this->assertSame('done', $project->fresh()->status);
        $this->assertSame('completed', $project->workflow->delivery_status);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Project siap ditagihkan');

        $this->actingAs($admin)->post(route('admin.invoices.store'), [
            'purchase_order_request_id' => $poRequest->id,
            'invoice_date' => now()->format('Y-m-d'),
            'terms' => [[
                'description' => 'Pelunasan', 'percentage' => 100,
                'amount' => (float) $quotation->fresh()->grand_total,
                'due_date' => now()->addDays(14)->format('Y-m-d'),
            ]],
        ])->assertRedirect();
        $invoice = Invoice::where('purchase_order_request_id', $poRequest->id)->firstOrFail();
        $term = $invoice->terms()->firstOrFail();
        $this->actingAs($admin)->put(route('admin.invoices.terms.update', [$invoice, $term]), [
            'status' => 'paid', 'paid_date' => now()->format('Y-m-d'),
        ])->assertRedirect();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('paid', $poRequest->fresh()->status);
    }

    public function test_production_image_and_structured_spec_are_snapshotted_into_excel_quotation(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales']);
        $drafter = User::factory()->create(['role' => 'drafter']);
        $production = User::factory()->create(['role' => 'production']);
        $spv = User::factory()->create(['role' => 'sales_spv']);
        $customer = Customer::create([
            'name' => 'Customer Excel Detail',
            'pipeline_stage' => 'follow_up',
            'sales_id' => $sales->id,
        ]);
        $designRequest = DesignRequest::create([
            'code' => 'DR-EXCEL-001',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'project_name' => 'Lab Export Excel',
            'request_date' => today(),
            'deadline' => today()->addWeek(),
            'priority' => 'high',
            'short_description' => 'Penawaran detail bergambar.',
            'detail_need' => 'Fume hood lengkap.',
            'sales_id' => $sales->id,
            'production_pic_id' => $drafter->id,
            'status' => 'assigned',
        ]);
        $specification = "[General]\nType: FH-150 ECO\nManufacturer: PT. Robust Multilab Solusindo\n[Dimensions (W x D x H, mm)]\nOverall Dimension: 1500 x 890 x 2350\n[Utilities]\nElectrical Socket: Single electric socket, IP55\n@ 4 | pcs | 500000";

        $this->actingAs($drafter)->post(route('drafter.design-requests.feedback', $designRequest), [
            'action' => 'review',
        ])->assertForbidden();

        $this->actingAs($drafter)->post(route('documents.store'), [
            'documentable_type' => DesignRequest::class,
            'documentable_id' => $designRequest->id,
            'name' => 'Drawing Fume Hood',
            'category' => 'request_drawing',
            'file' => UploadedFile::fake()->create('drawing-fume-hood.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($production)->post(route('drafter.design-requests.feedback', $designRequest), [
            'cost_material' => 5000000,
            'cost_production' => 2000000,
            'cost_installation' => 500000,
            'technical_note' => 'Render final untuk penawaran.',
            'items' => [[
                'category' => 'Fume Hood',
                'name' => 'FUME HOOD ECO',
                'variant' => 'FUME HOOD FH-150 ECO',
                'specification' => $specification,
                'qty' => 1,
                'unit' => 'Unit',
                'unit_price' => 9000000,
                'quotation_image' => UploadedFile::fake()->image('fh-150.png', 800, 600),
            ]],
            'action' => 'submit',
        ])->assertRedirect(route('drafter.design-requests.index'));

        $designItem = $designRequest->items()->firstOrFail();
        $this->assertSame('completed', $designRequest->fresh()->status);
        $this->assertSame(9000000.0, (float) $designItem->unit_price);
        Storage::disk('public')->assertExists($designItem->quotation_image_path);

        $this->actingAs($sales)->post(route('sales.quotations.store'), [
            'design_request_id' => $designRequest->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'project_name' => $designRequest->project_name,
            'delivery_method' => 'email',
            'quote_date' => today()->format('Y-m-d'),
            'valid_until' => today()->addMonth()->format('Y-m-d'),
            'priority' => 'high',
            'currency' => 'IDR',
            'discount_type' => 'percent',
            'discount_value' => 0,
            'tax_percent' => 11,
            'items' => [[
                'source_design_request_item_id' => $designItem->id,
                'category' => $designItem->category,
                'name' => $designItem->name,
                'variant' => $designItem->variant,
                'specification' => $specification,
                'qty' => 1,
                'unit' => 'Unit',
                'cost_price' => 9000000,
                'margin' => 20,
            ]],
            'action' => 'draft',
        ])->assertRedirect();

        $quotation = Quotation::where('project_name', 'Lab Export Excel')->latest()->firstOrFail();
        $quotationItem = $quotation->items()->firstOrFail();
        $this->assertNotSame($designItem->quotation_image_path, $quotationItem->quotation_image_path);
        Storage::disk('public')->assertExists($quotationItem->quotation_image_path);

        $this->actingAs($sales)
            ->get(route('sales.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Excel Terkunci')
            ->assertSee('PDF Terkunci');

        $this->actingAs($sales)
            ->get(route('sales.quotations.excel', $quotation))
            ->assertRedirect()
            ->assertSessionHas('error', 'Excel penawaran hanya bisa didownload setelah disetujui SPV.');

        $this->actingAs($sales)->post(route('sales.quotations.submit-approval', $quotation))->assertRedirect();
        $this->actingAs($spv)->get(route('spv.quotation-approvals.excel', $quotation->fresh()))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($spv)
            ->post(route('spv.quotation-approvals.approve', $quotation->fresh()), [
                'approval_note' => 'Excel sudah sesuai.',
            ])
            ->assertRedirect();

        $response = $this->actingAs($sales)->get(route('sales.quotations.excel', $quotation->fresh()));
        $response->assertOk()->assertDownload();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()) === true);
        $this->assertNotFalse($zip->locateName('xl/drawings/drawing1.xml'));
        $this->assertNotFalse($zip->locateName('xl/media/image1.png'));
        $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $drawing = $zip->getFromName('xl/drawings/drawing1.xml');
        $zip->close();
        $this->assertStringContainsString('Dimensions (W x D x H, mm)', $worksheet);
        $this->assertMatchesRegularExpression('/<f>H\\d+\\*J\\d+<\\/f>/', $worksheet);
        $this->assertMatchesRegularExpression('/<f>D\\d+\\*F\\d+<\\/f><v>2000000<\\/v>/', $worksheet);
        $this->assertMatchesRegularExpression('/<c r="C7"[^>]*s="1">/', $worksheet);
        $this->assertMatchesRegularExpression('/<c r="G7"[^>]*s="1">/', $worksheet);
        $this->assertMatchesRegularExpression('/<mergeCell ref="H\\d+:K\\d+"\\/>/', $worksheet);
        $this->assertStringContainsString('<xdr:oneCellAnchor>', $drawing);
        $this->assertStringNotContainsString('<xdr:twoCellAnchor', $drawing);
        $this->assertMatchesRegularExpression('/<a:picLocks noChangeAspect="1"\\/>/', $drawing);
        $this->assertMatchesRegularExpression('/<xdr:ext cx="\\d+" cy="\\d+"\\/>/', $drawing);
    }

    public function test_sales_can_upload_a_custom_quotation_image_and_optional_item_is_excluded_from_total(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales']);

        $this->actingAs($sales)->post(route('sales.quotations.store'), [
            'customer_name' => 'Customer Gambar Custom',
            'project_name' => 'Penawaran Item Alternatif',
            'delivery_method' => 'email',
            'quote_date' => today()->format('Y-m-d'),
            'valid_until' => today()->addMonth()->format('Y-m-d'),
            'priority' => 'medium',
            'currency' => 'IDR',
            'discount_type' => 'percent',
            'discount_value' => 0,
            'tax_percent' => 11,
            'items' => [[
                'name' => 'CUSTOM LAB ITEM',
                'specification' => 'Gambar diunggah langsung dari penawaran.',
                'quotation_image' => UploadedFile::fake()->image('custom-item.png', 800, 600),
                'qty' => 1,
                'unit' => 'Unit',
                'cost_price' => 1000000,
                'margin' => 10,
                'is_optional' => 1,
            ]],
            'action' => 'draft',
        ])->assertRedirect();

        $quotation = Quotation::where('project_name', 'Penawaran Item Alternatif')->firstOrFail();
        $item = $quotation->items()->firstOrFail();

        $this->assertTrue($item->is_optional);
        $this->assertSame(0.0, (float) $quotation->subtotal);
        $this->assertSame('custom-item.png', $item->quotation_image_name);
        Storage::disk('public')->assertExists($item->quotation_image_path);
    }

    public function test_rich_quotation_seeder_creates_idempotent_reference_export(): void
    {
        Storage::fake('public');

        $this->seed(RichQuotationSeeder::class);
        $this->seed(RichQuotationSeeder::class);

        $quotation = Quotation::where('code', 'Q-DEMO-DETAIL-2026')->firstOrFail();
        $items = $quotation->items()->get();

        $this->assertCount(3, $items);
        $this->assertSame(1500000.0, (float) $quotation->subtotal);
        $this->assertSame(1665000.0, (float) $quotation->grand_total);
        $this->assertStringContainsString('[Under-Bench Cabinet formations]', $items->first()->specification);
        $this->assertStringContainsString('@ 4 | pcs | 500000', $items->first()->specification);
        foreach ($items as $item) {
            Storage::disk('public')->assertExists($item->quotation_image_path);
        }

        $sales = User::where('email', 'sales@robust.test')->firstOrFail();
        $this->actingAs($sales)->get(route('sales.design-requests.show', $quotation->designRequest))
            ->assertOk()
            ->assertSee('quotation-item-card', false)
            ->assertSee('structured-spec-view', false)
            ->assertSee('Under-Bench Cabinet formations');

        $this->actingAs($sales)->get(route('sales.quotations.create', ['dr' => $quotation->design_request_id]))
            ->assertOk()
            ->assertSee('Tambah gambar')
            ->assertSee('Item alternatif')
            ->assertSee('Tidak dihitung ke total penawaran.');

        $this->actingAs($sales)->get(route('sales.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('quotation-item-card', false)
            ->assertSee('structured-spec-view', false)
            ->assertSee('Under-Bench Cabinet formations');

        $drafter = User::where('email', 'drafter@robust.test')->firstOrFail();
        $this->actingAs($drafter)->get(route('drafter.design-requests.show', $quotation->designRequest))
            ->assertOk()
            ->assertSee('data-specification-editor', false)
            ->assertSee('Tambah Bagian');

        $administrator = User::factory()->create(['role' => 'administrator']);
        $this->actingAs($administrator)->get(route('admin.item-masters.index'))
            ->assertOk()
            ->assertSee('master-item-list', false)
            ->assertSee('data-specification-editor', false);

        $requestPo = PurchaseOrderRequest::create([
            'code' => 'RPO-STRUCTURED-SPEC',
            'quotation_id' => $quotation->id,
            'customer_id' => $quotation->customer_id,
            'project_number' => 'PRJ-STRUCTURED-SPEC',
            'customer_name' => $quotation->customer_name,
            'requested_by' => $administrator->id,
            'request_date' => today(),
            'status' => 'submitted',
        ]);
        $this->actingAs($administrator)
            ->get(route('admin.purchase-order-requests.show', $requestPo))
            ->assertOk()
            ->assertSee('quotation-item-card', false)
            ->assertSee('structured-spec-view', false)
            ->assertSee('Under-Bench Cabinet formations')
            ->assertDontSee('<td class="small">[General]', false);

        $response = $this->actingAs($sales)->get(route('sales.quotations.excel', $quotation));
        $response->assertOk()->assertDownload();

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()) === true);
        $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $drawing = $zip->getFromName('xl/drawings/drawing1.xml');
        $zip->close();

        $this->assertStringContainsString('Under-Bench Cabinet formations', $worksheet);
        $this->assertStringContainsString('By Others / by Client', $worksheet);
        $this->assertMatchesRegularExpression('/<f>D\\d+\\*F\\d+<\\/f><v>2000000<\\/v>/', $worksheet);
        $this->assertSame(3, substr_count($drawing, '<xdr:pic>'));
        preg_match_all(
            '/<xdr:oneCellAnchor>.*?<xdr:from>.*?<xdr:row>(\\d+)<\\/xdr:row>.*?<\\/xdr:from><xdr:ext cx="(\\d+)" cy="(\\d+)"\\/>.*?<\\/xdr:oneCellAnchor>/s',
            $drawing,
            $anchors,
            PREG_SET_ORDER
        );
        $this->assertCount(3, $anchors);
        foreach ($anchors as $anchor) {
            $this->assertGreaterThan(0, (int) $anchor[2]);
            $this->assertGreaterThan(0, (int) $anchor[3]);
            $this->assertLessThanOrEqual(6500000, (int) $anchor[3]);
        }
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

    public function test_quotation_price_margin_owner_error_page_and_pdf_are_consistent(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $otherSales = User::factory()->create(['role' => 'sales']);
        $spv = User::factory()->create(['role' => 'sales_spv']);
        $customer = Customer::create([
            'name' => 'Customer Margin Otomatis',
            'pipeline_stage' => 'follow_up',
            'sales_id' => $sales->id,
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'project_name' => 'Lab Margin Otomatis',
            'delivery_method' => 'email',
            'quote_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(30)->format('Y-m-d'),
            'priority' => 'medium',
            'currency' => 'IDR',
            'discount_type' => 'percent',
            'discount_value' => 0,
            'tax_percent' => 11,
            'target_margin' => 1, // Nilai browser harus diabaikan dan dihitung ulang.
            'items' => [[
                'name' => 'Wall Bench',
                'qty' => 2,
                'unit' => 'Unit',
                'cost_price' => 1000000,
                'margin' => 20,
            ]],
            'action' => 'draft',
        ];

        $response = $this->actingAs($sales)->post(route('sales.quotations.store'), $payload);
        $quotation = Quotation::where('project_name', 'Lab Margin Otomatis')->firstOrFail();
        $response->assertRedirect(route('sales.quotations.show', $quotation));

        $item = $quotation->items()->firstOrFail();
        $this->assertSame($sales->id, (int) $quotation->sales_id);
        $this->assertSame(1000000.0, (float) $item->cost_price);
        $this->assertSame(1250000.0, (float) $item->unit_price);
        $this->assertSame(2500000.0, (float) $item->total);
        $this->assertSame(20.0, (float) $quotation->target_margin);
        $this->actingAs($sales)->get(route('sales.quotations.show', $quotation))->assertSuccessful();

        $this->actingAs($otherSales)->get(route('sales.quotations.show', $quotation))
            ->assertForbidden()
            ->assertSee('Akses ditolak')
            ->assertSee('Penawaran ini bukan milik Anda.');

        $quotation->update([
            'status' => 'approved',
            'approved_by' => $spv->id,
            'approved_at' => now(),
        ]);
        $pdf = $this->actingAs($sales)->get(route('sales.quotations.pdf', $quotation));
        $pdf->assertSuccessful()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $pdf->getContent());
        $this->assertStringContainsString('PENAWARAN HARGA', $pdf->getContent());
    }

    public function test_customer_index_updates_the_detail_panel_for_non_superadmin_roles(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $salesAdmin = User::factory()->create(['role' => 'sales_admin']);
        $salesSpv = User::factory()->create(['role' => 'sales_spv']);
        $firstCustomer = Customer::create([
            'name' => 'Customer Detail Pertama',
            'pipeline_stage' => 'identify',
            'sales_id' => $sales->id,
        ]);
        $selectedCustomer = Customer::create([
            'name' => 'Customer Detail Terpilih',
            'pipeline_stage' => 'follow_up',
            'sales_id' => $sales->id,
        ]);

        foreach ([$sales, $salesAdmin, $salesSpv] as $user) {
            $response = $this->actingAs($user)->get(route('sales.customers.index', [
                'customer' => $selectedCustomer->id,
            ]));

            $response->assertSuccessful()
                ->assertSee('Customer Detail Terpilih')
                ->assertSee('data-selected-customer="'.$selectedCustomer->id.'"', false)
                ->assertSee('data-customer-href=', false);
        }

        $this->actingAs($sales)->get(route('sales.customers.index', [
            'customer' => $firstCustomer->id,
        ]))->assertSuccessful()
            ->assertSee('data-selected-customer="'.$firstCustomer->id.'"', false);
    }
}

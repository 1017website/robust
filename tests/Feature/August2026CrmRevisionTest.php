<?php

namespace Tests\Feature;

use App\Models\DesignRequest;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\PurchaseOrderRequest;
use App\Models\User;
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
}

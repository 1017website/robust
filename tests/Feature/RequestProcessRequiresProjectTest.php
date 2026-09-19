<?php

namespace Tests\Feature;

use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Request Process hanya boleh dibuat setelah Project (Request PO) diajukan, karena
 * produksi berjalan atas dasar PO yang sudah tercatat di Accurate.
 */
class RequestProcessRequiresProjectTest extends TestCase
{
    use DatabaseTransactions;

    protected function wonQuotation(User $sales): Quotation
    {
        return Quotation::create([
            'code' => 'Q-GATE-'.uniqid(),
            'customer_name' => 'PT Gate Test',
            'project_name' => 'Lab Gate',
            'status' => 'customer_accepted',
            'sales_id' => $sales->id,
            'grand_total' => 5000000,
        ]);
    }

    public function test_request_process_is_blocked_until_project_is_submitted(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = $this->wonQuotation($sales);

        $this->assertFalse($quotation->canCreateProject());

        $this->actingAs($sales)
            ->get(route('sales.projects.create', ['quotation' => $quotation->id]))
            ->assertNotFound();
    }

    public function test_draft_project_is_not_enough_to_unlock_request_process(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = $this->wonQuotation($sales);

        PurchaseOrderRequest::create([
            'code' => 'RPO-GATE-DRAFT',
            'quotation_id' => $quotation->id,
            'requested_by' => $sales->id,
            'status' => 'draft',
        ]);

        $this->assertFalse($quotation->fresh()->canCreateProject());
    }

    public function test_submitted_project_unlocks_request_process(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = $this->wonQuotation($sales);

        PurchaseOrderRequest::create([
            'code' => 'RPO-GATE-SUBMITTED',
            'quotation_id' => $quotation->id,
            'requested_by' => $sales->id,
            'status' => 'submitted',
        ]);

        $this->assertTrue($quotation->fresh()->canCreateProject());

        $this->actingAs($sales)
            ->get(route('sales.projects.create', ['quotation' => $quotation->id]))
            ->assertSuccessful();
    }

    public function test_submitting_a_project_starts_it_running_and_creates_request_process(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = $this->wonQuotation($sales);

        $this->actingAs($sales)->post(route('admin.purchase-order-requests.store'), [
            'purchase_source' => 'crm',
            'quotation_id' => $quotation->id,
            'customer_name' => $quotation->customer_name,
            'request_date' => today()->format('Y-m-d'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $requestPo = PurchaseOrderRequest::where('quotation_id', $quotation->id)->firstOrFail();

        // Tidak ada lagi fase menunggu Accurate: Project langsung berjalan.
        $this->assertSame('po_created', $requestPo->status);
        $this->assertSame('Berjalan', PurchaseOrderRequest::statuses()[$requestPo->status]);
        $this->assertNotNull($requestPo->processed_at);

        // Request Process ikut terbentuk supaya produksi bisa langsung jalan.
        $this->assertNotNull($quotation->fresh()->project);
    }

    public function test_request_process_reuses_the_project_number_as_its_code(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = $this->wonQuotation($sales);

        $this->actingAs($sales)->post(route('admin.purchase-order-requests.store'), [
            'purchase_source' => 'crm',
            'quotation_id' => $quotation->id,
            'code' => 'PRJ-SHARED-001',
            'customer_name' => $quotation->customer_name,
            'request_date' => today()->format('Y-m-d'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $requestPo = PurchaseOrderRequest::where('quotation_id', $quotation->id)->firstOrFail();

        $this->assertSame('PRJ-SHARED-001', $requestPo->projectNumber());
        $this->assertSame('PRJ-SHARED-001', $quotation->fresh()->project->code);
    }

    public function test_a_draft_is_saved_without_starting_anything(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = $this->wonQuotation($sales);

        $this->actingAs($sales)->post(route('admin.purchase-order-requests.store'), [
            'purchase_source' => 'crm',
            'quotation_id' => $quotation->id,
            'customer_name' => $quotation->customer_name,
            'action' => 'draft',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $draft = PurchaseOrderRequest::where('quotation_id', $quotation->id)->firstOrFail();
        $this->assertTrue($draft->isDraft());
        $this->assertNull($quotation->fresh()->project);
    }

    public function test_the_form_no_longer_asks_for_accurate_po_details(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);

        $this->actingAs($sales)->get(route('admin.purchase-order-requests.create'))
            ->assertOk()
            ->assertDontSee('No PO Accurate')
            ->assertDontSee('Tanggal PO Accurate')
            ->assertDontSee('name="accurate_po_number"', false)
            ->assertDontSee('name="accurate_po_date"', false);
    }

    public function test_waiting_for_accurate_statuses_are_retired_but_still_readable(): void
    {
        $this->assertArrayNotHasKey('submitted', PurchaseOrderRequest::processStatuses());
        $this->assertArrayNotHasKey('processing_accurate', PurchaseOrderRequest::processStatuses());

        // Record lama tetap terbaca dan masih dihitung sebagai Project berjalan.
        $this->assertSame('Diajukan (status lama)', PurchaseOrderRequest::statuses()['submitted']);
        $this->assertContains('submitted', PurchaseOrderRequest::openStatuses());
        $this->assertContains('po_created', PurchaseOrderRequest::openStatuses());
        $this->assertNotContains('paid', PurchaseOrderRequest::openStatuses());
        $this->assertNotContains('draft', PurchaseOrderRequest::openStatuses());
    }

    public function test_production_phases_are_no_longer_selectable_on_project(): void
    {
        $selectable = array_keys(PurchaseOrderRequest::processStatuses());

        $this->assertSame(['po_created', 'paid', 'cancelled'], $selectable);

        // Data historis tetap punya label agar badge tidak menampilkan kode mentah.
        $this->assertSame('Produksi (status lama)', PurchaseOrderRequest::statuses()['production']);
    }

    public function test_legacy_status_stays_selectable_for_the_record_that_still_uses_it(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = $this->wonQuotation($sales);

        $requestPo = PurchaseOrderRequest::create([
            'code' => 'RPO-GATE-LEGACY',
            'quotation_id' => $quotation->id,
            'requested_by' => $sales->id,
            'status' => 'production',
        ]);

        $this->assertArrayHasKey('production', $requestPo->selectableStatuses());
        $this->assertSame('production', array_key_first($requestPo->selectableStatuses()));
    }
}

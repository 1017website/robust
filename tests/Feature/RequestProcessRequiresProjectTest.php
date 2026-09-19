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

    public function test_production_phases_are_no_longer_selectable_on_project(): void
    {
        $selectable = array_keys(PurchaseOrderRequest::processStatuses());

        $this->assertSame(
            ['submitted', 'processing_accurate', 'po_created', 'paid', 'cancelled'],
            $selectable
        );

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

<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectOrderItemsTest extends TestCase
{
    use RefreshDatabase;

    private function directPayload(): array
    {
        return [
            'purchase_source' => 'external', 'action' => 'submit',
            'customer_name' => 'Customer PO', 'request_date' => today()->toDateString(),
            'external_project_name' => 'Project langsung PO', 'po_total' => 3097000,
            'order_discount_type' => 'percent', 'order_discount_value' => 10,
            'order_tax_percent' => 11, 'order_additional_cost' => 100000,
            'order_items' => [['name' => 'Cabinet', 'qty' => 2, 'unit' => 'Unit', 'unit_price' => 1500000, 'specification' => "[Material]\nWarna: Putih"]],
        ];
    }

    private function quotation(User $sales, string $mode): Quotation
    {
        $quotation = Quotation::create(['code' => 'Q-'.$mode, 'sales_id' => $sales->id, 'customer_name' => 'Customer PO', 'project_name' => 'Project '.$mode, 'creation_mode' => $mode, 'status' => 'ready', 'tax_percent' => 0, 'discount_value' => 0, 'grand_total' => 3000000]);
        $quotation->items()->create(['name' => 'Cabinet', 'qty' => 2, 'unit' => 'Unit', 'unit_price' => 1500000, 'total' => 3000000]);

        return $quotation;
    }

    public function test_quotation_lists_put_newest_first_even_with_equal_creation_times(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $older = $this->quotation($sales, 'builder');
        $newer = $this->quotation($sales, 'upload');
        $older->forceFill(['created_at' => now()->startOfDay(), 'approved_at' => now()])->save();
        $newer->forceFill(['created_at' => now()->startOfDay(), 'approved_at' => null])->save();

        $this->actingAs($sales)->get(route('sales.quotations.index'))
            ->assertOk()->assertSeeInOrder([$newer->code, $older->code]);
        $this->get(route('admin.purchase-order-requests.create'))
            ->assertOk()->assertSeeInOrder([$newer->code, $older->code]);
    }

    public function test_direct_po_requires_items_and_matching_total_without_creating_partial_projects(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'sales']));
        $payload = $this->directPayload();
        unset($payload['order_items']);
        $this->post(route('admin.purchase-order-requests.store'), $payload)->assertSessionHasErrors('order_items');
        $payload = $this->directPayload();
        $payload['po_total'] = 1000;
        $this->post(route('admin.purchase-order-requests.store'), $payload)->assertSessionHasErrors('po_total');
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('purchase_order_requests', 0);
        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_direct_po_items_and_calculated_value_reach_production_and_qc(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'sales']));
        $payload = $this->directPayload();
        $payload['po_total'] = '3.097.000';
        $payload['order_items'][0]['unit_price'] = '1.500.000';
        $payload['order_additional_cost'] = '100.000';
        $this->post(route('admin.purchase-order-requests.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $project = Project::sole();
        $this->assertSame(3097000.0, (float) $project->total_value);
        $this->assertSame(2700000.0, (float) $project->project_value);
        $this->assertSame(297000.0, (float) $project->tax_amount);
        $this->assertSame('Cabinet', $project->quotation->items->sole()->name);
        $this->get(route('admin.purchase-order-requests.show', PurchaseOrderRequest::sole()))->assertOk()->assertSee('Item Project')->assertSee('Cabinet');
        $checks = ProjectWorkflow::qcChecklistDefinition($project, false);
        $this->assertStringContainsString('Putih', json_encode($checks));
        $this->actingAs(User::factory()->create(['role' => 'production']))->get(route('project-workspace.show', $project))
            ->assertOk()->assertSee('Cabinet')->assertSee('Putih')->assertDontSee('Rp 1.500.000');
    }

    public function test_builder_and_upload_share_the_same_project_path_without_overwriting_prices(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        foreach (['builder', 'upload'] as $mode) {
            $quotation = $this->quotation($sales, $mode);
            $item = $quotation->items()->sole();
            $payload = ['purchase_source' => 'crm', 'quotation_id' => $quotation->id, 'customer_name' => 'Customer PO', 'request_date' => today()->toDateString(), 'po_total' => 3000000,
                'order_items' => [['id' => $item->id, 'name' => 'Nama palsu', 'qty' => 99, 'unit_price' => 1, 'specification' => 'Warna: Putih']]];
            $this->actingAs($sales)->get(route('admin.purchase-order-requests.create', ['quotation' => $quotation->id]))
                ->assertOk()->assertSee('Dari Penawaran')->assertSee('Langsung dari PO')->assertDontSee('Penawaran CRM')->assertDontSee('PO Existing / Non-CRM');
            $this->post(route('admin.purchase-order-requests.store'), $payload)->assertSessionHasNoErrors();
            $this->assertSame(3000000.0, (float) $quotation->fresh()->grand_total);
            $this->assertSame('Cabinet', $item->fresh()->name);
            $this->assertSame(2.0, (float) $item->fresh()->qty);
            $this->assertSame('Warna: Putih', $item->fresh()->specification);
            $this->assertSame($quotation->id, $quotation->fresh()->purchaseOrderRequest->quotation_id);
        }
        $this->assertDatabaseCount('quotations', 2);
        $this->assertDatabaseCount('projects', 2);
    }

    public function test_linked_quotation_cannot_start_without_items_or_with_an_incorrect_po_value(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = $this->quotation($sales, 'upload');
        $payload = ['quotation_id' => $quotation->id, 'customer_name' => 'Customer PO', 'request_date' => today()->toDateString(), 'po_total' => 3000000];
        $this->actingAs($sales);
        $payload['po_total'] = 1;
        $this->post(route('admin.purchase-order-requests.store'), $payload)->assertSessionHasErrors('po_total');
        $this->assertDatabaseCount('projects', 0);
        $quotation->items()->delete();
        $this->post(route('admin.purchase-order-requests.store'), $payload)->assertSessionHasErrors('order_items');
    }

    public function test_projects_can_be_submitted_without_item_specifications(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($sales);
        $payload = $this->directPayload();
        unset($payload['order_items'][0]['specification']);
        $this->post(route('admin.purchase-order-requests.store'), $payload)->assertSessionHasNoErrors();

        foreach (['builder', 'upload'] as $mode) {
            $quotation = $this->quotation($sales, $mode);
            $this->post(route('admin.purchase-order-requests.store'), [
                'quotation_id' => $quotation->id, 'customer_name' => 'Customer PO',
                'request_date' => today()->toDateString(), 'po_total' => 3000000,
                'order_items' => [['id' => $quotation->items->sole()->id, 'specification' => '']],
            ])->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('projects', 3);
    }

    public function test_direct_po_draft_preserves_items_and_can_be_completed_before_submission(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'sales']));
        $payload = $this->directPayload();
        $payload['action'] = 'draft';
        $payload['order_items'][0]['specification'] = '';
        $this->post(route('admin.purchase-order-requests.store'), $payload)->assertSessionHasNoErrors();
        $draft = PurchaseOrderRequest::sole();
        $this->assertDatabaseCount('projects', 0);
        $this->assertSame('Cabinet', $draft->quotation->items->sole()->name);
        $this->get(route('admin.purchase-order-requests.edit', $draft))->assertOk()->assertSee('Cabinet');
        $payload = $this->directPayload();
        $payload['order_items'][0]['id'] = $draft->quotation->items->sole()->id;
        $this->put(route('admin.purchase-order-requests.draft', $draft), $payload)->assertSessionHasNoErrors();
        $this->assertSame('po_created', $draft->fresh()->status);
        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseCount('quotations', 1);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeliveryOrderWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_delivery_can_create_update_and_download_delivery_order(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $delivery = User::factory()->create(['role' => 'delivery']);
        $otherDelivery = User::factory()->create(['role' => 'delivery']);
        $customer = Customer::create([
            'name' => 'Customer Delivery Order',
            'pipeline_stage' => 'won_closing',
            'sales_id' => $sales->id,
        ]);
        $project = Project::create([
            'code' => 'PRJ-DO-001',
            'name' => 'Project Delivery Order',
            'customer_id' => $customer->id,
            'project_manager_id' => $sales->id,
            'status' => 'finishing',
        ]);
        ProjectWorkflow::create([
            'project_id' => $project->id,
            'production_status' => 'production_finished',
            'qc_completed' => true,
            'delivery_status' => 'scheduling',
        ]);

        $payload = [
            'delivery_date' => '2026-07-28',
            'delivery_address' => 'Jl. Laboratorium No. 10, Jakarta',
            'recipient_name' => 'Budi Penerima',
            'recipient_phone' => '08123456789',
            'driver_name' => 'Agus Driver',
            'vehicle_number' => 'b 1234 robust',
            'notes' => 'Mohon periksa jumlah barang.',
            'items' => [[
                'name' => 'Wall Bench 2000 mm',
                'qty' => 2,
                'unit' => 'Unit',
            ]],
        ];

        $this->actingAs($delivery)
            ->post(route('delivery-orders.store', $project), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $deliveryOrder = DeliveryOrder::where('project_id', $project->id)->firstOrFail();
        $this->assertStringStartsWith('DO-2026-', $deliveryOrder->code);
        $this->assertSame('B 1234 ROBUST', $deliveryOrder->vehicle_number);
        $this->assertSame('Wall Bench 2000 mm', $deliveryOrder->items[0]['name']);

        $this->actingAs($otherDelivery)
            ->post(route('delivery-orders.store', $project), array_merge($payload, ['driver_name' => 'Driver Pengganti']))
            ->assertRedirect();

        $this->assertSame('Driver Pengganti', $deliveryOrder->fresh()->driver_name);
        $this->assertSame($deliveryOrder->id, DeliveryOrder::where('project_id', $project->id)->sole()->id);

        $this->actingAs($delivery)
            ->get(route('project-workspace.show', $project))
            ->assertOk()
            ->assertSee('Delivery Order (DO)')
            ->assertSee('Perbarui DO')
            ->assertSee(route('delivery-orders.pdf', $project), false);

        $pdfResponse = $this->actingAs($delivery)
            ->get(route('delivery-orders.pdf', [$project, 'download' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="'.$deliveryOrder->code.'.pdf"');
        $this->assertStringStartsWith('%PDF-', $pdfResponse->getContent());

        $this->actingAs($sales)
            ->post(route('delivery-orders.store', $project), $payload)
            ->assertForbidden();
    }

    public function test_delivery_order_requires_completed_qc(): void
    {
        $delivery = User::factory()->create(['role' => 'delivery']);
        $project = Project::create([
            'code' => 'PRJ-DO-PENDING-QC',
            'name' => 'Project Pending QC',
            'status' => 'finishing',
        ]);
        ProjectWorkflow::create([
            'project_id' => $project->id,
            'production_status' => 'production_finished',
            'qc_completed' => false,
        ]);

        $this->actingAs($delivery)
            ->post(route('delivery-orders.store', $project), [
                'delivery_date' => '2026-07-28',
                'delivery_address' => 'Alamat customer',
                'items' => [['name' => 'Meja Lab', 'qty' => 1, 'unit' => 'Unit']],
            ])
            ->assertStatus(422);
    }
}

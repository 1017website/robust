<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\OperationalUserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssignmentAndOperationalUsersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reassigning_lead_also_transfers_linked_customer_ownership(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $firstSales = User::factory()->create(['role' => 'sales']);
        $secondSales = User::factory()->create(['role' => 'sales']);
        $customer = Customer::create([
            'name' => 'Customer Ownership Assignment',
            'pipeline_stage' => 'identify',
            'sales_id' => $firstSales->id,
        ]);
        $lead = Lead::create([
            'code' => 'LD-OWNER-SYNC',
            'instansi' => $customer->name,
            'pic_name' => 'PIC Ownership',
            'phone' => '081200001234',
            'location' => 'Bandung',
            'city' => 'Bandung',
            'instansi_type' => 'Industri',
            'source' => 'distributor',
            'lab_name' => 'Lab Ownership',
            'priority' => 'medium',
            'stage' => 'lead',
            'status' => 'aktif',
            'sales_id' => $firstSales->id,
            'customer_id' => $customer->id,
            'created_by' => $administrator->id,
        ]);

        $this->actingAs($administrator)->post(route('admin.assignment.reassign'), [
            'lead_id' => $lead->id,
            'to_sales_id' => $secondSales->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($secondSales->id, $lead->fresh()->sales_id);
        $this->assertSame($secondSales->id, $customer->fresh()->sales_id);
        $this->actingAs($firstSales)->get(route('sales.leads.show', $lead))->assertForbidden();
        $this->actingAs($firstSales)->get(route('sales.customers.show', $customer))->assertForbidden();
        $this->actingAs($secondSales)->get(route('sales.leads.show', $lead))->assertOk();
        $this->actingAs($secondSales)->get(route('sales.customers.show', $customer))->assertOk();
    }

    public function test_operational_user_seeder_is_idempotent_and_creates_required_roles(): void
    {
        $this->seed(OperationalUserSeeder::class);
        $this->seed(OperationalUserSeeder::class);

        foreach ([
            'administration@robust.test' => 'administration',
            'qc@robust.test' => 'qc',
            'delivery@robust.test' => 'delivery',
        ] as $email => $role) {
            $this->assertSame(1, User::withTrashed()->where('email', $email)->count());
            $user = User::where('email', $email)->firstOrFail();
            $this->assertSame($role, $user->role);
            $this->assertTrue($user->is_active);
            $this->actingAs($user)->get(route('drafter.projects.index'))->assertOk();
        }

        $administration = User::where('email', 'administration@robust.test')->firstOrFail();
        $this->actingAs($administration)
            ->get(route('administration.project-monitoring.index'))
            ->assertOk();

        $qc = User::where('email', 'qc@robust.test')->firstOrFail();
        $delivery = User::where('email', 'delivery@robust.test')->firstOrFail();
        $this->actingAs($qc)->get(route('admin.invoices.index'))->assertForbidden();
        $this->actingAs($delivery)->get(route('admin.invoices.index'))->assertForbidden();
    }
}

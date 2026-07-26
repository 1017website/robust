<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LeadActivityNotificationRegressionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pra_lead_whatsapp_is_required(): void
    {
        $admin = User::factory()->create(['role' => 'sales_admin']);

        $this->actingAs($admin)
            ->from(route('admin.pra-leads.index'))
            ->post(route('admin.pra-leads.store'), [
                'instansi' => 'Prospek Tanpa WhatsApp',
                'pic_name' => 'PIC Prospek',
                'source' => 'website',
                'priority' => 'medium',
                'action' => 'save',
            ])
            ->assertRedirect(route('admin.pra-leads.index'))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('pra_leads', ['instansi' => 'Prospek Tanpa WhatsApp']);
    }

    public function test_division_flows_from_pra_lead_to_lead_and_customer(): void
    {
        $admin = User::factory()->create(['role' => 'sales_admin']);
        $sales = User::factory()->create(['role' => 'sales']);

        $this->actingAs($admin)->post(route('admin.pra-leads.store'), [
            'instansi' => 'Customer Divisi Test',
            'division' => 'Research and Development',
            'pic_name' => 'PIC Divisi',
            'phone' => '081234567890',
            'source' => 'website',
            'priority' => 'medium',
            'assigned_sales_id' => $sales->id,
            'action' => 'send',
        ])->assertRedirect(route('admin.pra-leads.index'));

        $praLead = PraLead::where('instansi', 'Customer Divisi Test')->firstOrFail();

        $this->actingAs($sales)
            ->post(route('sales.request-masuk.accept', $praLead))
            ->assertRedirect(route('sales.leads.index'));

        $lead = Lead::where('pra_lead_id', $praLead->id)->firstOrFail();
        $this->assertSame('Research and Development', $lead->division);
        $this->assertSame('Research and Development', $lead->customer?->division);
    }

    public function test_activity_pipeline_selection_updates_customer_pipeline(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $customer = Customer::create([
            'name' => 'Customer Pipeline Activity',
            'pipeline_stage' => 'identify',
            'sales_id' => $sales->id,
        ]);

        $this->actingAs($sales)->post(route('activities.store'), [
            'customer_id' => $customer->id,
            'type' => 'follow_up',
            'title' => 'Follow up kebutuhan',
            'activity_date' => now()->format('Y-m-d'),
            'pipeline_stage' => 'follow_up',
            'status' => 'scheduled',
        ])->assertRedirect(route('activities.index'));

        $this->assertSame('follow_up', $customer->fresh()->pipeline_stage);
        $this->assertSame(
            'follow_up',
            Activity::where('customer_id', $customer->id)->latest('id')->value('pipeline_stage')
        );
    }

    public function test_sidebar_badges_match_notifications_for_the_sales_user(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);

        PraLead::create([
            'code' => 'PRA-BADGE-1',
            'instansi' => 'Request Badge Test',
            'pic_name' => 'PIC Badge',
            'phone' => '081200000001',
            'source' => 'website',
            'priority' => 'medium',
            'status' => 'waiting_acceptance',
            'assigned_sales_id' => $sales->id,
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($sales)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Request masuk perlu respon');
        $response->assertSeeInOrder([
            'bi bi-inbox',
            'Request Masuk',
            'side-badge',
        ], false);
    }

    public function test_design_request_can_load_customer_pic_and_project_from_master_data(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $customer = Customer::create([
            'code' => 'CUST-MASTER-DR',
            'name' => 'Customer Master Design',
            'pipeline_stage' => 'identify',
            'sales_id' => $sales->id,
        ]);
        $customer->pics()->create([
            'name' => 'PIC Master Design',
            'is_primary' => true,
        ]);
        Lead::create([
            'code' => 'LD-MASTER-DR',
            'customer_id' => $customer->id,
            'instansi' => $customer->name,
            'pic_name' => 'PIC Master Design',
            'phone' => '081200000002',
            'lab_name' => 'Proyek Master Laboratorium',
            'scope_items' => ['Wall Bench'],
            'priority' => 'high',
            'status' => 'aktif',
            'stage' => 'lead',
            'sales_id' => $sales->id,
        ]);

        $response = $this->actingAs($sales)->get(route('sales.design-requests.create'));

        $response->assertOk();
        $response->assertSee('Ambil dari Master Lead / Customer');
        $response->assertSee('data-customer="Customer Master Design"', false);
        $response->assertSee('data-pic="PIC Master Design"', false);
        $response->assertSee('data-project="Proyek Master Laboratorium"', false);
    }

    public function test_design_request_other_scope_requires_detail_and_saves_urgency(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $drafter = User::factory()->create(['role' => 'drafter']);
        $customer = Customer::create([
            'name' => 'Customer Scope Lainnya',
            'pipeline_stage' => 'identify',
            'sales_id' => $sales->id,
        ]);
        $payload = [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'pic_name' => 'PIC Scope',
            'project_name' => 'Proyek Scope Khusus',
            'request_date' => now()->format('Y-m-d'),
            'deadline' => now()->addDays(7)->format('Y-m-d'),
            'priority' => 'urgent',
            'short_description' => 'Desain kebutuhan khusus.',
            'detail_need' => 'Detail kebutuhan khusus customer.',
            'scope_checklist' => ['Lainnya'],
            'production_pic_id' => $drafter->id,
            'action' => 'send',
        ];

        $this->actingAs($sales)
            ->from(route('sales.design-requests.create'))
            ->post(route('sales.design-requests.store'), $payload)
            ->assertRedirect(route('sales.design-requests.create'))
            ->assertSessionHasErrors('scope_other');

        $this->actingAs($sales)
            ->post(route('sales.design-requests.store'), $payload + [
                'scope_other' => 'Pass Box Custom',
            ])
            ->assertRedirect(route('sales.design-requests.index'));

        $designRequest = DesignRequest::where('project_name', 'Proyek Scope Khusus')->firstOrFail();
        $this->assertSame('urgent', $designRequest->priority);
        $this->assertSame(['Lainnya: Pass Box Custom'], $designRequest->scope_checklist);
    }
}

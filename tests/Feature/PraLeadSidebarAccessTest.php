<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\DesignRequest;
use App\Models\PraLead;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PraLeadSidebarAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_empty_data_has_no_sidebar_badges_for_any_role(): void
    {
        foreach (array_keys(User::roles()) as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertSame([], $this->counts($user), $role);
            $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertDontSee('class="side-badge"', false);
        }
    }

    public function test_only_administrator_sales_admin_and_spv_can_manage_pra_leads(): void
    {
        foreach (array_keys(User::roles()) as $role) {
            $user = User::factory()->create(['role' => $role]);
            $allowed = in_array($role, ['administrator', 'sales', 'sales_admin', 'sales_spv'], true);
            $payload = ['instansi' => 'Prospek '.$role, 'pic_name' => 'PIC', 'phone' => '08123456789', 'source' => 'distributor', 'priority' => 'medium'];
            $page = $this->actingAs($user)->get(route('admin.pra-leads.index'));
            $response = $this->post(route('admin.pra-leads.store'), $payload);
            if ($allowed) {
                $page->assertOk();
                $response->assertSessionHasNoErrors()->assertRedirect(route('admin.pra-leads.index'));
                $lead = PraLead::where('instansi', $payload['instansi'])->firstOrFail();
                $this->put(route('admin.pra-leads.update', $lead), $payload + ['admin_note' => 'Updated'])->assertSessionHasNoErrors()->assertRedirect();
                $this->assertSame('Updated', $lead->fresh()->admin_note);
                $this->delete(route('admin.pra-leads.destroy', $lead))->assertRedirect();
            } else {
                $page->assertForbidden();
                $response->assertForbidden();
            }
            $sidebar = $this->get(route('profile.edit'))->assertOk()->getContent();
            $this->assertSame($allowed, str_contains($sidebar, 'href="'.route('admin.pra-leads.index').'"'));
        }
    }

    public function test_po_badge_matches_sales_visibility_and_status(): void
    {
        $sales = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role' => 'administrator']);
        PurchaseOrderRequest::create(['code' => 'OTHER-PO', 'requested_by' => $other->id, 'status' => 'submitted']);
        $this->assertSame(1, $this->counts($sales)['admin.purchase-order-requests.*'] ?? 0);
        $this->actingAs($sales)->get(route('admin.purchase-order-requests.index'))->assertOk()->assertViewHas('requests', fn ($rows) => $rows->total() === 1);

        PurchaseOrderRequest::create(['code' => 'OWN-PO', 'requested_by' => $sales->id, 'status' => 'submitted']);
        PurchaseOrderRequest::create(['code' => 'DRAFT-PO', 'requested_by' => $sales->id, 'status' => 'draft']);
        $quote = Quotation::create(['customer_name' => 'Customer', 'project_name' => 'Project', 'sales_id' => $sales->id]);
        PurchaseOrderRequest::create(['code' => 'QUOTE-PO', 'quotation_id' => $quote->id, 'requested_by' => $other->id, 'status' => 'submitted']);
        $this->assertSame(3, $this->counts($sales)['admin.purchase-order-requests.*']);
        $this->assertSame(3, $this->counts($admin)['admin.purchase-order-requests.*']);
        $this->actingAs($sales)->get(route('admin.purchase-order-requests.index', ['status' => 'submitted']))->assertOk()->assertViewHas('requests', fn ($rows) => $rows->total() === 3);
    }

    public function test_design_badges_exclude_unassigned_other_and_deleted_records(): void
    {
        $drafter = User::factory()->create(['role' => 'drafter']);
        $other = User::factory()->create(['role' => 'drafter']);
        $sales = User::factory()->create();
        $production = User::factory()->create(['role' => 'production']);
        foreach ([null, $other->id, $drafter->id] as $pic) {
            DesignRequest::create(['customer_name' => 'Customer', 'project_name' => 'Project', 'production_pic_id' => $pic, 'status' => 'assigned']);
        }
        $deleted = DesignRequest::create(['customer_name' => 'Deleted', 'project_name' => 'Deleted', 'production_pic_id' => $drafter->id, 'status' => 'assigned']);
        $deleted->delete();
        DesignRequest::create(['customer_name' => 'Completed', 'project_name' => 'Completed', 'sales_id' => $sales->id, 'status' => 'completed', 'submitted_at' => now()]);
        DesignRequest::create(['customer_name' => 'Drawing', 'project_name' => 'Drawing', 'status' => 'drawing_uploaded']);
        $this->assertSame(1, $this->counts($drafter)['drafter.design-requests.*']);
        $this->assertSame(1, $this->counts($production)['drafter.design-requests.*']);
        $counts = $this->counts($sales);
        $this->assertSame(1, $counts['sales.design-requests.*']);
        $this->assertSame(0, $counts['sales.quotations.*'] ?? 0);
    }

    public function test_overdue_notification_opens_matching_activity_list(): void
    {
        $sales = User::factory()->create();
        foreach (['scheduled', 'completed', 'cancelled'] as $status) {
            Activity::create(['title' => 'Activity '.$status, 'type' => 'call', 'sales_id' => $sales->id, 'activity_date' => today()->subDay(), 'status' => $status]);
        }
        $this->assertSame(1, $this->counts($sales)['activities.*']);
        $this->actingAs($sales)->get(route('activities.index', ['period' => 'overdue']))->assertOk()
            ->assertViewHas('activities', fn ($rows) => $rows->total() === 1)
            ->assertSee('Aktivitas Terlambat');
    }

    private function counts(User $user): array
    {
        $counts = [];
        View::composer('layouts.app', function ($view) use (&$counts) {
            $counts = $view->getData()['sidebarNotificationCounts'];
        });
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();

        return $counts;
    }
}

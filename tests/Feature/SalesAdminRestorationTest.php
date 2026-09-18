<?php

namespace Tests\Feature;

use App\Models\PurchaseOrderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SalesAdminRestorationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sales_admin_menus_and_system_boundaries(): void
    {
        $admin = User::factory()->create(['role' => 'sales_admin']);
        $this->assertTrue($admin->isSalesAdmin());
        $this->assertTrue($admin->canManageBackOffice());
        $this->assertFalse($admin->isAdministrator());
        foreach (['dashboard', 'pipeline.index', 'admin.pra-leads.index', 'admin.assignment.index',
            'admin.purchase-order-requests.index', 'admin.invoices.index', 'sales.customers.index',
            'administration.project-monitoring.index', 'activities.index', 'calendar.index',
            'documents.index', 'admin.users.index', 'profile.edit'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
        $this->get(route('admin.system-settings.index'))->assertForbidden();
        $this->get(route('admin.item-masters.index'))->assertForbidden();
        $superadmin = User::factory()->create(['role' => 'administrator']);
        $this->delete(route('admin.users.destroy', $superadmin))->assertForbidden();
        $this->post(route('admin.users.store'), [
            'name' => 'Forbidden Admin', 'email' => 'forbidden-admin@example.test',
            'role' => 'administrator', 'password' => 'test-password', 'password_confirmation' => 'test-password',
        ])->assertSessionHasErrors('role');
    }

    public function test_administrator_can_assign_sales_admin_from_manage_user(): void
    {
        $superadmin = User::factory()->create(['role' => 'administrator']);
        $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Sales Admin Test', 'email' => 'new-sales-admin@example.test',
            'role' => 'sales_admin', 'password' => 'test-password', 'password_confirmation' => 'test-password',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'new-sales-admin@example.test', 'role' => 'sales_admin']);
    }

    public function test_sales_can_request_po_but_only_back_office_can_process_accurate(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $admin = User::factory()->create(['role' => 'sales_admin']);
        $po = PurchaseOrderRequest::create(['code' => 'ROLE-PO', 'requested_by' => $sales->id, 'status' => 'submitted']);
        $this->actingAs($sales)->get(route('admin.purchase-order-requests.create'))->assertOk();
        $this->get(route('admin.purchase-order-requests.show', $po))->assertOk()->assertDontSee('Update Status Accurate');
        $this->put(route('admin.purchase-order-requests.update', $po), ['status' => 'processing_accurate'])->assertForbidden();
        $this->actingAs($admin)->get(route('admin.purchase-order-requests.show', $po))->assertOk()->assertSee('Update Status Accurate');
        $this->put(route('admin.purchase-order-requests.update', $po), ['status' => 'processing_accurate'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('processing_accurate', $po->fresh()->status);
    }

    public function test_restoration_migration_changes_only_confirmed_account(): void
    {
        $target = User::factory()->create(['email' => 'admin@robust.test', 'role' => 'sales', 'job_title' => 'Sales']);
        $other = User::factory()->create(['role' => 'sales']);
        $migration = require database_path('migrations/2026_09_18_000100_restore_sales_admin_account.php');
        $migration->up();
        $this->assertSame('sales_admin', $target->fresh()->role);
        $this->assertSame('Sales Admin', $target->fresh()->job_title);
        $this->assertSame('sales', $other->fresh()->role);
        $migration->up();
        $this->assertSame('sales_admin', $target->fresh()->role);
    }
}

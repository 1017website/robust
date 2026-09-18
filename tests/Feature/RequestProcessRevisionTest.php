<?php

namespace Tests\Feature;

use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestProcessRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_checklists_are_empty_and_saved_legacy_items_are_preserved(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->assertSame([], (new PurchaseOrderRequest)->checklistItems());
        $this->actingAs($sales)->get(route('admin.purchase-order-requests.create'))
            ->assertOk()->assertSee('Belum ada item checklist.')->assertDontSee('Penawaran final sudah siap dikirim');
        $this->post(route('admin.purchase-order-requests.store'), ['action' => 'draft', 'checklist_present' => 1])
            ->assertSessionHasNoErrors();
        $draft = PurchaseOrderRequest::sole();
        $this->assertSame([], $draft->checklistItems());
        $draft->update(['checklist' => ['customer_po' => true]]);
        $this->assertSame([['key' => 'customer_po', 'label' => 'PO customer / bukti order sudah dilampirkan', 'checked' => true]], $draft->fresh()->checklistItems());
    }

    public function test_quotation_index_shows_and_searches_customer_pic(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $customer = Customer::create(['name' => 'Customer Uji', 'sales_id' => $sales->id]);
        $customer->pics()->create(['name' => 'PIC Utama Uji', 'is_primary' => true]);
        Quotation::create(['code' => 'Q-PIC-1', 'project_name' => 'Proyek Uji', 'customer_name' => $customer->name, 'customer_id' => $customer->id, 'pic_name' => 'PIC Penawaran Uji', 'sales_id' => $sales->id]);
        Quotation::create(['code' => 'Q-PIC-2', 'project_name' => 'Proyek Uji', 'customer_name' => $customer->name, 'customer_id' => $customer->id, 'sales_id' => $sales->id]);
        $this->actingAs($sales)->get(route('sales.quotations.index'))->assertOk()
            ->assertSee('PIC Customer')->assertSee('PIC Penawaran Uji')->assertSee('PIC Utama Uji');
        $this->get(route('sales.quotations.index', ['q' => 'PIC Penawaran Uji']))->assertOk()
            ->assertSee('Q-PIC-1')->assertDontSee('Q-PIC-2');
    }

    public function test_sales_can_change_submitted_number_without_losing_document_and_duplicate_is_rejected(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $request = PurchaseOrderRequest::create(['code' => 'RPO-OLD', 'status' => 'submitted', 'customer_po_file' => 'purchase-order-requests/old.pdf']);
        PurchaseOrderRequest::create(['code' => 'RPO-TAKEN', 'status' => 'draft']);
        $this->actingAs($sales)->put(route('admin.purchase-order-requests.document', $request), ['code' => 'RPO-NEW'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('RPO-NEW', $request->fresh()->code);
        $this->assertSame('purchase-order-requests/old.pdf', $request->fresh()->customer_po_file);
        $this->put(route('admin.purchase-order-requests.document', $request), ['code' => 'RPO-TAKEN'])
            ->assertSessionHasErrors('code');
        $this->assertSame('RPO-NEW', $request->fresh()->code);
    }

    public function test_merge_migration_preserves_accounts_and_custom_job_titles(): void
    {
        $legacy = User::factory()->create(['role' => 'sales_admin', 'job_title' => 'Sales Admin']);
        $custom = User::factory()->create(['role' => 'sales_admin', 'job_title' => 'Coordinator']);
        $administrator = User::factory()->create(['role' => 'administrator']);
        $migration = require database_path('migrations/2026_09_18_000200_merge_sales_admin_permissions_into_sales.php');
        $migration->up();
        $migration->up();
        $this->assertSame('sales', $legacy->fresh()->role);
        $this->assertSame('Sales', $legacy->fresh()->job_title);
        $this->assertSame('sales', $custom->fresh()->role);
        $this->assertSame('Coordinator', $custom->fresh()->job_title);
        $this->assertSame('administrator', $administrator->fresh()->role);
    }

    public function test_custom_numbers_are_unique_and_can_be_changed_on_a_draft(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($sales)->post(route('admin.purchase-order-requests.store'), [
            'action' => 'draft', 'code' => 'RPO/CLIENT/001',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $draft = PurchaseOrderRequest::sole();
        $this->assertSame('RPO/CLIENT/001', $draft->code);

        $this->post(route('admin.purchase-order-requests.store'), [
            'action' => 'draft', 'code' => $draft->code,
        ])->assertSessionHasErrors('code');

        $this->put(route('admin.purchase-order-requests.draft', $draft), [
            'action' => 'draft', 'code' => $draft->code,
        ])->assertSessionHasNoErrors();
        $this->put(route('admin.purchase-order-requests.draft', $draft), [
            'action' => 'draft', 'code' => 'RPO/CLIENT/002',
        ])->assertSessionHasNoErrors();
        $this->assertSame('RPO/CLIENT/002', $draft->fresh()->code);
    }

    public function test_automatic_number_skips_a_number_already_entered_manually(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($sales)->post(route('admin.purchase-order-requests.store'), [
            'action' => 'draft', 'code' => 'RPO-'.date('Y').'-0002',
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.purchase-order-requests.store'), [
            'action' => 'draft',
        ])->assertSessionHasNoErrors();
        $this->assertSame('RPO-'.date('Y').'-0003', PurchaseOrderRequest::latest('id')->first()->code);
    }

    public function test_sales_can_upload_and_replace_po_after_submission_but_drafter_cannot(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'sales']);
        $other = User::factory()->create(['role' => 'drafter']);
        $request = PurchaseOrderRequest::create([
            'code' => 'CUSTOM-PO', 'requested_by' => $owner->id, 'status' => 'submitted',
        ]);
        $route = route('admin.purchase-order-requests.document', $request);
        $this->actingAs($owner)->put($route, [
            'customer_po_file' => UploadedFile::fake()->create('po.pdf', 100, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $firstPath = $request->fresh()->customer_po_file;
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($other)->put($route, [
            'customer_po_file' => UploadedFile::fake()->create('other.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
        $this->assertSame($firstPath, $request->fresh()->customer_po_file);

        $this->actingAs($owner)->put($route, [
            'customer_po_file' => UploadedFile::fake()->create('po-new.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->assertNotSame($firstPath, $request->fresh()->customer_po_file);
        Storage::disk('public')->assertExists($request->fresh()->customer_po_file);

        // Ukuran tidak dibatasi aplikasi; berkas besar tetap diterima.
        $this->put($route, [
            'customer_po_file' => UploadedFile::fake()->create('po-besar.pdf', 51200, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        // Format di luar daftar tetap ditolak.
        $this->put($route, [
            'customer_po_file' => UploadedFile::fake()->create('po.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors('customer_po_file');
    }

    public function test_sales_can_delete_wrongly_uploaded_po_document(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'sales']);
        $request = PurchaseOrderRequest::create([
            'code' => 'REMOVE-PO', 'requested_by' => $owner->id, 'status' => 'submitted',
        ]);
        $route = route('admin.purchase-order-requests.document', $request);
        $this->actingAs($owner)->put($route, [
            'customer_po_file' => UploadedFile::fake()->create('salah-upload.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $path = $request->fresh()->customer_po_file;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($owner)->put($route, ['code' => 'REMOVE-PO', 'action' => 'remove_document'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($request->fresh()->customer_po_file);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('REMOVE-PO', $request->fresh()->code);
    }
}

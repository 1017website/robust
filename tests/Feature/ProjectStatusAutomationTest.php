<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceTerm;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Status Project tidak lagi diisi manual: Berjalan ditetapkan saat Project diajukan,
 * Lunas mengikuti pelunasan invoice, dan hanya pembatalan yang tetap keputusan manusia.
 */
class ProjectStatusAutomationTest extends TestCase
{
    use DatabaseTransactions;

    protected function runningProject(User $sales): PurchaseOrderRequest
    {
        $quotation = Quotation::create([
            'code' => 'Q-AUTO-'.uniqid(),
            'customer_name' => 'PT Otomatis',
            'project_name' => 'Lab Otomatis',
            'status' => 'request_po_created',
            'sales_id' => $sales->id,
            'grand_total' => 10000000,
        ]);

        return PurchaseOrderRequest::create([
            'code' => 'AUTO-'.uniqid(),
            'quotation_id' => $quotation->id,
            'requested_by' => $sales->id,
            'customer_name' => $quotation->customer_name,
            'status' => 'po_created',
        ]);
    }

    protected function invoiceFor(PurchaseOrderRequest $requestPo, float $total): Invoice
    {
        return Invoice::create([
            'code' => 'INV-AUTO-'.uniqid(),
            'purchase_order_request_id' => $requestPo->id,
            'invoice_date' => today(),
            'customer_name' => $requestPo->customer_name,
            'project_name' => $requestPo->quotation?->project_name ?: 'Lab Otomatis',
            'project_number' => $requestPo->projectNumber(),
            'grand_total' => $total,
            'paid_total' => 0,
            'status' => 'issued',
        ]);
    }

    public function test_project_becomes_paid_once_every_invoice_term_is_settled(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $requestPo = $this->runningProject($admin);
        $invoice = $this->invoiceFor($requestPo, 10000000);

        $first = InvoiceTerm::create(['invoice_id' => $invoice->id, 'term_number' => 1, 'amount' => 6000000, 'status' => 'issued']);
        $second = InvoiceTerm::create(['invoice_id' => $invoice->id, 'term_number' => 2, 'amount' => 4000000, 'status' => 'issued']);

        // Termin pertama lunas: Project masih berjalan.
        $this->actingAs($admin)->put(route('admin.invoices.terms.update', [$invoice, $first]), ['status' => 'paid'])
            ->assertRedirect();
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertSame('po_created', $requestPo->fresh()->status);

        // Termin kedua lunas: Project otomatis menjadi Lunas tanpa disentuh manual.
        $this->actingAs($admin)->put(route('admin.invoices.terms.update', [$invoice, $second]), ['status' => 'paid'])
            ->assertRedirect();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('paid', $requestPo->fresh()->status);
    }

    public function test_correcting_a_payment_reopens_the_project(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $requestPo = $this->runningProject($admin);
        $invoice = $this->invoiceFor($requestPo, 5000000);
        $term = InvoiceTerm::create(['invoice_id' => $invoice->id, 'term_number' => 1, 'amount' => 5000000, 'status' => 'issued']);

        $this->actingAs($admin)->put(route('admin.invoices.terms.update', [$invoice, $term]), ['status' => 'paid'])->assertRedirect();
        $this->assertSame('paid', $requestPo->fresh()->status);

        $this->actingAs($admin)->put(route('admin.invoices.terms.update', [$invoice, $term]), [
            'status' => 'partial',
            'paid_amount' => 1000000,
        ])->assertRedirect();

        $this->assertSame('po_created', $requestPo->fresh()->status);
    }

    public function test_cancelling_and_reactivating_is_the_only_manual_status_change(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $requestPo = $this->runningProject($admin);

        $this->actingAs($admin)->put(route('admin.purchase-order-requests.status', $requestPo), [
            'action' => 'cancel',
            'reason' => 'Customer menunda proyek.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $requestPo->fresh()->status);
        $this->assertSame('Customer menunda proyek.', $requestPo->fresh()->accurate_note);

        $this->actingAs($admin)->put(route('admin.purchase-order-requests.status', $requestPo), ['action' => 'reactivate'])
            ->assertRedirect();
        $this->assertSame('po_created', $requestPo->fresh()->status);
    }

    public function test_a_paid_project_cannot_be_cancelled(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $requestPo = $this->runningProject($admin);
        $requestPo->update(['status' => 'paid']);

        $this->actingAs($admin)->put(route('admin.purchase-order-requests.status', $requestPo), ['action' => 'cancel'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('paid', $requestPo->fresh()->status);
    }

    public function test_a_cancelled_project_is_not_reopened_by_invoice_activity(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $requestPo = $this->runningProject($admin);
        $invoice = $this->invoiceFor($requestPo, 5000000);
        $term = InvoiceTerm::create(['invoice_id' => $invoice->id, 'term_number' => 1, 'amount' => 5000000, 'status' => 'issued']);

        $requestPo->update(['status' => 'cancelled']);

        $this->actingAs($admin)->put(route('admin.invoices.terms.update', [$invoice, $term]), ['status' => 'paid'])
            ->assertRedirect();

        $this->assertSame('cancelled', $requestPo->fresh()->status);
    }

    public function test_the_status_panel_no_longer_asks_for_accurate_fields(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $requestPo = $this->runningProject($admin);

        $this->actingAs($admin)->get(route('admin.purchase-order-requests.show', $requestPo))
            ->assertOk()
            ->assertSee('Status Project')
            ->assertSee('Batalkan Project')
            ->assertDontSee('No PO Accurate')
            ->assertDontSee('Tanggal PO Accurate')
            ->assertDontSee('Catatan Accurate')
            ->assertDontSee('Update Status Accurate');
    }
}

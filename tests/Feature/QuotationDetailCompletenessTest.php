<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPic;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Detail penawaran harus memuat konteks yang dibutuhkan sales tanpa pindah halaman:
 * kontak customer, nilai deal, catatan, dan record yang terhubung.
 */
class QuotationDetailCompletenessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_detail_shows_customer_contact_notes_and_linked_records(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);

        $customer = Customer::create([
            'name' => 'PT Detail Lengkap',
            'category' => 'Industri',
            'email' => 'kontak@detail.test',
            'phone' => '0218889990',
            'address' => 'Jl. Contoh Raya No. 10',
            'city' => 'Sidoarjo',
            'pipeline_stage' => 'identify',
            'sales_id' => $sales->id,
        ]);

        CustomerPic::create([
            'customer_id' => $customer->id,
            'name' => 'Rahma Pratiwi',
            'position' => 'Kepala Lab',
            'phone' => '081200001111',
            'email' => 'rahma@detail.test',
            'is_primary' => true,
        ]);

        $quotation = Quotation::create([
            'code' => 'Q-DETAIL-0001',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'project_name' => 'Lab Terpadu',
            'status' => 'customer_accepted',
            'sales_id' => $sales->id,
            'creation_mode' => 'upload',
            'grand_total' => 125000000,
            'priority' => 'high',
            'currency' => 'IDR',
            'customer_note' => 'Harga sudah termasuk instalasi.',
            'internal_note' => 'Margin tipis, jangan diskon lagi.',
            'customer_response_note' => 'Customer setuju lewat telepon.',
            'customer_response_at' => now(),
        ]);

        $requestPo = PurchaseOrderRequest::create([
            'code' => '070926',
            'quotation_id' => $quotation->id,
            'requested_by' => $sales->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($sales)->get(route('sales.quotations.show', $quotation))->assertOk();

        // Kontak customer dan PIC.
        $response->assertSee('Rahma Pratiwi')
            ->assertSee('Kepala Lab')
            ->assertSee('081200001111')
            ->assertSee('rahma@detail.test')
            ->assertSee('Jl. Contoh Raya No. 10')
            ->assertSee('Sidoarjo');

        // Nilai deal tetap tampil walaupun penawaran berupa file unggahan.
        $response->assertSee('Ringkasan Harga')
            ->assertSee('Rp 125.000.000');

        // Catatan dan prioritas.
        $response->assertSee('Harga sudah termasuk instalasi.')
            ->assertSee('Margin tipis, jangan diskon lagi.')
            ->assertSee('Customer setuju lewat telepon.')
            ->assertSee('Tinggi');

        // Record yang terhubung.
        $response->assertSee('Terhubung Dengan')
            ->assertSee($requestPo->code)
            ->assertSee(route('admin.purchase-order-requests.show', $requestPo), false);
    }

    public function test_detail_states_plainly_when_linked_records_do_not_exist_yet(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);

        $quotation = Quotation::create([
            'code' => 'Q-DETAIL-0002',
            'customer_name' => 'PT Tanpa Relasi',
            'project_name' => 'Lab Kecil',
            'status' => 'ready',
            'sales_id' => $sales->id,
            'grand_total' => 5000000,
        ]);

        $this->actingAs($sales)->get(route('sales.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Tanpa Design Request')
            ->assertSee('Belum dibuat')
            ->assertSee('Belum dikirim');
    }
}

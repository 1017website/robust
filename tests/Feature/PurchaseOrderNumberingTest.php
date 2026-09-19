<?php

namespace Tests\Feature;

use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PurchaseOrderNumberGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Nomor PO berformat CCMMYY: urutan 2 digit, bulan 2 digit, tahun 2 digit.
 * Urutan direset tiap bulan; nomor awal diatur sekali lewat System Settings.
 */
class PurchaseOrderNumberingTest extends TestCase
{
    use DatabaseTransactions;

    protected function generator(): PurchaseOrderNumberGenerator
    {
        return app(PurchaseOrderNumberGenerator::class);
    }

    protected function makeRequestPo(string $code, ?Quotation $quotation = null): PurchaseOrderRequest
    {
        return PurchaseOrderRequest::create([
            'code' => $code,
            'quotation_id' => $quotation?->id,
            'status' => 'submitted',
        ]);
    }

    public function test_first_number_of_the_month_starts_at_one(): void
    {
        $date = Carbon::create(2026, 9, 19);

        $this->assertSame('010926', $this->generator()->next($date));
    }

    public function test_counter_continues_from_the_highest_number_in_the_same_month(): void
    {
        $date = Carbon::create(2026, 9, 19);
        $this->makeRequestPo('010926');
        $this->makeRequestPo('070926');

        $this->assertSame('080926', $this->generator()->next($date));
    }

    public function test_counter_resets_when_the_month_changes(): void
    {
        $this->makeRequestPo('090926');
        $this->makeRequestPo('120926');

        $this->assertSame('011026', $this->generator()->next(Carbon::create(2026, 10, 1)));
    }

    public function test_old_format_numbers_are_ignored_by_the_counter(): void
    {
        $this->makeRequestPo('RPO-2026-0926');
        $this->makeRequestPo('RPO-2026-0007');

        $this->assertSame('010926', $this->generator()->next(Carbon::create(2026, 9, 19)));
    }

    public function test_start_counter_applies_once_then_sequence_continues_normally(): void
    {
        SystemSetting::putValue(PurchaseOrderNumberGenerator::START_KEY, 48, 'number');
        SystemSetting::putValue(PurchaseOrderNumberGenerator::APPLIED_KEY, '0', 'boolean');

        $date = Carbon::create(2026, 9, 19);

        $this->assertSame('480926', $this->generator()->next($date));
        $this->makeRequestPo('480926');

        // Nomor awal sudah terpakai, jadi bulan berikutnya mulai dari 01 lagi.
        $this->assertSame('490926', $this->generator()->next($date));
        $this->assertSame('011026', $this->generator()->next(Carbon::create(2026, 10, 1)));
    }

    public function test_preview_does_not_consume_the_start_counter(): void
    {
        SystemSetting::putValue(PurchaseOrderNumberGenerator::START_KEY, 30, 'number');
        SystemSetting::putValue(PurchaseOrderNumberGenerator::APPLIED_KEY, '0', 'boolean');

        $date = Carbon::create(2026, 9, 19);

        $this->assertSame('300926', $this->generator()->preview($date));
        $this->assertFalse($this->generator()->startApplied());
        $this->assertSame('300926', $this->generator()->preview($date));
    }

    public function test_manual_number_is_skipped_so_codes_stay_unique(): void
    {
        $date = Carbon::create(2026, 9, 19);
        $this->makeRequestPo('020926');
        // Nomor 01 dipakai manual walaupun urutan tertinggi ada di 02.
        $this->makeRequestPo('030926');

        $this->assertSame('040926', $this->generator()->next($date));
    }

    public function test_counter_above_ninety_nine_grows_to_three_digits(): void
    {
        $this->makeRequestPo('990926');

        $this->assertSame('1000926', $this->generator()->next(Carbon::create(2026, 9, 19)));
    }

    public function test_administrator_can_change_the_start_counter_from_system_settings(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        SystemSetting::putValue(PurchaseOrderNumberGenerator::APPLIED_KEY, '1', 'boolean');

        $this->actingAs($admin)
            ->put(route('admin.system-settings.numbering'), ['po_number_start_counter' => 75])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(75, $this->generator()->startCounter());
        // Mengubah nilainya membuat nomor awal berlaku lagi.
        $this->assertFalse($this->generator()->startApplied());
    }

    public function test_start_counter_is_validated_and_closed_to_other_roles(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $sales = User::factory()->create(['role' => 'sales']);

        $this->actingAs($admin)
            ->put(route('admin.system-settings.numbering'), ['po_number_start_counter' => 0])
            ->assertSessionHasErrors('po_number_start_counter');

        $this->actingAs($sales)
            ->put(route('admin.system-settings.numbering'), ['po_number_start_counter' => 5])
            ->assertForbidden();
    }
}

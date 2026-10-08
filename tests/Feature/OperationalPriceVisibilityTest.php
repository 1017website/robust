<?php

namespace Tests\Feature;

use App\Models\DesignRequest;
use App\Models\ItemMaster;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalPriceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function design(User $sales): DesignRequest
    {
        $design = DesignRequest::create([
            'code' => 'DR-PRICE', 'sales_id' => $sales->id, 'customer_name' => 'Customer',
            'project_name' => 'Project Harga', 'status' => 'completed', 'request_date' => today(),
            'cost_material' => 123456789, 'cost_production' => 234567891, 'cost_installation' => 345678912,
        ]);
        $design->items()->create([
            'name' => 'Meja Teknis', 'qty' => 2, 'unit' => 'Unit', 'unit_price' => 456789123,
            'specification' => "[Material]\nWarna: Putih\n@ Baut | 4 | pcs | 987654321",
        ]);

        return $design;
    }

    public function test_only_sales_roles_can_view_prices(): void
    {
        foreach (['sales', 'sales_admin'] as $role) {
            $this->assertTrue((new User(['role' => $role]))->canViewPrices());
        }
        foreach (['production', 'qc', 'drafter', 'delivery', 'administration', 'sales_spv', 'administrator'] as $role) {
            $this->assertFalse((new User(['role' => $role]))->canViewPrices());
        }
    }

    public function test_production_design_page_omits_costs_price_inputs_and_raw_specification(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $design = $this->design($sales);
        $this->actingAs(User::factory()->create(['role' => 'production']))
            ->get(route('drafter.design-requests.show', $design))->assertOk()
            ->assertSee('Meja Teknis')->assertSee('Putih')->assertSee('Baut')
            ->assertDontSee('123456789')->assertDontSee('456789123')->assertDontSee('987654321')
            ->assertDontSee('Rp ')->assertDontSee('name="cost_material"', false)
            ->assertDontSee('][unit_price]', false)->assertDontSee('data-spec-raw', false);
        $this->actingAs($sales)->get(route('drafter.design-requests.show', $design))->assertOk()
            ->assertSee('name="cost_material"', false)->assertSee('456789123')->assertSee('987654321');
    }

    public function test_workspace_and_both_qc_checklists_hide_prices_from_non_sales(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = Quotation::create(['code' => 'Q-PRICE', 'sales_id' => $sales->id, 'customer_name' => 'Customer', 'project_name' => 'Project Harga', 'status' => 'won']);
        $quotation->items()->create([
            'name' => 'Meja Teknis', 'qty' => 2, 'unit' => 'Unit', 'unit_price' => 456789123,
            'specification' => "[Material]\nWarna: Putih\n@ Baut | 4 | pcs | 987654321",
        ]);
        $project = Project::create(['code' => 'PRJ-PRICE', 'name' => 'Project Harga', 'quotation_id' => $quotation->id, 'project_manager_id' => $sales->id, 'project_value' => 123456789]);
        $project->workflow()->create(['production_status' => 'production_finished', 'qc_completed' => true]);
        foreach (['production', 'qc', 'administrator'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('project-workspace.show', $project))->assertOk()
                ->assertSee('QC Produksi')->assertSee('QC Pemasangan')->assertSee('Meja Teknis')->assertSee('Baut')
                ->assertDontSee('Nilai Project')->assertDontSee('Rp ')->assertDontSee('987654321')->assertDontSee('456789123');
        }
        $this->actingAs($sales)->get(route('project-workspace.show', $project))->assertOk()
            ->assertSee('Nilai Project')->assertSee('Rp 456.789.123')->assertSee('Rp 987.654.321');
    }

    public function test_production_master_item_hides_prices_and_preserves_them_during_technical_updates(): void
    {
        $master = ItemMaster::create([
            'code' => 'ITM-PRICE', 'name' => 'Meja Teknis', 'category' => 'Meja', 'unit' => 'Unit',
            'default_cost_price' => 123456789, 'default_margin' => 35,
            'specification' => "Warna: Putih\n@ Baut | 4 | pcs | 987654321", 'is_active' => true,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'production']))->get(route('admin.item-masters.index'))->assertOk()
            ->assertDontSee('123456789')->assertDontSee('987654321')->assertDontSee('name="default_cost_price"', false)
            ->assertDontSee('name="default_margin"', false)->assertDontSee('Rp ');
        $this->put(route('admin.item-masters.update', $master), [
            'code' => 'ITM-PRICE', 'name' => 'Meja Teknis Baru', 'category' => 'Meja', 'unit' => 'Unit',
            'default_cost_price' => 1, 'default_margin' => 1, 'is_active' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('123456789.00', $master->fresh()->default_cost_price);
        $this->assertSame('35.00', $master->fresh()->default_margin);
        $this->assertSame('Meja Teknis Baru', $master->fresh()->name);
    }
}

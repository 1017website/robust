<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectQcStagesTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $quotation = Quotation::create(['code' => 'Q-QC-STAGES', 'customer_name' => 'Customer QC', 'project_name' => 'Project QC', 'sales_id' => $sales->id, 'status' => 'won']);
        $quotation->items()->create(['name' => 'Meja Laboratorium', 'qty' => 2, 'unit' => 'Unit', 'unit_price' => 1000000, 'subtotal' => 2000000, 'specification' => 'Warna putih']);
        $project = Project::create(['code' => 'PRJ-QC-STAGES', 'name' => 'Project QC Terpisah', 'quotation_id' => $quotation->id, 'project_manager_id' => $sales->id, 'status' => 'ongoing']);
        $project->workflow()->create(['production_status' => 'production_finished', 'production_progress' => 100]);

        return $project;
    }

    private function checks(Project $project, bool $installation = false): array
    {
        return collect(ProjectWorkflow::qcChecklistDefinition($project, false, $installation))
            ->flatMap(fn ($item) => collect($item['checks'])->pluck('key'))->mapWithKeys(fn ($key) => [$key => 1])->all();
    }

    public function test_stages_keep_progress_notes_checklists_and_files_separate(): void
    {
        Storage::fake('public');
        $qc = User::factory()->create(['role' => 'qc']);
        $project = $this->project();
        $this->actingAs($qc)->put(route('project-workflow.qc', $project), [
            'qc_progress' => 45, 'qc_note' => 'Finishing diperiksa',
            'qc_checklist' => [], 'qc_document' => UploadedFile::fake()->createWithContent('produksi.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $workflow = $project->workflow->fresh();
        $this->assertSame(45, $workflow->qc_progress);
        $this->assertSame(0, $workflow->qc_installation_progress);
        $this->assertFalse($workflow->qc_installation_completed);
        $productionFile = $workflow->qc_document_path;

        $this->put(route('project-workflow.qc', $project), [
            'qc_completed' => 1, 'qc_progress' => 45, 'qc_note' => 'Produksi lolos', 'qc_checklist' => $this->checks($project),
        ])->assertSessionHasNoErrors();
        $this->put(route('project-workflow.qc-installation', $project), [
            'qc_installation_progress' => 35, 'qc_installation_note' => 'Sambungan sedang diperiksa',
            'qc_installation_document' => UploadedFile::fake()->createWithContent('pemasangan.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertSessionHasNoErrors();
        $workflow->refresh();
        $this->assertSame(100, $workflow->qc_progress);
        $this->assertTrue($workflow->qc_completed);
        $this->assertSame(35, $workflow->qc_installation_progress);
        $this->assertSame('Produksi lolos', $workflow->qc_note);
        $this->assertSame($productionFile, $workflow->qc_document_path);
        $this->assertNotSame($productionFile, $workflow->qc_installation_document_path);
        Storage::disk('public')->assertExists([$productionFile, $workflow->qc_installation_document_path]);
        $this->get(route('project-workflow.attachment', [$project, 'qc']))->assertOk();
        $this->get(route('project-workflow.attachment', [$project, 'qc-installation']))->assertOk();
        $this->put(route('project-workflow.qc-installation', $project), [
            'qc_installation_completed' => 1, 'qc_installation_progress' => 35, 'qc_installation_checklist' => $this->checks($project, true),
        ])->assertSessionHasNoErrors();
        $this->assertSame(100, $workflow->fresh()->qc_installation_progress);
        $this->assertTrue($workflow->fresh()->qc_installation_completed);
    }

    public function test_each_stage_requires_its_own_complete_checklist_and_valid_progress(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['role' => 'qc']));
        $this->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_progress' => 10])->assertUnprocessable();
        $this->putJson(route('project-workflow.qc', $project), ['qc_progress' => 101])->assertJsonValidationErrors('qc_progress');
        $this->putJson(route('project-workflow.qc', $project), ['qc_completed' => 1])->assertJsonValidationErrors('qc_checklist');
        $project->workflow->update(['qc_completed' => true, 'qc_progress' => 100]);
        $this->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_completed' => 1, 'qc_installation_checklist' => $this->checks($project)])
            ->assertJsonValidationErrors('qc_installation_checklist');
        $this->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_progress' => -5])->assertJsonValidationErrors('qc_installation_progress');
        $this->assertFalse($project->workflow->fresh()->qc_installation_completed);
    }

    public function test_delivery_depends_on_production_qc_and_permissions_are_preserved(): void
    {
        $project = $this->project();
        $delivery = User::factory()->create(['role' => 'delivery']);
        $this->actingAs($delivery)->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_progress' => 25])->assertForbidden();
        $this->putJson(route('project-workflow.delivery', $project), ['delivery_status' => 'scheduling'])->assertUnprocessable();
        $this->postJson(route('delivery-orders.store', $project), [])->assertUnprocessable();
        $project->workflow->update(['qc_completed' => true, 'qc_progress' => 100]);
        $this->put(route('project-workflow.delivery', $project), ['delivery_status' => 'scheduling'])->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('delivery-orders.store', $project), [
            'delivery_date' => '2026-10-08', 'delivery_address' => 'Lokasi Customer',
            'items' => [['name' => 'Meja Laboratorium', 'qty' => 2, 'unit' => 'Unit']],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNotNull($project->fresh()->deliveryOrder);
        $this->assertFalse($project->workflow->fresh()->qc_installation_completed);
    }

    public function test_workspace_and_monitoring_render_both_stages_with_progress(): void
    {
        $project = $this->project();
        $project->workflow->update(['qc_completed' => true, 'qc_progress' => 100, 'qc_installation_progress' => 35]);
        $this->actingAs(User::factory()->create(['role' => 'administrator']));
        $response = $this->get(route('project-workspace.show', $project))->assertOk()
            ->assertSee('QC Produksi')->assertSee('QC Pemasangan')
            ->assertSee('name="qc_progress"', false)->assertSee('name="qc_installation_progress"', false)
            ->assertSee('aria-label="Progress QC Pemasangan"', false);
        File::ensureDirectoryExists(base_path('tmp'));
        file_put_contents(base_path('tmp/qc-workspace-test.html'), $response->getContent());
        $this->get(route('administration.project-monitoring.index'))->assertOk()->assertSee('QC Produksi')->assertSee('QC Pemasangan')->assertSee('35%');
    }

    public function test_operational_progress_includes_both_qc_stages(): void
    {
        $workflow = new ProjectWorkflow(['production_progress' => 100, 'qc_progress' => 50, 'qc_installation_progress' => 0]);
        $before = $workflow->completionPercent();
        $workflow->qc_installation_progress = 50;
        $this->assertGreaterThan($before, $workflow->completionPercent());
        $workflow->fill(['production_progress' => 100, 'qc_completed' => true, 'qc_installation_completed' => true, 'delivery_status' => 'completed']);
        $this->assertSame(100, $workflow->completionPercent());
    }

    public function test_migration_preserves_existing_qc_as_production_qc(): void
    {
        $project = $this->project();
        $migration = require database_path('migrations/2026_10_08_000100_split_production_and_installation_qc.php');
        $migration->down();
        $project->workflow->update(['qc_completed' => true, 'qc_note' => 'QC lama lolos', 'qc_document_path' => 'qc/lama.pdf']);
        $migration->up();
        $workflow = $project->workflow->fresh();
        $this->assertTrue($workflow->qc_completed);
        $this->assertSame(100, $workflow->qc_progress);
        $this->assertSame('QC lama lolos', $workflow->qc_note);
        $this->assertSame('qc/lama.pdf', $workflow->qc_document_path);
        $this->assertFalse($workflow->qc_installation_completed);
        $this->assertSame(0, $workflow->qc_installation_progress);
    }
}

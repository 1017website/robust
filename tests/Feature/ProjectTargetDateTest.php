<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTargetDateTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        $project = Project::create(['code' => 'PRJ-TARGET-CHANGE', 'name' => 'Project Jadwal']);
        $project->workflow()->create([
            'production_status' => 'production_finished', 'production_progress' => 100,
            'production_target_date' => '2026-10-20',
            'qc_completed' => true, 'qc_result' => 'passed', 'qc_target_date' => '2026-10-21',
            'qc_checklist' => ['saved' => true], 'qc_note' => 'Catatan final produksi',
            'qc_document_path' => 'qc/final.pdf',
            'qc_installation_completed' => true, 'qc_installation_result' => 'passed',
            'qc_installation_target_date' => '2026-10-22', 'qc_installation_note' => 'Catatan final pemasangan',
            'qc_installation_checklist' => ['saved' => true], 'delivery_status' => 'completed',
        ]);

        return $project;
    }

    public function test_target_dates_can_change_after_completion_with_history_and_without_changing_qc_results(): void
    {
        $project = $this->project();
        foreach (['production' => 'production', 'qc' => 'qc_production', 'qc-installation' => 'qc_installation'] as $stage => $role) {
            $prefix = str_replace('-', '_', $stage);
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user);
            $before = $project->workflow->fresh()->getAttributes();
            $projectBefore = $project->fresh()->getAttributes();
            $oldDate = $project->workflow->fresh()->{$prefix.'_target_date'}->format('Y-m-d');
            $this->put(route('project-workflow.target-date', [$project, $stage]), [
                $prefix.'_target_date' => '2026-11-01', 'target_date_reason' => 'Jadwal mundur karena lokasi belum siap.',
                'qc_result' => 'failed', 'qc_checklist' => [], 'production_status' => 'stock',
            ])->assertRedirect()->assertSessionHasNoErrors();
            $after = $project->workflow->fresh()->getAttributes();
            unset($before[$prefix.'_target_date'], $before['updated_at'], $after[$prefix.'_target_date'], $after['updated_at']);
            $this->assertSame($before, $after);
            $this->assertSame($projectBefore, $project->fresh()->getAttributes());
            $entry = $project->workflowHistory()->where('action', $prefix.'_target_date_updated')->sole();
            $this->assertSame($user->id, $entry->user_id);
            $this->assertNotNull($entry->created_at);
            $this->assertSame($oldDate, $entry->meta['before']['target_date']);
            $this->assertSame('2026-11-01', $entry->meta['after']['target_date']);
            $this->assertSame('Jadwal mundur karena lokasi belum siap.', $entry->meta['reason']);
            $this->get(route('project-workspace.show', $project))->assertOk()
                ->assertSee('Ubah tanggal target selesai')->assertSee('01/11/2026')
                ->assertSee('Jadwal mundur karena lokasi belum siap.')->assertSee($user->name);
            $this->put(route('project-workflow.target-date', [$project, $stage]), [$prefix.'_target_date' => '2026-11-01'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame(1, $project->workflowHistory()->where('action', $prefix.'_target_date_updated')->count());
            $this->put(route('project-workflow.target-date', [$project, $stage]), [$prefix.'_target_date' => '2026-11-05'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $latest = $project->workflowHistory()->where('action', $prefix.'_target_date_updated')->latest('id')->first();
            $this->assertSame('2026-11-01', $latest->meta['before']['target_date']);
            $this->assertSame('2026-11-05', $latest->meta['after']['target_date']);
        }
        $this->assertSame(6, $project->workflowHistory()->count());
    }

    public function test_target_date_changes_enforce_stage_permissions_and_required_valid_dates(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['role' => 'administrator']));
        foreach (['production', 'qc', 'qc-installation'] as $stage) {
            $prefix = str_replace('-', '_', $stage);
            foreach ([[], [$prefix.'_target_date' => '2026-02-30']] as $data) {
                $this->putJson(route('project-workflow.target-date', [$project, $stage]), $data)
                    ->assertJsonValidationErrors($prefix.'_target_date');
            }
        }
        foreach (['production' => ['qc', 'qc-installation'], 'qc_production' => ['production', 'qc-installation'], 'qc_installation' => ['production', 'qc'], 'sales' => ['production', 'qc', 'qc-installation']] as $role => $stages) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            foreach ($stages as $stage) {
                $this->putJson(route('project-workflow.target-date', [$project, $stage]), [str_replace('-', '_', $stage).'_target_date' => '2026-11-01'])
                    ->assertForbidden();
            }
        }
        $this->assertSame(0, $project->workflowHistory()->count());
    }
}

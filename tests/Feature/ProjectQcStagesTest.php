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
        $project->workflow()->create(['production_target_date' => '2026-10-20', 'production_status' => 'production_finished', 'production_progress' => 100, 'delivery_status' => 'delivered']);

        return $project;
    }

    private function checks(Project $project, bool $installation = false): array
    {
        return collect(ProjectWorkflow::qcChecklistDefinition($project, false, $installation))
            ->flatMap(fn ($item) => collect($item['checks'])->pluck('key'))->mapWithKeys(fn ($key) => [$key => 1])->all();
    }

    public function test_different_qc_accounts_can_only_update_their_own_stage_and_history_records_each_user(): void
    {
        $production = User::factory()->create(['role' => 'qc_production']);
        $installation = User::factory()->create(['role' => 'qc_installation']);
        $project = $this->project();

        $this->actingAs($production)->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_target_date' => '2026-10-21', 'qc_installation_result' => 'in_progress', 'qc_installation_progress' => 20])->assertForbidden();
        $this->put(route('project-workflow.qc', $project), ['qc_target_date' => '2026-10-21', 'qc_result' => 'passed', 'qc_completed' => 1, 'qc_checklist' => $this->checks($project), 'qc_note' => 'Lolos oleh QC produksi',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($installation)->putJson(route('project-workflow.qc', $project), ['qc_target_date' => '2026-10-21', 'qc_result' => 'in_progress', 'qc_progress' => 20])->assertForbidden();
        $this->put(route('project-workflow.qc-installation', $project), [
            'qc_installation_target_date' => '2026-10-21', 'qc_installation_result' => 'in_progress',
            'qc_installation_progress' => 35, 'qc_installation_note' => 'Diperiksa oleh QC pemasangan',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $workflow = $project->workflow->fresh();
        $this->assertSame($production->id, $workflow->qc_updated_by);
        $this->assertSame($installation->id, $workflow->qc_installation_updated_by);
        $this->assertSame($production->id, $project->workflowHistory()->where('action', 'qc_updated')->sole()->user_id);
        $this->assertSame($installation->id, $project->workflowHistory()->where('action', 'qc_installation_updated')->sole()->user_id);
        $this->assertSame(100, $workflow->qc_progress);
        $this->assertSame(0, $workflow->qc_installation_progress);
    }

    public function test_qc_accounts_see_their_own_forms_work_queue_and_notifications(): void
    {
        $project = $this->project();
        $project->update(['target_date' => today()->addDays(2)]);
        $project->workflow->update(['delivery_status' => 'scheduling']);
        foreach (['qc_production' => 'qc', 'qc_installation' => 'qc_installation'] as $role => $prefix) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('drafter.projects.index'));
            $this->get(route('drafter.calendar.index'))->assertOk();
            $response = $this->get(route('project-workspace.show', $project))->assertOk()
                ->assertSee('data-qc-check', false)->assertDontSee('name="'.$prefix.'_progress"', false)
                ->assertDontSee('name="'.($prefix === 'qc' ? 'qc_installation' : 'qc').'_progress"', false)
                ->assertDontSee('id="production-tab"', false)->assertDontSee('id="delivery-tab"', false);
            $this->get(route('drafter.projects.index'))->assertOk()->assertSee($user->roleLabel())
                ->assertSee($prefix === 'qc' ? $project->code : 'Belum ada Request Process yang siap ditangani tim QC Pemasangan.');
            if ($prefix === 'qc') {
                $response->assertSee('Project menunggu QC Produksi')->assertDontSee('Project menunggu QC Pemasangan');
                $response->assertSee($project->code.' · Deadline 2 hari lagi');
            } else {
                $response->assertDontSee('Project menunggu QC Produksi')->assertDontSee('Project menunggu QC Pemasangan');
                $response->assertDontSee($project->code.' · Deadline 2 hari lagi');
                $project->workflow->update(['qc_completed' => true, 'qc_progress' => 100]);
                $this->get(route('drafter.projects.index'))->assertOk()->assertDontSee('Buka QC Pemasangan');
                $project->workflow->update(['delivery_status' => 'delivered']);
                $this->get(route('drafter.projects.index'))->assertOk()->assertSee($project->code)->assertSee('Buka QC Pemasangan');
                $this->get(route('project-workspace.show', $project))->assertOk()
                    ->assertSee('Project menunggu QC Pemasangan')->assertDontSee('Project menunggu QC Produksi')
                    ->assertSee($project->code.' · Deadline 2 hari lagi');
            }
        }
    }

    public function test_administrator_can_create_separate_qc_roles_from_manage_user(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'administrator']))
            ->get(route('admin.users.index'))->assertOk()->assertSee('QC Produksi')->assertSee('QC Pemasangan');
        foreach (['qc_production', 'qc_installation'] as $role) {
            $this->post(route('admin.users.store'), [
                'name' => $role, 'email' => $role.'@example.test', 'role' => $role,
                'password' => 'secret123', 'password_confirmation' => 'secret123', 'is_active' => 1,
            ])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertDatabaseHas('users', ['email' => $role.'@example.test', 'role' => $role]);
        }
    }

    public function test_stages_keep_progress_notes_checklists_and_files_separate(): void
    {
        Storage::fake('public');
        $qc = User::factory()->create(['role' => 'qc']);
        $project = $this->project();
        $this->actingAs($qc)->put(route('project-workflow.qc', $project), [
            'qc_target_date' => '2026-10-21', 'qc_result' => 'in_progress',
            'qc_progress' => 45, 'qc_note' => 'Finishing diperiksa',
            'qc_checklist' => [], 'qc_document' => UploadedFile::fake()->createWithContent('produksi.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $workflow = $project->workflow->fresh();
        $this->assertSame(0, $workflow->qc_progress);
        $this->assertSame(0, $workflow->qc_installation_progress);
        $this->assertFalse($workflow->qc_installation_completed);
        $productionFile = $workflow->qc_document_path;

        $this->put(route('project-workflow.qc', $project), ['qc_target_date' => '2026-10-21', 'qc_result' => 'passed', 'qc_completed' => 1, 'qc_progress' => 45, 'qc_note' => 'Produksi lolos', 'qc_checklist' => $this->checks($project),
        ])->assertSessionHasNoErrors();
        $this->put(route('project-workflow.qc-installation', $project), [
            'qc_installation_target_date' => '2026-10-21', 'qc_installation_result' => 'in_progress',
            'qc_installation_progress' => 35, 'qc_installation_note' => 'Sambungan sedang diperiksa',
            'qc_installation_document' => UploadedFile::fake()->createWithContent('pemasangan.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertSessionHasNoErrors();
        $workflow->refresh();
        $this->assertSame(100, $workflow->qc_progress);
        $this->assertTrue($workflow->qc_completed);
        $this->assertSame(0, $workflow->qc_installation_progress);
        $this->assertSame('Produksi lolos', $workflow->qc_note);
        $this->assertSame($productionFile, $workflow->qc_document_path);
        $this->assertNotSame($productionFile, $workflow->qc_installation_document_path);
        Storage::disk('public')->assertExists([$productionFile, $workflow->qc_installation_document_path]);
        $this->get(route('project-workflow.attachment', [$project, 'qc']))->assertOk();
        $this->get(route('project-workflow.attachment', [$project, 'qc-installation']))->assertOk();
        $this->put(route('project-workflow.qc-installation', $project), ['qc_installation_target_date' => '2026-10-21', 'qc_installation_result' => 'passed', 'qc_installation_completed' => 1, 'qc_installation_progress' => 35, 'qc_installation_checklist' => $this->checks($project, true),
        ])->assertSessionHasNoErrors();
        $this->assertSame(100, $workflow->fresh()->qc_installation_progress);
        $this->assertTrue($workflow->fresh()->qc_installation_completed);
    }

    public function test_each_stage_requires_its_own_complete_checklist_and_valid_progress(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['role' => 'qc']));
        $this->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_target_date' => '2026-10-21', 'qc_installation_result' => 'in_progress', 'qc_installation_progress' => 10])->assertUnprocessable();
        $this->putJson(route('project-workflow.qc', $project), ['qc_target_date' => '2026-10-21', 'qc_result' => 'in_progress', 'qc_progress' => 101])->assertJsonValidationErrors('qc_progress');
        $this->putJson(route('project-workflow.qc', $project), ['qc_target_date' => '2026-10-21', 'qc_result' => 'passed', 'qc_completed' => 1])->assertJsonValidationErrors('qc_checklist');
        $project->workflow->update(['qc_completed' => true, 'qc_progress' => 100]);
        $this->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_target_date' => '2026-10-21', 'qc_installation_result' => 'passed', 'qc_installation_completed' => 1, 'qc_installation_checklist' => $this->checks($project)])
            ->assertJsonValidationErrors('qc_installation_checklist');
        $this->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_target_date' => '2026-10-21', 'qc_installation_result' => 'in_progress', 'qc_installation_progress' => -5])->assertJsonValidationErrors('qc_installation_progress');
        $this->assertFalse($project->workflow->fresh()->qc_installation_completed);
    }

    public function test_delivery_depends_on_production_qc_and_permissions_are_preserved(): void
    {
        $project = $this->project();
        $delivery = User::factory()->create(['role' => 'delivery']);
        $this->actingAs($delivery)->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_target_date' => '2026-10-21', 'qc_installation_result' => 'in_progress', 'qc_installation_progress' => 25])->assertForbidden();
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
        $project->workflow->update(['qc_installation_target_date' => '2026-10-21', 'qc_completed' => true, 'qc_progress' => 100, 'qc_checklist' => $this->checks($project), 'qc_installation_checklist' => array_slice($this->checks($project, true), 0, 2, true)]);
        $this->actingAs(User::factory()->create(['role' => 'administrator']));
        $response = $this->get(route('project-workspace.show', $project))->assertOk()
            ->assertSee('QC Produksi')->assertSee('QC Pemasangan')
            ->assertDontSee('name="qc_progress"', false)->assertDontSee('name="qc_installation_progress"', false)
            ->assertDontSee('data-qc-bar', false)->assertSee('data-qc-check', false);
        File::ensureDirectoryExists(base_path('tmp'));
        file_put_contents(base_path('tmp/qc-workspace-test.html'), $response->getContent());
        $this->get(route('administration.project-monitoring.index'))->assertOk()->assertSee('QC Produksi')->assertSee('QC Pemasangan')->assertSee('33%');
    }

    public function test_operational_progress_includes_both_qc_stages(): void
    {
        $project = $this->project();
        $workflow = $project->workflow;
        $workflow->qc_checklist = array_slice($this->checks($project), 0, 2, true);
        $before = $workflow->completionPercent();
        $workflow->qc_installation_checklist = array_slice($this->checks($project, true), 0, 3, true);
        $this->assertGreaterThan($before, $workflow->completionPercent());
        $workflow->fill(['production_progress' => 100, 'qc_completed' => true, 'qc_installation_completed' => true, 'qc_checklist' => $this->checks($project), 'qc_installation_checklist' => $this->checks($project, true), 'delivery_status' => 'completed']);
        $this->assertSame(100, $workflow->completionPercent());
    }

    public function test_qc_percentage_uses_only_current_checklist_and_recalculates_when_unchecked(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['role' => 'administrator']));
        foreach ([false, true] as $installation) {
            $prefix = $installation ? 'qc_installation' : 'qc';
            $route = $installation ? 'project-workflow.qc-installation' : 'project-workflow.qc';
            $checks = $this->checks($project, $installation);
            $partial = array_slice($checks, 0, 1, true) + ['unknown_check' => 1];
            $this->put(route($route, $project), [
                $prefix.'_target_date' => '2026-10-21', $prefix.'_result' => 'in_progress', $prefix.'_checklist' => $partial, $prefix.'_progress' => 100,
            ])->assertSessionHasNoErrors();
            $workflow = $project->workflow->fresh();
            $expected = (int) round(100 / count($checks));
            $this->assertSame($expected, $workflow->{$prefix.'_progress'});
            $this->assertSame($expected, $workflow->qcProgress($installation));
            $this->put(route($route, $project), [$prefix.'_target_date' => '2026-10-21', $prefix.'_result' => 'passed', $prefix.'_completed' => 1, $prefix.'_checklist' => $checks])->assertSessionHasNoErrors();
            $this->assertSame(100, $project->workflow->fresh()->qcProgress($installation));
        }
        $this->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_target_date' => '2026-10-21', 'qc_installation_result' => 'in_progress'])->assertStatus(423);
        $workflow = $project->workflow->fresh();
        $this->assertSame(100, $workflow->qcProgress(true));
        $this->assertTrue($workflow->qc_installation_completed);
        $this->assertSame(100, $workflow->qcProgress());
    }

    public function test_qc_result_is_required_and_failed_qc_requires_notes_before_reinspection(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['role' => 'administrator']));
        foreach ([false, true] as $installation) {
            $prefix = $installation ? 'qc_installation' : 'qc';
            $route = $installation ? 'project-workflow.qc-installation' : 'project-workflow.qc';
            $this->putJson(route($route, $project), [])->assertJsonValidationErrors($prefix.'_result');
            $this->putJson(route($route, $project), [$prefix.'_target_date' => '2026-10-21', $prefix.'_result' => 'invalid'])->assertJsonValidationErrors($prefix.'_result');
            $this->putJson(route($route, $project), [$prefix.'_target_date' => '2026-10-21', $prefix.'_result' => 'failed', $prefix.'_note' => '   '])->assertJsonValidationErrors($prefix.'_note');
            $this->put(route($route, $project), [$prefix.'_target_date' => '2026-10-21', $prefix.'_result' => 'in_progress'])->assertSessionHasNoErrors();
            $this->assertSame('Masih diperiksa', $project->workflow->fresh()->qcStatusLabel($installation));
            $this->put(route($route, $project), [
                $prefix.'_target_date' => '2026-10-21', $prefix.'_result' => 'failed', $prefix.'_note' => 'Perbaiki sambungan yang longgar.',
            ])->assertSessionHasNoErrors();
            $workflow = $project->workflow->fresh();
            $this->assertFalse($workflow->{$prefix.'_completed'});
            $this->assertSame('failed', $workflow->qcResult($installation));
            $this->assertSame('Belum lolos / perlu perbaikan', $workflow->qcStatusLabel($installation));
            $this->get(route('project-workspace.show', $project))->assertOk()->assertSee('Belum lolos / perlu perbaikan')->assertSee('Perbaiki sambungan yang longgar.');
            if (! $installation) {
                $this->putJson(route('project-workflow.delivery', $project), ['delivery_status' => 'scheduling'])->assertUnprocessable();
                $this->putJson(route('project-workflow.qc-installation', $project), ['qc_installation_target_date' => '2026-10-21', 'qc_installation_result' => 'in_progress'])->assertUnprocessable();
            }
            $this->putJson(route($route, $project), [$prefix.'_target_date' => '2026-10-21', $prefix.'_result' => 'passed'])->assertJsonValidationErrors($prefix.'_checklist');
            $this->put(route($route, $project), [
                $prefix.'_target_date' => '2026-10-21', $prefix.'_result' => 'passed', $prefix.'_checklist' => $this->checks($project, $installation),
                $prefix.'_note' => 'Sudah diperbaiki dan diperiksa ulang.',
            ])->assertSessionHasNoErrors();
            $this->assertTrue($project->workflow->fresh()->{$prefix.'_completed'});
            $this->assertSame('passed', $project->workflow->fresh()->qcResult($installation));
            $this->assertSame('failed', $project->workflowHistory()->where('action', $prefix.'_updated')->orderBy('id')->skip(1)->first()->meta['after']['result']);
        }
    }

    public function test_passed_qc_is_locked_for_all_editors_and_keeps_saved_data_files_and_history(): void
    {
        Storage::fake('public');
        $project = $this->project();
        $this->actingAs(User::factory()->create(['role' => 'administrator']));
        foreach ([false, true] as $installation) {
            $prefix = $installation ? 'qc_installation' : 'qc';
            $route = $installation ? 'project-workflow.qc-installation' : 'project-workflow.qc';
            $this->put(route($route, $project), [
                $prefix.'_target_date' => '2026-10-21', $prefix.'_result' => 'passed', $prefix.'_checklist' => $this->checks($project, $installation),
                $prefix.'_note' => 'Hasil final tersimpan.',
                $prefix.'_document' => UploadedFile::fake()->createWithContent('final.pdf', "%PDF-1.4\n%%EOF"),
            ])->assertSessionHasNoErrors();
            $saved = $project->workflow->fresh()->getAttributes();
            $historyCount = $project->workflowHistory()->count();
            $files = Storage::disk('public')->allFiles();
            foreach (['administrator', 'qc', $installation ? 'qc_installation' : 'qc_production'] as $role) {
                $this->actingAs(User::factory()->create(['role' => $role]));
                foreach (['in_progress', 'failed', 'passed'] as $result) {
                    $this->putJson(route($route, $project), [
                        $prefix.'_target_date' => '2026-10-21', $prefix.'_result' => $result, $prefix.'_checklist' => [],
                        $prefix.'_note' => 'Percobaan mengubah hasil final.',
                        $prefix.'_document' => UploadedFile::fake()->createWithContent('replacement.pdf', "%PDF-1.4\n%%EOF"),
                    ])->assertStatus(423);
                    $this->assertSame($saved, $project->workflow->fresh()->getAttributes());
                    $this->assertSame($historyCount, $project->workflowHistory()->count());
                    $this->assertSame($files, Storage::disk('public')->allFiles());
                }
                $this->get(route('project-workspace.show', $project))->assertOk()
                    ->assertSee('Hasil QC dikunci dan tidak dapat diubah.')
                    ->assertDontSee('name="'.$prefix.'_result"', false)
                    ->assertDontSee('Simpan '.($installation ? 'QC Pemasangan' : 'QC Produksi'))
                    ->assertSee('Hasil final tersimpan.');
                $this->get(route('project-workflow.attachment', [$project, $installation ? 'qc-installation' : 'qc']))->assertOk();
            }
            $this->actingAs(User::factory()->create(['role' => 'administrator']));
        }
    }

    public function test_installation_qc_requires_delivery_at_customer_in_forms_queue_notifications_and_server(): void
    {
        $project = $this->project();
        $project->workflow->update(['qc_completed' => true]);
        $this->actingAs(User::factory()->create(['role' => 'qc_installation']));
        foreach (['scheduling', 'scheduled', 'in_transit'] as $status) {
            $project->workflow->update(['delivery_status' => $status]);
            $this->putJson(route('project-workflow.qc-installation', $project), [
                'qc_installation_result' => 'in_progress', 'qc_installation_target_date' => '2026-10-25',
            ])->assertUnprocessable();
            $this->get(route('drafter.projects.index'))->assertOk()->assertDontSee('Buka QC Pemasangan');
            $response = $this->get(route('project-workspace.show', $project))->assertOk()
                ->assertSee('QC Pemasangan dilakukan di customer')->assertDontSee('Project menunggu QC Pemasangan');
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query("//*[@id='qc-installation']//fieldset[@disabled]")->length);
        }
        foreach (ProjectWorkflow::deliveryArrivedStatuses() as $status) {
            $project->workflow->update(['delivery_status' => $status]);
            $this->get(route('drafter.projects.index'))->assertOk()->assertSee('Buka QC Pemasangan');
            $this->get(route('project-workspace.show', $project))->assertOk()->assertSee('Project menunggu QC Pemasangan');
            $this->put(route('project-workflow.qc-installation', $project), [
                'qc_installation_result' => 'in_progress', 'qc_installation_target_date' => '2026-10-25',
            ])->assertRedirect()->assertSessionHasNoErrors();
        }
    }

    public function test_stage_target_dates_are_required_valid_and_saved_independently(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['role' => 'administrator']));
        $this->putJson(route('project-workflow.production', $project), ['production_status' => 'production'])
            ->assertJsonValidationErrors('production_target_date');
        $this->putJson(route('project-workflow.production', $project), ['production_status' => 'production', 'production_target_date' => '2026-02-30'])
            ->assertJsonValidationErrors('production_target_date');
        $this->put(route('project-workflow.production', $project), ['production_status' => 'production_finished', 'production_target_date' => '2026-10-20'])
            ->assertRedirect()->assertSessionHasNoErrors();
        foreach ([false, true] as $installation) {
            $prefix = $installation ? 'qc_installation' : 'qc';
            $route = $installation ? 'project-workflow.qc-installation' : 'project-workflow.qc';
            $this->putJson(route($route, $project), [$prefix.'_result' => 'in_progress'])->assertJsonValidationErrors($prefix.'_target_date');
            $this->putJson(route($route, $project), [$prefix.'_result' => 'in_progress', $prefix.'_target_date' => 'invalid'])->assertJsonValidationErrors($prefix.'_target_date');
            $date = $installation ? '2026-10-25' : '2026-10-21';
            $this->put(route($route, $project), [
                $prefix.'_result' => 'passed', $prefix.'_target_date' => $date,
                $prefix.'_checklist' => $this->checks($project, $installation),
            ])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($date, $project->workflow->fresh()->{$prefix.'_target_date'}->format('Y-m-d'));
            $this->assertSame($date, $project->workflowHistory()->where('action', $prefix.'_updated')->latest('id')->first()->meta['after']['target_date']);
        }
        $this->assertSame('2026-10-20', $project->workflow->fresh()->production_target_date->format('Y-m-d'));
        $this->assertNull($project->fresh()->target_date);
    }

    public function test_delivery_completion_waits_for_installation_qc_before_completing_project(): void
    {
        Storage::fake('public');
        $project = $this->project();
        $project->workflow->update(['qc_completed' => true]);
        $this->actingAs(User::factory()->create(['role' => 'administrator']));
        $this->put(route('project-workflow.delivery', $project), [
            'delivery_status' => 'completed', 'delivery_scheduled_at' => '2026-10-20 09:00',
            'customer_receiver_name' => 'Penerima customer', 'customer_received_at' => '2026-10-20 10:00',
            'pod' => UploadedFile::fake()->createWithContent('pod.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('finishing', $project->fresh()->status);
        $this->assertLessThan(100, $project->fresh()->progress);
        $this->put(route('project-workflow.qc-installation', $project), [
            'qc_installation_result' => 'passed', 'qc_installation_target_date' => '2026-10-25',
            'qc_installation_checklist' => $this->checks($project, true),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('done', $project->fresh()->status);
        $this->assertSame(100, $project->fresh()->progress);
    }

    public function test_workspace_tabs_follow_operational_order_and_qc_forms_are_separate(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['role' => 'administrator']));
        $response = $this->get(route('project-workspace.show', $project))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $tabs = $xpath->query("//ul[@role='tablist']//button");
        $this->assertSame(['#project-info', '#design-request', '#production', '#qc-production', '#delivery', '#qc-installation'],
            array_map(fn ($node) => $node->getAttribute('data-bs-target'), iterator_to_array($tabs)));
        $this->assertSame('Status Delivery', trim($tabs->item(4)->textContent));
        $this->assertSame(1, $xpath->query("//*[@id='qc-production']//form[@data-qc-stage]//input[@name='qc_result']")->length > 0 ? 1 : 0);
        $this->assertSame(0, $xpath->query("//*[@id='qc-production']//*[@name='qc_installation_result']")->length);
        $this->assertSame(0, $xpath->query("//*[@id='qc-installation']//*[@name='qc_result']")->length);
        $this->assertSame(3, $xpath->query("//*[@id='qc-installation']//input[@name='qc_installation_result']")->length);
        $this->assertSame(1, $xpath->query("//*[@id='design-request']//details[@id='design-revisions']")->length);
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

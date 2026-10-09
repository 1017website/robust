<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectItemProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_progress_is_scoped_averaged_and_kept_separate_for_each_stage(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $quote = Quotation::create(['code' => 'Q-ITEM-PROGRESS', 'sales_id' => $admin->id, 'customer_name' => 'Customer', 'project_name' => 'Project']);
        $first = $quote->items()->create(['name' => 'Meja', 'qty' => 1, 'unit' => 'Unit', 'unit_price' => 100000, 'total' => 100000]);
        $second = $quote->items()->create(['name' => 'Rak', 'qty' => 2, 'unit' => 'Unit', 'unit_price' => 100000, 'total' => 200000]);
        $project = Project::create(['code' => 'PRJ-ITEM-PROGRESS', 'name' => 'Project', 'quotation_id' => $quote->id]);
        $workflow = $project->workflow()->create();
        $this->actingAs($admin);
        $payload = ['production_status' => 'production', 'production_progress' => 99, 'production_item_progress' => [$first->id => 20, $second->id => 80]];
        $this->put(route('project-workflow.production', $project), $payload)->assertSessionHasNoErrors();
        $this->assertSame(50, $workflow->fresh()->production_progress);
        $this->assertSame(20, $workflow->fresh()->production_item_progress[$first->id]);
        $this->assertSame(80, $project->workflowHistory()->latest('id')->first()->meta['after']['item_progress'][$second->id]);
        $this->put(route('project-workflow.production', $project), array_replace($payload, ['production_status' => 'production_finished']))
            ->assertSessionHasErrors('production_item_progress');
        $this->put(route('project-workflow.production', $project), array_replace($payload, ['production_item_progress' => [99999 => 100]]))
            ->assertSessionHasErrors('production_item_progress');
        $this->put(route('project-workflow.production', $project), array_replace($payload, ['production_item_progress' => [$first->id => 101]]))
            ->assertSessionHasErrors('production_item_progress.'.$first->id);
        $payload['production_status'] = 'production_finished';
        $payload['production_item_progress'] = [$first->id => 100, $second->id => 100];
        $this->put(route('project-workflow.production', $project), $payload)->assertSessionHasNoErrors();
        $this->put(route('project-workflow.qc', $project), ['qc_result' => 'in_progress', 'qc_item_progress' => [$first->id => 40, $second->id => 80]])->assertSessionHasNoErrors();
        $this->assertSame(0, $workflow->fresh()->qc_progress);
        $checks = collect(ProjectWorkflow::qcChecklistDefinition($project, false))->flatMap(fn ($item) => collect($item['checks'])->pluck('key'))->mapWithKeys(fn ($key) => [$key => 1])->all();
        $this->put(route('project-workflow.qc', $project), ['qc_result' => 'passed', 'qc_completed' => 1, 'qc_checklist' => $checks])->assertSessionHasNoErrors();
        $this->put(route('project-workflow.qc', $project), ['qc_result' => 'passed', 'qc_completed' => 1, 'qc_checklist' => $checks, 'qc_item_progress' => [$first->id => 100, $second->id => 100]])->assertSessionHasNoErrors();
        $this->put(route('project-workflow.qc-installation', $project), ['qc_installation_result' => 'in_progress', 'qc_installation_item_progress' => [$first->id => 10, $second->id => 30]])->assertSessionHasNoErrors();
        $this->assertSame(0, $workflow->fresh()->qc_installation_progress);
        $this->assertSame(100, $workflow->fresh()->qc_progress);
        $this->assertSame(100, $workflow->fresh()->production_progress);
        foreach (['administrator', 'production', 'qc_production', 'qc_installation'] as $role) {
            $user = $role === 'administrator' ? $admin : User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('project-workspace.show', $project))->assertOk()->assertSee('Meja')->assertSee('Rak');
        }
    }
}

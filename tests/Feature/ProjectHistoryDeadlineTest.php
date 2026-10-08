<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\Quotation;
use App\Models\User;
use App\Support\ProjectDeadline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectHistoryDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private function project(User $sales, array $attributes = []): Project
    {
        $quotation = Quotation::create([
            'code' => 'Q-'.uniqid(), 'customer_name' => 'Customer Riwayat',
            'project_name' => 'Project Riwayat', 'sales_id' => $sales->id,
        ]);
        $quotation->items()->create(['name' => 'Cabinet Uji', 'qty' => 1, 'unit' => 'Unit']);

        return Project::create($attributes + [
            'code' => 'RP-'.uniqid(), 'name' => 'Project Riwayat',
            'quotation_id' => $quotation->id, 'status' => 'ongoing',
            'target_date' => today()->addDays(3),
        ]);
    }

    public function test_production_keeps_each_update_and_old_attachments_with_protected_downloads(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales']);
        $production = User::factory()->create(['role' => 'production']);
        $project = $this->project($sales);
        foreach ([35, 70] as $progress) {
            $this->actingAs($production)->put(route('project-workflow.production', $project), [
                'production_status' => 'production', 'production_progress' => $progress,
                'production_note' => 'Catatan '.$progress,
                'production_report' => UploadedFile::fake()->create('checklist-'.$progress.'.pdf', 10, 'application/pdf'),
                'progress_files' => [UploadedFile::fake()->create('bukti-'.$progress.'.pdf', 10, 'application/pdf')],
            ])->assertRedirect()->assertSessionHasNoErrors();
        }

        $history = $project->workflowHistory()->orderBy('id')->get();
        $this->assertCount(2, $history);
        $this->assertSame($production->id, $history[0]->user_id);
        $this->assertSame(35, $history[1]->meta['before']['progress']);
        $this->assertSame(70, $history[1]->meta['after']['progress']);
        foreach ($history as $entry) {
            foreach ($entry->meta['attachments'] as $attachment) {
                Storage::disk('public')->assertExists($attachment['path']);
            }
        }
        $this->actingAs($sales)->get(route('sales.projects.show', $project))->assertOk()
            ->assertSee('Riwayat Pekerjaan')->assertSee('Catatan 35')->assertSee('Catatan 70');
        $this->get(route('project-workflow.history-attachment', [$project, $history[0], 0]))
            ->assertDownload('checklist-35.pdf');
        $other = $this->project($sales);
        $this->get(route('project-workflow.history-attachment', [$other, $history[0], 0]))->assertNotFound();
        $this->get(route('project-workflow.history-attachment', [$project, $history[0], 99]))->assertNotFound();
        $outsider = User::factory()->create(['role' => 'drafter']);
        $this->actingAs($outsider)->get(route('project-workflow.history-attachment', [$project, $history[0], 0]))->assertForbidden();
        $this->actingAs($production)->put(route('project-workflow.production', $project), [
            'production_status' => 'production', 'production_progress' => 101,
        ])->assertSessionHasErrors('production_progress');
        $this->assertSame(2, $project->workflowHistory()->count());
    }

    public function test_both_qc_stages_keep_progress_notes_checklists_and_documents(): void
    {
        Storage::fake('public');
        $sales = User::factory()->create(['role' => 'sales']);
        $qc = User::factory()->create(['role' => 'qc']);
        $project = $this->project($sales);
        $project->workflow()->create(['production_status' => 'production_finished']);
        foreach (['qc', 'qc_installation'] as $prefix) {
            $route = $prefix === 'qc' ? 'project-workflow.qc' : 'project-workflow.qc-installation';
            $checklist = collect(ProjectWorkflow::qcChecklistDefinition($project, false, $prefix === 'qc_installation'))
                ->flatMap(fn ($item) => collect($item['checks'])->pluck('key'))->mapWithKeys(fn ($key) => [$key => 1])->all();
            foreach ([25, 100] as $progress) {
                $this->actingAs($qc)->put(route($route, $project), [
                    $prefix.'_progress' => $progress, $prefix.'_completed' => $progress === 100,
                    $prefix.'_note' => $prefix.' catatan '.$progress,
                    $prefix.'_checklist' => $progress === 100 ? $checklist : [],
                    $prefix.'_document' => UploadedFile::fake()->create($prefix.'-'.$progress.'.pdf', 10, 'application/pdf'),
                ])->assertRedirect()->assertSessionHasNoErrors();
            }
            $entries = $project->workflowHistory()->where('action', $prefix.'_updated')->orderBy('id')->get();
            $this->assertCount(2, $entries);
            $this->assertSame(25, $entries[1]->meta['before']['progress']);
            $this->assertTrue($entries[1]->meta['after']['completed']);
            $this->assertNotEmpty($entries[1]->meta['after']['checklist_labels']);
            Storage::disk('public')->assertExists($entries[0]->meta['attachments'][0]['path']);
        }
        $this->actingAs($qc)->get(route('project-workspace.show', $project))->assertOk()
            ->assertSee('qc catatan 25')->assertSee('qc_installation catatan 100')->assertSee('Cabinet Uji');
        $this->put(route('project-workflow.qc', $project), ['qc_completed' => 1, 'qc_checklist' => []])
            ->assertSessionHasErrors('qc_checklist');
        $this->assertSame(4, $project->workflowHistory()->count());
    }

    public function test_deadline_boundaries_and_completed_projects(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 8)->startOfDay());
        $sales = User::factory()->create(['role' => 'sales']);
        foreach ([-2 => 'Terlambat 2 hari', 0 => 'Deadline hari ini', 3 => 'Deadline 3 hari lagi', 4 => null] as $days => $label) {
            $project = $this->project($sales, ['target_date' => today()->addDays($days)]);
            $this->assertSame($label, ProjectDeadline::indicator($project)['label'] ?? null);
        }
        foreach (['done', 'cancelled'] as $status) {
            $this->assertNull(ProjectDeadline::indicator($this->project($sales, ['status' => $status])));
        }
        $this->assertNull(ProjectDeadline::indicator($this->project($sales, ['target_date' => null])));
        $this->assertSame(3, ProjectDeadline::apply(Project::query())->count());
    }

    public function test_deadline_notifications_reach_sales_production_and_qc_and_resolve_when_done(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $otherSales = User::factory()->create(['role' => 'sales']);
        $production = User::factory()->create(['role' => 'production']);
        $qc = User::factory()->create(['role' => 'qc']);
        $due = $this->project($sales, ['code' => 'RP-DUE-3']);
        $due->workflow()->create(['production_status' => 'production_finished']);
        $later = $this->project($sales, ['code' => 'RP-LATER-4', 'target_date' => today()->addDays(4)]);

        foreach ([$sales, $production, $qc] as $user) {
            $this->actingAs($user)->get(route('project-workspace.show', $due))->assertOk()
                ->assertSee('RP-DUE-3 · Deadline 3 hari lagi')->assertSee(route('project-workspace.show', $due));
        }
        $this->actingAs($otherSales)->get(route('sales.projects.index'))->assertOk()
            ->assertDontSee('RP-DUE-3 · Deadline 3 hari lagi');
        $this->actingAs($sales)->get(route('sales.projects.index', ['deadline' => 1]))->assertOk()
            ->assertSee('RP-DUE-3')->assertDontSee('RP-LATER-4');
        $this->actingAs($production)->get(route('drafter.projects.index', ['deadline' => 1]))->assertOk()
            ->assertSee('RP-DUE-3')->assertDontSee('RP-LATER-4');
        $due->update(['status' => 'done']);
        $this->actingAs($sales)->get(route('project-workspace.show', $due))->assertOk()
            ->assertDontSee('Deadline 3 hari lagi');
    }

    public function test_many_deadlines_keep_all_alert_types_and_correct_counts(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        for ($index = 0; $index < 7; $index++) {
            $this->project($sales);
        }
        Quotation::create(['code' => 'Q-REVISION-ALERT', 'customer_name' => 'Customer Uji', 'project_name' => 'Penawaran Revisi Uji', 'sales_id' => $sales->id, 'status' => 'revision']);
        $this->actingAs($sales)->get(route('sales.projects.index'))->assertOk()
            ->assertSee('3 project lain mendekati / melewati deadline')
            ->assertSee('Penawaran perlu revisi')
            ->assertSee('8 item');
    }

    public function test_history_is_paginated_isolated_by_project_and_escapes_notes(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $project = $this->project($sales);
        for ($index = 1; $index <= 16; $index++) {
            $project->workflowHistory()->create([
                'user_id' => $sales->id, 'action' => 'production_updated', 'description' => 'Produksi diperbarui',
                'meta' => ['stage' => 'Produksi', 'before' => [], 'after' => ['note' => $index === 16 ? '<script>alert("test")</script>' : 'Riwayat nomor '.$index]],
            ]);
        }
        $other = $this->project($sales);
        $other->workflowHistory()->create(['action' => 'qc_updated', 'description' => 'QC project lain', 'meta' => ['after' => ['note' => 'Catatan rahasia project lain']]]);
        $this->actingAs($sales)->get(route('project-workspace.show', $project))->assertOk()
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("test")</script>', false)
            ->assertDontSee('Catatan rahasia project lain')->assertDontSee('Riwayat nomor 1<', false);
        $this->get(route('project-workspace.show', [$project, 'history_page' => 2]))->assertOk()
            ->assertSee('Riwayat nomor 1')->assertDontSee('Riwayat nomor 16');
    }

    public function test_work_sections_are_separate_and_overview_includes_history_for_every_role(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $project = $this->project($sales);
        $project->workflow()->create(['production_status' => 'production_finished', 'qc_completed' => true]);
        foreach (['sales', 'production', 'qc', 'delivery'] as $role) {
            $user = $role === 'sales' ? $sales : User::factory()->create(['role' => $role]);
            $response = $this->actingAs($user)->get(route('project-workspace.show', $project))->assertOk()
                ->assertDontSee('Production, QC &amp; Delivery', false);
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            foreach (['production', 'qc', 'delivery'] as $stage) {
                $this->assertSame(1, $xpath->query("//*[@id='{$stage}']")->length);
                $this->assertSame(1, $xpath->query("//button[@data-bs-target='#{$stage}']")->length);
            }
            $this->assertSame(1, $xpath->query("//*[@id='project-info']//*[@id='work-summary-title']")->length);
            $this->assertSame(1, $xpath->query("//*[@id='project-info']//*[@id='workflow-history']")->length);
            $this->assertSame(0, $xpath->query("//*[@id='operations']")->length);
            if ($role !== 'sales') {
                $this->assertSame($role === 'production' ? 1 : 2, $xpath->query("//*[@id='{$role}']//form[contains(@action,'/{$role}')]")->length);
            }
        }
    }

    public function test_delivery_and_do_updates_are_logged_and_latest_work_is_independent_of_history_page(): void
    {
        Storage::fake('public');
        $this->freezeTime();
        $sales = User::factory()->create(['role' => 'sales']);
        $delivery = User::factory()->create(['role' => 'delivery', 'name' => 'Petugas Delivery Uji']);
        $project = $this->project($sales);
        $project->workflow()->create(['production_status' => 'production_finished', 'qc_completed' => true]);
        $workspace = route('project-workspace.show', $project);
        foreach (['scheduling', 'scheduled'] as $status) {
            $this->actingAs($delivery)->from($workspace)->put(route('project-workflow.delivery', $project), [
                'delivery_status' => $status, 'delivery_scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'delivery_note' => 'Pengiriman '.$status,
                'pod' => UploadedFile::fake()->create($status.'.pdf', 10, 'application/pdf'),
            ])->assertRedirect($workspace.'#delivery')->assertSessionHasNoErrors();
        }
        $entries = $project->workflowHistory()->orderBy('id')->get();
        $this->assertCount(2, $entries);
        $this->assertSame('scheduling', $entries[1]->meta['before']['status']);
        $this->assertSame('scheduled', $entries[1]->meta['after']['status']);
        Storage::disk('public')->assertExists($entries[0]->meta['attachments'][0]['path']);
        $this->get(route('project-workflow.history-attachment', [$project, $entries[0], 0]))->assertDownload('scheduling.pdf');
        $this->post(route('delivery-orders.store', $project), [
            'delivery_date' => today()->toDateString(), 'delivery_address' => 'Alamat DO Uji',
            'notes' => 'DO siap dikirim', 'items' => [['name' => 'Cabinet Uji', 'qty' => 1, 'unit' => 'Unit']],
        ])->assertRedirect($workspace.'#delivery')->assertSessionHasNoErrors();
        $this->assertSame(3, $project->workflowHistory()->count());
        $this->actingAs($sales)->get($workspace.'?history_page=2')->assertOk()->assertSee('DO siap dikirim');
        $dom = new \DOMDocument;
        @$dom->loadHTML($this->get($workspace)->getContent());
        $summary = (new \DOMXPath($dom))->query("//section[@aria-labelledby='work-summary-title']")->item(0)->textContent;
        $this->assertStringContainsString('Delivery Order', $summary);
        $this->assertStringContainsString('Petugas Delivery Uji', $summary);
        $this->assertStringContainsString('DO siap dikirim', $summary);
        $this->actingAs($delivery)->from($workspace)->put(route('project-workflow.delivery', $project), ['delivery_status' => 'completed'])
            ->assertSessionHasErrors('customer_receiver_name');
        $this->assertSame(3, $project->workflowHistory()->count());
    }

    public function test_each_operational_user_sees_their_work_status_and_direct_work_links(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $project = $this->project($sales, ['progress' => 61]);
        $project->workflow()->create([
            'production_status' => 'production_finished', 'production_progress' => 100,
            'qc_completed' => true, 'qc_progress' => 100, 'qc_installation_progress' => 25,
            'delivery_status' => 'in_transit', 'delivery_scheduled_at' => now()->addDay(),
        ]);
        $project->workflowHistory()->create([
            'user_id' => $sales->id, 'action' => 'delivery_updated', 'description' => 'Delivery diperbarui',
            'meta' => ['stage' => 'Delivery', 'after' => ['note' => 'Barang sedang dikirim ke customer.']],
        ]);
        foreach (['production' => 'Produksi', 'qc' => 'QC', 'delivery' => 'Delivery'] as $role => $label) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('drafter.projects.index'));
            $response = $this->get(route('drafter.projects.index'))->assertOk()
                ->assertSee('Pekerjaan '.$label)->assertSee('Pembaruan Terakhir')->assertSee('Barang sedang dikirim ke customer.')
                ->assertSee(route('project-workspace.show', $project).'#'.$role)
                ->assertSee(route('project-workspace.show', $project).'#project-info')
                ->assertSee(route('project-workspace.show', $project).'#workflow-history')
                ->assertDontSee('61%')->assertDontSee('QC Attachment')->assertDontSee('Delivery Monitoring');
            match ($role) {
                'production' => $response->assertSee('Produksi 100%'),
                'qc' => $response->assertSee('QC Pemasangan')->assertSee('25%'),
                'delivery' => $response->assertSee('Dalam Pengiriman')->assertSee('DO/BA kembali'),
            };
        }
    }
}

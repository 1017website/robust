<?php

namespace Tests\Manual;

use App\Models\DesignRevision;
use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Audit manual role operasional pada database lokal aktif.
 *
 * Tidak memakai transaksi agar hasil workflow dapat diperiksa lewat UI.
 */
class LiveOperationalRolesAuditTest extends TestCase
{
    public function test_operational_roles_complete_the_live_project_workflow(): void
    {
        $project = Project::where('code', 'PRJ-2026-0001')->firstOrFail();
        $production = User::where('email', 'production@robust.test')->firstOrFail();
        $administration = User::where('email', 'administration@robust.test')->firstOrFail();
        $qc = User::where('email', 'qc@robust.test')->firstOrFail();
        $delivery = User::where('email', 'delivery@robust.test')->firstOrFail();

        foreach ([$production, $administration, $qc, $delivery] as $user) {
            $this->actingAs($user)->get(route('drafter.projects.index'))->assertOk();
            $this->actingAs($user)->get(route('project-workspace.show', $project))->assertOk();
            $this->actingAs($user)->get(route('admin.invoices.index'))->assertForbidden();
        }
        $this->actingAs($administration)
            ->get(route('administration.project-monitoring.index'))
            ->assertOk();

        // Endpoint operasional tetap eksklusif untuk masing-masing role.
        $this->actingAs($qc)->put(route('project-workflow.production', $project), [
            'production_status' => 'production_finished',
        ])->assertForbidden();
        $this->actingAs($production)->put(route('project-workflow.qc', $project), [
            'qc_completed' => 1,
        ])->assertForbidden();
        $this->actingAs($administration)->put(route('project-workflow.delivery', $project), [
            'delivery_out_completed' => 1,
        ])->assertForbidden();

        $this->actingAs($production)->put(route('project-workflow.production', $project), [
            'production_status' => 'production_finished',
            'production_report_completed' => 1,
            'production_report' => UploadedFile::fake()->create(
                'checklist-produksi-live-audit.pdf',
                40,
                'application/pdf'
            ),
        ])->assertRedirect();

        $this->actingAs($qc)->put(route('project-workflow.qc', $project), [
            'qc_completed' => 1,
            'qc_document' => UploadedFile::fake()->create(
                'checklist-qc-live-audit.pdf',
                40,
                'application/pdf'
            ),
        ])->assertRedirect();

        $this->actingAs($delivery)->put(route('project-workflow.delivery', $project), [
            'delivery_out_completed' => 1,
            'delivery_out_photo' => UploadedFile::fake()->image('do-keluar-live-audit.jpg'),
            'delivery_returned_completed' => 1,
            'delivery_returned_photo' => UploadedFile::fake()->image('ba-kembali-live-audit.jpg'),
        ])->assertRedirect();

        $this->actingAs($administration)->post(route('design-revisions.store', $project), [
            'revision_date' => '2026-07-26',
            'notes' => 'Revision administrasi untuk melengkapi audit workflow lintas role.',
            'revision_file' => UploadedFile::fake()->create(
                'design-revision-live-audit.pdf',
                40,
                'application/pdf'
            ),
        ])->assertRedirect();

        $workflow = ProjectWorkflow::where('project_id', $project->id)->firstOrFail();
        $revision = DesignRevision::where('project_id', $project->id)->latest('id')->firstOrFail();

        $this->assertSame('production_finished', $workflow->production_status);
        $this->assertTrue($workflow->production_report_completed);
        $this->assertTrue($workflow->qc_completed);
        $this->assertTrue($workflow->delivery_out_completed);
        $this->assertTrue($workflow->delivery_returned_completed);
        $this->assertSame(100, $workflow->completionPercent());
        $this->assertSame($production->id, $workflow->production_updated_by);
        $this->assertSame($qc->id, $workflow->qc_updated_by);
        $this->assertSame($delivery->id, $workflow->delivery_updated_by);
        $this->assertSame($administration->id, $revision->created_by);

        foreach ([
            $workflow->production_report_path,
            $workflow->qc_document_path,
            $workflow->delivery_out_photo_path,
            $workflow->delivery_returned_photo_path,
            $revision->file_path,
        ] as $path) {
            $this->assertNotNull($path);
            $this->assertTrue(Storage::disk('public')->exists($path));
        }

        $directory = storage_path('app/private/audits');
        File::ensureDirectoryExists($directory);
        File::put(
            $directory.'/live-operational-roles-audit-20260726.json',
            json_encode([
                'generated_at' => now()->toIso8601String(),
                'result' => 'passed',
                'project' => [
                    'id' => $project->id,
                    'code' => $project->code,
                    'name' => $project->name,
                ],
                'accounts' => [
                    'production' => $production->email,
                    'administration' => $administration->email,
                    'qc' => $qc->email,
                    'delivery' => $delivery->email,
                ],
                'workflow' => [
                    'production_status' => $workflow->production_status,
                    'production_report_completed' => $workflow->production_report_completed,
                    'qc_completed' => $workflow->qc_completed,
                    'delivery_out_completed' => $workflow->delivery_out_completed,
                    'delivery_returned_completed' => $workflow->delivery_returned_completed,
                    'completion_percent' => $workflow->completionPercent(),
                ],
                'design_revision' => [
                    'id' => $revision->id,
                    'number' => $revision->revision_number,
                    'created_by' => $administration->email,
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}

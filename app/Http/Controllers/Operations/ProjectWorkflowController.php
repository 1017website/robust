<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectWorkflowController extends Controller
{
    public function updateProduction(Request $request, Project $project)
    {
        $data = $request->validate([
            'production_status' => ['required', Rule::in(array_keys(ProjectWorkflow::productionStatuses()))],
            'production_report_completed' => ['nullable', 'boolean'],
            'production_report' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $workflow = $project->workflow()->firstOrCreate();
        $completed = $request->boolean('production_report_completed');
        if ($completed && ! $request->hasFile('production_report') && ! $workflow->production_report_path) {
            throw ValidationException::withMessages(['production_report' => 'Upload Checklist Produksi PDF sebelum menandai laporan lengkap.']);
        }

        $update = [
            'production_status' => $data['production_status'],
            'production_report_completed' => $completed,
            'production_updated_by' => $request->user()->id,
            'production_updated_at' => now(),
        ];
        if ($file = $request->file('production_report')) {
            $update += $this->replaceFile($workflow->production_report_path, $file, "project-workflows/{$project->id}/production", 'production_report');
        }
        $workflow->update($update);

        return back()->with('success', 'Laporan produksi berhasil diperbarui.')->withFragment('operations');
    }

    public function updateQc(Request $request, Project $project)
    {
        $request->validate([
            'qc_completed' => ['nullable', 'boolean'],
            'qc_document' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);
        $workflow = $project->workflow()->firstOrCreate();
        $completed = $request->boolean('qc_completed');
        if ($completed && ! $request->hasFile('qc_document') && ! $workflow->qc_document_path) {
            throw ValidationException::withMessages(['qc_document' => 'Upload Checklist QC PDF sebelum menandai QC selesai.']);
        }

        $update = [
            'qc_completed' => $completed,
            'qc_updated_by' => $request->user()->id,
            'qc_updated_at' => now(),
        ];
        if ($file = $request->file('qc_document')) {
            $update += $this->replaceFile($workflow->qc_document_path, $file, "project-workflows/{$project->id}/qc", 'qc_document');
        }
        $workflow->update($update);

        return back()->with('success', 'QC Attachment berhasil diperbarui.')->withFragment('operations');
    }

    public function updateDelivery(Request $request, Project $project)
    {
        $request->validate([
            'delivery_out_completed' => ['nullable', 'boolean'],
            'delivery_out_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'delivery_returned_completed' => ['nullable', 'boolean'],
            'delivery_returned_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $workflow = $project->workflow()->firstOrCreate();
        $outCompleted = $request->boolean('delivery_out_completed');
        $returnedCompleted = $request->boolean('delivery_returned_completed');

        if ($outCompleted && ! $request->hasFile('delivery_out_photo') && ! $workflow->delivery_out_photo_path) {
            throw ValidationException::withMessages(['delivery_out_photo' => 'Upload foto bukti DO/BA Keluar sebelum menandai proses selesai.']);
        }
        if ($returnedCompleted && ! $request->hasFile('delivery_returned_photo') && ! $workflow->delivery_returned_photo_path) {
            throw ValidationException::withMessages(['delivery_returned_photo' => 'Upload foto bukti DO/BA Kembali sebelum menandai proses selesai.']);
        }

        $update = [
            'delivery_out_completed' => $outCompleted,
            'delivery_returned_completed' => $returnedCompleted,
            'delivery_updated_by' => $request->user()->id,
            'delivery_updated_at' => now(),
        ];
        if ($file = $request->file('delivery_out_photo')) {
            $update += $this->replaceFile($workflow->delivery_out_photo_path, $file, "project-workflows/{$project->id}/delivery", 'delivery_out_photo');
        }
        if ($file = $request->file('delivery_returned_photo')) {
            $update += $this->replaceFile($workflow->delivery_returned_photo_path, $file, "project-workflows/{$project->id}/delivery", 'delivery_returned_photo');
        }
        $workflow->update($update);

        return back()->with('success', 'Monitoring Delivery berhasil diperbarui.')->withFragment('operations');
    }

    public function attachment(Request $request, Project $project, string $type)
    {
        abort_unless(ProjectAccess::canView($request->user(), $project), 403);
        $workflow = $project->workflow;
        abort_unless($workflow, 404);

        [$path, $name] = match ($type) {
            'production' => [$workflow->production_report_path, $workflow->production_report_name],
            'qc' => [$workflow->qc_document_path, $workflow->qc_document_name],
            'delivery-out' => [$workflow->delivery_out_photo_path, $workflow->delivery_out_photo_name],
            'delivery-returned' => [$workflow->delivery_returned_photo_path, $workflow->delivery_returned_photo_name],
            default => [null, null],
        };
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $absolutePath = Storage::disk('public')->path($path);
        if ($request->boolean('download')) {
            return response()->download($absolutePath, $name ?: basename($path));
        }

        return response()->file($absolutePath, ['Content-Type' => Storage::disk('public')->mimeType($path)]);
    }

    private function replaceFile(?string $oldPath, $file, string $directory, string $prefix): array
    {
        $newPath = $file->store($directory, 'public');
        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return [
            "{$prefix}_path" => $newPath,
            "{$prefix}_name" => $file->getClientOriginalName(),
        ];
    }
}

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
        abort_unless(
            $project->documents()->where('category', 'fabrication_drawing')->where('is_current', true)->exists(),
            422,
            'Produksi baru dapat dimulai setelah Drafter mengunggah gambar fabrikasi.'
        );

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
        $project->update([
            'status' => $data['production_status'] === 'production_finished' ? 'finishing' : 'ongoing',
            'progress' => match ($data['production_status']) {
                'production_finished' => max(60, (int) $project->progress),
                'production' => max(30, (int) $project->progress),
                default => max(10, (int) $project->progress),
            },
        ]);

        return back()->with('success', 'Laporan produksi berhasil diperbarui.')->withFragment('operations');
    }

    public function updateQc(Request $request, Project $project)
    {
        $workflow = $project->workflow()->firstOrCreate();
        abort_unless(
            $workflow->production_status === 'production_finished',
            422,
            'QC baru dapat dimulai setelah Produksi menandai pekerjaan selesai.'
        );

        $data = $request->validate([
            'qc_completed' => ['nullable', 'boolean'],
            'qc_document' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'qc_checklist' => ['nullable', 'array'],
            'qc_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $completed = $request->boolean('qc_completed');
        $definition = ProjectWorkflow::qcChecklistDefinition($project, false);
        $inputChecklist = $data['qc_checklist'] ?? [];
        $checklist = collect($definition)
            ->flatMap(fn (array $item) => collect($item['checks'])->pluck('key'))
            ->mapWithKeys(fn (string $key) => [$key => ! empty($inputChecklist[$key])])
            ->all();
        if ($completed && collect($checklist)->contains(false)) {
            throw ValidationException::withMessages(['qc_checklist' => 'Semua spesifikasi penawaran wajib dicek sebelum QC diselesaikan.']);
        }

        $update = [
            'qc_completed' => $completed,
            'qc_checklist' => $checklist,
            'qc_note' => $data['qc_note'] ?? null,
            'qc_updated_by' => $request->user()->id,
            'qc_updated_at' => now(),
        ];
        if ($file = $request->file('qc_document')) {
            $update += $this->replaceFile($workflow->qc_document_path, $file, "project-workflows/{$project->id}/qc", 'qc_document');
        }
        $workflow->update($update);
        if ($completed) {
            $project->update(['status' => 'finishing', 'progress' => max(80, (int) $project->progress)]);
        }

        return back()->with('success', 'Checklist QC berhasil diperbarui.')->withFragment('operations');
    }

    public function updateDelivery(Request $request, Project $project)
    {
        $workflow = $project->workflow()->firstOrCreate();
        abort_unless($workflow->qc_completed, 422, 'Delivery baru dapat diproses setelah QC selesai.');

        $data = $request->validate([
            'delivery_status' => ['nullable', Rule::in(array_keys(ProjectWorkflow::deliveryStatuses()))],
            'delivery_scheduled_at' => ['nullable', 'date'],
            'pod' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
            'customer_receiver_name' => ['nullable', 'string', 'max:255'],
            'customer_received_at' => ['nullable', 'date'],
            'delivery_note' => ['nullable', 'string', 'max:2000'],
            'delivery_out_completed' => ['nullable', 'boolean'],
            'delivery_out_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'delivery_returned_completed' => ['nullable', 'boolean'],
            'delivery_returned_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $outCompleted = $request->boolean('delivery_out_completed');
        $returnedCompleted = $request->boolean('delivery_returned_completed');
        $deliveryStatus = $data['delivery_status']
            ?? ($returnedCompleted ? 'completed' : ($outCompleted ? 'delivered' : $workflow->delivery_status));

        if ($request->has('delivery_out_completed') && $outCompleted && ! $request->hasFile('delivery_out_photo') && ! $workflow->delivery_out_photo_path) {
            throw ValidationException::withMessages(['delivery_out_photo' => 'Upload foto bukti DO/BA Keluar sebelum menandai proses selesai.']);
        }
        if ($request->has('delivery_returned_completed') && $returnedCompleted && ! $request->hasFile('delivery_returned_photo') && ! $workflow->delivery_returned_photo_path) {
            throw ValidationException::withMessages(['delivery_returned_photo' => 'Upload foto bukti DO/BA Kembali sebelum menandai proses selesai.']);
        }
        if ($deliveryStatus !== 'scheduling' && empty($data['delivery_scheduled_at']) && ! $workflow->delivery_scheduled_at) {
            throw ValidationException::withMessages(['delivery_scheduled_at' => 'Isi jadwal pengiriman terlebih dahulu.']);
        }
        if (in_array($deliveryStatus, ['delivered', 'customer_received', 'completed'], true)
            && ! $request->hasFile('pod') && ! $workflow->pod_path
            && ! $request->hasFile('delivery_out_photo') && ! $workflow->delivery_out_photo_path) {
            throw ValidationException::withMessages(['pod' => 'Upload POD / bukti barang terkirim.']);
        }
        if (in_array($deliveryStatus, ['customer_received', 'completed'], true)
            && (empty($data['customer_receiver_name']) || empty($data['customer_received_at']))) {
            throw ValidationException::withMessages([
                'customer_receiver_name' => 'Nama penerima customer wajib diisi.',
                'customer_received_at' => 'Tanggal barang diterima customer wajib diisi.',
            ]);
        }

        $update = [
            'delivery_status' => $deliveryStatus,
            'delivery_scheduled_at' => $data['delivery_scheduled_at'] ?? $workflow->delivery_scheduled_at,
            'customer_receiver_name' => $data['customer_receiver_name'] ?? $workflow->customer_receiver_name,
            'customer_received_at' => $data['customer_received_at'] ?? $workflow->customer_received_at,
            'delivery_note' => $data['delivery_note'] ?? null,
            'delivery_out_completed' => $outCompleted || in_array($deliveryStatus, ['delivered', 'customer_received', 'completed'], true),
            'delivery_returned_completed' => $returnedCompleted || $deliveryStatus === 'completed',
            'delivery_updated_by' => $request->user()->id,
            'delivery_updated_at' => now(),
        ];
        if ($file = $request->file('pod')) {
            $update += $this->replaceFile($workflow->pod_path, $file, "project-workflows/{$project->id}/delivery", 'pod');
        }
        if ($file = $request->file('delivery_out_photo')) {
            $update += $this->replaceFile($workflow->delivery_out_photo_path, $file, "project-workflows/{$project->id}/delivery", 'delivery_out_photo');
        }
        if ($file = $request->file('delivery_returned_photo')) {
            $update += $this->replaceFile($workflow->delivery_returned_photo_path, $file, "project-workflows/{$project->id}/delivery", 'delivery_returned_photo');
        }
        $workflow->update($update);
        $project->update([
            'status' => $deliveryStatus === 'completed' ? 'done' : 'finishing',
            'progress' => $deliveryStatus === 'completed'
                ? 100
                : max(in_array($deliveryStatus, ['delivered', 'customer_received'], true) ? 90 : 85, (int) $project->progress),
        ]);

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
            'delivery-pod' => [$workflow->pod_path, $workflow->pod_name],
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

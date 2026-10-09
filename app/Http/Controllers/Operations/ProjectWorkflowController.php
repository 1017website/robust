<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Support\ProjectAccess;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectWorkflowController extends Controller
{
    public function updateTargetDate(Request $request, Project $project, string $stage)
    {
        $prefix = match ($stage) {
            'production' => 'production',
            'qc' => 'qc',
            'qc-installation' => 'qc_installation',
            default => abort(404),
        };
        $allowed = match ($prefix) {
            'production' => in_array($request->user()->role, ['administrator', 'production'], true),
            'qc' => $request->user()->canUpdateQcProduction(),
            'qc_installation' => $request->user()->canUpdateQcInstallation(),
        };
        abort_unless($allowed && ProjectAccess::canView($request->user(), $project), 403);
        $data = $request->validate([
            "{$prefix}_target_date" => ['required', 'date_format:Y-m-d'],
            'target_date_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($project, $prefix, $data) {
            $workflow = $project->workflow()->lockForUpdate()->firstOrFail();
            abort_if($prefix !== 'production' && $workflow->{"{$prefix}_completed"}, 423, 'QC sudah selesai dan lolos. Tanggal target selesai dikunci dan tidak dapat diubah.');
            $before = $this->snapshot($workflow, $prefix);
            if ($before['target_date'] !== $data["{$prefix}_target_date"]) {
                $workflow->update(["{$prefix}_target_date" => $data["{$prefix}_target_date"]]);
                $label = match ($prefix) {
                    'production' => 'Produksi', 'qc' => 'QC Produksi', default => 'QC Pemasangan',
                };
                Logger::record("{$prefix}_target_date_updated", "Target selesai {$label} diperbarui", $project, [
                    'stage' => $label, 'before' => $before, 'after' => $this->snapshot($workflow, $prefix),
                    'reason' => $data['target_date_reason'] ?? null,
                ]);
            }

            return back()->with('success', 'Tanggal target selesai tersimpan. Setiap perubahan tanggal dicatat dalam riwayat.')
                ->withFragment(match ($prefix) { 'production' => 'production', 'qc' => 'qc-production', default => 'qc-installation' });
        });
    }

    public function updateProduction(Request $request, Project $project)
    {
        return DB::transaction(fn () => $this->saveProduction($request, $project));
    }

    private function saveProduction(Request $request, Project $project)
    {
        $project->loadMissing('quotation.designRequest');
        $hasProductionReference = $project->documents()->where('category', 'fabrication_drawing')->where('is_current', true)->exists()
            || ($project->quotation && ! $project->quotation->designRequest);
        abort_unless(
            $hasProductionReference,
            422,
            'Produksi baru dapat dimulai setelah Drafter mengunggah gambar fabrikasi.'
        );

        $data = $request->validate([
            'production_target_date' => ['required', 'date_format:Y-m-d'],
            'production_status' => ['required', Rule::in(array_keys(ProjectWorkflow::productionStatuses()))],
            'production_progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'production_item_progress' => ['nullable', 'array'],
            'production_item_progress.*' => ['required', 'integer', 'min:0', 'max:100'],
            'production_note' => ['nullable', 'string', 'max:2000'],
            'production_report_completed' => ['nullable', 'boolean'],
            'production_report' => ['nullable', 'file', 'mimes:pdf'],
            'progress_files' => ['nullable', 'array'],
            'progress_files.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp'],
        ]);

        $workflow = $project->workflow()->firstOrCreate();
        $before = $this->snapshot($workflow, 'production');
        $attachments = [];
        $completed = $request->boolean('production_report_completed');
        if ($completed && ! $request->hasFile('production_report') && ! $workflow->production_report_path) {
            throw ValidationException::withMessages(['production_report' => 'Upload Checklist Produksi PDF sebelum menandai laporan lengkap.']);
        }

        $progress = isset($data['production_progress'])
            ? (int) $data['production_progress']
            : match ($data['production_status']) {
                'production_finished' => 100,
                'production' => max(30, (int) $workflow->production_progress),
                default => (int) $workflow->production_progress,
            };
        if ($data['production_status'] === 'production_finished') {
            $progress = 100;
        }

        $itemProgress = $this->itemProgress($request, $project, $workflow, 'production', $data['production_status'] === 'production_finished');
        if ($itemProgress !== null) {
            $progress = (int) round(array_sum($itemProgress) / count($itemProgress));
        }

        $update = [
            'production_target_date' => $data['production_target_date'],
            'production_status' => $data['production_status'],
            'production_progress' => $progress,
            'production_note' => $data['production_note'] ?? null,
            'production_report_completed' => $completed,
            'production_updated_by' => $request->user()->id,
            'production_updated_at' => now(),
        ];
        if ($itemProgress !== null) $update['production_item_progress'] = $itemProgress;
        if ($file = $request->file('production_report')) {
            $update += $this->replaceFile($workflow->production_report_path, $file, "project-workflows/{$project->id}/production", 'production_report', true);
            $attachments[] = ['path' => $update['production_report_path'], 'name' => $update['production_report_name']];
        }
        $workflow->update($update);
        foreach ($request->file('progress_files', []) as $file) {
            $path = $file->store("projects/{$project->id}/production-progress", 'public');
            $attachments[] = ['path' => $path, 'name' => $file->getClientOriginalName()];
            Document::create([
                'documentable_type' => Project::class,
                'documentable_id' => $project->id,
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'category' => 'production_progress',
                'description' => $data['production_note'] ?? null,
                'file_path' => $path,
                'file_type' => strtolower($file->getClientOriginalExtension()),
                'file_size' => $file->getSize(),
                'version' => 'v1.0',
                'revision_number' => 1,
                'is_current' => true,
                'uploaded_by' => $request->user()->id,
            ]);
        }
        $project->update([
            'status' => $data['production_status'] === 'production_finished' ? 'finishing' : 'ongoing',
            'progress' => max(10, min(60, 10 + (int) round($progress * .5))),
        ]);

        Logger::record('production_updated', 'Produksi diperbarui', $project, [
            'stage' => 'Produksi', 'before' => $before,
            'after' => $this->snapshot($workflow, 'production'), 'attachments' => $attachments,
        ]);

        return back()->with('success', 'Laporan produksi berhasil diperbarui.')->withFragment('production');
    }

    public function updateQc(Request $request, Project $project)
    {
        return DB::transaction(fn () => $this->saveQc($request, $project, false));
    }

    public function updateInstallationQc(Request $request, Project $project)
    {
        return DB::transaction(fn () => $this->saveQc($request, $project, true));
    }

    private function saveQc(Request $request, Project $project, bool $installation)
    {
        $project->workflow()->firstOrCreate();
        $workflow = $project->workflow()->lockForUpdate()->firstOrFail();
        $prefix = $installation ? 'qc_installation' : 'qc';
        $attachments = [];
        $label = $installation ? 'QC Pemasangan' : 'QC Produksi';
        abort_if($workflow->{"{$prefix}_completed"}, 423, "{$label} sudah selesai dan lolos. Hasil QC dikunci dan tidak dapat diubah.");
        $before = $this->snapshot($workflow, $prefix);
        abort_unless(
            $installation ? $workflow->installationQcReady() : $workflow->production_status === 'production_finished',
            422,
            $installation ? 'QC Pemasangan dilakukan di customer setelah QC Produksi lolos dan Delivery berstatus Terkirim atau Diterima Customer.' : 'QC Produksi baru dapat dimulai setelah Produksi menandai pekerjaan selesai.'
        );

        $data = $request->validate([
            "{$prefix}_target_date" => ['required', 'date_format:Y-m-d'],
            "{$prefix}_result" => ['required', Rule::in(['in_progress', 'failed', 'passed'])],
            "{$prefix}_progress" => ['nullable', 'integer', 'min:0', 'max:100'],
            "{$prefix}_item_progress" => ['nullable', 'array'],
            "{$prefix}_item_progress.*" => ['required', 'integer', 'min:0', 'max:100'],
            "{$prefix}_document" => ['nullable', 'file', 'mimes:pdf'],
            "{$prefix}_checklist" => ['nullable', 'array'],
            "{$prefix}_checklist.*" => ['boolean'],
            "{$prefix}_note" => [Rule::requiredIf($request->input("{$prefix}_result") === 'failed'), 'nullable', 'string', 'max:2000'],
        ]);
        $completed = $data["{$prefix}_result"] === 'passed';
        $definition = ProjectWorkflow::qcChecklistDefinition($project, false, $installation);
        $inputChecklist = $data["{$prefix}_checklist"] ?? [];
        $checklist = collect($definition)
            ->flatMap(fn (array $item) => collect($item['checks'])->pluck('key'))
            ->mapWithKeys(fn (string $key) => [$key => ! empty($inputChecklist[$key])])
            ->all();
        if ($completed && (empty($checklist) || collect($checklist)->contains(false))) {
            throw ValidationException::withMessages(["{$prefix}_checklist" => "Semua pemeriksaan wajib dicek sebelum {$label} diselesaikan."]);
        }

        $update = [
            "{$prefix}_target_date" => $data["{$prefix}_target_date"],
            "{$prefix}_result" => $data["{$prefix}_result"],
            "{$prefix}_completed" => $completed,
            "{$prefix}_progress" => ProjectWorkflow::qcChecklistPercent($definition, $checklist),
            "{$prefix}_checklist" => $checklist,
            "{$prefix}_note" => $data["{$prefix}_note"] ?? null,
            "{$prefix}_updated_by" => $request->user()->id,
            "{$prefix}_updated_at" => now(),
        ];
        if ($file = $request->file("{$prefix}_document")) {
            $update += $this->replaceFile($workflow->{"{$prefix}_document_path"}, $file, "project-workflows/{$project->id}/{$prefix}", "{$prefix}_document", true);
            $attachments[] = ['path' => $update["{$prefix}_document_path"], 'name' => $update["{$prefix}_document_name"]];
        }
        $workflow->update($update);
        if ($completed && ! $installation) {
            $project->update(['status' => 'finishing', 'progress' => max(80, (int) $project->progress)]);
        } elseif ($completed && $installation && $workflow->delivery_status === 'completed') {
            $project->update(['status' => 'done', 'progress' => 100]);
        }

        Logger::record("{$prefix}_updated", "{$label} diperbarui", $project, [
            'stage' => $label, 'before' => $before,
            'after' => $this->snapshot($workflow, $prefix), 'attachments' => $attachments,
        ]);

        $message = match ($data["{$prefix}_result"]) {
            'passed' => "{$label} selesai dan lolos. Hasil berhasil disimpan.",
            'failed' => "{$label} belum lolos. Catatan perbaikan berhasil disimpan; periksa kembali setelah perbaikan.",
            default => "Progres {$label} berhasil disimpan. Pemeriksaan dapat dilanjutkan nanti.",
        };

        return back()->with('success', $message)->withFragment($installation ? 'qc-installation' : 'qc-production');
    }

    public function updateDelivery(Request $request, Project $project)
    {
        return DB::transaction(fn () => $this->saveDelivery($request, $project));
    }

    private function saveDelivery(Request $request, Project $project)
    {
        $workflow = $project->workflow()->firstOrCreate();
        $before = $this->snapshot($workflow, 'delivery');
        $attachments = [];
        abort_unless($workflow->qc_completed, 422, 'Delivery baru dapat diproses setelah QC Produksi selesai.');

        $data = $request->validate([
            'delivery_status' => ['nullable', Rule::in(array_keys(ProjectWorkflow::deliveryStatuses()))],
            'delivery_scheduled_at' => ['nullable', 'date'],
            'pod' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp'],
            'customer_receiver_name' => ['nullable', 'string', 'max:255'],
            'customer_received_at' => ['nullable', 'date'],
            'delivery_note' => ['nullable', 'string', 'max:2000'],
            'delivery_out_completed' => ['nullable', 'boolean'],
            'delivery_out_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
            'delivery_returned_completed' => ['nullable', 'boolean'],
            'delivery_returned_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
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
            $update += $this->replaceFile($workflow->pod_path, $file, "project-workflows/{$project->id}/delivery", 'pod', true);
            $attachments[] = ['path' => $update['pod_path'], 'name' => $update['pod_name']];
        }
        if ($file = $request->file('delivery_out_photo')) {
            $update += $this->replaceFile($workflow->delivery_out_photo_path, $file, "project-workflows/{$project->id}/delivery", 'delivery_out_photo', true);
            $attachments[] = ['path' => $update['delivery_out_photo_path'], 'name' => $update['delivery_out_photo_name']];
        }
        if ($file = $request->file('delivery_returned_photo')) {
            $update += $this->replaceFile($workflow->delivery_returned_photo_path, $file, "project-workflows/{$project->id}/delivery", 'delivery_returned_photo', true);
            $attachments[] = ['path' => $update['delivery_returned_photo_path'], 'name' => $update['delivery_returned_photo_name']];
        }
        $workflow->update($update);
        $project->update([
            'status' => $deliveryStatus === 'completed' && $workflow->qc_installation_completed ? 'done' : 'finishing',
            'progress' => $deliveryStatus === 'completed' && $workflow->qc_installation_completed
                ? 100
                : max(in_array($deliveryStatus, ProjectWorkflow::deliveryArrivedStatuses(), true) ? 90 : 85, (int) $project->progress),
        ]);

        Logger::record('delivery_updated', 'Delivery diperbarui', $project, [
            'stage' => 'Delivery', 'before' => $before,
            'after' => $this->snapshot($workflow, 'delivery'), 'attachments' => $attachments,
        ]);

        return back()->with('success', 'Monitoring Delivery berhasil diperbarui.')->withFragment('delivery');
    }

    public function attachment(Request $request, Project $project, string $type)
    {
        abort_unless(ProjectAccess::canView($request->user(), $project), 403);
        $workflow = $project->workflow;
        abort_unless($workflow, 404);

        [$path, $name] = match ($type) {
            'production' => [$workflow->production_report_path, $workflow->production_report_name],
            'qc' => [$workflow->qc_document_path, $workflow->qc_document_name],
            'qc-installation' => [$workflow->qc_installation_document_path, $workflow->qc_installation_document_name],
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

    public function historyAttachment(Request $request, Project $project, ActivityLog $history, int $index)
    {
        abort_unless(ProjectAccess::canView($request->user(), $project), 403);
        abort_unless($project->workflowHistory()->whereKey($history->id)->exists(), 404);
        $attachment = $history->meta['attachments'][$index] ?? null;
        abort_unless($attachment && Storage::disk('public')->exists($attachment['path']), 404);

        return Storage::disk('public')->download($attachment['path'], $attachment['name']);
    }

    private function itemProgress(Request $request, Project $project, ProjectWorkflow $workflow, string $prefix, bool $completed): ?array
    {
        $field = "{$prefix}_item_progress";
        $saved = $workflow->{$field} ?? [];
        if (! $request->has($field) && $saved === []) return null;
        $items = $project->quotation?->items()->where('is_optional', false)->pluck('id')->all() ?? [];
        $input = $request->input($field) ?? [];
        if (array_diff(array_map('strval', array_keys($input)), array_map('strval', $items))) {
            throw ValidationException::withMessages([$field => 'Item tidak termasuk dalam project ini.']);
        }
        if ($items === []) return null;
        $stageWasCompleted = $prefix === 'production' ? $workflow->production_status === 'production_finished' : (bool) $workflow->{$prefix.'_completed'};
        $progress = [];
        foreach ($items as $id) $progress[$id] = (int) ($input[$id] ?? $saved[$id] ?? ($stageWasCompleted ? 100 : 0));
        if ($completed && min($progress) < 100) {
            throw ValidationException::withMessages([$field => 'Semua item harus mencapai 100% sebelum tahap diselesaikan.']);
        }
        return $progress;
    }

    private function snapshot(ProjectWorkflow $workflow, string $prefix): array
    {
        if ($prefix === 'delivery') {
            return [
                'status' => $workflow->delivery_status, 'note' => $workflow->delivery_note,
                'scheduled_at' => $workflow->delivery_scheduled_at?->toIso8601String(),
                'receiver_name' => $workflow->customer_receiver_name,
                'received_at' => $workflow->customer_received_at?->toIso8601String(),
                'out_completed' => (bool) $workflow->delivery_out_completed,
                'returned_completed' => (bool) $workflow->delivery_returned_completed,
            ];
        }
        $fields = $prefix === 'production'
            ? ['status', 'target_date', 'progress', 'item_progress', 'note', 'report_completed', 'report_path', 'report_name']
            : ['result', 'completed', 'target_date', 'progress', 'note', 'checklist', 'document_path', 'document_name'];

        $snapshot = collect($fields)->mapWithKeys(fn ($field) => [$field => $workflow->{"{$prefix}_{$field}"}])->all();
        $snapshot['target_date'] = $workflow->{"{$prefix}_target_date"}?->format('Y-m-d');
        $snapshot['item_names'] = $workflow->project->quotation?->items?->pluck('name', 'id')->all() ?? [];
        if ($prefix !== 'production') {
            $snapshot['progress'] = $workflow->qcProgress($prefix === 'qc_installation');
            $snapshot['checklist_labels'] = collect(ProjectWorkflow::qcChecklistDefinition($workflow->project, false, $prefix === 'qc_installation'))
                ->flatMap(fn ($item) => collect($item['checks'])->mapWithKeys(fn ($check) => [$check['key'] => $item['item_name'].' · '.$check['label']]))
                ->all();
        }

        return $snapshot;
    }

    private function replaceFile(?string $oldPath, $file, string $directory, string $prefix, bool $retainOld = false): array
    {
        $newPath = $file->store($directory, 'public');
        if (! $retainOld && $oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return [
            "{$prefix}_path" => $newPath,
            "{$prefix}_name" => $file->getClientOriginalName(),
        ];
    }
}

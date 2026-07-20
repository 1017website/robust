<?php

namespace App\Http\Controllers\Drafter;

use App\Http\Controllers\Controller;
use App\Models\DesignRevision;
use App\Models\Project;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DesignRevisionController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_unless(ProjectAccess::canView($request->user(), $project), 403);
        $data = $request->validate([
            'revision_date' => ['required', 'date'],
            'notes' => ['required', 'string', 'max:5000'],
            'revision_file' => ['required', 'file', 'max:51200', 'extensions:pdf,dwg,dxf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,rar'],
        ]);

        $file = $request->file('revision_file');
        $path = $file->store("project-revisions/{$project->id}", 'public');

        try {
            DB::transaction(function () use ($project, $data, $file, $path, $request) {
                Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
                $nextNumber = (int) DesignRevision::where('project_id', $project->id)->max('revision_number') + 1;
                DesignRevision::create([
                    'project_id' => $project->id,
                    'revision_number' => $nextNumber,
                    'revision_date' => $data['revision_date'],
                    'notes' => $data['notes'],
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'submitted',
                    'created_by' => $request->user()->id,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        return back()->with('success', 'Design Revision baru berhasil disimpan.')->withFragment('design-revisions');
    }

    public function updateStatus(Request $request, Project $project, DesignRevision $designRevision)
    {
        abort_unless(ProjectAccess::canView($request->user(), $project), 403);
        abort_unless((int) $designRevision->project_id === (int) $project->id, 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(DesignRevision::statuses()))],
        ]);
        $designRevision->update([
            'status' => $data['status'],
            'status_updated_by' => $request->user()->id,
            'status_updated_at' => now(),
        ]);

        return back()->with('success', 'Status design revision berhasil diperbarui.')->withFragment('design-revisions');
    }

    public function attachment(Request $request, Project $project, DesignRevision $designRevision)
    {
        abort_unless((int) $designRevision->project_id === (int) $project->id, 404);
        abort_unless(ProjectAccess::canView($request->user(), $project), 403);
        abort_unless(Storage::disk('public')->exists($designRevision->file_path), 404);

        $absolutePath = Storage::disk('public')->path($designRevision->file_path);
        if ($request->boolean('download')) {
            return response()->download($absolutePath, $designRevision->original_name);
        }

        return response()->file($absolutePath, ['Content-Type' => $designRevision->mime_type ?: 'application/octet-stream']);
    }
}

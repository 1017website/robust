<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;

class ProjectWorkspaceController extends Controller
{
    public function show(Request $request, Project $project)
    {
        abort_unless(ProjectAccess::canView($request->user(), $project), 403, 'Project ini tidak tersedia untuk akun Anda.');

        $project->load([
            'customer', 'projectManager', 'quotation', 'terms', 'activities', 'documents',
            'workflow.productionUpdater', 'workflow.qcUpdater', 'workflow.deliveryUpdater',
            'designRevisions.creator', 'designRevisions.statusUpdater',
        ]);
        $workflow = $project->workflow ?: $project->workflow()->make();

        return view('projects.workspace', compact('project', 'workflow'));
    }
}

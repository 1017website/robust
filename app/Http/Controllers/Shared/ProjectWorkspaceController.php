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
            'customer', 'projectManager', 'quotation.items', 'quotation.purchaseOrderRequest',
            'quotation.designRequest.items.itemMaster', 'quotation.designRequest.documents.uploader',
            'quotation.designRequest.revisionRequests.requester', 'quotation.designRequest.sales',
            'quotation.designRequest.productionPic', 'quotation.designRequest.customer.primaryPic',
            'quotation.designRequest.lead', 'terms', 'activities', 'documents.uploader',
            'workflow.productionUpdater', 'workflow.qcUpdater', 'workflow.deliveryUpdater',
            'designRevisions.creator', 'designRevisions.statusUpdater',
        ]);
        $workflow = $project->workflow ?: $project->workflow()->make();
        $fabricationDocuments = $project->documents
            ->where('category', 'fabrication_drawing')
            ->sortByDesc('created_at')
            ->values();
        $qcChecklistDefinition = \App\Models\ProjectWorkflow::qcChecklistDefinition($project);

        return view('projects.workspace', compact('project', 'workflow', 'fabricationDocuments', 'qcChecklistDefinition'));
    }
}

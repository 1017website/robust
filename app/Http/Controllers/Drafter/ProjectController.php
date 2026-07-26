<?php

namespace App\Http\Controllers\Drafter;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $applyRoleScope = function ($query) use ($user) {
            if ($user->isDrafter()) {
                $query->where(function ($scope) use ($user) {
                    $scope->where('project_manager_id', $user->id)
                        ->orWhereJsonContains('internal_team', (string) $user->id)
                        ->orWhereJsonContains('internal_team', $user->id);
                });
            } elseif ($user->isProduction()) {
                $query->whereHas('documents', fn ($documents) => $documents
                    ->where('category', 'fabrication_drawing')
                    ->where('is_current', true));
            } elseif ($user->isQc()) {
                $query->whereHas('workflow', fn ($workflow) => $workflow
                    ->where('production_status', 'production_finished'));
            } elseif ($user->isDelivery()) {
                $query->whereHas('workflow', fn ($workflow) => $workflow
                    ->where('qc_completed', true));
            }

            return $query;
        };

        $query = Project::with('customer', 'projectManager', 'quotation')
            ->with('workflow');
        $applyRoleScope($query);
        $query->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($keyword = $request->get('q')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$keyword}%"));
            });
        }

        $projects = $query->paginate(8)->withQueryString();
        $selectedProject = $request->filled('project')
            ? $projects->getCollection()->firstWhere('id', (int) $request->get('project'))
            : null;
        $selectedProject ??= $projects->first();

        $base = Project::query();
        $applyRoleScope($base);

        $stats = [
            'aktif' => (clone $base)->whereIn('status', ['planning', 'ongoing', 'finishing'])->count(),
            'planning' => (clone $base)->where('status', 'planning')->count(),
            'ongoing' => (clone $base)->where('status', 'ongoing')->count(),
            'finishing' => (clone $base)->where('status', 'finishing')->count(),
            'done' => (clone $base)->where('status', 'done')->count(),
            'overdue' => (clone $base)->whereNotIn('status', ['done', 'cancelled'])->whereDate('target_date', '<', today())->count(),
        ];

        return view('drafter.projects.index', compact('projects', 'selectedProject', 'stats'));
    }
}

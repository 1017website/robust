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

        $query = Project::with('customer', 'projectManager', 'quotation')
            ->with('workflow')
            ->when($user->isDrafter(), function ($q) use ($user) {
                $q->where(function ($w) use ($user) {
                    $w->where('project_manager_id', $user->id)
                        ->orWhereJsonContains('internal_team', (string) $user->id)
                        ->orWhereJsonContains('internal_team', $user->id);
                });
            })
            ->latest();

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

        $base = Project::query()
            ->when($user->isDrafter(), function ($q) use ($user) {
                $q->where(function ($w) use ($user) {
                    $w->where('project_manager_id', $user->id)
                        ->orWhereJsonContains('internal_team', (string) $user->id)
                        ->orWhereJsonContains('internal_team', $user->id);
                });
            });

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

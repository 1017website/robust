<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\DesignRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        if (Auth::user()->isDrafter()) {
            $designRequests = DesignRequest::with('sales')
                ->where(function ($query) use ($month, $year) {
                    $query->whereYear('deadline', $year)->whereMonth('deadline', $month)
                        ->orWhere(function ($q) use ($month, $year) {
                            $q->whereYear('request_date', $year)->whereMonth('request_date', $month);
                        });
                })
                ->orderBy('deadline')
                ->get();

            $projects = Project::with('projectManager')
                ->whereYear('target_date', $year)->whereMonth('target_date', $month)
                ->orderBy('target_date')
                ->get();

            $events = collect();
            foreach ($designRequests as $dr) {
                if ($dr->deadline) {
                    $events->push((object) [
                        'date' => $dr->deadline,
                        'time' => null,
                        'title' => ($dr->status === 'completed' ? 'Submit Final' : 'Deadline').' - '.$dr->project_name,
                        'subtitle' => $dr->code,
                        'type' => match ($dr->status) {
                            'completed' => 'Dokumen',
                            'review' => 'Review',
                            'costing' => 'QC',
                            default => 'Produksi',
                        },
                        'status' => $dr->status,
                    ]);
                }
            }
            foreach ($projects as $project) {
                if ($project->target_date) {
                    $events->push((object) [
                        'date' => $project->target_date,
                        'time' => null,
                        'title' => 'Target Project - '.$project->name,
                        'subtitle' => $project->code,
                        'type' => 'Meeting',
                        'status' => $project->status,
                    ]);
                }
            }

            $events = $events->sortBy('date')->values();
            $byDate = $events->groupBy(fn ($event) => $event->date->format('Y-m-d'));
            $todayEvents = $events->filter(fn ($event) => $event->date->isToday())->values();
            $upcomingEvents = $events->filter(fn ($event) => $event->date->isFuture())->take(5)->values();
            $typeSummary = $events->groupBy('type')->map->count();

            return view('drafter.calendar.index', compact('events', 'byDate', 'todayEvents', 'upcomingEvents', 'typeSummary', 'month', 'year'));
        }

        $query = Activity::whereYear('activity_date', $year)->whereMonth('activity_date', $month);
        if (Auth::user()->isSales()) {
            $query->where('sales_id', Auth::id());
        }
        $activities = $query->orderBy('activity_date')->orderBy('activity_time')->get();

        $byDate = $activities->groupBy(fn ($a) => $a->activity_date->format('Y-m-d'));

        $stats = [
            'today' => $activities->filter(fn ($a) => $a->activity_date->isToday())->count(),
            'this_week' => $activities->filter(fn ($a) => $a->activity_date->isCurrentWeek())->count(),
            'overdue' => $activities->filter(fn ($a) => $a->activity_date->isPast() && $a->status !== 'completed')->count(),
            'upcoming' => $activities->filter(fn ($a) => $a->activity_date->isFuture())->count(),
        ];

        $todayActivities = (clone $activities)->filter(fn ($a) => $a->activity_date->isToday())->values();
        $typeSummary = $activities->groupBy('type')->map->count();

        return view('shared.calendar.index', compact('activities', 'byDate', 'month', 'year', 'stats', 'todayActivities', 'typeSummary'));
    }
}

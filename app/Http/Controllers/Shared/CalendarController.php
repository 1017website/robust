<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

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

<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\DesignRequest;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        if (Auth::user()->isDrafter()) {
            $user = Auth::user();
            $base = DesignRequest::query()->where(function ($query) use ($user) {
                $query->where('production_pic_id', $user->id)->orWhereNull('production_pic_id');
            });

            $summary = [
                'active_projects' => Project::whereIn('status', ['planning', 'ongoing', 'finishing'])->count(),
                'completed_projects' => Project::where('status', 'done')->count(),
                'completed_tasks' => (clone $base)->where('status', 'completed')->count(),
                'revisi' => (clone $base)->whereIn('status', ['review', 'rejected'])->count(),
                'overdue' => (clone $base)->whereNotIn('status', ['completed', 'rejected'])->whereDate('deadline', '<', today())->count(),
                'on_time' => 0,
            ];
            $totalFinished = max(1, (clone $base)->whereIn('status', ['completed', 'rejected'])->count());
            $onTime = (clone $base)->where('status', 'completed')->whereColumn('submitted_at', '<=', 'deadline')->count();
            $summary['on_time'] = round($onTime / $totalFinished * 100);

            $statusSummary = collect(['assigned','drafting','costing','review','completed'])->mapWithKeys(fn ($status) => [
                $status => (clone $base)->where('status', $status)->count(),
            ]);

            $monthlyCompleted = DesignRequest::selectRaw('MONTH(submitted_at) as m, count(*) as total')
                ->whereNotNull('submitted_at')
                ->whereYear('submitted_at', now()->year)
                ->groupBy('m')
                ->get()
                ->keyBy('m');

            $productivity = DesignRequest::selectRaw('production_pic_id, count(*) as total')
                ->with('productionPic')
                ->where('status', 'completed')
                ->whereNotNull('production_pic_id')
                ->groupBy('production_pic_id')
                ->orderByDesc('total')
                ->take(5)
                ->get();

            $upcomingDeadlines = (clone $base)->whereNotIn('status', ['completed', 'rejected'])
                ->whereNotNull('deadline')
                ->orderBy('deadline')
                ->take(6)
                ->get();

            $activeProjects = Project::whereIn('status', ['planning', 'ongoing', 'finishing'])
                ->orderByDesc('progress')
                ->take(5)
                ->get();

            return view('drafter.reports.index', compact('summary', 'statusSummary', 'monthlyCompleted', 'productivity', 'upcomingDeadlines', 'activeProjects'));
        }

        $isSales = Auth::user()->isSales();
        $uid = Auth::id();

        $leadScope = fn ($q) => $isSales ? $q->where('sales_id', $uid) : $q;
        $quoteScope = fn ($q) => $isSales ? $q->where('sales_id', $uid) : $q;
        $customerScope = fn ($q) => $isSales ? $q->where('sales_id', $uid) : $q;
        $activityScope = fn ($q) => $isSales ? $q->where('sales_id', $uid) : $q;

        $wonStatuses = Quotation::wonStatuses();
        $totalQuotes = $quoteScope(Quotation::query())->count();
        $won = $quoteScope(Quotation::whereIn('status', $wonStatuses))->count();

        $summary = [
            'total_leads' => $leadScope(Lead::query())->count(),
            'active_customers' => $customerScope(Customer::where('status', 'aktif'))->count(),
            'total_quotations' => $totalQuotes,
            'won' => $won,
            'total_value' => $quoteScope(Quotation::whereIn('status', $wonStatuses))->sum('grand_total'),
            'active_projects' => Project::whereIn('status', ['planning', 'ongoing', 'finishing'])->when($isSales, fn ($q) => $q->whereHas('quotation', fn ($qq) => $qq->where('sales_id', $uid)))->count(),
        ];

        $monthly = Quotation::selectRaw("MONTH(created_at) as m, count(*) as total, sum(grand_total) as value")
            ->when($isSales, fn ($q) => $q->where('sales_id', $uid))
            ->whereYear('created_at', now()->year)
            ->groupBy('m')->get()->keyBy('m');

        $winRate = $totalQuotes > 0 ? round($won / $totalQuotes * 100) : 0;

        $pipelineValue = collect(Customer::stages())->mapWithKeys(function ($label, $stage) use ($isSales, $uid) {
            $q = Customer::where('pipeline_stage', $stage);
            if ($isSales) $q->where('sales_id', $uid);
            return [$stage => ['label' => $label, 'count' => $q->count()]];
        });

        $activitySummary = $activityScope(Activity::selectRaw('type, count(*) as total'))
            ->whereMonth('activity_date', now()->month)
            ->groupBy('type')->pluck('total', 'type');

        $leadSource = $leadScope(Lead::selectRaw('source, count(*) as total'))
            ->groupBy('source')->pluck('total', 'source');

        $topCustomers = $customerScope(Customer::withSum('quotations as quotation_value', 'grand_total'))
            ->withCount('quotations')
            ->orderByDesc('quotation_value')
            ->take(5)->get();

        $upcomingActivities = $activityScope(Activity::query())
            ->whereDate('activity_date', '>=', today())
            ->orderBy('activity_date')->orderBy('activity_time')
            ->take(5)->get();

        return view('shared.reports.index', compact(
            'summary', 'monthly', 'winRate', 'pipelineValue', 'activitySummary', 'leadSource', 'topCustomers', 'upcomingActivities'
        ));
    }
}

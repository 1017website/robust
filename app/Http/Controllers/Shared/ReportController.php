<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\DesignRequest;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->isDrafter()) {
            $user = Auth::user();
            $base = DesignRequest::query()->where('production_pic_id', $user->id);
            $projectBase = Project::query()->where(function ($query) use ($user) {
                $query->where('project_manager_id', $user->id)
                    ->orWhereJsonContains('internal_team', (string) $user->id)
                    ->orWhereJsonContains('internal_team', $user->id);
            });

            $summary = [
                'active_projects' => (clone $projectBase)->whereIn('status', ['planning', 'ongoing', 'finishing'])->count(),
                'completed_projects' => (clone $projectBase)->where('status', 'done')->count(),
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
                ->where('production_pic_id', $user->id)
                ->whereNotNull('submitted_at')
                ->whereYear('submitted_at', now()->year)
                ->groupBy('m')
                ->get()
                ->keyBy('m');

            $productivity = DesignRequest::selectRaw('production_pic_id, count(*) as total')
                ->with('productionPic')
                ->where('production_pic_id', $user->id)
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

            $activeProjects = (clone $projectBase)->whereIn('status', ['planning', 'ongoing', 'finishing'])
                ->orderByDesc('progress')
                ->take(5)
                ->get();

            if ($request->get('export') === 'csv') {
                return $this->csv('laporan-drafter.csv', [
                    ['Metrik', 'Nilai'],
                    ...collect($summary)->map(fn ($value, $label) => [str($label)->headline(), $value])->values()->all(),
                ]);
            }

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
            'total_value' => $quoteScope(Quotation::whereIn('status', $wonStatuses))
                ->whereYear('updated_at', now()->year)
                ->whereMonth('updated_at', now()->month)
                ->sum('grand_total'),
            'active_projects' => Project::whereIn('status', ['planning', 'ongoing', 'finishing'])->when($isSales, fn ($q) => $q->whereHas('quotation', fn ($qq) => $qq->where('sales_id', $uid)))->count(),
        ];

        $monthly = Quotation::selectRaw("MONTH(created_at) as m, count(*) as total, sum(grand_total) as value")
            ->when($isSales, fn ($q) => $q->where('sales_id', $uid))
            ->whereYear('created_at', now()->year)
            ->groupBy('m')->get()->keyBy('m');

        $winRate = $totalQuotes > 0 ? round($won / $totalQuotes * 100) : 0;
        $salesTarget = (float) SystemSetting::value('sales_monthly_target', 0);
        $targetPercent = $salesTarget > 0 ? min(100, round($summary['total_value'] / $salesTarget * 100, 1)) : 0;

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

        if ($request->get('export') === 'csv') {
            return $this->csv('laporan-sales-'.now()->format('Y-m').'.csv', [
                ['Metrik', 'Nilai'],
                ['Total Leads', $summary['total_leads']],
                ['Customer Aktif', $summary['active_customers']],
                ['Total Penawaran', $summary['total_quotations']],
                ['Won / Closing', $summary['won']],
                ['Nilai Closing Bulan Ini', $summary['total_value']],
                ['Conversion Rate (%)', $winRate],
            ]);
        }

        return view('shared.reports.index', compact(
            'summary', 'monthly', 'winRate', 'pipelineValue', 'activitySummary', 'leadSource', 'topCustomers', 'upcomingActivities', 'salesTarget', 'targetPercent'
        ));
    }

    protected function csv(string $filename, array $rows)
    {
        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($output, $row, ';');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

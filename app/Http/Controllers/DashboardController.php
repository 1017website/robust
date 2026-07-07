<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return match ($user->role) {
            'administrator' => $this->adminDashboard(),
            'sales_admin' => $this->adminDashboard(),
            'sales_spv' => $this->spvDashboard(),
            'drafter' => $this->drafterDashboard(),
            default => $this->salesDashboard(),
        };
    }

    protected function adminDashboard()
    {
        $stats = [
            'pra_leads' => PraLead::count(),
            'assigned' => PraLead::where('status', 'assigned')->count(),
            'waiting' => PraLead::where('status', 'waiting_acceptance')->count(),
            'rejected' => PraLead::where('status', 'rejected')->count(),
            'leads_aktif' => Lead::where('status', 'aktif')->count(),
            'project_aktif' => Project::whereIn('status', ['planning', 'ongoing', 'finishing'])->count(),
        ];

        $praLeadByStatus = PraLead::selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        $praLeadBySource = PraLead::selectRaw('source, count(*) as total')
            ->groupBy('source')->pluck('total', 'source');

        $distribution = PraLead::selectRaw('assigned_sales_id, count(*) as total')
            ->whereNotNull('assigned_sales_id')
            ->groupBy('assigned_sales_id')
            ->with('assignedSales')
            ->get();

        $recentPraLeads = PraLead::with('assignedSales')->latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'stats', 'praLeadByStatus', 'praLeadBySource', 'distribution', 'recentPraLeads'
        ));
    }

    protected function salesDashboard()
    {
        $user = Auth::user();
        $uid = $user->id;

        $activeQuotationStatuses = ['draft', 'waiting_approval', 'revision', 'approved', 'sent_to_customer'];
        $wonStatuses = ['customer_accepted', 'request_po_created', 'won'];

        $wonValue = (float) Quotation::where('sales_id', $uid)
            ->whereIn('status', $wonStatuses)
            ->whereMonth('updated_at', now()->month)
            ->sum('grand_total');
        $target = 12500000000;

        $stats = [
            'leads_aktif' => Lead::where('sales_id', $uid)->where('status', 'aktif')->count(),
            'penawaran_aktif' => Quotation::where('sales_id', $uid)->whereIn('status', $activeQuotationStatuses)->count(),
            'project_berjalan' => Project::whereHas('quotation', fn ($q) => $q->where('sales_id', $uid))->whereIn('status', ['planning', 'ongoing', 'finishing'])->count(),
            'deal_won' => Quotation::where('sales_id', $uid)->whereIn('status', $wonStatuses)->whereMonth('updated_at', now()->month)->count(),
            'target_value' => $target,
            'won_value' => $wonValue,
            'target_percent' => $target > 0 ? min(100, round($wonValue / $target * 100)) : 0,
        ];

        $pipeline = [
            'leads' => Lead::where('sales_id', $uid)->whereIn('stage', ['lead', 'identify'])->count(),
            'design_request' => DesignRequest::where('sales_id', $uid)->count(),
            'penawaran' => Quotation::where('sales_id', $uid)->whereIn('status', $activeQuotationStatuses)->count(),
            'negosiasi' => Quotation::where('sales_id', $uid)->whereIn('status', ['sent_to_customer', 'negotiation'])->count(),
            'won' => Quotation::where('sales_id', $uid)->whereIn('status', $wonStatuses)->count(),
        ];

        $pipelineValues = [
            'design_request' => (float) DesignRequest::where('sales_id', $uid)->sum('cost_total'),
            'penawaran' => (float) Quotation::where('sales_id', $uid)->whereIn('status', $activeQuotationStatuses)->sum('grand_total'),
            'negosiasi' => (float) Quotation::where('sales_id', $uid)->whereIn('status', ['sent_to_customer', 'negotiation'])->sum('grand_total'),
            'won' => (float) Quotation::where('sales_id', $uid)->whereIn('status', $wonStatuses)->sum('grand_total'),
        ];

        $monthly = collect(range(1, 6))->mapWithKeys(function ($i) use ($uid, $wonStatuses) {
            $date = now()->startOfMonth()->subMonths(6 - $i);
            return [$date->format('M') => [
                'pipeline' => (float) Quotation::where('sales_id', $uid)->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->sum('grand_total'),
                'won' => (float) Quotation::where('sales_id', $uid)->whereIn('status', $wonStatuses)->whereYear('updated_at', $date->year)->whereMonth('updated_at', $date->month)->sum('grand_total'),
            ]];
        });

        $recentLeads = Lead::where('sales_id', $uid)->latest()->take(4)->get();
        $todayActivities = Activity::where('sales_id', $uid)
            ->whereDate('activity_date', today())->orderBy('activity_time')->take(5)->get();
        $requestMasuk = PraLead::where('assigned_sales_id', $uid)->where('status', 'waiting_acceptance')->count();
        $todos = Activity::where('sales_id', $uid)
            ->where('status', '!=', 'completed')
            ->orderBy('activity_date')->orderBy('activity_time')->take(5)->get();
        $upcomingMeetings = Activity::where('sales_id', $uid)
            ->whereDate('activity_date', '>=', today())
            ->orderBy('activity_date')->orderBy('activity_time')->take(3)->get();
        $activitySummary = Activity::selectRaw('type, count(*) as total')
            ->where('sales_id', $uid)
            ->whereMonth('activity_date', now()->month)
            ->groupBy('type')->pluck('total', 'type');

        return view('sales.dashboard', compact(
            'stats', 'pipeline', 'pipelineValues', 'monthly', 'recentLeads', 'todayActivities', 'requestMasuk', 'todos', 'upcomingMeetings', 'activitySummary'
        ));
    }

    protected function spvDashboard()
    {
        $stats = [
            'waiting_approval' => Quotation::where('status', 'waiting_approval')->count(),
            'approved_month' => Quotation::where('status', 'approved')->whereMonth('approved_at', now()->month)->count(),
            'revision' => Quotation::where('status', 'revision')->count(),
            'rejected_month' => Quotation::where('status', 'rejected')->whereMonth('rejected_at', now()->month)->count(),
        ];

        $approvalQueue = Quotation::with('sales')
            ->whereIn('status', ['waiting_approval', 'revision'])
            ->latest('submitted_for_approval_at')
            ->take(8)
            ->get();

        return view('spv.dashboard', compact('stats', 'approvalQueue'));
    }

    protected function drafterDashboard()
    {
        $user = Auth::user();

        $stats = [
            'request_baru' => DesignRequest::where('status', 'assigned')->count(),
            'drafting' => DesignRequest::where('status', 'drafting')->count(),
            'waiting_approval' => DesignRequest::where('status', 'review')->count(),
            'completed' => DesignRequest::where('status', 'completed')->count(),
        ];

        $myTasks = DesignRequest::where('production_pic_id', $user->id)
            ->whereNotIn('status', ['completed', 'rejected'])
            ->orderBy('deadline')->take(8)->get();

        $queue = DesignRequest::with('sales')->whereIn('status', ['assigned', 'drafting', 'costing', 'review'])
            ->latest()->take(6)->get();

        return view('drafter.dashboard', compact('stats', 'myTasks', 'queue'));
    }
}

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

        $stats = [
            'leads_aktif' => Lead::where('sales_id', $user->id)->where('status', 'aktif')->count(),
            'penawaran_aktif' => Quotation::where('sales_id', $user->id)->whereIn('status', ['waiting_approval', 'approved', 'sent_to_customer'])->count(),
            'project_berjalan' => Project::whereHas('quotation', fn ($q) => $q->where('sales_id', $user->id))->whereIn('status', ['planning', 'ongoing', 'finishing'])->count(),
            'deal_won' => Quotation::where('sales_id', $user->id)->whereIn('status', ['customer_accepted', 'request_po_created', 'won'])->whereMonth('updated_at', now()->month)->count(),
        ];

        $pipeline = [
            'leads' => Lead::where('sales_id', $user->id)->where('stage', 'lead')->count(),
            'design_request' => Lead::where('sales_id', $user->id)->where('stage', 'design_request')->count(),
            'penawaran' => Quotation::where('sales_id', $user->id)->whereIn('status', ['waiting_approval', 'approved', 'sent_to_customer'])->count(),
            'won' => Quotation::where('sales_id', $user->id)->whereIn('status', ['customer_accepted', 'request_po_created', 'won'])->count(),
        ];

        $recentLeads = Lead::where('sales_id', $user->id)->latest()->take(4)->get();
        $todayActivities = Activity::where('sales_id', $user->id)
            ->whereDate('activity_date', today())->orderBy('activity_time')->get();
        $requestMasuk = PraLead::where('assigned_sales_id', $user->id)
            ->where('status', 'waiting_acceptance')->count();

        return view('sales.dashboard', compact(
            'stats', 'pipeline', 'recentLeads', 'todayActivities', 'requestMasuk'
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

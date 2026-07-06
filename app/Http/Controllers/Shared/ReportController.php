<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        $isSales = Auth::user()->isSales();
        $uid = Auth::id();

        $leadScope = fn ($q) => $isSales ? $q->where('sales_id', $uid) : $q;
        $quoteScope = fn ($q) => $isSales ? $q->where('sales_id', $uid) : $q;

        $summary = [
            'total_leads' => $leadScope(Lead::query())->count(),
            'total_quotations' => $quoteScope(Quotation::query())->count(),
            'won' => $quoteScope(Quotation::whereIn('status', ['customer_accepted', 'request_po_created', 'won']))->count(),
            'total_value' => $quoteScope(Quotation::whereIn('status', ['customer_accepted', 'request_po_created', 'won']))->sum('grand_total'),
            'active_projects' => Project::whereIn('status', ['planning', 'ongoing', 'finishing'])->count(),
        ];

        $monthly = Quotation::selectRaw("MONTH(created_at) as m, count(*) as total, sum(grand_total) as value")
            ->when($isSales, fn ($q) => $q->where('sales_id', $uid))
            ->whereYear('created_at', now()->year)
            ->groupBy('m')->get()->keyBy('m');

        $winRate = $summary['total_quotations'] > 0 ? round($summary['won'] / $summary['total_quotations'] * 100) : 0;

        $pipelineValue = collect(Customer::stages())->mapWithKeys(function ($label, $stage) use ($isSales, $uid) {
            $q = Customer::where('pipeline_stage', $stage);
            if ($isSales) $q->where('sales_id', $uid);
            return [$stage => ['label' => $label, 'count' => $q->count()]];
        });

        return view('shared.reports.index', compact('summary', 'monthly', 'winRate', 'pipelineValue'));
    }
}

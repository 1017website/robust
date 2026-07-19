<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ExcelWorkbook;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $salesList = User::assignableSales();

        $workload = $salesList->map(function ($s) {
            return [
                'sales' => $s,
                'request_masuk' => PraLead::where('assigned_sales_id', $s->id)->where('status', 'waiting_acceptance')->count(),
                'leads_aktif' => Lead::where('sales_id', $s->id)->whereIn('status', Lead::activeStatuses())->count(),
                'design_request' => Lead::where('sales_id', $s->id)->where('stage', 'design_request')->count(),
                'penawaran_aktif' => Quotation::where('sales_id', $s->id)->whereIn('status', Quotation::activeSalesStatuses())->count(),
                'project_aktif' => Project::whereHas('quotation', fn ($q) => $q->where('sales_id', $s->id))->whereIn('status', ['planning', 'ongoing', 'finishing'])->count(),
            ];
        });

        $acceptance = $salesList->map(function ($s) {
            $assigned = PraLead::where('assigned_sales_id', $s->id)->whereIn('status', ['waiting_acceptance', 'accepted', 'rejected'])->count();
            $accepted = PraLead::where('assigned_sales_id', $s->id)->where('status', 'accepted')->count();
            $rejected = PraLead::where('assigned_sales_id', $s->id)->where('status', 'rejected')->count();

            return [
                'sales' => $s,
                'assigned' => $assigned,
                'accepted' => $accepted,
                'rejected' => $rejected,
                'rate' => $assigned > 0 ? round($accepted / $assigned * 100) : 0,
            ];
        });

        $stats = [
            'total_sales' => $salesList->count(),
            'total_leads' => Lead::count(),
            'active_projects' => Project::whereIn('status', ['planning', 'ongoing', 'finishing'])->count(),
            'acceptance_rate' => $acceptance->avg('rate') ? round($acceptance->avg('rate')) : 0,
        ];

        $leads = Lead::with('sales')->latest()->take(60)->get();
        $projects = Project::with('quotation.sales', 'quotation.customer')->latest()->take(6)->get();
        $defaultSelectedRow = $workload->sortByDesc('leads_aktif')->first();
        $selectedSales = $request->filled('sales')
            ? $salesList->firstWhere('id', (int) $request->get('sales'))
            : null;
        $selectedSales ??= $defaultSelectedRow['sales'] ?? $salesList->first();

        if ($request->get('export') === 'excel') {
            $acceptanceBySales = $acceptance->keyBy(fn ($row) => $row['sales']->id);
            $rows = $workload->map(fn ($row) => [
                $row['sales']->name,
                $row['request_masuk'],
                $row['leads_aktif'],
                $row['design_request'],
                $row['penawaran_aktif'],
                $row['project_aktif'],
                ($acceptanceBySales[$row['sales']->id]['rate'] ?? 0).'%',
            ]);

            return ExcelWorkbook::download(
                'assignment-sales-'.now()->format('Y-m-d').'.xlsx',
                ['Sales', 'Request Masuk', 'Leads Aktif', 'Design Request', 'Penawaran Aktif', 'Project Aktif', 'Acceptance Rate'],
                $rows,
                'Assignment Sales'
            );
        }

        return view('admin.assignment.index', compact('salesList', 'workload', 'acceptance', 'stats', 'leads', 'projects', 'selectedSales'));
    }

    public function reassign(Request $request)
    {
        $data = $request->validate([
            'lead_id' => ['required', 'exists:leads,id'],
            'to_sales_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'sales')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
        ]);

        $lead = Lead::findOrFail($data['lead_id']);
        $lead->update(['sales_id' => $data['to_sales_id']]);
        Logger::record('reassigned', "Lead {$lead->instansi} dialihkan", $lead);

        return back()->with('success', 'Lead berhasil dialihkan.');
    }
}

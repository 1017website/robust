<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Logger;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        $salesList = User::assignableSales();

        $workload = $salesList->map(function ($s) {
            return [
                'sales' => $s,
                'request_masuk' => PraLead::where('assigned_sales_id', $s->id)->where('status', 'waiting_acceptance')->count(),
                'leads_aktif' => Lead::where('sales_id', $s->id)->where('status', 'aktif')->count(),
                'design_request' => Lead::where('sales_id', $s->id)->where('stage', 'design_request')->count(),
                'penawaran_aktif' => Quotation::where('sales_id', $s->id)->whereIn('status', ['sent', 'negotiation'])->count(),
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

        return view('admin.assignment.index', compact('salesList', 'workload', 'acceptance', 'stats'));
    }

    public function reassign(Request $request)
    {
        $data = $request->validate([
            'lead_id' => ['required', 'exists:leads,id'],
            'to_sales_id' => ['required', 'exists:users,id'],
        ]);

        $lead = Lead::findOrFail($data['lead_id']);
        $lead->update(['sales_id' => $data['to_sales_id']]);
        Logger::record('reassigned', "Lead {$lead->instansi} dialihkan", $lead);

        return back()->with('success', 'Lead berhasil dialihkan.');
    }
}

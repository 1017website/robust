<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with([
            'customer', 'projectManager', 'workflow',
            'quotation.purchaseOrderRequest.invoice.terms',
        ])->latest('start_date');

        if ($keyword = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('location', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$keyword}%"));
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $projects = $query->paginate(25)->withQueryString();
        $stats = [
            'projects' => Project::count(),
            'active' => Project::whereIn('status', ['planning', 'ongoing', 'finishing'])->count(),
            'production_finished' => Project::whereHas('workflow', fn ($q) => $q->where('production_status', 'production_finished'))->count(),
            'qc_complete' => Project::whereHas('workflow', fn ($q) => $q->where('qc_completed', true))->count(),
            'delivery_complete' => Project::whereHas('workflow', fn ($q) => $q->where('delivery_returned_completed', true))->count(),
            'receivable' => max(0, (float) Invoice::sum('grand_total') - (float) Invoice::sum('paid_total')),
        ];

        return view('administration.project-monitoring.index', compact('projects', 'stats'));
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'administration_comment' => ['nullable', 'string', 'max:2000'],
            'payment_confirmation_completed' => ['nullable', 'boolean'],
            'withholding_tax_receipt_completed' => ['nullable', 'boolean'],
        ]);

        $workflow = $project->workflow()->firstOrCreate();
        $workflow->update([
            'administration_comment' => $data['administration_comment'] ?? null,
            'payment_confirmation_completed' => $request->boolean('payment_confirmation_completed'),
            'withholding_tax_receipt_completed' => $request->boolean('withholding_tax_receipt_completed'),
            'administration_updated_by' => Auth::id(),
            'administration_updated_at' => now(),
        ]);

        Logger::record('administration_updated', "Monitoring administrasi project {$project->code} diperbarui", $project);

        return back()->with('success', 'Data administrasi project berhasil disimpan.');
    }
}

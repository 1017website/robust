<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\CodeGenerator;
use App\Services\LeadCustomerConnector;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->leadQueryForCurrentUser()->with('customer')->latest();

        if ($s = $request->get('q')) {
            $query->where(fn ($w) => $w->where('instansi', 'like', "%$s%")
                ->orWhere('pic_name', 'like', "%$s%"));
        }
        if ($stage = $request->get('stage')) {
            $query->where('stage', $stage);
        }
        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        $leads = $query->paginate(10)->withQueryString();

        $base = $this->leadQueryForCurrentUser();
        $stats = [
            'total' => (clone $base)->count(),
            'lead' => (clone $base)->whereIn('stage', ['lead', 'identify'])->count(),
            'design_request' => (clone $base)->where('stage', 'design_request')->count(),
            'penawaran' => (clone $base)->where('stage', 'penawaran')->count(),
            'won' => (clone $base)->where(function ($q) { $q->whereIn('stage', ['won', 'closing'])->orWhere('status', 'won'); })->count(),
        ];
        $selectedLead = $leads->first();

        return view('sales.leads.index', compact('leads', 'stats', 'selectedLead'));
    }

    public function create()
    {
        return view('sales.leads.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['code'] = CodeGenerator::next(Lead::class, 'LD', 5, true);
        $data['sales_id'] = Auth::id();
        $data['created_by'] = Auth::id();
        $data['stage'] = 'lead';
        $data['status'] = 'aktif';

        $lead = DB::transaction(function () use ($data) {
            $lead = Lead::create($data);
            app(LeadCustomerConnector::class)->ensureForLead($lead);

            return $lead->fresh('customer');
        });

        Logger::record('created', "Lead {$lead->instansi} dibuat manual dan terhubung ke Customer", $lead);

        return redirect()->route('sales.leads.show', $lead)->with('success', 'Lead berhasil disimpan dan terhubung ke Customer.');
    }

    public function show(Lead $lead)
    {
        abort_unless($this->canManageLead($lead), 403);
        $lead->load('praLead', 'customer.primaryPic', 'designRequests', 'documents', 'quotations');
        return view('sales.leads.show', compact('lead'));
    }

    public function edit(Lead $lead)
    {
        abort_unless($this->canManageLead($lead), 403);
        return view('sales.leads.edit', compact('lead'));
    }

    public function update(Request $request, Lead $lead)
    {
        abort_unless($this->canManageLead($lead), 403);
        DB::transaction(function () use ($request, $lead) {
            $lead->update($this->validateData($request));
            app(LeadCustomerConnector::class)->ensureForLead($lead->fresh('customer'));
        });

        Logger::record('updated', "Lead {$lead->instansi} diperbarui dan data Customer disinkronkan", $lead);
        return redirect()->route('sales.leads.show', $lead)->with('success', 'Lead diperbarui dan data Customer ikut disinkronkan.');
    }

    public function destroy(Lead $lead)
    {
        abort_unless($this->canManageLead($lead), 403);
        $lead->delete();
        return redirect()->route('sales.leads.index')->with('success', 'Lead dihapus.');
    }

    protected function leadQueryForCurrentUser()
    {
        $user = Auth::user();
        $query = Lead::query();

        if ($user && $user->isAdminLevel()) {
            return $query;
        }

        return $query->where('sales_id', Auth::id());
    }

    protected function canManageLead(Lead $lead): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isAdminLevel()) {
            return true;
        }

        return (int) $lead->sales_id === (int) $user->id;
    }

    protected function validateData(Request $request): array
    {
        $data = $request->validate([
            'instansi' => ['required', 'string', 'max:255'],
            'pic_name' => ['required', 'string', 'max:255'],
            'pic_position' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['required', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'instansi_type' => ['required', 'string', 'max:100'],
            'source' => ['required', 'string'],
            'reference' => ['nullable', 'string', 'max:255'],
            'lab_name' => ['required', 'string', 'max:255'],
            'need_description' => ['nullable', 'string', 'max:500'],
            'scope_items' => ['nullable', 'array'],
            'est_value_min' => ['nullable', 'numeric'],
            'est_value_max' => ['nullable', 'numeric'],
            'priority' => ['required', 'in:low,medium,high'],
            'initial_note' => ['nullable', 'string'],
        ]);
        $data['scope_items'] = array_values(array_filter($request->input('scope_items', [])));
        return $data;
    }
}

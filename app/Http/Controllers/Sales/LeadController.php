<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Lead;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\LeadCustomerConnector;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::query()
            ->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))
            ->latest();

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

        $base = Lead::query()->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()));
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
        $salesList = User::assignableSales();
        return view('sales.leads.create', compact('salesList'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['code'] = CodeGenerator::next(Lead::class, 'LD', 5, true);
        $data['sales_id'] = Auth::user()->isSales() ? Auth::id() : $data['sales_id'];
        $data['created_by'] = Auth::id();
        $data['stage'] = 'lead';
        $data['status'] = 'aktif';

        $lead = DB::transaction(function () use ($request, $data) {
            $lead = Lead::create($data);
            app(LeadCustomerConnector::class)->ensureForLead($lead);
            $this->storeLeadDocuments($request, $lead);

            return $lead->fresh(['customer', 'documents']);
        });

        Logger::record('created', "Lead {$lead->instansi} dibuat manual dan terhubung ke Customer", $lead);

        return redirect()
            ->route('sales.leads.create')
            ->withInput($request->except('documents'))
            ->with([
                'success' => 'Lead berhasil disimpan dan terhubung ke Customer.',
                'created_lead_id' => $lead->id,
                'created_lead_code' => $lead->code,
                'created_lead_name' => $lead->instansi,
            ]);
    }

    public function show(Lead $lead)
    {
        $this->ensureAccess($lead);
        $lead->load('praLead', 'customer', 'designRequests', 'documents', 'quotations');
        return view('sales.leads.show', compact('lead'));
    }

    public function edit(Lead $lead)
    {
        $this->ensureAccess($lead);
        $salesList = User::assignableSales();
        return view('sales.leads.edit', compact('lead', 'salesList'));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->ensureAccess($lead);
        $data = $this->validateData($request);
        if (Auth::user()->isSales()) {
            unset($data['sales_id']);
        }

        DB::transaction(function () use ($request, $lead, $data) {
            $lead->update($data);
            $this->storeLeadDocuments($request, $lead);
        });

        Logger::record('updated', "Lead {$lead->instansi} diperbarui", $lead);
        return redirect()->route('sales.leads.show', $lead)->with('success', 'Lead diperbarui.');
    }

    public function destroy(Lead $lead)
    {
        $this->ensureAccess($lead);
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
            'capacity' => ['nullable', 'string', 'max:255'],
            'est_value_min' => ['nullable', 'numeric'],
            'est_value_max' => ['nullable', 'numeric'],
            'priority' => ['required', 'in:low,medium,high'],
            'initial_note' => ['nullable', 'string'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'sales_id' => [
                Rule::requiredIf(fn () => ! Auth::user()->isSales()),
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'sales')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
        ]);
        $data['scope_items'] = array_values(array_filter($request->input('scope_items', [])));
        unset($data['documents']);

        return $data;
    }

    protected function storeLeadDocuments(Request $request, Lead $lead): void
    {
        foreach ($request->file('documents', []) as $file) {
            $path = $file->store('documents', 'public');

            Document::create([
                'documentable_type' => Lead::class,
                'documentable_id' => $lead->id,
                'name' => $file->getClientOriginalName(),
                'category' => 'lainnya',
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);
        }
    }

    protected function ensureAccess(Lead $lead): void
    {
        abort_if(
            Auth::user()->isSales() && (int) $lead->sales_id !== (int) Auth::id(),
            403,
            'Lead ini bukan milik Anda.'
        );
    }
}

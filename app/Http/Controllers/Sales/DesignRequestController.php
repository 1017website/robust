<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\LeadCustomerConnector;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DesignRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DesignRequest::with('productionPic', 'sales')
            ->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))
            ->latest();

        if ($s = $request->get('q')) {
            $query->where(function ($w) use ($s) {
                $w->where('code', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('pic_name', 'like', "%{$s}%")
                    ->orWhere('project_name', 'like', "%{$s}%")
                    ->orWhere('detail_need', 'like', "%{$s}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if (! Auth::user()->isSales() && $salesId = $request->get('sales_id')) {
            $query->where('sales_id', $salesId);
        }
        if ($productionPicId = $request->get('production_pic_id')) {
            $query->where('production_pic_id', $productionPicId);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        $designRequests = $query->paginate(10)->withQueryString();
        $base = DesignRequest::query()->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()));
        $stats = [
            'total' => (clone $base)->count(),
            'waiting' => (clone $base)->where('status', 'assigned')->count(),
            'progress' => (clone $base)->whereIn('status', ['drafting', 'costing', 'review'])->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
        ];

        $selectedRequest = $designRequests->first();
        $salesList = User::assignableSales();
        $drafters = User::where('role', 'drafter')->where('is_active', true)->orderBy('name')->get();

        return view('sales.design_requests.index', compact('designRequests', 'stats', 'selectedRequest', 'salesList', 'drafters'));
    }

    public function create(Request $request)
    {
        $lead = $request->get('lead') ? $this->leadQuery()->findOrFail($request->get('lead')) : null;
        $drafters = User::assignableDraftersQuery()->get();
        $drafterWorkloads = $this->drafterWorkloads($drafters);
        $salesList = User::assignableSales();
        return view('sales.design_requests.create', compact('lead', 'drafters', 'drafterWorkloads', 'salesList'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id' => ['nullable', Rule::exists('leads', 'id')->where(fn ($query) => $this->scopeLeadExistsRule($query))],
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where(fn ($query) => $this->scopeCustomerExistsRule($query))],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'project_name' => ['required', 'string', 'max:255'],
            'request_date' => ['required', 'date'],
            'deadline' => ['required', 'date', 'after_or_equal:request_date'],
            'priority' => ['required', 'in:low,medium,high'],
            'short_description' => ['required', 'string', 'max:500'],
            'lab_type' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'string', 'max:255'],
            'detail_need' => ['required', 'string', 'max:1000'],
            'scope_checklist' => ['nullable', 'array'],
            'scope_checklist.*' => ['nullable', 'string', 'max:120'],
            'outputs' => ['nullable', 'array'],
            'outputs.*' => ['nullable', 'string', 'max:80'],
            'extra_note' => ['nullable', 'string', 'max:500'],
            'production_pic_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->whereRaw('LOWER(role) = ?', ['drafter'])->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            }))],
            'production_note' => ['nullable', 'string', 'max:300'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,heic,doc,docx,xls,xlsx', 'max:10240'],
            'sales_id' => [
                Rule::requiredIf(fn () => ! Auth::user()->isSales() && empty($request->input('lead_id'))),
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'sales')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
        ]);

        $lead = null;
        $customer = null;

        if (! empty($data['lead_id'])) {
            $lead = $this->leadQuery()->with('customer.primaryPic')->findOrFail($data['lead_id']);
            $customer = app(LeadCustomerConnector::class)->ensureForLead($lead)->load('primaryPic');
        } elseif (! empty($data['customer_id'])) {
            $customer = $this->customerQuery()->with('primaryPic')->findOrFail($data['customer_id']);
        } elseif (! empty($data['customer_name'])) {
            $customer = $this->findOrCreateCustomerFromRequest($data);
        }

        if ($customer) {
            $data['customer_id'] = $customer->id;
            $data['customer_name'] = $customer->name;
            $data['pic_name'] = $data['pic_name'] ?: $customer->primaryPic?->name;
        }

        $data['scope_checklist'] = array_values(array_filter($data['scope_checklist'] ?? []));
        $data['outputs'] = array_values(array_filter($data['outputs'] ?? []));
        $data['code'] = CodeGenerator::next(DesignRequest::class, 'DR', 3);
        $lead = $this->leadQuery()->find($data['lead_id'] ?? null);
        $data['sales_id'] = Auth::user()->isSales() ? Auth::id() : ($lead?->sales_id ?: $data['sales_id']);
        $data['created_by'] = Auth::id();
        $data['status'] = $request->input('action') === 'send' ? 'assigned' : 'draft';
        if ($lead) {
            $data['customer_id'] = $lead->customer_id;
            $lead->update(['stage' => 'design_request']);
        }

        unset($data['attachments']);
        $designRequest = DesignRequest::create($data);
        foreach ($request->file('attachments', []) as $attachment) {
            $designRequest->documents()->create([
                'name' => pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME),
                'category' => 'sales_sketch',
                'description' => 'Sketsa/lampiran awal dari sales',
                'file_path' => $attachment->store('design-requests/sales-sketches', 'public'),
                'file_type' => $attachment->getClientOriginalExtension(),
                'file_size' => $attachment->getSize(),
                'version' => 'v1.0', 'revision_number' => 1, 'is_current' => true,
                'uploaded_by' => Auth::id(),
            ]);
        }
        Logger::record('created', "Design Request {$designRequest->code} dibuat", $designRequest);

        return redirect()
            ->route('sales.design-requests.index')
            ->with('success', 'Design Request berhasil disimpan dan drafter sudah ditugaskan.');
    }

    public function show(DesignRequest $designRequest)
    {
        abort_unless($this->canViewDesignRequest($designRequest), 403);
        $designRequest->load('items', 'productionPic', 'documents', 'sales', 'customer.primaryPic', 'lead');

        return view('sales.design_requests.show', compact('designRequest'));
    }

    protected function designRequestQuery()
    {
        return DesignRequest::with('productionPic', 'sales', 'customer', 'lead')
            ->when(Auth::user()->isSales(), function ($query) {
                $query->where(function ($scope) {
                    $scope->where('sales_id', Auth::id())
                        ->orWhereHas('lead', fn ($lead) => $lead->where('sales_id', Auth::id()))
                        ->orWhereHas('customer', fn ($customer) => $customer->where('sales_id', Auth::id()));
                });
            });
    }

    protected function leadQuery()
    {
        return Lead::query()
            ->when(Auth::user()->isSales(), fn ($query) => $query->where('sales_id', Auth::id()));
    }

    protected function customerQuery()
    {
        return Customer::query()
            ->when(Auth::user()->isSales(), function ($query) {
                $query->where(function ($scope) {
                    $scope->where('sales_id', Auth::id())->orWhereNull('sales_id');
                });
            });
    }

    protected function assignableDraftersQuery()
    {
        return User::query()
            ->whereRaw('LOWER(role) = ?', ['drafter'])
            ->where(function ($query) {
                $query->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name');
    }

    protected function drafterWorkloads($drafters)
    {
        return $drafters->map(function (User $drafter) {
            return [
                'drafter' => $drafter,
                'active_requests' => DesignRequest::where('production_pic_id', $drafter->id)
                    ->whereNotIn('status', ['completed', 'rejected'])
                    ->count(),
            ];
        })->sortBy('active_requests')->values();
    }

    protected function scopeLeadExistsRule($query)
    {
        if (Auth::user()->isSales()) {
            $query->where('sales_id', Auth::id());
        }

        return $query;
    }

    protected function scopeCustomerExistsRule($query)
    {
        if (Auth::user()->isSales()) {
            $query->where(function ($scope) {
                $scope->where('sales_id', Auth::id())->orWhereNull('sales_id');
            });
        }

        return $query;
    }

    protected function findOrCreateCustomerFromRequest(array $data): Customer
    {
        $customer = Customer::query()
            ->when(Auth::user()->isSales(), function ($query) {
                $query->where(function ($scope) {
                    $scope->where('sales_id', Auth::id())->orWhereNull('sales_id');
                });
            })
            ->where('name', $data['customer_name'])
            ->first();

        if (! $customer) {
            $customer = Customer::create([
                'code' => CodeGenerator::next(Customer::class, 'CUST', 4),
                'name' => $data['customer_name'],
                'type' => $data['lab_type'] ?? null,
                'pipeline_stage' => 'identify',
                'probability' => 0,
                'status' => 'aktif',
                'sales_id' => Auth::user()->isSales() ? Auth::id() : null,
            ]);
        }

        if (! empty($data['pic_name']) && ! $customer->primaryPic) {
            $customer->pics()->create([
                'name' => $data['pic_name'],
                'is_primary' => true,
            ]);
            $customer->load('primaryPic');
        }

        return $customer;
    }

    protected function canViewDesignRequest(DesignRequest $designRequest): bool
    {
        if (! Auth::user()->isSales()) {
            return true;
        }

        $userId = (int) Auth::id();

        return (int) $designRequest->sales_id === $userId
            || (int) optional($designRequest->lead)->sales_id === $userId
            || (int) optional($designRequest->customer)->sales_id === $userId;
    }
}

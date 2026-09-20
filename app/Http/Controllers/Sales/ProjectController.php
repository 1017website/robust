<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\Logger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with('customer', 'projectManager')->latest();
        if (Auth::user()->isSales() && ! Auth::user()->isAdminLevel()) {
            $query->whereHas('quotation', fn ($q) => $q->where('sales_id', Auth::id()));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        $projects = $query->paginate(10)->withQueryString();
        return view('sales.projects.index', compact('projects'));
    }

    public function create(Request $request)
    {
        $sourceProjectId = $request->integer('project');
        if (! $sourceProjectId && $request->filled('quotation')) {
            $sourceProjectId = (int) PurchaseOrderRequest::query()
                ->where('quotation_id', $request->integer('quotation'))
                ->value('id');
        }

        $sourceProject = $sourceProjectId
            ? $this->eligibleSourceProjectQuery()->findOrFail($sourceProjectId)
            : null;
        $availableProjects = $this->eligibleSourceProjectQuery()->get();
        $managers = User::where('is_active', true)
            ->whereIn('role', ['sales', 'drafter'])
            ->orderBy('name')
            ->get();
        $team = User::where('is_active', true)->get();
        return view('sales.projects.create', compact('sourceProject', 'availableProjects', 'managers', 'team'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'purchase_order_request_id' => ['required', 'exists:purchase_order_requests,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['required', 'in:'.implode(',', array_keys(Project::statuses()))],
            'start_date' => ['required', 'date'],
            'target_date' => ['required', 'date', 'after_or_equal:start_date'],
            'work_method' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string'],
            'scope_of_work' => ['nullable', 'string'],
            'payment_scheme' => ['nullable', 'string', 'max:100'],
            'project_manager_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->whereIn('role', ['sales', 'drafter'])
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'internal_team' => ['nullable', 'array'],
            'internal_team.*' => [Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'external_vendor' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $sourceProject = $this->eligibleSourceProjectQuery()->findOrFail($data['purchase_order_request_id']);
        $quotation = $sourceProject->quotation;
        unset($data['purchase_order_request_id']);
        $data['code'] = ($data['code'] ?? null) ?: $this->nextProjectCode($sourceProject);
        $data['customer_id'] = $quotation->customer_id;
        $data['project_value'] = $quotation->subtotal - $quotation->discount_amount;
        $data['tax_amount'] = $quotation->tax_amount;
        $data['total_value'] = $quotation->grand_total;
        $data['internal_team'] = array_values($data['internal_team'] ?? []);
        $data['created_by'] = Auth::id();

        $project = Project::create($data);
        Logger::record('created', "Request Process {$project->name} dibuat dari Project {$sourceProject->projectNumber()}", $project);

        return redirect()->route('sales.projects.show', $project)->with('success', 'Request Process berhasil dibuat.');
    }

    public function show(Project $project)
    {
        abort_unless($this->canViewProject($project), 403);
        $project->load([
            'customer', 'projectManager', 'quotation.items', 'quotation.documents.uploader', 'quotation.purchaseOrderRequest',
            'quotation.designRequest.items.itemMaster', 'quotation.designRequest.documents.uploader',
            'quotation.designRequest.revisionRequests.requester', 'quotation.designRequest.sales',
            'quotation.designRequest.productionPic', 'quotation.designRequest.customer.primaryPic',
            'quotation.designRequest.lead', 'terms', 'activities', 'documents.uploader',
            'workflow.productionUpdater', 'workflow.qcUpdater', 'workflow.deliveryUpdater',
            'designRevisions.creator', 'designRevisions.statusUpdater',
        ]);
        $workflow = $project->workflow ?: $project->workflow()->make();
        $fabricationDocuments = $project->documents
            ->where('category', 'fabrication_drawing')
            ->sortByDesc('created_at')
            ->values();
        $showPrices = Auth::user()->canViewPrices();
        $productionProgressDocuments = $project->documents
            ->where('category', 'production_progress')
            ->sortByDesc('created_at')
            ->values();
        $qcChecklistDefinition = \App\Models\ProjectWorkflow::qcChecklistDefinition($project, $showPrices);

        return view('projects.workspace', compact('project', 'workflow', 'fabricationDocuments', 'productionProgressDocuments', 'qcChecklistDefinition', 'showPrices'));
    }

    /**
     * Request Process memakai Nomor Proyek milik Project-nya. Kode PRJ otomatis hanya
     * dipakai bila nomor itu sudah terpakai Request Process lain.
     */
    protected function nextProjectCode(PurchaseOrderRequest $sourceProject): string
    {
        $projectNumber = trim((string) $sourceProject->projectNumber());

        // Kode unik di level database, jadi Request Process terhapus pun masih memegangnya.
        return $projectNumber !== '' && ! Project::withTrashed()->where('code', $projectNumber)->exists()
            ? $projectNumber
            : CodeGenerator::next(Project::class, 'PRJ', 4, true);
    }

    protected function eligibleSourceProjectQuery(): Builder
    {
        return PurchaseOrderRequest::query()
            ->with([
                'quotation.sales', 'quotation.customer.primaryPic', 'quotation.items',
                'quotation.documents.uploader', 'quotation.designRequest.productionPic',
            ])
            ->visibleTo(Auth::user())
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereHas('quotation', fn ($query) => $query
                ->whereIn('status', Quotation::wonStatuses())
                ->whereDoesntHave('project'))
            ->latest();
    }

    protected function canViewProject(Project $project): bool
    {
        $user = Auth::user();
        if ($user->isAdminLevel() || ! $user->isSales()) {
            return true;
        }

        $project->loadMissing('quotation');

        return (int) $project->project_manager_id === (int) $user->id
            || (int) ($project->quotation?->sales_id) === (int) $user->id;
    }
}

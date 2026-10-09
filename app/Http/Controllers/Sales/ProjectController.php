<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Services\CodeGenerator;
use App\Services\Logger;
use App\Support\ProjectDeadline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        if ($request->boolean('deadline')) {
            ProjectDeadline::apply($query);
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
            abort_if(! $sourceProjectId, 404, 'Project yang sudah diajukan tidak ditemukan.');
        }

        $sourceProject = $sourceProjectId
            ? $this->eligibleSourceProjectQuery()->findOrFail($sourceProjectId)
            : null;
        $availableProjects = $this->eligibleSourceProjectQuery()->get();
        return view('sales.projects.create', compact('sourceProject', 'availableProjects'));
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
            'customer_po_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'note' => ['nullable', 'string'],
        ]);

        $sourceProject = $this->eligibleSourceProjectQuery()->findOrFail($data['purchase_order_request_id']);
        $quotation = $sourceProject->quotation;
        $poFile = $data['customer_po_file'] ?? null;
        unset($data['purchase_order_request_id']);
        unset($data['customer_po_file']);
        $data['code'] = ($data['code'] ?? null) ?: $this->nextProjectCode($sourceProject);
        $data['quotation_id'] = $quotation->id;
        $data['customer_id'] = $quotation->customer_id;
        $data['project_value'] = $quotation->subtotal - $quotation->discount_amount;
        $data['tax_amount'] = $quotation->tax_amount;
        $data['total_value'] = $quotation->grand_total;
        $data['created_by'] = Auth::id();

        $project = Project::create($data);
        if ($poFile) {
            $oldPath = $sourceProject->customer_po_file;
            $newPath = $poFile->store('purchase-order-requests', 'public');
            $sourceProject->update(['customer_po_file' => $newPath]);
            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }
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
            'workflow.productionUpdater', 'workflow.qcUpdater', 'workflow.qcInstallationUpdater', 'workflow.deliveryUpdater',
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
        $qcChecklistDefinition = ProjectWorkflow::qcChecklistDefinition($project, $showPrices);
        $workflowHistory = $project->workflowHistory()->with('user')->orderByDesc('id')->get();
        $latestHistoryEntry = $project->workflowHistory()->with('user')->orderByDesc('id')->first();

        return view('projects.workspace', compact('project', 'workflow', 'fabricationDocuments', 'productionProgressDocuments', 'qcChecklistDefinition', 'showPrices', 'workflowHistory', 'latestHistoryEntry'));
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

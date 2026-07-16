<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Project;
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
        if (Auth::user()->isSales()) {
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
        $quotation = $request->get('quotation')
            ? $this->eligibleQuotationQuery()->with('customer')->findOrFail($request->get('quotation'))
            : null;
        $wonQuotations = $this->eligibleQuotationQuery()->get();
        $managers = User::assignableSales();
        $team = User::where('is_active', true)->get();
        return view('sales.projects.create', compact('quotation', 'wonQuotations', 'managers', 'team'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'quotation_id' => ['required', 'exists:quotations,id'],
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
                    ->where('role', 'sales')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'internal_team' => ['nullable', 'array'],
            'internal_team.*' => [Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'external_vendor' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $quotation = $this->eligibleQuotationQuery()->findOrFail($data['quotation_id']);
        $data['code'] = ($data['code'] ?? null) ?: CodeGenerator::next(Project::class, 'PRJ', 4, true);
        $data['customer_id'] = $quotation->customer_id;
        $data['project_value'] = $quotation->subtotal - $quotation->discount_amount;
        $data['tax_amount'] = $quotation->tax_amount;
        $data['total_value'] = $quotation->grand_total;
        $data['internal_team'] = array_values($data['internal_team'] ?? []);
        $data['created_by'] = Auth::id();

        $project = Project::create($data);
        Logger::record('created', "Project {$project->name} dibuat dari penawaran {$quotation->code}", $project);

        return redirect()->route('sales.projects.show', $project)->with('success', 'Project berhasil dibuat.');
    }

    public function show(Project $project)
    {
        abort_unless($this->canViewProject($project), 403);
        $project->load('customer', 'projectManager', 'quotation', 'terms', 'activities', 'documents');
        return view('sales.projects.show', compact('project'));
    }

    protected function eligibleQuotationQuery(): Builder
    {
        return Quotation::with('sales')
            ->whereIn('status', Quotation::wonStatuses())
            ->whereDoesntHave('project')
            ->when(Auth::user()->isSales(), fn ($query) => $query->where('sales_id', Auth::id()))
            ->latest();
    }

    protected function canViewProject(Project $project): bool
    {
        $user = Auth::user();
        if (! $user->isSales()) {
            return true;
        }

        $project->loadMissing('quotation');

        return (int) $project->project_manager_id === (int) $user->id
            || (int) ($project->quotation?->sales_id) === (int) $user->id;
    }
}

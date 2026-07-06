<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\DesignRequest;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PipelineController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isSales = $user->isSales();
        $salesId = Auth::id();

        $salesScope = fn (Builder $q, string $column = 'sales_id') => $isSales ? $q->where($column, $salesId) : $q;
        $canOpenAdmin = in_array($user->role, ['administrator', 'sales_admin'], true);
        $canOpenSales = $user->role === 'sales';
        $canOpenSpv = in_array($user->role, ['administrator', 'sales_spv'], true);

        $cards = [
            [
                'label' => 'Pra Lead Menunggu Sales',
                'count' => PraLead::where('status', 'waiting_acceptance')->count(),
                'route' => $canOpenAdmin ? route('admin.pra-leads.index', ['status' => 'waiting_acceptance']) : '#',
                'icon' => 'bi-percent',
            ],
            [
                'label' => 'Lead Aktif',
                'count' => $salesScope(Lead::query())->where('status', 'aktif')->count(),
                'route' => $canOpenSales ? route('sales.leads.index') : '#',
                'icon' => 'bi-person-lines-fill',
            ],
            [
                'label' => 'Design Berjalan',
                'count' => $salesScope(DesignRequest::query())->whereIn('status', ['assigned', 'drafting', 'costing', 'review'])->count(),
                'route' => $canOpenSales ? route('sales.design-requests.index') : '#',
                'icon' => 'bi-pencil-square',
            ],
            [
                'label' => 'Menunggu Approval SPV',
                'count' => $salesScope(Quotation::query())->where('status', 'waiting_approval')->count(),
                'route' => $canOpenSpv ? route('spv.quotation-approvals.index', ['status' => 'waiting_approval']) : ($canOpenSales ? route('sales.quotations.index', ['status' => 'waiting_approval']) : '#'),
                'icon' => 'bi-check2-square',
            ],
            [
                'label' => 'Revisi Penawaran',
                'count' => $salesScope(Quotation::query())->where('status', 'revision')->count(),
                'route' => $canOpenSales ? route('sales.quotations.index', ['status' => 'revision']) : ($canOpenSpv ? route('spv.quotation-approvals.index', ['status' => 'revision']) : '#'),
                'icon' => 'bi-arrow-repeat',
            ],
            [
                'label' => 'Request PO Open',
                'count' => PurchaseOrderRequest::whereIn('status', ['submitted', 'processing_accurate'])->count(),
                'route' => $canOpenAdmin ? route('admin.purchase-order-requests.index') : '#',
                'icon' => 'bi-receipt',
            ],
        ];

        $leadPipeline = $salesScope(Lead::query())
            ->selectRaw('stage, count(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        $designPipeline = $salesScope(DesignRequest::with('sales')->latest())
            ->whereIn('status', ['assigned', 'drafting', 'costing', 'review', 'completed'])
            ->limit(10)
            ->get();

        $quotationPipeline = $salesScope(Quotation::with('sales', 'designRequest', 'purchaseOrderRequest')->latest())
            ->whereIn('status', ['draft', 'waiting_approval', 'revision', 'approved', 'sent_to_customer', 'customer_accepted', 'request_po_created'])
            ->limit(12)
            ->get();

        $requestPoPipeline = PurchaseOrderRequest::with('quotation.sales')
            ->whereIn('status', ['submitted', 'processing_accurate'])
            ->latest()
            ->limit(10)
            ->get();

        $sla = [
            'design_overdue' => $salesScope(DesignRequest::query())
                ->whereNotIn('status', ['completed', 'rejected'])
                ->whereDate('deadline', '<', now()->toDateString())
                ->count(),
            'approval_overdue' => $salesScope(Quotation::query())
                ->where('status', 'waiting_approval')
                ->where('submitted_for_approval_at', '<', now()->subDays(2))
                ->count(),
            'po_overdue' => PurchaseOrderRequest::whereIn('status', ['submitted', 'processing_accurate'])
                ->whereDate('request_date', '<', now()->subDays(3)->toDateString())
                ->count(),
        ];

        return view('shared.pipeline.index', compact('cards', 'leadPipeline', 'designPipeline', 'quotationPipeline', 'requestPoPipeline', 'sla'));
    }
}

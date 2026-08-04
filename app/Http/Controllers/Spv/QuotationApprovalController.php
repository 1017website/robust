<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\Http\Request;

class QuotationApprovalController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with('sales')->latest();
        if ($search = $request->get('q')) {
            $query->where(fn ($scope) => $scope
                ->where('code', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('project_name', 'like', "%{$search}%")
                ->orWhereHas('sales', fn ($sales) => $sales->where('name', 'like', "%{$search}%")));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $quotations = $query->paginate(12)->withQueryString();
        $stats = [
            'today' => Quotation::whereDate('created_at', today())->count(),
            'month' => Quotation::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
            'ready' => Quotation::where('status', 'ready')->count(),
            'sales' => Quotation::whereMonth('created_at', now()->month)->distinct('sales_id')->count('sales_id'),
        ];

        return view('spv.quotation_approvals.index', compact('quotations', 'stats'));
    }

    public function show(Quotation $quotation)
    {
        $quotation->load('items.itemMaster', 'sales', 'designRequest', 'lead', 'documents.uploader', 'approvalHistories.user');

        return view('spv.quotation_approvals.show', compact('quotation'));
    }
}

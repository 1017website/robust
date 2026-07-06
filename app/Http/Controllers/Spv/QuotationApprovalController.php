<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationApprovalHistory;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuotationApprovalController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with('sales')
            ->whereIn('status', ['waiting_approval', 'revision', 'approved', 'rejected'])
            ->latest('submitted_for_approval_at');

        if ($s = $request->get('q')) {
            $query->where(fn ($w) => $w->where('code', 'like', "%$s%")
                ->orWhere('customer_name', 'like', "%$s%")
                ->orWhere('project_name', 'like', "%$s%"));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $quotations = $query->paginate(12)->withQueryString();

        $stats = [
            'waiting' => Quotation::where('status', 'waiting_approval')->count(),
            'approved_month' => Quotation::where('status', 'approved')->whereMonth('approved_at', now()->month)->count(),
            'revision' => Quotation::where('status', 'revision')->count(),
            'rejected_month' => Quotation::where('status', 'rejected')->whereMonth('rejected_at', now()->month)->count(),
        ];

        return view('spv.quotation_approvals.index', compact('quotations', 'stats'));
    }

    public function show(Quotation $quotation)
    {
        $quotation->load('items', 'sales', 'designRequest', 'designRequest.lead', 'lead', 'approvedBy', 'rejectedBy', 'approvalHistories.user');
        return view('spv.quotation_approvals.show', compact('quotation'));
    }

    public function approve(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array($quotation->status, ['waiting_approval', 'revision', 'rejected'], true)) {
            return back()->with('error', 'Penawaran ini tidak sedang menunggu approval.');
        }

        $oldStatus = $quotation->status;

        $quotation->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_note' => $data['approval_note'] ?? null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_note' => null,
            'revision_note' => null,
        ]);

        $this->recordHistory($quotation, 'approved', $oldStatus, 'approved', $data['approval_note'] ?? null);
        Logger::record('approved', "Penawaran {$quotation->code} disetujui SPV", $quotation);
        return redirect()->route('spv.quotation-approvals.show', $quotation)->with('success', 'Penawaran berhasil di-approve. Sales sudah bisa download PDF.');
    }

    public function revision(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'revision_note' => ['required', 'string', 'max:1000'],
        ]);

        if (! in_array($quotation->status, ['waiting_approval', 'approved'], true)) {
            return back()->with('error', 'Penawaran ini tidak bisa diminta revisi pada status saat ini.');
        }

        $oldStatus = $quotation->status;

        $quotation->update([
            'status' => 'revision',
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'revision_note' => $data['revision_note'],
        ]);

        $this->recordHistory($quotation, 'revision', $oldStatus, 'revision', $data['revision_note']);
        Logger::record('revision', "Penawaran {$quotation->code} diminta revisi SPV", $quotation);
        return redirect()->route('spv.quotation-approvals.show', $quotation)->with('success', 'Catatan revisi berhasil dikirim ke sales.');
    }

    public function reject(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'rejection_note' => ['required', 'string', 'max:1000'],
        ]);

        if (! in_array($quotation->status, ['waiting_approval', 'revision'], true)) {
            return back()->with('error', 'Penawaran ini tidak bisa ditolak pada status saat ini.');
        }

        $oldStatus = $quotation->status;

        $quotation->update([
            'status' => 'rejected',
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'rejection_note' => $data['rejection_note'],
        ]);

        $this->recordHistory($quotation, 'rejected', $oldStatus, 'rejected', $data['rejection_note']);
        Logger::record('rejected', "Penawaran {$quotation->code} ditolak SPV", $quotation);
        return redirect()->route('spv.quotation-approvals.show', $quotation)->with('success', 'Penawaran berhasil ditolak.');
    }

    protected function recordHistory(Quotation $quotation, string $action, ?string $from, ?string $to, ?string $note = null): void
    {
        $quotation->refresh()->load('items');

        QuotationApprovalHistory::create([
            'quotation_id' => $quotation->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'status_from' => $from,
            'status_to' => $to,
            'note' => $note,
            'snapshot' => [
                'subtotal' => $quotation->subtotal,
                'discount_amount' => $quotation->discount_amount,
                'tax_amount' => $quotation->tax_amount,
                'additional_total' => $quotation->additional_total,
                'grand_total' => $quotation->grand_total,
                'target_margin' => $quotation->target_margin,
                'items_count' => $quotation->items->count(),
            ],
        ]);
    }
}


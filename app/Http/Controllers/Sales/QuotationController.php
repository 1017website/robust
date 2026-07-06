<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Quotation;
use App\Models\QuotationApprovalHistory;
use App\Services\CodeGenerator;
use App\Services\Logger;
use App\Services\QuotationCalculator;
use App\Services\SimpleQuotationPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with('customer', 'sales')->where('sales_id', Auth::id())->latest();

        if ($s = $request->get('q')) {
            $query->where(fn ($w) => $w->where('customer_name', 'like', "%$s%")
                ->orWhere('project_name', 'like', "%$s%")
                ->orWhere('code', 'like', "%$s%"));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $quotations = $query->paginate(10)->withQueryString();
        return view('sales.quotations.index', compact('quotations'));
    }

    public function create(Request $request)
    {
        $designRequest = $request->get('dr') ? DesignRequest::with('items', 'lead', 'customer')->find($request->get('dr')) : null;

        if ($designRequest && $designRequest->sales_id !== Auth::id()) {
            abort(403, 'Design request ini bukan milik Anda.');
        }

        $customers = Customer::orderBy('name')->get();
        $completedDR = DesignRequest::where('status', 'completed')->where('sales_id', Auth::id())->get();
        return view('sales.quotations.create', compact('designRequest', 'customers', 'completedDR'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $designRequest = $this->resolveDesignRequest($data['design_request_id'] ?? null);
        $link = $this->resolveLeadAndCustomer($data, $designRequest);

        $action = $request->input('action');
        $submitApproval = in_array($action, ['send', 'submit_approval'], true);

        $quotation = DB::transaction(function () use ($data, $submitApproval, $designRequest, $link) {
            $quotation = Quotation::create([
                'code' => CodeGenerator::next(Quotation::class, 'Q', 4, true),
                'design_request_id' => $designRequest?->id,
                'lead_id' => $link['lead_id'],
                'customer_id' => $link['customer_id'],
                'customer_name' => $data['customer_name'],
                'pic_name' => $data['pic_name'] ?? null,
                'project_name' => $data['project_name'],
                'sales_id' => Auth::id(),
                'delivery_method' => $data['delivery_method'],
                'quote_date' => $data['quote_date'],
                'valid_until' => $data['valid_until'],
                'priority' => $data['priority'],
                'currency' => $data['currency'],
                'internal_note' => $data['internal_note'] ?? null,
                'customer_note' => $data['customer_note'] ?? null,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'] ?? 0,
                'discount_reason' => $data['discount_reason'] ?? null,
                'tax_percent' => $data['tax_percent'],
                'target_margin' => $data['target_margin'] ?? 0,
                'additional_costs' => array_values($data['additional_costs'] ?? []),
                'status' => $submitApproval ? 'waiting_approval' : 'draft',
                'submitted_for_approval_at' => $submitApproval ? now() : null,
                'created_by' => Auth::id(),
            ]);

            $this->syncItems($quotation, $data['items']);
            $quotation->load('items', 'designRequest');
            QuotationCalculator::recalculate($quotation)->save();

            if ($quotation->lead) {
                $quotation->lead->update(['stage' => 'penawaran']);
            }

            $this->recordHistory(
                $quotation,
                $submitApproval ? 'submitted' : 'created',
                null,
                $quotation->status,
                $submitApproval ? 'Penawaran dibuat dan langsung diajukan ke SPV.' : 'Penawaran disimpan sebagai draft.'
            );

            return $quotation;
        });

        Logger::record('created', "Penawaran {$quotation->code} dibuat", $quotation);
        $message = $submitApproval
            ? 'Penawaran berhasil dibuat dan diajukan ke SPV untuk approval.'
            : 'Penawaran berhasil disimpan sebagai draft.';

        return redirect()->route('sales.quotations.show', $quotation)->with('success', $message);
    }

    public function show(Quotation $quotation)
    {
        $this->ensureOwner($quotation);

        $quotation->load('items', 'customer', 'sales', 'designRequest', 'designRequest.lead', 'lead', 'approvedBy', 'rejectedBy', 'purchaseOrderRequest', 'approvalHistories.user');
        return view('sales.quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        $this->ensureOwner($quotation);

        if (! $quotation->canBeEdited()) {
            return redirect()->route('sales.quotations.show', $quotation)->with('error', 'Penawaran hanya bisa diedit saat Draft, Revisi, atau Ditolak SPV.');
        }

        $quotation->load('items', 'designRequest');
        $designRequest = $quotation->designRequest;
        $customers = Customer::orderBy('name')->get();
        $completedDR = DesignRequest::where('status', 'completed')->where('sales_id', Auth::id())->get();

        return view('sales.quotations.edit', compact('quotation', 'designRequest', 'customers', 'completedDR'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        $this->ensureOwner($quotation);

        if (! $quotation->canBeEdited()) {
            return redirect()->route('sales.quotations.show', $quotation)->with('error', 'Penawaran hanya bisa diedit saat Draft, Revisi, atau Ditolak SPV.');
        }

        $data = $this->validatedData($request);
        $designRequest = $this->resolveDesignRequest($data['design_request_id'] ?? null);
        $link = $this->resolveLeadAndCustomer($data, $designRequest);
        $oldStatus = $quotation->status;
        $action = $request->input('action');
        $submitApproval = in_array($action, ['send', 'submit_approval'], true);

        DB::transaction(function () use ($quotation, $data, $designRequest, $link, $oldStatus, $submitApproval) {
            $quotation->update([
                'design_request_id' => $designRequest?->id,
                'lead_id' => $link['lead_id'],
                'customer_id' => $link['customer_id'],
                'customer_name' => $data['customer_name'],
                'pic_name' => $data['pic_name'] ?? null,
                'project_name' => $data['project_name'],
                'delivery_method' => $data['delivery_method'],
                'quote_date' => $data['quote_date'],
                'valid_until' => $data['valid_until'],
                'priority' => $data['priority'],
                'currency' => $data['currency'],
                'internal_note' => $data['internal_note'] ?? null,
                'customer_note' => $data['customer_note'] ?? null,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'] ?? 0,
                'discount_reason' => $data['discount_reason'] ?? null,
                'tax_percent' => $data['tax_percent'],
                'target_margin' => $data['target_margin'] ?? 0,
                'additional_costs' => array_values($data['additional_costs'] ?? []),
                'status' => $submitApproval ? 'waiting_approval' : 'draft',
                'submitted_for_approval_at' => $submitApproval ? now() : $quotation->submitted_for_approval_at,
                'approved_by' => null,
                'approved_at' => null,
                'approval_note' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_note' => null,
                'revision_note' => null,
            ]);

            $this->syncItems($quotation, $data['items']);
            $quotation->load('items', 'designRequest');
            QuotationCalculator::recalculate($quotation)->save();

            if ($quotation->lead) {
                $quotation->lead->update(['stage' => 'penawaran']);
            }

            $this->recordHistory(
                $quotation,
                $submitApproval ? 'submitted' : 'updated',
                $oldStatus,
                $quotation->status,
                $submitApproval ? 'Penawaran direvisi dan diajukan ulang ke SPV.' : 'Penawaran diperbarui sebagai draft.'
            );
        });

        Logger::record('updated', "Penawaran {$quotation->code} diperbarui", $quotation);

        return redirect()->route('sales.quotations.show', $quotation)->with('success', $submitApproval ? 'Revisi penawaran berhasil diajukan ulang ke SPV.' : 'Penawaran berhasil diperbarui.');
    }

    public function submitApproval(Quotation $quotation)
    {
        $this->ensureOwner($quotation);

        if (! $quotation->canBeSubmittedForApproval()) {
            return back()->with('error', 'Penawaran ini tidak bisa diajukan approval pada status saat ini.');
        }

        $oldStatus = $quotation->status;
        $quotation->update([
            'status' => 'waiting_approval',
            'submitted_for_approval_at' => now(),
            'approved_by' => null,
            'approved_at' => null,
            'approval_note' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_note' => null,
            'revision_note' => null,
        ]);

        $this->recordHistory($quotation, 'submitted', $oldStatus, 'waiting_approval', 'Penawaran diajukan ke SPV.');
        Logger::record('submitted', "Penawaran {$quotation->code} diajukan approval SPV", $quotation);

        return back()->with('success', 'Penawaran berhasil diajukan ke SPV.');
    }

    public function downloadPdf(Quotation $quotation, SimpleQuotationPdf $pdf)
    {
        $this->ensureOwner($quotation);

        if (! $quotation->canDownloadPdf()) {
            return back()->with('error', 'PDF penawaran hanya bisa didownload setelah disetujui SPV.');
        }

        $quotation->load('items', 'sales', 'approvedBy');
        $filename = str($quotation->code ?: 'penawaran')->replace(['/', '\\'], '-')->slug('-')->toString().'.pdf';

        return response($pdf->make($quotation), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function markSentToCustomer(Quotation $quotation)
    {
        $this->ensureOwner($quotation);

        if (! $quotation->canDownloadPdf()) {
            return back()->with('error', 'Penawaran harus disetujui SPV sebelum dikirim ke customer.');
        }

        $oldStatus = $quotation->status;
        $quotation->update([
            'status' => 'sent_to_customer',
            'sent_at' => now(),
        ]);

        $this->recordHistory($quotation, 'sent_to_customer', $oldStatus, 'sent_to_customer', 'Penawaran ditandai sudah dikirim ke customer.');
        Logger::record('sent', "Penawaran {$quotation->code} dikirim ke customer", $quotation);
        return back()->with('success', 'Penawaran ditandai sudah dikirim ke customer.');
    }

    public function markWon(Request $request, Quotation $quotation)
    {
        $this->ensureOwner($quotation);

        if (! in_array($quotation->status, ['approved', 'sent_to_customer', 'negotiation', 'sent'], true)) {
            return back()->with('error', 'Penawaran belum bisa ditandai customer setuju.');
        }

        $oldStatus = $quotation->status;
        $quotation->update([
            'status' => 'customer_accepted',
            'customer_response_at' => now(),
            'customer_response_note' => $request->input('note'),
        ]);
        if ($quotation->lead) {
            $quotation->lead->update(['stage' => 'won', 'status' => 'won']);
        }
        $this->recordHistory($quotation, 'customer_accepted', $oldStatus, 'customer_accepted', $request->input('note'));
        Logger::record('customer_accepted', "Penawaran {$quotation->code} disetujui customer", $quotation);
        return back()->with('success', 'Penawaran ditandai customer setuju. Sales Admin sudah bisa membuat Request PO.');
    }

    public function markLost(Request $request, Quotation $quotation)
    {
        $this->ensureOwner($quotation);

        $oldStatus = $quotation->status;
        $quotation->update([
            'status' => 'customer_rejected',
            'customer_response_at' => now(),
            'customer_response_note' => $request->input('note'),
        ]);
        if ($quotation->lead) {
            $quotation->lead->update(['stage' => 'lost', 'status' => 'lost']);
        }
        $this->recordHistory($quotation, 'customer_rejected', $oldStatus, 'customer_rejected', $request->input('note'));
        Logger::record('customer_rejected', "Penawaran {$quotation->code} tidak disetujui customer", $quotation);
        return back()->with('success', 'Penawaran ditandai tidak disetujui customer.');
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'design_request_id' => ['nullable', 'exists:design_requests,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'project_name' => ['required', 'string', 'max:255'],
            'delivery_method' => ['required', 'in:email,whatsapp,hardcopy'],
            'quote_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:quote_date'],
            'priority' => ['required', 'in:low,medium,high'],
            'currency' => ['required', 'string', 'max:10'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'discount_type' => ['required', 'in:percent,nominal'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'tax_percent' => ['required', 'numeric', 'min:0'],
            'target_margin' => ['nullable', 'numeric', 'min:0'],
            'additional_costs' => ['nullable', 'array'],
            'additional_costs.*.label' => ['nullable', 'string', 'max:100'],
            'additional_costs.*.amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.margin' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    protected function resolveDesignRequest(?int $designRequestId): ?DesignRequest
    {
        if (! $designRequestId) {
            return null;
        }

        $designRequest = DesignRequest::with('lead', 'customer')->findOrFail($designRequestId);
        if ($designRequest->sales_id !== Auth::id()) {
            abort(403, 'Design request ini bukan milik Anda.');
        }

        return $designRequest;
    }

    protected function resolveLeadAndCustomer(array $data, ?DesignRequest $designRequest): array
    {
        return [
            'lead_id' => $designRequest?->lead_id,
            'customer_id' => $designRequest?->customer_id ?: ($data['customer_id'] ?? null),
        ];
    }

    protected function syncItems(Quotation $quotation, array $items): void
    {
        $quotation->items()->delete();

        foreach ($items as $i => $item) {
            $qty = (float) $item['qty'];
            $unitPrice = (float) $item['unit_price'];
            $quotation->items()->create([
                'category' => $item['category'] ?? null,
                'name' => $item['name'],
                'specification' => $item['specification'] ?? null,
                'qty' => $qty,
                'unit' => $item['unit'] ?? 'Unit',
                'unit_price' => $unitPrice,
                'margin' => $item['margin'] ?? 0,
                'total' => $qty * $unitPrice,
                'sort_order' => $i,
            ]);
        }
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

    protected function ensureOwner(Quotation $quotation): void
    {
        if ($quotation->sales_id !== Auth::id()) {
            abort(403, 'Penawaran ini bukan milik Anda.');
        }
    }
}

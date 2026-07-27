<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\DesignRequestItem;
use App\Models\ItemMaster;
use App\Models\Quotation;
use App\Models\QuotationApprovalHistory;
use App\Services\CodeGenerator;
use App\Services\Logger;
use App\Services\QuotationCalculator;
use App\Services\QuotationExcelExporter;
use App\Services\SimpleQuotationPdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with('customer', 'sales')
            ->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))
            ->latest();

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
        $designRequest = $request->get('dr')
            ? $this->accessibleCompletedDesignRequests()->with('items')->find($request->get('dr'))
            : null;

        if ($request->filled('dr') && ! $designRequest) {
            abort(404, 'Design Request selesai tidak ditemukan atau bukan milik Anda.');
        }

        $customers = Customer::when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))->orderBy('name')->get();
        $completedDR = $this->accessibleCompletedDesignRequests()->get();
        $itemMasters = ItemMaster::where('is_active', true)->orderBy('category')->orderBy('name')->get();

        return view('sales.quotations.create', compact('designRequest', 'customers', 'completedDR', 'itemMasters'));
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
                'sales_id' => $this->quotationSalesId($designRequest),
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
                'target_margin' => 0,
                'additional_costs' => array_values($data['additional_costs'] ?? []),
                'status' => $submitApproval ? 'waiting_approval' : 'draft',
                'submitted_for_approval_at' => $submitApproval ? now() : null,
                'created_by' => Auth::id(),
            ]);

            $this->syncItems($quotation, $data['items']);
            $this->storeQuotationDocuments($quotation, $data['documents'] ?? []);
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

        $quotation->load('items.itemMaster', 'customer', 'sales', 'designRequest', 'designRequest.lead', 'lead', 'documents.uploader', 'approvedBy', 'rejectedBy', 'purchaseOrderRequest', 'approvalHistories.user');

        return view('sales.quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        $this->ensureOwner($quotation);

        if (! $quotation->canBeEdited()) {
            return redirect()->route('sales.quotations.show', $quotation)->with('error', 'Penawaran hanya bisa diedit saat Draft, Revisi, atau Ditolak SPV.');
        }

        $quotation->load('items', 'designRequest', 'documents.uploader');
        $designRequest = $quotation->designRequest;
        $customers = Customer::when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))->orderBy('name')->get();
        $completedDR = $this->accessibleCompletedDesignRequests()->get();
        $itemMasters = ItemMaster::where('is_active', true)->orderBy('category')->orderBy('name')->get();

        return view('sales.quotations.edit', compact('quotation', 'designRequest', 'customers', 'completedDR', 'itemMasters'));
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
                'target_margin' => 0,
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
            $this->storeQuotationDocuments($quotation, $data['documents'] ?? []);
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

    public function downloadExcel(Quotation $quotation, QuotationExcelExporter $excel)
    {
        $this->ensureOwner($quotation);

        if (! $quotation->canDownloadPdf()) {
            return back()->with('error', 'Excel penawaran hanya bisa didownload setelah disetujui SPV.');
        }

        return $excel->download($quotation);
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

        if (! in_array($quotation->status, ['approved', 'sent_to_customer', 'negotiation', 'sent'], true)) {
            return back()->with('error', 'Penawaran belum bisa ditandai customer tidak setuju.');
        }

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
            'design_request_id' => ['nullable', 'exists:design_requests,id,deleted_at,NULL'],
            'customer_id' => ['nullable', 'exists:customers,id,deleted_at,NULL'],
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
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
            'discount_type' => ['required', 'in:percent,nominal'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'tax_percent' => ['required', 'numeric', 'min:0'],
            'additional_costs' => ['nullable', 'array'],
            'additional_costs.*.label' => ['nullable', 'string', 'max:100'],
            'additional_costs.*.amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:quotation_items,id'],
            'items.*.source_design_request_item_id' => ['nullable', 'integer', 'exists:design_request_items,id'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.item_master_id' => ['nullable', 'exists:item_masters,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.variant' => ['nullable', 'string', 'max:255'],
            'items.*.specification' => ['nullable', 'string', 'max:12000'],
            'items.*.quotation_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.margin' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'items.*.is_optional' => ['nullable', 'boolean'],
        ]);
    }

    protected function resolveDesignRequest(?int $designRequestId): ?DesignRequest
    {
        if (! $designRequestId) {
            return null;
        }

        $designRequest = DesignRequest::with('lead', 'customer')->findOrFail($designRequestId);
        if (! $this->canAccessDesignRequestForQuotation($designRequest)) {
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
        $existingItems = $quotation->items()->get()->keyBy('id');
        $quotation->items()->delete();

        foreach ($items as $i => $item) {
            $existing = ! empty($item['id']) ? $existingItems->get((int) $item['id']) : null;
            abort_if(! empty($item['id']) && ! $existing, 422, 'Item penawaran tidak sesuai.');

            $sourceItem = null;
            if (! empty($item['source_design_request_item_id'])) {
                $sourceItem = DesignRequestItem::query()
                    ->where('id', $item['source_design_request_item_id'])
                    ->when($quotation->design_request_id, fn ($query) => $query->where('design_request_id', $quotation->design_request_id))
                    ->firstOrFail();
            }

            $qty = (float) $item['qty'];
            $costPrice = (float) ($item['cost_price'] ?? $item['unit_price'] ?? 0);
            $unitPrice = array_key_exists('unit_price', $item)
                ? (float) $item['unit_price']
                : QuotationCalculator::sellingPrice($costPrice, (float) ($item['margin'] ?? 0));
            $margin = $unitPrice > 0
                ? min(max((($unitPrice - $costPrice) / $unitPrice) * 100, 0), 99.99)
                : 0;
            [$imagePath, $imageName] = $this->snapshotQuotationImage(
                $quotation,
                $sourceItem,
                $existing,
                $item['quotation_image'] ?? null,
            );

            $quotation->items()->create([
                'source_design_request_item_id' => $sourceItem?->id,
                'category' => $item['category'] ?? null,
                'item_master_id' => $item['item_master_id'] ?? null,
                'name' => $item['name'],
                'variant' => $item['variant'] ?? null,
                'specification' => $item['specification'] ?? null,
                'quotation_image_path' => $imagePath,
                'quotation_image_name' => $imageName,
                'qty' => $qty,
                'unit' => $item['unit'] ?? 'Unit',
                'cost_price' => $costPrice,
                'unit_price' => $unitPrice,
                'margin' => $margin,
                'is_optional' => ! empty($item['is_optional']),
                'total' => round($qty * $unitPrice, 2),
                'sort_order' => $i,
            ]);
        }
    }

    protected function storeQuotationDocuments(Quotation $quotation, array $documents): void
    {
        foreach ($documents as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store("quotation-documents/{$quotation->id}", 'public');

            $quotation->documents()->create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'category' => 'quotation_support',
                'file_path' => $path,
                'file_type' => strtolower($file->getClientOriginalExtension()),
                'file_size' => $file->getSize(),
                'version' => 'v1.0',
                'revision_number' => 1,
                'is_current' => true,
                'uploaded_by' => Auth::id(),
            ]);
        }
    }

    protected function snapshotQuotationImage(
        Quotation $quotation,
        ?DesignRequestItem $sourceItem,
        $existing,
        ?UploadedFile $uploadedImage = null,
    ): array {
        if ($uploadedImage) {
            $target = $uploadedImage->store("quotation-snapshots/{$quotation->id}", 'public');

            return [$target, $uploadedImage->getClientOriginalName()];
        }

        if ($existing?->quotation_image_path && Storage::disk('public')->exists($existing->quotation_image_path)) {
            return [$existing->quotation_image_path, $existing->quotation_image_name];
        }

        if ($sourceItem?->quotation_image_path && Storage::disk('public')->exists($sourceItem->quotation_image_path)) {
            $extension = strtolower(pathinfo($sourceItem->quotation_image_path, PATHINFO_EXTENSION)) ?: 'png';
            $target = "quotation-snapshots/{$quotation->id}/item-{$sourceItem->id}-".str()->random(8).".{$extension}";
            Storage::disk('public')->put($target, Storage::disk('public')->get($sourceItem->quotation_image_path));

            return [$target, $sourceItem->quotation_image_name ?: basename($sourceItem->quotation_image_path)];
        }

        return [null, null];
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

    protected function customerQuery()
    {
        return Customer::query()
            ->when(Auth::user()->isSales(), fn ($query) => $query->where('sales_id', Auth::id()));
    }

    protected function accessibleCompletedDesignRequests()
    {
        return DesignRequest::with('customer', 'lead', 'sales')
            ->where('status', 'completed')
            ->when(Auth::user()->isSales(), function ($query) {
                $query->where(function ($scope) {
                    $scope->where('sales_id', Auth::id())
                        ->orWhereHas('lead', fn ($lead) => $lead->where('sales_id', Auth::id()))
                        ->orWhereHas('customer', fn ($customer) => $customer->where('sales_id', Auth::id()));
                });
            })
            ->latest();
    }

    protected function canAccessDesignRequestForQuotation(DesignRequest $designRequest): bool
    {
        if (! Auth::user()->isSales()) {
            return true;
        }

        $userId = (int) Auth::id();

        return (int) $designRequest->sales_id === $userId
            || (int) optional($designRequest->lead)->sales_id === $userId
            || (int) optional($designRequest->customer)->sales_id === $userId;
    }

    protected function quotationSalesId(?DesignRequest $designRequest): int
    {
        if (Auth::user()->isSales()) {
            return (int) Auth::id();
        }

        return (int) ($designRequest?->sales_id ?: Auth::id());
    }

    protected function ensureOwner(Quotation $quotation): void
    {
        if (Auth::user()->isSales() && (int) $quotation->sales_id !== (int) Auth::id()) {
            abort(403, 'Penawaran ini bukan milik Anda.');
        }
    }
}

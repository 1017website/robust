<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Services\CodeGenerator;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrderRequest::with('quotation.sales', 'requester')->latest();

        if ($s = $request->get('q')) {
            $query->where(fn ($w) => $w->where('code', 'like', "%$s%")
                ->orWhere('customer_po_number', 'like', "%$s%")
                ->orWhere('accurate_po_number', 'like', "%$s%")
                ->orWhere('delivery_pic_name', 'like', "%$s%")
                ->orWhereHas('quotation', fn ($q) => $q->where('code', 'like', "%$s%")
                    ->orWhere('customer_name', 'like', "%$s%")
                    ->orWhere('project_name', 'like', "%$s%")));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(12)->withQueryString();
        return view('admin.purchase_order_requests.index', compact('requests'));
    }

    public function create(Request $request)
    {
        $quotation = $request->get('quotation') ? Quotation::with('sales', 'purchaseOrderRequest')->find($request->get('quotation')) : null;
        $quotations = Quotation::with('sales')
            ->whereIn('status', ['approved', 'sent_to_customer', 'customer_accepted'])
            ->whereDoesntHave('purchaseOrderRequest')
            ->latest('approved_at')
            ->get();

        return view('admin.purchase_order_requests.create', compact('quotation', 'quotations'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request, true);

        $quotation = Quotation::with('purchaseOrderRequest')->findOrFail($data['quotation_id']);
        if (! $quotation->canCreatePurchaseOrderRequest()) {
            return back()->withInput()->with('error', 'Request PO hanya bisa dibuat dari penawaran yang sudah approved / dikirim / disetujui customer dan belum pernah dibuatkan Request PO.');
        }

        if ($request->hasFile('customer_po_file')) {
            $data['customer_po_file'] = $request->file('customer_po_file')->store('purchase-order-requests', 'public');
        }

        $checklist = $this->normalizedChecklist($data['checklist'] ?? []);

        $poRequest = PurchaseOrderRequest::create([
            'code' => CodeGenerator::next(PurchaseOrderRequest::class, 'RPO', 4, true),
            'quotation_id' => $quotation->id,
            'requested_by' => Auth::id(),
            'request_date' => $data['request_date'],
            'customer_po_number' => $data['customer_po_number'] ?? null,
            'customer_po_file' => $data['customer_po_file'] ?? null,
            'delivery_address' => $data['delivery_address'] ?? null,
            'delivery_pic_name' => $data['delivery_pic_name'] ?? null,
            'delivery_pic_phone' => $data['delivery_pic_phone'] ?? null,
            'npwp_name' => $data['npwp_name'] ?? null,
            'npwp_number' => $data['npwp_number'] ?? null,
            'payment_term' => $data['payment_term'] ?? null,
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'checklist' => $checklist,
            'checklist_completed_at' => $this->isChecklistComplete($checklist) ? now() : null,
            'admin_note' => $data['admin_note'] ?? null,
            'status' => 'submitted',
        ]);

        $quotation->update(['status' => 'request_po_created']);

        Logger::record('created', "Request PO {$poRequest->code} dibuat dari penawaran {$quotation->code}", $poRequest);

        return redirect()->route('admin.purchase-order-requests.show', $poRequest)->with('success', 'Request PO berhasil dibuat. Lanjutkan proses PO di Accurate.');
    }

    public function show(PurchaseOrderRequest $purchaseOrderRequest)
    {
        $purchaseOrderRequest->load('quotation.items', 'quotation.sales', 'requester');
        return view('admin.purchase_order_requests.show', ['requestPo' => $purchaseOrderRequest]);
    }

    public function update(Request $request, PurchaseOrderRequest $purchaseOrderRequest)
    {
        $data = $request->validate([
            'status' => ['required', 'in:submitted,processing_accurate,po_created,cancelled'],
            'accurate_po_number' => ['nullable', 'string', 'max:100'],
            'accurate_po_date' => ['nullable', 'date'],
            'accurate_note' => ['nullable', 'string', 'max:1500'],
            'delivery_address' => ['nullable', 'string', 'max:1500'],
            'delivery_pic_name' => ['nullable', 'string', 'max:255'],
            'delivery_pic_phone' => ['nullable', 'string', 'max:50'],
            'npwp_name' => ['nullable', 'string', 'max:255'],
            'npwp_number' => ['nullable', 'string', 'max:100'],
            'payment_term' => ['nullable', 'string', 'max:255'],
            'expected_delivery_date' => ['nullable', 'date'],
            'checklist' => ['nullable', 'array'],
        ]);

        $checklist = $this->normalizedChecklist($data['checklist'] ?? []);

        $purchaseOrderRequest->update([
            'status' => $data['status'],
            'accurate_po_number' => $data['accurate_po_number'] ?? null,
            'accurate_po_date' => $data['accurate_po_date'] ?? null,
            'accurate_note' => $data['accurate_note'] ?? null,
            'delivery_address' => $data['delivery_address'] ?? null,
            'delivery_pic_name' => $data['delivery_pic_name'] ?? null,
            'delivery_pic_phone' => $data['delivery_pic_phone'] ?? null,
            'npwp_name' => $data['npwp_name'] ?? null,
            'npwp_number' => $data['npwp_number'] ?? null,
            'payment_term' => $data['payment_term'] ?? null,
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'checklist' => $checklist,
            'checklist_completed_at' => $this->isChecklistComplete($checklist) ? now() : null,
            'processed_at' => in_array($data['status'], ['processing_accurate', 'po_created'], true) ? now() : $purchaseOrderRequest->processed_at,
        ]);

        Logger::record('updated', "Status Request PO {$purchaseOrderRequest->code} diperbarui", $purchaseOrderRequest);

        return back()->with('success', 'Request PO berhasil diperbarui.');
    }

    protected function validatedData(Request $request, bool $creating = false): array
    {
        $rules = [
            'request_date' => ['required', 'date'],
            'customer_po_number' => ['nullable', 'string', 'max:100'],
            'customer_po_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:5120'],
            'delivery_address' => ['nullable', 'string', 'max:1500'],
            'delivery_pic_name' => ['nullable', 'string', 'max:255'],
            'delivery_pic_phone' => ['nullable', 'string', 'max:50'],
            'npwp_name' => ['nullable', 'string', 'max:255'],
            'npwp_number' => ['nullable', 'string', 'max:100'],
            'payment_term' => ['nullable', 'string', 'max:255'],
            'expected_delivery_date' => ['nullable', 'date'],
            'checklist' => ['nullable', 'array'],
            'admin_note' => ['nullable', 'string', 'max:1500'],
        ];

        if ($creating) {
            $rules['quotation_id'] = ['required', 'exists:quotations,id', 'unique:purchase_order_requests,quotation_id'];
        }

        return $request->validate($rules);
    }

    protected function normalizedChecklist(array $input): array
    {
        $items = [];
        foreach (PurchaseOrderRequest::checklistItems() as $key => $label) {
            $items[$key] = ! empty($input[$key]);
        }

        return $items;
    }

    protected function isChecklistComplete(array $checklist): bool
    {
        return collect(PurchaseOrderRequest::checklistItems())->keys()->every(fn ($key) => ! empty($checklist[$key]));
    }
}

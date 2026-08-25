<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\Logger;
use App\Services\OperationalDocumentPdf;
use App\Services\ProjectProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrderRequest::with('quotation.sales', 'requester')->latest()
            ->when(Auth::user()->isSales(), fn ($query) => $query->whereHas('quotation', fn ($quotation) => $quotation->where('sales_id', Auth::id())));

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
        $quotationQuery = Quotation::with('sales', 'customer.primaryPic', 'purchaseOrderRequest')
            ->when(Auth::user()->isSales(), fn ($query) => $query->where('sales_id', Auth::id()));
        $quotation = $request->get('quotation') ? (clone $quotationQuery)->findOrFail($request->get('quotation')) : null;
        $quotations = Quotation::with('sales', 'customer.primaryPic')
            ->whereIn('status', ['approved', 'sent_to_customer', 'customer_accepted'])
            ->whereDoesntHave('purchaseOrderRequest')
            ->when(Auth::user()->isSales(), fn ($query) => $query->where('sales_id', Auth::id()))
            ->latest('approved_at')
            ->get();
        $salesList = User::assignableSales();

        return view('admin.purchase_order_requests.create', compact('quotation', 'quotations', 'salesList'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request, true);

        if ($request->hasFile('customer_po_file')) {
            $data['customer_po_file'] = $request->file('customer_po_file')->store('purchase-order-requests', 'public');
        }

        $checklist = $this->normalizedChecklist($data['checklist'] ?? []);
        $isExternal = $data['purchase_source'] === 'external';
        $quotation = null;

        if (! $isExternal) {
            $quotation = Quotation::with('purchaseOrderRequest')->findOrFail($data['quotation_id']);
            abort_if(Auth::user()->isSales() && (int) $quotation->sales_id !== (int) Auth::id(), 403);
            if (! $quotation->canCreatePurchaseOrderRequest()) {
                return back()->withInput()->with('error', 'Request PO hanya bisa dibuat dari penawaran yang sudah siap/dikirim/disetujui customer dan belum pernah dibuatkan Request PO.');
            }
        }

        [$poRequest, $quotation] = DB::transaction(function () use ($data, $checklist, $isExternal, $quotation) {
            if ($isExternal) {
                $salesId = Auth::user()->isSales() ? Auth::id() : $data['external_sales_id'];
                $externalReference = trim((string) ($data['external_quotation_number'] ?? ''));
                $quotation = Quotation::create([
                    'code' => CodeGenerator::next(Quotation::class, 'EXTQ', 4, true),
                    'customer_name' => $data['customer_name'],
                    'pic_name' => $data['delivery_pic_name'] ?? null,
                    'project_name' => $data['external_project_name'],
                    'sales_id' => $salesId,
                    'delivery_method' => 'hardcopy',
                    'quote_date' => $data['request_date'],
                    'priority' => 'medium',
                    'currency' => 'IDR',
                    'creation_mode' => 'external',
                    'internal_note' => 'Penawaran dibuat di luar CRM'.($externalReference !== '' ? '. Nomor referensi: '.$externalReference : '.'),
                    'subtotal' => $data['external_order_value'],
                    'tax_percent' => 0,
                    'tax_amount' => 0,
                    'grand_total' => $data['external_order_value'],
                    'status' => 'request_po_created',
                    'created_by' => Auth::id(),
                ]);
            }

            $poRequest = PurchaseOrderRequest::create([
                'code' => CodeGenerator::next(PurchaseOrderRequest::class, 'RPO', 4, true),
                'quotation_id' => $quotation->id,
                'customer_id' => $quotation->customer_id,
                'project_number' => $data['project_number'],
                'customer_name' => $data['customer_name'],
                'customer_area' => $data['customer_area'] ?? null,
                'customer_division' => $data['customer_division'] ?? null,
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

            if ($quotation->status !== 'request_po_created') {
                $quotation->update(['status' => 'request_po_created']);
            }
            return [$poRequest, $quotation];
        });

        Logger::record(
            'created',
            $isExternal
                ? "Request PO {$poRequest->code} dibuat dari PO existing / penawaran luar CRM"
                : "Request PO {$poRequest->code} dibuat dari penawaran {$quotation->code}",
            $poRequest
        );

        return redirect()->route('admin.purchase-order-requests.show', $poRequest)->with('success', 'Request PO berhasil dibuat. Lanjutkan proses PO di Accurate.');
    }

    public function show(PurchaseOrderRequest $purchaseOrderRequest)
    {
        abort_if(Auth::user()->isSales() && (int) $purchaseOrderRequest->quotation?->sales_id !== (int) Auth::id(), 403);
        $purchaseOrderRequest->load('quotation.items', 'quotation.sales', 'quotation.project.workflow', 'requester', 'invoice');
        return view('admin.purchase_order_requests.show', ['requestPo' => $purchaseOrderRequest]);
    }

    public function downloadPdf(PurchaseOrderRequest $purchaseOrderRequest, OperationalDocumentPdf $pdf)
    {
        abort_if(Auth::user()->isSales() && (int) $purchaseOrderRequest->quotation?->sales_id !== (int) Auth::id(), 403);

        $filename = trim((string) preg_replace(
            '/[^\pL\pN._-]+/u',
            '-',
            $purchaseOrderRequest->project_number ?: $purchaseOrderRequest->code ?: 'request-po'
        ), '-_.').'.pdf';

        return response($pdf->makeRequestPo($purchaseOrderRequest), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function update(Request $request, PurchaseOrderRequest $purchaseOrderRequest, ProjectProvisioner $projectProvisioner)
    {
        abort_unless(Auth::user()->isAdminLevel(), 403, 'Update proses Request PO hanya untuk Administrator.');
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(PurchaseOrderRequest::statuses()))],
            'accurate_po_number' => ['nullable', 'required_if:status,po_created', 'string', 'max:100'],
            'accurate_po_date' => ['nullable', 'required_if:status,po_created', 'date'],
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
        $actor = $request->user();

        $project = DB::transaction(function () use ($data, $checklist, $purchaseOrderRequest, $projectProvisioner, $actor) {
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
                'processed_at' => in_array($data['status'], ['processing_accurate', 'po_created', 'production', 'installation', 'invoicing', 'paid'], true) ? now() : $purchaseOrderRequest->processed_at,
            ]);

            return $data['status'] === 'po_created'
                ? $projectProvisioner->fromAccuratePurchaseOrder($purchaseOrderRequest->fresh(), $actor)
                : null;
        });

        Logger::record('updated', "Status Request PO {$purchaseOrderRequest->code} diperbarui", $purchaseOrderRequest);

        return back()->with(
            'success',
            $project
                ? ($project->quotation?->design_request_id
                    ? "PO Accurate tersimpan. Project {$project->code} otomatis dibuat dan diteruskan ke Drafter."
                    : "PO Accurate tersimpan. Project {$project->code} otomatis dibuat dan langsung masuk ke Produksi.")
                : 'Request PO berhasil diperbarui.'
        );
    }

    protected function validatedData(Request $request, bool $creating = false): array
    {
        if ($creating && ! $request->filled('purchase_source')) {
            $request->merge(['purchase_source' => 'crm']);
        }

        $rules = [
            'purchase_source' => ['required', Rule::in(['crm', 'external'])],
            'project_number' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_area' => ['nullable', 'string', 'max:255'],
            'customer_division' => ['nullable', 'string', 'max:255'],
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
            'external_project_name' => ['nullable', 'required_if:purchase_source,external', 'string', 'max:255'],
            'external_quotation_number' => ['nullable', 'string', 'max:100'],
            'external_order_value' => ['nullable', 'required_if:purchase_source,external', 'numeric', 'min:0.01'],
            'external_sales_id' => [
                Rule::requiredIf(fn () => $request->input('purchase_source') === 'external' && ! Auth::user()->isSales()),
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'sales')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
        ];

        if ($creating) {
            $rules['quotation_id'] = [
                Rule::excludeIf(fn () => $request->input('purchase_source') === 'external'),
                Rule::requiredIf(fn () => $request->input('purchase_source') === 'crm'),
                'nullable',
                'exists:quotations,id',
                'unique:purchase_order_requests,quotation_id',
            ];
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

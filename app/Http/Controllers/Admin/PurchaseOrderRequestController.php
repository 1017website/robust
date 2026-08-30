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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PurchaseOrderRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrderRequest::with('quotation.sales', 'requester')->latest()
            ->when(Auth::user()->isSales(), fn ($query) => $query->where(fn ($scope) => $scope
                ->where('requested_by', Auth::id())
                ->orWhereHas('quotation', fn ($quotation) => $quotation->where('sales_id', Auth::id()))));

        if ($s = $request->get('q')) {
            $query->where(fn ($w) => $w->where('code', 'like', "%$s%")
                ->orWhere('customer_po_number', 'like', "%$s%")
                ->orWhere('accurate_po_number', 'like', "%$s%")
                ->orWhere('delivery_pic_name', 'like', "%$s%")
                ->orWhere('customer_name', 'like', "%$s%")
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
        $quotation = $request->get('quotation')
            ? Quotation::with('sales', 'customer.primaryPic', 'purchaseOrderRequest')
                ->when(Auth::user()->isSales(), fn ($query) => $query->where('sales_id', Auth::id()))
                ->findOrFail($request->get('quotation'))
            : null;

        return view('admin.purchase_order_requests.create', [
            'requestPo' => null,
            'quotation' => $quotation,
            'quotations' => $this->selectableQuotations(),
            'salesList' => User::assignableSales(),
        ]);
    }

    /** Melanjutkan pengisian Request PO yang masih berstatus draf. */
    public function edit(PurchaseOrderRequest $purchaseOrderRequest)
    {
        $this->authorizeAccess($purchaseOrderRequest);
        abort_unless($purchaseOrderRequest->isDraft(), 403, 'Hanya Request PO berstatus draf yang dapat diubah.');

        return view('admin.purchase_order_requests.create', [
            'requestPo' => $purchaseOrderRequest,
            'quotation' => $purchaseOrderRequest->quotation?->load('sales', 'customer.primaryPic'),
            'quotations' => $this->selectableQuotations($purchaseOrderRequest),
            'salesList' => User::assignableSales(),
        ]);
    }

    public function store(Request $request)
    {
        $asDraft = $this->wantsDraft($request);
        $data = $this->validatedData($request, true, $asDraft);

        if ($request->hasFile('customer_po_file')) {
            $data['customer_po_file'] = $request->file('customer_po_file')->store('purchase-order-requests', 'public');
        }

        $isExternal = $data['purchase_source'] === 'external';
        $quotation = null;

        if (! $isExternal && ! empty($data['quotation_id'])) {
            $quotation = Quotation::with('purchaseOrderRequest')->findOrFail($data['quotation_id']);
            abort_if(Auth::user()->isSales() && (int) $quotation->sales_id !== (int) Auth::id(), 403);
            if (! $quotation->canCreatePurchaseOrderRequest()) {
                return back()->withInput()->with('error', 'Request PO hanya bisa dibuat dari penawaran yang sudah siap/dikirim/disetujui customer dan belum pernah dibuatkan Request PO.');
            }
        }

        $poRequest = DB::transaction(function () use ($data, $isExternal, $asDraft, $quotation) {
            if ($isExternal && ! $asDraft) {
                $quotation = $this->createExternalQuotation($data);
            }

            $poRequest = PurchaseOrderRequest::create($this->attributes($data, $quotation) + [
                'code' => CodeGenerator::next(PurchaseOrderRequest::class, 'RPO', 4, true),
                'requested_by' => Auth::id(),
                'status' => $asDraft ? 'draft' : 'submitted',
            ]);

            if (! $asDraft && $quotation && $quotation->status !== 'request_po_created') {
                $quotation->update(['status' => 'request_po_created']);
            }

            return $poRequest;
        });

        Logger::record(
            'created',
            $asDraft
                ? "Draf Request PO {$poRequest->code} disimpan"
                : ($isExternal
                    ? "Request PO {$poRequest->code} dibuat dari PO existing / penawaran luar CRM"
                    : "Request PO {$poRequest->code} dibuat dari penawaran ".($poRequest->quotation?->code ?: '-')),
            $poRequest
        );

        return redirect()
            ->route('admin.purchase-order-requests.show', $poRequest)
            ->with('success', $asDraft
                ? 'Draf Request PO tersimpan. Lengkapi datanya kapan saja lalu ajukan.'
                : 'Request PO berhasil dibuat. Lanjutkan proses PO di Accurate.');
    }

    /** Menyimpan ulang draf, atau mengajukannya setelah lengkap. */
    public function updateDraft(Request $request, PurchaseOrderRequest $purchaseOrderRequest)
    {
        $this->authorizeAccess($purchaseOrderRequest);
        abort_unless($purchaseOrderRequest->isDraft(), 403, 'Hanya Request PO berstatus draf yang dapat diubah.');

        $asDraft = $this->wantsDraft($request);
        $data = $this->validatedData($request, true, $asDraft, $purchaseOrderRequest);

        if ($request->hasFile('customer_po_file')) {
            $data['customer_po_file'] = $request->file('customer_po_file')->store('purchase-order-requests', 'public');
        }

        $isExternal = $data['purchase_source'] === 'external';
        $quotation = $purchaseOrderRequest->quotation;

        if ($isExternal) {
            // Catatan penawaran eksternal milik draf ini saja yang boleh dipakai ulang.
            $quotation = $quotation?->isExternal() ? $quotation : null;
        } elseif (! empty($data['quotation_id'])) {
            $quotation = Quotation::with('purchaseOrderRequest')->findOrFail($data['quotation_id']);
            abort_if(Auth::user()->isSales() && (int) $quotation->sales_id !== (int) Auth::id(), 403);
            if ((int) $quotation->id !== (int) $purchaseOrderRequest->quotation_id && ! $quotation->canCreatePurchaseOrderRequest()) {
                return back()->withInput()->with('error', 'Request PO hanya bisa dibuat dari penawaran yang sudah siap/dikirim/disetujui customer dan belum pernah dibuatkan Request PO.');
            }
        } elseif ($quotation?->isExternal()) {
            $quotation = null;
        }

        DB::transaction(function () use ($data, $isExternal, $asDraft, $purchaseOrderRequest, &$quotation) {
            if ($isExternal && ! $asDraft && ! $quotation) {
                $quotation = $this->createExternalQuotation($data);
            }
            if ($isExternal && $quotation) {
                $quotation->update([
                    'customer_name' => $data['customer_name'],
                    'project_name' => $data['external_project_name'],
                    'subtotal' => $data['external_order_value'],
                    'grand_total' => $data['external_order_value'],
                ]);
            }

            $purchaseOrderRequest->update($this->attributes($data, $quotation) + [
                'status' => $asDraft ? 'draft' : 'submitted',
            ]);

            if (! $asDraft && $quotation && $quotation->status !== 'request_po_created') {
                $quotation->update(['status' => 'request_po_created']);
            }
        });

        Logger::record(
            'updated',
            $asDraft ? "Draf Request PO {$purchaseOrderRequest->code} diperbarui" : "Request PO {$purchaseOrderRequest->code} diajukan",
            $purchaseOrderRequest
        );

        return redirect()
            ->route('admin.purchase-order-requests.show', $purchaseOrderRequest)
            ->with('success', $asDraft
                ? 'Draf Request PO tersimpan.'
                : 'Request PO berhasil diajukan. Lanjutkan proses PO di Accurate.');
    }

    /**
     * Simpan checklist Request PO.
     *
     * Terbuka untuk setiap akun yang berhak atas Request PO ini (Administrator dan
     * Sales pemiliknya), termasuk saat masih berstatus draf, sehingga item yang tidak
     * diperlukan bisa dihapus dan item sendiri bisa ditambahkan.
     */
    public function updateChecklist(Request $request, PurchaseOrderRequest $purchaseOrderRequest)
    {
        $this->authorizeAccess($purchaseOrderRequest);

        $data = $request->validate([
            'checklist' => ['nullable', 'array', 'max:40'],
            'checklist.*.key' => ['nullable', 'string', 'max:60'],
            'checklist.*.label' => ['required_with:checklist', 'string', 'max:255'],
            'checklist.*.checked' => ['nullable', 'boolean'],
        ]);

        $purchaseOrderRequest->update($this->checklistAttributes($data['checklist'] ?? []));

        return back()->with('success', 'Checklist kelengkapan diperbarui.');
    }

    public function show(PurchaseOrderRequest $purchaseOrderRequest)
    {
        $this->authorizeAccess($purchaseOrderRequest);
        $purchaseOrderRequest->load('quotation.items', 'quotation.sales', 'quotation.project.workflow', 'requester', 'invoice');

        return view('admin.purchase_order_requests.show', ['requestPo' => $purchaseOrderRequest]);
    }

    public function downloadPdf(PurchaseOrderRequest $purchaseOrderRequest, OperationalDocumentPdf $pdf)
    {
        $this->authorizeAccess($purchaseOrderRequest);
        abort_if($purchaseOrderRequest->isDraft(), 403, 'Draf Request PO belum dapat diekspor. Ajukan request terlebih dahulu.');

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
        abort_unless(Auth::user()->canManageBackOffice(), 403, 'Update proses Request PO hanya untuk Administrator dan Sales.');
        abort_if($purchaseOrderRequest->isDraft(), 403, 'Draf Request PO harus diajukan terlebih dahulu.');

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(PurchaseOrderRequest::processStatuses()))],
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
        ]);

        $actor = $request->user();

        $project = DB::transaction(function () use ($data, $purchaseOrderRequest, $projectProvisioner, $actor) {
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

    protected function wantsDraft(Request $request): bool
    {
        return $request->input('action') === 'draft';
    }

    protected function authorizeAccess(PurchaseOrderRequest $purchaseOrderRequest): void
    {
        if (! Auth::user()->isSales()) {
            return;
        }

        abort_unless(
            (int) $purchaseOrderRequest->requested_by === (int) Auth::id()
                || (int) $purchaseOrderRequest->quotation?->sales_id === (int) Auth::id(),
            403
        );
    }

    /** Penawaran yang belum punya Request PO, ditambah penawaran milik draf yang sedang diubah. */
    protected function selectableQuotations(?PurchaseOrderRequest $requestPo = null)
    {
        return Quotation::with('sales', 'customer.primaryPic')
            ->whereIn('status', ['approved', 'sent_to_customer', 'customer_accepted'])
            ->where(fn ($query) => $query
                ->whereDoesntHave('purchaseOrderRequest')
                ->when($requestPo?->quotation_id, fn ($scope, $id) => $scope->orWhere('id', $id)))
            ->when(Auth::user()->isSales(), fn ($query) => $query->where('sales_id', Auth::id()))
            ->latest('approved_at')
            ->get();
    }

    protected function createExternalQuotation(array $data): Quotation
    {
        $externalReference = trim((string) ($data['external_quotation_number'] ?? ''));

        return Quotation::create([
            'code' => CodeGenerator::next(Quotation::class, 'EXTQ', 4, true),
            'customer_name' => $data['customer_name'],
            'pic_name' => $data['delivery_pic_name'] ?? null,
            'project_name' => $data['external_project_name'],
            'sales_id' => Auth::user()->isSales() ? Auth::id() : $data['external_sales_id'],
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

    /**
     * Ubah item checklist yang dikirim form menjadi bentuk simpan.
     *
     * Item yang dihapus pengguna tidak ikut terkirim, sehingga hilang dengan
     * sendirinya. Item baru tanpa key mendapat key dari labelnya.
     */
    protected function normalizedChecklist(array $input): array
    {
        $items = [];
        $used = [];

        foreach ($input as $row) {
            $label = trim((string) (is_array($row) ? ($row['label'] ?? '') : $row));
            if ($label === '') {
                continue;
            }

            $key = trim((string) (is_array($row) ? ($row['key'] ?? '') : ''));
            if ($key === '') {
                $key = Str::slug($label, '_') ?: 'item';
            }
            while (in_array($key, $used, true)) {
                $key .= '_x';
            }
            $used[] = $key;

            $items[] = [
                'key' => $key,
                'label' => $label,
                'checked' => (bool) (is_array($row) ? filter_var($row['checked'] ?? false, FILTER_VALIDATE_BOOLEAN) : false),
            ];
        }

        return $items;
    }

    protected function checklistAttributes(?array $input): array
    {
        $checklist = $this->normalizedChecklist($input ?? []);
        $complete = $checklist !== [] && collect($checklist)->every(fn ($item) => $item['checked']);

        return [
            'checklist' => $checklist,
            'checklist_completed_at' => $complete ? now() : null,
        ];
    }

    protected function attributes(array $data, ?Quotation $quotation): array
    {
        return [
            'quotation_id' => $quotation?->id,
            'customer_id' => $quotation?->customer_id,
            'project_number' => $data['project_number'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_area' => $data['customer_area'] ?? null,
            'customer_division' => $data['customer_division'] ?? null,
            'request_date' => $data['request_date'] ?? null,
            'customer_po_number' => $data['customer_po_number'] ?? null,
            'delivery_address' => $data['delivery_address'] ?? null,
            'delivery_pic_name' => $data['delivery_pic_name'] ?? null,
            'delivery_pic_phone' => $data['delivery_pic_phone'] ?? null,
            'npwp_name' => $data['npwp_name'] ?? null,
            'npwp_number' => $data['npwp_number'] ?? null,
            'payment_term' => $data['payment_term'] ?? null,
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'admin_note' => $data['admin_note'] ?? null,
        ]
            + (array_key_exists('customer_po_file', $data) ? ['customer_po_file' => $data['customer_po_file']] : [])
            + (array_key_exists('checklist', $data) ? $this->checklistAttributes($data['checklist']) : []);
    }

    /**
     * Saat menyimpan draf seluruh isian boleh kosong; kelengkapan baru divalidasi
     * penuh ketika Request PO benar-benar diajukan.
     */
    protected function validatedData(Request $request, bool $withQuotationRule = false, bool $asDraft = false, ?PurchaseOrderRequest $current = null): array
    {
        if (! $request->filled('purchase_source')) {
            $request->merge(['purchase_source' => 'crm']);
        }

        // Form selalu mengirim penanda checklist. Tanpa penanda (mis. request lama
        // atau API), checklist yang tersimpan dibiarkan apa adanya.
        if ($request->boolean('checklist_present') && ! $request->has('checklist')) {
            $request->merge(['checklist' => []]);
        }

        $required = fn (string ...$rules) => $asDraft ? ['nullable', ...$rules] : ['required', ...$rules];

        $rules = [
            'purchase_source' => ['required', Rule::in(['crm', 'external'])],
            'project_number' => $required('string', 'max:100'),
            'customer_name' => $required('string', 'max:255'),
            'customer_area' => ['nullable', 'string', 'max:255'],
            'customer_division' => ['nullable', 'string', 'max:255'],
            'request_date' => $required('date'),
            'customer_po_number' => ['nullable', 'string', 'max:100'],
            'customer_po_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:5120'],
            'delivery_address' => ['nullable', 'string', 'max:1500'],
            'delivery_pic_name' => ['nullable', 'string', 'max:255'],
            'delivery_pic_phone' => ['nullable', 'string', 'max:50'],
            'npwp_name' => ['nullable', 'string', 'max:255'],
            'npwp_number' => ['nullable', 'string', 'max:100'],
            'payment_term' => ['nullable', 'string', 'max:255'],
            'expected_delivery_date' => ['nullable', 'date'],
            'admin_note' => ['nullable', 'string', 'max:1500'],
            'checklist' => ['nullable', 'array', 'max:40'],
            'checklist.*.key' => ['nullable', 'string', 'max:60'],
            'checklist.*.label' => ['required_with:checklist', 'string', 'max:255'],
            'checklist.*.checked' => ['nullable', 'boolean'],
            'external_project_name' => $asDraft
                ? ['nullable', 'string', 'max:255']
                : ['nullable', 'required_if:purchase_source,external', 'string', 'max:255'],
            'external_quotation_number' => ['nullable', 'string', 'max:100'],
            'external_order_value' => $asDraft
                ? ['nullable', 'numeric', 'min:0']
                : ['nullable', 'required_if:purchase_source,external', 'numeric', 'min:0.01'],
            'external_sales_id' => [
                Rule::requiredIf(fn () => ! $asDraft && $request->input('purchase_source') === 'external' && ! Auth::user()->isSales()),
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'sales')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
        ];

        if ($withQuotationRule) {
            $rules['quotation_id'] = [
                Rule::excludeIf(fn () => $request->input('purchase_source') === 'external'),
                Rule::requiredIf(fn () => ! $asDraft && $request->input('purchase_source') === 'crm'),
                'nullable',
                'exists:quotations,id',
                Rule::unique('purchase_order_requests', 'quotation_id')->ignore($current?->id),
            ];
        }

        return $request->validate($rules);
    }
}

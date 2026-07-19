<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceTerm;
use App\Models\PurchaseOrderRequest;
use App\Services\CodeGenerator;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with('purchaseOrderRequest.quotation.sales', 'terms')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($scope) => $scope
                ->where('code', 'like', '%'.$request->q.'%')->orWhere('customer_name', 'like', '%'.$request->q.'%')
                ->orWhere('project_name', 'like', '%'.$request->q.'%')->orWhere('project_number', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()->paginate(12)->withQueryString();
        return view('admin.invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $requestPo = PurchaseOrderRequest::with('quotation.items', 'quotation.customer', 'invoice')->findOrFail($request->integer('request_po'));
        abort_unless($requestPo->canCreateInvoice(), 422, 'Invoice untuk Request PO ini sudah ada atau Request PO dibatalkan.');
        return view('admin.invoices.create', compact('requestPo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'purchase_order_request_id' => ['required', 'exists:purchase_order_requests,id', 'unique:invoices,purchase_order_request_id'],
            'invoice_date' => ['required', 'date'], 'note' => ['nullable', 'string', 'max:1500'],
            'terms' => ['required', 'array', 'min:1'], 'terms.*.description' => ['nullable', 'string', 'max:255'],
            'terms.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'terms.*.amount' => ['required', 'numeric', 'min:0.01'], 'terms.*.due_date' => ['nullable', 'date'],
        ]);
        $requestPo = PurchaseOrderRequest::with('quotation')->findOrFail($data['purchase_order_request_id']);
        abort_unless($requestPo->canCreateInvoice(), 422, 'Request PO tidak dapat dibuatkan invoice.');
        $quotation = $requestPo->quotation;
        $termsTotal = collect($data['terms'])->sum(fn ($term) => (float) $term['amount']);
        abort_if(round($termsTotal, 2) !== round((float) $quotation->grand_total, 2), 422, 'Total seluruh termin harus sama dengan grand total penawaran.');

        $invoice = DB::transaction(function () use ($data, $requestPo, $quotation) {
            $installation = collect($quotation->additional_costs ?? [])->filter(fn ($cost) => str_contains(strtolower($cost['label'] ?? ''), 'instal'))->sum('amount');
            $invoice = Invoice::create([
                'code' => CodeGenerator::next(Invoice::class, 'INV', 4, true), 'purchase_order_request_id' => $requestPo->id,
                'invoice_date' => $data['invoice_date'], 'customer_name' => $requestPo->customer_name ?: $quotation->customer_name,
                'project_number' => $requestPo->project_number, 'project_name' => $quotation->project_name,
                'subtotal' => $quotation->subtotal, 'tax_amount' => $quotation->tax_amount,
                'installation_amount' => $installation, 'grand_total' => $quotation->grand_total,
                'status' => 'issued', 'note' => $data['note'] ?? null, 'created_by' => Auth::id(),
            ]);
            foreach (array_values($data['terms']) as $index => $term) {
                $invoice->terms()->create($term + ['term_number' => $index + 1, 'status' => 'planned']);
            }
            $requestPo->update(['status' => 'invoicing']);
            return $invoice;
        });
        Logger::record('created', "Invoice {$invoice->code} diterbitkan", $invoice);
        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice dan termin pembayaran berhasil diterbitkan.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('terms', 'purchaseOrderRequest.quotation.sales', 'creator');
        return view('admin.invoices.show', compact('invoice'));
    }

    public function updateTerm(Request $request, Invoice $invoice, InvoiceTerm $term)
    {
        abort_unless((int) $term->invoice_id === (int) $invoice->id, 404);
        $data = $request->validate([
            'status' => ['required', 'in:planned,issued,partial,paid,cancelled'], 'issued_date' => ['nullable', 'date'],
            'accurate_invoice_number' => ['nullable', 'string', 'max:100'], 'paid_amount' => ['nullable', 'numeric', 'min:0', 'max:'.$term->amount],
            'paid_date' => ['nullable', 'date'], 'note' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($invoice, $term, $data) {
            if ($data['status'] === 'paid') $data['paid_amount'] = $term->amount;
            $term->update($data);
            $paid = (float) $invoice->terms()->sum('paid_amount');
            $status = $paid >= (float) $invoice->grand_total ? 'paid' : ($paid > 0 ? 'partial' : 'issued');
            $invoice->update(['paid_total' => $paid, 'status' => $status]);
            $invoice->purchaseOrderRequest()->update(['status' => $status === 'paid' ? 'paid' : 'invoicing']);
        });
        return back()->with('success', 'Termin pembayaran berhasil diperbarui.');
    }
}

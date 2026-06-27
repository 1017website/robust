<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\CodeGenerator;
use App\Services\Logger;
use App\Services\QuotationCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with('customer', 'sales')->where('sales_id', Auth::id())->latest();

        if ($s = $request->get('q')) {
            $query->where(fn ($w) => $w->where('customer_name', 'like', "%$s%")->orWhere('project_name', 'like', "%$s%")->orWhere('code', 'like', "%$s%"));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $quotations = $query->paginate(10)->withQueryString();
        return view('sales.quotations.index', compact('quotations'));
    }

    public function create(Request $request)
    {
        $designRequest = $request->get('dr') ? DesignRequest::with('items')->find($request->get('dr')) : null;
        $customers = Customer::orderBy('name')->get();
        $completedDR = DesignRequest::where('status', 'completed')->where('sales_id', Auth::id())->get();
        return view('sales.quotations.create', compact('designRequest', 'customers', 'completedDR'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'design_request_id' => ['nullable', 'exists:design_requests,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'project_name' => ['required', 'string', 'max:255'],
            'delivery_method' => ['required', 'in:email,whatsapp,hardcopy'],
            'quote_date' => ['required', 'date'],
            'valid_until' => ['required', 'date'],
            'priority' => ['required', 'in:low,medium,high'],
            'currency' => ['required', 'string', 'max:10'],
            'internal_note' => ['nullable', 'string', 'max:500'],
            'customer_note' => ['nullable', 'string', 'max:300'],
            'discount_type' => ['required', 'in:percent,nominal'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'tax_percent' => ['required', 'numeric', 'min:0'],
            'target_margin' => ['nullable', 'numeric'],
            'additional_costs' => ['nullable', 'array'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string'],
            'items.*.qty' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $quotation = DB::transaction(function () use ($data, $request) {
            $quotation = Quotation::create([
                'code' => CodeGenerator::next(Quotation::class, 'Q', 4, true),
                'design_request_id' => $data['design_request_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
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
                'status' => $request->input('action') === 'send' ? 'sent' : 'draft',
                'sent_at' => $request->input('action') === 'send' ? now() : null,
                'created_by' => Auth::id(),
            ]);

            foreach ($data['items'] as $i => $item) {
                $total = (float) $item['qty'] * (float) $item['unit_price'];
                $quotation->items()->create([
                    'category' => $item['category'] ?? null,
                    'name' => $item['name'],
                    'specification' => $item['specification'] ?? null,
                    'qty' => $item['qty'],
                    'unit' => $item['unit'] ?? 'Unit',
                    'unit_price' => $item['unit_price'],
                    'margin' => $item['margin'] ?? 0,
                    'total' => $total,
                    'sort_order' => $i,
                ]);
            }

            $quotation->load('items');
            QuotationCalculator::recalculate($quotation)->save();

            return $quotation;
        });

        Logger::record('created', "Penawaran {$quotation->code} dibuat", $quotation);
        return redirect()->route('sales.quotations.show', $quotation)->with('success', 'Penawaran berhasil disimpan.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load('items', 'customer', 'sales', 'designRequest');
        return view('sales.quotations.show', compact('quotation'));
    }

    public function markWon(Quotation $quotation)
    {
        $quotation->update(['status' => 'won']);
        if ($quotation->lead) {
            $quotation->lead->update(['stage' => 'won', 'status' => 'won']);
        }
        Logger::record('won', "Penawaran {$quotation->code} menang", $quotation);
        return redirect()->route('sales.projects.create', ['quotation' => $quotation->id])->with('success', 'Penawaran ditandai Won. Lanjutkan buat project.');
    }

    public function markLost(Request $request, Quotation $quotation)
    {
        $quotation->update(['status' => 'lost']);
        Logger::record('lost', "Penawaran {$quotation->code} kalah", $quotation);
        return back()->with('success', 'Penawaran ditandai Lost.');
    }
}

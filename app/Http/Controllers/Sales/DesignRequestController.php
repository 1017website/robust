<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\DesignRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesignRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DesignRequest::with('productionPic', 'sales')
            ->where('sales_id', Auth::id())->latest();

        if ($s = $request->get('q')) {
            $query->where(fn ($w) => $w->where('customer_name', 'like', "%$s%")->orWhere('project_name', 'like', "%$s%"));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $designRequests = $query->paginate(8)->withQueryString();

        $stats = [
            'total' => DesignRequest::where('sales_id', Auth::id())->count(),
            'waiting' => DesignRequest::where('sales_id', Auth::id())->where('status', 'assigned')->count(),
            'progress' => DesignRequest::where('sales_id', Auth::id())->whereIn('status', ['drafting', 'costing', 'review'])->count(),
            'completed' => DesignRequest::where('sales_id', Auth::id())->where('status', 'completed')->count(),
        ];

        $selectedRequest = $designRequests->first();

        return view('sales.design_requests.index', compact('designRequests', 'stats', 'selectedRequest'));
    }

    public function create(Request $request)
    {
        $lead = $request->get('lead') ? Lead::find($request->get('lead')) : null;
        $drafters = User::where('role', 'drafter')->where('is_active', true)->get();
        return view('sales.design_requests.create', compact('lead', 'drafters'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id' => ['nullable', 'exists:leads,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'project_name' => ['required', 'string', 'max:255'],
            'request_date' => ['required', 'date'],
            'deadline' => ['required', 'date'],
            'priority' => ['required', 'in:low,medium,high'],
            'short_description' => ['required', 'string', 'max:500'],
            'lab_type' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'string', 'max:255'],
            'detail_need' => ['required', 'string', 'max:1000'],
            'scope_checklist' => ['nullable', 'array'],
            'outputs' => ['nullable', 'array'],
            'extra_note' => ['nullable', 'string', 'max:500'],
            'production_pic_id' => ['required', 'exists:users,id'],
            'production_note' => ['nullable', 'string', 'max:300'],
        ]);

        $data['code'] = CodeGenerator::next(DesignRequest::class, 'DR', 3);
        $data['sales_id'] = Auth::id();
        $data['created_by'] = Auth::id();
        $data['status'] = $request->input('action') === 'send' ? 'assigned' : 'draft';
        if ($lead = Lead::find($data['lead_id'] ?? null)) {
            $data['customer_id'] = $lead->customer_id;
            $lead->update(['stage' => 'design_request']);
        }

        $dr = DesignRequest::create($data);
        Logger::record('created', "Design Request {$dr->code} dibuat", $dr);

        return redirect()->route('sales.design-requests.index')->with('success', 'Design Request berhasil dikirim ke produksi.');
    }

    public function show(DesignRequest $designRequest)
    {
        $designRequest->load('items', 'productionPic', 'documents', 'sales');
        return view('sales.design_requests.show', compact('designRequest'));
    }
}

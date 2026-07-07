<?php

namespace App\Http\Controllers\Drafter;

use App\Http\Controllers\Controller;
use App\Models\DesignRequest;
use App\Models\User;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DesignRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DesignRequest::with('sales', 'productionPic', 'documents')
            ->orderByRaw('deadline is null, deadline asc')
            ->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($sales = $request->get('sales_id')) {
            $query->where('sales_id', $sales);
        }
        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }
        if ($date = $request->get('date')) {
            $query->whereDate('request_date', $date);
        }
        if ($s = $request->get('q')) {
            $query->where(fn ($w) => $w->where('customer_name', 'like', "%$s%")
                ->orWhere('project_name', 'like', "%$s%")
                ->orWhere('code', 'like', "%$s%"));
        }

        $designRequests = $query->paginate(8)->withQueryString();
        $selected = $designRequests->first()?->load('sales', 'documents', 'lead', 'items');
        $salesUsers = User::assignableSales();

        $stats = [
            'baru' => DesignRequest::whereIn('status', ['assigned', 'draft'])->count(),
            'drafting' => DesignRequest::where('status', 'drafting')->count(),
            'review' => DesignRequest::where('status', 'review')->count(),
            'completed' => DesignRequest::where('status', 'completed')->count(),
            'terlambat' => DesignRequest::whereNotIn('status', ['completed', 'rejected'])->whereDate('deadline', '<', today())->count(),
        ];

        return view('drafter.design_requests.index', compact('designRequests', 'stats', 'selected', 'salesUsers'));
    }

    public function show(DesignRequest $designRequest)
    {
        $designRequest->load('items', 'sales', 'documents', 'lead', 'customer');
        return view('drafter.design_requests.show', compact('designRequest'));
    }

    public function updateProgress(Request $request, DesignRequest $designRequest)
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'production_note' => ['nullable', 'string'],
        ]);
        $designRequest->update($data);
        Logger::record('progress', "Progress DR {$designRequest->code} diperbarui", $designRequest);
        return back()->with('success', 'Progress diperbarui.');
    }

    public function submitFeedback(Request $request, DesignRequest $designRequest)
    {
        $data = $request->validate([
            'dimensions' => ['nullable', 'array'],
            'materials' => ['nullable', 'array'],
            'accessories' => ['nullable', 'array'],
            'material_estimation' => ['nullable', 'array'],
            'cost_material' => ['nullable', 'numeric'],
            'cost_production' => ['nullable', 'numeric'],
            'cost_installation' => ['nullable', 'numeric'],
            'technical_note' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($designRequest, $data, $request) {
            $designRequest->update([
                'dimensions' => $data['dimensions'] ?? null,
                'materials' => $data['materials'] ?? null,
                'accessories' => $data['accessories'] ?? null,
                'material_estimation' => $data['material_estimation'] ?? null,
                'cost_material' => $data['cost_material'] ?? 0,
                'cost_production' => $data['cost_production'] ?? 0,
                'cost_installation' => $data['cost_installation'] ?? 0,
                'cost_total' => ($data['cost_material'] ?? 0) + ($data['cost_production'] ?? 0) + ($data['cost_installation'] ?? 0),
                'technical_note' => $data['technical_note'] ?? null,
                'status' => match ($request->input('action')) {
                    'submit' => 'completed',
                    'review' => 'review',
                    default => $designRequest->status ?: 'drafting',
                },
                'progress' => match ($request->input('action')) {
                    'submit' => 100,
                    'review' => max((int) $designRequest->progress, 75),
                    default => max((int) $designRequest->progress, 25),
                },
                'submitted_at' => $request->input('action') === 'submit' ? now() : $designRequest->submitted_at,
            ]);

            if ($request->input('action') === 'submit' && ! empty($data['items'])) {
                $designRequest->items()->delete();
                foreach ($data['items'] as $item) {
                    if (empty($item['name'])) continue;
                    $designRequest->items()->create([
                        'category' => $item['category'] ?? null,
                        'name' => $item['name'],
                        'specification' => $item['specification'] ?? null,
                        'qty' => $item['qty'] ?? 1,
                        'unit' => $item['unit'] ?? 'Unit',
                        'unit_price' => $item['unit_price'] ?? 0,
                        'margin' => $item['margin'] ?? 0,
                        'total' => ($item['qty'] ?? 1) * ($item['unit_price'] ?? 0),
                    ]);
                }
            }
        });

        Logger::record('submitted', "DR {$designRequest->code} diperbarui oleh drafter", $designRequest);

        $message = $request->input('action') === 'submit'
            ? 'Berhasil submit final ke sales.'
            : 'Feedback design request berhasil disimpan.';

        return redirect()->route('drafter.design-requests.index')->with('success', $message);
    }
}

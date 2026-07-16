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
            ->where('production_pic_id', Auth::id())
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
            'baru' => DesignRequest::where('production_pic_id', Auth::id())->whereIn('status', ['assigned', 'draft'])->count(),
            'drafting' => DesignRequest::where('production_pic_id', Auth::id())->where('status', 'drafting')->count(),
            'review' => DesignRequest::where('production_pic_id', Auth::id())->where('status', 'review')->count(),
            'completed' => DesignRequest::where('production_pic_id', Auth::id())->where('status', 'completed')->count(),
            'terlambat' => DesignRequest::where('production_pic_id', Auth::id())->whereNotIn('status', ['completed', 'rejected'])->whereDate('deadline', '<', today())->count(),
        ];

        return view('drafter.design_requests.index', compact('designRequests', 'stats', 'selected', 'salesUsers'));
    }

    public function show(DesignRequest $designRequest)
    {
        $this->ensureAssigned($designRequest);
        $designRequest->load('items', 'sales', 'documents', 'lead', 'customer');
        return view('drafter.design_requests.show', compact('designRequest'));
    }

    public function updateProgress(Request $request, DesignRequest $designRequest)
    {
        $this->ensureAssigned($designRequest);
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(DesignRequest::statuses()))],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'production_note' => ['nullable', 'string'],
        ]);
        $designRequest->update($data);
        Logger::record('progress', "Progress DR {$designRequest->code} diperbarui", $designRequest);
        return back()->with('success', 'Progress diperbarui.');
    }

    public function submitFeedback(Request $request, DesignRequest $designRequest)
    {
        $this->ensureAssigned($designRequest);
        $data = $request->validate([
            'action' => ['required', 'in:save,review,submit'],
            'dimensions' => ['nullable', 'array'],
            'dimensions.*.item' => ['nullable', 'string', 'max:255'],
            'dimensions.*.size' => ['nullable', 'string', 'max:255'],
            'materials' => ['nullable', 'array'],
            'materials.*.item' => ['nullable', 'string', 'max:255'],
            'materials.*.material' => ['nullable', 'string', 'max:255'],
            'materials.*.finish' => ['nullable', 'string', 'max:255'],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['nullable', 'string', 'max:255'],
            'material_estimation' => ['nullable', 'array'],
            'material_estimation.*.material' => ['nullable', 'string', 'max:255'],
            'material_estimation.*.qty' => ['nullable', 'string', 'max:100'],
            'cost_material' => ['nullable', 'numeric'],
            'cost_production' => ['nullable', 'numeric'],
            'cost_installation' => ['nullable', 'numeric'],
            'technical_note' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.margin' => ['nullable', 'numeric', 'min:0'],
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
                    'save' => $designRequest->status === 'assigned' ? 'drafting' : $designRequest->status,
                },
                'progress' => match ($request->input('action')) {
                    'submit' => 100,
                    'review' => max((int) $designRequest->progress, 75),
                    'save' => max((int) $designRequest->progress, 25),
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

    protected function ensureAssigned(DesignRequest $designRequest): void
    {
        abort_unless(
            Auth::user()->isAdministrator()
                || (int) $designRequest->production_pic_id === (int) Auth::id(),
            403,
            'Design request ini tidak ditugaskan kepada Anda.'
        );
    }
}

<?php

namespace App\Http\Controllers\Drafter;

use App\Http\Controllers\Controller;
use App\Models\DesignRequest;
use App\Models\ItemMaster;
use App\Models\User;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DesignRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DesignRequest::with('sales', 'productionPic', 'documents')
            ->when(Auth::user()->isDrafter(), fn ($query) => $query->where('production_pic_id', Auth::id()))
            ->orderByRaw('deadline is null, deadline asc')
            ->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($sales = $request->get('sales_id')) {
            $query->where('sales_id', $sales);
        }
        if ($priority = $request->get('priority')) {
            $query->whereIn('priority', $priority === 'urgent' ? ['urgent', 'high'] : ['normal', 'medium', 'low']);
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
        $selected = $request->filled('design_request')
            ? $designRequests->getCollection()->firstWhere('id', (int) $request->get('design_request'))
            : null;
        $selected ??= $designRequests->first();
        $selected?->load('sales', 'documents', 'lead', 'items');
        $salesUsers = User::assignableSales();

        $base = fn () => DesignRequest::query()->when(Auth::user()->isDrafter(), fn ($query) => $query->where('production_pic_id', Auth::id()));
        $stats = [
            'baru' => $base()->whereIn('status', ['assigned', 'draft'])->count(),
            'drafting' => $base()->where('status', 'drafting')->count(), 'review' => $base()->where('status', 'review')->count(),
            'completed' => $base()->where('status', 'completed')->count(),
            'terlambat' => $base()->whereNotIn('status', ['completed', 'rejected'])->whereDate('deadline', '<', today())->count(),
        ];

        return view('drafter.design_requests.index', compact('designRequests', 'stats', 'selected', 'salesUsers'));
    }

    public function show(DesignRequest $designRequest)
    {
        $this->ensureAssigned($designRequest);
        $designRequest->load('items.itemMaster', 'sales', 'documents.uploader', 'lead', 'customer', 'quotations.purchaseOrderRequest');
        $itemMasters = ItemMaster::where('is_active', true)->orderBy('category')->orderBy('name')->get();
        return view('drafter.design_requests.show', compact('designRequest', 'itemMasters'));
    }

    public function updateProgress(Request $request, DesignRequest $designRequest)
    {
        abort_unless(Auth::user()->isProduction() || Auth::user()->isAdministrator(), 403, 'Progress hanya dapat diperbarui oleh Produksi.');
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
        $isCostEditor = Auth::user()->isProduction() || Auth::user()->isAdministrator();
        $isImageEditor = Auth::user()->isDrafter() || Auth::user()->isAdministrator();

        if ($request->input('action') === 'submit' && ! $isCostEditor) {
            throw ValidationException::withMessages([
                'action' => 'Submit final dan penetapan HPP hanya dapat dilakukan oleh Produksi.',
            ]);
        }

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
            'technical_note' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer', 'exists:design_request_items,id'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.item_master_id' => ['nullable', 'exists:item_masters,id'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.variant' => ['nullable', 'string', 'max:255'],
            'items.*.specification' => ['nullable', 'string', 'max:12000'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.margin' => ['nullable', 'numeric', 'min:0'],
            'items.*.is_optional' => ['nullable', 'boolean'],
            'items.*.quotation_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'items.*.remove_image' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($designRequest, $data, $request, $isCostEditor, $isImageEditor) {
            $update = [
                'dimensions' => $data['dimensions'] ?? null,
                'materials' => $data['materials'] ?? null,
                'accessories' => $data['accessories'] ?? null,
                'material_estimation' => $data['material_estimation'] ?? null,
                'technical_note' => $data['technical_note'] ?? null,
                'status' => match ($request->input('action')) {
                    'submit' => 'completed',
                    'review' => 'review',
                    'save' => in_array($designRequest->status, ['draft', 'assigned'], true) ? 'drafting' : $designRequest->status,
                },
                'progress' => match ($request->input('action')) {
                    'submit' => 100,
                    'review' => max((int) $designRequest->progress, 75),
                    'save' => max((int) $designRequest->progress, 25),
                },
                'submitted_at' => $request->input('action') === 'submit' ? now() : $designRequest->submitted_at,
            ];

            if ($isCostEditor) {
                $update += [
                    'cost_material' => $data['cost_material'] ?? 0,
                    'cost_production' => $data['cost_production'] ?? 0,
                    'cost_installation' => $data['cost_installation'] ?? 0,
                    'cost_total' => ($data['cost_material'] ?? 0)
                        + ($data['cost_production'] ?? 0)
                        + ($data['cost_installation'] ?? 0),
                ];
            }

            $designRequest->update($update);
            $this->syncQuotationItems($request, $designRequest, $data['items'] ?? [], $isCostEditor, $isImageEditor);
        });

        Logger::record('submitted', "DR {$designRequest->code} diperbarui oleh ".Auth::user()->roleLabel(), $designRequest);

        $message = $request->input('action') === 'submit'
            ? 'Berhasil submit final ke sales.'
            : 'Feedback design request berhasil disimpan.';

        return redirect()->route('drafter.design-requests.index')->with('success', $message);
    }

    protected function syncQuotationItems(Request $request, DesignRequest $designRequest, array $items, bool $isCostEditor, bool $isImageEditor): void
    {
        $existing = $designRequest->items()->get()->keyBy('id');
        $keptIds = [];

        foreach (array_values($items) as $index => $itemData) {
            if (blank($itemData['name'] ?? null)) {
                continue;
            }

            $item = ! empty($itemData['id']) ? $existing->get((int) $itemData['id']) : null;
            abort_if(! empty($itemData['id']) && ! $item, 422, 'Item Design Request tidak sesuai.');

            $item ??= $designRequest->items()->make();
            $qty = (float) ($itemData['qty'] ?? 1);
            $hpp = $isCostEditor ? (float) ($itemData['unit_price'] ?? 0) : (float) ($item->unit_price ?? 0);

            $item->fill([
                'category' => $itemData['category'] ?? null,
                'item_master_id' => $itemData['item_master_id'] ?? null,
                'name' => $itemData['name'],
                'variant' => $itemData['variant'] ?? null,
                'specification' => $itemData['specification'] ?? null,
                'qty' => $qty,
                'unit' => $itemData['unit'] ?? 'Unit',
                'unit_price' => $hpp,
                'margin' => $item->margin ?? 0,
                'is_optional' => ! empty($itemData['is_optional']),
                'sort_order' => $index,
                'total' => round($qty * $hpp, 2),
            ]);

            $image = $request->file("items.{$index}.quotation_image");
            if ($image && $isImageEditor) {
                if ($item->quotation_image_path) {
                    Storage::disk('public')->delete($item->quotation_image_path);
                }
                $item->quotation_image_path = $image->store("design-request-items/{$designRequest->id}", 'public');
                $item->quotation_image_name = $image->getClientOriginalName();
            } elseif ($isImageEditor && ! empty($itemData['remove_image']) && $item->quotation_image_path) {
                Storage::disk('public')->delete($item->quotation_image_path);
                $item->quotation_image_path = null;
                $item->quotation_image_name = null;
            }

            $item->save();
            $keptIds[] = $item->id;
        }

        if ($keptIds) {
            $designRequest->items()->whereNotIn('id', $keptIds)->delete();
        } elseif ($designRequest->items()->exists()) {
            $designRequest->items()->delete();
        }
    }

    protected function ensureAssigned(DesignRequest $designRequest): void
    {
        abort_unless(
            Auth::user()->isAdministrator()
                || Auth::user()->isProduction()
                || (int) $designRequest->production_pic_id === (int) Auth::id(),
            403,
            'Design request ini tidak ditugaskan kepada Anda.'
        );
    }
}

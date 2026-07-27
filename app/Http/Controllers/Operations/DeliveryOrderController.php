<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Project;
use App\Services\CodeGenerator;
use App\Services\DeliveryOrderPdf;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;

class DeliveryOrderController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_unless(ProjectAccess::canView($request->user(), $project), 403);
        abort_unless($project->workflow?->qc_completed, 422, 'Delivery Order baru dapat dibuat setelah QC selesai.');

        $data = $request->validate([
            'delivery_date' => ['required', 'date'],
            'delivery_address' => ['required', 'string', 'max:2000'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:500'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:50'],
        ]);

        $items = collect($data['items'])->map(fn (array $item) => [
            'name' => trim($item['name']),
            'qty' => round((float) $item['qty'], 2),
            'unit' => trim($item['unit']),
        ])->values()->all();

        $deliveryOrder = $project->deliveryOrder;
        $values = [
            'delivery_date' => $data['delivery_date'],
            'delivery_address' => $data['delivery_address'],
            'recipient_name' => $data['recipient_name'] ?? null,
            'recipient_phone' => $data['recipient_phone'] ?? null,
            'driver_name' => $data['driver_name'] ?? null,
            'vehicle_number' => strtoupper($data['vehicle_number'] ?? ''),
            'items' => $items,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ];

        if ($deliveryOrder) {
            $deliveryOrder->update($values);
            $message = "Delivery Order {$deliveryOrder->code} berhasil diperbarui.";
        } else {
            $deliveryOrder = $project->deliveryOrder()->create($values + [
                'code' => CodeGenerator::next(DeliveryOrder::class, 'DO', 4, true),
                'created_by' => $request->user()->id,
            ]);
            $message = "Delivery Order {$deliveryOrder->code} berhasil dibuat.";
        }

        return back()->with('success', $message)->withFragment('operations');
    }

    public function pdf(Request $request, Project $project, DeliveryOrderPdf $pdf)
    {
        abort_unless(ProjectAccess::canView($request->user(), $project), 403);
        $deliveryOrder = $project->deliveryOrder;
        abort_unless($deliveryOrder, 404, 'Delivery Order belum dibuat.');

        $filename = $deliveryOrder->code.'.pdf';
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($pdf->makeDeliveryOrder($deliveryOrder), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }
}

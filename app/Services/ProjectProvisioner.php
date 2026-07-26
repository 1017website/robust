<?php

namespace App\Services;

use App\Models\Project;
use App\Models\PurchaseOrderRequest;
use App\Models\User;

class ProjectProvisioner
{
    public function fromAccuratePurchaseOrder(PurchaseOrderRequest $requestPo, ?User $creator = null): Project
    {
        $requestPo->loadMissing('quotation.designRequest', 'quotation.customer', 'quotation.items', 'quotation.project');
        $quotation = $requestPo->quotation;

        if ($quotation->project) {
            $quotation->project->workflow()->firstOrCreate();

            return $quotation->project;
        }

        $designRequest = $quotation->designRequest;
        $drafterId = $designRequest?->production_pic_id;
        $startDate = $requestPo->accurate_po_date ?: today();
        $targetDate = $requestPo->expected_delivery_date;
        $requestedCode = trim((string) $requestPo->project_number);
        $code = $requestedCode !== '' && ! Project::where('code', $requestedCode)->exists()
            ? $requestedCode
            : CodeGenerator::next(Project::class, 'PRJ', 4, true);

        $project = Project::create([
            'code' => $code,
            'quotation_id' => $quotation->id,
            'customer_id' => $quotation->customer_id,
            'name' => $quotation->project_name ?: 'Project '.$code,
            'description' => $designRequest?->short_description,
            'category' => 'Produksi',
            'type' => 'Purchase Order',
            'priority' => $quotation->priority ?: 'medium',
            'status' => 'planning',
            'start_date' => $startDate,
            'target_date' => $targetDate,
            'duration_days' => $targetDate ? $startDate->diffInDays($targetDate) : null,
            'work_method' => 'production_order',
            'location' => $requestPo->delivery_address,
            'scope_of_work' => $designRequest?->detail_need ?: $quotation->items->pluck('name')->implode(', '),
            'project_value' => (float) $quotation->subtotal - (float) $quotation->discount_amount,
            'tax_amount' => $quotation->tax_amount,
            'total_value' => $quotation->grand_total,
            'currency' => $quotation->currency ?: 'IDR',
            'payment_scheme' => $requestPo->payment_term,
            'project_manager_id' => $drafterId,
            'internal_team' => $drafterId ? [(string) $drafterId] : [],
            'note' => 'Project otomatis dibuat setelah PO Accurate terbit: '.($requestPo->accurate_po_number ?: $requestPo->code),
            'progress' => 0,
            'created_by' => $creator?->id ?: $requestPo->requested_by,
        ]);
        $project->workflow()->create();

        Logger::record('created', "Project {$project->code} otomatis dibuat dari PO Accurate {$requestPo->accurate_po_number}", $project);

        return $project;
    }
}

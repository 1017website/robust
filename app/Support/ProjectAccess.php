<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProjectAccess
{
    public static function scopeRequestProcesses(Builder $query, User $user): Builder
    {
        if ($user->isDrafter()) {
            $query->where(fn ($scope) => $scope->where('project_manager_id', $user->id)
                ->orWhereJsonContains('internal_team', (string) $user->id)
                ->orWhereJsonContains('internal_team', $user->id));
        } elseif ($user->isProduction()) {
            $query->where(fn ($scope) => $scope
                ->whereHas('documents', fn ($documents) => $documents->where('category', 'fabrication_drawing')->where('is_current', true))
                ->orWhereHas('quotation', fn ($quotations) => $quotations->whereNull('design_request_id')));
        } elseif ($user->role === 'qc_installation') {
            $query->whereHas('workflow', fn ($workflow) => $workflow->where('qc_completed', true)
                ->whereIn('delivery_status', ProjectWorkflow::deliveryArrivedStatuses()));
        } elseif ($user->isQc()) {
            $query->whereHas('workflow', fn ($workflow) => $workflow->where('production_status', 'production_finished'));
        } elseif ($user->isDelivery()) {
            $query->whereHas('workflow', fn ($workflow) => $workflow->where('qc_completed', true));
        }

        return $query;
    }

    public static function canView(User $user, Project $project): bool
    {
        if ($user->isAdminLevel() || in_array($user->role, ['sales_spv', 'administration', 'production', 'qc', 'qc_production', 'qc_installation', 'delivery'], true)) {
            return true;
        }

        $project->loadMissing('quotation');

        if ($user->isSales()) {
            return (int) $project->project_manager_id === (int) $user->id
                || (int) $project->quotation?->sales_id === (int) $user->id;
        }

        if ($user->isDrafter()) {
            $team = collect($project->internal_team ?? [])->map(fn ($id) => (int) $id);

            return (int) $project->project_manager_id === (int) $user->id || $team->contains((int) $user->id);
        }

        return false;
    }
}

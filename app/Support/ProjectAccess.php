<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;

class ProjectAccess
{
    public static function canView(User $user, Project $project): bool
    {
        if (in_array($user->role, ['administrator', 'sales_admin', 'sales_spv', 'administration', 'production', 'qc', 'delivery'], true)) {
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

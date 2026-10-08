<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

class ProjectDeadline
{
    public const WARNING_DAYS = 3;

    public static function apply(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['done', 'cancelled'])
            ->whereDate('target_date', '<=', today()->addDays(self::WARNING_DAYS));
    }

    public static function indicator(Project $project): ?array
    {
        if (! $project->target_date || in_array($project->status, ['done', 'cancelled'], true)) {
            return null;
        }

        $days = (int) today()->diffInDays($project->target_date, false);

        return match (true) {
            $days < 0 => ['label' => 'Terlambat '.abs($days).' hari', 'tone' => 'danger'],
            $days === 0 => ['label' => 'Deadline hari ini', 'tone' => 'danger'],
            $days <= self::WARNING_DAYS => ['label' => 'Deadline '.$days.' hari lagi', 'tone' => 'warning'],
            default => null,
        };
    }
}

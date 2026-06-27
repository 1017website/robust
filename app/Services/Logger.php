<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Logger
{
    public static function record(string $action, string $description, ?Model $subject = null, array $meta = []): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => $subject ? get_class($subject) : null,
            'loggable_id' => $subject?->getKey(),
            'action' => $action,
            'description' => $description,
            'meta' => $meta ?: null,
        ]);
    }
}

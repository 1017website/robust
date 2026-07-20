<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignRevision extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'revision_date' => 'date',
        'status_updated_at' => 'datetime',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function statusUpdater(): BelongsTo { return $this->belongsTo(User::class, 'status_updated_by'); }

    public static function statuses(): array
    {
        return [
            'submitted' => 'Submitted',
            'reviewed' => 'Reviewed',
            'approved' => 'Approved',
        ];
    }

    public function label(): string
    {
        return 'Revision '.$this->revision_number;
    }
}

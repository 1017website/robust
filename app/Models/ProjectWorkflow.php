<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWorkflow extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'production_status' => 'stock',
        'production_report_completed' => false,
        'qc_completed' => false,
        'delivery_out_completed' => false,
        'delivery_returned_completed' => false,
    ];

    protected $casts = [
        'production_report_completed' => 'boolean',
        'production_updated_at' => 'datetime',
        'qc_completed' => 'boolean',
        'qc_updated_at' => 'datetime',
        'delivery_out_completed' => 'boolean',
        'delivery_returned_completed' => 'boolean',
        'delivery_updated_at' => 'datetime',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function productionUpdater(): BelongsTo { return $this->belongsTo(User::class, 'production_updated_by'); }
    public function qcUpdater(): BelongsTo { return $this->belongsTo(User::class, 'qc_updated_by'); }
    public function deliveryUpdater(): BelongsTo { return $this->belongsTo(User::class, 'delivery_updated_by'); }

    public static function productionStatuses(): array
    {
        return [
            'stock' => 'Stock',
            'production' => 'Production',
            'production_finished' => 'Production Finished',
        ];
    }

    public function completionPercent(): int
    {
        $done = collect([
            $this->production_report_completed,
            $this->qc_completed,
            $this->delivery_out_completed,
            $this->delivery_returned_completed,
        ])->filter()->count();

        return $done * 25;
    }
}

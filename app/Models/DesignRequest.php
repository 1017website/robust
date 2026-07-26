<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DesignRequest extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'scope_checklist' => 'array',
        'outputs' => 'array',
        'dimensions' => 'array',
        'materials' => 'array',
        'accessories' => 'array',
        'material_estimation' => 'array',
        'request_date' => 'date',
        'deadline' => 'date',
        'submitted_at' => 'datetime',
        'cost_total' => 'decimal:2',
    ];

    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function sales(): BelongsTo { return $this->belongsTo(User::class, 'sales_id'); }
    public function productionPic(): BelongsTo { return $this->belongsTo(User::class, 'production_pic_id'); }
    /** Nama relasi baru; productionPic dipertahankan untuk kompatibilitas data lama. */
    public function drafter(): BelongsTo { return $this->belongsTo(User::class, 'production_pic_id'); }
    public function items(): HasMany { return $this->hasMany(DesignRequestItem::class); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class); }
    public function documents(): MorphMany { return $this->morphMany(Document::class, 'documentable'); }
    public function revisionRequests(): HasMany { return $this->hasMany(DesignRequestRevisionRequest::class)->orderByDesc('revision_number'); }

    public function hasPrePo(): bool
    {
        return $this->quotations()->whereHas('purchaseOrderRequest')->exists();
    }

    public function hasProductionReadyDocument(?\Illuminate\Support\Carbon $after = null): bool
    {
        return $this->documents()
            ->whereIn('category', ['request_drawing', 'fabrication_drawing', 'supporting_document'])
            ->where('is_current', true)
            ->when($after, fn ($query) => $query->where('created_at', '>=', $after))
            ->whereHas('uploader', fn ($query) => $query->where('role', 'drafter'))
            ->exists();
    }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'assigned' => 'Assigned',
            'drawing_uploaded' => 'Drawing Uploaded',
            'revision_requested' => 'Revision Requested',
            'revision_drawing_uploaded' => 'Revision Drawing Uploaded',
            'drafting' => 'Drafting',
            'costing' => 'Costing',
            'review' => 'Review',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
        ];
    }

    public static function urgencyOptions(): array
    {
        return [
            'normal' => 'Normal',
            'urgent' => 'Urgent',
        ];
    }

    public static function urgencyLabel(?string $priority): string
    {
        return in_array($priority, ['urgent', 'high'], true) ? 'Urgent' : 'Normal';
    }
}

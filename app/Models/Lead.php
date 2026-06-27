<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lead extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'scope_items' => 'array',
        'est_value_min' => 'decimal:2',
        'est_value_max' => 'decimal:2',
        'initial_followup_date' => 'date',
    ];

    public function praLead(): BelongsTo { return $this->belongsTo(PraLead::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function sales(): BelongsTo { return $this->belongsTo(User::class, 'sales_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function designRequests(): HasMany { return $this->hasMany(DesignRequest::class); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class); }
    public function documents(): MorphMany { return $this->morphMany(Document::class, 'documentable'); }
}

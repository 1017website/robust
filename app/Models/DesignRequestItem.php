<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignRequestItem extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'margin' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function designRequest(): BelongsTo { return $this->belongsTo(DesignRequest::class); }
}

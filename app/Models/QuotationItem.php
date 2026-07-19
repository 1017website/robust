<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'qty' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'margin' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function itemMaster(): BelongsTo { return $this->belongsTo(ItemMaster::class); }
}

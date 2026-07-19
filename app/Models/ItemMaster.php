<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemMaster extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'default_cost_price' => 'decimal:2',
        'default_margin' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function quotationItems(): HasMany { return $this->hasMany(QuotationItem::class); }
}

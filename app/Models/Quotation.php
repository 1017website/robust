<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'additional_costs' => 'array',
        'quote_date' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'additional_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function designRequest(): BelongsTo { return $this->belongsTo(DesignRequest::class); }
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function sales(): BelongsTo { return $this->belongsTo(User::class, 'sales_id'); }
    public function items(): HasMany { return $this->hasMany(QuotationItem::class)->orderBy('sort_order'); }
    public function project() { return $this->hasOne(Project::class); }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'sent' => 'Terkirim',
            'negotiation' => 'Negosiasi',
            'won' => 'Won / Closing',
            'lost' => 'Lost',
            'expired' => 'Expired',
        ];
    }
}

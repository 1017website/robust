<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'invoice_date' => 'date', 'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2',
        'installation_amount' => 'decimal:2', 'grand_total' => 'decimal:2', 'paid_total' => 'decimal:2',
    ];

    public function purchaseOrderRequest(): BelongsTo { return $this->belongsTo(PurchaseOrderRequest::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function terms(): HasMany { return $this->hasMany(InvoiceTerm::class)->orderBy('term_number'); }

    public function balance(): float { return max(0, (float) $this->grand_total - (float) $this->paid_total); }

    public static function statuses(): array
    {
        return ['draft' => 'Draft', 'issued' => 'Diterbitkan', 'partial' => 'Dibayar Sebagian', 'paid' => 'Lunas', 'cancelled' => 'Dibatalkan'];
    }
}

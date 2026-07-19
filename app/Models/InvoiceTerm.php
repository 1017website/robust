<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceTerm extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'percentage' => 'decimal:2', 'amount' => 'decimal:2', 'paid_amount' => 'decimal:2',
        'due_date' => 'date', 'issued_date' => 'date', 'paid_date' => 'date',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }

    public static function statuses(): array
    {
        return ['planned' => 'Rencana', 'issued' => 'Ditagihkan', 'partial' => 'Dibayar Sebagian', 'paid' => 'Lunas', 'cancelled' => 'Dibatalkan'];
    }
}

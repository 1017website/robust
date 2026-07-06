<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationApprovalHistory extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function actionLabels(): array
    {
        return [
            'created' => 'Dibuat',
            'updated' => 'Diperbarui',
            'submitted' => 'Diajukan ke SPV',
            'approved' => 'Disetujui SPV',
            'revision' => 'Diminta Revisi',
            'rejected' => 'Ditolak SPV',
            'sent_to_customer' => 'Dikirim ke Customer',
            'customer_accepted' => 'Customer Setuju',
            'customer_rejected' => 'Customer Tidak Setuju',
        ];
    }

    public function actionLabel(): string
    {
        return self::actionLabels()[$this->action] ?? str($this->action)->headline()->toString();
    }
}

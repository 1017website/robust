<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'additional_costs' => 'array',
        'quote_date' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'submitted_for_approval_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'customer_response_at' => 'datetime',
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
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejectedBy(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function items(): HasMany { return $this->hasMany(QuotationItem::class)->orderBy('sort_order'); }
    public function approvalHistories(): HasMany { return $this->hasMany(QuotationApprovalHistory::class)->latest(); }
    public function project(): HasOne { return $this->hasOne(Project::class); }
    public function purchaseOrderRequest(): HasOne { return $this->hasOne(PurchaseOrderRequest::class); }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'waiting_approval' => 'Menunggu Approval SPV',
            'revision' => 'Perlu Revisi',
            'approved' => 'Approved SPV',
            'rejected' => 'Ditolak SPV',
            'sent_to_customer' => 'Dikirim ke Customer',
            'customer_accepted' => 'Customer Setuju',
            'customer_rejected' => 'Customer Tidak Setuju',
            'request_po_created' => 'Request PO Dibuat',
            'expired' => 'Expired',

            // Kompatibilitas data lama
            'sent' => 'Terkirim',
            'negotiation' => 'Negosiasi',
            'won' => 'Won / Closing',
            'lost' => 'Lost',
        ];
    }

    public static function activeSalesStatuses(): array
    {
        return [
            'draft',
            'waiting_approval',
            'revision',
            'approved',
            'sent_to_customer',

            // Kompatibilitas data lama.
            'sent',
            'negotiation',
        ];
    }

    public static function wonStatuses(): array
    {
        return ['customer_accepted', 'request_po_created', 'won'];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'revision', 'rejected'], true);
    }

    public function canBeSubmittedForApproval(): bool
    {
        return $this->canBeEdited();
    }

    public function canBeReviewedBySpv(): bool
    {
        return in_array($this->status, ['waiting_approval', 'revision'], true);
    }

    public function canDownloadPdf(): bool
    {
        return in_array($this->status, ['approved', 'sent_to_customer', 'customer_accepted', 'request_po_created', 'won'], true);
    }

    public function canCreatePurchaseOrderRequest(): bool
    {
        return in_array($this->status, ['approved', 'sent_to_customer', 'customer_accepted'], true)
            && ! $this->purchaseOrderRequest()->exists();
    }

    public function estimatedCostTotal(): float
    {
        $itemCost = $this->items->sum(
            fn (QuotationItem $item) => (float) $item->qty * (float) $item->cost_price
        );

        return $itemCost > 0
            ? round($itemCost, 2)
            : (float) ($this->designRequest?->cost_total ?? 0);
    }

    public function estimatedGrossProfit(): float
    {
        return max(0, (float) $this->grand_total - $this->estimatedCostTotal());
    }

    public function estimatedGrossMarginPercent(): float
    {
        $grandTotal = (float) $this->grand_total;

        return $grandTotal > 0 ? round($this->estimatedGrossProfit() / $grandTotal * 100, 2) : 0.0;
    }
}

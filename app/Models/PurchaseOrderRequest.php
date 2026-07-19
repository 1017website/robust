<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrderRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'request_date' => 'date',
        'accurate_po_date' => 'date',
        'processed_at' => 'datetime',
        'expected_delivery_date' => 'date',
        'checklist' => 'array',
        'checklist_completed_at' => 'datetime',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function canCreateInvoice(): bool
    {
        return $this->status !== 'cancelled' && ! $this->invoice()->exists();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    public static function checklistItems(): array
    {
        return [
            'quotation_approved' => 'Penawaran final sudah approved SPV',
            'customer_po' => 'PO customer / bukti order sudah dilampirkan',
            'customer_data' => 'Data customer sudah lengkap',
            'delivery_address' => 'Alamat pengiriman / lokasi project sudah jelas',
            'pic_contact' => 'PIC penerima barang / project sudah jelas',
            'payment_term' => 'Termin pembayaran sudah jelas',
            'accurate_ready' => 'Data siap diinput ke Accurate',
        ];
    }

    public function checklistProgress(): array
    {
        $values = $this->checklist ?? [];
        $total = count(self::checklistItems());
        $done = collect(self::checklistItems())->keys()->filter(fn ($key) => ! empty($values[$key]))->count();

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? round($done / $total * 100) : 0,
            'complete' => $total > 0 && $done === $total,
        ];
    }

    public function isChecklistComplete(): bool
    {
        return $this->checklistProgress()['complete'];
    }

    public static function statuses(): array
    {
        return [
            'submitted' => 'Diajukan ke Accurate',
            'processing_accurate' => 'Diproses di Accurate',
            'po_created' => 'PO Accurate Dibuat',
            'production' => 'Produksi',
            'installation' => 'Installasi',
            'invoicing' => 'Invoicing',
            'paid' => 'Lunas',
            'cancelled' => 'Dibatalkan',
        ];
    }
}

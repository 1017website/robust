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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canCreateInvoice(): bool
    {
        if (in_array($this->status, ['draft', 'cancelled'], true) || $this->invoice()->exists()) {
            return false;
        }

        $project = $this->quotation?->project;

        // Data lama yang belum memiliki Project tetap dapat ditagihkan.
        return ! $project || $project->workflow?->delivery_status === 'completed';
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    /** Item bawaan yang muncul saat Request PO baru dibuat. */
    public static function defaultChecklistItems(): array
    {
        return [
            'quotation_approved' => 'Penawaran final sudah siap dikirim',
            'customer_po' => 'PO customer / bukti order sudah dilampirkan',
            'customer_data' => 'Data customer sudah lengkap',
            'delivery_address' => 'Alamat pengiriman / lokasi project sudah jelas',
            'pic_contact' => 'PIC penerima barang / project sudah jelas',
            'payment_term' => 'Termin pembayaran sudah jelas',
            'accurate_ready' => 'Data siap diinput ke Accurate',
        ];
    }

    /**
     * Checklist milik Request PO ini.
     *
     * Item disimpan per Request PO sehingga setiap akun dapat menghapus item yang
     * tidak diperlukan atau menambah item sendiri tanpa mengubah Request PO lain.
     * Data lama yang masih berbentuk {key: bool} tetap terbaca.
     *
     * @return array<int, array{key: string, label: string, checked: bool}>
     */
    public function checklistItems(): array
    {
        $stored = $this->checklist;

        if ($stored === null) {
            return collect(self::defaultChecklistItems())
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'checked' => false])
                ->values()
                ->all();
        }

        $defaults = self::defaultChecklistItems();
        $items = [];

        foreach ($stored as $key => $value) {
            if (is_array($value)) {
                // Bentuk baru: daftar item lengkap dengan label sendiri.
                $label = trim((string) ($value['label'] ?? ''));
                $itemKey = (string) ($value['key'] ?? $key);
                if ($label === '') {
                    $label = $defaults[$itemKey] ?? \Illuminate\Support\Str::headline($itemKey);
                }
                $items[] = ['key' => $itemKey, 'label' => $label, 'checked' => (bool) ($value['checked'] ?? false)];

                continue;
            }

            // Bentuk lama: {key: bool}.
            $items[] = [
                'key' => (string) $key,
                'label' => $defaults[$key] ?? \Illuminate\Support\Str::headline((string) $key),
                'checked' => (bool) $value,
            ];
        }

        return $items;
    }

    public function checklistProgress(): array
    {
        $items = $this->checklistItems();
        $total = count($items);
        $done = collect($items)->filter(fn ($item) => $item['checked'])->count();

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
            'complete' => $total > 0 && $done === $total,
        ];
    }

    public function isChecklistComplete(): bool
    {
        return $this->checklistProgress()['complete'];
    }

    /** Seluruh status termasuk draf yang belum diajukan. */
    public static function statuses(): array
    {
        return ['draft' => 'Draft'] + self::processStatuses();
    }

    /** Status proses setelah Request PO diajukan (dipakai pada form update Accurate). */
    public static function processStatuses(): array
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

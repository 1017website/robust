<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrderRequest extends Model
{
    protected $guarded = ['id'];

    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, User $user): \Illuminate\Database\Eloquent\Builder
    {
        return $query->when(($user->isSales() && ! $user->isAdminLevel()), fn ($query) => $query->where(fn ($scope) => $scope
            ->where('requested_by', $user->id)
            ->orWhereHas('quotation', fn ($quotation) => $quotation->where('sales_id', $user->id))));
    }

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

    /**
     * Nomor Proyek. Sejak kolom isian digabung, nomor record inilah Nomor Proyek.
     * Kolom `project_number` dipertahankan agar record lama tetap memakai nomor
     * yang sudah terlanjur dipakai di dokumen dan invoice.
     */
    public function projectNumber(): string
    {
        return (string) ($this->project_number ?: $this->code);
    }

    public function canCreateInvoice(): bool
    {
        if (in_array($this->status, ['draft', 'cancelled'], true) || $this->invoice()->exists()) {
            return false;
        }

        // Invoice hanya boleh terbit setelah barang benar-benar sampai: Project sudah
        // terbentuk (PO Accurate dibuat) dan Delivery menandai pengiriman selesai.
        return $this->quotation?->project?->workflow?->delivery_status === 'completed';
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    /** Label untuk membaca checklist lama yang disimpan sebagai key dan boolean. */
    protected static function legacyChecklistLabels(): array
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
     * Checklist milik Project ini.
     *
     * Item disimpan per Project sehingga setiap akun dapat menghapus item yang
     * tidak diperlukan atau menambah item sendiri tanpa mengubah Project lain.
     * Data lama yang masih berbentuk {key: bool} tetap terbaca.
     *
     * @return array<int, array{key: string, label: string, checked: bool}>
     */
    public function checklistItems(): array
    {
        $stored = $this->checklist;

        if ($stored === null) {
            return [];
        }

        $defaults = self::legacyChecklistLabels();
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

    /** Seluruh status termasuk draf yang belum diajukan dan status lama. */
    public static function statuses(): array
    {
        return ['draft' => 'Draft'] + self::processStatuses() + self::legacyStatuses();
    }

    /**
     * Status proses setelah Project diajukan (dipakai pada form update Accurate).
     *
     * Hanya memuat fase administratif: PO customer masuk sampai pelunasan. Fase
     * produksi, installasi, dan pengiriman dilacak di Request Process melalui
     * ProjectWorkflow agar tidak ada dua tempat mencatat fase yang sama.
     */
    public static function processStatuses(): array
    {
        return [
            'submitted' => 'Diajukan ke Accurate',
            'processing_accurate' => 'Diproses di Accurate',
            'po_created' => 'PO Accurate Dibuat',
            'paid' => 'Lunas',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /**
     * Status lama yang kini menjadi tanggung jawab Request Process. Tidak dapat
     * dipilih lagi, tetapi tetap punya label agar data historis terbaca.
     */
    public static function legacyStatuses(): array
    {
        return [
            'production' => 'Produksi (status lama)',
            'installation' => 'Installasi (status lama)',
            'invoicing' => 'Invoicing (status lama)',
        ];
    }

    /** Opsi status yang boleh dipilih, termasuk status lama yang sedang dipakai record ini. */
    public function selectableStatuses(): array
    {
        $options = self::processStatuses();

        if (array_key_exists($this->status, self::legacyStatuses())) {
            $options = [$this->status => self::legacyStatuses()[$this->status]] + $options;
        }

        return $options;
    }
}

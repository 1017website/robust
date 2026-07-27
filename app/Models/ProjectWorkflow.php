<?php

namespace App\Models;

use App\Support\StructuredSpecification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWorkflow extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'production_status' => 'stock',
        'production_report_completed' => false,
        'qc_completed' => false,
        'delivery_status' => 'scheduling',
        'delivery_out_completed' => false,
        'delivery_returned_completed' => false,
    ];

    protected $casts = [
        'production_report_completed' => 'boolean',
        'production_updated_at' => 'datetime',
        'qc_completed' => 'boolean',
        'qc_checklist' => 'array',
        'qc_updated_at' => 'datetime',
        'payment_confirmation_completed' => 'boolean',
        'withholding_tax_receipt_completed' => 'boolean',
        'administration_updated_at' => 'datetime',
        'delivery_scheduled_at' => 'datetime',
        'customer_received_at' => 'datetime',
        'delivery_out_completed' => 'boolean',
        'delivery_returned_completed' => 'boolean',
        'delivery_updated_at' => 'datetime',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function productionUpdater(): BelongsTo { return $this->belongsTo(User::class, 'production_updated_by'); }
    public function qcUpdater(): BelongsTo { return $this->belongsTo(User::class, 'qc_updated_by'); }
    public function administrationUpdater(): BelongsTo { return $this->belongsTo(User::class, 'administration_updated_by'); }
    public function deliveryUpdater(): BelongsTo { return $this->belongsTo(User::class, 'delivery_updated_by'); }

    public static function productionStatuses(): array
    {
        return [
            'stock' => 'Menunggu Produksi',
            'production' => 'Sedang Diproduksi',
            'production_finished' => 'Produksi Selesai',
        ];
    }

    public static function deliveryStatuses(): array
    {
        return [
            'scheduling' => 'Atur Jadwal',
            'scheduled' => 'Terjadwal',
            'in_transit' => 'Dalam Pengiriman',
            'delivered' => 'Terkirim',
            'customer_received' => 'Diterima Customer',
            'completed' => 'Selesai',
        ];
    }

    public static function qcChecklistDefinition(Project $project, bool $includePrices = true): array
    {
        $project->loadMissing('quotation.items');

        return $project->quotation?->items
            ->values()
            ->map(function (QuotationItem $item) use ($includePrices): array {
                $checks = [[
                    'key' => "item_{$item->id}_quantity",
                    'label' => 'Jumlah: '.rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.').' '.($item->unit ?: 'Unit'),
                ]];

                foreach (StructuredSpecification::flatten($item->specification) as $index => $specification) {
                    if (($specification['type'] ?? null) === 'section') {
                        continue;
                    }

                    $label = trim((string) ($specification['label'] ?? 'Spesifikasi'));
                    if (($specification['type'] ?? null) === 'breakdown') {
                        $value = trim(implode(' ', array_filter([
                            $specification['qty'] ?? null,
                            $specification['unit'] ?? null,
                            $includePrices && isset($specification['unit_price']) ? '@ Rp '.number_format((float) $specification['unit_price'], 0, ',', '.') : null,
                        ])));
                    } else {
                        if (($specification['type'] ?? null) === 'subdetail') {
                            $parent = trim((string) ($specification['parent_label'] ?? ''));
                            $label = trim(implode(' - ', array_filter([$parent, $label])));
                        }
                        $value = trim((string) ($specification['value'] ?? ''));
                    }

                    $checks[] = [
                        'key' => "item_{$item->id}_spec_{$index}",
                        'label' => $label.($value !== '' ? ': '.$value : ''),
                    ];
                }

                $checks[] = ['key' => "item_{$item->id}_visual", 'label' => 'Kondisi fisik, warna, dan finishing sesuai'];
                $checks[] = ['key' => "item_{$item->id}_function", 'label' => 'Fungsi dan kelengkapan item telah diuji'];

                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'variant' => $item->variant,
                    'checks' => $checks,
                ];
            })
            ->all() ?? [];
    }

    public function qcChecklistComplete(Project $project): bool
    {
        $values = $this->qc_checklist ?? [];
        $keys = collect(self::qcChecklistDefinition($project))->flatMap(fn (array $item) => collect($item['checks'])->pluck('key'));

        return $keys->isNotEmpty() && $keys->every(fn (string $key) => ! empty($values[$key]));
    }

    public function completionPercent(): int
    {
        $done = collect([
            $this->production_status === 'production_finished',
            $this->qc_completed,
            in_array($this->delivery_status, ['delivered', 'customer_received', 'completed'], true),
            $this->delivery_status === 'completed',
        ])->filter()->count();

        return $done * 25;
    }
}

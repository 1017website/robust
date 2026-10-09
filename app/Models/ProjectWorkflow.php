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
        'production_progress' => 0,
        'production_report_completed' => false,
        'qc_completed' => false,
        'delivery_status' => 'scheduling',
        'delivery_out_completed' => false,
        'delivery_returned_completed' => false,
    ];

    protected $casts = [
        'production_target_date' => 'date',
        'qc_target_date' => 'date',
        'qc_installation_target_date' => 'date',
        'production_item_progress' => 'array',
        'qc_item_progress' => 'array',
        'qc_installation_item_progress' => 'array',
        'production_report_completed' => 'boolean',
        'production_progress' => 'integer',
        'production_updated_at' => 'datetime',
        'qc_completed' => 'boolean',
        'qc_progress' => 'integer',
        'qc_installation_completed' => 'boolean',
        'qc_installation_progress' => 'integer',
        'qc_installation_checklist' => 'array',
        'qc_installation_updated_at' => 'datetime',
        'qc_checklist' => 'array',
        'qc_updated_at' => 'datetime',
        'payment_confirmation_completed' => 'boolean',
        'withholding_tax_receipt_completed' => 'boolean',
        'administration_updated_at' => 'datetime',
        'delivery_scheduled_at' => 'datetime',
        'customer_received_at' => 'datetime',
        'delivery_out_completed' => 'boolean',
        'delivery_returned_completed' => 'boolean',
        'delivery_items' => 'array',
        'delivery_updated_at' => 'datetime',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function productionUpdater(): BelongsTo { return $this->belongsTo(User::class, 'production_updated_by'); }
    public function qcUpdater(): BelongsTo { return $this->belongsTo(User::class, 'qc_updated_by'); }
    public function qcInstallationUpdater(): BelongsTo { return $this->belongsTo(User::class, 'qc_installation_updated_by'); }
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

    public static function qcChecklistDefinition(Project $project, bool $includePrices = true, bool $installation = false): array
    {
        $project->loadMissing('quotation.items');

        return $project->quotation?->items
            ->where('is_optional', false)
            ->values()
            ->map(function (QuotationItem $item) use ($includePrices, $installation): array {
                $checks = [[
                    'key' => "item_{$item->id}_quantity",
                    'label' => 'Jumlah: '.rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.').' '.($item->unit ?: 'Unit'),
                ]];

                foreach (filled($item->specification) ? StructuredSpecification::flatten($item->specification) : [] as $index => $specification) {
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
                if ($installation) {
                    $checks[] = ['key' => "item_{$item->id}_installation_position", 'label' => 'Posisi dan ukuran pemasangan sesuai lokasi yang disepakati'];
                    $checks[] = ['key' => "item_{$item->id}_installation_fixing", 'label' => 'Sambungan, pengikat, dan kestabilan pemasangan telah diperiksa'];
                }

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
        $production = (int) round(min(100, max(0, (int) $this->production_progress)) * .25);
        $qc = (int) round($this->qcProgress() * .15 + $this->qcProgress(true) * .10);
        $delivery = in_array($this->delivery_status, ['delivered', 'customer_received', 'completed'], true) ? 25 : 0;
        $completed = $this->delivery_status === 'completed' ? 25 : 0;

        return min(100, $production + $qc + $delivery + $completed);
    }

    public static function deliveryArrivedStatuses(): array
    {
        return ['delivered', 'customer_received', 'completed'];
    }

    public function installationQcReady(): bool
    {
        return $this->qc_completed && in_array($this->delivery_status, self::deliveryArrivedStatuses(), true);
    }

    public function qcResult(bool $installation = false): ?string
    {
        $prefix = $installation ? 'qc_installation' : 'qc';

        return $this->{"{$prefix}_completed"} ? 'passed' : $this->{"{$prefix}_result"};
    }

    public function qcStatusLabel(bool $installation = false): string
    {
        return match ($this->qcResult($installation)) {
            'passed' => 'Selesai dan lolos QC',
            'failed' => 'Belum lolos / perlu perbaikan',
            'in_progress' => 'Masih diperiksa',
            default => $this->qcProgress($installation) > 0 ? 'Masih diperiksa' : 'Belum dimulai',
        };
    }

    public static function qcChecklistPercent(array $definition, array $values): int
    {
        $keys = collect($definition)->flatMap(fn (array $item) => collect($item['checks'])->pluck('key'));

        return $keys->isEmpty() ? 0 : (int) round($keys->filter(fn (string $key) => ! empty($values[$key]))->count() / $keys->count() * 100);
    }

    public function qcProgress(bool $installation = false): int
    {
        $prefix = $installation ? 'qc_installation' : 'qc';

        $project = $this->project;

        return $project ? self::qcChecklistPercent(
            self::qcChecklistDefinition($project, false, $installation),
            $this->{"{$prefix}_checklist"} ?? [],
        ) : 0;
    }
}

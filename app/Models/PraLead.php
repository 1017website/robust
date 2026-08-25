<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PraLead extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'est_value_min' => 'decimal:2',
        'est_value_max' => 'decimal:2',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function assignedSales(): BelongsTo { return $this->belongsTo(User::class, 'assigned_sales_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'assigned' => 'Ditugaskan',
            'waiting_acceptance' => 'Menunggu Konfirmasi Sales',
            'accepted' => 'Diterima Sales',
            'rejected' => 'Ditolak Sales',
        ];
    }

    public static function sources(): array
    {
        return [
            'distributor' => 'Distributor',
            'supplier' => 'Supplier',
            'loops_lab_nusantara' => 'Loops LabNusantara',
            'robust_multilab_solusindo' => 'Robust Multilab Solusindo',
            'robust_indonesia_sinar_lab' => 'Robust Indonesia - Sinar Lab',
            'mec' => 'MEC',
        ];
    }
}

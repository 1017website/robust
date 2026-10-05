<?php

namespace App\Models;

use App\Support\Format;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'probability' => 'integer',
        'partner_since' => 'date',
    ];

    public function sales(): BelongsTo { return $this->belongsTo(User::class, 'sales_id'); }
    public function pics(): HasMany { return $this->hasMany(CustomerPic::class); }
    public function primaryPic() { return $this->hasOne(CustomerPic::class)->where('is_primary', true); }
    public function leads(): HasMany { return $this->hasMany(Lead::class); }
    public function projects(): HasMany { return $this->hasMany(Project::class); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class); }
    public function activities(): HasMany { return $this->hasMany(Activity::class); }
    public function documents(): MorphMany { return $this->morphMany(Document::class, 'documentable'); }
    public function purchaseOrderRequests(): HasMany { return $this->hasMany(PurchaseOrderRequest::class); }

    public static function stages(): array
    {
        return [
            'identify' => 'Identify',
            'approaching' => 'Approaching',
            'follow_up' => 'Follow Up',
            'won_closing' => 'Won / Closing',
            'lost' => 'Lost',
            'maintaining' => 'Maintaining',
        ];
    }

    public static function categories(): array
    {
        return [
            'Pendidikan',
            'Universitas',
            'Sekolah',
            'Rumah Sakit',
            'Laboratorium Swasta',
            'Industri',
            'Farmasi',
            'Kesehatan',
            'Pemerintah',
            'BUMN',
            'BUMD',
            'Distributor',
            'Kontraktor',
            'Lainnya',
        ];
    }

    /**
     * Nilai pada kartu pipeline. Tahap awal (Identify, Approaching, Follow Up) belum
     * punya angka pasti sehingga memakai estimasi budget lead; tahap akhir memakai
     * nilai penawaran. Won / Maintaining hanya menghitung penawaran yang disetujui,
     * Lost menghitung seluruh penawaran yang sempat diajukan (bukan draf).
     */
    public function pipelineValueLabel(): string
    {
        if (in_array($this->pipeline_stage, ['identify', 'approaching', 'follow_up'], true)) {
            $min = (float) $this->leads->sum('est_value_min');
            $max = (float) $this->leads->sum('est_value_max');
            [$low, $high] = [min($min, $max), max($min, $max)];

            if ($high <= 0) {
                return 'Estimasi belum diisi';
            }

            return $low > 0 && $low < $high
                ? 'Est. '.Format::rupiahShort($low).' – '.Format::rupiahShort($high)
                : 'Est. '.Format::rupiahShort($high);
        }

        $quotations = $this->pipeline_stage === 'lost'
            ? $this->quotations->where('status', '!=', 'draft')
            : $this->quotations->whereIn('status', ['customer_accepted', 'request_po_created', 'won']);

        return Format::rupiahShort($quotations->sum('grand_total'));
    }
}

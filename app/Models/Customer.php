<?php

namespace App\Models;

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
            'Distributor',
            'Kontraktor',
            'Lainnya',
        ];
    }
}

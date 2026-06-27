<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'internal_team' => 'array',
        'start_date' => 'date',
        'target_date' => 'date',
        'project_value' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function projectManager(): BelongsTo { return $this->belongsTo(User::class, 'project_manager_id'); }
    public function terms(): HasMany { return $this->hasMany(ProjectTerm::class); }
    public function activities(): HasMany { return $this->hasMany(Activity::class); }
    public function documents(): MorphMany { return $this->morphMany(Document::class, 'documentable'); }

    public static function statuses(): array
    {
        return [
            'planning' => 'Planning',
            'ongoing' => 'Berjalan',
            'finishing' => 'Finishing',
            'done' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];
    }
}

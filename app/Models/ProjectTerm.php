<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTerm extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['due_date' => 'date', 'amount' => 'decimal:2', 'percentage' => 'decimal:2'];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPic extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_primary' => 'boolean'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}

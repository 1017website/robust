<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'activity_date' => 'date',
        'next_followup_date' => 'date',
        'duration_minutes' => 'integer',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function sales(): BelongsTo { return $this->belongsTo(User::class, 'sales_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public static function types(): array
    {
        return [
            'meeting' => 'Meeting',
            'call' => 'Call',
            'survey_lokasi' => 'Survey Lokasi',
            'presentasi' => 'Presentasi',
            'follow_up' => 'Follow Up',
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'penawaran' => 'Penawaran',
        ];
    }

    public static function statuses(): array
    {
        return [
            'scheduled' => 'Scheduled',
            'in_progress' => 'In Progress',
            'pending' => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }
}

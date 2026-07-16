<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $casts = ['tags' => 'array'];

    public function documentable(): MorphTo { return $this->morphTo(); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdminLevel() || $user->isSalesSpv()) {
            return $query;
        }

        if ($user->isDrafter()) {
            return $query->where(function (Builder $documentQuery) use ($user) {
                $documentQuery->where('uploaded_by', $user->id)
                    ->orWhere(function (Builder $designQuery) use ($user) {
                        $designQuery->where('documentable_type', DesignRequest::class)
                            ->whereIn('documentable_id', DesignRequest::query()
                                ->select('id')
                                ->where('production_pic_id', $user->id));
                    });
            });
        }

        if ($user->isSales()) {
            return $query->where(function (Builder $documentQuery) use ($user) {
                $documentQuery->where('uploaded_by', $user->id)
                    ->orWhere(fn (Builder $q) => $q->where('documentable_type', Customer::class)
                        ->whereIn('documentable_id', Customer::query()->select('id')->where('sales_id', $user->id)))
                    ->orWhere(fn (Builder $q) => $q->where('documentable_type', Lead::class)
                        ->whereIn('documentable_id', Lead::query()->select('id')->where('sales_id', $user->id)))
                    ->orWhere(fn (Builder $q) => $q->where('documentable_type', DesignRequest::class)
                        ->whereIn('documentable_id', DesignRequest::query()->select('id')->where('sales_id', $user->id)))
                    ->orWhere(fn (Builder $q) => $q->where('documentable_type', Project::class)
                        ->whereIn('documentable_id', Project::query()->select('id')->where(function (Builder $projectQuery) use ($user) {
                            $projectQuery->where('project_manager_id', $user->id)
                                ->orWhereHas('quotation', fn (Builder $quotationQuery) => $quotationQuery->where('sales_id', $user->id));
                        })));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) return '-';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / (1024 ** $i), 2).' '.$units[$i];
    }
}

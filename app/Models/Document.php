<?php

namespace App\Models;

use App\Models\Concerns\HasDeploymentSafeSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasDeploymentSafeSoftDeletes;

    protected $guarded = ['id'];
    protected $casts = ['tags' => 'array', 'is_current' => 'boolean', 'revision_number' => 'integer'];

    public function documentable(): MorphTo { return $this->morphTo(); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function parent(): BelongsTo { return $this->belongsTo(Document::class, 'parent_document_id'); }
    public function revisions(): HasMany { return $this->hasMany(Document::class, 'parent_document_id')->orderByDesc('revision_number'); }

    public function revisionLabel(): string
    {
        if (! $this->parent_document_id) {
            return 'Dokumen awal';
        }

        // Urutan penyimpanan dimulai dari 1 untuk dokumen awal. Nomor revisi
        // yang dilihat user karena itu satu tingkat lebih kecil.
        return 'Rev '.max(1, (int) $this->revision_number - 1);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdminLevel() || $user->isSalesSpv()) {
            return $query;
        }

        if ($user->isProduction()) {
            return $query->where('documentable_type', Project::class);
        }

        if ($user->isDrafter()) {
            return $query->where(function (Builder $documentQuery) use ($user) {
                $documentQuery->where('uploaded_by', $user->id)
                    ->orWhere(function (Builder $designQuery) use ($user) {
                        $designQuery->where('documentable_type', DesignRequest::class)
                            ->whereIn('documentable_id', DesignRequest::query()->select('id')
                                ->where('production_pic_id', $user->id));
                    })
                    ->orWhere(fn (Builder $projectQuery) => $projectQuery
                        ->where('documentable_type', Project::class)
                        ->whereIn('documentable_id', Project::query()->select('id')->where(function (Builder $projects) use ($user) {
                            $projects->where('project_manager_id', $user->id)
                                ->orWhereJsonContains('internal_team', (string) $user->id)
                                ->orWhereJsonContains('internal_team', $user->id);
                        })));
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

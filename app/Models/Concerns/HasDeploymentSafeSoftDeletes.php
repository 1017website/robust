<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Throwable;

trait HasDeploymentSafeSoftDeletes
{
    use SoftDeletes;

    /**
     * Allow the application to boot while a newly deployed soft-delete
     * migration has not been executed yet.
     */
    public static function bootSoftDeletes(): void
    {
        $model = new static;

        try {
            $hasDeletedAt = $model->getConnection()
                ->getSchemaBuilder()
                ->hasColumn($model->getTable(), $model->getDeletedAtColumn());
        } catch (Throwable) {
            $hasDeletedAt = false;
        }

        if ($hasDeletedAt) {
            static::addGlobalScope(new SoftDeletingScope);
        }
    }
}

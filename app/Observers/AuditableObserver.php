<?php

namespace App\Observers;

use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditableObserver
{
    public function created(Model $model): void
    {
        AuditLogger::record(
            $model,
            'created',
            [],
            AuditLogger::sanitizeAttributes($model->getAttributes())
        );
    }

    public function updated(Model $model): void
    {
        [$old, $new] = AuditLogger::partitionUpdatedChanges($model);
        if ($new === []) {
            return;
        }
        AuditLogger::record($model, 'updated', $old, $new);
    }

    public function deleted(Model $model): void
    {
        AuditLogger::record(
            $model,
            'deleted',
            AuditLogger::sanitizeAttributes($model->getAttributes()),
            []
        );
    }

    public function restored(Model $model): void
    {
        AuditLogger::record(
            $model,
            'restored',
            [],
            AuditLogger::sanitizeAttributes($model->getAttributes())
        );
    }
}

<?php

namespace App\Models\Concerns;

use App\Core\Audit\AuditService;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditService::class)->created($model);
        });

        static::updated(function (Model $model): void {
            $oldValues = [];

            foreach (array_keys($model->getChanges()) as $attribute) {
                if ($attribute === 'updated_at') {
                    continue;
                }

                $oldValues[$attribute] = $model->getOriginal($attribute);
            }

            if ($oldValues !== []) {
                app(AuditService::class)->updated($model, $oldValues);
            }
        });

        static::deleted(function (Model $model): void {
            app(AuditService::class)->deleted($model);
        });
    }
}

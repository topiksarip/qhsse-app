<?php

namespace App\Models\Concerns;

use App\Core\Audit\AuditService;
use App\Models\Contracts\ProvidesAuditContext;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditService::class)->created(
                $model,
                moduleName: self::auditModuleName($model),
                referenceId: self::auditReferenceId($model),
            );
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
                app(AuditService::class)->updated(
                    $model,
                    $oldValues,
                    moduleName: self::auditModuleName($model),
                    referenceId: self::auditReferenceId($model),
                );
            }
        });

        static::deleted(function (Model $model): void {
            app(AuditService::class)->deleted(
                $model,
                moduleName: self::auditModuleName($model),
                referenceId: self::auditReferenceId($model),
            );
        });
    }

    private static function auditModuleName(Model $model): ?string
    {
        $context = $model instanceof ProvidesAuditContext ? $model->auditContext() : [];

        return $context['module_name'] ?? null;
    }

    private static function auditReferenceId(Model $model): ?int
    {
        $context = $model instanceof ProvidesAuditContext ? $model->auditContext() : [];

        return isset($context['reference_id']) ? (int) $context['reference_id'] : null;
    }
}

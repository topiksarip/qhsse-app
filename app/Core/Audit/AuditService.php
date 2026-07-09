<?php

namespace App\Core\Audit;

use App\Models\Core\Audit\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;

class AuditService
{
    public function log(
        string $event,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?User $actor = null,
        ?string $moduleName = null,
        ?int $referenceId = null,
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        $actor ??= Auth::user();
        $request ??= request();

        return AuditLog::create([
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'module_name' => $moduleName,
            'reference_id' => $referenceId,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'ip_address' => $request?->ip() ?? RequestFacade::ip(),
            'user_agent' => $request?->userAgent(),
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'metadata' => $metadata,
        ]);
    }

    public function created(Model $model, ?User $actor = null, ?string $moduleName = null, ?int $referenceId = null): AuditLog
    {
        return $this->log('created', $model, [], $model->getAttributes(), $actor, $moduleName, $referenceId);
    }

    public function updated(Model $model, array $oldValues, ?User $actor = null, ?string $moduleName = null, ?int $referenceId = null): AuditLog
    {
        return $this->log('updated', $model, $oldValues, $model->getChanges(), $actor, $moduleName, $referenceId);
    }

    public function deleted(Model $model, ?User $actor = null, ?string $moduleName = null, ?int $referenceId = null): AuditLog
    {
        return $this->log('deleted', $model, $model->getOriginal(), [], $actor, $moduleName, $referenceId);
    }

    public function workflow(string $event, string $moduleName, int $referenceId, array $oldValues, array $newValues, ?User $actor = null, array $metadata = []): AuditLog
    {
        return $this->log($event, null, $oldValues, $newValues, $actor, $moduleName, $referenceId, $metadata);
    }

    private function sanitize(array $values): array
    {
        $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

        foreach ($hidden as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = '[redacted]';
            }
        }

        return $values;
    }
}

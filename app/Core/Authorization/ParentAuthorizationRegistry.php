<?php

namespace App\Core\Authorization;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ParentAuthorizationRegistry
{
    /**
     * Registry of allowed modules with their model class and authorization method.
     * Modules NOT in this registry are blocked from generic endpoints (fail-closed).
     * Sensitive modules like asset/document have dedicated protected endpoints.
     */
    private const REGISTRY = [
        'incident' => [
            'model' => \App\Models\Modules\Incident\IncidentReport::class,
            'policy' => 'view',
        ],
        'capa' => [
            'model' => \App\Models\Modules\Capa\CapaAction::class,
            'policy' => 'view',
        ],
    ];

    /**
     * Check if user can access the parent resource.
     * Fails closed: returns false for unknown modules or failed authorization.
     */
    public function canAccessParent(string $moduleName, int $referenceId, User $user): bool
    {
        if (! isset(self::REGISTRY[$moduleName])) {
            return false;
        }

        $config = self::REGISTRY[$moduleName];
        $modelClass = $config['model'];
        $policyMethod = $config['policy'];

        /** @var Model|null $parent */
        $parent = $modelClass::find($referenceId);

        if (! $parent) {
            return false;
        }

        return $user->can($policyMethod, $parent);
    }

    /**
     * Get the parent model instance if authorized.
     */
    public function getAuthorizedParent(string $moduleName, int $referenceId, User $user): ?Model
    {
        if (! $this->canAccessParent($moduleName, $referenceId, $user)) {
            return null;
        }

        $config = self::REGISTRY[$moduleName];
        $modelClass = $config['model'];

        return $modelClass::find($referenceId);
    }

    /**
     * Check if module is registered.
     */
    public function isModuleRegistered(string $moduleName): bool
    {
        return isset(self::REGISTRY[$moduleName]);
    }
}

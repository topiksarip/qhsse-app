<?php

declare(strict_types=1);

namespace App\Policies\Modules\EmergencyPreparedness;

use App\Models\Modules\EmergencyPreparedness\EmergencyDrill;
use App\Models\User;
use App\Core\Services\ScopeService;

class EmergencyDrillPolicy
{
    public function __construct(
        protected ScopeService $scopeService
    ) {
    }
    public function viewAny(User $user): bool
    {
        return $user->can('emergency.drills.view');
    }

    public function view(User $user, EmergencyDrill $emergencyDrill): bool
    {
        if (! $user->can('emergency.drills.view')) {
            return false;
        }

        return $this->scopeService->canAccess($user, $emergencyDrill);
    }

    public function create(User $user): bool
    {
        return $user->can('emergency.drills.create');
    }

    public function update(User $user, EmergencyDrill $emergencyDrill): bool
    {
        if (! $user->can('emergency.drills.update')) {
            return false;
        }

        return $this->scopeService->canAccess($user, $emergencyDrill);
    }

    public function execute(User $user, EmergencyDrill $emergencyDrill): bool
    {
        if (! $user->can('emergency.drills.execute')) {
            return false;
        }

        // Can only execute scheduled drills
        if ($emergencyDrill->status !== 'scheduled') {
            return false;
        }

        return $this->scopeService->canAccess($user, $emergencyDrill);
    }

    public function delete(User $user, EmergencyDrill $emergencyDrill): bool
    {
        // Only Super Admin and Admin can delete
        if (! $user->hasAnyRole(['Super Admin', 'Admin'])) {
            return false;
        }

        // Cannot delete executed drills
        if ($emergencyDrill->status === 'executed') {
            return false;
        }

        return true;
    }

    public function export(User $user): bool
    {
        return $user->can('emergency.drills.export');
    }
}

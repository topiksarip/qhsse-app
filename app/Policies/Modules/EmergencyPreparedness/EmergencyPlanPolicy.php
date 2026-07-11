<?php

declare(strict_types=1);

namespace App\Policies\Modules\EmergencyPreparedness;

use App\Models\Modules\EmergencyPreparedness\EmergencyPlan;
use App\Models\User;
use App\Core\Services\ScopeService;

class EmergencyPlanPolicy
{
    public function __construct(
        protected ScopeService $scopeService
    ) {
    }
    public function viewAny(User $user): bool
    {
        return $user->can('emergency.plans.view');
    }

    public function view(User $user, EmergencyPlan $emergencyPlan): bool
    {
        if (! $user->can('emergency.plans.view')) {
            return false;
        }

        return $this->scopeService->canAccess($user, $emergencyPlan);
    }

    public function create(User $user): bool
    {
        return $user->can('emergency.plans.create');
    }

    public function update(User $user, EmergencyPlan $emergencyPlan): bool
    {
        if (! $user->can('emergency.plans.update')) {
            return false;
        }

        return $this->scopeService->canAccess($user, $emergencyPlan);
    }

    public function delete(User $user, EmergencyPlan $emergencyPlan): bool
    {
        // Only Super Admin and Admin can delete
        if (! $user->hasAnyRole(['Super Admin', 'Admin'])) {
            return false;
        }

        return true;
    }

    public function export(User $user): bool
    {
        return $user->can('emergency.plans.export');
    }
}

<?php

declare(strict_types=1);

namespace App\Policies\Modules\EmergencyPreparedness;

use App\Models\Modules\EmergencyPreparedness\EmergencyContact;
use App\Models\User;
use App\Core\Services\ScopeService;

class EmergencyContactPolicy
{
    public function __construct(
        protected ScopeService $scopeService
    ) {
    }
    public function viewAny(User $user): bool
    {
        return $user->can('emergency.contacts.view');
    }

    public function view(User $user, EmergencyContact $emergencyContact): bool
    {
        if (! $user->can('emergency.contacts.view')) {
            return false;
        }

        return $this->scopeService->canAccess($user, $emergencyContact);
    }

    public function create(User $user): bool
    {
        return $user->can('emergency.contacts.create');
    }

    public function update(User $user, EmergencyContact $emergencyContact): bool
    {
        if (! $user->can('emergency.contacts.update')) {
            return false;
        }

        return $this->scopeService->canAccess($user, $emergencyContact);
    }

    public function delete(User $user, EmergencyContact $emergencyContact): bool
    {
        // Only Super Admin and Admin can delete
        if (! $user->hasAnyRole(['Super Admin', 'Admin'])) {
            return false;
        }

        return true;
    }
}

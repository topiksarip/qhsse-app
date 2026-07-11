<?php

declare(strict_types=1);

namespace App\Policies\Modules\LegalCompliance;

use App\Models\Modules\LegalCompliance\LegalRegister;
use App\Models\User;
use App\Services\Core\ScopeService;

class LegalRegisterPolicy
{
    public function __construct(
        private readonly ScopeService $scopeService
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('legal.register.view');
    }

    public function view(User $user, LegalRegister $legalRegister): bool
    {
        if (! $user->can('legal.register.view')) {
            return false;
        }

        return $this->scopeService->canAccess($user, $legalRegister);
    }

    public function create(User $user): bool
    {
        return $user->can('legal.register.create');
    }

    public function update(User $user, LegalRegister $legalRegister): bool
    {
        if (! $user->can('legal.register.update')) {
            return false;
        }

        // Cannot update inactive register
        if ($legalRegister->status === 'inactive') {
            return false;
        }

        return $this->scopeService->canAccess($user, $legalRegister);
    }

    public function delete(User $user, LegalRegister $legalRegister): bool
    {
        // Only Super Admin and Admin can delete
        if (! $user->hasAnyRole(['Super Admin', 'Admin'])) {
            return false;
        }

        // Cannot delete register with pending obligations
        if ($legalRegister->obligations()->pending()->exists()) {
            return false;
        }

        return true;
    }

    public function export(User $user): bool
    {
        return $user->can('legal.register.export');
    }

    public function changeComplianceStatus(User $user, LegalRegister $legalRegister): bool
    {
        if (! $user->can('legal.register.update')) {
            return false;
        }

        if ($legalRegister->status === 'inactive') {
            return false;
        }

        return $this->scopeService->canAccess($user, $legalRegister);
    }

    public function viewObligations(User $user, LegalRegister $legalRegister): bool
    {
        if (! $user->can('legal.obligations.view')) {
            return false;
        }

        return $this->scopeService->canAccess($user, $legalRegister);
    }

    public function createObligation(User $user, LegalRegister $legalRegister): bool
    {
        if (! $user->can('legal.obligations.create')) {
            return false;
        }

        if ($legalRegister->status === 'inactive') {
            return false;
        }

        return $this->scopeService->canAccess($user, $legalRegister);
    }

    public function updateObligation(User $user, LegalRegister $legalRegister): bool
    {
        if (! $user->can('legal.obligations.update')) {
            return false;
        }

        if ($legalRegister->status === 'inactive') {
            return false;
        }

        return $this->scopeService->canAccess($user, $legalRegister);
    }
}

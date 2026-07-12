<?php

declare(strict_types=1);

namespace App\Policies\Modules\LegalCompliance;

use App\Core\Services\ScopeService;
use App\Models\Modules\LegalCompliance\LegalObligation;
use App\Models\User;

class LegalObligationPolicy
{
    public function __construct(
        private readonly ScopeService $scopeService
    ) {}

    public function view(User $user, LegalObligation $legalObligation): bool
    {
        if (! $user->can('legal.obligations.view')) {
            return false;
        }

        $legalRegister = $legalObligation->legalRegister;

        return $this->scopeService->canAccess($user, $legalRegister);
    }

    public function update(User $user, LegalObligation $legalObligation): bool
    {
        if (! $user->can('legal.obligations.update')) {
            return false;
        }

        $legalRegister = $legalObligation->legalRegister;

        // Cannot update obligation of inactive register
        if ($legalRegister->status === 'inactive') {
            return false;
        }

        return $this->scopeService->canAccess($user, $legalRegister);
    }

    public function delete(User $user, LegalObligation $legalObligation): bool
    {
        // Only QHSSE Manager and Admin can delete obligations
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager'])) {
            return false;
        }

        $legalRegister = $legalObligation->legalRegister;

        if ($legalRegister->status === 'inactive') {
            return false;
        }

        return $this->scopeService->canAccess($user, $legalRegister);
    }

    public function complete(User $user, LegalObligation $legalObligation): bool
    {
        if (! $user->can('legal.obligations.update')) {
            return false;
        }

        // Only pending obligations can be completed
        if ($legalObligation->status !== 'pending') {
            return false;
        }

        $legalRegister = $legalObligation->legalRegister;

        if ($legalRegister->status === 'inactive') {
            return false;
        }

        return $this->scopeService->canAccess($user, $legalRegister);
    }
}

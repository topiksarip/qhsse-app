<?php

namespace App\Policies\Modules\Contractor;

use App\Models\Modules\Contractor\Contractor;
use App\Models\User;
use App\Modules\Contractor\ContractorAccess;

class ContractorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contractor.management.view');
    }

    public function view(User $user, Contractor $contractor): bool
    {
        if (! $user->can('contractor.management.view')) {
            return false;
        }

        return app(ContractorAccess::class)->canView($user, $contractor);
    }

    public function create(User $user): bool
    {
        return $user->can('contractor.management.create');
    }

    public function update(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.update');
    }

    public function delete(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.delete');
    }

    public function restore(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.delete');
    }

    public function forceDelete(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('contractor.management.export');
    }

    public function approve(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.approve')
            && in_array($contractor->approval_status, ['submitted']);
    }

    public function reject(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.approve')
            && in_array($contractor->approval_status, ['submitted']);
    }

    public function evaluate(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.evaluate');
    }
    public function suspend(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.update')
            && $contractor->contract_status === 'active';
    }

    public function activate(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.update')
            && in_array($contractor->contract_status, ['pending', 'suspended']);
    }

    public function terminate(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.delete')
            && in_array($contractor->contract_status, ['active', 'suspended']);
    }
}

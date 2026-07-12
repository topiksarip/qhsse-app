<?php

namespace App\Policies\Modules\Contractor;

use App\Models\Modules\Contractor\Contractor;
use App\Models\User;

class ContractorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contractor.management.view');
    }

    public function view(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.view');
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

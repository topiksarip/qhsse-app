<?php

namespace App\Policies\Modules\Permit;

use App\Models\Modules\Permit\Permit;
use App\Models\User;

class PermitPolicy
{
    /**
     * Determine whether the user can view any permits.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('permit.work.view');
    }

    /**
     * Determine whether the user can view the permit.
     */
    public function view(User $user, Permit $permit): bool
    {
        if (! $user->can('permit.work.view')) {
            return false;
        }

        // Organization scope check
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasAnyRole(['QHSSE Manager', 'Admin'])) {
            return true;
        }

        if ($user->hasRole('QHSSE Officer') && $user->employee?->site_id === $permit->site_id) {
            return true;
        }

        if ($user->hasAnyRole(['Supervisor', 'Department Head']) && $user->employee?->department_id === $permit->department_id) {
            return true;
        }

        if ($permit->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create permits.
     */
    public function create(User $user): bool
    {
        return $user->can('permit.work.create');
    }

    /**
     * Determine whether the user can update the permit.
     */
    public function update(User $user, Permit $permit): bool
    {
        if (! $user->can('permit.work.update')) {
            return false;
        }

        // Only draft permits can be edited
        if ($permit->status !== 'draft') {
            return false;
        }

        // Creator can edit their own draft
        if ($permit->created_by === $user->id) {
            return true;
        }

        // Admin/QHSSE can edit any draft
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the permit.
     */
    public function delete(User $user, Permit $permit): bool { return $user->can('permit.work.delete'); }

    /**
     * Determine whether the user can export permits.
     */
    public function export(User $user): bool
    {
        return $user->can('permit.work.export');
    }
}

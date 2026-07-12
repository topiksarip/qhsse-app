<?php

namespace App\Policies\Modules\Asset;

use App\Models\Modules\Asset\Asset;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('asset.management.view');
    }

    public function view(User $user, Asset $asset): bool
    {
        if (!$user->hasPermissionTo('asset.management.view')) {
            return false;
        }

        // Super Admin and Admin bypass scope
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // QHSSE Manager, Top Management, Auditor - all sites
        if ($user->hasRole(['QHSSE Manager', 'Top Management', 'Auditor'])) {
            return true;
        }

        // QHSSE Officer - assigned site
        if ($user->hasRole('QHSSE Officer')) {
            return $asset->site_id === $user->site_id;
        }

        // Supervisor, Department Head - department scope
        if ($user->hasRole(['Supervisor', 'Department Head'])) {
            return $asset->department_id === $user->department_id;
        }

        // Employee - own department (read-only)
        if ($user->hasRole('Employee')) {
            return $asset->department_id === $user->department_id;
        }

        // Contractor - company scope
        if ($user->hasRole('Contractor')) {
            return $asset->site_id === $user->site_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('asset.management.create');
    }

    public function update(User $user, Asset $asset): bool
    {
        if (!$user->hasPermissionTo('asset.management.update')) {
            return false;
        }

        // Cannot update decommissioned assets
        if ($asset->status === 'decommissioned') {
            return false;
        }

        // Super Admin and Admin bypass scope
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // QHSSE Manager - all sites
        if ($user->hasRole('QHSSE Manager')) {
            return true;
        }

        // QHSSE Officer - assigned site only
        if ($user->hasRole('QHSSE Officer')) {
            return $asset->site_id === $user->site_id;
        }

        return false;
    }

    public function delete(User $user, Asset $asset): bool
    {
        // Only Super Admin can delete
        return $user->hasRole('Super Admin');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('asset.management.export');
    }

    public function decommission(User $user, Asset $asset): bool
    {
        // Only Super Admin, Admin, QHSSE Manager can decommission
        return $user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager']);
    }
}

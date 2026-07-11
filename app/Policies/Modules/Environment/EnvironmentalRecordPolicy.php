<?php

namespace App\Policies\Modules\Environment;

use App\Models\Modules\Environment\EnvironmentalRecord;
use App\Models\User;

class EnvironmentalRecordPolicy
{
    /**
     * Determine whether the user can view any environmental records.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('environment.records.view');
    }

    /**
     * Determine whether the user can view the environmental record.
     */
    public function view(User $user, EnvironmentalRecord $environmentalRecord): bool
    {
        if (! $user->can('environment.records.view')) {
            return false;
        }

        // Organization scope check
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasAnyRole(['QHSSE Manager', 'Admin'])) {
            return true;
        }

        if ($user->hasRole('QHSSE Officer') && $user->employee?->site_id === $environmentalRecord->site_id) {
            return true;
        }

        if ($user->hasAnyRole(['Supervisor', 'Department Head']) && $user->employee?->site_id === $environmentalRecord->site_id) {
            return true;
        }

        if ($environmentalRecord->reporter_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create environmental records.
     */
    public function create(User $user): bool
    {
        return $user->can('environment.records.create');
    }

    /**
     * Determine whether the user can update the environmental record.
     */
    public function update(User $user, EnvironmentalRecord $environmentalRecord): bool
    {
        if (! $user->can('environment.records.update')) {
            return false;
        }

        // Reporter can edit their own record if not closed
        if ($environmentalRecord->reporter_id === $user->id && $environmentalRecord->status !== 'closed') {
            return true;
        }

        // QHSSE can edit any record if not closed
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager', 'QHSSE Officer']) && $environmentalRecord->status !== 'closed') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the environmental record.
     */
    public function delete(User $user, EnvironmentalRecord $environmentalRecord): bool
    {
        // Environmental records are never deleted, only closed
        return false;
    }

    /**
     * Determine whether the user can export environmental records.
     */
    public function export(User $user): bool
    {
        return $user->can('environment.records.export');
    }
}

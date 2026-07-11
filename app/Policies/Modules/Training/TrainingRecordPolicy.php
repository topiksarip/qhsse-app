<?php

namespace App\Policies\Modules\Training;

use App\Models\Modules\Training\TrainingRecord;
use App\Models\User;

class TrainingRecordPolicy
{
    /**
     * Determine whether the user can view any training records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('training.records.view');
    }

    /**
     * Determine whether the user can view the training record.
     */
    public function view(User $user, TrainingRecord $record): bool
    {
        if (! $user->hasPermissionTo('training.records.view')) {
            return false;
        }

        // Super Admin, Admin, QHSSE Manager, Auditor can view all
        if ($user->hasRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Auditor'])) {
            return true;
        }

        // QHSSE Officer can view records in their site
        if ($user->hasRole('QHSSE Officer')) {
            return $user->employee
                && $record->employee->site_id === $user->employee->site_id;
        }

        // Supervisor/Department Head can view records in their department
        if ($user->hasRole(['Supervisor', 'Department Head'])) {
            return $user->employee
                && $record->employee->department_id === $user->employee->department_id;
        }

        // Employee can only view own records
        return $user->employee && $record->employee_id === $user->employee->id;
    }

    /**
     * Determine whether the user can create training records.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('training.records.create');
    }

    /**
     * Determine whether the user can update the training record.
     */
    public function update(User $user, TrainingRecord $record): bool
    {
        if (! $user->hasPermissionTo('training.records.update')) {
            return false;
        }

        // Super Admin, Admin, QHSSE Manager can update all
        if ($user->hasRole(['Super Admin', 'Admin', 'QHSSE Manager'])) {
            return true;
        }

        // QHSSE Officer can update records in their site
        if ($user->hasRole('QHSSE Officer')) {
            return $user->employee
                && $record->employee->site_id === $user->employee->site_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the training record.
     */
    public function delete(User $user, TrainingRecord $record): bool
    {
        // Only Super Admin and Admin can delete training records
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    /**
     * Determine whether the user can export training records.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('training.records.export');
    }
}

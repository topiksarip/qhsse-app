<?php

declare(strict_types=1);

namespace App\Policies\Modules\RiskManagement;

use App\Models\Modules\RiskManagement\RiskRegister;
use App\Models\User;

class RiskRegisterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('risk.registers.view');
    }

    public function view(User $user, RiskRegister $riskRegister): bool
    {
        if (!$user->can('risk.registers.view')) {
            return false;
        }

        // Super Admin and Admin can view all
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // QHSSE Manager, Top Management, Auditor can view all
        if ($user->hasRole(['QHSSE Manager', 'Top Management', 'Auditor'])) {
            return true;
        }

        // QHSSE Officer can view in their site scope
        if ($user->hasRole('QHSSE Officer')) {
            if ($user->can('core.scope.all')) {
                return true;
            }
            if ($user->employee && $user->employee->site_id && $riskRegister->site_id === $user->employee->site_id) {
                return true;
            }
        }

        // Supervisor can view in their department
        if ($user->hasRole('Supervisor')) {
            if ($user->employee && $riskRegister->department_id === $user->employee->department_id) {
                return true;
            }
        }

        // Employee/Reporter can view in their department
        if ($user->employee && $riskRegister->department_id === $user->employee->department_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('risk.registers.create');
    }

    public function update(User $user, RiskRegister $riskRegister): bool
    {
        if (!$user->can('risk.registers.update')) {
            return false;
        }

        // Super Admin and Admin can update all
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // QHSSE Manager can update all
        if ($user->hasRole('QHSSE Manager')) {
            return true;
        }

        // QHSSE Officer can update in their site scope
        if ($user->hasRole('QHSSE Officer')) {
            if ($user->can('core.scope.all')) {
                return true;
            }
            if ($user->employee && $user->employee->site_id && $riskRegister->site_id === $user->employee->site_id) {
                return true;
            }
        }

        // Supervisor can update in their department
        if ($user->hasRole('Supervisor')) {
            if ($user->employee && $riskRegister->department_id === $user->employee->department_id) {
                return true;
            }
        }

        return false;
    }

    public function assess(User $user, RiskRegister $riskRegister): bool
    {
        if (!$user->can('risk.registers.assess')) {
            return false;
        }

        // Cannot assess obsolete records
        if ($riskRegister->isObsolete()) {
            return false;
        }

        // Only QHSSE roles can assess
        if ($user->hasRole(['Super Admin', 'Admin', 'QHSSE Manager', 'QHSSE Officer'])) {
            return true;
        }

        return false;
    }

    public function delete(User $user, RiskRegister $riskRegister): bool
    {
        return $user->can('risk.registers.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('risk.registers.export');
    }
}

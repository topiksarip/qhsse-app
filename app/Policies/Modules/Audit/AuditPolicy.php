<?php

namespace App\Policies\Modules\Audit;

use App\Models\Modules\Audit\Audit;
use App\Models\User;

class AuditPolicy
{
    public function view(User $user, Audit $audit): bool
    {
        if (! $user->can('audit.management.view')) {
            return false;
        }

        if ($user->can('core.scope.all')) {
            return true;
        }

        $employee = $user->employee;

        // Owner or lead auditor can always view
        if ($audit->created_by === $user->id || $audit->lead_auditor_id === $user->id) {
            return true;
        }

        // Department scope
        if ($user->can('core.scope.department') && $employee?->department_id === $audit->department_id) {
            return true;
        }

        // Site scope
        if ($user->can('core.scope.site') && $employee?->site_id !== null) {
            return $audit->department()->where('site_id', $employee->site_id)->exists();
        }

        return false;
    }

    public function update(User $user, Audit $audit): bool
    {
        if (! $user->can('audit.management.update')) {
            return false;
        }

        if ($audit->status !== 'planned') {
            return false;
        }

        if ($user->can('core.scope.all')) {
            return true;
        }

        $employee = $user->employee;

        // Owner can update
        if ($audit->created_by === $user->id) {
            return true;
        }

        // Department scope
        if ($user->can('core.scope.department') && $employee?->department_id === $audit->department_id) {
            return true;
        }

        // Site scope
        if ($user->can('core.scope.site') && $employee?->site_id !== null) {
            return $audit->department()->where('site_id', $employee->site_id)->exists();
        }

        return false;
    }
}

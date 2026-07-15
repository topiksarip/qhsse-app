<?php

namespace App\Modules\Training;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TrainingAccess
{
    /**
     * Apply organization scope to a TrainingRecord query based on the authenticated
     * user's core.scope.* permissions — NOT hardcoded role names.
     *
     * Scope is resolved through the related employee (site_id / department_id) because
     * TrainingRecord references an Employee, not a direct site_id column.
     */
    public function scope(Builder $query, ?User $user = null): Builder
    {
        $user ??= Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('core.scope.all')) {
            return $query;
        }

        $employee = $user->employee;

        $scoped = false;
        $query->where(function (Builder $q) use ($user, $employee, &$scoped): void {
            if ($user->can('core.scope.own')) {
                $q->orWhere('employee_id', $user->employee?->id);
                $scoped = true;
            }

            if ($user->can('core.scope.department') && $employee?->department_id) {
                $q->orWhereHas('employee', fn (Builder $eq) => $eq->where('department_id', $employee->department_id));
                $scoped = true;
            }

            if ($user->can('core.scope.site') && $employee?->site_id) {
                $q->orWhereHas('employee', fn (Builder $eq) => $eq->where('site_id', $employee->site_id));
                $scoped = true;
            }
        });

        // Fail closed: no applicable scope permission matched -> no rows
        if (! $scoped) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Resolve the employee query scope for "create record" / picker screens.
     * Returns a Builder<Employee> narrowed by core.scope.* (site/department/own).
     * Super Admin / Admin / QHSSE Manager reach everything via core.scope.all.
     */
    public function employeeScope(Builder $query, ?User $user = null): Builder
    {
        $user ??= Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('core.scope.all')) {
            return $query;
        }

        $employee = $user->employee;

        return $query->where(function (Builder $q) use ($user, $employee): void {
            if ($user->can('core.scope.own')) {
                $q->orWhere('id', $user->employee?->id);
            }

            if ($user->can('core.scope.department') && $employee?->department_id) {
                $q->orWhere('department_id', $employee->department_id);
            }

            if ($user->can('core.scope.site') && $employee?->site_id) {
                $q->orWhere('site_id', $employee->site_id);
            }
        });
    }
}

<?php

namespace App\Modules\Capa;

use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CapaAccess
{
    /**
     * Apply organization scope to CAPA query based on authenticated user's scope.
     * Uses permission-based scoping (core.scope.*) — NOT hardcoded role names.
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
                $q->orWhere('assigned_to', $user->id);
                $q->orWhere('assigned_by', $user->id);
                $scoped = true;
            }

            if ($user->can('core.scope.department') && $employee?->department_id) {
                $q->orWhere('department_id', $employee->department_id);
                $scoped = true;
            }

            if ($user->can('core.scope.site') && $employee?->site_id) {
                $q->orWhere('site_id', $employee->site_id);
                $scoped = true;
            }
        });

        if (! $scoped) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Check if user can access a specific CAPA action.
     * Permission-based — Super Admin / Admin / QHSSE Manager reach via core.scope.all,
     * and NO employee record is required (unlike the old hardcode-role logic).
     */
    public function canAccess(CapaAction $capa, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->can('core.scope.all')) {
            return true;
        }

        $employee = $user->employee;

        if ($user->can('core.scope.site') && $employee?->site_id && $capa->site_id === $employee->site_id) {
            return true;
        }

        if ($user->can('core.scope.department') && $employee?->department_id && $capa->department_id === $employee->department_id) {
            return true;
        }

        if ($user->can('core.scope.own') && ($capa->assigned_to === $user->id || $capa->assigned_by === $user->id)) {
            return true;
        }

        return false;
    }
}

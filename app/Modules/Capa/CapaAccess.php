<?php

namespace App\Modules\Capa;

use App\Models\Core\Employee\Employee;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CapaAccess
{
    /**
     * Apply organization scope to CAPA query based on authenticated user's employee context.
     * Fails closed: if no employee or inactive employee, returns empty result.
     */
    public function scope(Builder $query, ?User $user = null): Builder
    {
        $user ??= Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        /** @var Employee|null $employee */
        $employee = $user->employee()->where('is_active', true)->first();

        if (! $employee) {
            return $query->whereRaw('1 = 0');
        }

        $siteIds = $this->getSiteIds($employee);
        $departmentIds = $this->getDepartmentIds($employee);

        // CAPA is scoped by site_id and department_id
        return $query->where(function (Builder $q) use ($siteIds, $departmentIds): void {
            if (! empty($siteIds)) {
                $q->whereIn('site_id', $siteIds);
            }
            if (! empty($departmentIds)) {
                $q->whereIn('department_id', $departmentIds);
            }
        });
    }

    /**
     * Check if user can access a specific CAPA action.
     */
    public function canAccess(CapaAction $capa, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        /** @var Employee|null $employee */
        $employee = $user->employee()->where('is_active', true)->first();

        if (! $employee) {
            return false;
        }

        $siteIds = $this->getSiteIds($employee);
        $departmentIds = $this->getDepartmentIds($employee);

        $inSiteScope = in_array($capa->site_id, $siteIds, true);
        $inDepartmentScope = in_array($capa->department_id, $departmentIds, true);

        return $inSiteScope && $inDepartmentScope;
    }

    private function getSiteIds(Employee $employee): array
    {
        $roles = $employee->roles;

        // System Admin or QHSSE Manager sees all sites
        if ($roles->contains('name', 'System Admin') || $roles->contains('name', 'QHSSE Manager')) {
            return \App\Models\Core\MasterData\Site::query()->pluck('id')->toArray();
        }

        // Otherwise: employee's own site + their department's site if assigned
        $siteIds = [];
        if ($employee->site_id) {
            $siteIds[] = $employee->site_id;
        }
        if ($employee->department && $employee->department->site_id) {
            $siteIds[] = $employee->department->site_id;
        }

        return array_unique(array_filter($siteIds));
    }

    private function getDepartmentIds(Employee $employee): array
    {
        $roles = $employee->roles;

        // System Admin or QHSSE Manager sees all departments
        if ($roles->contains('name', 'System Admin') || $roles->contains('name', 'QHSSE Manager')) {
            return \App\Models\Core\MasterData\Department::query()->pluck('id')->toArray();
        }

        // QHSSE Officer: their site's departments
        if ($roles->contains('name', 'QHSSE Officer') && $employee->site_id) {
            return \App\Models\Core\MasterData\Department::query()
                ->where('site_id', $employee->site_id)
                ->pluck('id')
                ->toArray();
        }

        // Department Head: own department
        if ($roles->contains('name', 'Department Head') && $employee->department_id) {
            return [$employee->department_id];
        }

        // Supervisor / Contractor: own department
        if ($employee->department_id) {
            return [$employee->department_id];
        }

        return [];
    }
}

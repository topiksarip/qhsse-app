<?php

namespace App\Modules\Apd;

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Apd\ApdCatalog;
use App\Models\Modules\Apd\ApdItem;
use App\Models\Modules\Apd\ApdIssuance;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApdAccess
{
    public function scope(Builder $query, User $user): Builder
    {
        if ($user->can('core.scope.all')) {
            return $query;
        }

        $employee = $user->employee;

        if ($user->can('core.scope.site') && $employee?->site_id) {
            return $query->where('site_id', $employee->site_id);
        }

        if (($user->can('core.scope.department') || $user->can('core.scope.own'))
            && $employee?->department_id) {
            return $query->where('department_id', $employee->department_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function canView(User $user, ApdItem $item): bool
    {
        return $this->scope(ApdItem::query(), $user)->whereKey($item)->exists();
    }

    public function canViewCatalog(User $user, ApdCatalog $catalog): bool
    {
        // Catalog is global reference data; any apd.view user may see it.
        return $user->hasPermissionTo('apd.view');
    }

    public function canViewIssuance(User $user, ApdIssuance $issuance): bool
    {
        if (! $user->hasPermissionTo('apd.view')) {
            return false;
        }

        $item = $issuance->item;

        if (! $item) {
            return $user->can('core.scope.all');
        }

        return $this->canView($user, $item);
    }

    public function canUseLocation(User $user, int $siteId, ?int $departmentId = null): bool
    {
        if ($user->can('core.scope.all')) {
            return true;
        }

        $employee = $user->employee;

        if ($user->can('core.scope.site')) {
            if ($employee?->site_id && $employee->site_id !== $siteId) {
                return false;
            }
            if ($departmentId && $employee?->department_id && $employee->department_id !== $departmentId) {
                return false;
            }
            return true;
        }

        if (($user->can('core.scope.department') || $user->can('core.scope.own')) && $employee?->department_id) {
            if ($departmentId && $employee->department_id !== $departmentId) {
                return false;
            }
            return true;
        }

        return false;
    }

    /** @return Collection<int, Site> */
    public function sites(User $user): Collection
    {
        $query = Site::active()->orderBy('name');

        if (! $user->can('core.scope.all')) {
            $query->whereKey($user->employee?->site_id ?? 0);
        }

        return $query->get(['id', 'name', 'code']);
    }

    /** @return Collection<int, Area> */
    public function areas(User $user): Collection
    {
        $query = Area::query()->where('is_active', true)->orderBy('name');

        if (! $user->can('core.scope.all')) {
            $query->where('site_id', $user->employee?->site_id ?? 0);
        }

        return $query->get(['id', 'site_id', 'name']);
    }

    /** @return Collection<int, Department> */
    public function departments(User $user): Collection
    {
        $query = Department::active()->orderBy('name');
        $employee = $user->employee;

        if ($user->can('core.scope.all')) {
            return $query->get(['id', 'site_id', 'name']);
        }

        if ($user->can('core.scope.site')) {
            $query->where('site_id', $employee?->site_id ?? 0);
        } else {
            $query->whereKey($employee?->department_id ?? 0);
        }

        return $query->get(['id', 'site_id', 'name']);
    }

    /**
     * Scope issuance list by the related item's organization scope.
     */
    public function scopeIssuance(Builder $query, User $user): Builder
    {
        if ($user->can('core.scope.all')) {
            return $query;
        }

        return $query->whereExists(function ($exists) use ($user) {
            $exists->select(DB::raw(1))
                ->from('apd_items')
                ->whereColumn('apd_items.id', 'apd_issuances.apd_item_id')
                ->where($this->itemScopeClauses($user));
        });
    }

    /**
     * Scope inspection list by the related item's organization scope.
     */
    public function scopeInspection(Builder $query, User $user): Builder
    {
        if ($user->can('core.scope.all')) {
            return $query;
        }

        return $query->whereExists(function ($exists) use ($user) {
            $exists->select(DB::raw(1))
                ->from('apd_items')
                ->whereColumn('apd_items.id', 'apd_inspections.apd_item_id')
                ->where($this->itemScopeClauses($user));
        });
    }

    public function canViewInspection(User $user, \App\Models\Modules\Apd\ApdInspection $inspection): bool
    {
        if (! $user->hasPermissionTo('apd.view')) {
            return false;
        }

        $item = $inspection->item;

        if (! $item) {
            return $user->can('core.scope.all');
        }

        return $this->canView($user, $item);
    }

    private function itemScopeClauses(User $user): \Closure
    {
        $employee = $user->employee;

        return function ($q) use ($user, $employee) {
            if ($user->can('core.scope.site') && $employee?->site_id) {
                $q->where('site_id', $employee->site_id);
                if ($employee?->department_id) {
                    $q->where('department_id', $employee->department_id);
                }
                return;
            }

            if (($user->can('core.scope.department') || $user->can('core.scope.own')) && $employee?->department_id) {
                $q->where('department_id', $employee->department_id);
                return;
            }

            $q->whereRaw('1 = 0');
        };
    }

    /** @return Collection<int, \App\Models\Modules\Employee\Employee> */
    public function employees(User $user): Collection
    {
        $query = Employee::query()->orderBy('name');

        if (! $user->can('core.scope.all')) {
            $siteId = $user->employee?->site_id;
            if ($siteId) {
                $query->where('site_id', $siteId);
            }
        }

        return $query->get(['id', 'name', 'employee_number']);
    }

    /** @return Collection<int, \App\Models\Modules\Contractor\Contractor> */
    public function contractors(User $user): Collection
    {
        $query = \App\Models\Modules\Contractor\Contractor::query()->orderBy('name');

        if (! $user->can('core.scope.all')) {
            $siteId = $user->employee?->site_id;
            if ($siteId) {
                $query->where('site_id', $siteId);
            }
        }

        return $query->get(['id', 'name', 'contractor_number']);
    }
}

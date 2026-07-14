<?php

namespace App\Modules\Asset;

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Asset\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AssetAccess
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

    public function canView(User $user, Asset $asset): bool
    {
        return $this->scope(Asset::query(), $user)->whereKey($asset)->exists();
    }

    public function canUseLocation(User $user, int $siteId, ?int $departmentId = null): bool
    {
        if ($user->can('core.scope.all')) {
            return true;
        }

        $employee = $user->employee;

        if ($user->can('core.scope.site')) {
            return $employee?->site_id !== null && $employee->site_id === $siteId;
        }

        if ($user->can('core.scope.department') || $user->can('core.scope.own')) {
            return $employee?->site_id === $siteId
                && $employee?->department_id !== null
                && $employee->department_id === $departmentId;
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

    /** @return Collection<int, User> */
    public function inspectors(Asset $asset): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($asset): void {
                $query->whereHas('employee', fn (Builder $employee) => $employee->where('site_id', $asset->site_id))
                    ->orWhereHas('roles', fn (Builder $roles) => $roles->whereIn('name', ['Super Admin', 'Admin', 'QHSSE Manager']));
            })
            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', ['Super Admin', 'Admin', 'QHSSE Manager', 'QHSSE Officer']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function canInspect(Asset $asset, int $userId): bool
    {
        return $this->inspectors($asset)->contains('id', $userId);
    }
}

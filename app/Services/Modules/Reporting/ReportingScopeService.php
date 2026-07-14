<?php

namespace App\Services\Modules\Reporting;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Reporting\SavedReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportingScopeService
{
    public function scopeReports(Builder $query, User $user): Builder
    {
        if ($user->can('core.scope.all')) {
            return $query;
        }

        $employee = $user->employee;

        if ($user->can('core.scope.department') && $employee?->department_id) {
            return $query->where('parameters->department_id', $employee->department_id);
        }

        if ($user->can('core.scope.site') && $employee?->site_id) {
            return $query->where('parameters->site_id', $employee->site_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function canAccessReport(User $user, SavedReport $report): bool
    {
        if ($user->can('core.scope.all')) {
            return true;
        }

        $parameters = $report->parameters ?? [];
        $employee = $user->employee;

        if ($user->can('core.scope.department') && $employee?->department_id) {
            return (int) ($parameters['department_id'] ?? 0) === $employee->department_id;
        }

        if ($user->can('core.scope.site') && $employee?->site_id) {
            return (int) ($parameters['site_id'] ?? 0) === $employee->site_id;
        }

        return false;
    }

    public function scopedParameters(User $user, array $parameters): array
    {
        if ($user->can('core.scope.all')) {
            return $parameters;
        }

        $employee = $user->employee;

        if ($user->can('core.scope.department') && $employee?->department_id) {
            abort_if(isset($parameters['department_id']) && (int) $parameters['department_id'] !== $employee->department_id, 403);
            abort_if(isset($parameters['site_id']) && (int) $parameters['site_id'] !== $employee->site_id, 403);

            return array_merge($parameters, [
                'site_id' => $employee->site_id,
                'department_id' => $employee->department_id,
            ]);
        }

        if ($user->can('core.scope.site') && $employee?->site_id) {
            abort_if(isset($parameters['site_id']) && (int) $parameters['site_id'] !== $employee->site_id, 403);

            if (isset($parameters['department_id'])) {
                abort_unless(Department::query()
                    ->whereKey($parameters['department_id'])
                    ->where('site_id', $employee->site_id)
                    ->exists(), 403);
            }

            return array_merge($parameters, ['site_id' => $employee->site_id]);
        }

        abort(403);
    }

    /** @return Collection<int, Site> */
    public function availableSites(User $user): Collection
    {
        $query = Site::active()->orderBy('name');
        if (! $user->can('core.scope.all')) {
            $query->whereKey($user->employee?->site_id ?? 0);
        }

        return $query->get(['id', 'name', 'code']);
    }

    /** @return Collection<int, Department> */
    public function availableDepartments(User $user): Collection
    {
        $query = Department::active()->orderBy('name');
        if ($user->can('core.scope.all')) {
            return $query->get(['id', 'name', 'code', 'site_id']);
        }

        $employee = $user->employee;
        if ($user->can('core.scope.department')) {
            $query->whereKey($employee?->department_id ?? 0);
        } elseif ($user->can('core.scope.site')) {
            $query->where('site_id', $employee?->site_id ?? 0);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->get(['id', 'name', 'code', 'site_id']);
    }
}

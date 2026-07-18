<?php

namespace App\Modules\Incident;

use App\Models\Modules\Apd\ApdItem;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IncidentAccess
{
    /**
     * Scope permissions are seeded under the default `web` guard even when the
     * user authenticates via the `sanctum` (API) guard. Force the guard so the
     * check resolves the permission row regardless of the auth guard in use.
     */
    private function canScope(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission, 'web');
    }

    /** @return Builder<IncidentReport> */
    public function visibleQuery(User $user): Builder
    {
        $query = IncidentReport::query();

        if ($this->canScope($user, 'core.scope.all')) {
            return $query;
        }

        $employee = $user->employee;

        $scoped = false;
        $query->where(function (Builder $builder) use ($user, $employee, &$scoped): void {
            if ($this->canScope($user, 'core.scope.own')) {
                $builder->orWhere('reporter_id', $user->id);
                $scoped = true;
            }

            if ($this->canScope($user, 'core.scope.department') && $employee?->department_id) {
                $builder->orWhere('department_id', $employee->department_id);
                $scoped = true;
            }

            if ($this->canScope($user, 'core.scope.site') && $employee?->site_id) {
                $builder->orWhere('site_id', $employee->site_id);
                $scoped = true;
            }

            if ($this->canScope($user, 'core.scope.company') && $user->company_id) {
                $builder->orWhereHas('reporter', fn (Builder $reporter) => $reporter->where('company_id', $user->company_id));
                $scoped = true;
            }
        });

        if (! $scoped) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function ensureVisible(User $user, IncidentReport $incident): void
    {
        abort_unless($this->visibleQuery($user)->whereKey($incident->id)->exists(), 403);
    }

    public function ensureSiteAllowed(User $user, int $siteId): void
    {
        if ($this->canScope($user, 'core.scope.all')) {
            return;
        }

        if ($user->employee?->site_id) {
            abort_unless($user->employee->site_id === $siteId, 403);

            return;
        }

        abort_unless($this->canScope($user, 'core.scope.company'), 403);
    }

    /**
     * APD items accessible for PPE linking on an incident, scoped by location.
     *
     * @return Collection<int, ApdItem>
     */
    public function apdAccessibleItems(User $user): Collection
    {
        $query = ApdItem::query();

        if ($this->canScope($user, 'core.scope.all')) {
            return $query->orderBy('item_number')->get(['id', 'item_number', 'status']);
        }

        $employee = $user->employee;
        $query->where(function (Builder $builder) use ($employee): void {
            if ($employee?->site_id) {
                $builder->where('site_id', $employee->site_id);
            }
            if ($employee?->department_id) {
                $builder->orWhere('department_id', $employee->department_id);
            }
        });

        return $query->orderBy('item_number')->get(['id', 'item_number', 'status']);
    }
}
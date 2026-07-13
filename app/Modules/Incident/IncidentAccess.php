<?php

namespace App\Modules\Incident;

use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class IncidentAccess
{
    /** @return Builder<IncidentReport> */
    public function visibleQuery(User $user): Builder
    {
        $query = IncidentReport::query();

        if ($user->can('core.scope.all')) {
            return $query;
        }

        $employee = $user->employee;

        return $query->where(function (Builder $builder) use ($user, $employee): void {
            if ($user->can('core.scope.own')) {
                $builder->orWhere('reporter_id', $user->id);
            }

            if ($user->can('core.scope.department') && $employee?->department_id) {
                $builder->orWhere('department_id', $employee->department_id);
            }

            if ($user->can('core.scope.site') && $employee?->site_id) {
                $builder->orWhere('site_id', $employee->site_id);
            }

            if ($user->can('core.scope.company') && $user->company_id) {
                $builder->orWhereHas('reporter', fn (Builder $reporter) => $reporter->where('company_id', $user->company_id));
            }
        });
    }

    public function ensureVisible(User $user, IncidentReport $incident): void
    {
        abort_unless($this->visibleQuery($user)->whereKey($incident->id)->exists(), 403);
    }

    public function ensureSiteAllowed(User $user, int $siteId): void
    {
        if ($user->can('core.scope.all')) {
            return;
        }

        if ($user->employee?->site_id) {
            abort_unless($user->employee->site_id === $siteId, 403);

            return;
        }

        abort_unless($user->can('core.scope.company'), 403);
    }
}

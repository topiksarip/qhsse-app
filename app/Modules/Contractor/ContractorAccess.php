<?php

namespace App\Modules\Contractor;

use App\Models\Modules\Contractor\Contractor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scope-based access control for Contractor.
 *
 * Contractors are not bound to a single site; they carry an
 * `authorized_sites` JSON array. Users with `core.scope.site`
 * (or narrower) see only contractors authorized for their site.
 * Users with `core.scope.all` see everything.
 */
class ContractorAccess
{
    public function scope(Builder $query, User $user): Builder
    {
        if ($user->can('core.scope.all')) {
            return $query;
        }

        $siteId = $user->employee?->site_id;

        if (($user->can('core.scope.site')
            || $user->can('core.scope.department')
            || $user->can('core.scope.own'))
            && $siteId) {
            return $query->whereJsonContains('authorized_sites', $siteId);
        }

        return $query->whereRaw('1 = 0');
    }

    public function canView(User $user, Contractor $contractor): bool
    {
        return $this->scope(Contractor::query(), $user)->whereKey($contractor->getKey())->exists();
    }
}

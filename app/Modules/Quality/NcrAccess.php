<?php

namespace App\Modules\Quality;

use App\Models\Modules\Quality\Ncr;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class NcrAccess
{
    /** @return Builder<Ncr> */
    public function scope(User $user, ?Builder $query = null): Builder
    {
        $query ??= Ncr::query();

        if ($user->can('core.scope.all')) {
            return $query;
        }

        $siteId = $user->employee?->site_id;

        return $siteId ? $query->where('site_id', $siteId) : $query->whereRaw('1 = 0');
    }

    public function canAccess(User $user, Ncr $ncr): bool
    {
        return $this->scope($user)->whereKey($ncr->getKey())->exists();
    }
}

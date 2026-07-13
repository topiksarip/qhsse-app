<?php

namespace App\Modules\Security;

use App\Models\Modules\Security\VisitorLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class VisitorAccess
{
    /** @return Builder<VisitorLog> */
    public function scope(User $user, ?Builder $query = null): Builder
    {
        $query ??= VisitorLog::query();

        if ($user->can('core.scope.all')) {
            return $query;
        }

        $siteId = $user->employee?->site_id;

        return $siteId ? $query->where('site_id', $siteId) : $query->whereRaw('1 = 0');
    }

    public function canAccess(User $user, VisitorLog $visitor): bool
    {
        return $this->scope($user)->whereKey($visitor->getKey())->exists();
    }

    public function ensureSiteAllowed(User $user, int $siteId): void
    {
        if ($user->can('core.scope.all')) {
            return;
        }

        abort_unless((int) $user->employee?->site_id === $siteId, 403);
    }

    /** @return list<int>|null */
    public function allowedSiteIds(User $user): ?array
    {
        if ($user->can('core.scope.all')) {
            return null;
        }

        return $user->employee?->site_id ? [(int) $user->employee->site_id] : [];
    }
}

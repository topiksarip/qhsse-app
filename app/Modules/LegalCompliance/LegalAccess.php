<?php

namespace App\Modules\LegalCompliance;

use App\Models\Modules\LegalCompliance\LegalRegister;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scope-based access control for Legal Register (mirrors AssetAccess/ContractorAccess).
 *
 * Replaces hardcode hasAnyRole() in LegalRegisterController index/export (WS-3).
 */
class LegalAccess
{
    public function scope(Builder $query, User $user): Builder
    {
        if ($user->can('core.scope.all')) {
            return $query;
        }

        if ($user->can('core.scope.site')) {
            $siteId = $user->employee?->site_id;
            if ($siteId) {
                return $query->where('site_id', $siteId);
            }
        }

        if ($user->can('core.scope.department')) {
            $deptId = $user->employee?->department_id;
            if ($deptId) {
                return $query->where('department_id', $deptId);
            }
        }

        // own / fallback
        return $query->where('owner_id', $user->id);
    }

    public function canView(User $user, LegalRegister $register): bool
    {
        if ($user->can('core.scope.all')) {
            return true;
        }

        if ($user->can('core.scope.site')) {
            $siteId = $user->employee?->site_id;
            if ($siteId && $register->site_id === $siteId) {
                return true;
            }
        }

        if ($user->can('core.scope.department')) {
            if ($register->department_id && $register->department_id === $user->employee?->department_id) {
                return true;
            }
        }

        return $register->owner_id === $user->id;
    }
}

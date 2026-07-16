<?php

namespace App\Policies\Modules\Apd;

use App\Models\Modules\Apd\ApdInspection;
use App\Models\User;
use App\Modules\Apd\ApdAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApdInspectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('apd.view');
    }

    public function view(User $user, ApdInspection $inspection): bool
    {
        return app(ApdAccess::class)->canViewInspection($user, $inspection);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('apd.inspect');
    }

    public function update(User $user, ApdInspection $inspection): bool
    {
        if (! $user->hasPermissionTo('apd.update')) {
            return false;
        }

        return app(ApdAccess::class)->canViewInspection($user, $inspection);
    }

    public function delete(User $user, ApdInspection $inspection): bool
    {
        if (! $user->hasPermissionTo('apd.delete')) {
            return false;
        }

        return app(ApdAccess::class)->canViewInspection($user, $inspection);
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('apd.export');
    }
}

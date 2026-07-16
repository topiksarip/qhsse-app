<?php

namespace App\Policies\Modules\Apd;

use App\Models\Modules\Apd\ApdItem;
use App\Models\User;
use App\Modules\Apd\ApdAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApdItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('apd.view');
    }

    public function view(User $user, ApdItem $item): bool
    {
        if (! $user->hasPermissionTo('apd.view')) {
            return false;
        }

        return app(ApdAccess::class)->canView($user, $item);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('apd.create');
    }

    public function receive(User $user): bool
    {
        return $user->hasPermissionTo('apd.create');
    }

    public function update(User $user, ApdItem $item): bool
    {
        if (! $user->hasPermissionTo('apd.update')) {
            return false;
        }

        return app(ApdAccess::class)->canView($user, $item);
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('apd.export');
    }
}

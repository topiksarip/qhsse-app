<?php

namespace App\Policies\Modules\Apd;

use App\Models\Modules\Apd\ApdIssuance;
use App\Models\User;
use App\Modules\Apd\ApdAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApdIssuancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('apd.view');
    }

    public function view(User $user, ApdIssuance $issuance): bool
    {
        if (! $user->hasPermissionTo('apd.view')) {
            return false;
        }

        return app(ApdAccess::class)->canViewIssuance($user, $issuance);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('apd.create')
            || $user->hasPermissionTo('apd.request');
    }

    public function request(User $user): bool
    {
        return $user->hasPermissionTo('apd.request');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermissionTo('apd.approve');
    }

    public function issue(User $user): bool
    {
        return $user->hasPermissionTo('apd.issue');
    }

    public function receive(User $user): bool
    {
        return $user->hasPermissionTo('apd.receive') || $user->hasPermissionTo('apd.issue');
    }

    public function update(User $user, ApdIssuance $issuance): bool
    {
        if (! $user->hasPermissionTo('apd.update')) {
            return false;
        }

        return app(ApdAccess::class)->canViewIssuance($user, $issuance);
    }

    public function delete(User $user, ApdIssuance $issuance): bool
    {
        if (! $user->hasPermissionTo('apd.delete')) {
            return false;
        }

        return app(ApdAccess::class)->canViewIssuance($user, $issuance);
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('apd.export');
    }
}

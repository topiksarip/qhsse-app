<?php

namespace App\Policies\Modules\Asset;

use App\Models\Modules\Asset\Asset;
use App\Models\User;
use App\Modules\Asset\AssetAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('asset.management.view');
    }

    public function view(User $user, Asset $asset): bool
    {
        if (! $user->hasPermissionTo('asset.management.view')) {
            return false;
        }

        return app(AssetAccess::class)->canView($user, $asset);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('asset.management.create');
    }

    public function update(User $user, Asset $asset): bool
    {
        if (! $user->hasPermissionTo('asset.management.update')) {
            return false;
        }

        if ($asset->status !== 'active') {
            return false;
        }

        return app(AssetAccess::class)->canView($user, $asset);
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('asset.management.export');
    }

    public function decommission(User $user, Asset $asset): bool
    {
        return $asset->status !== 'decommissioned'
            && $user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager'])
            && app(AssetAccess::class)->canView($user, $asset);
    }

    public function changeStatus(User $user, Asset $asset): bool
    {
        return in_array($asset->status, ['active', 'inactive'], true)
            && $user->hasPermissionTo('asset.management.update')
            && app(AssetAccess::class)->canView($user, $asset);
    }
}

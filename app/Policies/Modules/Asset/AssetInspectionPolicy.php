<?php

namespace App\Policies\Modules\Asset;

use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetInspection;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetInspectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('asset.inspections.view')
            && app(AssetPolicy::class)->view($user, $asset);
    }

    public function view(User $user, AssetInspection $inspection): bool
    {
        if (! $user->hasPermissionTo('asset.inspections.view')) {
            return false;
        }

        // Check if user can view the parent asset
        return app(AssetPolicy::class)->view($user, $inspection->asset);
    }

    public function create(User $user, Asset $asset): bool
    {
        if (! $user->hasPermissionTo('asset.inspections.create')) {
            return false;
        }

        // Cannot add inspections to decommissioned assets
        if ($asset->status === 'decommissioned') {
            return false;
        }

        // Must have permission to view parent asset
        return app(AssetPolicy::class)->view($user, $asset);
    }

    public function update(User $user, AssetInspection $inspection): bool
    {
        if (! $user->hasPermissionTo('asset.inspections.create')) {
            return false;
        }

        // Cannot update inspections of decommissioned assets
        if ($inspection->asset->status === 'decommissioned') {
            return false;
        }

        // Must have permission to view parent asset
        return app(AssetPolicy::class)->view($user, $inspection->asset);
    }

    public function delete(User $user, $model = null): bool
    {
        if (! $user->hasPermissionTo('asset.inspections.delete')) {
            return false;
        }

        // $model is either the parent Asset (scope check from index) or an
        // AssetInspection instance (record-level check), or null (class-string).
        $asset = $model instanceof AssetInspection ? $model->asset : $model;

        // Block delete of records belonging to a decommissioned asset.
        if ($asset instanceof Asset && $asset->status === 'decommissioned') {
            return false;
        }

        return true;
    }

    public function linkCapa(User $user, AssetInspection $inspection): bool
    {
        // Must be fail result to link CAPA
        if ($inspection->result !== 'fail') {
            return false;
        }

        return $user->hasPermissionTo('capa.actions.create')
            && app(AssetPolicy::class)->view($user, $inspection->asset);
    }
}

<?php

namespace App\Policies\Modules\Asset;

use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetCertificate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetCertificatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('asset.certificates.view')
            && app(AssetPolicy::class)->view($user, $asset);
    }

    public function view(User $user, AssetCertificate $certificate): bool
    {
        if (! $user->hasPermissionTo('asset.certificates.view')) {
            return false;
        }

        // Check if user can view the parent asset
        return app(AssetPolicy::class)->view($user, $certificate->asset);
    }

    public function create(User $user, Asset $asset): bool
    {
        if (! $user->hasPermissionTo('asset.certificates.create')) {
            return false;
        }

        // Cannot add certificates to decommissioned assets
        if ($asset->status === 'decommissioned') {
            return false;
        }

        // Must have permission to view parent asset
        return app(AssetPolicy::class)->view($user, $asset);
    }

    public function update(User $user, AssetCertificate $certificate): bool
    {
        if (! $user->hasPermissionTo('asset.certificates.update')) {
            return false;
        }

        // Cannot update certificates of decommissioned assets
        if ($certificate->asset->status === 'decommissioned') {
            return false;
        }

        // Must have permission to view parent asset
        return app(AssetPolicy::class)->view($user, $certificate->asset);
    }

    public function delete(User $user, $model = null): bool
    {
        if (! $user->hasPermissionTo('asset.certificates.delete')) {
            return false;
        }

        // $model is either the parent Asset (scope check from index) or an
        // AssetCertificate instance (record-level check), or null (class-string).
        $asset = $model instanceof AssetCertificate ? $model->asset : $model;

        // Block delete of records belonging to a decommissioned asset.
        if ($asset instanceof Asset && $asset->status === 'decommissioned') {
            return false;
        }

        return true;
    }
}

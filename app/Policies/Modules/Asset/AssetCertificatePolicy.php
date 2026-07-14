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

    public function delete(User $user, AssetCertificate $certificate): bool
    {
        return false;
    }
}

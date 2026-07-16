<?php

namespace App\Policies\Modules\Apd;

use App\Models\Modules\Apd\ApdCatalog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApdCatalogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('apd.view');
    }

    public function view(User $user, ApdCatalog $catalog): bool
    {
        return $user->hasPermissionTo('apd.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('apd.create');
    }

    public function update(User $user, ApdCatalog $catalog): bool
    {
        return $user->hasPermissionTo('apd.update');
    }

    public function delete(User $user, ?ApdCatalog $catalog = null): bool
    {
        if (! $user->hasPermissionTo('apd.delete')) {
            return false;
        }

        // Cannot delete a catalog that still has stock items.
        if ($catalog && $catalog->items()->exists()) {
            return false;
        }

        return true;
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('apd.export');
    }
}

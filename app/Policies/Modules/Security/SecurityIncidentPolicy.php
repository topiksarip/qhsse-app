<?php

namespace App\Policies\Modules\Security;

use App\Models\Modules\Security\SecurityIncident;
use App\Models\User;

class SecurityIncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('security.incidents.view');
    }

    public function view(User $user, SecurityIncident $securityIncident): bool
    {
        if (! $user->can('security.incidents.view')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasAnyRole(['QHSSE Manager', 'Admin'])) {
            return true;
        }

        if ($user->hasAnyRole(['QHSSE Officer', 'Security Officer']) && $user->employee?->site_id === $securityIncident->site_id) {
            return true;
        }

        if ($securityIncident->reported_by === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('security.incidents.create');
    }

    public function update(User $user, SecurityIncident $securityIncident): bool
    {
        if (! $user->can('security.incidents.update')) {
            return false;
        }

        if ($securityIncident->isClosed()) {
            return false;
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager'])) {
            return true;
        }

        if ($user->hasAnyRole(['QHSSE Officer', 'Security Officer']) && $user->employee?->site_id === $securityIncident->site_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, SecurityIncident $securityIncident): bool { return $user->can('security.incidents.delete'); }

    public function export(User $user): bool
    {
        return $user->can('security.incidents.export');
    }
}

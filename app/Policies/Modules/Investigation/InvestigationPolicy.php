<?php

namespace App\Policies\Modules\Investigation;

use App\Models\Modules\Investigation\Investigation;
use App\Models\User;

class InvestigationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('investigation.reports.view');
    }

    public function view(User $user, Investigation $investigation): bool
    {
        return $user->can('investigation.reports.view');
    }

    public function create(User $user): bool
    {
        return $user->can('investigation.reports.create');
    }

    public function update(User $user, Investigation $investigation): bool
    {
        return $user->can('investigation.reports.update');
    }

    public function delete(User $user, Investigation $investigation): bool
    {
        return $user->can('investigation.reports.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('investigation.reports.export');
    }
}

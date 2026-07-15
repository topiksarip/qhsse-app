<?php

namespace App\Policies\Modules\Inspection;

use App\Models\Modules\Inspection\Inspection;
use App\Models\User;

class InspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inspection.checklists.view');
    }

    public function view(User $user, Inspection $inspection): bool
    {
        return $user->can('inspection.checklists.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inspection.checklists.create');
    }

    public function update(User $user, Inspection $inspection): bool
    {
        return $user->can('inspection.checklists.update');
    }

    public function delete(User $user, Inspection $inspection): bool
    {
        return $user->can('inspection.checklists.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('inspection.checklists.export');
    }
}

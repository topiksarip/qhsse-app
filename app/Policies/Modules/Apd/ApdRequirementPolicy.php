<?php

namespace App\Policies\Modules\Apd;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApdRequirementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('apd.view');
    }

    public function create(User $user): bool
    {
        return $user->can('apd.requirements.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('apd.requirements.manage');
    }
}

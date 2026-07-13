<?php

namespace App\Policies\Modules\Security;

use App\Models\Modules\Security\PatrolChecklist;
use App\Models\User;

class PatrolChecklistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('security.patrols.view');
    }

    public function view(User $user, PatrolChecklist $patrol): bool
    {
        return $user->can('security.patrols.view') && $this->withinScope($user, $patrol);
    }

    public function create(User $user): bool
    {
        return $user->can('security.patrols.create');
    }

    public function update(User $user, PatrolChecklist $patrol): bool
    {
        return $user->can('security.patrols.create')
            && $patrol->isScheduled()
            && $this->withinScope($user, $patrol);
    }

    public function execute(User $user, PatrolChecklist $patrol): bool
    {
        return $user->can('security.patrols.execute')
            && ! $patrol->isCompleted()
            && $this->withinScope($user, $patrol);
    }

    public function export(User $user): bool
    {
        return $user->can('security.patrols.export');
    }

    private function withinScope(User $user, PatrolChecklist $patrol): bool
    {
        if ($user->can('core.scope.all')) {
            return true;
        }

        if ($user->employee?->site_id === $patrol->site_id) {
            return true;
        }

        return $patrol->assigned_to === $user->id;
    }
}

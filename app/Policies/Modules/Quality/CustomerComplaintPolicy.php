<?php

declare(strict_types=1);

namespace App\Policies\Modules\Quality;

use App\Models\Modules\Quality\CustomerComplaint;
use App\Models\User;

class CustomerComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('quality.complaints.view');
    }

    public function view(User $user, CustomerComplaint $complaint): bool
    {
        return $user->hasPermissionTo('quality.complaints.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('quality.complaints.view');
    }

    public function update(User $user, CustomerComplaint $complaint): bool
    {
        return $user->hasPermissionTo('quality.complaints.view') && $complaint->isOpen();
    }

    public function close(User $user, CustomerComplaint $complaint): bool
    {
        return $user->hasPermissionTo('quality.complaints.view') && $complaint->isOpen();
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('quality.complaints.view');
    }
}

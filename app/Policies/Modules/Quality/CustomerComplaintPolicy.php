<?php

declare(strict_types=1);

namespace App\Policies\Modules\Quality;

use App\Models\Modules\Quality\CustomerComplaint;
use App\Models\User;
use App\Modules\Quality\ComplaintAccess;

class CustomerComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('quality.complaints.view');
    }

    public function view(User $user, CustomerComplaint $complaint): bool
    {
        return $user->hasPermissionTo('quality.complaints.view')
            && app(ComplaintAccess::class)->canAccess($user, $complaint);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('quality.complaints.create');
    }

    public function update(User $user, CustomerComplaint $complaint): bool
    {
        return $user->hasPermissionTo('quality.complaints.update')
            && app(ComplaintAccess::class)->canAccess($user, $complaint)
            && $complaint->isOpen();
    }

    public function close(User $user, CustomerComplaint $complaint): bool
    {
        return $user->hasPermissionTo('quality.complaints.close')
            && app(ComplaintAccess::class)->canAccess($user, $complaint)
            && $complaint->isOpen();
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('quality.complaints.export');
    }
}

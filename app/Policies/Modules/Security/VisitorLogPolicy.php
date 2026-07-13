<?php

declare(strict_types=1);

namespace App\Policies\Modules\Security;

use App\Models\Modules\Security\VisitorLog;
use App\Models\User;
use App\Modules\Security\VisitorAccess;

class VisitorLogPolicy
{
    /**
     * Determine if user can view any visitor logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('security.visitor.view');
    }

    /**
     * Determine if user can view the visitor log.
     */
    public function view(User $user, VisitorLog $visitor): bool
    {
        return $user->hasPermissionTo('security.visitor.view')
            && app(VisitorAccess::class)->canAccess($user, $visitor);
    }

    /**
     * Determine if user can create visitor logs.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('security.visitor.log');
    }

    /**
     * Determine if user can update the visitor log.
     * Only allow update if visitor has not checked out yet.
     */
    public function update(User $user, VisitorLog $visitor): bool
    {
        return $user->hasPermissionTo('security.visitor.log')
            && app(VisitorAccess::class)->canAccess($user, $visitor)
            && $visitor->checked_out_at === null;
    }

    /**
     * Determine if user can check out the visitor.
     * Only allow check-out if visitor is still checked in.
     */
    public function checkOut(User $user, VisitorLog $visitor): bool
    {
        return $user->hasPermissionTo('security.visitor.log')
            && app(VisitorAccess::class)->canAccess($user, $visitor)
            && $visitor->status === 'checked_in'
            && $visitor->checked_out_at === null;
    }

    /**
     * Determine if user can export visitor logs.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('security.visitor.view');
    }
}

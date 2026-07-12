<?php

namespace App\Policies\Modules\Reporting;

use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reporting.templates.view');
    }

    public function view(User $user, ReportTemplate $template): bool
    {
        return $user->hasPermissionTo('reporting.templates.view');
    }

    public function create(User $user): bool
    {
        // Only users with create permission can create custom templates
        return $user->hasPermissionTo('reporting.templates.create');
    }

    public function update(User $user, ReportTemplate $template): bool
    {
        if (!$user->hasPermissionTo('reporting.templates.update')) {
            return false;
        }

        // Super Admin and Admin can update all templates
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // QHSSE Manager can update custom templates
        if ($user->hasRole('QHSSE Manager')) {
            return true;
        }

        return false;
    }

    public function delete(User $user, ReportTemplate $template): bool
    {
        // Only Super Admin and Admin can delete
        if (!$user->hasRole(['Super Admin', 'Admin'])) {
            return false;
        }

        // Pre-defined templates cannot be deleted
        if ($template->is_predefined) {
            return false;
        }

        // Cannot delete if reports exist
        if ($template->savedReports()->count() > 0) {
            return false;
        }

        return true;
    }

    public function activate(User $user, ReportTemplate $template): bool
    {
        return $user->hasPermissionTo('reporting.templates.update');
    }

    public function deactivate(User $user, ReportTemplate $template): bool
    {
        return $user->hasPermissionTo('reporting.templates.update');
    }

    public function viewConfig(User $user, ReportTemplate $template): bool
    {
        // Only users with update permission can view config
        return $user->hasPermissionTo('reporting.templates.update');
    }
}

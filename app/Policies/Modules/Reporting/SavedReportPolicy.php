<?php

namespace App\Policies\Modules\Reporting;

use App\Models\Modules\Reporting\SavedReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SavedReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reporting.reports.view');
    }

    public function view(User $user, SavedReport $report): bool
    {
        if (!$user->hasPermissionTo('reporting.reports.view')) {
            return false;
        }

        // Super Admin and Admin can view all
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // QHSSE Manager, Auditor, Top Management: view all (scope: all)
        if ($user->hasRole(['QHSSE Manager', 'Auditor', 'Top Management'])) {
            return true;
        }

        // Users can view their own reports
        if ($report->generated_by === $user->id) {
            return true;
        }

        // QHSSE Officer: view reports for their site
        if ($user->hasRole('QHSSE Officer')) {
            $params = $report->parameters ?? [];
            $reportSiteId = $params['site_id'] ?? null;
            
            if (!$reportSiteId) {
                // Report for all sites - allowed if officer's scope includes it
                return true;
            }
            
            return $user->employee?->site_id === $reportSiteId;
        }

        // Supervisor, Department Head: view reports for their department
        if ($user->hasRole(['Supervisor', 'Department Head'])) {
            $params = $report->parameters ?? [];
            $reportDeptId = $params['department_id'] ?? null;
            
            if (!$reportDeptId) {
                // Report for all departments - allowed if scope matches
                return $user->employee?->department_id !== null;
            }
            
            return $user->employee?->department_id === $reportDeptId;
        }

        return false;
    }

    public function generate(User $user): bool
    {
        return $user->hasPermissionTo('reporting.reports.generate');
    }

    public function download(User $user, SavedReport $report): bool
    {
        if (!$user->hasPermissionTo('reporting.reports.download')) {
            return false;
        }

        // Must be able to view the report first
        if (!$this->view($user, $report)) {
            return false;
        }

        // Report must be completed
        if (!$report->isCompleted()) {
            return false;
        }

        return true;
    }

    public function regenerate(User $user, SavedReport $report): bool
    {
        // Can regenerate if user can generate reports AND can view this report
        if (!$user->hasPermissionTo('reporting.reports.generate')) {
            return false;
        }

        return $this->view($user, $report);
    }

    public function delete(User $user, SavedReport $report): bool
    {
        // Only Super Admin, Admin, and QHSSE Manager can delete
        if (!$user->hasRole(['Super Admin', 'Admin', 'QHSSE Manager'])) {
            return false;
        }

        // Cannot delete while processing
        if ($report->isProcessing()) {
            return false;
        }

        return true;
    }

    public function viewParameters(User $user, SavedReport $report): bool
    {
        // Can view parameters if can view the report
        return $this->view($user, $report);
    }

    public function viewError(User $user, SavedReport $report): bool
    {
        // Can view error details if can view the report
        return $this->view($user, $report);
    }
}

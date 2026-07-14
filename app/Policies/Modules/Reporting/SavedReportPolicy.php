<?php

namespace App\Policies\Modules\Reporting;

use App\Models\Modules\Reporting\SavedReport;
use App\Models\User;
use App\Services\Modules\Reporting\ReportingScopeService;
use Illuminate\Auth\Access\HandlesAuthorization;

class SavedReportPolicy
{
    use HandlesAuthorization;

    public function __construct(
        protected ReportingScopeService $reportingScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reporting.reports.view');
    }

    public function view(User $user, SavedReport $report): bool
    {
        if (! $user->hasPermissionTo('reporting.reports.view')) {
            return false;
        }

        return $this->reportingScope->canAccessReport($user, $report);
    }

    public function generate(User $user): bool
    {
        return $user->hasPermissionTo('reporting.reports.generate');
    }

    public function download(User $user, SavedReport $report): bool
    {
        if (! $user->hasPermissionTo('reporting.reports.download')) {
            return false;
        }

        // Must be able to view the report first
        if (! $this->view($user, $report)) {
            return false;
        }

        // Report must be completed
        if (! $report->isCompleted()) {
            return false;
        }

        return true;
    }

    public function regenerate(User $user, SavedReport $report): bool
    {
        // Can regenerate if user can generate reports AND can view this report
        if (! $user->hasPermissionTo('reporting.reports.generate')) {
            return false;
        }

        return $this->view($user, $report);
    }

    public function delete(User $user, SavedReport $report): bool
    {
        // Only Super Admin, Admin, and QHSSE Manager can delete
        if (! $user->hasRole(['Super Admin', 'Admin', 'QHSSE Manager'])) {
            return false;
        }

        // Cannot delete while processing
        if ($report->isProcessing()) {
            return false;
        }

        return $this->reportingScope->canAccessReport($user, $report);
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

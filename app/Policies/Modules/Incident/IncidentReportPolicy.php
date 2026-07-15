<?php

namespace App\Policies\Modules\Incident;

use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;

class IncidentReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('incident.reports.view');
    }

    public function view(User $user, IncidentReport $incidentReport): bool
    {
        return $user->can('incident.reports.view');
    }

    public function create(User $user): bool
    {
        return $user->can('incident.reports.create');
    }

    public function update(User $user, IncidentReport $incidentReport): bool
    {
        return $user->can('incident.reports.update');
    }

    public function delete(User $user, IncidentReport $incidentReport): bool
    {
        return $user->can('incident.reports.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('incident.reports.export');
    }
}

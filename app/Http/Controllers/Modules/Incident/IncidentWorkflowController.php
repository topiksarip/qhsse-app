<?php

namespace App\Http\Controllers\Modules\Incident;

use App\Http\Controllers\Controller;
use App\Models\Modules\Incident\IncidentReport;
use App\Modules\Incident\IncidentAccess;
use App\Modules\Incident\IncidentLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class IncidentWorkflowController extends Controller
{
    public function __construct(
        private readonly IncidentLifecycle $lifecycle,
        private readonly IncidentAccess $access,
    ) {}

    public function submit(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        return $this->transition($request, $incidentReport, 'submit', 'submitted', null, 'Laporan insiden telah disubmit untuk review.');
    }

    public function review(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        return $this->transition($request, $incidentReport, 'review', 'under_review', null, 'Laporan insiden sedang dalam review.');
    }

    public function reject(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']])['reason'];

        return $this->transition($request, $incidentReport, 'reject', 'rejected', $reason, 'Laporan insiden telah ditolak.');
    }

    public function close(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']])['reason'];

        return $this->transition($request, $incidentReport, 'close', 'closed', $reason, 'Laporan insiden telah ditutup.');
    }

    private function transition(
        Request $request,
        IncidentReport $incident,
        string $action,
        string $status,
        ?string $reason,
        string $success,
    ): RedirectResponse {
        $actor = $request->user();
        $this->access->ensureVisible($actor, $incident);

        try {
            $this->lifecycle->transition($incident, $actor, $action, $status, $reason);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return redirect()->route('incident.reports.show', $incident)->with('success', $success);
    }
}

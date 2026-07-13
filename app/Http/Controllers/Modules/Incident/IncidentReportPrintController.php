<?php

namespace App\Http\Controllers\Modules\Incident;

use App\Http\Controllers\Controller;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\Workflow\WorkflowHistory;
use App\Models\Modules\Incident\IncidentReport;
use App\Modules\Incident\IncidentAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class IncidentReportPrintController extends Controller
{
    public function __construct(private readonly IncidentAccess $access) {}

    public function __invoke(Request $request, IncidentReport $incidentReport): View
    {
        $this->access->ensureVisible($request->user(), $incidentReport);
        $incidentReport->load(['site', 'area', 'department', 'reporter', 'severity', 'priority', 'involvedPersons']);

        return view('reports.incident-detail', [
            'incident' => $incidentReport,
            'evidence' => ManagedFile::query()
                ->where('module_name', 'incident')
                ->where('reference_id', $incidentReport->id)
                ->where('collection', 'evidence')
                ->whereNull('deleted_at')
                ->orderBy('created_at')
                ->get(),
            'history' => WorkflowHistory::query()
                ->where('module_name', 'incident')
                ->where('reference_id', $incidentReport->id)
                ->orderBy('created_at')
                ->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Modules\Incident\StoreIncidentReportRequest;
use App\Http\Requests\Modules\Incident\UpdateIncidentReportRequest;
use App\Http\Resources\Api\V1\IncidentReportResource;
use App\Http\Resources\ApiResponse;
use App\Models\Modules\Incident\IncidentReport;
use App\Modules\Incident\IncidentAccess;
use App\Modules\Incident\IncidentLifecycle;
use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Core\Workflow\WorkflowService;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * JSON API for Incident Reporting (Flutter / mobile).
 * Reuses the same core services as the Inertia web controller.
 */
class IncidentReportController extends ApiController
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly WorkflowService $workflowService,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
        private readonly IncidentAccess $access,
        private readonly IncidentLifecycle $lifecycle,
    ) {}

    public function index(Request $request, ListQuery $listQuery): \Illuminate\Http\JsonResponse
    {
        $items = $listQuery->paginate(
            $this->access->visibleQuery($request->user())->with(['site', 'area', 'severity', 'priority', 'reporter']),
            ['incident_number', 'title'],
            ['occurred_at', 'created_at', 'incident_number'],
            'occurred_at',
            15,
        );

        return $this->ok(
            IncidentReportResource::collection($items->items()),
            null,
            [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ]
        );
    }

    public function show(Request $request, IncidentReport $incidentReport): \Illuminate\Http\JsonResponse
    {
        $this->access->ensureVisible($request->user(), $incidentReport);
        $incidentReport->load(['site', 'area', 'department', 'reporter', 'severity', 'priority', 'involvedPersons', 'apdItem', 'capaActions']);

        return $this->ok(new IncidentReportResource($incidentReport));
    }

    public function store(StoreIncidentReportRequest $request): \Illuminate\Http\JsonResponse
    {
        $actor = $request->user();
        $validated = $request->validated();
        $this->access->ensureSiteAllowed($actor, (int) $validated['site_id']);

        $incident = DB::transaction(function () use ($validated, $actor) {
            $incident = IncidentReport::create([
                'incident_number' => 'TEMP-'.uniqid(),
                'title' => $validated['title'],
                'category' => $validated['category'],
                'occurred_at' => $validated['occurred_at'],
                'site_id' => $validated['site_id'],
                'area_id' => $validated['area_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'reporter_id' => $actor->id,
                'severity_id' => $validated['severity_id'],
                'priority_id' => $validated['priority_id'],
                'description' => $validated['description'],
                'immediate_action' => $validated['immediate_action'] ?? null,
                'ppe_involved' => $validated['ppe_involved'] ?? false,
                'apd_item_id' => $validated['apd_item_id'] ?? null,
                'ppe_failure' => $validated['ppe_failure'] ?? false,
                'ppe_notes' => $validated['ppe_notes'] ?? null,
                'status' => 'draft',
            ]);

            $generated = $this->numberingService->generate(
                moduleName: 'incident',
                actor: $actor,
                referenceType: IncidentReport::class,
                referenceId: $incident->id,
            );
            $incident->update(['incident_number' => $generated->number]);

            $this->workflowService->start('incident', $incident->id, $actor);
            $this->auditService->created($incident, $actor, 'incident', $incident->id);
            $this->activityService->log('incident', $incident->id, 'incident.created', 'Laporan insiden dibuat', $actor);

            if (! empty($validated['involved_persons'])) {
                foreach ($validated['involved_persons'] as $person) {
                    $incident->involvedPersons()->attach($person['employee_id'], ['note' => $person['note'] ?? null]);
                }
            }

            return $incident;
        });

        if (($validated['action'] ?? 'draft') === 'submit') {
            $this->lifecycle->transition($incident, $actor, 'submit', 'submitted');
            $incident->refresh();
        }

        return \App\Http\Resources\ApiResponse::ok(
            new IncidentReportResource($incident),
            'Laporan insiden berhasil dibuat.',
            [],
            201
        );
    }

    public function update(UpdateIncidentReportRequest $request, IncidentReport $incidentReport): \Illuminate\Http\JsonResponse
    {
        $actor = $request->user();
        $validated = $request->validated();
        $this->access->ensureVisible($actor, $incidentReport);
        abort_unless($incidentReport->status === 'draft', 409, 'Hanya insiden draft yang dapat diperbarui.');
        $this->access->ensureSiteAllowed($actor, (int) ($validated['site_id'] ?? $incidentReport->site_id));
        $oldValues = $incidentReport->getAttributes();

        DB::transaction(function () use ($incidentReport, $validated, $actor, $oldValues) {
            $incidentReport->update(Arr::except($validated, ['involved_persons', 'action']));

            if (isset($validated['involved_persons'])) {
                $sync = [];
                foreach ($validated['involved_persons'] as $person) {
                    $sync[$person['employee_id']] = ['note' => $person['note'] ?? null];
                }
                $incidentReport->involvedPersons()->sync($sync);
            }

            $incidentReport->refresh();
            $this->auditService->updated($incidentReport, $oldValues, $actor, 'incident', $incidentReport->id);
            $this->activityService->log('incident', $incidentReport->id, 'incident.updated', 'Laporan insiden diperbarui', $actor);
        });

        if (($validated['action'] ?? 'draft') === 'submit') {
            $this->lifecycle->transition($incidentReport, $actor, 'submit', 'submitted');
            $incidentReport->refresh();
        }

        return $this->ok(new IncidentReportResource($incidentReport), 'Laporan insiden berhasil diperbarui.');
    }

    public function destroy(Request $request, IncidentReport $incidentReport): \Illuminate\Http\JsonResponse
    {
        $actor = $request->user();
        $this->access->ensureVisible($actor, $incidentReport);
        abort_unless($actor->hasPermissionTo('incident.reports.delete', 'web'), 403, 'Forbidden.');
        abort_unless($incidentReport->status === 'draft', 409, 'Hanya insiden draft yang dapat dihapus.');

        DB::transaction(function () use ($incidentReport, $actor) {
            $this->auditService->deleted($incidentReport, $actor, 'incident', $incidentReport->id);
            $this->activityService->log('incident', $incidentReport->id, 'incident.deleted', 'Laporan insiden dihapus', $actor);
            $incidentReport->delete();
        });

        return $this->ok(null, 'Laporan insiden berhasil dihapus.');
    }

    public function submit(Request $request, IncidentReport $incidentReport): \Illuminate\Http\JsonResponse
    {
        $actor = $request->user();
        $this->access->ensureVisible($actor, $incidentReport);
        $this->lifecycle->transition($incidentReport, $actor, 'submit', 'submitted');
        $incidentReport->refresh();

        return $this->ok(new IncidentReportResource($incidentReport), 'Insiden diajukan.');
    }

    public function review(Request $request, IncidentReport $incidentReport): \Illuminate\Http\JsonResponse
    {
        $actor = $request->user();
        $this->access->ensureVisible($actor, $incidentReport);
        $this->lifecycle->transition($incidentReport, $actor, 'review', 'under_review');
        $incidentReport->refresh();

        return $this->ok(new IncidentReportResource($incidentReport), 'Insiden sedang ditinjau.');
    }

    public function close(Request $request, IncidentReport $incidentReport): \Illuminate\Http\JsonResponse
    {
        $actor = $request->user();
        $this->access->ensureVisible($actor, $incidentReport);
        $this->lifecycle->transition($incidentReport, $actor, 'close', 'closed', $request->input('reason'));
        $incidentReport->refresh();

        return $this->ok(new IncidentReportResource($incidentReport), 'Insiden ditutup.');
    }
}

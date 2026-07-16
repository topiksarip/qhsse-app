<?php

namespace App\Http\Controllers\Modules\Incident;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Core\Workflow\WorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Incident\StoreIncidentReportRequest;
use App\Http\Requests\Modules\Incident\UpdateIncidentReportRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Comments\Comment;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Core\Workflow\WorkflowHistory;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use App\Modules\Incident\IncidentAccess;
use App\Modules\Incident\IncidentLifecycle;
use App\Modules\Capa\CapaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentReportController extends Controller
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly WorkflowService $workflowService,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
        private readonly IncidentAccess $access,
        private readonly IncidentLifecycle $lifecycle,
        private readonly CapaService $capaService,
    ) {}

    public function index(Request $request, ListQuery $listQuery): Response
    {
        $items = $listQuery->paginate(
            $this->access->visibleQuery($request->user())->with(['site', 'area', 'severity', 'priority', 'reporter']),
            ['incident_number', 'title'],
            ['occurred_at', 'created_at', 'incident_number'],
            'occurred_at',
            15,
        );

        return Inertia::render('Modules/Incident/Index', [
            'items' => $items,
            'filters' => $listQuery->filters(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Modules/Incident/Form', $this->formOptions($request->user()) + ['item' => null]);
    }

    public function store(StoreIncidentReportRequest $request): RedirectResponse
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
        }

        return redirect()->route('incident.reports.show', $incident)
            ->with('success', 'Laporan insiden berhasil dibuat.');
    }

    public function show(Request $request, IncidentReport $incidentReport): Response
    {
        $this->access->ensureVisible($request->user(), $incidentReport);
        $incidentReport->load(['site', 'area', 'department', 'reporter', 'severity', 'priority', 'involvedPersons', 'apdItem', 'capaActions.assignedTo']);

        $evidence = ManagedFile::query()
            ->where('module_name', 'incident')
            ->where('reference_id', $incidentReport->id)
            ->where('collection', 'evidence')
            ->whereNull('deleted_at')
            ->get();

        $comments = Comment::query()
            ->where('module_name', 'incident')
            ->where('reference_id', $incidentReport->id)
            ->whereNull('deleted_at')
            ->with('author')
            ->orderBy('created_at')
            ->get();

        $activities = ActivityLog::query()
            ->where('module_name', 'incident')
            ->where('reference_id', $incidentReport->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $workflowHistory = WorkflowHistory::query()
            ->where('module_name', 'incident')
            ->where('reference_id', $incidentReport->id)
            ->orderBy('created_at')
            ->get();

        $workflowInstance = WorkflowInstance::query()
            ->where('module_name', 'incident')
            ->where('reference_id', $incidentReport->id)
            ->first();

        $availableTransitions = [];
        if ($workflowInstance) {
            $availableTransitions = collect($this->workflowService->availableTransitions($workflowInstance))
                ->whereIn('action_key', ['submit', 'review', 'reject', 'close'])
                ->map(fn ($t) => [
                    'action_key' => $t->action_key,
                    'action_label' => $t->action_label,
                    'requires_reason' => $t->requires_reason,
                ])
                ->values()
                ->all();
        }

        return Inertia::render('Modules/Incident/Show', [
            'incident' => $incidentReport,
            'evidence' => $evidence,
            'comments' => $comments,
            'activities' => $activities,
            'workflowHistory' => $workflowHistory,
            'availableTransitions' => $availableTransitions,
            'capaActions' => $incidentReport->capaActions->map(fn ($c) => [
                'id' => $c->id,
                'action_number' => $c->action_number,
                'title' => $c->title,
                'status' => $c->status,
            ]),
            'can' => [
                'escalate' => $request->user()->can('create', CapaAction::class),
            ],
            'users' => \App\Models\User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'priorities' => \App\Models\Core\MasterData\Priority::where('is_active', true)->orderBy('sla_days', 'desc')->get(['id', 'name']),
        ]);
    }

    /**
     * Escalate an incident (with PPE failure) to a CAPA action.
     */
    public function escalate(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        $this->access->ensureVisible($request->user(), $incidentReport);
        abort_unless($request->user()->can('create', CapaAction::class), 403);

        if (!$incidentReport->ppe_failure) {
            return back()->with('error', 'Hanya insiden dengan kegagalan APD yang dapat dieskalasi ke CAPA.');
        }

        $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $action = $this->capaService->escalateFrom('incident', $incidentReport->id, [
            'title' => 'Tindak lanjut PPE: ' . ($incidentReport->incident_number ?? $incidentReport->id),
            'description' => 'Insiden terkait kegagalan APD (ID ' . $incidentReport->id . '). ' . ($incidentReport->ppe_notes ?? ''),
            'site_id' => $incidentReport->site_id,
            'department_id' => $incidentReport->department_id,
            'assigned_to' => (int) $request->input('assigned_to'),
            'priority_id' => (int) $request->input('priority_id'),
            'due_date' => $request->input('due_date'),
            'source_type' => 'corrective',
        ], $request->user());

        return redirect()->route('capa.actions.show', $action)
            ->with('success', 'Insiden dieskalasi ke CAPA ' . $action->action_number . '.');
    }

    public function edit(Request $request, IncidentReport $incidentReport): Response
    {
        $this->access->ensureVisible($request->user(), $incidentReport);
        abort_unless($incidentReport->status === 'draft', 409);
        $incidentReport->load(['site', 'area', 'department', 'severity', 'priority', 'involvedPersons']);

        return Inertia::render('Modules/Incident/Form', $this->formOptions($request->user()) + ['item' => $incidentReport]);
    }

    public function update(UpdateIncidentReportRequest $request, IncidentReport $incidentReport): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();
        $this->access->ensureVisible($actor, $incidentReport);
        abort_unless($incidentReport->status === 'draft', 409);
        $this->access->ensureSiteAllowed($actor, (int) ($validated['site_id'] ?? $incidentReport->site_id));
        $oldValues = $incidentReport->getAttributes();
        $oldValues['involved_person_ids'] = $incidentReport->involvedPersons()->pluck('employees.id')->all();

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
            $incidentReport->setAttribute('involved_person_ids', $incidentReport->involvedPersons()->pluck('employees.id')->all());
            $this->auditService->updated($incidentReport, $oldValues, $actor, 'incident', $incidentReport->id);
            $this->activityService->log('incident', $incidentReport->id, 'incident.updated', 'Laporan insiden diperbarui', $actor);
        });

        if (($validated['action'] ?? 'draft') === 'submit') {
            $this->lifecycle->transition($incidentReport, $actor, 'submit', 'submitted');
        }

        return redirect()->route('incident.reports.show', $incidentReport)
            ->with('success', 'Laporan insiden berhasil diperbarui.');
    }

    public function destroy(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        $actor = $request->user();
        $this->access->ensureVisible($actor, $incidentReport);
        $this->authorize('delete', $incidentReport);
        abort_unless($incidentReport->status === 'draft', 409, 'Hanya insiden draft yang dapat dihapus.');

        DB::transaction(function () use ($incidentReport, $actor) {
            $this->auditService->deleted($incidentReport, $actor, 'incident', $incidentReport->id);
            $this->activityService->log('incident', $incidentReport->id, 'incident.deleted', 'Laporan insiden dihapus', $actor);
            $incidentReport->delete();
        });

        return redirect()->route('incident.reports.index')->with('success', 'Laporan insiden berhasil dihapus.');
    }

    public function export(Request $request, ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $query = $listQuery->apply(
            $this->access->visibleQuery($request->user())->with(['site', 'severity', 'priority', 'reporter']),
            ['incident_number', 'title'],
            ['occurred_at', 'created_at'],
            'occurred_at',
        );

        return $exporter->stream($query, [
            'Nomor' => 'incident_number',
            'Judul' => 'title',
            'Kategori' => 'category',
            'Severity' => fn ($item) => $item->severity?->name ?? '',
            'Priority' => fn ($item) => $item->priority?->name ?? '',
            'Status' => 'status',
            'Tanggal Kejadian' => fn ($item) => $item->occurred_at?->format('Y-m-d H:i') ?? '',
            'Reporter' => fn ($item) => $item->reporter?->name ?? '',
            'Site' => fn ($item) => $item->site?->name ?? '',
        ], 'incidents-export.csv');
    }

    private function formOptions(User $user): array
    {
        $sites = Site::query()->where('is_active', true);
        $areas = Area::query()->where('is_active', true);
        $departments = Department::query()->where('is_active', true);
        $employees = Employee::query()->where('is_active', true);

        if (! $user->can('core.scope.all') && $user->employee?->site_id) {
            $siteId = $user->employee?->site_id;
            $sites->whereKey($siteId ?? 0);
            $areas->where('site_id', $siteId ?? 0);
            $departments->where('site_id', $siteId ?? 0);
            $employees->where('site_id', $siteId ?? 0);
        }

        return [
            'sites' => $sites->orderBy('name')->get(['id', 'name']),
            'areas' => $areas->orderBy('name')->get(['id', 'name', 'site_id']),
            'departments' => $departments->orderBy('name')->get(['id', 'name', 'site_id']),
            'employees' => $employees->orderBy('name')->get(['id', 'name', 'employee_no', 'site_id']),
            'severities' => Severity::where('is_active', true)->orderBy('level', 'desc')->get(['id', 'name', 'level', 'color']),
            'priorities' => Priority::where('is_active', true)->orderBy('sla_days', 'desc')->get(['id', 'name', 'sla_days', 'color']),
            'apd_items' => $this->access->apdAccessibleItems($user),
        ];
    }
}

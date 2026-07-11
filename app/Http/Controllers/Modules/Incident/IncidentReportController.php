<?php

namespace App\Http\Controllers\Modules\Incident;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Files\FileReference;
use App\Core\Files\ManagedFileService;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Core\Workflow\WorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Incident\StoreIncidentReportRequest;
use App\Http\Requests\Modules\Incident\UpdateIncidentReportRequest;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Workflow\WorkflowHistory;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Core\Comments\Comment;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentReportController extends Controller
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly WorkflowService $workflowService,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
        private readonly NotificationService $notificationService,
        private readonly ManagedFileService $fileService,
    ) {}

    public function index(ListQuery $listQuery): Response
    {
        $items = $listQuery->paginate(
            IncidentReport::query()->with(['site', 'area', 'severity', 'priority', 'reporter']),
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

    public function create(): Response
    {
        return Inertia::render('Modules/Incident/Form', [
            'item' => null,
            'sites' => Site::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            'severities' => Severity::where('is_active', true)->orderBy('level', 'desc')->get(['id', 'name', 'level', 'color']),
            'priorities' => Priority::where('is_active', true)->orderBy('sla_days', 'desc')->get(['id', 'name', 'sla_days', 'color']),
        ]);
    }

    public function store(StoreIncidentReportRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();

        $incident = DB::transaction(function () use ($validated, $actor) {
            $incident = IncidentReport::create([
                'incident_number' => 'TEMP-' . uniqid(),
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
            $this->doSubmit($incident, $actor);
        }

        return redirect()->route('incident.reports.show', $incident)
            ->with('success', 'Laporan insiden berhasil dibuat.');
    }

    public function show(IncidentReport $incidentReport): Response
    {
        $incidentReport->load(['site', 'area', 'department', 'reporter', 'severity', 'priority', 'involvedPersons']);

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
        ]);
    }

    public function edit(IncidentReport $incidentReport): Response
    {
        $incidentReport->load(['site', 'area', 'department', 'severity', 'priority', 'involvedPersons']);

        return Inertia::render('Modules/Incident/Form', [
            'item' => $incidentReport,
            'sites' => Site::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            'severities' => Severity::where('is_active', true)->orderBy('level', 'desc')->get(['id', 'name', 'level', 'color']),
            'priorities' => Priority::where('is_active', true)->orderBy('sla_days', 'desc')->get(['id', 'name', 'sla_days', 'color']),
        ]);
    }

    public function update(UpdateIncidentReportRequest $request, IncidentReport $incidentReport): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();
        $oldValues = $incidentReport->getAttributes();

        DB::transaction(function () use ($incidentReport, $validated, $actor, $oldValues) {
            $incidentReport->update($validated);

            if (isset($validated['involved_persons'])) {
                $sync = [];
                foreach ($validated['involved_persons'] as $person) {
                    $sync[$person['employee_id']] = ['note' => $person['note'] ?? null];
                }
                $incidentReport->involvedPersons()->sync($sync);
            }

            $this->auditService->updated($incidentReport, $oldValues, $actor, 'incident', $incidentReport->id);
            $this->activityService->log('incident', $incidentReport->id, 'incident.updated', 'Laporan insiden diperbarui', $actor);
        });

        return redirect()->route('incident.reports.show', $incidentReport)
            ->with('success', 'Laporan insiden berhasil diperbarui.');
    }

    public function submit(IncidentReport $incidentReport, Request $request): RedirectResponse
    {
        try {
            $this->doSubmit($incidentReport, $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }
        return redirect()->route('incident.reports.show', $incidentReport)
            ->with('success', 'Laporan insiden telah disubmit untuk review.');
    }

    private function doSubmit(IncidentReport $incident, User $actor): void
    {
        DB::transaction(function () use ($incident, $actor) {
            $this->workflowService->transition('incident', $incident->id, 'submit', $actor);
            $incident->update(['status' => 'submitted']);
            $this->activityService->log('incident', $incident->id, 'incident.submitted', 'Laporan disubmit untuk review', $actor);

            $qhsseUsers = $this->getQhsseUsers();
            if ($qhsseUsers->isNotEmpty()) {
                $this->notificationService->notifyMany(
                    $qhsseUsers,
                    'incident.submitted',
                    ['incident_number' => $incident->incident_number, 'title' => $incident->title, 'actor_name' => $actor->name],
                    $actor,
                    'incident',
                    $incident->id,
                    route('incident.reports.show', $incident),
                );
            }
        });
    }

    public function review(IncidentReport $incidentReport, Request $request): RedirectResponse
    {
        $actor = $request->user();

        try {
            DB::transaction(function () use ($incidentReport, $actor) {
                $this->workflowService->transition('incident', $incidentReport->id, 'review', $actor);
                $incidentReport->update(['status' => 'under_review']);
                $this->activityService->log('incident', $incidentReport->id, 'incident.reviewing', 'Laporan sedang direview', $actor);

                if ($incidentReport->reporter) {
                    $this->notificationService->notify(
                        $incidentReport->reporter,
                        'incident.reviewing',
                        ['incident_number' => $incidentReport->incident_number, 'actor_name' => $actor->name],
                        $actor,
                        'incident',
                        $incidentReport->id,
                        route('incident.reports.show', $incidentReport),
                    );
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()->route('incident.reports.show', $incidentReport)
            ->with('success', 'Laporan insiden sedang dalam review.');
    }

    public function close(IncidentReport $incidentReport, Request $request): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $actor = $request->user();

        try {
            DB::transaction(function () use ($incidentReport, $actor, $request) {
                $this->workflowService->transition('incident', $incidentReport->id, 'close', $actor, $request->input('reason'));
                $incidentReport->update(['status' => 'closed']);
                $this->activityService->log('incident', $incidentReport->id, 'incident.closed', 'Laporan ditutup: ' . $request->input('reason'), $actor);

                if ($incidentReport->reporter) {
                    $this->notificationService->notify(
                        $incidentReport->reporter,
                        'incident.closed',
                        ['incident_number' => $incidentReport->incident_number, 'reason' => $request->input('reason'), 'actor_name' => $actor->name],
                        $actor,
                        'incident',
                        $incidentReport->id,
                        route('incident.reports.show', $incidentReport),
                    );
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()->route('incident.reports.show', $incidentReport)
            ->with('success', 'Laporan insiden telah ditutup.');
    }

    public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $query = $listQuery->apply(
            IncidentReport::query()->with(['site', 'severity', 'priority', 'reporter']),
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

    private function getQhsseUsers()
    {
        $officerRole = Role::where('name', 'QHSSE Officer')->first();
        $managerRole = Role::where('name', 'QHSSE Manager')->first();

        $userIds = collect();
        if ($officerRole) {
            $userIds = $userIds->merge($officerRole->users()->pluck('users.id'));
        }
        if ($managerRole) {
            $userIds = $userIds->merge($managerRole->users()->pluck('users.id'));
        }

        return User::whereIn('id', $userIds->unique())->where('is_active', true)->get();
    }
}

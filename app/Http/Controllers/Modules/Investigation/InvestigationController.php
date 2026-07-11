<?php

namespace App\Http\Controllers\Modules\Investigation;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Core\Workflow\WorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Investigation\StoreInvestigationRequest;
use App\Http\Requests\Modules\Investigation\UpdateInvestigationRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Comments\Comment;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\Workflow\WorkflowHistory;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\Investigation\Investigation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvestigationController extends Controller
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly WorkflowService $workflowService,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(ListQuery $listQuery): Response
    {
        $items = $listQuery->paginate(
            Investigation::query()->with(['incident', 'investigator']),
            ['investigation_number', 'title'],
            ['created_at', 'investigation_number'],
            'created_at',
            15,
        );

        return Inertia::render('Modules/Investigation/Index', [
            'items' => $items,
            'filters' => $listQuery->filters(),
        ]);
    }

    public function create(): Response
    {
        $incidents = IncidentReport::whereNotIn('status', ['closed', 'rejected'])
            ->orderBy('occurred_at', 'desc')
            ->get(['id', 'incident_number', 'title']);

        return Inertia::render('Modules/Investigation/Form', [
            'item' => null,
            'incidents' => $incidents,
        ]);
    }

    public function store(StoreInvestigationRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();

        $investigation = DB::transaction(function () use ($validated, $actor) {
            $investigation = Investigation::create([
                'investigation_number' => 'TEMP-' . uniqid(),
                'incident_id' => $validated['incident_id'],
                'title' => $validated['title'],
                'status' => 'draft',
                'investigator_id' => $actor->id,
                'root_cause' => $validated['root_cause'] ?? null,
                'five_whys' => $validated['five_whys'] ?? null,
                'fishbone' => $validated['fishbone'] ?? null,
                'contributing_factors' => $validated['contributing_factors'] ?? null,
                'timeline_events' => $validated['timeline_events'] ?? null,
                'recommendations' => $validated['recommendations'] ?? null,
            ]);

            $generated = $this->numberingService->generate(
                moduleName: 'investigation',
                actor: $actor,
                referenceType: Investigation::class,
                referenceId: $investigation->id,
            );
            $investigation->update(['investigation_number' => $generated->number]);

            $this->workflowService->start('investigation', $investigation->id, $actor);
            $this->auditService->created($investigation, $actor, 'investigation', $investigation->id);
            $this->activityService->log('investigation', $investigation->id, 'investigation.created', 'Investigasi dibuat', $actor);

            if (! empty($validated['team_members'])) {
                foreach ($validated['team_members'] as $member) {
                    $investigation->teamMembers()->attach($member['user_id'], ['role' => $member['role'] ?? null]);
                }
            }

            return $investigation;
        });

        if (($validated['action'] ?? 'draft') === 'start') {
            $this->doStart($investigation, $actor);
        }

        return redirect()->route('investigation.reports.show', $investigation)
            ->with('success', 'Investigasi berhasil dibuat.');
    }

    public function show(Investigation $investigation): Response
    {
        $investigation->load(['incident', 'investigator', 'teamMembers']);

        $evidence = ManagedFile::query()
            ->where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->whereNull('deleted_at')
            ->get();

        $comments = Comment::query()
            ->where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->whereNull('deleted_at')
            ->with('author')
            ->orderBy('created_at')
            ->get();

        $activities = ActivityLog::query()
            ->where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $workflowHistory = WorkflowHistory::query()
            ->where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->orderBy('created_at')
            ->get();

        $workflowInstance = WorkflowInstance::query()
            ->where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
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

        return Inertia::render('Modules/Investigation/Show', [
            'investigation' => $investigation,
            'evidence' => $evidence,
            'comments' => $comments,
            'activities' => $activities,
            'workflowHistory' => $workflowHistory,
            'availableTransitions' => $availableTransitions,
        ]);
    }

    public function edit(Investigation $investigation): Response
    {
        $investigation->load(['incident', 'investigator', 'teamMembers']);

        return Inertia::render('Modules/Investigation/Form', [
            'item' => $investigation,
            'incidents' => IncidentReport::orderBy('occurred_at', 'desc')->get(['id', 'incident_number', 'title']),
        ]);
    }

    public function update(UpdateInvestigationRequest $request, Investigation $investigation): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();
        $oldValues = $investigation->getAttributes();

        DB::transaction(function () use ($investigation, $validated, $actor, $oldValues) {
            $investigation->update($validated);

            if (isset($validated['team_members'])) {
                $sync = [];
                foreach ($validated['team_members'] as $member) {
                    $sync[$member['user_id']] = ['role' => $member['role'] ?? null];
                }
                $investigation->teamMembers()->sync($sync);
            }

            $this->auditService->updated($investigation, $oldValues, $actor, 'investigation', $investigation->id);
            $this->activityService->log('investigation', $investigation->id, 'investigation.updated', 'Investigasi diperbarui', $actor);
        });

        return redirect()->route('investigation.reports.show', $investigation)
            ->with('success', 'Investigasi berhasil diperbarui.');
    }

    public function start(Investigation $investigation, Request $request): RedirectResponse
    {
        try {
            $this->doStart($investigation, $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }
        return redirect()->route('investigation.reports.show', $investigation)
            ->with('success', 'Investigasi dimulai.');
    }

    private function doStart(Investigation $investigation, User $actor): void
    {
        DB::transaction(function () use ($investigation, $actor) {
            $this->workflowService->transition('investigation', $investigation->id, 'start', $actor);
            $investigation->update(['status' => 'in_progress', 'started_at' => now()]);
            $this->activityService->log('investigation', $investigation->id, 'investigation.started', 'Investigasi dimulai', $actor);

            $qhsseUsers = $this->getQhsseUsers();
            if ($qhsseUsers->isNotEmpty()) {
                $this->notificationService->notifyMany(
                    $qhsseUsers,
                    'investigation.started',
                    ['investigation_number' => $investigation->investigation_number, 'title' => $investigation->title, 'actor_name' => $actor->name],
                    $actor,
                    'investigation',
                    $investigation->id,
                    route('investigation.reports.show', $investigation),
                );
            }
        });
    }

    public function complete(Investigation $investigation, Request $request): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $actor = $request->user();

        if (blank($investigation->root_cause) || blank($investigation->recommendations)) {
            return back()->withErrors(['workflow' => 'Root cause dan rekomendasi wajib diisi sebelum menyelesaikan investigasi.']);
        }

        try {
            DB::transaction(function () use ($investigation, $actor, $request) {
                $this->workflowService->transition('investigation', $investigation->id, 'complete', $actor, $request->input('reason'));
                $investigation->update(['status' => 'completed', 'completed_at' => now()]);
                $this->activityService->log('investigation', $investigation->id, 'investigation.completed', 'Investigasi selesai: ' . $request->input('reason'), $actor);

                if ($investigation->investigator) {
                    $this->notificationService->notify(
                        $investigation->investigator,
                        'investigation.completed',
                        ['investigation_number' => $investigation->investigation_number, 'reason' => $request->input('reason'), 'actor_name' => $actor->name],
                        $actor,
                        'investigation',
                        $investigation->id,
                        route('investigation.reports.show', $investigation),
                    );
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()->route('investigation.reports.show', $investigation)
            ->with('success', 'Investigasi telah diselesaikan.');
    }

    public function cancel(Investigation $investigation, Request $request): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $actor = $request->user();

        try {
            DB::transaction(function () use ($investigation, $actor, $request) {
                $this->workflowService->transition('investigation', $investigation->id, 'cancel', $actor, $request->input('reason'));
                $investigation->update(['status' => 'cancelled']);
                $this->activityService->log('investigation', $investigation->id, 'investigation.cancelled', 'Investigasi dibatalkan: ' . $request->input('reason'), $actor);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()->route('investigation.reports.show', $investigation)
            ->with('success', 'Investigasi telah dibatalkan.');
    }

    public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $query = $listQuery->apply(
            Investigation::query()->with(['incident', 'investigator']),
            ['investigation_number', 'title'],
            ['created_at', 'investigation_number'],
            'created_at',
        );

        return $exporter->stream($query, [
            'Nomor' => 'investigation_number',
            'Judul' => 'title',
            'Insiden' => fn ($item) => $item->incident?->incident_number ?? '',
            'Status' => 'status',
            'Investigator' => fn ($item) => $item->investigator?->name ?? '',
            'Dibuat' => fn ($item) => $item->created_at?->format('Y-m-d H:i') ?? '',
        ], 'investigations-export.csv');
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

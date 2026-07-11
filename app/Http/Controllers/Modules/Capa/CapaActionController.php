<?php

namespace App\Http\Controllers\Modules\Capa;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Core\Workflow\WorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Capa\StoreCapaActionRequest;
use App\Http\Requests\Modules\Capa\UpdateCapaActionRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Comments\Comment;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\Workflow\WorkflowHistory;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CapaActionController extends Controller
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
            CapaAction::query()->with(['site', 'department', 'assignedTo', 'priority', 'severity']),
            ['action_number', 'title'],
            ['created_at', 'due_date', 'action_number'],
            'created_at',
            15,
        );

        return Inertia::render('Modules/Capa/Index', [
            'items' => $items,
            'filters' => $listQuery->filters(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Modules/Capa/Form', [
            'item' => null,
            'sites' => \App\Models\Core\MasterData\Site::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => \App\Models\Core\MasterData\Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            'severities' => \App\Models\Core\MasterData\Severity::where('is_active', true)->orderBy('level', 'desc')->get(['id', 'name', 'level', 'color']),
            'priorities' => \App\Models\Core\MasterData\Priority::where('is_active', true)->orderBy('sla_days', 'desc')->get(['id', 'name', 'sla_days', 'color']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCapaActionRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();

        $action = DB::transaction(function () use ($validated, $actor) {
            $action = CapaAction::create([
                'action_number' => 'TEMP-' . uniqid(),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'source_module' => $validated['source_module'] ?? 'manual',
                'source_reference_id' => $validated['source_reference_id'] ?? null,
                'source_type' => $validated['source_type'] ?? null,
                'site_id' => $validated['site_id'],
                'department_id' => $validated['department_id'] ?? null,
                'assigned_to' => $validated['assigned_to'],
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'due_date' => $validated['due_date'] ?? null,
                'severity_id' => $validated['severity_id'] ?? null,
                'priority_id' => $validated['priority_id'],
                'status' => 'open',
            ]);

            $generated = $this->numberingService->generate(moduleName: 'capa', actor: $actor, referenceType: CapaAction::class, referenceId: $action->id);
            $action->update(['action_number' => $generated->number]);

            $this->workflowService->start('capa', $action->id, $actor);
            $this->auditService->created($action, $actor, 'capa', $action->id);
            $this->activityService->log('capa', $action->id, 'capa.created', 'CAPA action dibuat', $actor);

            // Notify PIC
            $this->notificationService->notify(
                User::find($validated['assigned_to']),
                'capa.assigned',
                ['action_number' => $action->action_number, 'title' => $action->title, 'actor_name' => $actor->name],
                $actor, 'capa', $action->id,
                route('capa.actions.show', $action),
            );

            return $action;
        });

        return redirect()->route('capa.actions.show', $action)->with('success', 'CAPA action berhasil dibuat.');
    }

    public function show(CapaAction $capaAction): Response
    {
        $capaAction->load(['site', 'department', 'assignedTo', 'assignedBy', 'severity', 'priority', 'verifiedBy']);

        $evidence = ManagedFile::query()->where('module_name', 'capa')->where('reference_id', $capaAction->id)->whereNull('deleted_at')->get();
        $comments = Comment::query()->where('module_name', 'capa')->where('reference_id', $capaAction->id)->whereNull('deleted_at')->with('author')->orderBy('created_at')->get();
        $activities = ActivityLog::query()->where('module_name', 'capa')->where('reference_id', $capaAction->id)->orderBy('created_at', 'desc')->get();
        $workflowHistory = WorkflowHistory::query()->where('module_name', 'capa')->where('reference_id', $capaAction->id)->orderBy('created_at')->get();

        $workflowInstance = WorkflowInstance::query()->where('module_name', 'capa')->where('reference_id', $capaAction->id)->first();
        $availableTransitions = [];
        if ($workflowInstance) {
            $availableTransitions = collect($this->workflowService->availableTransitions($workflowInstance))
                ->map(fn ($t) => ['action_key' => $t->action_key, 'action_label' => $t->action_label, 'requires_reason' => $t->requires_reason])
                ->values()->all();
        }

        return Inertia::render('Modules/Capa/Show', [
            'action' => $capaAction,
            'evidence' => $evidence,
            'comments' => $comments,
            'activities' => $activities,
            'workflowHistory' => $workflowHistory,
            'availableTransitions' => $availableTransitions,
        ]);
    }

    public function edit(CapaAction $capaAction): Response
    {
        $capaAction->load(['site', 'department', 'assignedTo', 'severity', 'priority']);
        return Inertia::render('Modules/Capa/Form', [
            'item' => $capaAction,
            'sites' => \App\Models\Core\MasterData\Site::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => \App\Models\Core\MasterData\Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            'severities' => \App\Models\Core\MasterData\Severity::where('is_active', true)->orderBy('level', 'desc')->get(['id', 'name', 'level', 'color']),
            'priorities' => \App\Models\Core\MasterData\Priority::where('is_active', true)->orderBy('sla_days', 'desc')->get(['id', 'name', 'sla_days', 'color']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateCapaActionRequest $request, CapaAction $capaAction): RedirectResponse
    {
        $actor = $request->user();
        $oldValues = $capaAction->getAttributes();

        DB::transaction(function () use ($capaAction, $request, $actor, $oldValues) {
            $capaAction->update($request->validated());
            $this->auditService->updated($capaAction, $oldValues, $actor, 'capa', $capaAction->id);
            $this->activityService->log('capa', $capaAction->id, 'capa.updated', 'CAPA action diperbarui', $actor);
        });

        return redirect()->route('capa.actions.show', $capaAction)->with('success', 'CAPA action berhasil diperbarui.');
    }

    public function start(CapaAction $capaAction, Request $request): RedirectResponse
    {
        return $this->transition($capaAction, 'start', $request);
    }

    public function submitVerification(CapaAction $capaAction, Request $request): RedirectResponse
    {
        return $this->transition($capaAction, 'submit_verification', $request);
    }

    public function verifyClose(CapaAction $capaAction, Request $request): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $actor = $request->user();

        try {
            DB::transaction(function () use ($capaAction, $actor, $request) {
                $this->workflowService->transition('capa', $capaAction->id, 'verify_close', $actor, $request->input('reason'));
                $capaAction->update([
                    'status' => 'closed',
                    'verification_note' => $request->input('reason'),
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                    'closed_at' => now(),
                ]);
                $this->activityService->log('capa', $capaAction->id, 'capa.closed', 'CAPA diverifikasi & ditutup: ' . $request->input('reason'), $actor);
                if ($capaAction->assignedTo) {
                    $this->notificationService->notify($capaAction->assignedTo, 'capa.closed', ['action_number' => $capaAction->action_number, 'reason' => $request->input('reason'), 'actor_name' => $actor->name], $actor, 'capa', $capaAction->id, route('capa.actions.show', $capaAction));
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }
        return redirect()->route('capa.actions.show', $capaAction)->with('success', 'CAPA diverifikasi & ditutup.');
    }

    public function reject(CapaAction $capaAction, Request $request): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $actor = $request->user();

        try {
            DB::transaction(function () use ($capaAction, $actor, $request) {
                $this->workflowService->transition('capa', $capaAction->id, 'reject', $actor, $request->input('reason'));
                $capaAction->update(['status' => 'rejected', 'verification_note' => $request->input('reason')]);
                $this->activityService->log('capa', $capaAction->id, 'capa.rejected', 'CAPA ditolak: ' . $request->input('reason'), $actor);
                if ($capaAction->assignedTo) {
                    $this->notificationService->notify($capaAction->assignedTo, 'capa.rejected', ['action_number' => $capaAction->action_number, 'reason' => $request->input('reason'), 'actor_name' => $actor->name], $actor, 'capa', $capaAction->id, route('capa.actions.show', $capaAction));
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }
        return redirect()->route('capa.actions.show', $capaAction)->with('success', 'CAPA ditolak.');
    }

    public function restart(CapaAction $capaAction, Request $request): RedirectResponse
    {
        return $this->transition($capaAction, 'restart', $request);
    }

    private function transition(CapaAction $capaAction, string $actionKey, Request $request): RedirectResponse
    {
        $actor = $request->user();
        try {
            DB::transaction(function () use ($capaAction, $actionKey, $actor) {
                $instance = $this->workflowService->transition('capa', $capaAction->id, $actionKey, $actor);
                $capaAction->update(['status' => $instance->current_status]);
                $this->activityService->log('capa', $capaAction->id, 'capa.transitioned', "CAPA: {$actionKey}", $actor);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }
        return redirect()->route('capa.actions.show', $capaAction)->with('success', 'Status CAPA diperbarui.');
    }

    public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $query = $listQuery->apply(
            CapaAction::query()->with(['site', 'assignedTo', 'priority', 'severity']),
            ['action_number', 'title'], ['created_at', 'due_date'], 'created_at',
        );
        return $exporter->stream($query, [
            'Nomor' => 'action_number', 'Judul' => 'title', 'Deskripsi' => 'description',
            'Source' => 'source_module', 'Type' => 'source_type', 'Status' => 'status',
            'PIC' => fn ($i) => $i->assignedTo?->name ?? '',
            'Site' => fn ($i) => $i->site?->name ?? '',
            'Priority' => fn ($i) => $i->priority?->name ?? '',
            'Due Date' => fn ($i) => $i->due_date?->format('Y-m-d') ?? '',
        ], 'capa-actions-export.csv');
    }
}

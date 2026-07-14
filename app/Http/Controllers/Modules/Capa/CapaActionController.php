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
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
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
        private readonly \App\Modules\Capa\CapaAccess $capaAccess,
    ) {}

    public function index(ListQuery $listQuery, Request $request): Response
    {
        $query = $this->capaAccess->scope(CapaAction::query(), $request->user());
        
        $items = $listQuery->paginate(
            $query->with(['site', 'department', 'assignedTo', 'priority', 'severity']),
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

    public function create(Request $request): Response
    {
        $prefill = collect($request->only([
            'source_module',
            'source_reference_id',
            'source_type',
            'site_id',
            'department_id',
            'title',
            'description',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();

        foreach (['source_reference_id', 'site_id', 'department_id'] as $integerKey) {
            if (array_key_exists($integerKey, $prefill)) {
                $prefill[$integerKey] = (int) $prefill[$integerKey];
            }
        }

        return Inertia::render('Modules/Capa/Form', [
            'item' => null,
            'prefill' => $prefill,
            'sites' => Site::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            'severities' => Severity::where('is_active', true)->orderBy('level', 'desc')->get(['id', 'name', 'level', 'color']),
            'priorities' => Priority::where('is_active', true)->orderBy('sla_days', 'desc')->get(['id', 'name', 'sla_days', 'color']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCapaActionRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();

        $action = DB::transaction(function () use ($validated, $actor) {
            $action = CapaAction::create([
                'action_number' => 'TEMP-'.uniqid(),
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

    public function show(Request $request, CapaAction $capaAction): Response
    {
        abort_unless($this->capaAccess->canAccess($capaAction, $request->user()), 403);
        
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

    public function edit(Request $request, CapaAction $capaAction): Response
    {
        abort_unless($this->capaAccess->canAccess($capaAction, $request->user()), 403);
        
        $capaAction->load(['site', 'department', 'assignedTo', 'severity', 'priority']);

        return Inertia::render('Modules/Capa/Form', [
            'item' => $capaAction,
            'sites' => Site::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            'severities' => Severity::where('is_active', true)->orderBy('level', 'desc')->get(['id', 'name', 'level', 'color']),
            'priorities' => Priority::where('is_active', true)->orderBy('sla_days', 'desc')->get(['id', 'name', 'sla_days', 'color']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateCapaActionRequest $request, CapaAction $capaAction): RedirectResponse
    {
        abort_unless($this->capaAccess->canAccess($capaAction, $request->user()), 403);
        
        $actor = $request->user();
        $oldValues = $capaAction->getAttributes();

        DB::transaction(function () use ($capaAction, $request, $actor, $oldValues) {
            $capaAction->update($request->validated());
            $this->auditService->updated($capaAction, $oldValues, $actor, 'capa', $capaAction->id);
            $this->activityService->log('capa', $capaAction->id, 'capa.updated', 'CAPA action diperbarui', $actor);
        });

        return redirect()->route('capa.actions.show', $capaAction)->with('success', 'CAPA action berhasil diperbarui.');
    }

    public function start(Request $request, CapaAction $capaAction): RedirectResponse
    {
        abort_unless($this->capaAccess->canAccess($capaAction, $request->user()), 403);
        return $this->transition($capaAction, 'start', $request);
    }

    public function submitVerification(Request $request, CapaAction $capaAction): RedirectResponse
    {
        abort_unless($this->capaAccess->canAccess($capaAction, $request->user()), 403);
        return $this->transition($capaAction, 'submit_verification', $request);
    }

    public function verifyClose(Request $request, CapaAction $capaAction): RedirectResponse
    {
        abort_unless($this->capaAccess->canAccess($capaAction, $request->user()), 403);
        
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
                $this->activityService->log('capa', $capaAction->id, 'capa.closed', 'CAPA diverifikasi & ditutup: '.$request->input('reason'), $actor);
                if ($capaAction->assignedTo) {
                    $this->notificationService->notify($capaAction->assignedTo, 'capa.closed', ['action_number' => $capaAction->action_number, 'reason' => $request->input('reason'), 'actor_name' => $actor->name], $actor, 'capa', $capaAction->id, route('capa.actions.show', $capaAction));
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()->route('capa.actions.show', $capaAction)->with('success', 'CAPA diverifikasi & ditutup.');
    }

    public function reject(Request $request, CapaAction $capaAction): RedirectResponse
    {
        abort_unless($this->capaAccess->canAccess($capaAction, $request->user()), 403);
        
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $actor = $request->user();

        try {
            DB::transaction(function () use ($capaAction, $actor, $request) {
                $this->workflowService->transition('capa', $capaAction->id, 'reject', $actor, $request->input('reason'));
                $capaAction->update(['status' => 'rejected', 'verification_note' => $request->input('reason')]);
                $this->activityService->log('capa', $capaAction->id, 'capa.rejected', 'CAPA ditolak: '.$request->input('reason'), $actor);
                if ($capaAction->assignedTo) {
                    $this->notificationService->notify($capaAction->assignedTo, 'capa.rejected', ['action_number' => $capaAction->action_number, 'reason' => $request->input('reason'), 'actor_name' => $actor->name], $actor, 'capa', $capaAction->id, route('capa.actions.show', $capaAction));
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()->route('capa.actions.show', $capaAction)->with('success', 'CAPA ditolak.');
    }

    public function restart(Request $request, CapaAction $capaAction): RedirectResponse
    {
        abort_unless($this->capaAccess->canAccess($capaAction, $request->user()), 403);
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

    public function export(Request $request, ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $baseQuery = $this->capaAccess->scope(CapaAction::query(), $request->user());
        
        $query = $listQuery->apply(
            $baseQuery->with(['site', 'assignedTo', 'priority', 'severity']),
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

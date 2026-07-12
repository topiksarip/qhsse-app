<?php

namespace App\Http\Controllers\Modules\Audit;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Comments\CommentService;
use App\Core\Export\CsvExporter;
use App\Core\Files\FileReference;
use App\Core\Files\ManagedFileService;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Core\Workflow\WorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Audit\GenerateAuditReportRequest;
use App\Http\Requests\Modules\Audit\StoreAuditFindingRequest;
use App\Http\Requests\Modules\Audit\StoreAuditRequest;
use App\Http\Requests\Modules\Audit\UpdateAuditFindingRequest;
use App\Http\Requests\Modules\Audit\UpdateAuditRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Comments\Comment;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Audit\Audit;
use App\Models\Modules\Audit\AuditFinding;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    private const AUDIT_TYPES = [
        'internal' => 'Internal',
        'external' => 'Eksternal',
        'supplier' => 'Pemasok',
        'regulatory' => 'Regulator',
    ];

    public function __construct(
        private readonly NumberingService $numbering,
        private readonly WorkflowService $workflow,
        private readonly NotificationService $notifications,
        private readonly ActivityService $activity,
        private readonly AuditService $audit,
        private readonly ManagedFileService $files,
    ) {}

    public function index(Request $request, ListQuery $listQuery): Response
    {
        $query = $this->visibleQuery($request->user())->with([
            'department:id,name',
            'leadAuditor:id,name',
            'creator:id,name',
        ])->withCount([
            'findings',
            'findings as major_findings_count' => fn ($q) => $q->where('classification', 'major_nc'),
        ]);

        $this->applyFilters($query, $request);

        $audits = $listQuery->paginate(
            $query,
            ['audit_number', 'title'],
            ['created_at', 'scheduled_date', 'audit_number', 'title'],
            'scheduled_date',
            15,
        );

        return Inertia::render('Modules/Audit/Index', [
            'audits' => $audits,
            'filters' => $request->only(['search', 'status', 'audit_type', 'site_id', 'department_id']),
            'sites' => Site::query()->where('is_active', true)->get(['id', 'name']),
            'departments' => Department::query()->where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Modules/Audit/Form', $this->formProps());
    }

    public function store(StoreAuditRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();

        $this->ensureAssignmentWithinScope($actor, $validated);

        $audit = DB::transaction(function () use ($validated, $actor) {
            // Generate audit number first
            $generated = $this->numbering->generate(
                moduleName: 'audit',
                actor: $actor
            );

            $audit = Audit::create([
                'audit_number' => $generated->number,
                'title' => $validated['title'],
                'audit_type' => $validated['audit_type'],
                'scope' => $validated['scope'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'lead_auditor_id' => $validated['lead_auditor_id'],
                'scheduled_date' => $validated['scheduled_date'],
                'status' => 'planned',
                'created_by' => $actor->id,
            ]);

            // Update generated number with reference
            $generated->update([
                'reference_type' => Audit::class,
                'reference_id' => $audit->id,
            ]);

            $this->workflow->start('audit', $audit->id, $actor);

            $this->audit->log(
                'audit.created',
                $audit,
                [],
                $audit->getAttributes(),
                $actor,
                'audit',
                $audit->id,
            );

            $this->activity->log(
                moduleName: 'audit',
                referenceId: $audit->id,
                event: 'audit.created',
                description: "Audit {$audit->audit_number} dibuat oleh {$actor->name}",
                actor: $actor,
            );

            return $audit;
        });

        return redirect()->route('audits.show', $audit)->with('success', 'Audit berhasil dibuat.');
    }

    public function show(Audit $audit): Response
    {
        $this->authorize('view', $audit);

        $audit->load([
            'department:id,name',
            'leadAuditor:id,name,email',
            'creator:id,name',
            'findings' => fn ($q) => $q->with(['capaAction:id,capa_number,title,status', 'closedByUser:id,name'])->orderBy('created_at'),
        ]);

        $evidenceFiles = ManagedFile::query()
            ->where('module_name', 'audit')
            ->where('reference_id', $audit->id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $comments = Comment::query()
            ->where('module_name', 'audit')
            ->where('reference_id', $audit->id)
            ->whereNull('parent_id')
            ->with(['author:id,name', 'replies.author:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        $activities = ActivityLog::query()
            ->where('module_name', 'audit')
            ->where('reference_id', $audit->id)
            ->with('actor:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $workflow = $this->workflow->getWorkflow('audit', $audit->id);
        $user = auth()->user();

        return Inertia::render('Modules/Audit/Show', [
            'audit' => $audit,
            'findings' => $audit->findings,
            'evidenceFiles' => $evidenceFiles,
            'comments' => $comments,
            'activities' => $activities,
            'workflowHistory' => $workflow['history'],
            'availableTransitions' => $workflow['available_transitions'],
            'can' => [
                'update' => $this->canUpdate($audit),
                'start' => $this->canExecute($audit) && $audit->status === 'planned',
                'generate_report' => $this->canExecute($audit) && $audit->status === 'in_progress',
                'close' => $this->canClose($audit),
                'create_finding' => $this->canCreateFinding($audit),
                'close_finding' => $user->can('audit.findings.close') && in_array($audit->status, ['in_progress', 'report_ready']),
                'comment' => $user->can('core.comments.create'),
                'upload_file' => $user->can('core.files.upload'),
                'download_file' => $user->can('core.files.download'),
            ],
        ]);
    }

    public function edit(Audit $audit): Response
    {
        $this->authorize('update', $audit);

        abort_if($audit->status !== 'planned', 403, 'Hanya audit dengan status Direncanakan yang dapat diedit.');

        return Inertia::render('Modules/Audit/Form', array_merge(
            $this->formProps($audit),
            ['audit' => $audit->load(['department', 'leadAuditor'])]
        ));
    }

    public function update(UpdateAuditRequest $request, Audit $audit): RedirectResponse
    {
        $this->authorize('update', $audit);

        abort_if($audit->status !== 'planned', 403, 'Hanya audit dengan status Direncanakan yang dapat diedit.');

        $actor = $request->user();
        $validated = $request->validated();

        $this->ensureAssignmentWithinScope($actor, $validated);

        $oldValues = $audit->toArray();

        $audit->update([
            'title' => $validated['title'],
            'audit_type' => $validated['audit_type'],
            'scope' => $validated['scope'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'lead_auditor_id' => $validated['lead_auditor_id'],
            'scheduled_date' => $validated['scheduled_date'],
        ]);

        $this->audit->updated($audit, $oldValues, $actor, 'audit', $audit->id);

        $this->activity->log(
            moduleName: 'audit',
            referenceId: $audit->id,
            event: 'audit.updated',
            description: "Audit {$audit->audit_number} diperbarui oleh {$actor->name}",
            actor: $actor,
        );

        return redirect()->route('audits.show', $audit)->with('success', 'Audit berhasil diperbarui.');
    }

    public function startAudit(Request $request, Audit $audit): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($this->canExecute($audit), 403);

        // Only allow starting audits in 'planned' status
        abort_if($audit->status !== 'planned', 403, 'Audit harus berstatus planned untuk dimulai.');

        try {
            DB::transaction(function () use ($audit, $actor): void {
                $this->workflow->transition('audit', $audit->id, 'start', $actor);
                $audit->update(['status' => 'in_progress', 'start_date' => today()]);
                $this->activity->log('audit', $audit->id, 'audit.started', 'Audit dimulai', $actor);
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return back()->with('success', 'Audit dimulai.');
    }

    public function generateReport(GenerateAuditReportRequest $request, Audit $audit): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($this->canExecute($audit), 403);

        // Only allow generating report for audits in 'in_progress' status
        abort_if($audit->status !== 'in_progress', 403, 'Audit harus berstatus in_progress untuk membuat laporan.');

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($audit, $actor, $validated): void {
                $this->workflow->transition('audit', $audit->id, 'generate_report', $actor);
                $audit->update(['status' => 'report_ready', 'report_date' => today(), 'summary' => $validated['summary']]);
                $this->activity->log('audit', $audit->id, 'audit.report_generated', 'Laporan audit dibuat', $actor);
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return back()->with('success', 'Laporan audit dibuat.');
    }

    public function closeAudit(Request $request, Audit $audit): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($this->canClose($audit), 403);
        abort_unless($audit->allFindingsClosed(), 422, 'Semua temuan harus ditutup sebelum audit dapat ditutup.');
        abort_unless($audit->majorFindingsHaveCapa(), 422, 'Semua temuan major harus memiliki CAPA sebelum audit dapat ditutup.');

        try {
            DB::transaction(function () use ($audit, $actor): void {
                $this->workflow->transition('audit', $audit->id, 'close', $actor);
                $audit->update(['status' => 'closed', 'close_date' => today()]);
                $this->activity->log('audit', $audit->id, 'audit.closed', 'Audit ditutup', $actor);
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return back()->with('success', 'Audit ditutup.');
    }

    public function storeFinding(StoreAuditFindingRequest $request, Audit $audit): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($this->canCreateFinding($audit), 403);

        $validated = $request->validated();

        $finding = DB::transaction(function () use ($audit, $validated, $actor) {
            $findingCount = $audit->findings()->count() + 1;
            $findingNumber = "{$audit->audit_number}-F".str_pad((string) $findingCount, 2, '0', STR_PAD_LEFT);

            $finding = AuditFinding::create([
                'audit_id' => $audit->id,
                'finding_number' => $findingNumber,
                'classification' => $validated['classification'],
                'description' => $validated['description'],
                'recommendation' => $validated['recommendation'] ?? null,
                'capa_action_id' => $validated['capa_action_id'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'status' => 'open',
            ]);

            $this->activity->log('audit', $audit->id, 'audit.finding_created', "Temuan {$findingNumber} dibuat", $actor);

            return $finding;
        });

        return back()->with('success', 'Temuan audit berhasil ditambahkan.');
    }

    public function updateFinding(UpdateAuditFindingRequest $request, Audit $audit, AuditFinding $finding): RedirectResponse
    {
        $actor = $request->user();
        abort_unless(auth()->user()->can('audit.findings.update') && in_array($audit->status, ['in_progress', 'report_ready']), 403);
        abort_unless($finding->audit_id === $audit->id, 404);

        $validated = $request->validated();

        $finding->update([
            'description' => $validated['description'],
            'classification' => $validated['classification'],
            'recommendation' => $validated['recommendation'] ?? null,
            'capa_action_id' => $validated['capa_action_id'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $this->activity->log('audit', $audit->id, 'audit.finding_updated', "Temuan {$finding->finding_number} diperbarui", $actor);

        return back()->with('success', 'Temuan berhasil diperbarui.');
    }

    public function closeFinding(Request $request, Audit $audit, AuditFinding $finding): RedirectResponse
    {
        $actor = $request->user();
        abort_unless(auth()->user()->can('audit.findings.close') && in_array($audit->status, ['in_progress', 'report_ready']), 403);
        abort_unless($finding->audit_id === $audit->id, 404);
        abort_if($finding->status === 'closed', 422, 'Temuan sudah ditutup.');

        $finding->update(['status' => 'closed', 'closed_date' => today(), 'closed_by' => $actor->id]);

        $this->activity->log('audit', $audit->id, 'audit.finding_closed', "Temuan {$finding->finding_number} ditutup", $actor);

        return back()->with('success', 'Temuan ditutup.');
    }

    public function comment(Request $request, Audit $audit, CommentService $comments): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureVisible($actor, $audit);
        abort_unless($actor->can('core.comments.create'), 403);

        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $comments->add('audit', $audit->id, $validated['body'], $actor);

        return back()->with('success', 'Komentar ditambahkan.');
    }

    public function uploadEvidence(Request $request, Audit $audit): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureVisible($actor, $audit);
        abort_unless($actor->can('core.files.upload'), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ]);

        $file = $this->files->store(
            $validated['file'],
            new FileReference('audit', $audit->id, 'evidence'),
            $actor,
        );

        $this->audit->log(
            'audit.file.uploaded',
            $audit,
            [],
            ['file_id' => $file->id, 'original_name' => $file->original_name],
            $actor,
            'audit',
            $audit->id,
        );
        $this->activity->log('audit', $audit->id, 'audit.file_uploaded', "Bukti {$file->original_name} diunggah", $actor);

        return back()->with('success', 'Bukti audit berhasil diunggah.');
    }

    public function downloadEvidence(Request $request, Audit $audit, ManagedFile $file): StreamedResponse
    {
        $actor = $request->user();
        $this->ensureVisible($actor, $audit);
        abort_unless($actor->can('core.files.download'), 403);
        abort_unless(
            $file->module_name === 'audit'
                && $file->reference_id === $audit->id
                && $file->deleted_at === null,
            404,
        );

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function export(Request $request, ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $query = $this->visibleQuery($request->user())->with(['department', 'leadAuditor', 'creator']);
        $this->applyFilters($query, $request);
        $listQuery->apply($query, ['audit_number', 'title'], ['created_at', 'scheduled_date', 'audit_number', 'title'], 'scheduled_date');
        $this->audit->log('audit.exported', actor: $request->user(), moduleName: 'audit', metadata: [
            'filters' => $request->only(['search', 'status', 'audit_type', 'department_id']),
        ]);

        return $exporter->stream($query, [
            'Nomor' => 'audit_number',
            'Judul' => 'title',
            'Jenis' => fn ($item) => self::AUDIT_TYPES[$item->audit_type] ?? $item->audit_type,
            'Status' => 'status',
            'Department' => fn ($item) => $item->department?->name ?? '',
            'Lead Auditor' => fn ($item) => $item->leadAuditor?->name ?? '',
            'Tanggal Jadwal' => fn ($item) => $item->scheduled_date?->format('Y-m-d') ?? '',
            'Tanggal Mulai' => fn ($item) => $item->start_date?->format('Y-m-d') ?? '',
            'Tanggal Laporan' => fn ($item) => $item->report_date?->format('Y-m-d') ?? '',
            'Tanggal Tutup' => fn ($item) => $item->close_date?->format('Y-m-d') ?? '',
        ], 'audits-export-' . now()->format('YmdHis') . '.csv');
    }

    private function visibleQuery(User $user): Builder
    {
        $query = Audit::query();

        if ($user->can('core.scope.all')) {
            return $query;
        }

        $employee = $user->employee;
        $canSeeOwn = $user->can('core.scope.own') || $user->can('audit.management.create');

        $query->where(function (Builder $builder) use ($user, $employee, $canSeeOwn): void {
            if ($canSeeOwn) {
                $builder->where('created_by', $user->id)
                    ->orWhere('lead_auditor_id', $user->id);
            }

            if ($user->can('core.scope.department') && $employee?->department_id) {
                $builder->orWhere('department_id', $employee->department_id);
            }

            if ($user->can('core.scope.site') && $employee?->site_id) {
                $builder->orWhereHas('department', fn (Builder $department) => $department->where('site_id', $employee->site_id));
            }
        });

        return $query;
    }

    private function ensureVisible(User $user, Audit $audit): void
    {
        abort_unless($this->visibleQuery($user)->whereKey($audit->id)->exists(), 403);
    }

    private function ensureAssignmentWithinScope(User $user, array $validated, ?Audit $audit = null): void
    {
        if ($user->can('core.scope.all')) {
            return;
        }

        $employee = $user->employee;
        $departmentId = $validated['department_id'] ?? $audit?->department_id;
        $leadAuditorId = (int) ($validated['lead_auditor_id'] ?? $audit?->lead_auditor_id);

        if ($leadAuditorId !== $user->id) {
            $leadAuditor = User::query()->with('employee')->find($leadAuditorId);
            abort_unless($leadAuditor, 403);

            if ($user->can('core.scope.department')) {
                abort_unless($employee?->department_id && $leadAuditor->employee?->department_id === $employee->department_id, 403);
            } elseif ($user->can('core.scope.site')) {
                abort_unless($employee?->site_id && $leadAuditor->employee?->site_id === $employee->site_id, 403);
            } else {
                abort(403);
            }
        }

        if ($departmentId === null) {
            return;
        }

        if ($user->can('core.scope.department')) {
            abort_unless($employee?->department_id === (int) $departmentId, 403);
        } elseif ($user->can('core.scope.site')) {
            abort_unless($employee?->site_id && Department::query()->whereKey($departmentId)->where('site_id', $employee->site_id)->exists(), 403);
        } else {
            abort(403);
        }
    }

    private function canUpdate(Audit $audit): bool
    {
        return auth()->user()->can('audit.management.update') && $audit->status === 'planned';
    }

    private function canExecute(Audit $audit): bool
    {
        return auth()->user()->can('audit.management.execute') && in_array($audit->status, ['planned', 'in_progress']);
    }

    private function canClose(Audit $audit): bool
    {
        return auth()->user()->can('audit.management.close') && $audit->status === 'report_ready';
    }

    private function canCreateFinding(Audit $audit): bool
    {
        return auth()->user()->can('audit.findings.create') && in_array($audit->status, ['in_progress', 'report_ready']);
    }

    private function formProps(?Audit $audit = null): array
    {
        $user = auth()->user();
        $employee = $user->employee;

        $departmentsQuery = Department::query()->where('is_active', true);

        if (! $user->can('core.scope.all')) {
            if ($user->can('core.scope.department') && $employee?->department_id) {
                $departmentsQuery->whereKey($employee->department_id);
            } elseif ($user->can('core.scope.site') && $employee?->site_id) {
                $departmentsQuery->where('site_id', $employee->site_id);
            }
        }

        $usersQuery = User::query()->where('is_active', true)->with('employee:id,user_id,department_id,site_id');

        if (! $user->can('core.scope.all')) {
            if ($user->can('core.scope.department') && $employee?->department_id) {
                $usersQuery->whereHas('employee', fn (Builder $q) => $q->where('department_id', $employee->department_id));
            } elseif ($user->can('core.scope.site') && $employee?->site_id) {
                $usersQuery->whereHas('employee', fn (Builder $q) => $q->where('site_id', $employee->site_id));
            }
        }

        return [
            'item' => $audit,
            'auditTypes' => self::AUDIT_TYPES,
            'departments' => $departmentsQuery->orderBy('name')->get(['id', 'name']),
            'users' => $usersQuery->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query->when($request->string('status')->toString(), fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($request->string('audit_type')->toString(), fn (Builder $builder, string $type) => $builder->where('audit_type', $type))
            ->when($request->integer('department_id'), fn (Builder $builder, int $departmentId) => $builder->where('department_id', $departmentId));
    }
}

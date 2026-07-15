<?php

namespace App\Http\Controllers\Modules\DocumentControl;

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
use App\Http\Requests\Modules\DocumentControl\StoreDocumentRequest;
use App\Http\Requests\Modules\DocumentControl\UpdateDocumentRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Comments\Comment;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\MasterData\Department;
use App\Models\Core\Users\Employee;
use App\Models\Core\Workflow\WorkflowHistory;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\Modules\DocumentControl\DocumentReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentControlController extends Controller
{
    private const TYPES = [
        'sop' => 'SOP',
        'wi' => 'Work Instruction',
        'jsa' => 'JSA',
        'hiradc' => 'HIRADC',
        'msds' => 'MSDS',
        'policy' => 'Policy',
        'form' => 'Form',
        'manual' => 'Manual',
        'other' => 'Lainnya',
    ];

    public function __construct(
        private readonly NumberingService $numbering,
        private readonly WorkflowService $workflow,
        private readonly ManagedFileService $files,
        private readonly NotificationService $notifications,
        private readonly ActivityService $activity,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request, ListQuery $listQuery): Response
    {
        $query = $this->visibleQuery($request->user())->with(['department:id,name', 'owner:id,name', 'approver:id,name']);
        $this->applyFilters($query, $request);

        $items = $listQuery->paginate(
            $query,
            ['document_number', 'title'],
            ['created_at', 'effective_date', 'review_date', 'document_number', 'title'],
            'created_at',
            15,
        );

        return Inertia::render('Modules/DocumentControl/Index', [
            'items' => $items,
            'filters' => $request->only(['search', 'type', 'status', 'department_id', 'sort', 'direction', 'per_page']),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'documentTypes' => $this->documentTypes(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Modules/DocumentControl/Form', $this->formProps());
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();
        $action = $validated['action'] ?? 'draft';

        abort_if($action === 'submit_review' && ! $actor->can('document.control.submit_review'), 403);
        $this->ensureAssignmentWithinScope($actor, $validated);

        if ($action === 'submit_review' && ! $request->hasFile('file')) {
            throw ValidationException::withMessages(['file' => 'File dokumen wajib diunggah sebelum submit review.']);
        }

        $document = DB::transaction(function () use ($request, $validated, $actor): ControlledDocument {
            $document = ControlledDocument::withoutEvents(fn () => ControlledDocument::query()->create([
                ...$this->documentPayload($validated, $actor),
                'document_number' => 'TEMP-'.uniqid(),
                'status' => 'draft',
            ]));

            $generated = $this->numbering->generate(
                moduleName: 'document',
                actor: $actor,
                referenceType: ControlledDocument::class,
                referenceId: $document->id,
            );
            ControlledDocument::withoutEvents(fn () => $document->update(['document_number' => $generated->number]));
            $this->audit->log('document.created', $document, [], $document->fresh()->getAttributes(), $actor, 'document', $document->id);
            $this->workflow->start('document', $document->id, $actor);

            if ($request->hasFile('file')) {
                $file = $this->files->store(
                    $request->file('file'),
                    new FileReference('document', $document->id, 'document_file'),
                    $actor,
                );
                $this->auditFile('document.file.uploaded', $document, $file, $actor);
            }

            $this->activity->log('document', $document->id, 'document.created', 'Dokumen terkontrol dibuat', $actor);

            if (($validated['action'] ?? 'draft') === 'submit_review') {
                $this->submitForReview($document, $actor, null);
            }

            return $document;
        });

        if ($action === 'submit_review') {
            $this->notifyManagers($document, 'document.submitted', $actor);
        }

        return redirect()->route('document.control.show', $document)->with('success', 'Dokumen berhasil dibuat.');
    }

    public function show(Request $request, ControlledDocument $controlledDocument): Response
    {
        $this->ensureVisible($request->user(), $controlledDocument);
        $controlledDocument->load(['department:id,name', 'owner:id,name,email', 'approver:id,name,email']);

        $workflowInstance = WorkflowInstance::query()
            ->where('module_name', 'document')
            ->where('reference_id', $controlledDocument->id)
            ->first();

        return Inertia::render('Modules/DocumentControl/Show', [
            'document' => $controlledDocument,
            'files' => ManagedFile::query()
                ->where('module_name', 'document')
                ->where('reference_id', $controlledDocument->id)
                ->where('collection', 'document_file')
                ->active()
                ->latest()
                ->get(),
            'reviews' => $controlledDocument->reviews()->with('reviewer:id,name')->latest()->get(),
            'comments' => Comment::query()->where('module_name', 'document')->where('reference_id', $controlledDocument->id)->active()->with('author:id,name')->oldest()->get(),
            'activities' => ActivityLog::query()->where('module_name', 'document')->where('reference_id', $controlledDocument->id)->latest()->get(),
            'workflowHistory' => WorkflowHistory::query()->where('module_name', 'document')->where('reference_id', $controlledDocument->id)->oldest()->get(),
            'availableTransitions' => $workflowInstance ? $this->workflow->availableTransitions($workflowInstance) : [],
            'can' => $this->abilities($request->user(), $controlledDocument),
        ]);
    }

    public function edit(Request $request, ControlledDocument $controlledDocument): Response
    {
        $this->ensureMutable($request->user(), $controlledDocument);
        abort_unless(in_array($controlledDocument->status, ['draft', 'rejected'], true), 403);

        return Inertia::render('Modules/DocumentControl/Form', $this->formProps($controlledDocument));
    }

    public function update(UpdateDocumentRequest $request, ControlledDocument $controlledDocument): RedirectResponse
    {
        $this->ensureMutable($request->user(), $controlledDocument);
        abort_unless(in_array($controlledDocument->status, ['draft', 'rejected'], true), 403);
        $actor = $request->user();
        $validated = $request->validated();
        $this->ensureAssignmentWithinScope($actor, $validated, $controlledDocument);
        DB::transaction(function () use ($request, $controlledDocument, $validated, $actor): void {
            $oldValues = $controlledDocument->getOriginal();
            $controlledDocument->update($this->documentPayload($validated, $actor, $controlledDocument));
            $this->audit->log('document.updated', $controlledDocument, $oldValues, $controlledDocument->getChanges(), $actor, 'document', $controlledDocument->id);
            if ($request->hasFile('file')) {
                $file = $this->files->store(
                    $request->file('file'),
                    new FileReference('document', $controlledDocument->id, 'document_file'),
                    $actor,
                );
                $this->auditFile('document.file.uploaded', $controlledDocument, $file, $actor);
            }
            $this->activity->log('document', $controlledDocument->id, 'document.updated', 'Dokumen diperbarui', $actor);
        });

        return redirect()->route('document.control.show', $controlledDocument)->with('success', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(Request $request, ControlledDocument $controlledDocument): RedirectResponse
    {
        $this->authorize('delete', $controlledDocument);
        abort_unless(in_array($controlledDocument->status, ['draft', 'rejected'], true), 403);

        $actor = $request->user();
        DB::transaction(function () use ($controlledDocument, $actor) {
            $this->audit->deleted($controlledDocument, $actor, 'document', $controlledDocument->id);
            $this->activity->log('document', $controlledDocument->id, 'document.deleted', 'Dokumen dihapus', $actor);
            $controlledDocument->delete();
        });

        return redirect()->route('document.control.index')->with('success', 'Dokumen berhasil dihapus.');
    }

    public function submitReview(Request $request, ControlledDocument $controlledDocument): RedirectResponse
    {
        $this->ensureMutable($request->user(), $controlledDocument);
        $request->validate(['review_notes' => ['nullable', 'string', 'max:2000']]);
        abort_unless(in_array($controlledDocument->status, ['draft'], true), 422);

        validator($controlledDocument->only(['title', 'type', 'version', 'effective_date', 'review_date', 'expiry_date', 'owner_id']), [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'version' => ['required', 'string', 'max:20'],
            'effective_date' => ['required', 'date'],
            'review_date' => ['nullable', 'date', 'after_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:review_date'],
            'owner_id' => ['required', 'exists:users,id'],
        ])->validate();

        if (! $this->hasDocumentFile($controlledDocument)) {
            return back()->withErrors(['file' => 'File dokumen wajib diunggah sebelum submit review.']);
        }

        try {
            DB::transaction(fn () => $this->submitForReview($controlledDocument, $request->user(), $request->string('review_notes')->toString() ?: null));
            $this->notifyManagers($controlledDocument->fresh(), 'document.submitted', $request->user());
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return back()->with('success', 'Dokumen dikirim untuk review.');
    }

    public function approve(Request $request, ControlledDocument $controlledDocument): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureMutable($actor, $controlledDocument);
        $validated = $request->validate(['review_notes' => ['nullable', 'string', 'max:2000']]);

        try {
            DB::transaction(function () use ($controlledDocument, $actor, $validated): void {
                $this->workflow->transition('document', $controlledDocument->id, 'approve', $actor);
                $review = $this->pendingReview($controlledDocument);
                $review->update([
                    'reviewer_id' => $actor->id,
                    'review_date' => today(),
                    'review_notes' => $validated['review_notes'] ?? null,
                    'decision' => 'approve',
                ]);
                $controlledDocument->update(['status' => 'approved', 'approver_id' => $actor->id]);
                $this->auditTransition('document.approved', $controlledDocument, 'review', 'approved', $actor);
                $this->activity->log('document', $controlledDocument->id, 'document.approved', 'Dokumen disetujui', $actor);
            });
            $this->notifyOwner($controlledDocument->fresh(), 'document.approved', $actor);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return back()->with('success', 'Dokumen disetujui.');
    }

    public function makeEffective(Request $request, ControlledDocument $controlledDocument): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureMutable($actor, $controlledDocument);
        $validated = $request->validate(['effective_date' => ['nullable', 'date']]);

        try {
            DB::transaction(function () use ($controlledDocument, $actor, $validated): void {
                $this->workflow->transition('document', $controlledDocument->id, 'make_effective', $actor);
                $controlledDocument->update([
                    'status' => 'effective',
                    'effective_date' => $validated['effective_date'] ?? today(),
                ]);
                $this->auditTransition('document.effective', $controlledDocument, 'approved', 'effective', $actor, [
                    'effective_date' => $controlledDocument->effective_date?->toDateString(),
                ]);
                $this->activity->log('document', $controlledDocument->id, 'document.effective', 'Dokumen mulai berlaku efektif', $actor);
            });
            $this->notifyStakeholders($controlledDocument->fresh(), 'document.effective', $actor);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return back()->with('success', 'Dokumen sekarang berlaku efektif.');
    }

    public function obsolete(Request $request, ControlledDocument $controlledDocument): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);

        return $this->reasonedTransition($controlledDocument, $request->user(), 'obsolete', 'obsolete', $validated['reason'], 'Dokumen ditandai obsolete.');
    }

    public function reject(Request $request, ControlledDocument $controlledDocument): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureMutable($actor, $controlledDocument);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);

        try {
            DB::transaction(function () use ($controlledDocument, $actor, $validated): void {
                $this->workflow->transition('document', $controlledDocument->id, 'reject', $actor, $validated['reason']);
                $this->pendingReview($controlledDocument)->update([
                    'reviewer_id' => $actor->id,
                    'review_date' => today(),
                    'review_notes' => $validated['reason'],
                    'decision' => 'reject',
                ]);
                $controlledDocument->update(['status' => 'rejected']);
                $this->auditTransition('document.rejected', $controlledDocument, 'review', 'rejected', $actor, ['reason' => $validated['reason']]);
                $this->activity->log('document', $controlledDocument->id, 'document.rejected', 'Dokumen ditolak', $actor, ['reason' => $validated['reason']]);
            });
            $this->notifyOwner($controlledDocument->fresh(), 'document.rejected', $actor, $validated['reason']);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return back()->with('success', 'Dokumen ditolak untuk direvisi.');
    }

    public function revise(Request $request, ControlledDocument $controlledDocument): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureMutable($actor, $controlledDocument);

        try {
            DB::transaction(function () use ($controlledDocument, $actor): void {
                $this->workflow->transition('document', $controlledDocument->id, 'revise', $actor);
                $controlledDocument->reviews()->latest('id')->firstOrFail()->update(['decision' => 'revise']);
                $controlledDocument->update(['status' => 'draft']);
                $this->auditTransition('document.revised', $controlledDocument, 'rejected', 'draft', $actor);
                $this->activity->log('document', $controlledDocument->id, 'document.revised', 'Dokumen dikembalikan ke draft untuk revisi', $actor);
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return back()->with('success', 'Dokumen siap direvisi.');
    }

    public function download(Request $request, ControlledDocument $controlledDocument, ManagedFile $file): StreamedResponse
    {
        $this->ensureVisible($request->user(), $controlledDocument);
        abort_unless(
            $file->module_name === 'document'
            && $file->reference_id === $controlledDocument->id
            && $file->collection === 'document_file'
            && $file->deleted_at === null,
            404,
        );

        if ($controlledDocument->is_confidential) {
            abort_unless($this->canDownloadConfidential($request->user(), $controlledDocument), 403);
        }

        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);
        $this->activity->log('document', $controlledDocument->id, 'document.downloaded', 'File dokumen diunduh', $request->user(), ['file_id' => $file->id]);
        $this->auditFile('document.file.downloaded', $controlledDocument, $file, $request->user());

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function comment(Request $request, ControlledDocument $controlledDocument, CommentService $comments): RedirectResponse
    {
        $this->ensureVisible($request->user(), $controlledDocument);
        abort_unless($request->user()->can('core.comments.create'), 403);
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $comments->add('document', $controlledDocument->id, $validated['body'], $request->user());

        return back()->with('success', 'Komentar ditambahkan.');
    }

    public function export(Request $request, ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $query = $this->visibleQuery($request->user())->with(['department', 'owner', 'approver']);
        $this->applyFilters($query, $request);
        $listQuery->apply($query, ['document_number', 'title'], ['created_at', 'effective_date', 'document_number', 'title'], 'created_at');
        $this->audit->log('document.exported', actor: $request->user(), moduleName: 'document', metadata: [
            'filters' => $request->only(['search', 'type', 'status', 'department_id']),
        ]);

        return $exporter->stream($query, [
            'Nomor' => 'document_number',
            'Judul' => 'title',
            'Tipe' => 'type',
            'Versi' => 'version',
            'Status' => 'status',
            'Department' => fn ($item) => $item->department?->name ?? '',
            'Owner' => fn ($item) => $item->owner?->name ?? '',
            'Approver' => fn ($item) => $item->approver?->name ?? '',
            'Tanggal Efektif' => fn ($item) => $item->effective_date?->format('Y-m-d') ?? '',
            'Tanggal Review' => fn ($item) => $item->review_date?->format('Y-m-d') ?? '',
            'Kedaluwarsa' => fn ($item) => $item->expiry_date?->format('Y-m-d') ?? '',
            'Rahasia' => fn ($item) => $item->is_confidential ? 'Ya' : 'Tidak',
        ], 'documents-export.csv');
    }

    private function submitForReview(ControlledDocument $document, User $actor, ?string $notes): void
    {
        $this->workflow->transition('document', $document->id, 'submit_review', $actor);
        $document->update(['status' => 'review']);
        DocumentReview::query()->create([
            'document_id' => $document->id,
            'review_notes' => $notes,
            'decision' => 'pending',
        ]);
        $this->auditTransition('document.submitted', $document, 'draft', 'review', $actor, ['review_notes' => $notes]);
        $this->activity->log('document', $document->id, 'document.submitted', 'Dokumen dikirim untuk review', $actor);
    }

    private function reasonedTransition(ControlledDocument $document, User $actor, string $action, string $status, string $reason, string $message): RedirectResponse
    {
        $this->ensureMutable($actor, $document);

        try {
            DB::transaction(function () use ($document, $actor, $action, $status, $reason): void {
                $oldStatus = $document->status;
                $this->workflow->transition('document', $document->id, $action, $actor, $reason);
                $document->update(['status' => $status]);
                $this->auditTransition("document.{$action}", $document, $oldStatus, $status, $actor, ['reason' => $reason]);
                $this->activity->log('document', $document->id, "document.{$action}", "Dokumen {$status}", $actor, ['reason' => $reason]);
            });
            $this->notifyStakeholders($document->fresh(), "document.{$action}", $actor, $reason, includeOfficers: $action === 'obsolete');
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return back()->with('success', $message);
    }

    private function pendingReview(ControlledDocument $document): DocumentReview
    {
        $review = $document->reviews()->where('decision', 'pending')->latest('id')->first();
        if (! $review) {
            throw new \RuntimeException('Pending review record tidak ditemukan.');
        }

        return $review;
    }

    private function notifyOwner(ControlledDocument $document, string $type, User $actor, ?string $reason = null): void
    {
        $owner = $document->owner()->first();
        if (! $owner) {
            return;
        }

        $this->notifications->notify(
            $owner,
            $type,
            $this->notificationContext($document, $actor) + ['reason' => $reason],
            $actor,
            'document',
            $document->id,
            route('document.control.show', $document, false),
        );
    }

    private function notifyManagers(ControlledDocument $document, string $type, User $actor): void
    {
        $this->notifications->notifyMany(
            $this->usersWithRole('QHSSE Manager'),
            $type,
            $this->notificationContext($document, $actor),
            $actor,
            'document',
            $document->id,
            route('document.control.show', $document, false),
        );
    }

    private function notifyStakeholders(ControlledDocument $document, string $type, User $actor, ?string $reason = null, bool $includeOfficers = false): void
    {
        $recipients = collect([$document->owner()->first()])->filter();

        if ($document->department_id !== null) {
            $employeeIds = Employee::query()->where('department_id', $document->department_id)->pluck('id');
            $recipients = $recipients->merge(User::query()->where('is_active', true)->whereIn('employee_id', $employeeIds)->get());
        }

        if ($includeOfficers) {
            $recipients = $recipients->merge($this->usersWithRole('QHSSE Officer'));
        }

        $this->notifications->notifyMany(
            $recipients->unique('id')->values(),
            $type,
            $this->notificationContext($document, $actor) + ['reason' => $reason],
            $actor,
            'document',
            $document->id,
            route('document.control.show', $document, false),
        );
    }

    private function auditTransition(string $event, ControlledDocument $document, string $oldStatus, string $newStatus, User $actor, array $metadata = []): void
    {
        $this->audit->log(
            $event,
            $document,
            ['status' => $oldStatus],
            ['status' => $newStatus] + $metadata,
            $actor,
            'document',
            $document->id,
            $metadata,
        );
    }

    private function auditFile(string $event, ControlledDocument $document, ManagedFile $file, User $actor): void
    {
        $this->audit->log(
            $event,
            $file,
            [],
            $file->getAttributes(),
            $actor,
            'document',
            $document->id,
            ['file_id' => $file->id],
        );
    }

    private function notificationContext(ControlledDocument $document, User $actor): array
    {
        return [
            'document_number' => $document->document_number,
            'title' => $document->title,
            'actor_name' => $actor->name,
        ];
    }

    private function usersWithRole(string $roleName)
    {
        $role = Role::query()->where('name', $roleName)->first();

        return $role ? $role->users()->where('is_active', true)->get() : collect();
    }

    private function documentPayload(array $validated, User $actor, ?ControlledDocument $document = null): array
    {
        return [
            'title' => $validated['title'] ?? $document?->title,
            'type' => $validated['type'] ?? $document?->type,
            'version' => $validated['version'] ?? $document?->version,
            'revision_notes' => $validated['revision_notes'] ?? $document?->revision_notes,
            'effective_date' => $validated['effective_date'] ?? $document?->effective_date,
            'review_date' => $validated['review_date'] ?? $document?->review_date,
            'expiry_date' => $validated['expiry_date'] ?? $document?->expiry_date,
            'department_id' => $validated['department_id'] ?? $document?->department_id,
            'owner_id' => $validated['owner_id'] ?? $document?->owner_id ?? $actor->id,
            'is_confidential' => $validated['is_confidential'] ?? $document?->is_confidential ?? false,
        ];
    }

    private function formProps(?ControlledDocument $document = null): array
    {
        return [
            'item' => $document,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'documentTypes' => $this->documentTypes(),
        ];
    }

    private function documentTypes(): array
    {
        return collect(self::TYPES)->map(fn (string $label, string $value) => compact('value', 'label'))->values()->all();
    }

    private function visibleQuery(User $user): Builder
    {
        $query = ControlledDocument::query();
        if ($user->can('core.scope.all')) {
            return $query;
        }

        $employee = $user->employee;
        $canSeeOwnNonEffective = $user->can('core.scope.own')
            || $user->can('document.control.create')
            || $user->can('document.control.update')
            || $user->can('document.control.submit_review');

        $query->where(function (Builder $builder) use ($user, $employee, $canSeeOwnNonEffective): void {
            $builder->where('status', 'effective');

            if ($canSeeOwnNonEffective) {
                $builder->orWhere('owner_id', $user->id);
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

    private function ensureVisible(User $user, ControlledDocument $document): void
    {
        abort_unless($this->visibleQuery($user)->whereKey($document->id)->exists(), 403);
    }

    private function ensureMutable(User $user, ControlledDocument $document): void
    {
        if ($user->can('core.scope.all') || $document->owner_id === $user->id) {
            return;
        }

        $employee = $user->employee;
        $allowed = ($user->can('core.scope.department') && $employee?->department_id === $document->department_id)
            || ($user->can('core.scope.site') && $employee?->site_id !== null
                && $document->department()->where('site_id', $employee->site_id)->exists());

        abort_unless($allowed, 403);
    }

    private function ensureAssignmentWithinScope(User $user, array $validated, ?ControlledDocument $document = null): void
    {
        if ($user->can('core.scope.all')) {
            return;
        }

        $employee = $user->employee;
        $departmentId = $validated['department_id'] ?? $document?->department_id;
        $ownerId = (int) ($validated['owner_id'] ?? $document?->owner_id ?? $user->id);

        if ($ownerId !== $user->id) {
            $owner = User::query()->with('employee')->find($ownerId);
            abort_unless($owner, 403);

            if ($user->can('core.scope.department')) {
                abort_unless($employee?->department_id && $owner->employee?->department_id === $employee->department_id, 403);
            } elseif ($user->can('core.scope.site')) {
                abort_unless($employee?->site_id && $owner->employee?->site_id === $employee->site_id, 403);
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

    private function canDownloadConfidential(User $user, ControlledDocument $document): bool
    {
        return in_array($user->id, array_filter([$document->owner_id, $document->approver_id]), true)
            || $user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager']);
    }

    private function hasDocumentFile(ControlledDocument $document): bool
    {
        return ManagedFile::query()
            ->where('module_name', 'document')
            ->where('reference_id', $document->id)
            ->where('collection', 'document_file')
            ->active()
            ->exists();
    }

    private function abilities(User $user, ControlledDocument $document): array
    {
        return [
            'update' => $user->can('document.control.update') && in_array($document->status, ['draft', 'rejected'], true),
            'submit_review' => $user->can('document.control.submit_review') && $document->status === 'draft',
            'approve' => $user->can('document.control.approve') && $document->status === 'review',
            'make_effective' => $user->can('document.control.make_effective') && $document->status === 'approved',
            'obsolete' => $user->can('document.control.obsolete') && $document->status === 'effective',
            'revise' => $user->can('document.control.update') && $document->status === 'rejected',
            'comment' => $user->can('core.comments.create'),
            'download_file' => ! $document->is_confidential || $this->canDownloadConfidential($user, $document),
        ];
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query->when($request->string('type')->toString(), fn (Builder $builder, string $type) => $builder->where('type', $type))
            ->when($request->string('status')->toString(), fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($request->integer('department_id'), fn (Builder $builder, int $departmentId) => $builder->where('department_id', $departmentId));
    }
}

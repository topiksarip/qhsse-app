<?php

namespace App\Http\Controllers\Modules\Quality;

use App\Core\{Activity\ActivityService, Audit\AuditService, Export\CsvExporter, Notifications\NotificationService, Numbering\NumberingService, Query\ListQuery, Workflow\WorkflowService};
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Quality\{StoreNcrRequest, UpdateNcrRequest};
use App\Models\Core\MasterData\{Department, Severity, Site};
use App\Models\Modules\Quality\Ncr;
use App\Models\User;
use App\Modules\Quality\NcrAccess;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\{Inertia, Response as InertiaResponse};

class NcrController extends Controller
{
    public function __construct(
        protected NumberingService $numberingService,
        protected AuditService $auditService,
        protected ActivityService $activityService,
        protected WorkflowService $workflowService,
        protected NotificationService $notificationService,
        protected NcrAccess $access,
    ) {
        $this->authorizeResource(Ncr::class, 'ncr');
    }

    public function index(Request $request): InertiaResponse
    {
        $query = $this->access->scope(Auth::user(), Ncr::query()->with(['site', 'department', 'severity'])->select('ncrs.*'));

        if ($request->filled('site_id')) {
            $query->where('ncrs.site_id', $request->input('site_id'));
        }
        if ($request->filled('source')) {
            $query->where('ncrs.source', $request->input('source'));
        }
        if ($request->filled('status')) {
            $query->where('ncrs.status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($q) => $q->where('ncrs.ncr_number', 'like', "%{$search}%")->orWhere('ncrs.title', 'like', "%{$search}%"));
        }

        $ncrs = ListQuery::for($query)->defaultSort('-created_at')->paginate($request->input('per_page', 15))->withQueryString();

        return Inertia::render('Modules/Quality/Ncrs/Index', [
            'ncrs' => $ncrs,
            'filters' => $request->only(['site_id', 'source', 'status', 'search']),
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'sources' => Ncr::getSources(),
            'statuses' => Ncr::getStatuses(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Modules/Quality/Ncrs/Form', [
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'severities' => Severity::select('id', 'name', 'level')->orderBy('level')->get(),
            'sources' => Ncr::getSources(),
        ]);
    }

    public function store(StoreNcrRequest $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $ncrNumber = $this->numberingService->generate('quality', $user);

        $ncr = DB::transaction(function () use ($request, $ncrNumber, $user) {
            $data = $request->validated();
            $data['ncr_number'] = $ncrNumber;
            $data['status'] = 'open';

            $ncr = Ncr::create($data);

            $this->workflowService->start('quality', $ncr->id, $user);
            $this->auditService->created($ncr, $user, 'quality', $ncr->id);
            $this->activityService->log('quality', $ncr->id, 'created', "NCR {$ncr->ncr_number} created by {$user->name}", $user->id);

            return $ncr;
        });

        $this->notifyQhsseTeam($user, $ncr, 'quality.ncr.submitted', 'NCR baru diajukan: ' . $ncr->ncr_number);

        return redirect()->route('quality.ncrs.show', $ncr)->with('success', "NCR {$ncr->ncr_number} created successfully");
    }

    public function show(Ncr $ncr): InertiaResponse
    {
        $this->access->canAccess(Auth::user(), $ncr) || abort(403);

        $ncr->load(['site', 'department', 'severity', 'capaAction']);

        $workflow = $this->workflowService->getWorkflow('quality', $ncr->id);

        return Inertia::render('Modules/Quality/Ncrs/Show', [
            'ncr' => $ncr,
            'availableTransitions' => $workflow['available_transitions'],
        ]);
    }

    public function edit(Ncr $ncr): InertiaResponse
    {
        $this->access->canAccess(Auth::user(), $ncr) || abort(403);

        return Inertia::render('Modules/Quality/Ncrs/Form', [
            'ncr' => $ncr,
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'severities' => Severity::select('id', 'name', 'level')->orderBy('level')->get(),
            'sources' => Ncr::getSources(),
            'statuses' => Ncr::getStatuses(),
        ]);
    }

    public function update(UpdateNcrRequest $request, Ncr $ncr): \Illuminate\Http\RedirectResponse
    {
        $this->access->canAccess(Auth::user(), $ncr) || abort(403);

        abort_if($ncr->status === 'closed', 422, 'NCR sudah ditutup dan tidak dapat diubah.');

        $user = $request->user();

        DB::transaction(function () use ($request, $ncr, $user) {
            $ncr->update($request->validated());
            $this->auditService->updated($ncr, $ncr->getOriginal(), $user, 'quality', $ncr->id);
            $this->activityService->log('quality', $ncr->id, 'updated', "NCR {$ncr->ncr_number} updated", $user->id);
        });

        return redirect()->route('quality.ncrs.show', $ncr)->with('success', 'NCR updated successfully');
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('export', Ncr::class);

        $query = $this->access->scope(Auth::user(), Ncr::query()->with(['site', 'severity']));

        if ($request->filled('site_id')) {
            $query->where('ncrs.site_id', $request->input('site_id'));
        }

        $ncrs = $query->orderBy('created_at', 'desc')->get();

        return CsvExporter::export(
            data: $ncrs,
            filename: 'ncrs_' . now()->format('Y-m-d_His') . '.csv',
            columns: ['ncr_number' => 'NCR Number', 'source' => 'Source', 'title' => 'Title', 'site.name' => 'Site', 'severity.name' => 'Severity', 'status' => 'Status', 'created_at' => 'Created At'],
        );
    }

    public function transition(Request $request, Ncr $ncr, string $action): \Illuminate\Http\RedirectResponse
    {
        $this->access->canAccess(Auth::user(), $ncr) || abort(403);

        $user = $request->user();
        $reason = $request->input('reason');

        $validated = $request->validate([
            'reason' => in_array($action, ['close', 'reject'], true) ? 'required|min:10' : 'nullable|string',
        ]);

        DB::transaction(function () use ($user, $ncr, $action, $validated) {
            $old = $ncr->getOriginal();
            $instance = $this->workflowService->transition('quality', $ncr->id, $action, $user, $validated['reason'] ?? null);
            $ncr->refresh();
            $ncr->update(['status' => $instance->current_status]);

            if ($action === 'close') {
                abort_unless(
                    $ncr->root_cause && $ncr->corrective_action && $ncr->preventive_action,
                    422,
                    'Close NCR wajib mengisi root cause, corrective action, dan preventive action.'
                );
                $ncr->update(['closed_at' => now()]);
            }

            $this->auditService->log("transition.{$action}", $ncr, [], [], $user, 'quality', $ncr->id);
            $this->activityService->log('quality', $ncr->id, $action, "NCR {$ncr->ncr_number} transition: {$action}", $user->id);
        });

        $this->notifyQhsseTeam($user, $ncr, 'quality.ncr.' . $action, "NCR {$ncr->ncr_number} {$action}");

        return redirect()->route('quality.ncrs.show', $ncr)->with('success', "NCR {$action} berhasil.");
    }

    public function destroy(Request $request, Ncr $ncr): \Illuminate\Http\RedirectResponse
    {
        $this->access->canAccess(Auth::user(), $ncr) || abort(403);
        $this->authorize('delete', $ncr);

        $user = $request->user();
        $ncrNumber = $ncr->ncr_number;

        DB::transaction(function () use ($ncr, $user) {
            $this->auditService->deleted($ncr, $user, 'quality', $ncr->id);
            $this->activityService->log('quality', $ncr->id, 'deleted', "NCR {$ncr->ncr_number} deleted", $user->id);
            $ncr->delete();
        });

        return redirect()->route('quality.ncrs.index')->with('success', "NCR {$ncrNumber} berhasil dihapus.");
    }

    protected function notifyQhsseTeam(User $actor, Ncr $ncr, string $type, string $message): void
    {
        $recipients = \App\Models\User::query()
            ->whereHas('roles.permissions', fn ($q) => $q->where('name', 'quality.ncrs.view'))
            ->where(function ($q) use ($ncr) {
                $q->whereHas('permissions', fn ($p) => $p->where('name', 'core.scope.all'))
                    ->orWhereHas('employee', fn ($e) => $e->where('site_id', $ncr->site_id));
            })
            ->pluck('id')
            ->all();

        if ($recipients) {
            $this->notificationService->notifyMany($recipients, $type, [
                'message' => $message,
                'module' => 'quality',
                'reference_id' => $ncr->id,
            ], $actor->id, 'quality', $ncr->id, route('quality.ncrs.show', $ncr));
        }
    }
}

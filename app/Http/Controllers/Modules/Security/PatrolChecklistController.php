<?php

namespace App\Http\Controllers\Modules\Security;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Security\StorePatrolChecklistRequest;
use App\Http\Requests\Modules\Security\UpdatePatrolChecklistRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Security\PatrolChecklist;
use App\Models\Modules\Security\PatrolResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PatrolChecklistController extends Controller
{
    public function __construct(
        private NumberingService $numbering,
        private AuditService $audit,
        private ActivityService $activity,
        private NotificationService $notifications,
        private CsvExporter $csv,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PatrolChecklist::class);

        $query = PatrolChecklist::query()
            ->with(['site:id,name', 'area:id,name', 'assignedTo:id,name'])
            ->withCount([
                'results',
                'results as issue_count' => fn (Builder $query) => $query->where('result', 'issue'),
                'results as pending_count' => fn (Builder $query) => $query->whereNull('result'),
            ]);

        $this->applyScope($query, $request->user());

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn (Builder $item) => $item
                ->where('patrol_number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%"));
        }
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->integer('site_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_at', '<=', $request->date('date_to'));
        }

        return Inertia::render('Modules/Security/Patrols/Index', [
            'patrols' => $query->latest('scheduled_at')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'site_id', 'status', 'date_from', 'date_to']),
            'sites' => Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => PatrolChecklist::getStatuses(),
            'can' => [
                'create' => $request->user()->can('create', PatrolChecklist::class),
                'export' => $request->user()->can('export', PatrolChecklist::class),
                'delete' => $request->user()->can('delete', PatrolChecklist::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', PatrolChecklist::class);

        return Inertia::render('Modules/Security/Patrols/Form', $this->formOptions($request));
    }

    public function store(StorePatrolChecklistRequest $request): RedirectResponse
    {
        $patrol = DB::transaction(function () use ($request) {
            $generated = $this->numbering->generate('security_patrol', $request->user());
            $data = $request->safe()->except('checkpoints');
            $data['patrol_number'] = $generated->number;
            $data['status'] = 'scheduled';

            $patrol = PatrolChecklist::create($data);
            $patrol->results()->createMany(collect($request->validated('checkpoints'))
                ->map(fn (array $item) => ['checkpoint' => $item['checkpoint'], 'result' => null])
                ->all());
            $generated->update(['reference_type' => PatrolChecklist::class, 'reference_id' => $patrol->id]);

            $this->audit->log('security.patrol.created', $patrol, [], $patrol->getAttributes(), $request->user(), 'security_patrol', $patrol->id);
            $this->activity->log('security_patrol', $patrol->id, 'created', "Jadwal patroli {$patrol->patrol_number} dibuat.", $request->user());

            return $patrol;
        });

        return redirect()->route('security.patrols.show', $patrol)
            ->with('success', "Patroli {$patrol->patrol_number} berhasil dijadwalkan.");
    }

    public function show(Request $request, PatrolChecklist $patrol): Response
    {
        $this->authorize('view', $patrol);
        $patrol->load(['site:id,name', 'area:id,name', 'assignedTo:id,name,email', 'completedBy:id,name', 'results']);

        return Inertia::render('Modules/Security/Patrols/Show', [
            'patrol' => $patrol,
            'activities' => ActivityLog::query()
                ->where('module_name', 'security_patrol')
                ->where('reference_id', $patrol->id)
                ->latest()->get(),
            'resultOptions' => PatrolResult::getResults(),
            'can' => [
                'update' => $request->user()->can('update', $patrol),
                'execute' => $request->user()->can('execute', $patrol),
            ],
        ]);
    }

    public function edit(Request $request, PatrolChecklist $patrol): Response
    {
        $this->authorize('update', $patrol);
        $patrol->load('results:id,patrol_checklist_id,checkpoint');

        return Inertia::render('Modules/Security/Patrols/Form', [
            ...$this->formOptions($request),
            'patrol' => $patrol,
        ]);
    }

    public function update(UpdatePatrolChecklistRequest $request, PatrolChecklist $patrol): RedirectResponse
    {
        DB::transaction(function () use ($request, $patrol) {
            $old = [...$patrol->getAttributes(), 'checkpoints' => $patrol->results()->pluck('checkpoint')->all()];
            $patrol->update($request->safe()->except('checkpoints'));
            $patrol->results()->delete();
            $patrol->results()->createMany(collect($request->validated('checkpoints'))
                ->map(fn (array $item) => ['checkpoint' => $item['checkpoint'], 'result' => null])
                ->all());
            $new = [...$patrol->fresh()->getAttributes(), 'checkpoints' => $patrol->results()->pluck('checkpoint')->all()];
            $this->audit->log('security.patrol.updated', $patrol, $old, $new, $request->user(), 'security_patrol', $patrol->id);
            $this->activity->log('security_patrol', $patrol->id, 'updated', "Jadwal patroli {$patrol->patrol_number} diperbarui.", $request->user());
        });

        return redirect()->route('security.patrols.show', $patrol)->with('success', 'Jadwal patroli diperbarui.');
    }

    public function start(Request $request, PatrolChecklist $patrol): RedirectResponse
    {
        $this->authorize('execute', $patrol);
        if (! $patrol->canBeStarted()) {
            throw ValidationException::withMessages(['status' => 'Hanya patroli terjadwal yang dapat dimulai.']);
        }

        DB::transaction(function () use ($request, $patrol) {
            $lockedPatrol = PatrolChecklist::query()->lockForUpdate()->findOrFail($patrol->id);
            if (! $lockedPatrol->canBeStarted()) {
                throw ValidationException::withMessages(['status' => 'Hanya patroli terjadwal yang dapat dimulai.']);
            }
            $lockedPatrol->update(['status' => 'in_progress', 'started_at' => now()]);
            $this->audit->workflow('security.patrol.executed', 'security_patrol', $lockedPatrol->id, ['status' => 'scheduled'], ['status' => 'in_progress', 'started_at' => $lockedPatrol->started_at], $request->user());
            $this->activity->log('security_patrol', $lockedPatrol->id, 'started', "Patroli {$lockedPatrol->patrol_number} dimulai.", $request->user());
            $this->notifySiteTeam($lockedPatrol, 'security.patrol.executed', [
                'patrol_number' => $lockedPatrol->patrol_number,
                'actor_name' => $request->user()->name,
                'site_name' => $lockedPatrol->site->name,
            ], $request->user());
        });

        return back()->with('success', 'Eksekusi patroli dimulai.');
    }

    public function complete(Request $request, PatrolChecklist $patrol): RedirectResponse
    {
        $this->authorize('execute', $patrol);
        if (! $patrol->canBeCompleted()) {
            throw ValidationException::withMessages(['status' => 'Patroli harus berstatus In Progress.']);
        }
        if ($patrol->results()->doesntExist() || $patrol->results()->whereNull('result')->exists()) {
            throw ValidationException::withMessages(['results' => 'Semua checkpoint wajib diisi sebelum patroli diselesaikan.']);
        }

        DB::transaction(function () use ($request, $patrol) {
            $lockedPatrol = PatrolChecklist::query()->lockForUpdate()->findOrFail($patrol->id);
            if (! $lockedPatrol->canBeCompleted()) {
                throw ValidationException::withMessages(['status' => 'Patroli harus berstatus In Progress.']);
            }
            if ($lockedPatrol->results()->doesntExist() || $lockedPatrol->results()->whereNull('result')->exists()) {
                throw ValidationException::withMessages(['results' => 'Semua checkpoint wajib diisi sebelum patroli diselesaikan.']);
            }
            $lockedPatrol->update(['status' => 'completed', 'completed_at' => now(), 'completed_by' => $request->user()->id]);
            $this->audit->workflow('security.patrol.completed', 'security_patrol', $lockedPatrol->id, ['status' => 'in_progress'], ['status' => 'completed', 'completed_at' => $lockedPatrol->completed_at], $request->user());
            $this->activity->log('security_patrol', $lockedPatrol->id, 'completed', "Patroli {$lockedPatrol->patrol_number} diselesaikan.", $request->user(), ['issue_count' => $lockedPatrol->results()->where('result', 'issue')->count()]);
        });

        return back()->with('success', 'Patroli berhasil diselesaikan.');
    }

    public function export(Request $request)
    {
        $this->authorize('export', PatrolChecklist::class);
        $query = PatrolChecklist::query()->with(['site', 'area', 'assignedTo'])->withCount(['results', 'results as issue_count' => fn (Builder $q) => $q->where('result', 'issue')]);
        $this->applyScope($query, $request->user());
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn (Builder $item) => $item->where('patrol_number', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"));
        }
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->integer('site_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_at', '<=', $request->date('date_to'));
        }

        return $this->csv->stream($query->latest('scheduled_at'), [
            'Nomor' => 'patrol_number', 'Rute/Judul' => 'title', 'Site' => 'site.name', 'Area' => 'area.name',
            'Petugas' => 'assignedTo.name', 'Terjadwal' => 'scheduled_at', 'Dimulai' => 'started_at',
            'Selesai' => 'completed_at', 'Status' => 'status', 'Checkpoint' => 'results_count', 'Issue' => 'issue_count',
        ], 'patrol_checklists_'.now()->format('Ymd_His').'.csv');
    }

    public function destroy(Request $request, PatrolChecklist $patrol): RedirectResponse
    {
        $this->authorize('delete', $patrol);

        DB::transaction(function () use ($request, $patrol): void {
            $patrolNumber = $patrol->patrol_number;
            $patrol->delete();
            $this->audit->deleted($patrol, $request->user(), 'security_patrol', $patrol->id);
            $this->activity->log('security_patrol', $patrol->id, 'deleted', "Patroli {$patrolNumber} dihapus.", $request->user());
        });

        return redirect()->route('security.patrols.index')->with('success', 'Jadwal patroli berhasil dihapus.');
    }

    private function formOptions(Request $request): array
    {
        $sites = Site::query()->where('is_active', true);
        $areas = Area::query()->where('is_active', true);
        $officers = User::role('Security Officer')->permission('security.patrols.execute')
            ->with('employee:id,site_id')->where('is_active', true);

        if (! $request->user()->can('core.scope.all')) {
            $siteId = $request->user()->employee?->site_id;
            $sites->whereKey($siteId ?? 0);
            $areas->where('site_id', $siteId ?? 0);
            $officers->whereHas('employee', fn (Builder $employee) => $employee->where('site_id', $siteId ?? 0));
        }

        return [
            'patrol' => null,
            'sites' => $sites->orderBy('name')->get(['id', 'name']),
            'areas' => $areas->orderBy('name')->get(['id', 'site_id', 'name']),
            'officers' => $officers->orderBy('name')
                ->get(['id', 'name', 'email', 'employee_id'])
                ->map(fn (User $officer) => [
                    'id' => $officer->id,
                    'name' => $officer->name,
                    'email' => $officer->email,
                    'site_id' => $officer->employee?->site_id,
                ]),
        ];
    }

    private function applyScope(Builder $query, User $user): void
    {
        if ($user->can('core.scope.all')) {
            return;
        }
        $user->employee?->site_id
            ? $query->where('site_id', $user->employee->site_id)
            : $query->where('assigned_to', $user->id);
    }

    private function notifySiteTeam(PatrolChecklist $patrol, string $type, array $context, User $actor): void
    {
        $recipients = User::role(['QHSSE Officer', 'QHSSE Manager'])
            ->where('id', '!=', $actor->id)
            ->where(function (Builder $query) use ($patrol) {
                $query->whereHas('roles', fn (Builder $role) => $role->where('name', 'QHSSE Manager'))
                    ->orWhereHas('employee', fn (Builder $employee) => $employee->where('site_id', $patrol->site_id));
            })->get();
        $this->notifications->notifyMany($recipients, $type, $context, $actor, 'security_patrol', $patrol->id, route('security.patrols.show', $patrol));
    }
}

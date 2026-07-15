<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\EmergencyPreparedness;

use App\Core\Activity\ActivityService;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\EmergencyPreparedness\ExecuteEmergencyDrillRequest;
use App\Http\Requests\Modules\EmergencyPreparedness\StoreEmergencyDrillRequest;
use App\Http\Requests\Modules\EmergencyPreparedness\UpdateEmergencyDrillRequest;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\EmergencyPreparedness\EmergencyDrill;
use App\Models\Modules\EmergencyPreparedness\EmergencyPlan;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmergencyDrillController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly ActivityService $activityService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmergencyDrill::class);

        $query = EmergencyDrill::query()
            ->with(['emergencyPlan', 'site', 'observer'])
            ->orderBy('scheduled_date', 'desc');

        // Search
        if ($search = $request->input('search')) {
            $query->where('drill_number', 'like', "%{$search}%");
        }

        // Filters
        if ($siteId = $request->input('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($planId = $request->input('plan_id')) {
            $query->where('emergency_plan_id', $planId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($result = $request->input('result')) {
            $query->where('result', $result);
        }

        if ($request->has('upcoming')) {
            $query->upcoming();
        }

        if ($request->has('overdue')) {
            $query->overdue();
        }

        // Scope filtering
        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Top Management', 'Auditor'])) {
            if ($user->hasRole('QHSSE Officer')) {
                $query->whereIn('site_id', $user->employee->sites->pluck('id'));
            } elseif ($user->hasAnyRole(['Supervisor', 'Department Head', 'Employee / Reporter'])) {
                $query->where('site_id', $user->employee->site_id);
            }
        }

        $drills = $query->paginate(15);

        return Inertia::render('Modules/EmergencyPreparedness/Drills/Index', [
            'drills' => $drills,
            'filters' => $request->only(['search', 'site_id', 'plan_id', 'status', 'result']),
            'sites' => Site::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'plans' => EmergencyPlan::orderBy('name')->get(['id', 'name']),
            'can' => [
                'create' => $user->can('emergency.drills.create'),
                'export' => $user->can('emergency.drills.export'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EmergencyDrill::class);

        $sites = Site::where('is_active', true)->get(['id', 'name']);
        $plans = EmergencyPlan::all(['id', 'plan_number', 'name', 'type']);
        $users = User::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Modules/EmergencyPreparedness/Drills/CreateOrEdit', [
            'sites' => $sites,
            'plans' => $plans,
            'users' => $users,
        ]);
    }

    public function store(StoreEmergencyDrillRequest $request): RedirectResponse
    {
        $this->authorize('create', EmergencyDrill::class);

        $validated = $request->validated();

        // Generate drill number
        $validated['drill_number'] = $this->numberingService->generate('emergency')->number;
        $validated['status'] = 'scheduled';

        $drill = EmergencyDrill::create($validated);

        $this->activityService->log(
            'emergency',
            $drill->id,
            'emergency.drill.scheduled',
            "Emergency drill {$drill->drill_number} scheduled",
            $request->user()
        );

        // M15 WS-1: notify the assigned observer that a drill is scheduled
        if ($drill->observer_id) {
            $this->notificationService->notify(
                $drill->observer,
                'emergency.drill.scheduled',
                [
                    'title' => 'Emergency Drill Scheduled',
                    'message' => "You are assigned as observer for drill {$drill->drill_number}.",
                ],
                $request->user(),
                'emergency',
                $drill->id,
                route('emergency.drills.show', $drill)
            );
        }

        return redirect()->route('emergency.drills.show', $drill)
            ->with('success', "Latihan darurat {$drill->drill_number} berhasil dijadwalkan.");
    }

    public function show(EmergencyDrill $drill): Response
    {
        $this->authorize('view', $drill);

        $drill->load([
            'emergencyPlan',
            'site',
            'observer',
        ]);

        return Inertia::render('Modules/EmergencyPreparedness/Drills/Show', [
            'drill' => $drill,
        ]);
    }

    public function edit(EmergencyDrill $drill): Response
    {
        $this->authorize('update', $drill);

        $sites = Site::where('is_active', true)->get(['id', 'name']);
        $plans = EmergencyPlan::all(['id', 'plan_number', 'name', 'type']);
        $users = User::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Modules/EmergencyPreparedness/Drills/CreateOrEdit', [
            'drill' => $drill,
            'sites' => $sites,
            'plans' => $plans,
            'users' => $users,
        ]);
    }

    public function update(UpdateEmergencyDrillRequest $request, EmergencyDrill $drill): RedirectResponse
    {
        $this->authorize('update', $drill);

        $drill->update($request->validated());

        $this->activityService->log(
            'emergency',
            $drill->id,
            'emergency.drill.updated',
            "Emergency drill {$drill->drill_number} updated",
            $request->user()
        );

        return redirect()->route('emergency.drills.show', $drill)
            ->with('success', 'Latihan darurat berhasil diperbarui.');
    }

    public function execute(ExecuteEmergencyDrillRequest $request, EmergencyDrill $drill): RedirectResponse
    {
        $this->authorize('execute', $drill);

        if ($drill->status !== 'scheduled') {
            return redirect()->route('emergency.drills.show', $drill)
                ->with('error', 'Hanya latihan dengan status "scheduled" yang dapat dieksekusi.');
        }

        $validated = $request->validated();
        $validated['status'] = 'executed';

        $drill->update($validated);

        $this->activityService->log(
            moduleName: 'emergency',
            referenceId: $drill->id,
            event: 'emergency.drill.executed',
            description: "Emergency drill {$drill->drill_number} executed",
            actor: $request->user()
        );

        // Notify the observer that the drill was executed
        if ($drill->observer_id) {
            $this->notificationService->notify(
                $drill->observer,
                'emergency.drill.executed',
                [
                    'title' => 'Emergency Drill Executed',
                    'message' => "Drill {$drill->drill_number} was executed with result: {$drill->result_label}.",
                ],
                $request->user(),
                'emergency',
                $drill->id,
                route('emergency.drills.show', $drill)
            );
        }

        // M15 WS-1: failed / needs-improvement -> notify QHSSE Manager
        if (in_array($validated['result'], ['fail', 'needs_improvement'])) {
            $managers = User::role('QHSSE Manager')->where('is_active', true)->get();
            $this->notificationService->notifyMany(
                $managers,
                'emergency.drill.failed',
                [
                    'title' => 'Emergency Drill Needs Attention',
                    'message' => "Drill {$drill->drill_number} result: {$drill->result_label}.",
                ],
                $request->user(),
                'emergency',
                $drill->id,
                route('emergency.drills.show', $drill)
            );
        }

        return redirect()->route('emergency.drills.show', $drill)
            ->with('success', "Latihan darurat berhasil dieksekusi dengan hasil: {$drill->result_label}.");
    }

    public function destroy(EmergencyDrill $drill): RedirectResponse
    {
        $this->authorize('delete', $drill);

        $drillNumber = $drill->drill_number;

        $this->activityService->log(
            'emergency',
            $drill->id,
            'emergency.drill.deleted',
            "Emergency drill {$drillNumber} deleted",
            request()->user()
        );

        $drill->delete();

        return redirect()->route('emergency.drills.index')
            ->with('success', "Latihan darurat {$drillNumber} berhasil dihapus.");
    }

    public function export(Request $request)
    {
        $this->authorize('export', EmergencyDrill::class);

        $query = EmergencyDrill::query()
            ->with(['emergencyPlan', 'site', 'observer']);

        // Apply same filters as index
        if ($search = $request->input('search')) {
            $query->where('drill_number', 'like', "%{$search}%");
        }

        if ($siteId = $request->input('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($result = $request->input('result')) {
            $query->where('result', $result);
        }

        // Scope filtering
        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Top Management', 'Auditor'])) {
            if ($user->hasRole('QHSSE Officer')) {
                $query->whereIn('site_id', $user->employee->sites->pluck('id'));
            } elseif ($user->hasAnyRole(['Supervisor', 'Department Head'])) {
                $query->where('site_id', $user->employee->site_id);
            }
        }

        $drills = $query->get();

        $filename = 'emergency_drills_export_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($drills) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($file, [
                'Nomor Latihan',
                'Rencana Darurat',
                'Site',
                'Tanggal Terjadwal',
                'Tanggal Pelaksanaan',
                'Jumlah Peserta',
                'Observer',
                'Hasil',
                'Status',
                'Dibuat',
            ]);

            foreach ($drills as $drill) {
                fputcsv($file, [
                    $drill->drill_number,
                    $drill->emergencyPlan->name ?? '-',
                    $drill->site?->name ?? '-',
                    $drill->scheduled_date?->format('Y-m-d') ?? '-',
                    $drill->executed_date?->format('Y-m-d') ?? '-',
                    $drill->participants_count ?? '-',
                    $drill->observer->name ?? '-',
                    $drill->result_label ?? '-',
                    $drill->status_label,
                    $drill->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

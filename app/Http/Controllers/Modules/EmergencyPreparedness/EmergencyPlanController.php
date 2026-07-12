<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\EmergencyPreparedness;

use App\Core\Activity\ActivityService;
use App\Core\Numbering\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\EmergencyPreparedness\StoreEmergencyPlanRequest;
use App\Http\Requests\Modules\EmergencyPreparedness\UpdateEmergencyPlanRequest;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\EmergencyPreparedness\EmergencyPlan;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmergencyPlanController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly ActivityService $activityService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmergencyPlan::class);

        $query = EmergencyPlan::query()
            ->with(['site', 'contactPerson'])
            ->orderBy('created_at', 'desc');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('plan_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($siteId = $request->input('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($contactPersonId = $request->input('contact_person_id')) {
            $query->where('contact_person_id', $contactPersonId);
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

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $plans = $query->paginate(15);

        return Inertia::render('Modules/EmergencyPreparedness/Plans/Index', [
            'plans' => $plans,
            'filters' => $request->only(['search', 'site_id', 'type', 'contact_person_id', 'sort_by', 'sort_order']),
            'sites' => Site::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'can' => [
                'create' => $user->can('emergency.plans.create'),
                'export' => $user->can('emergency.plans.export'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EmergencyPlan::class);

        $sites = Site::where('is_active', true)->get(['id', 'name']);
        $users = User::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Modules/EmergencyPreparedness/Plans/CreateOrEdit', [
            'sites' => $sites,
            'users' => $users,
        ]);
    }

    public function store(StoreEmergencyPlanRequest $request): RedirectResponse
    {
        $this->authorize('create', EmergencyPlan::class);

        $validated = $request->validated();

        // Generate plan number
        $validated['plan_number'] = $this->numberingService->generate('emergency')->number;

        $plan = EmergencyPlan::create($validated);

        $this->activityService->log(
            moduleName: 'emergency',
            referenceId: $plan->id,
            event: 'emergency.plan.created',
            description: "Emergency plan {$plan->plan_number} created",
            actor: $request->user()
        );

        return redirect()->route('emergency.plans.show', $plan)
            ->with('success', "Rencana darurat {$plan->plan_number} berhasil dibuat.");
    }

    public function show(EmergencyPlan $plan): Response
    {
        $this->authorize('view', $plan);

        $plan->load([
            'site',
            'contactPerson',
            'drills' => function ($query) {
                $query->orderBy('scheduled_date', 'desc');
            },
        ]);

        return Inertia::render('Modules/EmergencyPreparedness/Plans/Show', [
            'plan' => $plan,
        ]);
    }

    public function edit(EmergencyPlan $plan): Response
    {
        $this->authorize('update', $plan);

        $sites = Site::where('is_active', true)->get(['id', 'name']);
        $users = User::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Modules/EmergencyPreparedness/Plans/CreateOrEdit', [
            'plan' => $plan,
            'sites' => $sites,
            'users' => $users,
        ]);
    }

    public function update(UpdateEmergencyPlanRequest $request, EmergencyPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $plan->update($request->validated());

        $this->activityService->log(
            moduleName: 'emergency',
            referenceId: $plan->id,
            event: 'emergency.plan.updated',
            description: "Emergency plan {$plan->plan_number} updated",
            actor: $request->user()
        );

        return redirect()->route('emergency.plans.show', $plan)
            ->with('success', 'Rencana darurat berhasil diperbarui.');
    }

    public function destroy(EmergencyPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $planNumber = $plan->plan_number;

        $this->activityService->log(
            moduleName: 'emergency',
            referenceId: $plan->id,
            event: 'emergency.plan.deleted',
            description: "Emergency plan {$planNumber} deleted",
            actor: request()->user()
        );

        $plan->delete();

        return redirect()->route('emergency.plans.index')
            ->with('success', "Rencana darurat {$planNumber} berhasil dihapus.");
    }

    public function export(Request $request)
    {
        $this->authorize('export', EmergencyPlan::class);

        $query = EmergencyPlan::query()
            ->with(['site', 'contactPerson']);

        // Apply same filters as index
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('plan_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($siteId = $request->input('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
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

        $plans = $query->get();

        $filename = 'emergency_plans_export_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($plans) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($file, [
                'Nomor Rencana',
                'Nama',
                'Tipe',
                'Site',
                'Contact Person',
                'Deskripsi',
                'Prosedur Respons',
                'Prosedur Eskalasi',
                'Peralatan Dibutuhkan',
                'Dibuat',
            ]);

            foreach ($plans as $plan) {
                fputcsv($file, [
                    $plan->plan_number,
                    $plan->name,
                    $plan->type_label,
                    $plan->site?->name ?? '-',
                    $plan->contactPerson?->name ?? '-',
                    $plan->description,
                    $plan->response_procedure,
                    $plan->escalation_procedure,
                    $plan->equipment_needed ?? '-',
                    $plan->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

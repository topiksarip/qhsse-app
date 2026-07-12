<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\RiskManagement;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Numbering\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\RiskManagement\AssessRiskRegisterRequest;
use App\Http\Requests\Modules\RiskManagement\StoreRiskRegisterRequest;
use App\Http\Requests\Modules\RiskManagement\UpdateRiskRegisterRequest;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\RiskMatrixLevel;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\RiskManagement\RiskRegister;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RiskRegisterController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
        private readonly CsvExporter $csvExporter,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', RiskRegister::class);

        $query = RiskRegister::query()
            ->with(['site', 'area', 'department', 'owner', 'severity', 'riskLevel', 'residualSeverity', 'residualRiskLevel']);

        // Search
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search): void {
                $q->where('register_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('activity', 'like', "%{$search}%")
                    ->orWhere('hazard', 'like', "%{$search}%");
            });
        }

        // Filters
        if (request('site_id')) {
            $query->where('site_id', request('site_id'));
        }

        if (request('area_id')) {
            $query->where('area_id', request('area_id'));
        }

        if (request('department_id')) {
            $query->where('department_id', request('department_id'));
        }

        if (request('type')) {
            $query->where('type', request('type'));
        }

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('risk_level_id')) {
            $query->where('risk_level_id', request('risk_level_id'));
        }

        if (request('owner_id')) {
            $query->where('owner_id', request('owner_id'));
        }

        // Sort
        $sortField = request('sort_field', 'created_at');
        $sortDirection = request('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate
        $items = $query->paginate(request('per_page', 15))->withQueryString();

        return Inertia::render('Modules/RiskManagement/Index', [
            'items' => $items,
            'filters' => [
                'search' => request('search'),
                'site_id' => request('site_id'),
                'area_id' => request('area_id'),
                'department_id' => request('department_id'),
                'type' => request('type'),
                'status' => request('status'),
                'risk_level_id' => request('risk_level_id'),
                'owner_id' => request('owner_id'),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
                'per_page' => request('per_page', 15),
            ],
            'sites' => Site::where('is_active', true)->get(['id', 'code', 'name']),
            'areas' => Area::where('is_active', true)->get(['id', 'code', 'name', 'site_id']),
            'departments' => Department::where('is_active', true)->get(['id', 'code', 'name']),
            'severities' => Severity::where('is_active', true)->orderBy('level')->get(),
            'riskLevels' => RiskMatrixLevel::where('is_active', true)->get(),
            'users' => User::where('is_active', true)->get(['id', 'name', 'email']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', RiskRegister::class);

        return Inertia::render('Modules/RiskManagement/Create', [
            'sites' => Site::where('is_active', true)->get(['id', 'code', 'name']),
            'areas' => Area::where('is_active', true)->get(['id', 'code', 'name', 'site_id']),
            'departments' => Department::where('is_active', true)->get(['id', 'code', 'name']),
            'severities' => Severity::where('is_active', true)->orderBy('level')->get(),
            'riskMatrixLevels' => RiskMatrixLevel::where('is_active', true)->get(),
            'users' => User::where('is_active', true)->get(['id', 'name', 'email']),
        ]);
    }

    public function store(StoreRiskRegisterRequest $request): RedirectResponse
    {
        $this->authorize('create', RiskRegister::class);

        $validated = $request->validated();
        $actor = $request->user();

        DB::beginTransaction();

        try {
            // Generate register number
            $generated = $this->numberingService->generate('risk', $actor);

            // Create risk register
            $riskRegister = RiskRegister::create([
                ...$validated,
                'register_number' => $generated->number,
            ]);

            // Audit trail
            $this->auditService->created($riskRegister, $actor, 'risk', $riskRegister->id);

            // Activity log
            $this->activityService->log(
                'risk',
                $riskRegister->id,
                'risk.created',
                "Risk register {$riskRegister->register_number} dibuat",
                $actor
            );

            DB::commit();

            return redirect()->route('risk.registers.show', $riskRegister)
                ->with('success', 'Risk register berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal membuat risk register: ' . $e->getMessage());
        }
    }

    public function show(RiskRegister $riskRegister): Response
    {
        $this->authorize('view', $riskRegister);

        $riskRegister->load([
            'site',
            'area',
            'department',
            'owner',
            'severity',
            'riskLevel',
            'residualSeverity',
            'residualRiskLevel',
            'comments.author',
            'activities.actor',
        ]);

        return Inertia::render('Modules/RiskManagement/Show', [
            'riskRegister' => $riskRegister,
        ]);
    }

    public function edit(RiskRegister $riskRegister): Response
    {
        $this->authorize('update', $riskRegister);

        if ($riskRegister->isObsolete()) {
            return back()->with('error', 'Risk register yang obsolete tidak dapat diedit.');
        }

        return Inertia::render('Modules/RiskManagement/Edit', [
            'riskRegister' => $riskRegister->load(['site', 'area', 'department', 'owner', 'severity', 'riskLevel', 'residualSeverity', 'residualRiskLevel']),
            'sites' => Site::where('is_active', true)->get(['id', 'code', 'name']),
            'areas' => Area::where('is_active', true)->get(['id', 'code', 'name', 'site_id']),
            'departments' => Department::where('is_active', true)->get(['id', 'code', 'name']),
            'severities' => Severity::where('is_active', true)->orderBy('level')->get(),
            'riskMatrixLevels' => RiskMatrixLevel::where('is_active', true)->get(),
            'users' => User::where('is_active', true)->get(['id', 'name', 'email']),
        ]);
    }

    public function update(UpdateRiskRegisterRequest $request, RiskRegister $riskRegister): RedirectResponse
    {
        $this->authorize('update', $riskRegister);

        if ($riskRegister->isObsolete()) {
            return back()->with('error', 'Risk register yang obsolete tidak dapat diedit.');
        }

        $validated = $request->validated();
        $actor = $request->user();
        $oldValues = $riskRegister->toArray();

        DB::beginTransaction();

        try {
            $riskRegister->update($validated);

            // Audit trail
            $this->auditService->updated($riskRegister, $oldValues, $actor, 'risk', $riskRegister->id);

            // Activity log
            $this->activityService->log(
                'risk',
                $riskRegister->id,
                'risk.updated',
                "Risk register {$riskRegister->register_number} diperbarui",
                $actor
            );

            DB::commit();

            return redirect()->route('risk.registers.show', $riskRegister)
                ->with('success', 'Risk register berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui risk register: ' . $e->getMessage());
        }
    }

    public function assess(AssessRiskRegisterRequest $request, RiskRegister $riskRegister): RedirectResponse
    {
        $this->authorize('assess', $riskRegister);

        if (!$riskRegister->canBeAssessed()) {
            return back()->with('error', 'Risk register hanya dapat dinilai jika statusnya identified.');
        }

        $validated = $request->validated();
        $actor = $request->user();
        $oldValues = $riskRegister->toArray();

        DB::beginTransaction();

        try {
            // Verify risk_level_id matches severity × probability
            $severity = Severity::findOrFail($validated['severity_id']);
            $expectedRiskLevel = RiskMatrixLevel::where('consequence', $severity->level)
                ->where('likelihood', $validated['probability_id'])
                ->where('is_active', true)
                ->first();

            if (!$expectedRiskLevel || $expectedRiskLevel->id !== $validated['risk_level_id']) {
                return back()->with('error', 'Risk level tidak sesuai dengan severity × probability.');
            }

            // Update risk register
            $riskRegister->update([
                'severity_id' => $validated['severity_id'],
                'probability_id' => $validated['probability_id'],
                'risk_level_id' => $validated['risk_level_id'],
                'additional_controls' => $validated['additional_controls'] ?? null,
                'residual_severity_id' => $validated['residual_severity_id'] ?? null,
                'residual_probability_id' => $validated['residual_probability_id'] ?? null,
                'residual_risk_level_id' => $validated['residual_risk_level_id'] ?? null,
                'status' => 'assessed',
            ]);

            // Audit trail
            $this->auditService->updated($riskRegister, $oldValues, $actor, 'risk', $riskRegister->id);

            // Activity log
            $this->activityService->log(
                'risk',
                $riskRegister->id,
                'risk.assessed',
                "Risk register {$riskRegister->register_number} telah dinilai. Risk level: {$riskRegister->riskLevel->level}",
                $actor
            );

            DB::commit();

            return redirect()->route('risk.registers.show', $riskRegister)
                ->with('success', 'Risk assessment berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal melakukan risk assessment: ' . $e->getMessage());
        }
    }

    public function needsControls(RiskRegister $riskRegister): RedirectResponse
    {
        $this->authorize('assess', $riskRegister);

        if (!$riskRegister->canNeedControls()) {
            return back()->with('error', 'Transisi status tidak valid.');
        }

        $actor = request()->user();
        $oldValues = $riskRegister->toArray();

        DB::beginTransaction();

        try {
            $riskRegister->update(['status' => 'controls_needed']);

            // Audit trail
            $this->auditService->updated($riskRegister, $oldValues, $actor, 'risk', $riskRegister->id);

            // Activity log
            $this->activityService->log(
                'risk',
                $riskRegister->id,
                'risk.controls_needed',
                "Risk register {$riskRegister->register_number} memerlukan kontrol tambahan",
                $actor
            );

            DB::commit();

            return redirect()->route('risk.registers.show', $riskRegister)
                ->with('success', 'Status berhasil diperbarui ke Controls Needed.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui status: ' . $e->getMessage());
        }
    }

    public function implementControls(RiskRegister $riskRegister): RedirectResponse
    {
        $this->authorize('assess', $riskRegister);

        if (!$riskRegister->canImplementControls()) {
            return back()->with('error', 'Transisi status tidak valid.');
        }

        if (empty($riskRegister->additional_controls)) {
            return back()->with('error', 'Additional controls wajib diisi sebelum mengimplementasi kontrol.');
        }

        $actor = request()->user();
        $oldValues = $riskRegister->toArray();

        DB::beginTransaction();

        try {
            $riskRegister->update(['status' => 'controls_in_place']);

            // Audit trail
            $this->auditService->updated($riskRegister, $oldValues, $actor, 'risk', $riskRegister->id);

            // Activity log
            $this->activityService->log(
                'risk',
                $riskRegister->id,
                'risk.controls_in_place',
                "Risk register {$riskRegister->register_number} - kontrol tambahan telah diimplementasi",
                $actor
            );

            DB::commit();

            return redirect()->route('risk.registers.show', $riskRegister)
                ->with('success', 'Status berhasil diperbarui ke Controls In Place.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui status: ' . $e->getMessage());
        }
    }

    public function monitor(RiskRegister $riskRegister): RedirectResponse
    {
        $this->authorize('assess', $riskRegister);

        if (!$riskRegister->canBeMonitored()) {
            return back()->with('error', 'Transisi status tidak valid.');
        }

        $actor = request()->user();
        $oldValues = $riskRegister->toArray();

        DB::beginTransaction();

        try {
            $riskRegister->update(['status' => 'monitored']);

            // Audit trail
            $this->auditService->updated($riskRegister, $oldValues, $actor, 'risk', $riskRegister->id);

            // Activity log
            $this->activityService->log(
                'risk',
                $riskRegister->id,
                'risk.monitored',
                "Risk register {$riskRegister->register_number} sekarang dipantau",
                $actor
            );

            DB::commit();

            return redirect()->route('risk.registers.show', $riskRegister)
                ->with('success', 'Status berhasil diperbarui ke Monitored.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui status: ' . $e->getMessage());
        }
    }

    public function obsolete(RiskRegister $riskRegister): RedirectResponse
    {
        $this->authorize('assess', $riskRegister);

        if ($riskRegister->isObsolete()) {
            return back()->with('error', 'Risk register sudah obsolete.');
        }

        $actor = request()->user();
        $oldValues = $riskRegister->toArray();

        DB::beginTransaction();

        try {
            $riskRegister->update(['status' => 'obsolete']);

            // Audit trail
            $this->auditService->updated($riskRegister, $oldValues, $actor, 'risk', $riskRegister->id);

            // Activity log
            $this->activityService->log(
                'risk',
                $riskRegister->id,
                'risk.obsolete',
                "Risk register {$riskRegister->register_number} ditetapkan obsolete",
                $actor
            );

            DB::commit();

            return redirect()->route('risk.registers.show', $riskRegister)
                ->with('success', 'Risk register berhasil ditetapkan obsolete.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }

    public function export()
    {
        $this->authorize('export', RiskRegister::class);

        $query = RiskRegister::query()
            ->with(['site', 'area', 'department', 'owner', 'severity', 'riskLevel', 'residualSeverity', 'residualRiskLevel']);

        // Apply same filters as index
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search): void {
                $q->where('register_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('activity', 'like', "%{$search}%");
            });
        }

        if (request('site_id')) {
            $query->where('site_id', request('site_id'));
        }
        if (request('area_id')) {
            $query->where('area_id', request('area_id'));
        }
        if (request('department_id')) {
            $query->where('department_id', request('department_id'));
        }
        if (request('type')) {
            $query->where('type', request('type'));
        }
        if (request('status')) {
            $query->where('status', request('status'));
        }
        if (request('risk_level_id')) {
            $query->where('risk_level_id', request('risk_level_id'));
        }

        $query->orderBy('created_at', 'desc');

        $columns = [
            'Nomor' => 'register_number',
            'Judul' => 'title',
            'Tipe' => 'type',
            'Site' => fn($item) => $item->site?->name ?? '',
            'Area' => fn($item) => $item->area?->name ?? '',
            'Department' => fn($item) => $item->department?->name ?? '',
            'Aktivitas' => 'activity',
            'Hazard' => 'hazard',
            'Existing Controls' => 'existing_controls',
            'Initial Severity' => fn($item) => $item->severity?->name ?? '',
            'Initial Probability' => 'probability_id',
            'Initial Risk Level' => fn($item) => $item->riskLevel?->name ?? '',
            'Additional Controls' => 'additional_controls',
            'Residual Severity' => fn($item) => $item->residualSeverity?->name ?? '',
            'Residual Probability' => 'residual_probability_id',
            'Residual Risk Level' => fn($item) => $item->residualRiskLevel?->name ?? '',
            'Owner' => fn($item) => $item->owner?->name ?? '',
            'Status' => 'status',
            'Review Date' => fn($item) => $item->review_date?->format('Y-m-d') ?? '',
            'Created At' => fn($item) => $item->created_at->format('Y-m-d H:i:s'),
        ];

        $filename = 'risk_registers_export_' . now()->format('Ymd_His') . '.csv';

        return $this->csvExporter->stream($query, $columns, $filename);
    }
}

<?php

namespace App\Http\Controllers\Modules\Permit;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Core\Workflow\WorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Permit\SignChecklistRequest;
use App\Http\Requests\Modules\Permit\StorePermitRequest;
use App\Http\Requests\Modules\Permit\UpdatePermitRequest;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Company;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Permit\Permit;
use App\Models\Modules\Permit\PermitChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PermitController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected NumberingService $numberingService,
        protected AuditService $auditService,
        protected ActivityService $activityService,
    ) {
        $this->authorizeResource(Permit::class, 'permit');
    }

    public function index(Request $request): InertiaResponse
    {
        $query = Permit::query()
            ->with(['site', 'area', 'department', 'contractor', 'creator', 'approver'])
            ->select('permits.*');

        // Organization scope
        $scope = $request->input('scope', 'all');
        $user = $request->user();

        if ($scope === 'site' && $user->employee?->site_id) {
            $query->where('permits.site_id', $user->employee->site_id);
        } elseif ($scope === 'department' && $user->employee?->department_id) {
            $query->where('permits.department_id', $user->employee->department_id);
        } elseif ($scope === 'own') {
            $query->where('permits.created_by', $user->id);
        }

        // Filters
        if ($request->filled('site_id')) {
            $query->where('permits.site_id', $request->input('site_id'));
        }

        if ($request->filled('department_id')) {
            $query->where('permits.department_id', $request->input('department_id'));
        }

        if ($request->filled('type')) {
            $query->where('permits.type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('permits.status', $request->input('status'));
        }

        if ($request->filled('risk_level')) {
            $query->where('permits.risk_level', $request->input('risk_level'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('permits.permit_number', 'like', "%{$search}%")
                    ->orWhere('permits.title', 'like', "%{$search}%")
                    ->orWhere('permits.work_location', 'like', "%{$search}%");
            });
        }

        // Validity filters
        if ($request->filled('validity_status')) {
            $now = now();
            match ($request->input('validity_status')) {
                'active' => $query->where('permits.status', 'active')
                    ->where('permits.start_datetime', '<=', $now)
                    ->where('permits.end_datetime', '>', $now),
                'expiring_soon' => $query->where('permits.status', 'active')
                    ->where('permits.end_datetime', '>', $now)
                    ->where('permits.end_datetime', '<=', $now->copy()->addHours(24)),
                'expired' => $query->where('permits.status', 'active')
                    ->where('permits.end_datetime', '<', $now),
                default => null,
            };
        }

        $permits = ListQuery::for($query)
            ->defaultSort('-created_at')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        return Inertia::render('Modules/Permit/Index', [
            'permits' => $permits,
            'filters' => $request->only(['scope', 'site_id', 'department_id', 'type', 'status', 'risk_level', 'validity_status', 'search']),
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'types' => Permit::getTypes(),
            'statuses' => Permit::getStatuses(),
            'riskLevels' => Permit::getRiskLevels(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Modules/Permit/Form', [
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'areas' => Area::select('id', 'site_id', 'name')->orderBy('name')->get(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'contractors' => Company::where('type', 'contractor')->select('id', 'name')->orderBy('name')->get(),
            'types' => Permit::getTypes(),
            'riskLevels' => Permit::getRiskLevels(),
        ]);
    }

    public function store(StorePermitRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = $request->user();

            // Generate permit number
            $permitNumber = $this->numberingService->generate(
                key: 'permit',
                siteId: $request->input('site_id'),
                includeSiteCode: true
            );

            // Calculate validity hours
            $startDatetime = Carbon::parse($request->input('start_datetime'));
            $endDatetime = Carbon::parse($request->input('end_datetime'));
            $validityHours = (int) $startDatetime->diffInHours($endDatetime);

            // Create permit
            $permit = Permit::create([
                ...$request->validated(),
                'permit_number' => $permitNumber,
                'validity_hours' => $validityHours,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            // Generate checklist items based on permit type
            $checklistItems = $this->getChecklistTemplateForType($permit->type);
            foreach ($checklistItems as $itemText) {
                PermitChecklist::create([
                    'permit_id' => $permit->id,
                    'item_text' => $itemText,
                    'is_checked' => false,
                ]);
            }

            // Audit trail
            $this->auditService->log(
                moduleName: 'permit',
                action: 'create',
                referenceId: $permit->id,
                details: "Permit {$permit->permit_number} dibuat",
                userId: $user->id
            );

            // Activity log
            $this->activityService->log(
                moduleName: 'permit',
                referenceId: $permit->id,
                action: 'create',
                description: "Permit {$permit->permit_number} dibuat oleh {$user->name}",
                userId: $user->id
            );

            return redirect()->route('permit.work.show', $permit)
                ->with('success', "Permit berhasil dibuat dengan nomor {$permit->permit_number}");
        });
    }

    protected function getChecklistTemplateForType(string $type): array
    {
        return match ($type) {
            'hot_work' => [
                'APD tahan api tersedia dan dipakai',
                'Fire extinguisher tersedia di lokasi kerja',
                'Fire watch ditugaskan dan briefing dilakukan',
                'Area kerja bebas dari material mudah terbakar',
                'Ventilasi memadai untuk menghilangkan asap/gas',
                'Sparks/slag tidak akan menyebabkan kebakaran',
            ],
            'working_at_height' => [
                'Full body harness dan lanyard tersedia dan dipakai',
                'Anchor point telah diperiksa dan aman',
                'Safety net atau catch platform tersedia bila diperlukan',
                'Ladder atau scaffolding dalam kondisi baik',
                'Area bawah telah di-barricade',
                'Cuaca memungkinkan untuk bekerja di ketinggian',
            ],
            'confined_space' => [
                'Gas test dilakukan dan hasil aman (O2, LEL, H2S, CO)',
                'Ventilasi/blower aktif selama pekerjaan',
                'Rescue equipment tersedia dan standby',
                'Komunikasi dengan hole watch terjaga',
                'Emergency evacuation plan telah dijelaskan',
                'SCBA atau respirator tersedia bila diperlukan',
            ],
            'electrical' => [
                'Power supply telah di-isolate dan di-LOTO',
                'Voltage test menunjukkan zero energy',
                'Insulated tools dan gloves tersedia dan dipakai',
                'Area kerja kering dan tidak ada genangan air',
                'Emergency shut-off point telah diidentifikasi',
                'Qualified electrician assigned',
            ],
            'excavation' => [
                'Underground utility telah di-locate dan di-mark',
                'Shoring atau sloping dilakukan sesuai kedalaman',
                'Soil stability telah dinilai',
                'Access/egress ladder tersedia setiap 25 feet',
                'Spoil pile minimal 2 feet dari edge',
                'Competent person assigned untuk inspeksi harian',
            ],
            'lifting' => [
                'Crane/hoist dalam kondisi baik dan certified',
                'Load calculation dan rigging plan tersedia',
                'Operator memiliki sertifikat valid',
                'Signal person ditugaskan',
                'Area lifting telah di-barricade',
                'Load test dilakukan sebelum actual lift',
            ],
            'other' => [
                'Hazard assessment telah dilakukan',
                'PPE yang sesuai tersedia dan dipakai',
                'Emergency procedure telah dijelaskan',
                'Supervisor telah meng-approve pekerjaan',
            ],
        };
    }

    public function show(Permit $permit): InertiaResponse
    {
        $permit->load([
            'site',
            'area',
            'department',
            'contractor',
            'creator.employee',
            'approver.employee',
            'closer.employee',
            'checklists.checker',
        ]);

        $workflow = $this->workflowService->getWorkflow('permit', $permit->id);

        // Map workflow transitions to permit action keys, gated by permission.
        $availableActions = collect($workflow['available_transitions'])
            ->map(function (array $t) {
                return [
                    'action_key' => $t['action_key'],
                    'action_label' => $t['action_label'],
                    'requires_reason' => (bool) $t['requires_reason'],
                ];
            })
            ->all();

        $checklistProgress = [
            'total' => $permit->checklists->count(),
            'signed' => $permit->checklists->where('is_checked', true)->count(),
            'all_signed' => $permit->checklists->count() > 0
                && $permit->checklists->where('is_checked', false)->count() === 0,
        ];

        return Inertia::render('Modules/Permit/Show', [
            'permit' => $permit,
            'workflow' => $workflow,
            'availableActions' => $availableActions,
            'checklistProgress' => $checklistProgress,
        ]);
    }

    public function edit(Permit $permit): InertiaResponse
    {
        return Inertia::render('Modules/Permit/Form', [
            'permit' => $permit,
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'areas' => Area::select('id', 'site_id', 'name')->orderBy('name')->get(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'contractors' => Company::where('type', 'contractor')->select('id', 'name')->orderBy('name')->get(),
            'types' => Permit::getTypes(),
            'riskLevels' => Permit::getRiskLevels(),
        ]);
    }

    public function update(UpdatePermitRequest $request, Permit $permit)
    {
        return DB::transaction(function () use ($request, $permit) {
            $user = $request->user();

            // Recalculate validity hours if dates changed
            $data = $request->validated();
            if (isset($data['start_datetime']) || isset($data['end_datetime'])) {
                $startDatetime = Carbon::parse($data['start_datetime'] ?? $permit->start_datetime);
                $endDatetime = Carbon::parse($data['end_datetime'] ?? $permit->end_datetime);
                $data['validity_hours'] = (int) $startDatetime->diffInHours($endDatetime);
            }

            $permit->update($data);

            // Audit trail
            $this->auditService->log(
                moduleName: 'permit',
                action: 'update',
                referenceId: $permit->id,
                details: "Permit {$permit->permit_number} diupdate",
                userId: $user->id
            );

            return redirect()->route('permit.work.show', $permit)
                ->with('success', 'Permit berhasil diupdate');
        });
    }

    public function signChecklist(SignChecklistRequest $request, Permit $permit)
    {
        return DB::transaction(function () use ($request, $permit) {
            $user = $request->user();
            $checklist = PermitChecklist::findOrFail($request->input('checklist_id'));

            if ($checklist->permit_id !== $permit->id) {
                abort(403, 'Checklist item tidak terkait dengan permit ini');
            }

            $checklist->update([
                'is_checked' => $request->input('is_checked'),
                'checked_by' => $request->input('is_checked') ? $user->id : null,
                'checked_at' => $request->input('is_checked') ? now() : null,
            ]);

            // Activity log
            $action = $request->input('is_checked') ? 'signed' : 'unsigned';
            $this->activityService->log(
                moduleName: 'permit',
                referenceId: $permit->id,
                action: $action,
                description: "Checklist item {$action} oleh {$user->name}",
                userId: $user->id
            );

            return back()->with('success', 'Checklist berhasil diupdate');
        });
    }

    public function transition(Request $request, Permit $permit)
    {
        $request->validate([
            'action' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $permit) {
            $user = $request->user();
            $action = $request->input('action');

            // Execute workflow transition
            $this->workflowService->transition(
                moduleName: 'permit',
                referenceId: $permit->id,
                currentStatus: $permit->status,
                actionKey: $action,
                reason: $request->input('reason'),
                userId: $user->id
            );

            // Update permit status based on action
            $newStatus = match ($action) {
                'submit' => 'submitted',
                'review' => 'under_review',
                'approve' => 'approved',
                'activate' => 'active',
                'close' => 'closed',
                'reject' => 'rejected',
                default => $permit->status,
            };

            $permit->update(['status' => $newStatus]);

            // Update action-specific fields
            if ($action === 'approve') {
                $permit->update([
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);
            } elseif ($action === 'close') {
                $permit->update([
                    'closed_by' => $user->id,
                    'closed_at' => now(),
                ]);
            } elseif ($action === 'reject') {
                $permit->update([
                    'cancellation_reason' => $request->input('reason'),
                ]);
            }

            // Audit trail
            $this->auditService->log(
                moduleName: 'permit',
                action: $action,
                referenceId: $permit->id,
                details: "Permit {$permit->permit_number} status changed to {$newStatus}",
                userId: $user->id
            );

            return back()->with('success', "Permit berhasil di-{$action}");
        });
    }

    public function export(Request $request)
    {
        $this->authorize('export', Permit::class);

        $query = Permit::query()->with(['site', 'creator', 'approver']);

        // Apply same filters as index
        $scope = $request->input('scope', 'all');
        $user = $request->user();

        if ($scope === 'site' && $user->employee?->site_id) {
            $query->where('permits.site_id', $user->employee->site_id);
        } elseif ($scope === 'department' && $user->employee?->department_id) {
            $query->where('permits.department_id', $user->employee->department_id);
        } elseif ($scope === 'own') {
            $query->where('permits.created_by', $user->id);
        }

        if ($request->filled('site_id')) {
            $query->where('permits.site_id', $request->input('site_id'));
        }

        if ($request->filled('type')) {
            $query->where('permits.type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('permits.status', $request->input('status'));
        }

        $permits = $query->orderBy('created_at', 'desc')->get();

        return CsvExporter::export(
            data: $permits,
            filename: 'permits_'.now()->format('Y-m-d_His').'.csv',
            columns: [
                'permit_number' => 'Permit Number',
                'type' => 'Type',
                'title' => 'Title',
                'site.name' => 'Site',
                'work_location' => 'Work Location',
                'start_datetime' => 'Start Date',
                'end_datetime' => 'End Date',
                'validity_hours' => 'Validity (hours)',
                'status' => 'Status',
                'risk_level' => 'Risk Level',
                'creator.name' => 'Created By',
                'created_at' => 'Created At',
            ]
        );
    }
}


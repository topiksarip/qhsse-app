<?php

namespace App\Http\Controllers\Modules\Contractor;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Contractor\StoreContractorEvaluationRequest;
use App\Http\Requests\Modules\Contractor\StoreContractorRequest;
use App\Http\Requests\Modules\Contractor\UpdateContractorPrequalificationRequest;
use App\Http\Requests\Modules\Contractor\UpdateContractorRequest;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Contractor\Contractor;
use App\Models\Modules\Contractor\ContractorEvaluation;
use App\Models\User;
use App\Modules\Contractor\ContractorAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractorController extends Controller
{
    public function __construct(
        protected NumberingService $numberingService,
        protected ActivityService $activityService,
        protected AuditService $auditService,
        protected NotificationService $notificationService,
        protected ContractorAccess $access,
    ) {
        $this->authorizeResource(Contractor::class, 'contractor');
    }

    public function index(Request $request): InertiaResponse
    {
        $query = $this->access->scope(
            Contractor::query()
                ->with(['createdBy:id,name', 'approvedBy:id,name'])
                ->orderBy('created_at', 'desc'),
            $request->user()
        );

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('contractor_number', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($status = $request->input('contract_status')) {
            $query->where('contract_status', $status);
        }

        if ($approvalStatus = $request->input('approval_status')) {
            $query->where('approval_status', $approvalStatus);
        }

        if ($businessType = $request->input('business_type')) {
            $query->where('business_type', $businessType);
        }

        if ($siteId = $request->input('site_id')) {
            $query->whereJsonContains('authorized_sites', (int)$siteId);
        }

        // Date range
        if ($dateFrom = $request->input('date_from')) {
            $query->where('contract_start_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('contract_end_date', '<=', $dateTo);
        }

        // Expiring soon
        if ($request->boolean('expiring_soon')) {
            $query->expiringSoon(30);
        }

        if ($request->boolean('safety_induction_expiring')) {
            $query->safetyInductionExpiring(30);
        }

        if ($request->boolean('insurance_expiring')) {
            $query->insuranceExpiring(30);
        }

        $contractors = $query->paginate($request->input('per_page', 15))
            ->withQueryString();

        return Inertia::render('Modules/Contractor/Index', [
            'contractors' => $contractors,
            'filters' => $request->only([
                'search', 'contract_status', 'approval_status', 
                'business_type', 'site_id', 'date_from', 'date_to',
                'expiring_soon', 'safety_induction_expiring', 'insurance_expiring'
            ]),
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'contractStatuses' => Contractor::getContractStatuses(),
            'approvalStatuses' => Contractor::getApprovalStatuses(),
            'businessTypes' => Contractor::getBusinessTypes(),
            'can' => [
                'create' => $request->user()->can('contractor.management.create'),
                'export' => $request->user()->can('contractor.management.export'),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Modules/Contractor/CreateOrEdit', [
            'contractor' => null,
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'areas' => Area::select('id', 'site_id', 'name')->orderBy('name')->get(),
            'contractStatuses' => Contractor::getContractStatuses(),
            'approvalStatuses' => Contractor::getApprovalStatuses(),
            'businessTypes' => Contractor::getBusinessTypes(),
        ]);
    }

    public function store(StoreContractorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generate contractor number
        $generated = $this->numberingService->generate(
            moduleName: 'contractor',
            actor: $request->user(),
            siteCode: null,
            referenceType: 'CON'
        );
        $validated['contractor_number'] = $generated->number;

        // Set audit fields
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        // If approved, set approval fields
        if ($validated['approval_status'] === 'approved') {
            $validated['approved_by'] = $request->user()->id;
            $validated['approved_at'] = now();
        }

        $contractor = Contractor::create($validated);

        // Audit trail
        $this->auditService->created($contractor, $request->user(), 'contractor', $contractor->id);

        // Notify QHSSE team of new registration
        $recipients = User::role(['QHSSE Manager', 'QHSSE Officer'])
            ->where('is_active', true)
            ->get();

        foreach ($recipients as $recipient) {
            $this->notificationService->notify(
                recipient: $recipient,
                type: 'contractor.registered',
                context: [
                    'contractor_id' => $contractor->id,
                    'contractor_number' => $contractor->contractor_number,
                    'company_name' => $contractor->company_name,
                    'registered_by' => $request->user()->name,
                ],
                actor: $request->user(),
                moduleName: 'contractor',
                referenceId: $contractor->id,
                actionUrl: route('contractors.show', $contractor),
            );
        }

        $this->activityService->log(
            moduleName: 'contractor',
            referenceId: $contractor->id,
            event: 'contractor.created',
            description: "Contractor {$contractor->company_name} created",
            actor: $request->user()
        );

        return redirect()->route('contractors.show', $contractor)
            ->with('success', 'Contractor berhasil ditambahkan.');
    }

    public function show(Contractor $contractor): InertiaResponse
    {
        $contractor->load([
            'createdBy:id,name',
            'updatedBy:id,name',
            'approvedBy:id,name',
        ]);

        return Inertia::render('Modules/Contractor/Show', [
            'contractor' => $contractor,
        ]);
    }

    public function edit(Contractor $contractor): InertiaResponse
    {
        return Inertia::render('Modules/Contractor/CreateOrEdit', [
            'contractor' => $contractor,
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'areas' => Area::select('id', 'site_id', 'name')->orderBy('name')->get(),
            'contractStatuses' => Contractor::getContractStatuses(),
            'approvalStatuses' => Contractor::getApprovalStatuses(),
            'businessTypes' => Contractor::getBusinessTypes(),
        ]);
    }

    public function update(UpdateContractorRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->authorize('update', $contractor);

        $validated = $request->validated();
        $validated['updated_by'] = $request->user()->id;

        // Transition guard: blacklisted -> active only by Admin / Super Admin (G8)
        $oldStatus = $contractor->contract_status;
        $newStatus = $validated['contract_status'] ?? $oldStatus;
        if ($oldStatus === 'blacklisted' && $newStatus === 'active'
            && ! $request->user()->hasAnyRole(['Admin', 'Super Admin'])) {
            return back()->withErrors([
                'contract_status' => 'Transisi blacklisted -> active hanya boleh dilakukan oleh Administrator.',
            ]);
        }

        // Handle approval status change
        $oldApprovalStatus = $contractor->approval_status;
        $newApprovalStatus = $validated['approval_status'];

        if ($oldApprovalStatus !== $newApprovalStatus && $newApprovalStatus === 'approved') {
            $validated['approved_by'] = $request->user()->id;
            $validated['approved_at'] = now();
        }

        $oldValues = $contractor->toArray();
        $contractor->update($validated);

        // Audit trail
        $this->auditService->updated($contractor, $oldValues, $request->user(), 'contractor', $contractor->id);

        // Log status change event
        if ($oldStatus !== $newStatus) {
            $this->auditService->log(
                'contractor.status_changed',
                $contractor,
                ['contract_status' => $oldStatus],
                ['contract_status' => $newStatus],
                $request->user(),
                'contractor',
                $contractor->id
            );
        }

        $this->activityService->log(
            moduleName: 'contractor',
            referenceId: $contractor->id,
            event: 'contractor.updated',
            description: "Contractor {$contractor->company_name} updated",
            actor: $request->user()
        );

        return redirect()->route('contractors.show', $contractor)
            ->with('success', 'Contractor berhasil diperbarui.');
    }

    /**
     * Store a new evaluation for a contractor.
     * Calculates total_score, derives result, and updates safety_rating.
     */
    public function storeEvaluation(Contractor $contractor, StoreContractorEvaluationRequest $request): RedirectResponse
    {
        $this->authorize('evaluate', $contractor);

        $actor = $request->user();
        $data = $request->validated();

        // 1. Calculate total_score from criteria
        $totalScore = (int) array_sum($data['criteria']);

        // 2. Derive result from total_score
        $result = ContractorEvaluation::deriveResult($totalScore);

        // 3. Create evaluation record (append-only)
        $evaluation = $contractor->evaluations()->create([
            'evaluation_date' => $data['evaluation_date'],
            'evaluator_id' => $actor->id,
            'criteria' => $data['criteria'],
            'total_score' => $totalScore,
            'result' => $result,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        // 4. Recalculate contractor safety_rating
        $oldRating = $contractor->safety_rating;
        $newRating = $this->calculateSafetyRating($contractor);
        $contractor->update(['safety_rating' => $newRating]);

        // 5. Audit trail: evaluation created
        $this->auditService->created($evaluation, $actor, 'contractor', $contractor->id);

        // 6. Audit trail: safety_rating updated (if changed)
        if ($oldRating !== $newRating) {
            $this->auditService->log(
                'contractor.safety_rating_updated',
                $contractor,
                ['safety_rating' => $oldRating],
                ['safety_rating' => $newRating],
                $actor,
                'contractor',
                $contractor->id
            );
        }

        // 7. Activity log
        $this->activityService->log(
            moduleName: 'contractor',
            referenceId: $contractor->id,
            event: 'contractor.evaluated',
            description: "Evaluation created by {$actor->name}. Score: {$totalScore}/100 ({$result}). Safety rating: " . ($newRating ?? 'N/A'),
            actor: $actor
        );

        // 8. Notification
        $this->notificationService->notifyMany(
            $this->getQhsseManagers(),
            'contractor.evaluated',
            [
                'contractor' => $contractor->fresh()->toArray(),
                'evaluation' => $evaluation->toArray(),
                'evaluator' => $actor->toArray(),
            ],
            $actor,
            'contractor',
            $contractor->id,
            route('contractors.show', $contractor)
        );

        return back()->with('success', 'Evaluasi berhasil ditambahkan.');
    }

    /**
     * Activate prequalification for a contractor.
     */
    public function setPrequalified(Contractor $contractor, UpdateContractorPrequalificationRequest $request): RedirectResponse
    {
        $this->authorize('update', $contractor);

        $actor = $request->user();
        $oldValues = $contractor->toArray();

        $contractor->update([
            'is_prequalified' => true,
            'prequalified_until' => $request->validated()['prequalified_until'],
        ]);

        // Audit trail
        $this->auditService->updated($contractor, $oldValues, $actor, 'contractor', $contractor->id);

        // Activity log
        $this->activityService->log(
            moduleName: 'contractor',
            referenceId: $contractor->id,
            event: 'contractor.prequalified',
            description: "Contractor {$contractor->contractor_number} prequalified until {$contractor->prequalified_until} by {$actor->name}",
            actor: $actor
        );

        // Notification
        $this->notificationService->notifyMany(
            $this->getContractorStakeholders($contractor),
            'contractor.prequalified',
            [
                'contractor' => $contractor->fresh()->toArray(),
                'actor' => $actor->toArray(),
            ],
            $actor,
            'contractor',
            $contractor->id,
            route('contractors.show', $contractor)
        );

        return back()->with('success', 'Prequalification berhasil diaktifkan.');
    }

    /**
     * Revoke prequalification from a contractor.
     */
    public function revokePrequalified(Contractor $contractor, Request $request): RedirectResponse
    {
        $this->authorize('update', $contractor);

        if (!$contractor->is_prequalified) {
            return back()->withErrors([
                'prequalify' => 'Kontraktor belum prequalified.',
            ]);
        }

        $actor = $request->user();
        $oldValues = $contractor->toArray();

        $contractor->update([
            'is_prequalified' => false,
            'prequalified_until' => null,
        ]);

        // Audit trail
        $this->auditService->updated($contractor, $oldValues, $actor, 'contractor', $contractor->id);

        // Activity log
        $this->activityService->log(
            moduleName: 'contractor',
            referenceId: $contractor->id,
            event: 'contractor.prequalification_revoked',
            description: "Contractor {$contractor->contractor_number} prequalification revoked by {$actor->name}",
            actor: $actor
        );

        return back()->with('success', 'Prequalification berhasil dicabut.');
    }

    /**
     * Calculate safety rating based on 3 latest evaluations.
     */
    private function calculateSafetyRating(Contractor $contractor): ?string
    {
        $evaluations = $contractor->evaluations()
            ->orderBy('evaluation_date', 'desc')
            ->limit(3)
            ->get();

        if ($evaluations->isEmpty()) {
            return null;
        }

        $avgScore = (float) $evaluations->avg('total_score');

        return match (true) {
            $avgScore >= 85 => 'excellent',
            $avgScore >= 70 => 'good',
            $avgScore >= 55 => 'fair',
            default => 'poor',
        };
    }

    /**
     * Recipients: QHSSE Manager(s).
     */
    private function getQhsseManagers()
    {
        return User::role('QHSSE Manager')->get();
    }

    /**
     * Recipients: contractor creator + QHSSE Managers/Officers.
     */
    private function getContractorStakeholders(Contractor $contractor)
    {
        $recipients = User::role(['QHSSE Manager', 'QHSSE Officer'])->get();

        if ($contractor->created_by) {
            $creator = User::find($contractor->created_by);
            if ($creator && !$recipients->contains('id', $creator->id)) {
                $recipients->push($creator);
            }
        }

        return $recipients;
    }

    public function destroy(Contractor $contractor): RedirectResponse
    {
        $this->authorize('delete', $contractor);

        $companyName = $contractor->company_name;

        $this->auditService->deleted($contractor, request()->user(), 'contractor', $contractor->id);

        $this->activityService->log(
            moduleName: 'contractor',
            referenceId: $contractor->id,
            event: 'contractor.deleted',
            description: "Contractor {$companyName} deleted",
            actor: request()->user()
        );

        $contractor->delete();

        return redirect()->route('contractors.index')
            ->with('success', "Contractor {$companyName} berhasil dihapus.");
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Contractor::class);

        $query = $this->access->scope(
            Contractor::query()
                ->with(['createdBy:id,name', 'approvedBy:id,name'])
                ->orderBy('created_at', 'desc'),
            $request->user()
        );

        // Apply same filters as index
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('contractor_number', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('contract_status')) {
            $query->where('contract_status', $status);
        }

        if ($approvalStatus = $request->input('approval_status')) {
            $query->where('approval_status', $approvalStatus);
        }

        $contractors = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contractors-export-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($contractors) {
            $file = fopen('php://output', 'w');

            // CSV Header
            fputcsv($file, [
                'Contractor Number',
                'Company Name',
                'Contact Person',
                'Contact Phone',
                'Contact Email',
                'Business Type',
                'Contract Status',
                'Contract Start',
                'Contract End',
                'Approval Status',
                'Safety Induction Valid',
                'Insurance Valid',
                'Performance Rating',
                'Incident Count',
                'Created By',
                'Created At',
            ]);

            // CSV Data
            foreach ($contractors as $contractor) {
                fputcsv($file, [
                    $contractor->contractor_number,
                    $contractor->company_name,
                    $contractor->contact_person,
                    $contractor->contact_phone,
                    $contractor->contact_email,
                    $contractor->business_type,
                    $contractor->contract_status_label,
                    $contractor->contract_start_date?->format('Y-m-d'),
                    $contractor->contract_end_date?->format('Y-m-d'),
                    $contractor->approval_status_label,
                    $contractor->is_safety_induction_valid ? 'Yes' : 'No',
                    $contractor->is_insurance_valid ? 'Yes' : 'No',
                    $contractor->performance_rating,
                    $contractor->incident_count,
                    $contractor->createdBy?->name,
                    $contractor->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

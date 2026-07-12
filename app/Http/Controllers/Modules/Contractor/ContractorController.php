<?php

namespace App\Http\Controllers\Modules\Contractor;

use App\Core\Activity\ActivityService;
use App\Core\Numbering\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Contractor\StoreContractorRequest;
use App\Http\Requests\Modules\Contractor\UpdateContractorRequest;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Contractor\Contractor;
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
    ) {
        $this->authorizeResource(Contractor::class, 'contractor');
    }

    public function index(Request $request): InertiaResponse
    {
        $query = Contractor::query()
            ->with(['createdBy:id,name', 'approvedBy:id,name'])
            ->orderBy('created_at', 'desc');

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
        $validated = $request->validated();
        $validated['updated_by'] = $request->user()->id;

        // Handle approval status change
        $oldApprovalStatus = $contractor->approval_status;
        $newApprovalStatus = $validated['approval_status'];

        if ($oldApprovalStatus !== $newApprovalStatus && $newApprovalStatus === 'approved') {
            $validated['approved_by'] = $request->user()->id;
            $validated['approved_at'] = now();
        }

        $contractor->update($validated);

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

    public function destroy(Contractor $contractor): RedirectResponse
    {
        $companyName = $contractor->company_name;

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

        $query = Contractor::query()
            ->with(['createdBy:id,name', 'approvedBy:id,name'])
            ->orderBy('created_at', 'desc');

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

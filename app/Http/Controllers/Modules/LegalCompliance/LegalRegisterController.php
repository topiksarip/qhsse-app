<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\LegalCompliance;

use App\Core\Activity\ActivityService;
use App\Core\Numbering\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\LegalCompliance\StoreLegalRegisterRequest;
use App\Http\Requests\Modules\LegalCompliance\UpdateLegalRegisterRequest;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\Modules\LegalCompliance\LegalRegister;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalRegisterController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly ActivityService $activityService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LegalRegister::class);

        $query = LegalRegister::query()
            ->with(['site', 'department', 'owner'])
            ->active();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('register_number', 'ilike', "%{$search}%")
                    ->orWhere('title', 'ilike', "%{$search}%")
                    ->orWhere('regulation_name', 'ilike', "%{$search}%")
                    ->orWhere('regulation_number', 'ilike', "%{$search}%");
            });
        }

        // Filters
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($complianceStatus = $request->input('compliance_status')) {
            $query->where('compliance_status', $complianceStatus);
        }

        if ($siteId = $request->input('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($ownerId = $request->input('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        // Scope filtering
        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Top Management', 'Auditor'])) {
            if ($user->hasRole('QHSSE Officer')) {
                $query->whereIn('site_id', $user->employee->sites->pluck('id'));
            } elseif ($user->hasAnyRole(['Supervisor', 'Department Head'])) {
                $query->where('department_id', $user->employee->department_id);
            } else {
                $query->where('owner_id', $user->id);
            }
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $registers = $query->paginate(15);

        return Inertia::render('Modules/LegalCompliance/Index', [
            'registers' => $registers,
            'filters' => $request->only(['search', 'category', 'compliance_status', 'site_id', 'department_id', 'owner_id', 'sort_by', 'sort_order']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LegalRegister::class);

        $sites = Site::where('is_active', true)->get(['id', 'name']);
        $departments = Department::where('is_active', true)->get(['id', 'name']);
        $users = User::where('is_active', true)->get(['id', 'name']);
        $documents = ControlledDocument::where('status', 'approved')->get(['id', 'document_number', 'title']);

        return Inertia::render('Modules/LegalCompliance/Create', [
            'sites' => $sites,
            'departments' => $departments,
            'users' => $users,
            'documents' => $documents,
        ]);
    }

    public function store(StoreLegalRegisterRequest $request): RedirectResponse
    {
        $this->authorize('create', LegalRegister::class);

        $validated = $request->validated();

        $generated = $this->numberingService->generate('legal', $request->user());
        $validated['register_number'] = $generated->number;

        // Set default compliance status if not provided
        $validated['compliance_status'] = $validated['compliance_status'] ?? 'in_progress';

        $register = LegalRegister::create($validated);

        $this->activityService->log(
            'legal',
            $register->id,
            'legal.register.created',
            "Register {$register->register_number} dibuat",
            $request->user(),
        );

        return redirect()->route('legal.registers.show', $register)
            ->with('success', "Register {$register->register_number} berhasil dibuat.");
    }

    public function show(LegalRegister $register): Response
    {
        $this->authorize('view', $register);

        $register->load([
            'site',
            'department',
            'owner',
            'document',
            'obligations.evidenceFile',
            'files',
            'comments.author',
            'activities.actor',
        ]);

        return Inertia::render('Modules/LegalCompliance/Show', [
            'register' => $register,
        ]);
    }

    public function edit(LegalRegister $register): Response
    {
        $this->authorize('update', $register);

        $sites = Site::where('is_active', true)->get(['id', 'name']);
        $departments = Department::where('is_active', true)->get(['id', 'name']);
        $users = User::where('is_active', true)->get(['id', 'name']);
        $documents = ControlledDocument::where('status', 'approved')->get(['id', 'document_number', 'title']);

        return Inertia::render('Modules/LegalCompliance/Edit', [
            'register' => $register,
            'sites' => $sites,
            'departments' => $departments,
            'users' => $users,
            'documents' => $documents,
        ]);
    }

    public function update(UpdateLegalRegisterRequest $request, LegalRegister $register): RedirectResponse
    {
        $this->authorize('update', $register);

        $oldComplianceStatus = $register->compliance_status;

        $register->update($request->validated());

        // Log compliance status change
        if ($oldComplianceStatus !== $register->compliance_status) {
            $this->activityService->log(
                'legal',
                $register->id,
                'legal.compliance.changed',
                'Status kepatuhan register diperbarui',
                $request->user(),
                [
                    'old_status' => $oldComplianceStatus,
                    'new_status' => $register->compliance_status,
                ],
            );

            // Send notification if changed to non_compliant
            if ($register->compliance_status === 'non_compliant') {
                // TODO: Send notification to QHSSE Manager
            }
        } else {
            $this->activityService->log(
                'legal',
                $register->id,
                'legal.register.updated',
                "Register {$register->register_number} diperbarui",
                $request->user(),
            );
        }

        return redirect()->route('legal.registers.show', $register)
            ->with('success', 'Register berhasil diperbarui.');
    }

    public function destroy(LegalRegister $register): RedirectResponse
    {
        $this->authorize('delete', $register);

        $registerNumber = $register->register_number;

        $this->activityService->log(
            'legal',
            $register->id,
            'legal.register.deleted',
            "Register {$registerNumber} dihapus",
            request()->user(),
        );

        $register->delete();

        return redirect()->route('legal.registers.index')
            ->with('success', "Register {$registerNumber} berhasil dihapus.");
    }

    public function export(Request $request)
    {
        $this->authorize('export', LegalRegister::class);

        $query = LegalRegister::query()
            ->with(['site', 'department', 'owner'])
            ->active();

        // Apply same filters as index
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('register_number', 'ilike', "%{$search}%")
                    ->orWhere('title', 'ilike', "%{$search}%")
                    ->orWhere('regulation_name', 'ilike', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($complianceStatus = $request->input('compliance_status')) {
            $query->where('compliance_status', $complianceStatus);
        }

        // Scope filtering
        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Top Management', 'Auditor'])) {
            if ($user->hasRole('QHSSE Officer')) {
                $query->whereIn('site_id', $user->employee->sites->pluck('id'));
            } elseif ($user->hasAnyRole(['Supervisor', 'Department Head'])) {
                $query->where('department_id', $user->employee->department_id);
            } else {
                $query->where('owner_id', $user->id);
            }
        }

        $registers = $query->get();

        $filename = 'legal_register_export_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($registers) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, [
                'Nomor Register',
                'Judul',
                'Nama Regulasi',
                'Nomor Regulasi',
                'Instansi Penerbit',
                'Kategori',
                'Status Kepatuhan',
                'Site',
                'Department',
                'Owner',
                'Tanggal Review',
                'Dokumen',
                'Status',
                'Dibuat',
            ]);

            foreach ($registers as $register) {
                fputcsv($file, [
                    $register->register_number,
                    $register->title,
                    $register->regulation_name,
                    $register->regulation_number,
                    $register->issuing_body,
                    $register->category_label,
                    $register->compliance_status_label,
                    $register->site?->name ?? '-',
                    $register->department?->name ?? '-',
                    $register->owner->name,
                    $register->next_review_date?->format('Y-m-d') ?? '-',
                    $register->document?->document_number ?? '-',
                    $register->status === 'active' ? 'Aktif' : 'Tidak Aktif',
                    $register->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

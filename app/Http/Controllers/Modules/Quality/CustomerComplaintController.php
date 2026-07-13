<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\Quality;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Numbering\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Quality\CloseCustomerComplaintRequest;
use App\Http\Requests\Modules\Quality\CreateCustomerComplaintRequest;
use App\Http\Requests\Modules\Quality\UpdateCustomerComplaintRequest;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Quality\CustomerComplaint;
use App\Modules\Quality\ComplaintAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerComplaintController extends Controller
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly ComplaintAccess $access,
        private readonly AuditService $audit,
        private readonly ActivityService $activity,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', CustomerComplaint::class);

        $query = $this->access->scope(Auth::user(), CustomerComplaint::query()->with(['site', 'severity']));

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('complaint_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($siteId = request('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($severityId = request('severity_id')) {
            $query->where('severity_id', $severityId);
        }

        $perPage = (int) request('per_page', 15);
        $complaints = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return Inertia::render('Modules/Quality/CustomerComplaint/Index', [
            'complaints' => $complaints,
            'filters' => [
                'search' => request('search'),
                'site_id' => request('site_id'),
                'status' => request('status'),
                'severity_id' => request('severity_id'),
            ],
            'sites' => $this->sites(),
            'severities' => Severity::orderBy('name')->get(['id', 'name', 'color']),
            'can' => [
                'create' => Auth::user()->can('create', CustomerComplaint::class),
                'export' => Auth::user()->can('export', CustomerComplaint::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CustomerComplaint::class);

        return Inertia::render('Modules/Quality/CustomerComplaint/Form', [
            'complaint' => null,
            'sites' => $this->sites(),
            'severities' => Severity::orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(CreateCustomerComplaintRequest $request): RedirectResponse
    {
        $this->authorize('create', CustomerComplaint::class);

        $complaintNumber = $this->numbering->generate('quality_complaint', $request->user());

        $complaint = DB::transaction(function () use ($request, $complaintNumber): CustomerComplaint {
            $complaint = CustomerComplaint::create([
                ...$request->validated(),
                'complaint_number' => $complaintNumber->number,
                'status' => 'open',
            ]);
            $this->audit->created($complaint, $request->user(), 'quality_complaint', $complaint->id);
            $this->activity->log('quality_complaint', $complaint->id, 'created', 'Complaint customer dicatat.', $request->user());

            return $complaint;
        });

        return redirect()->route('quality.complaints.show', $complaint)
            ->with('success', 'Complaint berhasil dicatat.');
    }

    public function show(CustomerComplaint $complaint): Response
    {
        $this->authorize('view', $complaint);

        $complaint->load(['site', 'severity', 'ncr']);

        return Inertia::render('Modules/Quality/CustomerComplaint/Show', [
            'complaint' => $complaint,
            'can' => [
                'update' => Auth::user()->can('update', $complaint),
                'close' => Auth::user()->can('close', $complaint),
            ],
        ]);
    }

    public function edit(CustomerComplaint $complaint): Response
    {
        $this->authorize('update', $complaint);

        return Inertia::render('Modules/Quality/CustomerComplaint/Form', [
            'complaint' => $complaint->load('site'),
            'sites' => $this->sites(),
            'severities' => Severity::orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function update(UpdateCustomerComplaintRequest $request, CustomerComplaint $complaint): RedirectResponse
    {
        $this->authorize('update', $complaint);

        $old = $complaint->getOriginal();
        $complaint->update($request->validated());
        $this->audit->updated($complaint, $old, $request->user(), 'quality_complaint', $complaint->id);
        $this->activity->log('quality_complaint', $complaint->id, 'updated', 'Complaint customer diperbarui.', $request->user());

        return redirect()->route('quality.complaints.show', $complaint)
            ->with('success', 'Complaint berhasil diperbarui.');
    }

    public function close(CloseCustomerComplaintRequest $request, CustomerComplaint $complaint): RedirectResponse
    {
        $this->authorize('close', $complaint);

        DB::transaction(function () use ($request, $complaint): void {
            $locked = CustomerComplaint::query()->lockForUpdate()->findOrFail($complaint->id);
            abort_unless($locked->isOpen(), 409, 'Complaint sudah ditutup.');
            $old = $locked->getOriginal();
            $locked->update([
                'status' => 'closed',
                'closed_at' => now(),
                'resolution' => $request->validated('resolution'),
            ]);
            $this->audit->updated($locked, $old, $request->user(), 'quality_complaint', $locked->id);
            $this->activity->log('quality_complaint', $locked->id, 'closed', 'Complaint customer ditutup.', $request->user());
        });

        return redirect()->route('quality.complaints.show', $complaint)
            ->with('success', 'Complaint berhasil ditutup.');
    }

    public function export(): StreamedResponse
    {
        $this->authorize('export', CustomerComplaint::class);

        $query = $this->access->scope(Auth::user(), CustomerComplaint::query()->with(['site', 'severity']));

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('complaint_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($siteId = request('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $complaints = $query->orderBy('created_at', 'desc')->get();

        $headers = ['Nomor', 'Nama Customer', 'Kontak', 'Judul', 'Produk/Layanan', 'Site', 'Severity', 'Status', 'Tanggal'];

        $data = $complaints->map(fn ($c) => [
            $c->complaint_number,
            $c->customer_name,
            $c->customer_contact,
            $c->title,
            $c->product_service ?? '—',
            $c->site->name ?? '—',
            $c->severity->name ?? '—',
            $c->status,
            $c->created_at->format('d/m/Y'),
        ]);

        $filename = 'customer_complaints_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($headers, $data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function sites()
    {
        $ids = $this->access->allowedSiteIds(Auth::user());

        return Site::query()->where('is_active', true)
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->orderBy('name')->get(['id', 'name']);
    }
}

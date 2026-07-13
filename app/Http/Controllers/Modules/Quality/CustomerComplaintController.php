<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\Quality;

use App\Core\Numbering\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Quality\CreateCustomerComplaintRequest;
use App\Http\Requests\Modules\Quality\UpdateCustomerComplaintRequest;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Quality\CustomerComplaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CustomerComplaintController extends Controller
{
    public function __construct(
        private readonly NumberingService $numbering
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', CustomerComplaint::class);

        $query = CustomerComplaint::query()->with(['site', 'severity']);

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
            'sites' => Site::orderBy('name')->get(['id', 'name']),
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
            'sites' => Site::orderBy('name')->get(['id', 'name']),
            'severities' => Severity::orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(CreateCustomerComplaintRequest $request): RedirectResponse
    {
        $this->authorize('create', CustomerComplaint::class);

        $complaintNumber = $this->numbering->generate('quality_complaint', $request->user());

        $complaint = CustomerComplaint::create([
            ...$request->validated(),
            'complaint_number' => $complaintNumber->number,
            'status' => 'open',
        ]);

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
            'sites' => Site::orderBy('name')->get(['id', 'name']),
            'severities' => Severity::orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function update(UpdateCustomerComplaintRequest $request, CustomerComplaint $complaint): RedirectResponse
    {
        $this->authorize('update', $complaint);

        $complaint->update($request->validated());

        return redirect()->route('quality.complaints.show', $complaint)
            ->with('success', 'Complaint berhasil diperbarui.');
    }

    public function close(CustomerComplaint $complaint): RedirectResponse
    {
        $this->authorize('close', $complaint);

        $complaint->update([
            'status' => 'closed',
            'closed_at' => now(),
            'resolution' => request('resolution'),
        ]);

        return redirect()->route('quality.complaints.show', $complaint)
            ->with('success', 'Complaint berhasil ditutup.');
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('export', CustomerComplaint::class);

        $query = CustomerComplaint::query()->with(['site', 'severity']);

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

        $data = $complaints->map(fn($c) => [
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

        $filename = 'customer_complaints_' . now()->format('Ymd_His') . '.csv';

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
}

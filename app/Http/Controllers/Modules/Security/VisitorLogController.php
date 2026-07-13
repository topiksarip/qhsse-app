<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Security\CreateVisitorLogRequest;
use App\Http\Requests\Modules\Security\UpdateVisitorLogRequest;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Security\VisitorLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VisitorLogController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', VisitorLog::class);

        $query = VisitorLog::query()
            ->with(['site', 'hostEmployee', 'checkedInBy', 'checkedOutBy']);

        // Search
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_company', 'like', "%{$search}%")
                    ->orWhere('visitor_id_number', 'like', "%{$search}%")
                    ->orWhereHas('hostEmployee', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Filters
        if ($siteId = request('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($status = request('status')) {
            if ($status === 'checked_in') {
                $query->checkedIn();
            } elseif ($status === 'checked_out') {
                $query->checkedOut();
            }
        }

        if ($type = request('visitor_type')) {
            $query->where('visitor_type', $type);
        }

        if ($from = request('from')) {
            $query->whereDate('checked_in_at', '>=', $from);
        }

        if ($to = request('to')) {
            $query->whereDate('checked_in_at', '<=', $to);
        }

        // Pagination
        $perPage = (int) request('per_page', 15);
        $visitors = $query->orderBy('checked_in_at', 'desc')->paginate($perPage)->withQueryString();

        return Inertia::render('Modules/Security/VisitorLog/Index', [
            'visitors' => $visitors,
            'filters' => [
                'search' => request('search'),
                'site_id' => request('site_id'),
                'status' => request('status'),
                'visitor_type' => request('visitor_type'),
                'from' => request('from'),
                'to' => request('to'),
            ],
            'sites' => Site::orderBy('name')->get(['id', 'name']),
            'can' => [
                'create' => Auth::user()->can('create', VisitorLog::class),
                'export' => Auth::user()->can('export', VisitorLog::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VisitorLog::class);

        return Inertia::render('Modules/Security/VisitorLog/Form', [
            'visitor' => null,
            'sites' => Site::orderBy('name')->get(['id', 'name']),
            'employees' => Employee::with('user')->orderBy('name')->get(['id', 'name', 'user_id']),
        ]);
    }

    public function store(CreateVisitorLogRequest $request): RedirectResponse
    {
        $this->authorize('create', VisitorLog::class);

        $visitor = VisitorLog::create([
            ...$request->validated(),
            'checked_in_by' => Auth::id(),
            'status' => 'checked_in',
        ]);

        return redirect()->route('security.visitors.show', $visitor)
            ->with('success', 'Pengunjung berhasil di-check-in.');
    }

    public function show(VisitorLog $visitor): Response
    {
        $this->authorize('view', $visitor);

        $visitor->load(['site', 'hostEmployee', 'checkedInBy', 'checkedOutBy']);

        return Inertia::render('Modules/Security/VisitorLog/Show', [
            'visitor' => $visitor,
            'can' => [
                'update' => Auth::user()->can('update', $visitor),
                'checkOut' => Auth::user()->can('checkOut', $visitor),
            ],
        ]);
    }

    public function edit(VisitorLog $visitor): Response
    {
        $this->authorize('update', $visitor);

        return Inertia::render('Modules/Security/VisitorLog/Form', [
            'visitor' => $visitor->load(['site', 'hostEmployee']),
            'sites' => Site::orderBy('name')->get(['id', 'name']),
            'employees' => Employee::with('user')->orderBy('name')->get(['id', 'name', 'user_id']),
        ]);
    }

    public function update(UpdateVisitorLogRequest $request, VisitorLog $visitor): RedirectResponse
    {
        $this->authorize('update', $visitor);

        $visitor->update($request->validated());

        return redirect()->route('security.visitors.show', $visitor)
            ->with('success', 'Data pengunjung berhasil diperbarui.');
    }

    public function checkOut(VisitorLog $visitor): RedirectResponse
    {
        $this->authorize('checkOut', $visitor);

        $visitor->update([
            'checked_out_at' => now(),
            'checked_out_by' => Auth::id(),
            'status' => 'checked_out',
        ]);

        return redirect()->route('security.visitors.show', $visitor)
            ->with('success', 'Pengunjung berhasil di-check-out.');
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('export', VisitorLog::class);

        $query = VisitorLog::query()
            ->with(['site', 'hostEmployee', 'checkedInBy', 'checkedOutBy']);

        // Apply same filters as index
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_company', 'like', "%{$search}%")
                    ->orWhere('visitor_id_number', 'like', "%{$search}%")
                    ->orWhereHas('hostEmployee', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($siteId = request('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($status = request('status')) {
            if ($status === 'checked_in') {
                $query->checkedIn();
            } elseif ($status === 'checked_out') {
                $query->checkedOut();
            }
        }

        if ($type = request('visitor_type')) {
            $query->where('visitor_type', $type);
        }

        if ($from = request('from')) {
            $query->whereDate('checked_in_at', '>=', $from);
        }

        if ($to = request('to')) {
            $query->whereDate('checked_in_at', '<=', $to);
        }

        $visitors = $query->orderBy('checked_in_at', 'desc')->get();

        $headers = [
            'Nama Pengunjung',
            'Perusahaan',
            'Tipe',
            'Nomor ID',
            'Telepon',
            'Host',
            'Site',
            'Tujuan',
            'Plat Kendaraan',
            'Check-In',
            'Check-Out',
            'Status',
            'Petugas Check-In',
            'Petugas Check-Out',
        ];

        $data = $visitors->map(fn($v) => [
            $v->visitor_name,
            $v->visitor_company ?? '—',
            $v->visitor_type,
            $v->visitor_id_number ?? '—',
            $v->visitor_phone ?? '—',
            $v->hostEmployee->name ?? '—',
            $v->site->name ?? '—',
            $v->purpose,
            $v->vehicle_number ?? '—',
            $v->checked_in_at?->format('d/m/Y H:i'),
            $v->checked_out_at?->format('d/m/Y H:i') ?? '—',
            $v->status,
            $v->checkedInBy->name ?? '—',
            $v->checkedOutBy->name ?? '—',
        ]);

        $filename = 'visitor_logs_' . now()->format('Ymd_His') . '.csv';

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

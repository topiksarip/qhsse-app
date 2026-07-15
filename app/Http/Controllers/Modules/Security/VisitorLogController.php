<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\Security;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Security\CreateVisitorLogRequest;
use App\Http\Requests\Modules\Security\UpdateVisitorLogRequest;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Security\VisitorLog;
use App\Modules\Security\VisitorAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitorLogController extends Controller
{
    public function __construct(
        private readonly VisitorAccess $access,
        private readonly AuditService $audit,
        private readonly ActivityService $activity,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', VisitorLog::class);

        $query = $this->access->scope(Auth::user(), VisitorLog::query()
            ->with(['site', 'hostEmployee', 'checkedInBy', 'checkedOutBy']));

        // Search
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_company', 'like', "%{$search}%")
                    ->orWhere('visitor_id_number', 'like', "%{$search}%")
                    ->orWhereHas('hostEmployee', fn ($q) => $q->where('name', 'like', "%{$search}%"));
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
            'sites' => $this->sites(),
            'can' => [
                'create' => $request->user()->can('create', VisitorLog::class),
                'export' => $request->user()->can('export', VisitorLog::class),
                'delete' => $request->user()->can('delete', VisitorLog::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VisitorLog::class);

        return Inertia::render('Modules/Security/VisitorLog/Form', [
            'visitor' => null,
            'sites' => $this->sites(),
            'employees' => $this->employees(),
        ]);
    }

    public function store(CreateVisitorLogRequest $request): RedirectResponse
    {
        $this->authorize('create', VisitorLog::class);

        $this->access->ensureSiteAllowed($request->user(), (int) $request->validated('site_id'));
        $visitor = DB::transaction(function () use ($request): VisitorLog {
            $visitor = VisitorLog::create([
                ...$request->validated(),
                'checked_in_by' => Auth::id(),
                'status' => 'checked_in',
            ]);
            $this->audit->created($visitor, $request->user(), 'security_visitor', $visitor->id);
            $this->activity->log('security_visitor', $visitor->id, 'checked_in', 'Pengunjung di-check-in.', $request->user());

            return $visitor;
        });

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
            'sites' => $this->sites(),
            'employees' => $this->employees(),
        ]);
    }

    public function update(UpdateVisitorLogRequest $request, VisitorLog $visitor): RedirectResponse
    {
        $this->authorize('update', $visitor);

        $this->access->ensureSiteAllowed($request->user(), (int) $request->validated('site_id'));
        $old = $visitor->getOriginal();
        $visitor->update($request->validated());
        $this->audit->updated($visitor, $old, $request->user(), 'security_visitor', $visitor->id);
        $this->activity->log('security_visitor', $visitor->id, 'updated', 'Data pengunjung diperbarui.', $request->user());

        return redirect()->route('security.visitors.show', $visitor)
            ->with('success', 'Data pengunjung berhasil diperbarui.');
    }

    public function checkOut(VisitorLog $visitor): RedirectResponse
    {
        $this->authorize('checkOut', $visitor);

        DB::transaction(function () use ($visitor): void {
            $locked = VisitorLog::query()->lockForUpdate()->findOrFail($visitor->id);
            abort_unless($locked->status === 'checked_in' && $locked->checked_out_at === null, 409, 'Pengunjung sudah di-check-out.');
            $old = $locked->getOriginal();
            $locked->update([
                'checked_out_at' => now(),
                'checked_out_by' => Auth::id(),
                'status' => 'checked_out',
            ]);
            $this->audit->updated($locked, $old, Auth::user(), 'security_visitor', $locked->id);
            $this->activity->log('security_visitor', $locked->id, 'checked_out', 'Pengunjung di-check-out.', Auth::user());
        });

        return redirect()->route('security.visitors.show', $visitor)
            ->with('success', 'Pengunjung berhasil di-check-out.');
    }

    public function destroy(VisitorLog $visitor): RedirectResponse
    {
        $this->authorize('delete', $visitor);

        DB::transaction(function () use ($visitor): void {
            $name = $visitor->visitor_name;
            $visitor->delete();
            $this->audit->deleted($visitor, Auth::user(), 'security_visitor', $visitor->id);
            $this->activity->log('security_visitor', $visitor->id, 'deleted', "Pengunjung {$name} dihapus.", Auth::user());
        });

        return redirect()->route('security.visitors.index')->with('success', 'Data pengunjung berhasil dihapus.');
    }

    public function export(): StreamedResponse
    {
        $this->authorize('export', VisitorLog::class);

        $query = $this->access->scope(Auth::user(), VisitorLog::query()
            ->with(['site', 'hostEmployee', 'checkedInBy', 'checkedOutBy']));

        // Apply same filters as index
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_company', 'like', "%{$search}%")
                    ->orWhere('visitor_id_number', 'like', "%{$search}%")
                    ->orWhereHas('hostEmployee', fn ($q) => $q->where('name', 'like', "%{$search}%"));
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

        $data = $visitors->map(fn ($v) => [
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

        $filename = 'visitor_logs_'.now()->format('Ymd_His').'.csv';

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

    private function employees()
    {
        $ids = $this->access->allowedSiteIds(Auth::user());

        return Employee::query()->where('is_active', true)
            ->when($ids !== null, fn ($query) => $query->whereIn('site_id', $ids))
            ->orderBy('name')->get(['id', 'name', 'site_id']);
    }
}

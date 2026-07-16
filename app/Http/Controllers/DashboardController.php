<?php

namespace App\Http\Controllers;

use App\Core\Query\DatePeriodExpression;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetCertificate;
use App\Models\Modules\Apd\ApdItem;
use App\Models\Modules\Apd\ApdInspection;
use App\Models\Modules\Apd\ApdCatalog;
use App\Modules\Apd\ApdAccess;
use App\Models\Modules\Capa\CapaAction;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\Inspection\Inspection;
use App\Models\Modules\Investigation\Investigation;
use App\Models\User;
use App\Modules\Asset\AssetAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly AssetAccess $assetAccess, private readonly ApdAccess $apdAccess) {}

    public function __invoke(Request $request): Response
    {
        $siteId = $request->integer('site_id') ?: null;
        $departmentId = $request->integer('department_id') ?: null;
        $from = Carbon::parse($request->query('from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->query('to', now()->toDateString()));

        $kpis = $this->buildKpis($request, $siteId, $departmentId, $from, $to);
        $widgets = $this->buildWidgets($siteId, $from, $to);

        if ($request->user()?->can('apd.view')) {
            $kpis = array_merge($kpis, $this->buildApdKpis($request->user(), $siteId, $departmentId));
        }
        return Inertia::render('Dashboard', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'site_id' => $siteId,
                'department_id' => $departmentId,
            ],
            'filterOptions' => [
                'sites' => Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            ],
            'kpis' => $kpis,
            'widgets' => $widgets,
            'quickLinks' => [
                ['label' => 'Laporan Insiden', 'route' => 'incident.reports.index', 'permission' => 'incident.reports.view'],
                ['label' => 'Investigasi', 'route' => 'investigation.reports.index', 'permission' => 'investigation.reports.view'],
                ['label' => 'CAPA / Action', 'route' => 'capa.actions.index', 'permission' => 'capa.actions.view'],
                ['label' => 'Inspeksi', 'route' => 'inspection.checklists.index', 'permission' => 'inspection.checklists.view'],
                ['label' => 'Asset & Equipment', 'route' => 'assets.index', 'permission' => 'asset.management.view'],
                ['label' => 'Sites', 'route' => 'core.sites.index', 'permission' => 'core.sites.view'],
                ['label' => 'Notifications', 'route' => 'core.notifications.index', 'permission' => 'core.notifications.view'],
            ],
            'notificationSummary' => [
                'unread' => CoreNotification::query()
                    ->where('recipient_id', $request->user()?->id)
                    ->whereNull('read_at')
                    ->count(),
            ],
        ]);
    }

    private function applySiteDept($query, ?int $siteId, ?int $departmentId): void
    {
        if ($siteId) {
            $query->where('site_id', $siteId);
        }
        if ($departmentId && in_array('department_id', $query->getModel()->getFillable())) {
            $query->where('department_id', $departmentId);
        }
    }

    private function buildKpis(Request $request, ?int $siteId, ?int $departmentId, Carbon $from, Carbon $to): array
    {
        $kpis = [];

        // 1. Total Incidents (in date range)
        $incidentQuery = IncidentReport::query()
            ->whereBetween('occurred_at', [$from->startOfDay(), $to->endOfDay()]);
        $this->applySiteDept($incidentQuery, $siteId, $departmentId);
        $totalIncidents = (clone $incidentQuery)->count();
        $openIncidents = IncidentReport::query()
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->count();
        $kpis[] = ['label' => 'Insiden', 'value' => $totalIncidents, 'sub' => "{$openIncidents} masih open", 'tone' => 'amber'];

        // 2. Investigations
        $openInvestigations = Investigation::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
        $completedInvestigations = Investigation::query()
            ->where('status', 'completed')
            ->count();
        $kpis[] = ['label' => 'Investigasi', 'value' => $openInvestigations, 'sub' => "{$completedInvestigations} selesai", 'tone' => 'indigo'];

        // 3. CAPA Actions
        $openCapas = CapaAction::query()
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->count();
        $overdueCapas = CapaAction::query()
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->where('due_date', '<', now()->toDateString())
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->count();
        $kpis[] = ['label' => 'CAPA Open', 'value' => $openCapas, 'sub' => $overdueCapas > 0 ? "{$overdueCapas} OVERDUE" : 'Tidak ada overdue', 'tone' => $overdueCapas > 0 ? 'amber' : 'emerald'];

        // 4. Inspections
        $pendingInspections = Inspection::query()
            ->where('status', 'pending')
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->count();
        $completedInspections = Inspection::query()
            ->where('status', 'completed')
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->count();
        $failedInspections = Inspection::query()
            ->where('status', 'completed')
            ->where('overall_result', 'fail')
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->count();
        $kpis[] = ['label' => 'Inspeksi', 'value' => $pendingInspections, 'sub' => "{$completedInspections} selesai, {$failedInspections} fail", 'tone' => 'sky'];

        // 5. Active Sites
        $kpis[] = ['label' => 'Site Aktif', 'value' => Site::query()->where('is_active', true)->count(), 'sub' => 'Operasional', 'tone' => 'emerald'];

        // 6. Employees
        $kpis[] = ['label' => 'Karyawan', 'value' => Employee::query()->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))->where('is_active', true)->count(), 'sub' => 'Aktif', 'tone' => 'indigo'];

        // 7. Active Users
        $kpis[] = ['label' => 'User Aktif', 'value' => User::query()->where('is_active', true)->count(), 'sub' => 'Login', 'tone' => 'sky'];

        // 8. Unread Notifications
        $kpis[] = ['label' => 'Notifikasi', 'value' => CoreNotification::query()->whereNull('read_at')->where('recipient_id', auth()->id())->count(), 'sub' => 'Belum dibaca', 'tone' => 'amber'];

        $assetQuery = $this->assetAccess->scope(Asset::query(), $request->user());
        $this->applySiteDept($assetQuery, $siteId, $departmentId);
        $assetIds = (clone $assetQuery)->select('assets.id');

        $totalAssets = (clone $assetQuery)->count();
        $safetyCritical = (clone $assetQuery)->where('safety_critical', true)->count();
        $expiredCertificates = AssetCertificate::query()
            ->activeRecords()
            ->whereIn('asset_id', clone $assetIds)
            ->where('status', 'expired')
            ->count();
        $expiringCertificates = AssetCertificate::query()
            ->activeRecords()
            ->whereIn('asset_id', clone $assetIds)
            ->whereIn('status', ['expiring_soon', 'expiring_critical'])
            ->count();
        $overdueInspections = (clone $assetQuery)
            ->whereDate('next_inspection_date', '<', today())
            ->count();
        $activeAssets = (clone $assetQuery)->where('status', 'active')->count();
        $decommissionedAssets = (clone $assetQuery)->where('status', 'decommissioned')->count();

        $kpis[] = ['label' => 'Total Aset', 'value' => $totalAssets, 'sub' => 'Dalam scope', 'tone' => 'indigo'];
        $kpis[] = ['label' => 'Safety-Critical Assets', 'value' => $safetyCritical, 'sub' => 'Prioritas tinggi', 'tone' => 'red'];
        $kpis[] = ['label' => 'Sertifikat Expired', 'value' => $expiredCertificates, 'sub' => 'Perlu tindakan', 'tone' => 'red'];
        $kpis[] = ['label' => 'Sertifikat Expiring Soon', 'value' => $expiringCertificates, 'sub' => 'Segera kedaluwarsa', 'tone' => 'amber'];
        $kpis[] = ['label' => 'Inspeksi Overdue', 'value' => $overdueInspections, 'sub' => 'Lewat jatuh tempo', 'tone' => 'amber'];
        $kpis[] = ['label' => 'Aset Aktif', 'value' => $activeAssets, 'sub' => 'Operasional', 'tone' => 'emerald'];
        $kpis[] = ['label' => 'Aset Decommissioned', 'value' => $decommissionedAssets, 'sub' => 'Riwayat permanen', 'tone' => 'indigo'];

        return $kpis;
    }

    private function buildApdKpis(User $user, ?int $siteId, ?int $departmentId): array
    {
        $itemQuery = $this->apdAccess->scope(ApdItem::query(), $user);
        if ($siteId) {
            $itemQuery->where('site_id', $siteId);
        }
        if ($departmentId) {
            $itemQuery->where('department_id', $departmentId);
        }

        // Low stock: catalogs whose active quantity (in_stock + issued) is below min_stock.
        $lowStockCatalogs = ApdCatalog::query()
            ->where('is_active', true)
            ->lowStock()
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->count();

        $damaged = (clone $itemQuery)->where('status', 'damaged')->count();
        $disposed = (clone $itemQuery)->where('status', 'disposed')->count();
        $lost = (clone $itemQuery)->where('status', 'lost')->count();

        $inspectionQuery = ApdInspection::query()
            ->whereHas('item', fn ($q) => $this->apdAccess->scope($q, $user)
                ->when($siteId, fn ($iq) => $iq->where('site_id', $siteId))
                ->when($departmentId, fn ($iq) => $iq->where('department_id', $departmentId)));

        $layak = (clone $inspectionQuery)->where('result', 'layak')->count();
        $tidakLayak = (clone $inspectionQuery)->where('result', 'tidak_layak')->count();

        $kpis = [];
        $kpis[] = [
            'label' => 'APD Stok Rendah',
            'value' => $lowStockCatalogs,
            'sub' => 'Di bawah batas minimum',
            'tone' => $lowStockCatalogs > 0 ? 'amber' : 'emerald',
        ];
        $kpis[] = [
            'label' => 'APD Rusak',
            'value' => $damaged,
            'sub' => $disposed > 0 ? "{$disposed} dimusnahkan" : 'Perlu diganti',
            'tone' => $damaged > 0 ? 'red' : 'emerald',
        ];
        $kpis[] = [
            'label' => 'APD Hilang',
            'value' => $lost,
            'sub' => 'Tidak di lokasi',
            'tone' => $lost > 0 ? 'amber' : 'emerald',
        ];
        $kpis[] = [
            'label' => 'Hasil Inspeksi',
            'value' => $layak,
            'sub' => "{$tidakLayak} tidak layak",
            'tone' => $tidakLayak > 0 ? 'amber' : 'emerald',
        ];

        return $kpis;
    }

    private function buildWidgets(?int $siteId, Carbon $from, Carbon $to): array
    {
        $widgets = [];

        // Widget 1: Incident Trend — monthly for last 6 months
        $monthExpression = DatePeriodExpression::month(DB::getDriverName(), 'occurred_at');
        $incidentTrend = IncidentReport::query()
            ->selectRaw("{$monthExpression} as month, COUNT(*) as cnt")
            ->where('occurred_at', '>=', now()->subMonths(5)->startOfMonth())
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->groupByRaw($monthExpression)
            ->pluck('cnt', 'month')
            ->toArray();

        $trendPoints = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $trendPoints[] = $incidentTrend[$month] ?? 0;
        }
        $widgets[] = [
            'title' => 'Tren Insiden (6 Bulan)',
            'description' => 'Jumlah insiden per bulan',
            'points' => $trendPoints,
            'labels' => collect(range(0, 5))->map(fn ($i) => now()->subMonths(5 - $i)->translatedFormat('M'))->toArray(),
        ];

        // Widget 2: CAPA Status Breakdown
        $capaStatus = CapaAction::query()
            ->selectRaw('status, COUNT(*) as cnt')
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $widgets[] = [
            'title' => 'Status CAPA',
            'description' => 'Distribusi status corrective/preventive action',
            'points' => [
                $capaStatus['open'] ?? 0,
                $capaStatus['in_progress'] ?? 0,
                $capaStatus['pending_verification'] ?? 0,
                $capaStatus['closed'] ?? 0,
            ],
            'labels' => ['Open', 'In Progress', 'Pending Verify', 'Closed'],
        ];

        // Widget 3: Inspection Results
        $inspectionResults = Inspection::query()
            ->selectRaw('overall_result, COUNT(*) as cnt')
            ->where('status', 'completed')
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->groupBy('overall_result')
            ->pluck('cnt', 'overall_result')
            ->toArray();

        $widgets[] = [
            'title' => 'Hasil Inspeksi',
            'description' => 'Pass vs Fail dari inspeksi yang selesai',
            'points' => [
                $inspectionResults['pass'] ?? 0,
                $inspectionResults['fail'] ?? 0,
                $inspectionResults['pending'] ?? 0,
            ],
            'labels' => ['Pass', 'Fail', 'Pending'],
        ];

        // Widget 4: Incident by Category
        $byCategory = IncidentReport::query()
            ->selectRaw('category, COUNT(*) as cnt')
            ->whereBetween('occurred_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->groupBy('category')
            ->orderByDesc('cnt')
            ->pluck('cnt', 'category')
            ->toArray();

        $widgets[] = [
            'title' => 'Insiden per Kategori',
            'description' => "Distribusi kategori insiden ({$from->format('d M')} – {$to->format('d M')})",
            'points' => array_values($byCategory),
            'labels' => array_keys($byCategory),
        ];

        return $widgets;
    }
}

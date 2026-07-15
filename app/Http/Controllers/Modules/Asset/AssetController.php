<?php

namespace App\Http\Controllers\Modules\Asset;

use App\Core\Activity\ActivityService;
use App\Core\Comments\CommentService;
use App\Core\Export\CsvExporter;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Asset\StoreAssetRequest;
use App\Http\Requests\Modules\Asset\UpdateAssetRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\Comments\Comment;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetCertificate;
use App\Models\Modules\Asset\AssetInspection;
use App\Modules\Asset\AssetAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly ActivityService $activityService,
        private readonly AssetAccess $access,
        private readonly CommentService $commentService,
        private readonly CsvExporter $csvExporter,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Asset::class);

        $query = $this->access->scope(
            Asset::query()
                ->with(['site', 'area', 'department', 'creator'])
                ->withCount([
                    'certificates',
                    'certificates as expired_certificates_count' => fn ($query) => $query->where('status', 'expired'),
                    'certificates as critical_certificates_count' => fn ($query) => $query->where('status', 'expiring_critical'),
                    'certificates as soon_certificates_count' => fn ($query) => $query->where('status', 'expiring_soon'),
                    'inspections as failed_inspections_without_capa' => fn ($query) => $query
                        ->where('result', 'fail')
                        ->whereDoesntHave('capaAction'),
                ]),
            $request->user(),
        );

        $listQuery = ListQuery::for($query, $request)
            ->search(['asset_number', 'name', 'serial_number', 'model', 'manufacturer'], $request->input('search'))
            ->filter('site_id', $request->input('site_id'))
            ->filter('category', $request->input('category'))
            ->filter('status', $request->input('status'))
            ->filter('safety_critical', $request->input('safety_critical'))
            ->defaultSort('-created_at');

        $assets = $listQuery->paginate(15);
        $assets->through(function (Asset $asset): Asset {
            $certificateStatus = match (true) {
                $asset->expired_certificates_count > 0 => 'expired',
                $asset->critical_certificates_count > 0 => 'expiring_critical',
                $asset->soon_certificates_count > 0 => 'expiring_soon',
                $asset->certificates_count > 0 => 'valid',
                default => null,
            };

            $asset->setAttribute('certificate_status', $certificateStatus);
            unset(
                $asset->certificates_count,
                $asset->expired_certificates_count,
                $asset->critical_certificates_count,
                $asset->soon_certificates_count,
            );

            return $asset;
        });

        return Inertia::render('Modules/Asset/Index', [
            'assets' => $assets,
            'filters' => $listQuery->filters(),
            'sites' => $this->access->sites($request->user()),
            'categories' => Asset::getCategories(),
            'statuses' => Asset::getStatuses(),
            'can' => [
                'create' => $request->user()->can('create', Asset::class),
                'export' => $request->user()->can('export', Asset::class),
                'delete' => $request->user()->can('delete', Asset::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Asset::class);

        return Inertia::render('Modules/Asset/CreateOrEdit', [
            'sites' => $this->access->sites($request->user()),
            'areas' => $this->access->areas($request->user()),
            'departments' => $this->access->departments($request->user()),
            'categories' => Asset::getCategories(),
            'statuses' => Asset::getStatuses(),
            'can' => ['create' => true],
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $asset = DB::transaction(function () use ($request): Asset {
            $validated = $request->validated();
            $generated = $this->numberingService->generate(
                moduleName: 'asset',
                actor: $request->user(),
                referenceType: Asset::class,
            );

            $validated['asset_number'] = $generated->number;
            $validated['created_by'] = $request->user()->id;
            $validated['updated_by'] = $request->user()->id;
            $validated['status'] = 'active';
            $validated['safety_critical'] = $validated['safety_critical'] ?? false;

            $asset = Asset::create($validated);
            $generated->update(['reference_id' => $asset->id]);

            $this->activityService->log(
                moduleName: 'asset',
                referenceId: $asset->id,
                action: 'asset.created',
                description: "Asset {$asset->asset_number} - {$asset->name} created",
                actor: $request->user(),
            );

            return $asset;
        });

        return redirect()->route('assets.show', $asset)->with('success', 'Asset created successfully.');
    }

    public function show(Request $request, Asset $asset): Response
    {
        $this->authorize('view', $asset);

        $asset->load(['site', 'area', 'department', 'creator', 'updater',
            'certificates' => fn ($q) => $q->orderBy('expiry_date'),
            'inspections' => fn ($q) => $q->with(['inspector', 'capaAction'])->orderBy('inspection_date', 'desc'),
        ]);

        return Inertia::render('Modules/Asset/Show', [
            'asset' => $asset,
            'comments' => Comment::query()
                ->where('module_name', 'asset')
                ->where('reference_id', $asset->id)
                ->active()
                ->with('author:id,name')
                ->oldest()
                ->get(),
            'activities' => ActivityLog::query()
                ->where('module_name', 'asset')
                ->where('reference_id', $asset->id)
                ->latest()
                ->get(),
            'auditLogs' => AuditLog::query()
                ->where('module_name', 'asset')
                ->where('auditable_id', $asset->id)
                ->latest()
                ->get(),
            'can' => [
                'update' => $request->user()->can('update', $asset),
                'decommission' => $request->user()->can('decommission', $asset),
                'changeStatus' => $request->user()->can('changeStatus', $asset),
                'comment' => $request->user()->can('core.comments.create'),
                'createCertificate' => $request->user()->can('create', [AssetCertificate::class, $asset]),
                'createInspection' => $request->user()->can('create', [AssetInspection::class, $asset]),
            ],
        ]);
    }

    public function edit(Request $request, Asset $asset): Response
    {
        $this->authorize('update', $asset);

        return Inertia::render('Modules/Asset/CreateOrEdit', [
            'asset' => [
                ...$asset->load(['site', 'area', 'department'])->toArray(),
                'purchase_date' => $asset->purchase_date?->toDateString(),
                'installation_date' => $asset->installation_date?->toDateString(),
                'warranty_expiry_date' => $asset->warranty_expiry_date?->toDateString(),
                'next_inspection_date' => $asset->next_inspection_date?->toDateString(),
            ],
            'sites' => $this->access->sites($request->user()),
            'areas' => $this->access->areas($request->user()),
            'departments' => $this->access->departments($request->user()),
            'categories' => Asset::getCategories(),
            'statuses' => Asset::getStatuses(),
            'can' => ['update' => true],
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validated();

        if (($validated['status'] ?? null) === 'decommissioned' && $asset->status !== 'decommissioned') {
            $this->authorize('decommission', $asset);
        }

        DB::transaction(function () use ($request, $asset, $validated): void {
            $validated['updated_by'] = $request->user()->id;
            $oldValues = $asset->only(array_keys($validated));
            $asset->update($validated);

            $this->activityService->log(
                moduleName: 'asset',
                referenceId: $asset->id,
                action: 'asset.updated',
                description: "Asset {$asset->asset_number} updated",
                actor: $request->user(),
                metadata: ['old' => $oldValues, 'new' => $validated],
            );
        });

        return redirect()->route('assets.show', $asset)->with('success', 'Asset updated successfully.');
    }

    public function status(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('changeStatus', $asset);
        $validated = $request->validate(['status' => ['required', 'in:active,inactive']]);

        abort_if($validated['status'] === $asset->status, 422, 'Status aset tidak berubah.');

        DB::transaction(function () use ($request, $asset, $validated): void {
            $asset->update(['status' => $validated['status'], 'updated_by' => $request->user()->id]);
            $this->activityService->log(
                moduleName: 'asset',
                referenceId: $asset->id,
                action: "asset.set_{$validated['status']}",
                description: "Asset {$asset->asset_number} set to {$validated['status']}",
                actor: $request->user(),
            );
        });

        return back()->with('success', 'Status aset berhasil diperbarui.');
    }

    public function decommission(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('decommission', $asset);

        DB::transaction(function () use ($request, $asset): void {
            $asset->update(['status' => 'decommissioned', 'updated_by' => $request->user()->id]);
            $this->activityService->log(
                moduleName: 'asset',
                referenceId: $asset->id,
                action: 'asset.decommissioned',
                description: "Asset {$asset->asset_number} decommissioned",
                actor: $request->user(),
            );
        });

        return back()->with('success', 'Aset berhasil di-decommission.');
    }

    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('delete', $asset);

        DB::transaction(function () use ($request, $asset): void {
            $assetNumber = $asset->asset_number;
            $asset->delete();
            $this->activityService->log(
                moduleName: 'asset',
                referenceId: $asset->id,
                action: 'asset.deleted',
                description: "Asset {$assetNumber} deleted",
                actor: $request->user(),
            );
        });

        return redirect()->route('assets.index')->with('success', 'Aset berhasil dihapus.');
    }

    public function comment(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('view', $asset);
        abort_unless($request->user()->can('core.comments.create'), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($asset): void {
                    if (! Comment::query()
                        ->whereKey($value)
                        ->where('module_name', 'asset')
                        ->where('reference_id', $asset->id)
                        ->active()
                        ->exists()) {
                        $fail('Komentar induk tidak valid untuk aset ini.');
                    }
                },
            ],
        ]);

        $this->commentService->add(
            moduleName: 'asset',
            referenceId: $asset->id,
            body: $validated['body'],
            author: $request->user(),
            parentId: $validated['parent_id'] ?? null,
        );

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Asset::class);

        $query = $this->access->scope(
            Asset::query()->with(['site', 'area', 'department', 'certificates', 'inspections']),
            $request->user(),
        );

        $listQuery = ListQuery::for($query, $request)
            ->search(['asset_number', 'name', 'serial_number'], $request->input('search'))
            ->filter('site_id', $request->input('site_id'))
            ->filter('category', $request->input('category'))
            ->filter('status', $request->input('status'))
            ->filter('safety_critical', $request->input('safety_critical'))
            ->defaultSort('-created_at');

        return $this->csvExporter->stream($query, [
            'Nomor Aset' => 'asset_number',
            'Nama' => 'name',
            'Kategori' => 'category',
            'Serial Number' => 'serial_number',
            'Model' => 'model',
            'Manufacturer' => 'manufacturer',
            'Site' => fn (Asset $asset) => $asset->site?->name,
            'Area' => fn (Asset $asset) => $asset->area?->name,
            'Department' => fn (Asset $asset) => $asset->department?->name,
            'Tanggal Pembelian' => fn (Asset $asset) => $asset->purchase_date?->format('Y-m-d'),
            'Tanggal Instalasi' => fn (Asset $asset) => $asset->installation_date?->format('Y-m-d'),
            'Masa Garansi' => fn (Asset $asset) => $asset->warranty_expiry_date?->format('Y-m-d'),
            'Status' => 'status',
            'Safety Critical' => fn (Asset $asset) => $asset->safety_critical ? 'Yes' : 'No',
            'Total Sertifikat' => fn (Asset $asset) => $asset->certificates->count(),
            'Sertifikat Expired' => fn (Asset $asset) => $asset->certificates->where('status', 'expired')->count(),
            'Sertifikat Expiring' => fn (Asset $asset) => $asset->certificates->whereIn('status', ['expiring_soon', 'expiring_critical'])->count(),
            'Inspeksi Terakhir' => fn (Asset $asset) => $asset->inspections->sortByDesc('inspection_date')->first()?->inspection_date?->format('Y-m-d'),
            'Inspeksi Berikutnya' => fn (Asset $asset) => $asset->inspections->sortByDesc('inspection_date')->first()?->next_inspection_date?->format('Y-m-d'),
            'Created At' => fn (Asset $asset) => $asset->created_at->format('Y-m-d H:i:s'),
        ], 'assets_export_'.now()->format('Ymd_His').'.csv');
    }
}

<?php

namespace App\Http\Controllers\Modules\Asset;

use App\Core\Activity\ActivityService;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Asset\StoreAssetRequest;
use App\Http\Requests\Modules\Asset\UpdateAssetRequest;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Asset\Asset;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly ActivityService $activityService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Asset::class);

        $query = Asset::query()
            ->with(['site', 'area', 'department', 'creator'])
            ->when(!$request->user()->hasRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Top Management', 'Auditor']), function ($q) use ($request) {
                if ($request->user()->hasRole('QHSSE Officer')) {
                    $q->where('site_id', $request->user()->site_id);
                } elseif ($request->user()->hasRole(['Supervisor', 'Department Head', 'Employee'])) {
                    $q->where('department_id', $request->user()->department_id);
                } elseif ($request->user()->hasRole('Contractor')) {
                    $q->where('site_id', $request->user()->site_id);
                }
            });

        $listQuery = ListQuery::for($query, $request)
            ->search(['asset_number', 'name', 'serial_number', 'model', 'manufacturer'], $request->input('search'))
            ->filter('site_id', $request->input('site_id'))
            ->filter('category', $request->input('category'))
            ->filter('status', $request->input('status'))
            ->filter('safety_critical', $request->input('safety_critical'))
            ->defaultSort('-created_at');

        return Inertia::render('Modules/Asset/Index', [
            'assets' => $listQuery->paginate(15),
            'filters' => $listQuery->filters(),
            'sites' => Site::select('id', 'name')->get(),
            'categories' => Asset::getCategories(),
            'statuses' => Asset::getStatuses(),
            'can' => [
                'create' => $request->user()->can('create', Asset::class),
                'export' => $request->user()->can('export', Asset::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Asset::class);

        return Inertia::render('Modules/Asset/CreateOrEdit', [
            'sites' => Site::with('areas')->get(),
            'departments' => Department::select('id', 'name')->get(),
            'categories' => Asset::getCategories(),
            'can' => ['create' => true],
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generate asset number
        $generated = $this->numberingService->generate(
            moduleName: 'asset',
            actor: $request->user(),
            siteCode: null,
            referenceType: 'AST'
        );
        $validated['asset_number'] = $generated->number;

        // Set audit fields
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['safety_critical'] = $validated['safety_critical'] ?? false;

        $asset = Asset::create($validated);

        // Log activity
        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'created',
            description: "Asset {$asset->asset_number} - {$asset->name} created",
            actor: $request->user()
        );

        return redirect()->route('assets.show', $asset)->with('success', 'Asset created successfully.');
    }

    public function show(Request $request, Asset $asset): Response
    {
        $this->authorize('view', $asset);

        $asset->load(['site', 'area', 'department', 'creator', 'updater', 
            'certificates' => fn($q) => $q->orderBy('expiry_date'),
            'inspections' => fn($q) => $q->with('inspector')->orderBy('inspection_date', 'desc')
        ]);

        return Inertia::render('Modules/Asset/Show', [
            'asset' => $asset,
            'can' => [
                'update' => $request->user()->can('update', $asset),
                'delete' => $request->user()->can('delete', $asset),
                'decommission' => $request->user()->can('decommission', $asset),
                'createCertificate' => $request->user()->can('create', [\App\Models\Modules\Asset\AssetCertificate::class, $asset]),
                'createInspection' => $request->user()->can('create', [\App\Models\Modules\Asset\AssetInspection::class, $asset]),
            ],
        ]);
    }

    public function edit(Request $request, Asset $asset): Response
    {
        $this->authorize('update', $asset);

        return Inertia::render('Modules/Asset/CreateOrEdit', [
            'asset' => $asset->load(['site', 'area', 'department']),
            'sites' => Site::with('areas')->get(),
            'departments' => Department::select('id', 'name')->get(),
            'categories' => Asset::getCategories(),
            'can' => ['update' => true],
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validated();

        // Set audit fields
        $validated['updated_by'] = $request->user()->id;

        $oldValues = $asset->only(array_keys($validated));
        $asset->update($validated);

        // Log activity
        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'updated',
            description: "Asset {$asset->asset_number} updated",
            actor: $request->user(),
            metadata: ['old' => $oldValues, 'new' => $validated]
        );

        return redirect()->route('assets.show', $asset)->with('success', 'Asset updated successfully.');
    }

    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('delete', $asset);

        $assetNumber = $asset->asset_number;
        $asset->delete();

        // Log activity
        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'deleted',
            description: "Asset {$assetNumber} soft deleted",
            actor: $request->user()
        );

        return redirect()->route('assets.index')->with('success', 'Asset deleted successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Asset::class);

        $query = Asset::query()
            ->with(['site', 'area', 'department'])
            ->when(!$request->user()->hasRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Top Management', 'Auditor']), function ($q) use ($request) {
                if ($request->user()->hasRole('QHSSE Officer')) {
                    $q->where('site_id', $request->user()->site_id);
                } elseif ($request->user()->hasRole(['Supervisor', 'Department Head', 'Employee'])) {
                    $q->where('department_id', $request->user()->department_id);
                }
            });

        $listQuery = ListQuery::for($query, $request)
            ->search(['asset_number', 'name', 'serial_number'], $request->input('search'))
            ->filter('site_id', $request->input('site_id'))
            ->filter('category', $request->input('category'))
            ->filter('status', $request->input('status'))
            ->filter('safety_critical', $request->input('safety_critical'))
            ->defaultSort('-created_at');

        $assets = $listQuery->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="assets-' . date('Y-m-d-His') . '.csv"',
        ];

        return response()->stream(function () use ($assets) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Asset Number', 'Name', 'Category', 'Serial Number', 'Model', 'Manufacturer', 
                'Site', 'Area', 'Department', 'Status', 'Safety Critical', 'Purchase Date', 'Created At']);

            foreach ($assets as $asset) {
                fputcsv($handle, [
                    $asset->asset_number,
                    $asset->name,
                    $asset->category,
                    $asset->serial_number,
                    $asset->model,
                    $asset->manufacturer,
                    $asset->site?->name,
                    $asset->area?->name,
                    $asset->department?->name,
                    $asset->status,
                    $asset->safety_critical ? 'Yes' : 'No',
                    $asset->purchase_date?->format('Y-m-d'),
                    $asset->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}

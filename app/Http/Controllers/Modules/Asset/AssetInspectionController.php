<?php

namespace App\Http\Controllers\Modules\Asset;

use App\Core\Activity\ActivityService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Asset\StoreAssetInspectionRequest;
use App\Http\Requests\Modules\Asset\UpdateAssetInspectionRequest;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetInspection;
use App\Modules\Asset\AssetAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AssetInspectionController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
        private readonly AssetAccess $access,
    ) {}

    public function index(Request $request, Asset $asset): Response
    {
        $this->authorize('viewAny', [AssetInspection::class, $asset]);

        $inspections = $asset->inspections()
            ->with(['inspector', 'capaAction', 'creator', 'updater'])
            ->orderBy('inspection_date', 'desc')
            ->get()
            ->each(function (AssetInspection $inspection) use ($request): void {
                $inspection->setAttribute('can_update', $request->user()->can('update', $inspection));
                $inspection->setAttribute('can_create_capa', $request->user()->can('linkCapa', $inspection));
            });

        return Inertia::render('Modules/Asset/Inspection/Index', [
            'asset' => $asset,
            'inspections' => $inspections,
            'can' => [
                'create' => $request->user()->can('create', [AssetInspection::class, $asset]),
            ],
        ]);
    }

    public function create(Request $request, Asset $asset): Response
    {
        $this->authorize('create', [AssetInspection::class, $asset]);

        $inspectors = $this->access->inspectors($asset);

        return Inertia::render('Modules/Asset/Inspection/CreateOrEdit', [
            'asset' => $asset,
            'inspectors' => $inspectors,
            'results' => AssetInspection::getResults(),
            'can' => ['create' => true],
        ]);
    }

    public function store(StoreAssetInspectionRequest $request, Asset $asset): RedirectResponse
    {
        DB::transaction(function () use ($request, $asset): void {
            $validated = $request->validated();
            $validated['asset_id'] = $asset->id;
            $validated['created_by'] = $request->user()->id;
            $validated['updated_by'] = $request->user()->id;

            $inspection = AssetInspection::create($validated);

            $this->syncNextInspectionDate($asset, $request->user()->id);

            $this->activityService->log(
                moduleName: 'asset',
                referenceId: $asset->id,
                action: 'asset.inspection.created',
                description: "Inspection recorded for asset {$asset->asset_number} with result: {$inspection->result}",
                actor: $request->user(),
            );
        });

        return redirect()->route('assets.show', ['asset' => $asset, 'tab' => 'inspections'])
            ->with('success', 'Inspection recorded successfully.');
    }

    public function show(Request $request, Asset $asset, AssetInspection $inspection): Response
    {
        $this->authorize('view', $inspection);

        $inspection->load(['inspector', 'capaAction', 'creator', 'updater']);

        return Inertia::render('Modules/Asset/Inspection/Show', [
            'asset' => $asset,
            'inspection' => $inspection,
            'can' => [
                'update' => $request->user()->can('update', $inspection),
                'delete' => $request->user()->can('delete', $inspection),
                'linkCapa' => $request->user()->can('linkCapa', $inspection),
            ],
        ]);
    }

    public function edit(Request $request, Asset $asset, AssetInspection $inspection): Response
    {
        $this->authorize('update', $inspection);

        $inspectors = $this->access->inspectors($asset);

        return Inertia::render('Modules/Asset/Inspection/CreateOrEdit', [
            'asset' => $asset,
            'inspection' => [
                ...$inspection->toArray(),
                'inspection_date' => $inspection->inspection_date?->toDateString(),
                'next_inspection_date' => $inspection->next_inspection_date?->toDateString(),
            ],
            'inspectors' => $inspectors,
            'results' => AssetInspection::getResults(),
            'can' => ['update' => true],
        ]);
    }

    public function update(UpdateAssetInspectionRequest $request, Asset $asset, AssetInspection $inspection): RedirectResponse
    {
        DB::transaction(function () use ($request, $asset, $inspection): void {
            $validated = $request->validated();
            $validated['updated_by'] = $request->user()->id;
            $oldValues = $inspection->only(array_keys($validated));
            $inspection->update($validated);

            $this->syncNextInspectionDate($asset, $request->user()->id);

            $this->activityService->log(
                moduleName: 'asset',
                referenceId: $asset->id,
                action: 'asset.inspection.updated',
                description: "Inspection updated for asset {$asset->asset_number}",
                actor: $request->user(),
                metadata: ['old' => $oldValues, 'new' => $validated],
            );
        });

        return redirect()->route('assets.show', ['asset' => $asset, 'tab' => 'inspections'])
            ->with('success', 'Inspection updated successfully.');
    }

    public function createCapa(Request $request, Asset $asset, AssetInspection $inspection): RedirectResponse
    {
        $this->authorize('linkCapa', $inspection);

        return redirect()->route('capa.actions.create', [
            'source_module' => 'asset_inspection',
            'source_reference_id' => $inspection->id,
            'source_type' => 'corrective',
            'site_id' => $asset->site_id,
            'department_id' => $asset->department_id,
            'title' => "CAPA for failed inspection {$asset->asset_number}",
            'description' => $inspection->findings ?: "Follow up failed inspection for {$asset->name}.",
        ]);
    }

    private function syncNextInspectionDate(Asset $asset, int $actorId): void
    {
        $nextInspectionDate = $asset->inspections()
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->value('next_inspection_date');

        $asset->update([
            'next_inspection_date' => $nextInspectionDate,
            'updated_by' => $actorId,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Modules\Asset;

use App\Core\Activity\ActivityService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Asset\StoreAssetInspectionRequest;
use App\Http\Requests\Modules\Asset\UpdateAssetInspectionRequest;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetInspection;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetInspectionController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function index(Request $request, Asset $asset): Response
    {
        $this->authorize('viewAny', [AssetInspection::class, $asset]);

        $inspections = $asset->inspections()
            ->with(['inspector', 'capaAction', 'creator', 'updater'])
            ->orderBy('inspection_date', 'desc')
            ->get();

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

        // Get users who can be inspectors (QHSSE roles)
        $inspectors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Super Admin', 'Admin', 'QHSSE Manager', 'QHSSE Officer']);
        })->select('id', 'name')->get();

        return Inertia::render('Modules/Asset/Inspection/CreateOrEdit', [
            'asset' => $asset,
            'inspectors' => $inspectors,
            'results' => AssetInspection::getResults(),
            'can' => ['create' => true],
        ]);
    }

    public function store(StoreAssetInspectionRequest $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validated();
        $validated['asset_id'] = $asset->id;
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $inspection = AssetInspection::create($validated);

        // Update asset next_inspection_date
        if (!empty($validated['next_inspection_date'])) {
            $asset->update(['next_inspection_date' => $validated['next_inspection_date']]);
        }

        // Log activity
        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'inspection_created',
            description: "Inspection recorded for asset {$asset->asset_number} with result: {$inspection->result}",
            actor: $request->user()
        );

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

        // Get users who can be inspectors
        $inspectors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Super Admin', 'Admin', 'QHSSE Manager', 'QHSSE Officer']);
        })->select('id', 'name')->get();

        return Inertia::render('Modules/Asset/Inspection/CreateOrEdit', [
            'asset' => $asset,
            'inspection' => $inspection,
            'inspectors' => $inspectors,
            'results' => AssetInspection::getResults(),
            'can' => ['update' => true],
        ]);
    }

    public function update(UpdateAssetInspectionRequest $request, Asset $asset, AssetInspection $inspection): RedirectResponse
    {
        $validated = $request->validated();
        $validated['updated_by'] = $request->user()->id;

        $oldValues = $inspection->only(array_keys($validated));
        $inspection->update($validated);

        // Update asset next_inspection_date if provided
        if (isset($validated['next_inspection_date'])) {
            $asset->update(['next_inspection_date' => $validated['next_inspection_date']]);
        }

        // Log activity
        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'inspection_updated',
            description: "Inspection updated for asset {$asset->asset_number}",
            actor: $request->user(),
            metadata: ['old' => $oldValues, 'new' => $validated]
        );

        return redirect()->route('assets.show', ['asset' => $asset, 'tab' => 'inspections'])
            ->with('success', 'Inspection updated successfully.');
    }

    public function destroy(Request $request, Asset $asset, AssetInspection $inspection): RedirectResponse
    {
        $this->authorize('delete', $inspection);

        $inspection->delete();

        // Log activity
        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'inspection_deleted',
            description: "Inspection deleted from asset {$asset->asset_number}",
            actor: $request->user()
        );

        return redirect()->route('assets.show', ['asset' => $asset, 'tab' => 'inspections'])
            ->with('success', 'Inspection deleted successfully.');
    }

    public function linkCapa(Request $request, Asset $asset, AssetInspection $inspection): RedirectResponse
    {
        $this->authorize('linkCapa', $inspection);

        $request->validate([
            'capa_action_id' => ['required', 'integer', 'exists:capa_actions,id'],
        ]);

        $inspection->update([
            'capa_action_id' => $request->capa_action_id,
            'updated_by' => $request->user()->id,
        ]);

        // Log activity
        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'inspection_capa_linked',
            description: "CAPA action linked to inspection for asset {$asset->asset_number}",
            actor: $request->user()
        );

        return redirect()->route('assets.show', ['asset' => $asset, 'tab' => 'inspections'])
            ->with('success', 'CAPA action linked successfully.');
    }
}

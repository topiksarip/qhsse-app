<?php

namespace App\Http\Controllers\Modules\Asset;

use App\Core\Activity\ActivityService;
use App\Core\Files\ManagedFileService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Asset\StoreAssetCertificateRequest;
use App\Http\Requests\Modules\Asset\UpdateAssetCertificateRequest;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetCertificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetCertificateController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
        private readonly ManagedFileService $fileService,
    ) {}

    public function index(Request $request, Asset $asset): Response
    {
        $this->authorize('viewAny', [AssetCertificate::class, $asset]);

        $certificates = $asset->certificates()
            ->with(['creator', 'updater'])
            ->orderBy('expiry_date')
            ->get();

        return Inertia::render('Modules/Asset/Certificates/Index', [
            'asset' => $asset,
            'certificates' => $certificates,
            'can' => [
                'create' => $request->user()->can('create', [AssetCertificate::class, $asset]),
            ],
        ]);
    }

    public function create(Request $request, Asset $asset): Response
    {
        $this->authorize('create', [AssetCertificate::class, $asset]);

        return Inertia::render('Modules/Asset/Certificates/CreateOrEdit', [
            'asset' => $asset,
            'can' => ['create' => true],
        ]);
    }

    public function store(StoreAssetCertificateRequest $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validated();
        $validated['asset_id'] = $asset->id;
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $certificate = AssetCertificate::create($validated);

        // Update status based on expiry
        $certificate->updateStatus();

        // Handle file upload
        if ($request->hasFile('file')) {
            $this->fileService->upload(
                file: $request->file('file'),
                moduleName: 'asset',
                referenceId: $certificate->id,
                collection: 'certificate',
                uploadedBy: $request->user()
            );
        }

        // Log activity
        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'certificate_created',
            description: "Certificate {$certificate->certificate_type} added to asset {$asset->asset_number}",
            actor: $request->user()
        );

        return redirect()->route('assets.show', ['asset' => $asset, 'tab' => 'certificates'])
            ->with('success', 'Certificate created successfully.');
    }

    public function show(Request $request, Asset $asset, AssetCertificate $certificate): Response
    {
        $this->authorize('view', $certificate);

        $certificate->load(['creator', 'updater']);

        // Get certificate files
        $files = $this->fileService->getFiles(
            moduleName: 'asset',
            referenceId: $certificate->id,
            collection: 'certificate'
        );

        return Inertia::render('Modules/Asset/Certificates/Show', [
            'asset' => $asset,
            'certificate' => $certificate,
            'files' => $files,
            'can' => [
                'update' => $request->user()->can('update', $certificate),
                'delete' => $request->user()->can('delete', $certificate),
            ],
        ]);
    }

    public function edit(Request $request, Asset $asset, AssetCertificate $certificate): Response
    {
        $this->authorize('update', $certificate);

        return Inertia::render('Modules/Asset/Certificates/CreateOrEdit', [
            'asset' => $asset,
            'certificate' => $certificate,
            'can' => ['update' => true],
        ]);
    }

    public function update(UpdateAssetCertificateRequest $request, Asset $asset, AssetCertificate $certificate): RedirectResponse
    {
        $validated = $request->validated();
        $validated['updated_by'] = $request->user()->id;

        $oldValues = $certificate->only(array_keys($validated));
        $certificate->update($validated);

        // Update status based on expiry
        $certificate->updateStatus();

        // Handle file upload
        if ($request->hasFile('file')) {
            $this->fileService->upload(
                file: $request->file('file'),
                moduleName: 'asset',
                referenceId: $certificate->id,
                collection: 'certificate',
                uploadedBy: $request->user()
            );
        }

        // Log activity
        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'certificate_updated',
            description: "Certificate {$certificate->certificate_type} updated for asset {$asset->asset_number}",
            actor: $request->user(),
            metadata: ['old' => $oldValues, 'new' => $validated]
        );

        return redirect()->route('assets.show', ['asset' => $asset, 'tab' => 'certificates'])
            ->with('success', 'Certificate updated successfully.');
    }

    public function destroy(Request $request, Asset $asset, AssetCertificate $certificate): RedirectResponse
    {
        $this->authorize('delete', $certificate);

        $certificateType = $certificate->certificate_type;
        $certificate->delete();

        // Log activity
        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'certificate_deleted',
            description: "Certificate {$certificateType} deleted from asset {$asset->asset_number}",
            actor: $request->user()
        );

        return redirect()->route('assets.show', ['asset' => $asset, 'tab' => 'certificates'])
            ->with('success', 'Certificate deleted successfully.');
    }

    public function download(Request $request, Asset $asset, AssetCertificate $certificate, int $fileId)
    {
        $this->authorize('view', $certificate);

        return $this->fileService->download($fileId, $request->user());
    }
}

<?php

namespace App\Http\Controllers\Modules\Asset;

use App\Core\Activity\ActivityService;
use App\Core\Files\FileReference;
use App\Core\Files\ManagedFileService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Asset\StoreAssetCertificateRequest;
use App\Http\Requests\Modules\Asset\UpdateAssetCertificateRequest;
use App\Models\Core\Files\ManagedFile;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetCertificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            ->get()
            ->each(fn (AssetCertificate $certificate) => $certificate->setAttribute(
                'can_update',
                $request->user()->can('update', $certificate),
            ));

        return Inertia::render('Modules/Asset/Certificate/Index', [
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

        return Inertia::render('Modules/Asset/Certificate/CreateOrEdit', [
            'asset' => $asset,
            'can' => ['create' => true],
        ]);
    }

    public function store(StoreAssetCertificateRequest $request, Asset $asset): RedirectResponse
    {
        $storedPath = null;

        try {
            DB::transaction(function () use ($request, $asset, &$storedPath): void {
                $validated = $request->safe()->except('certificate_file');
                $validated['asset_id'] = $asset->id;
                $validated['created_by'] = $request->user()->id;
                $validated['updated_by'] = $request->user()->id;

                if ($request->hasFile('certificate_file')) {
                    $file = $this->fileService->store(
                        $request->file('certificate_file'),
                        new FileReference('asset', $asset->id, 'certificate'),
                        $request->user(),
                    );
                    $validated['certificate_file_id'] = $file->id;
                    $storedPath = $file->path;
                }

                $certificate = AssetCertificate::create($validated);
                $certificate->updateStatus();

                $this->activityService->log(
                    moduleName: 'asset',
                    referenceId: $asset->id,
                    action: 'asset.certificate.created',
                    description: "Certificate {$certificate->certificate_number} added to asset {$asset->asset_number}",
                    actor: $request->user(),
                );
            });
        } catch (\Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk(ManagedFileService::DISK)->delete($storedPath);
            }

            throw $exception;
        }

        return redirect()->route('assets.show', ['asset' => $asset, 'tab' => 'certificates'])
            ->with('success', 'Certificate created successfully.');
    }

    public function show(Request $request, Asset $asset, AssetCertificate $certificate): Response
    {
        $this->authorize('view', $certificate);

        $certificate->load(['creator', 'updater', 'certificateFile']);

        $certificateData = $certificate->only([
            'id',
            'asset_id',
            'certificate_type',
            'certificate_number',
            'issuing_body',
            'issued_date',
            'expiry_date',
            'status',
            'notes',
            'created_at',
            'updated_at',
        ]);
        $certificateData['creator'] = $certificate->creator?->only(['id', 'name']);
        $certificateData['updater'] = $certificate->updater?->only(['id', 'name']);
        $certificateData['certificate_file'] = $certificate->certificateFile === null
            ? null
            : [
                'id' => $certificate->certificateFile->id,
                'original_name' => $certificate->certificateFile->original_name,
                'mime_type' => $certificate->certificateFile->mime_type,
                'size' => $certificate->certificateFile->size,
                'download_url' => route('assets.certificates.files.download', [
                    $asset,
                    $certificate,
                    $certificate->certificateFile,
                ]),
            ];

        return Inertia::render('Modules/Asset/Certificate/Show', [
            'asset' => $asset,
            'certificate' => $certificateData,
            'can' => [
                'update' => $request->user()->can('update', $certificate),
                'delete' => $request->user()->can('delete', $certificate),
            ],
        ]);
    }

    public function edit(Request $request, Asset $asset, AssetCertificate $certificate): Response
    {
        $this->authorize('update', $certificate);

        return Inertia::render('Modules/Asset/Certificate/CreateOrEdit', [
            'asset' => $asset,
            'certificate' => [
                ...$certificate->toArray(),
                'issued_date' => $certificate->issued_date?->toDateString(),
                'expiry_date' => $certificate->expiry_date?->toDateString(),
            ],
            'can' => ['update' => true],
        ]);
    }

    public function update(UpdateAssetCertificateRequest $request, Asset $asset, AssetCertificate $certificate): RedirectResponse
    {
        $storedPath = null;

        try {
            DB::transaction(function () use ($request, $asset, $certificate, &$storedPath): void {
                $validated = $request->safe()->except('certificate_file');
                $validated['updated_by'] = $request->user()->id;
                $oldValues = $certificate->only(array_keys($validated));
                $oldFile = $certificate->certificateFile;

                if ($request->hasFile('certificate_file')) {
                    $file = $this->fileService->store(
                        $request->file('certificate_file'),
                        new FileReference('asset', $asset->id, 'certificate'),
                        $request->user(),
                    );
                    $validated['certificate_file_id'] = $file->id;
                    $storedPath = $file->path;
                }

                $certificate->update($validated);
                $certificate->updateStatus();

                if ($request->hasFile('certificate_file') && $oldFile !== null) {
                    $this->fileService->markDeleted($oldFile, $request->user());
                }

                $this->activityService->log(
                    moduleName: 'asset',
                    referenceId: $asset->id,
                    action: 'asset.certificate.updated',
                    description: "Certificate {$certificate->certificate_number} updated for asset {$asset->asset_number}",
                    actor: $request->user(),
                    metadata: ['old' => $oldValues, 'new' => $validated],
                );
            });
        } catch (\Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk(ManagedFileService::DISK)->delete($storedPath);
            }

            throw $exception;
        }

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
        $managedFile = ManagedFile::query()->findOrFail($fileId);

        abort_unless(
            $certificate->certificate_file_id === $managedFile->id
            && $managedFile->module_name === 'asset'
            && $managedFile->reference_id === $asset->id
            && $managedFile->collection === 'certificate'
            && $managedFile->deleted_at === null,
            404,
        );
        abort_unless(Storage::disk($managedFile->disk)->exists($managedFile->path), 404);

        $this->activityService->log(
            moduleName: 'asset',
            referenceId: $asset->id,
            action: 'asset.certificate.file_downloaded',
            description: "Certificate file {$managedFile->original_name} downloaded",
            actor: $request->user(),
            metadata: ['certificate_id' => $certificate->id, 'file_id' => $managedFile->id],
        );

        return Storage::disk($managedFile->disk)->download($managedFile->path, $managedFile->original_name);
    }
}

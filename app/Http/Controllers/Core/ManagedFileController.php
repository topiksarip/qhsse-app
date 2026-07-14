<?php

namespace App\Http\Controllers\Core;

use App\Core\Authorization\ParentAuthorizationRegistry;
use App\Core\Files\ManagedFileService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\ManagedFileUploadRequest;
use App\Models\Core\Files\ManagedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagedFileController extends Controller
{
    public function __construct(private readonly ParentAuthorizationRegistry $authRegistry) {}

    public function index(Request $request): Response
    {
        $moduleName = $request->string('module_name')->toString();
        $referenceId = $request->integer('reference_id') ?: null;
        
        $query = ManagedFile::query()->with('uploader:id,name,email');
        
        // Block unregistered modules from generic endpoints (fail-closed)
        if ($moduleName) {
            if (!$this->authRegistry->isModuleRegistered($moduleName)) {
                // Fail-closed: unregistered modules cannot use generic endpoints
                $query->whereRaw('1 = 0');
            } elseif ($referenceId) {
                // Apply parent authorization for registered modules
                if (!$this->authRegistry->canAccessParent($moduleName, $referenceId, $request->user())) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where('module_name', $moduleName)->where('reference_id', $referenceId);
                }
            }
        }
        
        $files = $query
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('original_name', 'like', "%{$search}%")
                        ->orWhere('module_name', 'like', "%{$search}%")
                        ->orWhere('collection', 'like', "%{$search}%");
                });
            })
            ->when($request->string('module_name')->toString(), fn ($query, string $module) => $query->where('module_name', $module))
            ->when($request->integer('reference_id'), fn ($query, int $referenceId) => $query->where('reference_id', $referenceId))
            ->when(! $request->boolean('include_deleted'), fn ($query) => $query->active())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Core/Files/Index', [
            'files' => $files,
            'filters' => $request->only(['search', 'module_name', 'reference_id', 'include_deleted']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Core/Files/Form');
    }

    public function store(ManagedFileUploadRequest $request, ManagedFileService $service): RedirectResponse
    {
        $moduleName = $request->validated('module_name');
        $referenceId = $request->validated('reference_id');
        
        abort_unless($this->authRegistry->isModuleRegistered($moduleName), 403);
        abort_unless($this->authRegistry->canAccessParent($moduleName, $referenceId, $request->user()), 403);

        $service->store(
            $request->file('file'),
            $request->reference(),
            $request->user(),
            $request->validated('metadata') ?? [],
        );

        return redirect()->route('core.files.index');
    }

    public function download(Request $request, ManagedFile $file): StreamedResponse
    {
        abort_if($file->deleted_at !== null, 404);
        
        // Fail-closed: unregistered modules return 404 (not 403) to avoid info leak
        if (!$this->authRegistry->isModuleRegistered($file->module_name)) {
            abort(404);
        }
        
        if (!$this->authRegistry->canAccessParent($file->module_name, $file->reference_id, $request->user())) {
            abort(404);
        }
        
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function destroy(ManagedFile $file, ManagedFileService $service, Request $request): RedirectResponse
    {
        abort_if(in_array($file->module_name, ['document', 'asset'], true), 404);
        abort_if($file->deleted_at !== null, 404);

        $service->markDeleted($file, $request->user());

        return redirect()->route('core.files.index');
    }
}

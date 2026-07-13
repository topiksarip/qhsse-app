<?php

namespace App\Http\Controllers\Modules\Incident;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Files\FileReference;
use App\Core\Files\ManagedFileService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Incident\StoreIncidentEvidenceRequest;
use App\Models\Core\Files\ManagedFile;
use App\Models\Modules\Incident\IncidentReport;
use App\Modules\Incident\IncidentAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentEvidenceController extends Controller
{
    public function __construct(
        private readonly ManagedFileService $files,
        private readonly AuditService $audit,
        private readonly ActivityService $activity,
        private readonly IncidentAccess $access,
    ) {}

    public function store(StoreIncidentEvidenceRequest $request, IncidentReport $incidentReport): RedirectResponse
    {
        $actor = $request->user();
        $this->access->ensureVisible($actor, $incidentReport);
        abort_if(in_array($incidentReport->status, ['closed', 'rejected'], true), 409);

        $file = $this->files->store(
            $request->file('file'),
            new FileReference('incident', $incidentReport->id, 'evidence'),
            $actor,
        );

        $this->audit->log(
            'incident.evidence.uploaded',
            $incidentReport,
            [],
            ['file_id' => $file->id, 'original_name' => $file->original_name],
            $actor,
            'incident',
            $incidentReport->id,
        );
        $this->activity->log(
            'incident',
            $incidentReport->id,
            'incident.evidence_uploaded',
            "Evidence {$file->original_name} diunggah",
            $actor,
        );

        return back()->with('success', 'Evidence berhasil diunggah.');
    }

    public function download(Request $request, IncidentReport $incidentReport, ManagedFile $file): StreamedResponse
    {
        $this->access->ensureVisible($request->user(), $incidentReport);
        abort_unless(
            $file->module_name === 'incident'
                && $file->reference_id === $incidentReport->id
                && $file->collection === 'evidence'
                && $file->deleted_at === null,
            404,
        );
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}

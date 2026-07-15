<?php

namespace App\Http\Controllers\Modules\Reporting;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Reporting\GenerateReportRequest;
use App\Jobs\Modules\Reporting\GenerateReportJob;
use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\Modules\Reporting\SavedReport;
use App\Models\User;
use App\Services\Modules\Reporting\ReportingScopeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SavedReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ActivityService $activityService,
        protected ReportingScopeService $reportingScope,
        protected AuditService $auditService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SavedReport::class);

        $query = $this->reportingScope->scopeReports(
            SavedReport::with(['template', 'generatedBy']),
            $request->user(),
        );

        $filters = $request->only(['search', 'status', 'format', 'template_id']);

        $listQuery = ListQuery::for($query, $request);
        $listQuery->search(['name'], $filters['search'] ?? null);
        $listQuery->filter('status', $filters['status'] ?? null);
        $listQuery->filter('format', $filters['format'] ?? null);
        $listQuery->filter('template_id', $filters['template_id'] ?? null);
        $listQuery->sort($request->input('sort', 'created_at'), $request->input('direction', 'desc'));

        $reports = $listQuery->paginate($request->input('per_page', 15));

        // Get templates for filter dropdown
        $templates = ReportTemplate::active()->get(['id', 'name', 'type']);

        return Inertia::render('Modules/Reporting/SavedReport/Index', [
            'reports' => $reports,
            'templates' => $templates,
            'filters' => $filters,
        ]);
    }

    public function show(SavedReport $savedReport): Response
    {
        $this->authorize('view', $savedReport);

        $savedReport->load(['template', 'generatedBy', 'createdBy', 'updatedBy']);

        return Inertia::render('Modules/Reporting/SavedReport/Show', [
            'report' => $savedReport,
            'canDownload' => auth()->user()->can('download', $savedReport),
            'canRegenerate' => auth()->user()->can('regenerate', $savedReport),
            'canDelete' => auth()->user()->can('delete', $savedReport),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('generate', SavedReport::class);

        // Get template_id from query string if provided
        $templateId = $request->query('template_id');
        $template = null;

        if ($templateId) {
            $template = ReportTemplate::active()->find($templateId);
        }

        $templates = ReportTemplate::active()->get(['id', 'name', 'type', 'description']);
        $sites = $this->reportingScope->availableSites($request->user());
        $departments = $this->reportingScope->availableDepartments($request->user());

        return Inertia::render('Modules/Reporting/SavedReport/Generate', [
            'templates' => $templates,
            'sites' => $sites,
            'departments' => $departments,
            'selectedTemplate' => $template,
        ]);
    }

    public function store(GenerateReportRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $validated['parameters'] = $this->reportingScope->scopedParameters($user, $validated['parameters']);
        $report = null;

        try {
            $report = DB::transaction(function () use ($validated, $user): SavedReport {
                $report = SavedReport::create([
                    'name' => $validated['name'],
                    'template_id' => $validated['template_id'],
                    'status' => 'pending',
                    'parameters' => $validated['parameters'],
                    'format' => $validated['format'],
                    'generated_by' => $user->id,
                    'generated_at' => now(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                $this->activityService->log(
                    'reporting',
                    $report->id,
                    'report.generated',
                    "Report generation started: {$report->name}",
                    $user,
                    [],
                    'report.generated',
                    null,
                    ['template_id' => $report->template_id, 'format' => $report->format]
                );

                $this->auditService->created($report, $user, 'reporting', $report->id);

                return $report;
            });

            // The queue only receives a model that has been committed successfully.
            $this->dispatchReport($report, $user);

            return redirect()
                ->route('saved-reports.show', $report)
                ->with('success', 'Laporan sedang di-generate. Anda akan menerima notifikasi saat selesai.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal memulai generate laporan: '.$e->getMessage());
        }
    }

    public function download(SavedReport $savedReport): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('download', $savedReport);

        if (! $savedReport->canDownload()) {
            return back()->with('error', 'Laporan belum selesai di-generate atau file tidak ditemukan.');
        }

        $filePath = Storage::path($savedReport->file_path);

        if (! file_exists($filePath)) {
            return back()->with('error', 'File laporan tidak ditemukan.');
        }

        // Log download activity
        $this->activityService->log(
            'reporting',
            $savedReport->id,
            'report.downloaded',
            "Report downloaded: {$savedReport->name}",
            auth()->user(),
            [],
            'report.downloaded',
            null,
            ['format' => $savedReport->format, 'ip' => request()->ip()]
        );

        return response()->download(
            $filePath,
            $savedReport->getDownloadFileName(),
            [
                'Content-Type' => match ($savedReport->format) {
                    'csv' => 'text/csv',
                    'pdf' => 'application/pdf',
                    'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    default => 'application/octet-stream',
                },
            ]
        );
    }

    public function regenerate(SavedReport $savedReport): RedirectResponse
    {
        $this->authorize('regenerate', $savedReport);

        try {
            $user = auth()->user();

            $newReport = DB::transaction(function () use ($savedReport, $user): SavedReport {
                $newReport = SavedReport::create([
                    'name' => $savedReport->name.' (regenerated)',
                    'template_id' => $savedReport->template_id,
                    'status' => 'pending',
                    'parameters' => $savedReport->parameters,
                    'format' => $savedReport->format,
                    'generated_by' => $user->id,
                    'generated_at' => now(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                $this->activityService->log(
                    'reporting',
                    $newReport->id,
                    'report.regenerated',
                    "Report regenerated: {$newReport->name}",
                    $user,
                    [],
                    'report.regenerated',
                    null,
                    ['original_report_id' => $savedReport->id]
                );

                $this->auditService->created($newReport, $user, 'reporting', $newReport->id);

                return $newReport;
            });

            $this->dispatchReport($newReport, $user);

            return redirect()
                ->route('saved-reports.show', $newReport)
                ->with('success', 'Laporan sedang di-generate ulang.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal regenerate laporan: '.$e->getMessage());
        }
    }

    public function destroy(SavedReport $savedReport): RedirectResponse
    {
        $this->authorize('delete', $savedReport);

        if (! $savedReport->canDelete()) {
            return back()->with('error', 'Laporan sedang diproses dan tidak dapat dihapus.');
        }

        $filePath = $savedReport->file_path;

        try {
            $reportName = $savedReport->name;
            DB::transaction(function () use ($savedReport, $reportName): void {
                $savedReport->delete();

                $this->activityService->log(
                    'reporting',
                    $savedReport->id,
                    'report.deleted',
                    "Report deleted: {$reportName}",
                    auth()->user(),
                    [],
                    'report.deleted',
                    null,
                    []
                );

                $this->auditService->deleted($savedReport, auth()->user(), 'reporting', $savedReport->id);
            });

            if ($filePath && Storage::exists($filePath) && ! Storage::delete($filePath)) {
                Log::warning('Deleted report metadata but could not remove its private artifact.', [
                    'report_id' => $savedReport->id,
                    'file_path' => $filePath,
                ]);
            }

            return redirect()
                ->route('saved-reports.index')
                ->with('success', 'Laporan berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus laporan: '.$e->getMessage());
        }
    }

    private function dispatchReport(SavedReport $report, User $user): void
    {
        try {
            GenerateReportJob::dispatch($report, $user);
        } catch (\Throwable $e) {
            if ($report->exists && $report->isPending()) {
                $report->markAsFailed('Report generation could not be queued.');
            }

            throw $e;
        }
    }
}

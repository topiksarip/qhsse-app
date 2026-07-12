<?php

namespace App\Http\Controllers\Modules\Reporting;

use App\Core\Activity\ActivityService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Reporting\GenerateReportRequest;
use App\Jobs\Modules\Reporting\GenerateReportJob;
use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\Modules\Reporting\SavedReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SavedReportController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(
        protected ActivityService $activityService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SavedReport::class);

        $query = SavedReport::with(['template', 'generatedBy']);

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
        $sites = \App\Models\Core\MasterData\Site::active()->get(['id', 'name', 'code']);
        $departments = \App\Models\Core\MasterData\Department::active()->get(['id', 'name', 'code']);

        return Inertia::render('Modules/Reporting/SavedReport/Generate', [
            'templates' => $templates,
            'sites' => $sites,
            'departments' => $departments,
            'selectedTemplate' => $template,
        ]);
    }

    public function store(GenerateReportRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            $validated = $request->validated();

            // Create saved_report record with status 'pending'
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

            // Dispatch async job
            GenerateReportJob::dispatch($report, $user);

            $this->activityService->log(
                moduleName: 'reporting',
                action: 'report.generated',
                description: "Report generation started: {$report->name}",
                referenceId: $report->id,
                referenceType: SavedReport::class,
                metadata: ['template_id' => $report->template_id, 'format' => $report->format]
            );

            DB::commit();

            return redirect()
                ->route('saved-reports.show', $report)
                ->with('success', 'Laporan sedang di-generate. Anda akan menerima notifikasi saat selesai.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memulai generate laporan: ' . $e->getMessage());
        }
    }

    public function download(SavedReport $savedReport): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('download', $savedReport);

        if (!$savedReport->canDownload()) {
            return back()->with('error', 'Laporan belum selesai di-generate atau file tidak ditemukan.');
        }

        $filePath = storage_path('app/' . $savedReport->file_path);

        if (!file_exists($filePath)) {
            return back()->with('error', 'File laporan tidak ditemukan.');
        }

        // Log download activity
        $this->activityService->log(
            moduleName: 'reporting',
            action: 'report.downloaded',
            description: "Report downloaded: {$savedReport->name}",
            referenceId: $savedReport->id,
            referenceType: SavedReport::class,
            metadata: ['format' => $savedReport->format, 'ip' => request()->ip()]
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

        DB::beginTransaction();
        try {
            $user = auth()->user();

            // Create new report with same parameters
            $newReport = SavedReport::create([
                'name' => $savedReport->name . ' (regenerated)',
                'template_id' => $savedReport->template_id,
                'status' => 'pending',
                'parameters' => $savedReport->parameters,
                'format' => $savedReport->format,
                'generated_by' => $user->id,
                'generated_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Dispatch async job
            GenerateReportJob::dispatch($newReport, $user);

            $this->activityService->log(
                moduleName: 'reporting',
                action: 'report.regenerated',
                description: "Report regenerated: {$newReport->name}",
                referenceId: $newReport->id,
                referenceType: SavedReport::class,
                metadata: ['original_report_id' => $savedReport->id]
            );

            DB::commit();

            return redirect()
                ->route('saved-reports.show', $newReport)
                ->with('success', 'Laporan sedang di-generate ulang.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal regenerate laporan: ' . $e->getMessage());
        }
    }

    public function destroy(SavedReport $savedReport): RedirectResponse
    {
        $this->authorize('delete', $savedReport);

        if (!$savedReport->canDelete()) {
            return back()->with('error', 'Laporan sedang diproses dan tidak dapat dihapus.');
        }

        DB::beginTransaction();
        try {
            $reportName = $savedReport->name;

            // Delete file from disk if exists
            if ($savedReport->file_path && Storage::exists($savedReport->file_path)) {
                Storage::delete($savedReport->file_path);
            }

            $savedReport->delete();

            $this->activityService->log(
                moduleName: 'reporting',
                action: 'report.deleted',
                description: "Report deleted: {$reportName}",
                referenceId: $savedReport->id,
                referenceType: SavedReport::class
            );

            DB::commit();

            return redirect()
                ->route('saved-reports.index')
                ->with('success', 'Laporan berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus laporan: ' . $e->getMessage());
        }
    }
}

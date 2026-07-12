<?php

namespace App\Http\Controllers\Modules\Reporting;

use App\Core\Activity\ActivityService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Reporting\StoreReportTemplateRequest;
use App\Http\Requests\Modules\Reporting\UpdateReportTemplateRequest;
use App\Models\Modules\Reporting\ReportTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportTemplateController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(
        protected ActivityService $activityService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ReportTemplate::class);

        $query = ReportTemplate::with(['createdBy', 'updatedBy'])
            ->withCount('savedReports');

        $filters = $request->only(['search', 'type', 'is_active', 'is_predefined']);
        
        $listQuery = ListQuery::for($query, $request);
        $listQuery->search(['name', 'description'], $filters['search'] ?? null);
        $listQuery->filter('type', $filters['type'] ?? null);
        
        if (isset($filters['is_active'])) {
            $listQuery->filter('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }
        
        if (isset($filters['is_predefined'])) {
            $listQuery->filter('is_predefined', filter_var($filters['is_predefined'], FILTER_VALIDATE_BOOLEAN));
        }
        
        $listQuery->sort($request->input('sort', 'name'), $request->input('direction', 'asc'));

        $templates = $listQuery->paginate($request->input('per_page', 15));

        return Inertia::render('Modules/Reporting/ReportTemplate/Index', [
            'templates' => $templates,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ReportTemplate::class);

        return Inertia::render('Modules/Reporting/ReportTemplate/CreateOrEdit', [
            'template' => null,
        ]);
    }

    public function store(StoreReportTemplateRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            
            $template = ReportTemplate::create([
                ...$request->validated(),
                'is_predefined' => false,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->activityService->log(
                moduleName: 'reporting',
                action: 'template.created',
                description: "Report template created: {$template->name}",
                referenceId: $template->id,
                referenceType: ReportTemplate::class,
                metadata: ['type' => $template->type]
            );

            DB::commit();

            return redirect()
                ->route('report-templates.index')
                ->with('success', 'Template laporan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal membuat template: ' . $e->getMessage());
        }
    }

    public function show(ReportTemplate $reportTemplate): Response
    {
        $this->authorize('view', $reportTemplate);

        $reportTemplate->load(['createdBy', 'updatedBy'])
            ->loadCount('savedReports');

        return Inertia::render('Modules/Reporting/ReportTemplate/Show', [
            'template' => $reportTemplate,
        ]);
    }

    public function edit(ReportTemplate $reportTemplate): Response
    {
        $this->authorize('update', $reportTemplate);

        return Inertia::render('Modules/Reporting/ReportTemplate/CreateOrEdit', [
            'template' => $reportTemplate,
        ]);
    }

    public function update(UpdateReportTemplateRequest $request, ReportTemplate $reportTemplate): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $reportTemplate->update([
                ...$request->validated(),
                'updated_by' => $request->user()->id,
            ]);

            $this->activityService->log(
                moduleName: 'reporting',
                action: 'template.updated',
                description: "Report template updated: {$reportTemplate->name}",
                referenceId: $reportTemplate->id,
                referenceType: ReportTemplate::class
            );

            DB::commit();

            return redirect()
                ->route('report-templates.show', $reportTemplate)
                ->with('success', 'Template laporan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui template: ' . $e->getMessage());
        }
    }

    public function destroy(ReportTemplate $reportTemplate): RedirectResponse
    {
        $this->authorize('delete', $reportTemplate);

        if (!$reportTemplate->canBeDeleted()) {
            return back()->with('error', 'Template tidak dapat dihapus karena sudah digunakan atau merupakan template pre-defined.');
        }

        DB::beginTransaction();
        try {
            $templateName = $reportTemplate->name;
            $reportTemplate->delete();

            $this->activityService->log(
                moduleName: 'reporting',
                action: 'template.deleted',
                description: "Report template deleted: {$templateName}",
                referenceId: $reportTemplate->id,
                referenceType: ReportTemplate::class
            );

            DB::commit();

            return redirect()
                ->route('report-templates.index')
                ->with('success', 'Template laporan berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus template: ' . $e->getMessage());
        }
    }

    public function toggleActive(ReportTemplate $reportTemplate): RedirectResponse
    {
        $this->authorize('update', $reportTemplate);

        DB::beginTransaction();
        try {
            $newStatus = !$reportTemplate->is_active;
            $reportTemplate->update([
                'is_active' => $newStatus,
                'updated_by' => auth()->id(),
            ]);

            $action = $newStatus ? 'activated' : 'deactivated';
            $this->activityService->log(
                moduleName: 'reporting',
                action: "template.{$action}",
                description: "Report template {$action}: {$reportTemplate->name}",
                referenceId: $reportTemplate->id,
                referenceType: ReportTemplate::class
            );

            DB::commit();

            return back()->with('success', 'Status template berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengubah status template: ' . $e->getMessage());
        }
    }
}

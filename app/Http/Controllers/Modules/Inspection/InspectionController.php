<?php

namespace App\Http\Controllers\Modules\Inspection;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Files\FileReference;
use App\Core\Files\ManagedFileService;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Core\Workflow\WorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Inspection\CancelInspectionUnitRequest;
use App\Http\Requests\Modules\Inspection\SaveInspectionUnitResultRequest;
use App\Http\Requests\Modules\Inspection\StoreInspectionTemplateRequest;
use App\Http\Requests\Modules\Inspection\StoreInspectionRequest;
use App\Http\Requests\Modules\Inspection\UpdateInspectionRequest;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Inspection\Inspection;
use App\Models\Modules\Inspection\InspectionItem;
use App\Models\Modules\Inspection\InspectionTemplate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InspectionController extends Controller
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly WorkflowService $workflowService,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
        private readonly ManagedFileService $files,
    ) {}

    // === TEMPLATE CRUD ===

    public function templateIndex(ListQuery $listQuery): Response
    {
        $items = $listQuery->paginate(
            InspectionTemplate::query()->withCount('items'),
            ['code', 'name'],
            ['name', 'code', 'created_at'],
            'name',
            15,
        );
        return Inertia::render('Modules/Inspection/Templates/Index', ['items' => $items, 'filters' => $listQuery->filters()]);
    }

    public function templateCreate(): Response
    {
        return Inertia::render('Modules/Inspection/Templates/Form', ['item' => null]);
    }

    public function templateStore(StoreInspectionTemplateRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();

        $template = DB::transaction(function () use ($validated, $actor) {
            $template = InspectionTemplate::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'is_active' => true,
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $index => $item) {
                    InspectionItem::create([
                        'inspection_template_id' => $template->id,
                        'question' => $item['question'],
                        'type' => $item['type'],
                        'category' => $item['category'] ?? null,
                        'is_required' => $item['is_required'] ?? true,
                        'order' => $item['order'] ?? $index,
                    ]);
                }
            }

            $this->auditService->created($template, $actor, 'inspection', $template->id);
            $this->activityService->log('inspection', $template->id, 'template.created', 'Template inspeksi dibuat', $actor);
            return $template;
        });

        return redirect()->route('inspection.templates.show', $template)->with('success', 'Template inspeksi berhasil dibuat.');
    }

    public function templateShow(InspectionTemplate $template): Response
    {
        $template->load('items');
        return Inertia::render('Modules/Inspection/Templates/Show', ['template' => $template]);
    }

    public function templateEdit(InspectionTemplate $template): Response
    {
        $template->load('items');
        return Inertia::render('Modules/Inspection/Templates/Form', ['item' => $template]);
    }

    public function templateUpdate(StoreInspectionTemplateRequest $request, InspectionTemplate $template): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();
        $oldValues = $template->getAttributes();

        DB::transaction(function () use ($template, $validated, $actor, $oldValues) {
            $template->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
            ]);

            if (isset($validated['items'])) {
                $template->items()->delete();
                foreach ($validated['items'] as $index => $item) {
                    InspectionItem::create([
                        'inspection_template_id' => $template->id,
                        'question' => $item['question'],
                        'type' => $item['type'],
                        'category' => $item['category'] ?? null,
                        'is_required' => $item['is_required'] ?? true,
                        'order' => $item['order'] ?? $index,
                    ]);
                }
            }

            $this->auditService->updated($template, $oldValues, $actor, 'inspection', $template->id);
        });

        return redirect()->route('inspection.templates.show', $template)->with('success', 'Template berhasil diperbarui.');
    }

    public function templateDestroy(InspectionTemplate $template): RedirectResponse
    {
        $template->update(['is_active' => false]);
        return redirect()->route('inspection.templates.index')->with('success', 'Template dinonaktifkan.');
    }

    // === INSPECTION EXECUTION ===

    public function index(ListQuery $listQuery): Response
    {
        $items = $listQuery->paginate(
            Inspection::query()->with(['template', 'site', 'inspector']),
            ['inspection_number'],
            ['scheduled_at', 'created_at', 'inspection_number'],
            'scheduled_at',
            15,
        );
        return Inertia::render('Modules/Inspection/Index', ['items' => $items, 'filters' => $listQuery->filters()]);
    }

    public function create(): Response
    {
        return Inertia::render('Modules/Inspection/Form', [
            'item' => null,
            'templates' => InspectionTemplate::where('is_active', true)->with('items')->orderBy('name')->get(),
            'sites' => Site::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'assets' => \App\Models\Modules\Asset\Asset::where('status', 'active')->orderBy('asset_number')->get(['id', 'asset_number', 'name', 'serial_number']),
        ]);
    }

    public function store(StoreInspectionRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();

        $inspection = DB::transaction(function () use ($validated, $actor) {
            $inspection = Inspection::create([
                'inspection_number' => 'TEMP-' . uniqid(),
                'inspection_template_id' => $validated['inspection_template_id'],
                'site_id' => $validated['site_id'],
                'area_id' => $validated['area_id'] ?? null,
                'inspector_id' => $validated['inspector_id'],
                'scheduled_at' => $validated['scheduled_at'],
                'status' => 'pending',
                'overall_result' => 'pending',
            ]);

            $generated = $this->numberingService->generate(moduleName: 'inspection', actor: $actor, referenceType: Inspection::class, referenceId: $inspection->id);
            $inspection->update(['inspection_number' => $generated->number]);

            // Create inspection units from selected assets.
            $assets = \App\Models\Modules\Asset\Asset::whereIn('id', $validated['asset_ids'])->get();
            foreach ($assets as $asset) {
                \App\Models\Modules\Inspection\InspectionUnit::create([
                    'inspection_id' => $inspection->id,
                    'asset_id' => $asset->id,
                    'identifier' => $asset->asset_number ?: $asset->name,
                    'status' => 'pending',
                ]);
            }

            $this->workflowService->start('inspection', $inspection->id, $actor);
            $this->auditService->created($inspection, $actor, 'inspection', $inspection->id);
            $this->activityService->log('inspection', $inspection->id, 'inspection.created', 'Inspeksi dibuat', $actor);

            return $inspection;
        });

        return redirect()->route('inspection.checklists.show', $inspection)->with('success', 'Inspeksi berhasil dibuat.');
    }

    public function show(Inspection $inspection): Response
    {
        $inspection->load([
            'template.items',
            'site',
            'area',
            'inspector',
            'units.results.photoFile',
        ]);

        $files = \App\Models\Core\Files\ManagedFile::query()
            ->where('module_name', 'inspection')
            ->where('reference_id', $inspection->id)
            ->where('collection', 'inspection_result')
            ->get();

        return Inertia::render('Modules/Inspection/Show', [
            'inspection' => $inspection,
            'units' => $inspection->units()->with('results.photoFile')->get(),
            'files' => $files,
        ]);
    }

    public function update(UpdateInspectionRequest $request, Inspection $inspection): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($request, $inspection, $validated, $actor) {
            // Save results
            if (isset($validated['results'])) {
                foreach ($validated['results'] as $index => $result) {
                    $existing = \App\Models\Modules\Inspection\InspectionResult::where('inspection_id', $inspection->id)
                        ->where('inspection_item_id', $result['inspection_item_id'])
                        ->first();

                    $photoPath = $existing?->photo;

                    if ($request->hasFile("results.{$index}.photo")) {
                        $file = $request->file("results.{$index}.photo");
                        $stored = $this->files->store($file, new FileReference('inspection', $inspection->id, 'inspection_result'), $actor);
                        $photoPath = $stored->path;

                        if ($existing?->photo && $existing->photo !== $photoPath) {
                            $old = \App\Models\Core\Files\ManagedFile::where('path', $existing->photo)->first();
                            if ($old) {
                                $this->files->markDeleted($old, $actor);
                            }
                        }
                    }

                    \App\Models\Modules\Inspection\InspectionResult::updateOrCreate(
                        ['inspection_id' => $inspection->id, 'inspection_item_id' => $result['inspection_item_id']],
                        [
                            'answer' => $result['answer'] ?? null,
                            'remark' => $result['remark'] ?? null,
                            'is_unsafe' => $result['is_unsafe'] ?? false,
                            'photo' => $photoPath,
                        ],
                    );
                }
            }

            if (isset($validated['notes'])) {
                $inspection->update(['notes' => $validated['notes']]);
            }

            $this->activityService->log('inspection', $inspection->id, 'inspection.updated', 'Hasil inspeksi disimpan', $actor);
        });

        return redirect()->route('inspection.checklists.show', $inspection)->with('success', 'Hasil inspeksi disimpan.');
    }

    public function saveUnitResult(SaveInspectionUnitResultRequest $request, Inspection $inspection, \App\Models\Modules\Inspection\InspectionUnit $unit): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();

        abort_unless($unit->inspection_id === $inspection->id, 404);

        DB::transaction(function () use ($request, $inspection, $unit, $validated, $actor) {
            foreach ($validated['results'] as $index => $result) {
                $existing = \App\Models\Modules\Inspection\InspectionResult::where('inspection_id', $inspection->id)
                    ->where('inspection_unit_id', $unit->id)
                    ->where('inspection_item_id', $result['inspection_item_id'])
                    ->first();

                $photoPath = $existing?->photo;

                if ($request->hasFile("results.{$index}.photo")) {
                    $file = $request->file("results.{$index}.photo");
                    $stored = $this->files->store($file, new FileReference('inspection', $inspection->id, 'inspection_result'), $actor);
                    $photoPath = $stored->path;

                    if ($existing?->photo && $existing->photo !== $photoPath) {
                        $old = \App\Models\Core\Files\ManagedFile::where('path', $existing->photo)->first();
                        if ($old) {
                            $this->files->markDeleted($old, $actor);
                        }
                    }
                }

                \App\Models\Modules\Inspection\InspectionResult::updateOrCreate(
                    [
                        'inspection_id' => $inspection->id,
                        'inspection_unit_id' => $unit->id,
                        'inspection_item_id' => $result['inspection_item_id'],
                    ],
                    [
                        'answer' => $result['answer'] ?? null,
                        'remark' => $result['remark'] ?? null,
                        'is_unsafe' => $result['is_unsafe'] ?? false,
                        'photo' => $photoPath,
                    ],
                );
            }

            if (isset($validated['notes'])) {
                $unit->update(['notes' => $validated['notes']]);
            }

            $unit->update(['status' => 'done']);
            $this->activityService->log('inspection', $inspection->id, 'inspection.unit.saved', "Hasil unit {$unit->identifier} disimpan", $actor);
        });

        return redirect()->route('inspection.checklists.show', $inspection)->with('success', "Hasil unit {$unit->identifier} disimpan.");
    }

    public function cancelUnit(CancelInspectionUnitRequest $request, Inspection $inspection, \App\Models\Modules\Inspection\InspectionUnit $unit): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($unit->inspection_id === $inspection->id, 404);

        DB::transaction(function () use ($inspection, $unit, $request, $actor) {
            $unit->update([
                'status' => 'cancelled',
                'cancelled_reason' => $request->validated()['cancelled_reason'],
            ]);
            $this->activityService->log('inspection', $inspection->id, 'inspection.unit.cancelled', "Unit {$unit->identifier} dibatalkan", $actor);
        });

        return redirect()->route('inspection.checklists.show', $inspection)->with('success', "Unit {$unit->identifier} dibatalkan.");
    }

    public function destroy(Request $request, Inspection $inspection): RedirectResponse
    {
        $actor = $request->user();
        $this->authorize('delete', $inspection);
        abort_unless(in_array($inspection->status, ['draft', 'in_progress', 'cancelled'], true), 409, 'Inspeksi hanya dapat dihapus jika draft/in_progress/cancelled.');

        DB::transaction(function () use ($inspection, $actor) {
            $this->auditService->deleted($inspection, $actor, 'inspection', $inspection->id);
            $this->activityService->log('inspection', $inspection->id, 'inspection.deleted', 'Inspeksi dihapus', $actor);
            $inspection->delete();
        });

        return redirect()->route('inspection.checklists.index')->with('success', 'Inspeksi berhasil dihapus.');
    }

    public function start(Inspection $inspection, Request $request): RedirectResponse
    {
        $actor = $request->user();
        try {
            DB::transaction(function () use ($inspection, $actor) {
                $this->workflowService->transition('inspection', $inspection->id, 'start', $actor);
                $inspection->update(['status' => 'in_progress', 'executed_at' => now()]);
                $this->activityService->log('inspection', $inspection->id, 'inspection.started', 'Inspeksi dimulai', $actor);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }
        return redirect()->route('inspection.checklists.show', $inspection)->with('success', 'Inspeksi dimulai.');
    }

    public function complete(Inspection $inspection, Request $request): RedirectResponse
    {
        $actor = $request->user();

        if (!$inspection->canBeCompleted()) {
            return back()->withErrors(['workflow' => 'Inspeksi tidak dapat diselesaikan: masih ada unit yang belum diinspeksi (pending). Batalkan unit yang tidak diinspeksi jika perlu.']);
        }

        try {
            DB::transaction(function () use ($inspection, $actor) {
                $this->workflowService->transition('inspection', $inspection->id, 'complete', $actor);

                // Calculate overall result
                $hasUnsafe = $inspection->results()->where('is_unsafe', true)->exists();
                $overallResult = $hasUnsafe ? 'fail' : 'pass';

                $inspection->update(['status' => 'completed', 'overall_result' => $overallResult]);
                $this->activityService->log('inspection', $inspection->id, 'inspection.completed', "Inspeksi selesai. Result: {$overallResult}", $actor);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }
        return redirect()->route('inspection.checklists.show', $inspection)->with('success', 'Inspeksi diselesaikan.');
    }

    public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $query = $listQuery->apply(
            Inspection::query()->with(['template', 'site', 'inspector']),
            ['inspection_number'], ['scheduled_at', 'created_at'], 'scheduled_at',
        );
        return $exporter->stream($query, [
            'Nomor' => 'inspection_number',
            'Template' => fn ($i) => $i->template?->name ?? '',
            'Site' => fn ($i) => $i->site?->name ?? '',
            'Inspector' => fn ($i) => $i->inspector?->name ?? '',
            'Status' => 'status',
            'Result' => 'overall_result',
            'Scheduled' => fn ($i) => $i->scheduled_at?->format('Y-m-d') ?? '',
        ], 'inspections-export.csv');
    }

    public function exportUnits(Inspection $inspection, CsvExporter $exporter): StreamedResponse
    {
        $inspection->load(['template.items', 'units.results']);
        $items = $inspection->template?->items ?? collect();

        $columns = [
            'Nomor Inspeksi' => fn () => $inspection->inspection_number,
            'Unit' => fn ($u) => $u->identifier,
            'Status Unit' => fn ($u) => $u->status,
        ];

        foreach ($items as $item) {
            $columns[$item->question] = function ($u) use ($item) {
                $result = $u->results->firstWhere('inspection_item_id', $item->id);
                return $result?->answer ?? '';
            };
        }

        $columns['Catatan Unit'] = fn ($u) => $u->notes ?? '';
        $columns['Alasan Batal'] = fn ($u) => $u->cancelled_reason ?? '';

        $query = $inspection->units()->with('results');

        return $exporter->stream($query, $columns, "inspection-{$inspection->inspection_number}-units-export.csv");
    }
}

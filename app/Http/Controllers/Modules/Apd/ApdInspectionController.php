<?php

namespace App\Http\Controllers\Modules\Apd;

use App\Core\Activity\ActivityService;
use App\Core\Export\CsvExporter;
use App\Core\Files\FileReference;
use App\Core\Files\ManagedFileService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Apd\StoreApdInspectionRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Files\ManagedFile;
use App\Models\Modules\Apd\ApdInspection;
use App\Models\Modules\Apd\ApdItem;
use App\Models\Modules\Capa\CapaAction;
use App\Modules\Apd\ApdAccess;
use App\Modules\Capa\CapaService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ApdInspectionController extends Controller
{
    public function __construct(
        private readonly ApdAccess $access,
        private readonly ManagedFileService $files,
        private readonly ActivityService $activity,
        private readonly CsvExporter $csvExporter,
        private readonly CapaService $capaService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ApdInspection::class);

        $query = $this->access->scopeInspection(
            ApdInspection::query()->with(['item.catalog', 'item.site', 'inspector']),
            $request->user(),
        );

        $listQuery = ListQuery::for($query, $request)
            ->search(['result', 'notes'], $request->input('search'))
            ->filter('result', $request->input('result'))
            ->filter('inspection_type', $request->input('inspection_type'))
            ->defaultSort('-inspection_date');

        $inspections = $listQuery->paginate(15);

        return Inertia::render('Modules/Apd/Inspections/Index', [
            'inspections' => $inspections,
            'filters' => $listQuery->filters(),
            'results' => ApdInspection::getResults(),
            'inspectionTypes' => ApdInspection::getInspectionTypes(),
            'can' => [
                'create' => $request->user()->can('create', ApdInspection::class),
                'export' => $request->user()->can('export', ApdInspection::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ApdInspection::class);

        $items = $this->access->scope(ApdItem::query(), $request->user())
            ->whereNotIn('status', ['disposed', 'lost'])
            ->with(['catalog', 'site'])
            ->orderBy('item_number')
            ->get(['id', 'item_number', 'status', 'condition', 'catalog_id', 'site_id']);

        return Inertia::render('Modules/Apd/Inspections/Form', [
            'inspection' => null,
            'items' => $items,
            'inspectionTypes' => ApdInspection::getInspectionTypes(),
            'results' => ApdInspection::getResults(),
            'conditions' => ApdItem::getConditions(),
            'can' => ['create' => true],
        ]);
    }

    public function store(StoreApdInspectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();

        try {
            $inspection = DB::transaction(function () use ($data, $actor, $request) {
                $item = ApdItem::findOrFail($data['apd_item_id']);

                $inspection = ApdInspection::create([
                    'apd_item_id' => $item->id,
                    'inspection_type' => $data['inspection_type'],
                    'inspected_by' => $actor->id,
                    'inspection_date' => $data['inspection_date'],
                    'result' => $data['result'],
                    'condition' => $data['condition'] ?? null,
                    'next_inspection_date' => $data['next_inspection_date'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                // Auto-mark item damaged when unfit.
                if ($data['result'] === 'tidak_layak') {
                    $wasStatus = $item->status;
                    $item->update([
                        'status' => 'damaged',
                        'condition' => $data['condition'] ?? 'poor',
                        'updated_by' => $actor->id,
                    ]);
                    $this->activity->log(
                        'apd',
                        $item->id,
                        'apd.item.damaged',
                        "Item {$item->item_number} ditandai rusak akibat inspeksi tidak layak ({$inspection->inspection_type}).",
                        $actor,
                        ['inspection_id' => $inspection->id, 'previous_status' => $wasStatus],
                    );
                } elseif (! empty($data['condition'])) {
                    $item->update(['condition' => $data['condition'], 'updated_by' => $actor->id]);
                }

                // Attach photos to the new inspection record.
                if ($request->hasFile('photos')) {
                    foreach ($request->file('photos') as $photo) {
                        $this->files->store(
                            $photo,
                            new FileReference('apd', $inspection->id, 'inspection'),
                            $actor,
                        );
                    }
                }

                $this->activity->log(
                    'apd',
                    $inspection->id,
                    'apd.inspection.created',
                    "Inspeksi {$inspection->inspection_type} untuk item {$item->item_number}: {$data['result']}.",
                    $actor,
                );

                return $inspection;
            });
        } catch (Throwable $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()->route('apd.inspections.show', $inspection)
            ->with('success', 'Inspeksi APD berhasil disimpan.');
    }

    public function show(Request $request, ApdInspection $apd_inspection): Response
    {
        $this->authorize('view', $apd_inspection);

        $apd_inspection->load(['item.catalog', 'item.site', 'item.area', 'item.department', 'inspector', 'creator', 'capaActions.assignedTo']);

        return Inertia::render('Modules/Apd/Inspections/Show', [
            'inspection' => $apd_inspection,
            'files' => ManagedFile::query()
                ->where('module_name', 'apd')
                ->where('reference_id', $apd_inspection->id)
                ->where('collection', 'inspection')
                ->active()
                ->latest()
                ->get(),
            'activities' => ActivityLog::query()
                ->where('module_name', 'apd')
                ->where('reference_id', $apd_inspection->id)
                ->latest()
                ->get(),
            'can' => [
                'update' => $request->user()->can('update', $apd_inspection),
                'delete' => $request->user()->can('delete', $apd_inspection),
                'escalate' => $request->user()->can('create', CapaAction::class),
            ],
            'users' => \App\Models\User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'priorities' => \App\Models\Core\MasterData\Priority::where('is_active', true)->orderBy('sla_days', 'desc')->get(['id', 'name']),
        ]);
    }

    /**
     * Escalate an unfit inspection to a CAPA action.
     */
    public function escalate(Request $request, ApdInspection $apd_inspection): RedirectResponse
    {
        $this->authorize('view', $apd_inspection);
        abort_unless($request->user()->can('create', CapaAction::class), 403);

        if ($apd_inspection->result !== 'tidak_layak') {
            return back()->with('error', 'Hanya inspeksi tidak layak yang dapat dieskalasi ke CAPA.');
        }

        $item = $apd_inspection->item;
        $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $action = $this->capaService->escalateFrom('apd_inspection', $apd_inspection->id, [
            'title' => 'Tindak lanjut APD tidak layak: ' . ($item?->item_number ?? $apd_inspection->id),
            'description' => 'Hasil inspeksi APD tidak layak (ID ' . $apd_inspection->id . '). ' . ($apd_inspection->notes ?? ''),
            'site_id' => $item?->site_id ?? $request->user()->employee?->site_id ?? 1,
            'department_id' => $item?->department_id,
            'assigned_to' => (int) $request->input('assigned_to'),
            'priority_id' => (int) $request->input('priority_id'),
            'due_date' => $request->input('due_date'),
            'source_type' => 'corrective',
        ], $request->user());

        return redirect()->route('capa.actions.show', $action)
            ->with('success', 'Inspeksi tidak layak dieskalasi ke CAPA ' . $action->action_number . '.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', ApdInspection::class);

        $query = $this->access->scopeInspection(
            ApdInspection::query()->with(['item.catalog', 'item.site', 'inspector']),
            $request->user(),
        );

        $listQuery = ListQuery::for($query, $request)
            ->search(['result', 'notes'], $request->input('search'))
            ->filter('result', $request->input('result'))
            ->defaultSort('-inspection_date');

        return $this->csvExporter->stream($query, [
            'No. Item' => fn (ApdInspection $i) => $i->item?->item_number,
            'Katalog' => fn (ApdInspection $i) => $i->item?->catalog?->name,
            'Tipe' => fn (ApdInspection $i) => ApdInspection::getInspectionTypes()[$i->inspection_type] ?? $i->inspection_type,
            'Tgl Inspeksi' => fn (ApdInspection $i) => $i->inspection_date?->format('Y-m-d'),
            'Hasil' => fn (ApdInspection $i) => ApdInspection::getResults()[$i->result] ?? $i->result,
            'Kondisi' => fn (ApdInspection $i) => $i->condition ? (ApdItem::getConditions()[$i->condition] ?? $i->condition) : '',
            'Inspektor' => fn (ApdInspection $i) => $i->inspector?->name,
            'Next' => fn (ApdInspection $i) => $i->next_inspection_date?->format('Y-m-d'),
            'Catatan' => 'notes',
        ], 'apd_inspections_export_'.now()->format('Ymd_His').'.csv');
    }
}

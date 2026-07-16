<?php

namespace App\Http\Controllers\Modules\Apd;

use App\Core\Activity\ActivityService;
use App\Core\Export\CsvExporter;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Apd\ReceiveApdItemRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\Files\ManagedFile;
use App\Models\Modules\Apd\ApdCatalog;
use App\Models\Modules\Apd\ApdItem;
use App\Modules\Apd\ApdAccess;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApdItemController extends Controller
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly ActivityService $activityService,
        private readonly ApdAccess $access,
        private readonly CsvExporter $csvExporter,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ApdItem::class);

        $query = $this->access->scope(
            ApdItem::query()->with(['catalog', 'site', 'department', 'creator']),
            $request->user(),
        );

        $listQuery = ListQuery::for($query, $request)
            ->search(['item_number', 'serial_number'], $request->input('search'))
            ->filter('catalog_id', $request->input('catalog_id'))
            ->filter('status', $request->input('status'))
            ->filter('site_id', $request->input('site_id'))
            ->filter('condition', $request->input('condition'))
            ->defaultSort('-created_at');

        $items = $listQuery->paginate(15);

        return Inertia::render('Modules/Apd/Items/Index', [
            'items' => $items,
            'filters' => $listQuery->filters(),
            'sites' => $this->access->sites($request->user()),
            'catalogs' => ApdCatalog::active()->orderBy('name')->get(['id', 'name', 'catalog_code']),
            'statuses' => ApdItem::getStatuses(),
            'conditions' => ApdItem::getConditions(),
            'can' => [
                'create' => $request->user()->can('create', ApdItem::class),
                'export' => $request->user()->can('export', ApdItem::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ApdItem::class);

        return Inertia::render('Modules/Apd/Items/CreateOrReceive', [
            'item' => null,
            'catalogs' => ApdCatalog::active()->orderBy('name')->get(['id', 'name', 'catalog_code', 'track_type']),
            'sites' => $this->access->sites($request->user()),
            'areas' => $this->access->areas($request->user()),
            'departments' => $this->access->departments($request->user()),
            'conditions' => ApdItem::getConditions(),
            'can' => ['create' => true],
        ]);
    }

    public function store(ReceiveApdItemRequest $request): RedirectResponse
    {
        $item = DB::transaction(function () use ($request): ApdItem {
            $validated = $request->validated();
            $catalog = ApdCatalog::findOrFail($validated['catalog_id']);

            $generated = $this->numberingService->generate(
                moduleName: 'apd',
                actor: $request->user(),
                referenceType: ApdItem::class,
            );

            $validated['item_number'] = $generated->number;
            $validated['created_by'] = $request->user()->id;
            $validated['updated_by'] = $request->user()->id;
            $validated['status'] = 'in_stock';
            $validated['condition'] = $validated['condition'] ?? 'new';
            $validated['quantity'] = $validated['track_type'] === 'batch'
                ? ($validated['quantity'] ?? 1)
                : 1;

            $baseDate = !empty($validated['received_date'])
                ? Carbon::parse($validated['received_date'])
                : now();
            if ($catalog->inspection_interval_days) {
                $validated['next_inspection_date'] = $baseDate->copy()->addDays($catalog->inspection_interval_days);
            }
            if ($catalog->default_lifespan_months) {
                $validated['expiry_date'] = $baseDate->copy()->addMonths($catalog->default_lifespan_months);
            }

            $item = ApdItem::create($validated);
            $generated->update(['reference_id' => $item->id]);

            $this->activityService->log(
                moduleName: 'apd',
                referenceId: $item->id,
                action: 'apd.item.received',
                description: "Item APD {$item->item_number} diterima (stok)",
                actor: $request->user(),
            );

            return $item;
        });

        return redirect()->route('apd.items.show', $item)->with('success', 'Item APD berhasil diterima ke stok.');
    }

    public function show(Request $request, ApdItem $apd_item): Response
    {
        $this->authorize('view', $apd_item);

        $apd_item->load([
            'catalog',
            'site',
            'area',
            'department',
            'creator',
            'updater',
        ]);

        return Inertia::render('Modules/Apd/Items/Show', [
            'item' => $apd_item,
            'comments' => \App\Models\Core\Comments\Comment::query()
                ->where('module_name', 'apd')
                ->where('reference_id', $apd_item->id)
                ->active()
                ->with('author:id,name')
                ->oldest()
                ->get(),
            'activities' => ActivityLog::query()
                ->where('module_name', 'apd')
                ->where('reference_id', $apd_item->id)
                ->latest()
                ->get(),
            'auditLogs' => AuditLog::query()
                ->where('module_name', 'apd')
                ->where('auditable_id', $apd_item->id)
                ->latest()
                ->get(),
            'can' => [
                'update' => $request->user()->can('update', $apd_item),
                'issue' => $request->user()->can('apd.issue'),
                'inspect' => $request->user()->can('apd.inspect'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', ApdItem::class);

        $query = $this->access->scope(ApdItem::query()->with(['catalog', 'site', 'department']), $request->user());

        $listQuery = ListQuery::for($query, $request)
            ->search(['item_number', 'serial_number'], $request->input('search'))
            ->filter('catalog_id', $request->input('catalog_id'))
            ->filter('status', $request->input('status'))
            ->filter('site_id', $request->input('site_id'))
            ->filter('condition', $request->input('condition'))
            ->defaultSort('-created_at');

        return $this->csvExporter->stream($query, [
            'Nomor Item' => 'item_number',
            'Katalog' => fn (ApdItem $i) => $i->catalog?->name,
            'Kode Katalog' => fn (ApdItem $i) => $i->catalog?->catalog_code,
            'Serial' => 'serial_number',
            'Tipe' => 'track_type',
            'Jumlah' => 'quantity',
            'Site' => fn (ApdItem $i) => $i->site?->name,
            'Department' => fn (ApdItem $i) => $i->department?->name,
            'Lokasi Simpan' => 'storage_location',
            'Status' => fn (ApdItem $i) => ApdItem::getStatuses()[$i->status] ?? $i->status,
            'Kondisi' => fn (ApdItem $i) => ApdItem::getConditions()[$i->condition] ?? $i->condition,
            'Tgl Beli' => fn (ApdItem $i) => $i->purchase_date?->format('Y-m-d'),
            'Tgl Terima' => fn (ApdItem $i) => $i->received_date?->format('Y-m-d'),
            'Kedaluwarsa' => fn (ApdItem $i) => $i->expiry_date?->format('Y-m-d'),
            'Inspeksi Berikutnya' => fn (ApdItem $i) => $i->next_inspection_date?->format('Y-m-d'),
            'Created At' => fn (ApdItem $i) => $i->created_at->format('Y-m-d H:i:s'),
        ], 'apd_items_export_'.now()->format('Ymd_His').'.csv');
    }
}

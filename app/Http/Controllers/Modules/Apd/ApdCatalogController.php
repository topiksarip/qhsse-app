<?php

namespace App\Http\Controllers\Modules\Apd;

use App\Core\Activity\ActivityService;
use App\Core\Export\CsvExporter;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Apd\StoreApdCatalogRequest;
use App\Http\Requests\Modules\Apd\UpdateApdCatalogRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Apd\ApdCatalog;
use App\Models\Modules\Apd\ApdItem;
use App\Modules\Apd\ApdAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApdCatalogController extends Controller
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly ActivityService $activityService,
        private readonly ApdAccess $access,
        private readonly CsvExporter $csvExporter,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ApdCatalog::class);

        $query = ListQuery::for(ApdCatalog::query()->with(['creator']), $request)
            ->search(['catalog_code', 'name', 'sku', 'manufacturer', 'model'], $request->input('search'))
            ->filter('category', $request->input('category'))
            ->filter('track_type', $request->input('track_type'))
            ->filter('is_active', $request->input('is_active'))
            ->defaultSort('-created_at');

        $catalogs = $query->paginate(15)->through(function (ApdCatalog $catalog): ApdCatalog {
            $catalog->setAttribute('active_quantity', $catalog->active_quantity);
            $catalog->setAttribute('low_stock', $catalog->low_stock);

            return $catalog;
        });

        return Inertia::render('Modules/Apd/Catalog/Index', [
            'catalogs' => $catalogs,
            'filters' => $query->filters(),
            'categories' => ApdCatalog::getCategories(),
            'trackTypes' => ApdCatalog::getTrackTypes(),
            'can' => [
                'create' => $request->user()->can('create', ApdCatalog::class),
                'export' => $request->user()->can('export', ApdCatalog::class),
                'delete' => $request->user()->can('delete', ApdCatalog::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ApdCatalog::class);

        return Inertia::render('Modules/Apd/Catalog/CreateOrEdit', [
            'catalog' => null,
            'categories' => ApdCatalog::getCategories(),
            'trackTypes' => ApdCatalog::getTrackTypes(),
            'protectionLevels' => ApdCatalog::getProtectionLevels(),
            'can' => ['create' => true],
        ]);
    }

    public function store(StoreApdCatalogRequest $request): RedirectResponse
    {
        $catalog = DB::transaction(function () use ($request): ApdCatalog {
            $validated = $request->validated();
            $generated = $this->numberingService->generate(
                moduleName: 'apd',
                actor: $request->user(),
                referenceType: ApdCatalog::class,
            );

            $validated['catalog_code'] = $generated->number;
            $validated['created_by'] = $request->user()->id;
            $validated['updated_by'] = $request->user()->id;
            $validated['is_active'] = $validated['is_active'] ?? true;

            $catalog = ApdCatalog::create($validated);
            $generated->update(['reference_id' => $catalog->id]);

            $this->activityService->log(
                moduleName: 'apd',
                referenceId: $catalog->id,
                action: 'apd.catalog.created',
                description: "Katalog APD {$catalog->catalog_code} - {$catalog->name} dibuat",
                actor: $request->user(),
            );

            return $catalog;
        });

        return redirect()->route('apd.catalogs.show', $catalog)->with('success', 'Katalog APD berhasil dibuat.');
    }

    public function show(Request $request, ApdCatalog $apd_catalog): Response
    {
        $this->authorize('view', $apd_catalog);

        $apd_catalog->load(['creator', 'updater']);
        $apd_catalog->loadCount(['items']);
        $apd_catalog->loadSum(['items as active_quantity' => fn ($q) => $q->whereIn('status', ['in_stock', 'issued'])], 'quantity');

        $items = ApdItem::query()
            ->where('catalog_id', $apd_catalog->id)
            ->with(['site', 'department'])
            ->latest()
            ->paginate(10, pageName: 'items_page');

        return Inertia::render('Modules/Apd/Catalog/Show', [
            'catalog' => $apd_catalog,
            'items' => $items,
            'activities' => ActivityLog::query()
                ->where('module_name', 'apd')
                ->where('reference_id', $apd_catalog->id)
                ->latest()
                ->get(),
            'auditLogs' => AuditLog::query()
                ->where('module_name', 'apd')
                ->where('auditable_id', $apd_catalog->id)
                ->latest()
                ->get(),
            'can' => [
                'update' => $request->user()->can('update', $apd_catalog),
                'delete' => $request->user()->can('delete', $apd_catalog),
            ],
        ]);
    }

    public function edit(Request $request, ApdCatalog $apd_catalog): Response
    {
        $this->authorize('update', $apd_catalog);

        return Inertia::render('Modules/Apd/Catalog/CreateOrEdit', [
            'catalog' => $apd_catalog->load(['site'])->toArray(),
            'categories' => ApdCatalog::getCategories(),
            'trackTypes' => ApdCatalog::getTrackTypes(),
            'protectionLevels' => ApdCatalog::getProtectionLevels(),
            'can' => ['update' => true],
        ]);
    }

    public function update(UpdateApdCatalogRequest $request, ApdCatalog $apd_catalog): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $apd_catalog, $validated): void {
            $validated['updated_by'] = $request->user()->id;
            $oldValues = $apd_catalog->only(array_keys($validated));
            $apd_catalog->update($validated);

            $this->activityService->log(
                moduleName: 'apd',
                referenceId: $apd_catalog->id,
                action: 'apd.catalog.updated',
                description: "Katalog APD {$apd_catalog->catalog_code} diperbarui",
                actor: $request->user(),
                metadata: ['old' => $oldValues, 'new' => $validated],
            );
        });

        return redirect()->route('apd.catalogs.show', $apd_catalog)->with('success', 'Katalog APD berhasil diperbarui.');
    }

    public function destroy(Request $request, ApdCatalog $apd_catalog): RedirectResponse
    {
        $this->authorize('delete', $apd_catalog);

        DB::transaction(function () use ($request, $apd_catalog): void {
            $code = $apd_catalog->catalog_code;
            $apd_catalog->delete();

            $this->activityService->log(
                moduleName: 'apd',
                referenceId: $apd_catalog->id,
                action: 'apd.catalog.deleted',
                description: "Katalog APD {$code} dihapus",
                actor: $request->user(),
            );
        });

        return redirect()->route('apd.catalogs.index')
            ->with('success', 'Katalog APD berhasil dihapus.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', ApdCatalog::class);

        $query = ApdCatalog::query()->with(['items']);

        $listQuery = ListQuery::for($query, $request)
            ->search(['catalog_code', 'name', 'sku'], $request->input('search'))
            ->filter('category', $request->input('category'))
            ->filter('track_type', $request->input('track_type'))
            ->filter('is_active', $request->input('is_active'))
            ->defaultSort('-created_at');

        return $this->csvExporter->stream($query, [
            'Kode Katalog' => 'catalog_code',
            'Nama' => 'name',
            'Kategori' => fn (ApdCatalog $c) => ApdCatalog::getCategories()[$c->category] ?? $c->category,
            'Tipe Pelacakan' => fn (ApdCatalog $c) => ApdCatalog::getTrackTypes()[$c->track_type] ?? $c->track_type,
            'SKU' => 'sku',
            'Manufacturer' => 'manufacturer',
            'Model' => 'model',
            'Standard' => 'standard',
            'Level Perlindungan' => 'protection_level',
            'Masa Pakai (bulan)' => 'default_lifespan_months',
            'Interval Inspeksi (hari)' => 'inspection_interval_days',
            'Biaya Satuan' => 'default_unit_cost',
            'Min Stok' => 'min_stock',
            'Titik Pemesanan' => 'reorder_point',
            'Stok Aktif' => fn (ApdCatalog $c) => $c->items->whereIn('status', ['in_stock', 'issued'])->sum('quantity'),
            'Status' => fn (ApdCatalog $c) => $c->is_active ? 'Aktif' : 'Nonaktif',
            'Created At' => fn (ApdCatalog $c) => $c->created_at->format('Y-m-d H:i:s'),
        ], 'apd_catalogs_export_'.now()->format('Ymd_His').'.csv');
    }
}

<?php

namespace App\Http\Controllers\Modules\Apd;

use App\Core\Export\CsvExporter;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Apd\ProcessApdIssuanceRequest;
use App\Http\Requests\Modules\Apd\StoreApdIssuanceRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Modules\Apd\ApdIssuance;
use App\Models\Modules\Apd\ApdItem;
use App\Modules\Apd\ApdAccess;
use App\Modules\Apd\ApdLifecycle;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ApdIssuanceController extends Controller
{
    public function __construct(
        private readonly ApdLifecycle $lifecycle,
        private readonly ApdAccess $access,
        private readonly CsvExporter $csvExporter,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ApdIssuance::class);

        $query = $this->access->scopeIssuance(
            ApdIssuance::query()->with(['item.catalog', 'item.site', 'holder', 'issuedBy', 'requestedBy']),
            $request->user(),
        );

        $listQuery = ListQuery::for($query, $request)
            ->search(['issue_number', 'notes'], $request->input('search'))
            ->filter('status', $request->input('status'))
            ->filter('holder_type', $request->input('holder_type'))
            ->defaultSort('-created_at');

        $issuances = $listQuery->paginate(15);

        return Inertia::render('Modules/Apd/Issuances/Index', [
            'issuances' => $issuances,
            'filters' => $listQuery->filters(),
            'statuses' => ApdIssuance::getStatuses(),
            'holderTypes' => ApdIssuance::$holderTypes,
            'can' => [
                'create' => $request->user()->can('create', ApdIssuance::class),
                'export' => $request->user()->can('export', ApdIssuance::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ApdIssuance::class);

        return Inertia::render('Modules/Apd/Issuances/Form', [
            'issuance' => null,
            'items' => ApdItem::query()
                ->where('status', 'in_stock')
                ->with(['catalog', 'site'])
                ->orderBy('item_number')
                ->get(['id', 'item_number', 'track_type', 'serial_number', 'catalog_id', 'site_id']),
            'employees' => $this->access->employees($request->user()),
            'contractors' => $this->access->contractors($request->user()),
            'locations' => $this->access->areas($request->user()),
            'conditions' => ApdIssuance::getConditions(),
            'can' => ['create' => true],
        ]);
    }

    public function store(StoreApdIssuanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $asRequest = (bool) ($data['start_as_request'] ?? false);

        try {
            $issuance = $this->lifecycle->create($data, $request->user(), $asRequest);
        } catch (Throwable $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        $route = $asRequest ? 'apd.issuances.show' : 'apd.issuances.show';
        $message = $asRequest ? 'Permintaan APD berhasil diajukan.' : 'APD berhasil diissue.';

        return redirect()->route($route, $issuance)->with('success', $message);
    }

    public function show(Request $request, ApdIssuance $apd_issuance): Response
    {
        $this->authorize('view', $apd_issuance);

        $apd_issuance->load([
            'item.catalog',
            'item.site',
            'item.area',
            'item.department',
            'holder',
            'requestedBy',
            'approvedBy',
            'issuedBy',
            'returnedBy',
            'creator',
        ]);

        $workflow = app(\App\Core\Workflow\WorkflowService::class)->getWorkflow('apd', $apd_issuance->id);

        $available = collect($workflow['available_transitions'])
            ->map(fn ($t) => ['action_key' => $t->action_key, 'action_label' => $t->action_label, 'requires_reason' => $t->requires_reason])
            ->all();

        return Inertia::render('Modules/Apd/Issuances/Show', [
            'issuance' => $apd_issuance,
            'workflow' => [
                'current_status' => $workflow['current_status'],
                'history' => $workflow['history'],
                'available_transitions' => $available,
            ],
            'activities' => ActivityLog::query()
                ->where('module_name', 'apd')
                ->where('reference_id', $apd_issuance->id)
                ->latest()
                ->get(),
            'can' => [
                'request' => $request->user()->can('request', $apd_issuance),
                'approve' => $request->user()->can('approve', $apd_issuance),
                'issue' => $request->user()->can('issue', $apd_issuance),
                'receive' => $request->user()->can('receive', $apd_issuance),
                'inspect' => $request->user()->can('apd.inspect'),
            ],
        ]);
    }

    public function request(Request $request, ApdIssuance $apd_issuance): RedirectResponse
    {
        $this->authorize('request', $apd_issuance);
        $this->lifecycle->request($apd_issuance, $request->user());

        return redirect()->route('apd.issuances.show', $apd_issuance)->with('success', 'Permintaan diajukan.');
    }

    public function approve(Request $request, ApdIssuance $apd_issuance): RedirectResponse
    {
        $this->authorize('approve', $apd_issuance);
        $this->lifecycle->approve($apd_issuance, $request->user());

        return redirect()->route('apd.issuances.show', $apd_issuance)->with('success', 'Permintaan disetujui.');
    }

    public function issue(Request $request, ApdIssuance $apd_issuance): RedirectResponse
    {
        $this->authorize('issue', $apd_issuance);
        $this->lifecycle->issue($apd_issuance, $request->user(), $request->only(['condition_out', 'issue_date']));

        return redirect()->route('apd.issuances.show', $apd_issuance)->with('success', 'APD diissue.');
    }

    public function process(ProcessApdIssuanceRequest $request, ApdIssuance $apd_issuance): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();

        if ($data['action'] === 'return') {
            $this->authorize('receive', $apd_issuance);
            if (! $actor->can('apd.issue') && ! $actor->can('apd.receive')) {
                abort(403);
            }
            $this->lifecycle->return($apd_issuance, $actor, $data['condition_in'] ?? null, $data['returned_date'] ?? null);
            $message = 'APD dikembalikan ke stok.';
        } elseif ($data['action'] === 'dispose') {
            $this->authorize('issue', $apd_issuance);
            $this->lifecycle->dispose($apd_issuance, $actor, $data['reason'] ?? null);
            $message = 'APD dimusnahkan.';
        } elseif ($data['action'] === 'reject') {
            $this->authorize('approve', $apd_issuance);
            $this->lifecycle->reject($apd_issuance, $actor, $data['reason'] ?? null);
            $message = 'Permintaan ditolak.';
        } else {
            abort(422, 'Aksi tidak dikenal.');
        }

        return redirect()->route('apd.issuances.show', $apd_issuance)->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', ApdIssuance::class);

        $query = $this->access->scopeIssuance(
            ApdIssuance::query()->with(['item.catalog', 'item.site', 'holder']),
            $request->user(),
        );

        $listQuery = ListQuery::for($query, $request)
            ->search(['issue_number', 'notes'], $request->input('search'))
            ->filter('status', $request->input('status'))
            ->defaultSort('-created_at');

        return $this->csvExporter->stream($query, [
            'No. Issue' => 'issue_number',
            'Item' => fn (ApdIssuance $i) => $i->item?->item_number,
            'Katalog' => fn (ApdIssuance $i) => $i->item?->catalog?->name,
            'Pemegang' => fn (ApdIssuance $i) => $i->holder_label,
            'Qty' => 'quantity',
            'Status' => fn (ApdIssuance $i) => ApdIssuance::getStatuses()[$i->status] ?? $i->status,
            'Tgl Issue' => fn (ApdIssuance $i) => $i->issue_date?->format('Y-m-d'),
            'Tgl Kembali' => fn (ApdIssuance $i) => $i->returned_date?->format('Y-m-d'),
            'Catatan' => 'notes',
        ], 'apd_issuances_export_'.now()->format('Ymd_His').'.csv');
    }
}

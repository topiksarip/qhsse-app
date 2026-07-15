<?php

namespace App\Http\Controllers\Modules\Environment;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Environment\StoreEnvironmentalRecordRequest;
use App\Http\Requests\Modules\Environment\UpdateEnvironmentalRecordRequest;
use App\Core\Notifications\NotificationService;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Capa\CapaAction;
use App\Models\Modules\Environment\EnvironmentalRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EnvironmentalRecordController extends Controller
{
    public function __construct(
        protected NumberingService $numberingService,
        protected AuditService $auditService,
        protected ActivityService $activityService,
        protected NotificationService $notificationService,
    ) {
        $this->authorizeResource(EnvironmentalRecord::class, 'environmental_record');
    }

    public function index(Request $request): InertiaResponse
    {
        $query = EnvironmentalRecord::query()
            ->with(['site', 'area', 'reporter', 'capaAction'])
            ->select('environmental_records.*');

        // Organization scope
        $scope = $request->input('scope', 'all');
        $user = $request->user();

        if ($scope === 'site' && $user->employee?->site_id) {
            $query->where('environmental_records.site_id', $user->employee->site_id);
        } elseif ($scope === 'department' && $user->employee?->department_id) {
            // Department scope via site
            $query->where('environmental_records.site_id', $user->employee->site_id);
        } elseif ($scope === 'own') {
            $query->where('environmental_records.reporter_id', $user->id);
        }

        // Filters
        if ($request->filled('site_id')) {
            $query->where('environmental_records.site_id', $request->input('site_id'));
        }

        if ($request->filled('type')) {
            $query->where('environmental_records.type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('environmental_records.status', $request->input('status'));
        }

        if ($request->filled('is_exceedance')) {
            $query->where('environmental_records.is_exceedance', $request->boolean('is_exceedance'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('environmental_records.record_number', 'like', "%{$search}%")
                    ->orWhere('environmental_records.title', 'like', "%{$search}%")
                    ->orWhere('environmental_records.description', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('environmental_records.occurred_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('environmental_records.occurred_at', '<=', $request->input('date_to'));
        }

        $records = ListQuery::for($query)
            ->defaultSort('-created_at')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        return Inertia::render('Modules/Environmental/Index', [
            'records' => $records,
            'filters' => $request->only(['scope', 'site_id', 'type', 'status', 'is_exceedance', 'search', 'date_from', 'date_to']),
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'types' => EnvironmentalRecord::getTypes(),
            'statuses' => EnvironmentalRecord::getStatuses(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Modules/Environmental/Form', [
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'areas' => Area::select('id', 'site_id', 'name')->orderBy('name')->get(),
            'types' => EnvironmentalRecord::getTypes(),
        ]);
    }

    public function store(StoreEnvironmentalRecordRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = $request->user();

            // Generate record number
            $recordNumber = $this->numberingService->generate(
                key: 'environment',
                siteId: null,
                includeSiteCode: false
            );

            // Create record
            $data = $request->validated();
            $data['record_number'] = $recordNumber;
            $data['reporter_id'] = $user->id;

            $record = EnvironmentalRecord::create($data);

            // Calculate exceedance if applicable
            if ($record->measured_value !== null && $record->limit_value !== null) {
                $record->calculateExceedance();
                $record->save();
            }

            // Audit trail
            $this->auditService->log(
                moduleName: 'environment',
                action: 'create',
                referenceId: $record->id,
                details: "Environmental record {$record->record_number} dibuat",
                userId: $user->id
            );

            // Activity log
            $this->activityService->log(
                moduleName: 'environment',
                referenceId: $record->id,
                action: 'create',
                description: "Environmental record {$record->record_number} dibuat oleh {$user->name}",
                userId: $user->id
            );

            // Alert if exceedance
            if ($record->is_exceedance) {
                $this->activityService->log(
                    moduleName: 'environment',
                    referenceId: $record->id,
                    action: 'exceedance_detected',
                    description: "EXCEEDANCE detected: {$record->measured_value} {$record->unit} > {$record->limit_value} {$record->unit}",
                    userId: $user->id
                );
            }

            return redirect()->route('environment.records.show', $record)
                ->with('success', "Environmental record berhasil dibuat dengan nomor {$record->record_number}");
        });
    }

    public function show(EnvironmentalRecord $environmentalRecord): InertiaResponse
    {
        $environmentalRecord->load([
            'site',
            'area',
            'reporter.employee',
            'capaAction',
        ]);

        return Inertia::render('Modules/Environmental/Show', [
            'record' => $environmentalRecord,
        ]);
    }

    public function edit(EnvironmentalRecord $environmentalRecord): InertiaResponse
    {
        return Inertia::render('Modules/Environmental/Form', [
            'record' => $environmentalRecord,
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'areas' => Area::select('id', 'site_id', 'name')->orderBy('name')->get(),
            'types' => EnvironmentalRecord::getTypes(),
            'statuses' => EnvironmentalRecord::getStatuses(),
        ]);
    }

    public function update(UpdateEnvironmentalRecordRequest $request, EnvironmentalRecord $environmentalRecord)
    {
        return DB::transaction(function () use ($request, $environmentalRecord) {
            $user = $request->user();

            $environmentalRecord->update($request->validated());

            // Recalculate exceedance if measurement values changed
            if ($environmentalRecord->isDirty(['measured_value', 'limit_value'])) {
                $environmentalRecord->calculateExceedance();
                $environmentalRecord->save();

                // Log if new exceedance detected
                if ($environmentalRecord->is_exceedance && $environmentalRecord->wasChanged('is_exceedance')) {
                    $this->activityService->log(
                        moduleName: 'environment',
                        referenceId: $environmentalRecord->id,
                        action: 'exceedance_detected',
                        description: "EXCEEDANCE detected: {$environmentalRecord->measured_value} {$environmentalRecord->unit} > {$environmentalRecord->limit_value} {$environmentalRecord->unit}",
                        userId: $user->id
                    );
                }
            }

            // Audit trail
            $this->auditService->log(
                moduleName: 'environment',
                action: 'update',
                referenceId: $environmentalRecord->id,
                details: "Environmental record {$environmentalRecord->record_number} diupdate",
                userId: $user->id
            );

            return redirect()->route('environment.records.show', $environmentalRecord)
                ->with('success', 'Environmental record berhasil diupdate');
        });
    }

    public function investigate(EnvironmentalRecord $environmentalRecord, Request $request): RedirectResponse
    {
        $this->authorize('investigate', $environmentalRecord);
        abort_if($environmentalRecord->status !== 'recorded', 400, 'Record tidak dapat diinvestigasi dari status saat ini.');

        $actor = $request->user();
        $oldStatus = $environmentalRecord->status;

        $environmentalRecord->update(['status' => 'investigated']);

        $this->auditService->log(
            'environment.investigated',
            $environmentalRecord,
            ['status' => $oldStatus],
            ['status' => 'investigated'],
            $actor,
            'environment',
            $environmentalRecord->id,
        );

        $this->activityService->log(
            moduleName: 'environment',
            referenceId: $environmentalRecord->id,
            action: 'environment.investigated',
            description: "Record {$environmentalRecord->record_number} mulai diinvestigasi oleh {$actor->name}",
            userId: $actor->id,
        );

        if ($environmentalRecord->reporter_id) {
            $this->notificationService->notify(
                User::findOrFail($environmentalRecord->reporter_id),
                'environment.investigated',
                [
                    'record_number' => $environmentalRecord->record_number,
                    'title' => $environmentalRecord->title,
                ],
                $actor,
                'environment',
                $environmentalRecord->id,
                route('environment.records.show', $environmentalRecord),
            );
        }

        return back()->with('success', 'Record mulai diinvestigasi.');
    }

    public function openAction(EnvironmentalRecord $environmentalRecord, Request $request): RedirectResponse
    {
        $this->authorize('investigate', $environmentalRecord);
        abort_if($environmentalRecord->status !== 'investigated', 400, 'CAPA hanya dapat dibuka dari status investigated.');

        $actor = $request->user();
        $oldStatus = $environmentalRecord->status;

        $capaAction = DB::transaction(function () use ($environmentalRecord, $actor): CapaAction {
            $capaAction = CapaAction::create([
                'action_number' => 'TEMP-'.uniqid(),
                'title' => "CAPA: {$environmentalRecord->record_number}",
                'description' => $environmentalRecord->description,
                'source_module' => 'environment',
                'source_reference_id' => $environmentalRecord->id,
                'source_type' => EnvironmentalRecord::class,
                'site_id' => $environmentalRecord->site_id,
                'department_id' => $environmentalRecord->area?->department_id,
                'assigned_to' => $actor->id,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'priority_id' => Priority::firstOrCreate(
                    ['code' => 'medium'],
                    ['name' => 'Medium', 'sla_days' => 7, 'color' => '#f59e0b', 'is_active' => true],
                )->id,
                'status' => 'open',
            ]);

            $generated = $this->numberingService->generate(
                moduleName: 'capa',
                actor: $actor,
                referenceType: CapaAction::class,
                referenceId: $capaAction->id,
            );
            $capaAction->update(['action_number' => $generated->number]);

            return $capaAction;
        });

        $environmentalRecord->update([
            'status' => 'action_open',
            'capa_action_id' => $capaAction->id,
        ]);

        $this->auditService->log(
            'environment.action_opened',
            $environmentalRecord,
            ['status' => $oldStatus],
            ['status' => 'action_open', 'capa_action_id' => $capaAction->id],
            $actor,
            'environment',
            $environmentalRecord->id,
        );

        $this->activityService->log(
            moduleName: 'environment',
            referenceId: $environmentalRecord->id,
            action: 'environment.action_opened',
            description: "CAPA {$capaAction->action_number} dibuka untuk record {$environmentalRecord->record_number}",
            userId: $actor->id,
        );

        return back()->with('success', 'CAPA berhasil dibuka.');
    }

    public function close(EnvironmentalRecord $environmentalRecord, Request $request): RedirectResponse
    {
        $this->authorize('close', $environmentalRecord);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        abort_if($environmentalRecord->status === 'closed', 400, 'Record sudah ditutup.');

        $actor = $request->user();
        $oldStatus = $environmentalRecord->status;

        $environmentalRecord->update(['status' => 'closed']);

        $this->auditService->log(
            'environment.closed',
            $environmentalRecord,
            ['status' => $oldStatus],
            ['status' => 'closed', 'reason' => $validated['reason']],
            $actor,
            'environment',
            $environmentalRecord->id,
        );

        $this->activityService->log(
            moduleName: 'environment',
            referenceId: $environmentalRecord->id,
            action: 'environment.closed',
            description: "Record {$environmentalRecord->record_number} ditutup oleh {$actor->name}. Alasan: {$validated['reason']}",
            userId: $actor->id,
        );

        if ($environmentalRecord->reporter_id) {
            $this->notificationService->notify(
                User::findOrFail($environmentalRecord->reporter_id),
                'environment.closed',
                [
                    'record_number' => $environmentalRecord->record_number,
                    'title' => $environmentalRecord->title,
                    'reason' => $validated['reason'],
                ],
                $actor,
                'environment',
                $environmentalRecord->id,
                route('environment.records.show', $environmentalRecord),
            );
        }

        return back()->with('success', 'Record berhasil ditutup.');
    }

    public function destroy(Request $request, EnvironmentalRecord $environmentalRecord): RedirectResponse
    {
        $this->authorize('delete', $environmentalRecord);

        $user = $request->user();
        DB::transaction(function () use ($environmentalRecord, $user) {
            $this->auditService->deleted($environmentalRecord, $user, 'environment', $environmentalRecord->id);
            $this->activityService->log('environment', $environmentalRecord->id, 'environment.record.deleted', "Environmental record deleted", $user);
            $environmentalRecord->delete();
        });

        return redirect()->route('environment.records.index')->with('success', 'Environmental record deleted.');
    }

    public function export(Request $request)
    {
        $this->authorize('export', EnvironmentalRecord::class);

        $query = EnvironmentalRecord::query()->with(['site', 'reporter']);

        // Apply same filters as index
        $scope = $request->input('scope', 'all');
        $user = $request->user();

        if ($scope === 'site' && $user->employee?->site_id) {
            $query->where('environmental_records.site_id', $user->employee->site_id);
        } elseif ($scope === 'department' && $user->employee?->department_id) {
            $query->where('environmental_records.site_id', $user->employee->site_id);
        } elseif ($scope === 'own') {
            $query->where('environmental_records.reporter_id', $user->id);
        }

        if ($request->filled('site_id')) {
            $query->where('environmental_records.site_id', $request->input('site_id'));
        }

        if ($request->filled('type')) {
            $query->where('environmental_records.type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('environmental_records.status', $request->input('status'));
        }

        if ($request->filled('is_exceedance')) {
            $query->where('environmental_records.is_exceedance', $request->boolean('is_exceedance'));
        }

        $records = $query->orderBy('created_at', 'desc')->get();

        return CsvExporter::export(
            data: $records,
            filename: 'environmental_records_'.now()->format('Y-m-d_His').'.csv',
            columns: [
                'record_number' => 'Record Number',
                'type' => 'Type',
                'title' => 'Title',
                'site.name' => 'Site',
                'occurred_at' => 'Occurred At',
                'measured_value' => 'Measured Value',
                'unit' => 'Unit',
                'limit_value' => 'Limit Value',
                'is_exceedance' => 'Exceedance',
                'status' => 'Status',
                'reporter.name' => 'Reporter',
                'created_at' => 'Created At',
            ]
        );
    }
}


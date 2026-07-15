<?php

namespace App\Http\Controllers\Modules\Training;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Files\ManagedFileService;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Modules\Training\TrainingAccess;
use App\Http\Requests\Modules\Training\StoreTrainingRecordRequest;
use App\Http\Requests\Modules\Training\UpdateTrainingRecordRequest;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Training\TrainingProgram;
use App\Models\Modules\Training\TrainingRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrainingRecordController extends Controller
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly ManagedFileService $fileService,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
        private readonly NotificationService $notificationService,
        private readonly TrainingAccess $access,
    ) {}

    /**
     * Display a listing of training records.
     */
    public function index(Request $request, ListQuery $listQuery): Response
    {
        $user = $request->user();

        $query = TrainingRecord::query()
            ->with(['employee', 'trainingProgram'])
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->get('program_id'), fn ($q, $programId) => $q->where('training_program_id', $programId))
            ->when($request->get('employee_id'), fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($request->has('expired_only'), function ($q) {
                $q->where('status', 'completed')
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', now());
            })
            ->orderBy('created_at', 'desc');

        // Apply scope via core.scope.* permissions (not hardcoded roles)
        $this->access->scope($query, $user);

        $records = $listQuery->paginate(
            $query,
            ['training_number', 'provider'],
            ['training_number', 'start_date', 'created_at'],
            'created_at',
            (int) $request->get('per_page', 15)
        );

        return Inertia::render('Modules/Training/Records/Index', [
            'records' => $records,
            'filters' => $request->only(['search', 'status', 'program_id', 'employee_id', 'expired_only']),
            'statuses' => TrainingRecord::getStatuses(),
            'programs' => TrainingProgram::active()->get(['id', 'name', 'code']),
            'can' => [
                'create' => $user->can('training.records.create'),
                'update' => $user->can('training.records.update'),
                'view' => $user->can('training.records.view'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new training record.
     */
    public function create(): Response
    {
        $this->authorize('training.records.create');

        $user = auth()->user();
        $employeeQuery = Employee::where('is_active', true);

        // Apply organizational scope via core.scope.* (not hardcoded roles)
        $this->access->employeeScope($employeeQuery, $user);

        return Inertia::render('Modules/Training/Records/CreateOrEdit', [
            'record' => null,
            'programs' => TrainingProgram::active()->get(['id', 'name', 'code', 'duration_hours', 'is_certification', 'validity_months']),
            'employees' => $employeeQuery->get(['id', 'name', 'employee_no']),
            'statuses' => TrainingRecord::getStatuses(),
            'results' => TrainingRecord::getResults(),
        ]);
    }

    /**
     * Store a newly created training record in storage.
     */
    public function store(StoreTrainingRecordRequest $request): RedirectResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            // Generate training number
            $data['training_number'] = $this->numberingService->generate('training');
            $data['status'] = 'scheduled';

            $record = TrainingRecord::create($data);

            $this->auditService->log(
                'training',
                'create',
                $record->id,
                'Created training record: '.$record->training_number
            );

            $this->activityService->log(
                'training',
                $record->id,
                'created',
                'Record pelatihan "'.$record->training_number.'" dibuat',
                null,
                $record->toArray()
            );

            // Notify employee
            $this->notificationService->notify(
                $record->employee->user_id,
                'Training Scheduled',
                "Anda dijadwalkan untuk mengikuti pelatihan: {$record->trainingProgram->name}",
                route('training.records.show', $record)
            );

            return redirect()->route('training.records.show', $record)
                ->with('success', 'Record pelatihan berhasil dibuat dengan nomor '.$record->training_number);
        });
    }

    /**
     * Display the specified training record.
     */
    public function show(TrainingRecord $record): Response
    {
        $this->authorize('view', $record);

        $record->load(['employee', 'trainingProgram', 'certificateFile']);

        $user = auth()->user();

        return Inertia::render('Modules/Training/Records/Show', [
            'record' => $record,
            'can' => [
                'update' => $user->can('update', $record),
                'delete' => $user->can('delete', $record),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified training record.
     */
    public function edit(TrainingRecord $record): Response
    {
        $this->authorize('update', $record);

        $record->load(['employee', 'trainingProgram']);

        $user = auth()->user();
        $employeeQuery = Employee::where('is_active', true);

        // Apply organizational scope via core.scope.* (not hardcoded roles)
        $this->access->employeeScope($employeeQuery, $user);

        return Inertia::render('Modules/Training/Records/CreateOrEdit', [
            'record' => $record,
            'programs' => TrainingProgram::active()->get(['id', 'name', 'code', 'duration_hours', 'is_certification', 'validity_months']),
            'employees' => $employeeQuery->get(['id', 'name', 'employee_no']),
            'statuses' => TrainingRecord::getStatuses(),
            'results' => TrainingRecord::getResults(),
        ]);
    }

    /**
     * Update the specified training record in storage.
     */
    public function update(UpdateTrainingRecordRequest $request, TrainingRecord $record): RedirectResponse
    {
        $this->authorize('update', $record);

        return DB::transaction(function () use ($request, $record) {
            $oldData = $record->toArray();
            $data = $request->validated();

            // Handle certificate file upload
            if ($request->hasFile('certificate_file')) {
                $file = $request->file('certificate_file');
                $managedFile = $this->fileService->store(
                    $file,
                    'training',
                    $record->id,
                    'certificate',
                    $record->training_number.'-certificate'
                );
                $data['certificate_file_id'] = $managedFile->id;
            }

            // Auto-calculate expiry date if completed and program has validity
            if (isset($data['status']) && $data['status'] === 'completed' && isset($data['end_date'])) {
                $program = $record->trainingProgram;
                if ($program->validity_months) {
                    $data['expiry_date'] = now()->parse($data['end_date'])->addMonths($program->validity_months);
                }
            }

            $record->update($data);

            $this->auditService->log(
                'training',
                'update',
                $record->id,
                'Updated training record: '.$record->training_number
            );

            $this->activityService->log(
                'training',
                $record->id,
                'updated',
                'Record pelatihan "'.$record->training_number.'" diperbarui',
                $oldData,
                $record->fresh()->toArray()
            );

            // Notify if status changed to completed
            if (isset($data['status']) && $data['status'] === 'completed' && $oldData['status'] !== 'completed') {
                $this->notificationService->notify(
                    $record->employee->user_id,
                    'Training Completed',
                    "Pelatihan {$record->trainingProgram->name} telah selesai",
                    route('training.records.show', $record)
                );
            }

            return redirect()->route('training.records.show', $record)
                ->with('success', 'Record pelatihan berhasil diperbarui.');
        });
    }

    /**
     * Export training records to CSV.
     */
    public function destroy(Request $request, TrainingRecord $record): RedirectResponse
    {
        $this->authorize('delete', $record);

        $actor = $request->user();
        DB::transaction(function () use ($record, $actor) {
            $this->auditService->deleted($record, $actor, 'training', $record->id);
            $this->activityService->log('training', $record->id, 'training.record.deleted', "Training record {$record->training_number} deleted", $actor);
            $record->delete();
        });

        return redirect()->route('training.records.index')->with('success', 'Training record deleted.');
    }

    public function export(Request $request, CsvExporter $exporter)
    {
        $this->authorize('training.records.export');

        $user = auth()->user();
        $query = TrainingRecord::query()
            ->with(['employee', 'trainingProgram'])
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->get('program_id'), fn ($q, $programId) => $q->where('training_program_id', $programId));

        // Apply organizational scope via core.scope.* (not hardcoded roles)
        $this->access->scope($query, $user);

        $query->orderBy('created_at', 'desc');
        $records = $query->get();

        $data = $records->map(function ($record) {
            return [
                'Nomor' => $record->training_number,
                'Karyawan' => $record->employee->name,
                'Program' => $record->trainingProgram->name,
                'Provider' => $record->provider ?? '-',
                'Tanggal Mulai' => $record->start_date->format('Y-m-d'),
                'Tanggal Selesai' => $record->end_date?->format('Y-m-d') ?? '-',
                'Status' => $record->status,
                'Nilai' => $record->score ?? '-',
                'Hasil' => $record->result ?? '-',
                'Nomor Sertifikat' => $record->certificate_number ?? '-',
                'Tanggal Kedaluwarsa' => $record->expiry_date?->format('Y-m-d') ?? '-',
                'Dibuat' => $record->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return $exporter->export('training-records', $data);
    }
}

<?php

namespace App\Http\Controllers\Modules\Training;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Core\Services\Files\PrivateFileService;
use App\Http\Controllers\Controller;
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
        private readonly ListQuery $listQuery,
        private readonly NumberingService $numberingService,
        private readonly PrivateFileService $fileService,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Display a listing of training records.
     */
    public function index(Request $request): Response
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

        // Apply scope based on role
        if (! $user->hasRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Auditor'])) {
            if ($user->hasRole('QHSSE Officer')) {
                // Officer can see their site's records
                $query->whereHas('employee', function ($q) use ($user) {
                    if ($user->employee && $user->employee->site_id) {
                        $q->where('site_id', $user->employee->site_id);
                    }
                });
            } elseif ($user->hasRole(['Supervisor', 'Department Head'])) {
                // Can see their department's records
                $query->whereHas('employee', function ($q) use ($user) {
                    if ($user->employee && $user->employee->department_id) {
                        $q->where('department_id', $user->employee->department_id);
                    }
                });
            } else {
                // Employee can only see own records
                $query->where('employee_id', $user->employee?->id);
            }
        }

        $records = $this->listQuery->paginate(
            $query,
            $request->get('search'),
            ['training_number', 'provider'],
            (int) $request->get('per_page', 15)
        );

        return Inertia::render('Modules/Training/Records/Index', [
            'records' => $records,
            'filters' => $request->only(['search', 'status', 'program_id', 'employee_id', 'expired_only']),
            'statuses' => TrainingRecord::getStatuses(),
            'programs' => TrainingProgram::active()->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Show the form for creating a new training record.
     */
    public function create(): Response
    {
        $this->authorize('training.records.create');

        return Inertia::render('Modules/Training/Records/Form', [
            'programs' => TrainingProgram::active()->get(['id', 'name', 'code', 'duration_hours', 'is_certification', 'validity_months']),
            'employees' => Employee::where('is_active', true)->get(['id', 'name', 'employee_no']),
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
        $this->authorize('training.records.view');

        $record->load(['employee', 'trainingProgram', 'certificateFile']);

        return Inertia::render('Modules/Training/Records/Show', [
            'record' => $record,
        ]);
    }

    /**
     * Show the form for editing the specified training record.
     */
    public function edit(TrainingRecord $record): Response
    {
        $this->authorize('training.records.update');

        $record->load(['employee', 'trainingProgram']);

        return Inertia::render('Modules/Training/Records/Form', [
            'record' => $record,
            'programs' => TrainingProgram::active()->get(['id', 'name', 'code', 'duration_hours', 'is_certification', 'validity_months']),
            'employees' => Employee::where('is_active', true)->get(['id', 'name', 'employee_no']),
            'statuses' => TrainingRecord::getStatuses(),
            'results' => TrainingRecord::getResults(),
        ]);
    }

    /**
     * Update the specified training record in storage.
     */
    public function update(UpdateTrainingRecordRequest $request, TrainingRecord $record): RedirectResponse
    {
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
    public function export(Request $request, CsvExporter $exporter)
    {
        $this->authorize('training.records.export');

        $query = TrainingRecord::query()
            ->with(['employee', 'trainingProgram'])
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->get('program_id'), fn ($q, $programId) => $q->where('training_program_id', $programId))
            ->orderBy('created_at', 'desc');

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
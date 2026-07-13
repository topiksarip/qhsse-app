<?php

namespace App\Http\Controllers\Modules\Training;

use App\Http\Controllers\Controller;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Training\TrainingProgram;
use App\Models\Modules\Training\TrainingRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingMatrixController extends Controller
{
    /**
     * Display the training matrix.
     */
    public function index(Request $request): Response
    {
        $this->authorize('training.records.view');

        $user = $request->user();

        // Get employees based on scope
        $employeesQuery = Employee::query()
            ->where('is_active', true)
            ->with(['department', 'site']);

        // Apply scope based on role
        if (! $user->hasRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Auditor'])) {
            if ($user->hasRole('QHSSE Officer')) {
                if ($user->employee && $user->employee->site_id) {
                    $employeesQuery->where('site_id', $user->employee->site_id);
                }
            } elseif ($user->hasRole(['Supervisor', 'Department Head'])) {
                if ($user->employee && $user->employee->department_id) {
                    $employeesQuery->where('department_id', $user->employee->department_id);
                }
            } else {
                // Employee can only see themselves
                $employeesQuery->where('id', $user->employee?->id);
            }
        }

        // Filter by department if requested
        if ($request->get('department_id')) {
            $employeesQuery->where('department_id', $request->get('department_id'));
        }

        $employees = $employeesQuery->orderBy('name')->get();

        // Get active programs
        $programs = TrainingProgram::active()
            ->when($request->get('category'), fn ($q, $category) => $q->where('category', $category))
            ->orderBy('name')
            ->get();

        // Build matrix data
        $matrix = [];
        foreach ($employees as $employee) {
            $row = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'employee_no' => $employee->employee_no,
                'department' => $employee->department?->name,
                'programs' => [],
            ];

            foreach ($programs as $program) {
                // Get latest training record for this employee-program combination
                $record = TrainingRecord::where('employee_id', $employee->id)
                    ->where('training_program_id', $program->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $row['programs'][$program->id] = [
                    'status' => $record?->status,
                    'expiry_date' => $record?->expiry_date,
                    'is_expired' => $record?->isExpired() ?? false,
                    'is_expiry_near' => $record?->isExpiryNear() ?? false,
                    'record_id' => $record?->id,
                ];
            }

            $matrix[] = $row;
        }

        return Inertia::render('Modules/Training/Matrix/Index', [
            'matrix' => $matrix,
            'programs' => $programs,
            'filters' => $request->only(['department_id', 'category']),
            'categories' => TrainingProgram::getCategories(),
        ]);
    }
}

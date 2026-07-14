<?php

namespace App\Http\Controllers\Modules\Training;

use App\Http\Controllers\Controller;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
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

        if ($request->get('site_id')) {
            $employeesQuery->where('site_id', $request->get('site_id'));
        }

        $employees = $employeesQuery->orderBy('name')->get();

        // Get active programs
        $programs = TrainingProgram::active()
            ->when($request->get('program_category'), fn ($q, $category) => $q->where('category', $category))
            ->orderBy('name')
            ->get();

        $latestRecords = TrainingRecord::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereIn('training_program_id', $programs->pluck('id'))
            ->latest('created_at')
            ->get()
            ->unique(fn (TrainingRecord $record) => $record->employee_id.':'.$record->training_program_id)
            ->keyBy(fn (TrainingRecord $record) => $record->employee_id.':'.$record->training_program_id);

        // Build the keyed shape consumed directly by the React matrix.
        $matrix = [];
        foreach ($employees as $employee) {
            $employeeKey = 'emp_'.$employee->id;
            $matrix[$employeeKey] = [];

            foreach ($programs as $program) {
                $record = $latestRecords->get($employee->id.':'.$program->id);
                if ($record) {
                    $matrix[$employeeKey]['prog_'.$program->id] = $record;
                }
            }
        }

        return Inertia::render('Modules/Training/Matrix/Index', [
            'matrix' => $matrix,
            'programs' => $programs,
            'employees' => $employees,
            'sites' => Site::active()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::active()->orderBy('name')->get(['id', 'name', 'site_id']),
            'filters' => $request->only(['site_id', 'department_id', 'program_category']),
            'categories' => TrainingProgram::getCategories(),
        ]);
    }
}

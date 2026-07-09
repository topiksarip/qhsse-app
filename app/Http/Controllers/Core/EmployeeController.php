<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\EmployeeRequest;
use App\Models\Core\MasterData\Company;
use App\Models\Core\Users\Employee;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(): Response
    {
        $employees = Employee::query()
            ->with('company:id,name')
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('employee_no', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Core/Employees/Index', [
            'employees' => $employees,
            'filters' => request()->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Core/Employees/Form', [
            'employee' => null,
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        Employee::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('core.employees.index');
    }

    public function edit(Employee $employee): Response
    {
        return Inertia::render('Core/Employees/Form', [
            'employee' => $employee,
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('core.employees.index');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->update(['is_active' => false]);

        return redirect()->route('core.employees.index');
    }
}

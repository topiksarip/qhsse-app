<?php

namespace App\Http\Controllers\Core;

use App\Core\Export\CsvExporter;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\DepartmentRequest;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentController extends Controller
{
    public function index(ListQuery $listQuery): Response
    {
        $items = $listQuery->paginate(
            Department::query()->with('site:id,name'),
            ['code', 'name'],
        );

        return Inertia::render('Core/Departments/Index', [
            'items' => $items,
            'filters' => $listQuery->filters(),
        ]);
    }

    public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $query = $listQuery->apply(
            Department::query()->with('site:id,name'),
            ['code', 'name'],
        );

        return $exporter->stream($query, [
            'Code' => 'code',
            'Name' => 'name',
            'Site' => fn (Department $department): string => $department->site?->name ?? '',
            'Active' => fn (Department $department): string => $department->is_active ? 'Yes' : 'No',
        ], 'departments-export.csv');
    }

    public function create(): Response
    {
        return Inertia::render('Core/Departments/Form', [
            'item' => null,
            'sites' => Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('core.departments.index');
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('Core/Departments/Form', [
            'item' => $department,
            'sites' => Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('core.departments.index');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->update(['is_active' => false]);

        return redirect()->route('core.departments.index');
    }
}

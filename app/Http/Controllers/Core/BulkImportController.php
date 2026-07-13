<?php

namespace App\Http\Controllers\Core;

use App\Core\Import\MasterDataCsvImporter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\BulkImportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkImportController extends Controller
{
    private const TYPES = [
        'employees' => [
            'label' => 'Employees',
            'permission' => 'core.employees.create',
            'headers' => ['employee_no', 'name', 'email', 'phone', 'company_code', 'site_code', 'department_code', 'position_code', 'is_active'],
            'sample' => ['EMP-001', 'Budi Santoso', 'budi@example.com', '08123456789', 'COMP', 'SITE-A', 'HSE', 'OFFICER', 'true'],
        ],
        'sites' => [
            'label' => 'Sites',
            'permission' => 'core.sites.create',
            'headers' => ['code', 'name', 'address', 'is_active'],
            'sample' => ['SITE-A', 'Plant A', 'Jakarta', 'true'],
        ],
        'departments' => [
            'label' => 'Departments',
            'permission' => 'core.departments.create',
            'headers' => ['code', 'name', 'site_code', 'is_active'],
            'sample' => ['HSE', 'Health Safety Environment', 'SITE-A', 'true'],
        ],
    ];

    public function __construct(private readonly MasterDataCsvImporter $importer) {}

    public function create(Request $request): Response
    {
        $types = collect(self::TYPES)
            ->filter(fn (array $config): bool => $request->user()->can($config['permission']))
            ->map(fn (array $config, string $key): array => [
                'key' => $key,
                'label' => $config['label'],
                'headers' => $config['headers'],
            ])->values();
        abort_if($types->isEmpty(), 403);

        return Inertia::render('Core/Admin/Import', ['types' => $types]);
    }

    public function store(BulkImportRequest $request, string $type): RedirectResponse
    {
        $count = $this->importer->import($type, $request->file('file'), $request->user());

        return redirect()->route('admin.import.create')
            ->with('success', "{$count} data {$type} berhasil diimport.");
    }

    public function template(Request $request, string $type): StreamedResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $config = self::TYPES[$type];
        abort_unless($request->user()->can($config['permission']), 403);

        return response()->streamDownload(function () use ($config): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $config['headers']);
            fputcsv($handle, $config['sample']);
            fclose($handle);
        }, "template-{$type}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

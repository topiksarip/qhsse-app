<?php

use App\Core\Permissions\CorePermissions;
use Illuminate\Support\Facades\Route;

function navigationItems(): array
{
    $layout = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    preg_match_all(
        "/\{ label: '([^']+)', routeName: '([^']+)', active: '([^']+)'(?:, permission: '([^']+)')? \}/",
        $layout,
        $matches,
        PREG_SET_ORDER
    );

    return collect($matches)->mapWithKeys(fn (array $match) => [
        $match[1] => [
            'route' => $match[2],
            'active' => $match[3],
            'permission' => $match[4] ?? null,
        ],
    ])->all();
}

it('only references registered routes and permissions in the main navigation', function () {
    $permissions = CorePermissions::all();

    foreach (navigationItems() as $label => $item) {
        expect(Route::has($item['route']))
            ->toBeTrue("Navigation item [{$label}] references missing route [{$item['route']}].");

        if ($item['permission']) {
            expect($permissions)->toContain($item['permission']);
        }
    }
});

it('exposes operational modules through their backend view permissions', function () {
    expect(navigationItems())->toMatchArray([
        'Audit Management' => ['route' => 'audits.index', 'active' => 'audits.*', 'permission' => 'audit.management.view'],
        'Program Pelatihan' => ['route' => 'training.programs.index', 'active' => 'training.programs.*', 'permission' => 'training.programs.view'],
        'Record Pelatihan' => ['route' => 'training.records.index', 'active' => 'training.records.*', 'permission' => 'training.records.view'],
        'Matriks Kompetensi' => ['route' => 'training.matrix.index', 'active' => 'training.matrix.*', 'permission' => 'training.records.view'],
        'Rencana Darurat' => ['route' => 'emergency.plans.index', 'active' => 'emergency.plans.*', 'permission' => 'emergency.plans.view'],
        'Latihan Darurat' => ['route' => 'emergency.drills.index', 'active' => 'emergency.drills.*', 'permission' => 'emergency.drills.view'],
        'Kontak Darurat' => ['route' => 'emergency.contacts.index', 'active' => 'emergency.contacts.*', 'permission' => 'emergency.contacts.view'],
        'Contractor Management' => ['route' => 'contractors.index', 'active' => 'contractors.*', 'permission' => 'contractor.management.view'],
        'Asset & Equipment Safety' => ['route' => 'assets.index', 'active' => 'assets.*', 'permission' => 'asset.management.view'],
        'Communication & Campaign' => ['route' => 'campaigns.index', 'active' => 'campaigns.*', 'permission' => 'communication.campaigns.view'],
        'Report Templates' => ['route' => 'report-templates.index', 'active' => 'report-templates.*', 'permission' => 'reporting.templates.view'],
        'Saved Reports' => ['route' => 'saved-reports.index', 'active' => 'saved-reports.*', 'permission' => 'reporting.reports.view'],
    ]);
});

it('allows asset read-only users to reach the asset index', function () {
    $middleware = Route::getRoutes()->getByName('assets.index')->gatherMiddleware();

    expect($middleware)
        ->toContain('permission:asset.management.view')
        ->not->toContain('permission:asset.management.create')
        ->not->toContain('permission:asset.management.update');
});

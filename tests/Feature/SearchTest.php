<?php

use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Database\Seeders\IncidentReportingSeeder;
use Database\Seeders\InvestigationSeeder;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\QhsseMasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowSeeder;
use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(QhsseMasterDataSeeder::class);
    $this->seed(NumberingFormatSeeder::class);
    $this->seed(WorkflowSeeder::class);
    $this->seed(IncidentReportingSeeder::class);
    $this->seed(InvestigationSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

it('renders the search page without a query', function () {
    actingAs($this->admin)
        ->get(route('search.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Search/Index')
            ->where('searched', false));
});

it('finds an incident by keyword across modules', function () {
    $needle = 'KecelakaanUnik123';
    $incident = IncidentReport::factory()->create([
        'title' => 'Laporan ' . $needle,
    ]);

    actingAs($this->admin)
        ->get(route('search.index', ['q' => 'Unik123']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Search/Index')
            ->where('searched', true)
            ->where('total', 1)
            ->where('results.0.module', 'Laporan Insiden')
            ->where('results.0.items.0.title', 'Laporan ' . $needle)
            ->where('results.0.items.0.href', route('incident.reports.show', $incident, false)));
});

it('returns no results for a nonsense query', function () {
    IncidentReport::factory()->create(['title' => 'Laporan nyata']);

    actingAs($this->admin)
        ->get(route('search.index', ['q' => 'zzz_tidak_ada_zzz']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Search/Index')
            ->where('total', 0));
});

it('hides results for modules the user cannot view', function () {
    // A plain user without incident.reports.view must not see incident results.
    $plain = User::factory()->create();
    IncidentReport::factory()->create(['title' => 'RahasiaInsiden']);

    actingAs($plain)
        ->get(route('search.index', ['q' => 'RahasiaInsiden']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('total', 0));
});

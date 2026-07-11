<?php

use App\Models\Modules\Capa\CapaAction;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\Inspection\Inspection;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Database\Seeders\QhsseMasterDataSeeder::class);
    $this->seed(\Database\Seeders\NumberingFormatSeeder::class);
    $this->seed(\Database\Seeders\WorkflowSeeder::class);
    $this->seed(\Database\Seeders\NotificationTemplateSeeder::class);
    $this->seed(\Database\Seeders\IncidentReportingSeeder::class);
    $this->seed(\Database\Seeders\InvestigationSeeder::class);
    $this->seed(\Database\Seeders\CapaSeeder::class);
    $this->seed(\Database\Seeders\InspectionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

function getProps($response): array
{
    return $response->inertiaPage()['props'];
}

test('dashboard renders with real KPI data', function () {
    actingAs($this->admin);
    $response = $this->get(route('dashboard'));
    $response->assertStatus(200)->assertInertia(fn ($p) => $p
        ->component('Dashboard')
        ->has('kpis')
        ->has('widgets')
        ->has('filters')
        ->has('filterOptions.sites')
        ->has('filterOptions.departments')
        ->has('quickLinks')
    );
});

test('dashboard shows correct incident KPI count', function () {
    IncidentReport::factory()->count(3)->create(['occurred_at' => now()]);
    actingAs($this->admin);
    $response = $this->get(route('dashboard', ['from' => now()->subMonths(2)->toDateString(), 'to' => now()->toDateString()]));
    $kpis = getProps($response)['kpis'];
    $incidentKpi = collect($kpis)->firstWhere('label', 'Insiden');
    expect($incidentKpi['value'])->toBeGreaterThanOrEqual(3);
});

test('dashboard shows overdue CAPA in sub text', function () {
    CapaAction::factory()->create([
        'status' => 'open',
        'due_date' => now()->subDays(5)->toDateString(),
    ]);
    actingAs($this->admin);
    $response = $this->get(route('dashboard'));
    $kpis = getProps($response)['kpis'];
    $capaKpi = collect($kpis)->firstWhere('label', 'CAPA Open');
    expect($capaKpi['sub'])->toContain('OVERDUE');
});

test('dashboard shows completed inspection data', function () {
    Inspection::factory()->create(['status' => 'completed', 'overall_result' => 'pass']);
    Inspection::factory()->create(['status' => 'completed', 'overall_result' => 'fail']);
    actingAs($this->admin);
    $response = $this->get(route('dashboard'));
    $kpis = getProps($response)['kpis'];
    $inspKpi = collect($kpis)->firstWhere('label', 'Inspeksi');
    expect($inspKpi['sub'])->toContain('selesai')->toContain('fail');
});

test('dashboard widgets contain real data not placeholders', function () {
    IncidentReport::factory()->count(3)->create(['occurred_at' => now()]);
    actingAs($this->admin);
    $response = $this->get(route('dashboard'));
    $widgets = getProps($response)['widgets'];
    expect($widgets)->toHaveCount(4);
    // Incident trend widget should have 6 data points
    $trendWidget = collect($widgets)->firstWhere('title', 'Tren Insiden (6 Bulan)');
    expect($trendWidget['points'])->toHaveCount(6);
    expect($trendWidget['labels'])->toHaveCount(6);
    // CAPA status widget
    $capaWidget = collect($widgets)->firstWhere('title', 'Status CAPA');
    expect($capaWidget['points'])->toHaveCount(4);
    expect($capaWidget['labels'])->toEqual(['Open', 'In Progress', 'Pending Verify', 'Closed']);
    // Inspection results widget
    $inspWidget = collect($widgets)->firstWhere('title', 'Hasil Inspeksi');
    expect($inspWidget['labels'])->toEqual(['Pass', 'Fail', 'Pending']);
    // Incident by category widget
    $catWidget = collect($widgets)->first(fn ($w) => str_contains($w['title'], 'per Kategori'));
    expect($catWidget)->not->toBeNull();
});

test('dashboard filters by site_id', function () {
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    IncidentReport::factory()->create(['site_id' => $site->id]);

    actingAs($this->admin);
    $response = $this->get(route('dashboard', ['site_id' => $site->id]));
    $response->assertStatus(200);
    $filters = getProps($response)['filters'];
    expect($filters['site_id'])->toBe($site->id);
});

test('dashboard filters by date range', function () {
    IncidentReport::factory()->create(['occurred_at' => '2026-01-15']);
    IncidentReport::factory()->create(['occurred_at' => '2026-06-15']);

    actingAs($this->admin);
    $response = $this->get(route('dashboard', ['from' => '2026-06-01', 'to' => '2026-06-30']));
    $response->assertStatus(200);
    $filters = getProps($response)['filters'];
    expect($filters['from'])->toBe('2026-06-01');
    expect($filters['to'])->toBe('2026-06-30');
});

test('dashboard quick links are role-aware', function () {
    actingAs($this->admin);
    $response = $this->get(route('dashboard'));
    $quickLinks = getProps($response)['quickLinks'];
    $labels = collect($quickLinks)->pluck('label')->toArray();
    expect($labels)->toContain('Laporan Insiden');
    expect($labels)->toContain('CAPA / Action');
    expect($labels)->toContain('Inspeksi');
});

test('employee sees dashboard without error', function () {
    $emp = User::factory()->create();
    $emp->assignRole('Employee / Reporter');
    actingAs($emp);
    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard notification summary shows unread count', function () {
    actingAs($this->admin);
    $response = $this->get(route('dashboard'));
    $notif = getProps($response)['notificationSummary'];
    expect($notif)->toHaveKey('unread');
    expect($notif['unread'])->toBeInt();
});

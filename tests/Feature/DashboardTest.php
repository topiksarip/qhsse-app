<?php

use App\Models\Core\MasterData\Site;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetCertificate;
use App\Models\Modules\Capa\CapaAction;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\Inspection\Inspection;
use App\Models\User;
use Database\Seeders\CapaSeeder;
use Database\Seeders\IncidentReportingSeeder;
use Database\Seeders\InspectionSeeder;
use Database\Seeders\InvestigationSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\QhsseMasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowSeeder;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(QhsseMasterDataSeeder::class);
    $this->seed(NumberingFormatSeeder::class);
    $this->seed(WorkflowSeeder::class);
    $this->seed(NotificationTemplateSeeder::class);
    $this->seed(IncidentReportingSeeder::class);
    $this->seed(InvestigationSeeder::class);
    $this->seed(CapaSeeder::class);
    $this->seed(InspectionSeeder::class);

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
    expect($widgets)->toHaveCount(5);
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
    $site = Site::factory()->create();
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

test('user without apd.view does not see APD KPIs', function () {
    $emp = User::factory()->create(); // no role -> no apd.view
    actingAs($emp);
    $kpis = collect(getProps($this->get(route('dashboard')))['kpis'])->keyBy('label');
    expect($kpis->keys())->not->toContain('APD Stok Rendah');
});

test('dashboard exposes APD KPIs for apd.view users', function () {
    $this->admin->givePermissionTo('apd.view');

    $site = Site::factory()->create();
    $dept = \App\Models\Core\MasterData\Department::factory()->for($site)->create();

    $catalog = \App\Models\Modules\Apd\ApdCatalog::create([
        'catalog_code' => 'PPE-DASH-0001',
        'name' => 'Helm',
        'category' => 'head_protection',
        'track_type' => 'serial',
        'site_id' => $site->id,
        'department_id' => $dept->id,
        'min_stock' => 5,
        'reorder_point' => 3,
        'is_active' => true,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    // In-stock item below min_stock so the catalog counts as low stock.
    \App\Models\Modules\Apd\ApdItem::create([
        'item_number' => 'PPE-IT-DASH-0001',
        'catalog_id' => $catalog->id,
        'site_id' => $site->id,
        'department_id' => $dept->id,
        'track_type' => 'serial',
        'status' => 'in_stock',
        'quantity' => 1,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $damaged = \App\Models\Modules\Apd\ApdItem::create([
        'item_number' => 'PPE-IT-DASH-0002',
        'catalog_id' => $catalog->id,
        'site_id' => $site->id,
        'department_id' => $dept->id,
        'track_type' => 'serial',
        'status' => 'damaged',
        'quantity' => 1,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    \App\Models\Modules\Apd\ApdInspection::create([
        'apd_item_id' => $damaged->id,
        'inspection_type' => 'incidental',
        'inspected_by' => $this->admin->id,
        'inspection_date' => now()->toDateString(),
        'result' => 'tidak_layak',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    actingAs($this->admin);
    $kpis = collect(getProps($this->get(route('dashboard')))['kpis'])->keyBy('label');

    expect($kpis['APD Stok Rendah']['value'])->toBeGreaterThanOrEqual(1)
        ->and($kpis['APD Rusak']['value'])->toBeGreaterThanOrEqual(1)
        ->and($kpis['Hasil Inspeksi']['sub'])->toContain('tidak layak');
});

test('dashboard exposes scoped asset compliance KPIs', function () {
    $site = Site::factory()->create();
    $asset = Asset::create([
        'asset_number' => 'AST-DASH-0001',
        'name' => 'Safety Critical Asset',
        'category' => 'equipment',
        'site_id' => $site->id,
        'safety_critical' => true,
        'status' => 'active',
        'next_inspection_date' => today()->subDay(),
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    Asset::create([
        'asset_number' => 'AST-DASH-0002',
        'name' => 'Decommissioned Asset',
        'category' => 'equipment',
        'site_id' => $site->id,
        'status' => 'decommissioned',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    AssetCertificate::create([
        'asset_id' => $asset->id,
        'certificate_type' => 'Calibration',
        'certificate_number' => 'CERT-DASH-0001',
        'issued_date' => today()->subYear(),
        'status' => 'expired',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    AssetCertificate::create([
        'asset_id' => $asset->id,
        'certificate_type' => 'Permit',
        'certificate_number' => 'CERT-DASH-0002',
        'issued_date' => today()->subMonth(),
        'status' => 'expiring_soon',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    actingAs($this->admin);
    $kpis = collect(getProps($this->get(route('dashboard')))['kpis'])->keyBy('label');

    expect($kpis['Total Aset']['value'])->toBe(2)
        ->and($kpis['Safety-Critical Assets']['value'])->toBe(1)
        ->and($kpis['Sertifikat Expired']['value'])->toBe(1)
        ->and($kpis['Sertifikat Expiring Soon']['value'])->toBe(1)
        ->and($kpis['Inspeksi Overdue']['value'])->toBe(1)
        ->and($kpis['Aset Aktif']['value'])->toBe(1)
        ->and($kpis['Aset Decommissioned']['value'])->toBe(1);
});

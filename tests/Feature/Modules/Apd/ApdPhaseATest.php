<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Numbering\GeneratedNumber;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Apd\ApdCatalog;
use App\Models\Modules\Apd\ApdItem;
use App\Models\User;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, NumberingFormatSeeder::class]);
});

function apdActor(string $role, Site $site, Department $department): User
{
    $employee = Employee::factory()->create([
        'site_id' => $site->id,
        'department_id' => $department->id,
    ]);
    $user = User::factory()->linkedToEmployee($employee)->create();
    $user->assignRole($role);

    return $user;
}

function apdCatalog(User $actor, Site $site, ?Department $department = null, array $overrides = []): ApdCatalog
{
    static $sequence = 0;
    $sequence++;

    return ApdCatalog::create(array_merge([
        'catalog_code' => sprintf('PPE-TEST-%04d', $sequence),
        'name' => "Test APD {$sequence}",
        'category' => 'head_protection',
        'track_type' => 'serial',
        'site_id' => $site->id,
        'department_id' => $department?->id,
        'min_stock' => 5,
        'reorder_point' => 10,
        'is_active' => true,
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ], $overrides));
}

it('registers all apd routes', function () {
    expect(Route::has('apd.catalogs.index'))->toBeTrue()
        ->and(Route::has('apd.catalogs.store'))->toBeTrue()
        ->and(Route::has('apd.catalogs.show'))->toBeTrue()
        ->and(Route::has('apd.catalogs.update'))->toBeTrue()
        ->and(Route::has('apd.catalogs.destroy'))->toBeTrue()
        ->and(Route::has('apd.items.index'))->toBeTrue()
        ->and(Route::has('apd.items.store'))->toBeTrue()
        ->and(Route::has('apd.items.show'))->toBeTrue();
});

it('treats catalogs as a global master visible to every apd.view user', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    $departmentB = Department::factory()->for($siteB)->create();
    $officer = apdActor('QHSSE Officer', $siteA, $departmentA);
    $own = apdCatalog($officer, $siteA, $departmentA);
    $other = apdCatalog($officer, $siteB, $departmentB);

    // Catalogs are global reference data: index shows all, both show routes OK.
    $this->actingAs($officer)->get(route('apd.catalogs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('catalogs.data', 2));

    $this->get(route('apd.catalogs.show', $own))->assertOk();
    $this->get(route('apd.catalogs.show', $other))->assertOk();

    $csv = $this->get(route('apd.catalogs.export'))->assertOk()->streamedContent();
    expect($csv)->toContain($own->catalog_code)->and($csv)->toContain($other->catalog_code);
});

it('blocks catalog create/update/delete without apd permissions', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $reporter = apdActor('Employee / Reporter', $site, $department);
    // Employee/Reporter has apd.view only, not create/update/delete.
    $catalog = apdCatalog($reporter, $site, $department);

    $this->actingAs($reporter)->get(route('apd.catalogs.create'))->assertForbidden();
    $this->post(route('apd.catalogs.store'), [
        'name' => 'Unauthorized',
        'category' => 'foot_protection',
        'track_type' => 'serial',
        'site_id' => $site->id,
        'min_stock' => 0,
        'reorder_point' => 0,
    ])->assertForbidden();
    $this->put(route('apd.catalogs.update', $catalog), ['name' => 'Hack'])->assertForbidden();
    $this->delete(route('apd.catalogs.destroy', $catalog))->assertForbidden();

    expect(ApdCatalog::query()->count())->toBe(1);
});

it('creates a catalog with auto-generated numbering visible across sites (global master)', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    $departmentB = Department::factory()->for($siteB)->create();
    $manager = apdActor('QHSSE Manager', $siteA, $departmentA);
    $officerB = apdActor('QHSSE Officer', $siteB, $departmentB);

    $this->actingAs($manager)->post(route('apd.catalogs.store'), [
        'name' => 'Helm Safety',
        'category' => 'head_protection',
        'track_type' => 'serial',
        'site_id' => $siteA->id,
        'department_id' => $departmentA->id,
        'min_stock' => 10,
        'reorder_point' => 20,
    ])->assertRedirect();

    $catalog = ApdCatalog::query()->firstOrFail();
    expect($catalog->catalog_code)->toStartWith('PPE-')
        ->and(GeneratedNumber::query()->where('module_name', 'apd')->count())->toBe(1);

    // Catalog is a global master: an officer at another site can view it.
    $this->actingAs($officerB)->get(route('apd.catalogs.show', $catalog))->assertOk();
});

it('rejects a serial catalog without required fields and keeps numbering reserved', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    $departmentB = Department::factory()->for($siteB)->create();
    $manager = apdActor('QHSSE Manager', $siteA, $departmentA);

    $this->actingAs($manager)->from(route('apd.catalogs.create'))->post(route('apd.catalogs.store'), [
        'name' => '',
        'category' => '',
        'track_type' => '',
        'site_id' => $siteA->id,
        'min_stock' => 0,
        'reorder_point' => 0,
    ])->assertSessionHasErrors(['name', 'category', 'track_type']);

    expect(ApdCatalog::query()->count())->toBe(0)
        ->and(GeneratedNumber::query()->where('module_name', 'apd')->count())->toBe(0);
});

it('receives an apd item into stock with correct lifecycle defaults', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $manager = apdActor('QHSSE Manager', $site, $department);
    $catalog = apdCatalog($manager, $site, $department, ['track_type' => 'serial', 'inspection_interval_days' => 90]);

    $this->actingAs($manager)->post(route('apd.items.store'), [
        'catalog_id' => $catalog->id,
        'track_type' => 'serial',
        'serial_number' => 'SN-0001',
        'site_id' => $site->id,
        'department_id' => $department->id,
        'condition' => 'new',
        'received_date' => today()->toDateString(),
    ])->assertRedirect();

    $item = ApdItem::query()->firstOrFail();
    expect($item->item_number)->toStartWith('PPE-')
        ->and($item->status)->toBe('in_stock')
        ->and($item->quantity)->toBe(1)
        ->and($item->expiry_date)->toBeNull()
        ->and($item->next_inspection_date)->not->toBeNull()
        ->and(AuditLog::query()->where('module_name', 'apd')->where('auditable_id', $item->id)->exists())->toBeTrue();

    // Catalog active quantity is recomputed.
    expect($catalog->fresh()->active_quantity)->toBe(1);
});

it('rejects receiving a serial item without serial number', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $manager = apdActor('QHSSE Manager', $site, $department);
    $catalog = apdCatalog($manager, $site, $department, ['track_type' => 'serial', 'inspection_interval_days' => 90]);

    $this->actingAs($manager)->from(route('apd.items.create'))->post(route('apd.items.store'), [
        'catalog_id' => $catalog->id,
        'track_type' => 'serial',
        'site_id' => $site->id,
        'condition' => 'new',
    ])->assertSessionHasErrors('serial_number');

    expect(ApdItem::query()->count())->toBe(0);
});

it('scopes item index and show by organization', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    $departmentB = Department::factory()->for($siteB)->create();
    $officer = apdActor('QHSSE Officer', $siteA, $departmentA);
    $catalogA = apdCatalog($officer, $siteA, $departmentA);
    $catalogB = apdCatalog($officer, $siteB, $departmentB);

    $own = ApdItem::create([
        'item_number' => 'PPE-OWN',
        'catalog_id' => $catalogA->id,
        'track_type' => 'serial',
        'serial_number' => 'SN-OWN',
        'quantity' => 1,
        'status' => 'in_stock',
        'condition' => 'new',
        'site_id' => $siteA->id,
        'department_id' => $departmentA->id,
        'created_by' => $officer->id,
        'updated_by' => $officer->id,
    ]);
    ApdItem::create([
        'item_number' => 'PPE-OTHER',
        'catalog_id' => $catalogB->id,
        'track_type' => 'serial',
        'serial_number' => 'SN-OTHER',
        'quantity' => 1,
        'status' => 'in_stock',
        'condition' => 'new',
        'site_id' => $siteB->id,
        'department_id' => $departmentB->id,
        'created_by' => $officer->id,
        'updated_by' => $officer->id,
    ]);

    $this->actingAs($officer)->get(route('apd.items.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1));

    $this->get(route('apd.items.show', $own))->assertOk();
    $otherId = ApdItem::where('item_number', 'PPE-OTHER')->firstOrFail()->id;
    $this->get(route('apd.items.show', $otherId))->assertForbidden();
});

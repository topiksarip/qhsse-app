<?php

namespace Tests\Feature\Modules\Apd;

use App\Models\Core\MasterData\Site;
use App\Models\Core\MasterData\Department;
use App\Models\Modules\Apd\ApdInspection;
use App\Models\Modules\Apd\ApdItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\UsesRefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class]);
});

function apdInspectionActor(string $role, Site $site, Department $department): User
{
    $employee = \App\Models\Core\Users\Employee::factory()->create([
        'site_id' => $site->id,
        'department_id' => $department->id,
    ]);
    $user = User::factory()->linkedToEmployee($employee)->create();
    $user->assignRole($role);

    return $user;
}

function apdInspectionStockItem(User $actor, Site $site, Department $department): ApdItem
{
    static $seq = 0;
    $seq++;
    $catalog = \App\Models\Modules\Apd\ApdCatalog::create([
        'catalog_code' => sprintf('PPE-IS-%04d', $seq),
        'name' => "APD {$seq}",
        'category' => 'head_protection',
        'track_type' => 'serial',
        'site_id' => $site->id,
        'department_id' => $department->id,
        'min_stock' => 0,
        'reorder_point' => 0,
        'is_active' => true,
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ]);

    return ApdItem::create([
        'item_number' => sprintf('PPE-IT-%04d', $seq),
        'catalog_id' => $catalog->id,
        'site_id' => $site->id,
        'department_id' => $department->id,
        'track_type' => 'serial',
        'status' => 'issued',
        'quantity' => 1,
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ]);
}

it('registers inspection routes', function () {
    expect(Route::has('apd.inspections.index'))->toBeTrue()
        ->and(Route::has('apd.inspections.create'))->toBeTrue()
        ->and(Route::has('apd.inspections.store'))->toBeTrue()
        ->and(Route::has('apd.inspections.show'))->toBeTrue()
        ->and(Route::has('apd.inspections.export'))->toBeTrue();
});

it('allows QHSSE to create an inspection and keeps item status when layak', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = apdInspectionActor('QHSSE Manager', $site, $dept);
    $item = apdInspectionStockItem($manager, $site, $dept);

    $this->actingAs($manager)
        ->post(route('apd.inspections.store'), [
            'apd_item_id' => $item->id,
            'inspection_type' => 'manual',
            'inspection_date' => now()->format('Y-m-d'),
            'result' => 'layak',
            'condition' => 'good',
            'notes' => 'OK',
        ])
        ->assertRedirect(route('apd.inspections.show', ApdInspection::latest()->first()));

    $inspection = ApdInspection::latest()->first();
    expect($inspection->result)->toBe('layak')
        ->and($inspection->inspected_by)->toBe($manager->id);

    $item->refresh();
    expect($item->status)->toBe('issued')->and($item->condition)->toBe('good');
});

it('marks item damaged when inspection result is tidak_layak', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = apdInspectionActor('QHSSE Manager', $site, $dept);
    $item = apdInspectionStockItem($manager, $site, $dept);

    $this->actingAs($manager)
        ->post(route('apd.inspections.store'), [
            'apd_item_id' => $item->id,
            'inspection_type' => 'incidental',
            'inspection_date' => now()->format('Y-m-d'),
            'result' => 'tidak_layak',
            'condition' => 'poor',
        ])
        ->assertRedirect();

    $item->refresh();
    expect($item->status)->toBe('damaged')
        ->and($item->condition)->toBe('poor');

    expect(\App\Models\Core\Activity\ActivityLog::query()
        ->where('module_name', 'apd')
        ->where('reference_id', $item->id)
        ->where('event', 'apd.item.damaged')
        ->exists())->toBeTrue();
});

it('stores inspection photos in the inspection collection', function () {
    Storage::fake('local');
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = apdInspectionActor('QHSSE Manager', $site, $dept);
    $item = apdInspectionStockItem($manager, $site, $dept);

    $photo = UploadedFile::fake()->image('evidence.jpg');

    $this->actingAs($manager)
        ->post(route('apd.inspections.store'), [
            'apd_item_id' => $item->id,
            'inspection_type' => 'scheduled',
            'inspection_date' => now()->format('Y-m-d'),
            'result' => 'layak',
            'photos' => [$photo],
        ])
        ->assertRedirect();

    $inspection = ApdInspection::latest()->first();
    $this->assertDatabaseHas('managed_files', [
        'module_name' => 'apd',
        'reference_id' => $inspection->id,
        'collection' => 'inspection',
    ]);
});

it('blocks a scoped user from inspecting items outside their site', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $deptA = Department::factory()->for($siteA)->create();
    $deptB = Department::factory()->for($siteB)->create();
    $managerA = apdInspectionActor('QHSSE Manager', $siteA, $deptA);
    // QHSSE Officer has apd.inspect + core.scope.site (NOT core.scope.all) -> location-scoped.
    $officerB = apdInspectionActor('QHSSE Officer', $siteB, $deptB);
    $itemA = apdInspectionStockItem($managerA, $siteA, $deptA);

    $this->actingAs($officerB)
        ->post(route('apd.inspections.store'), [
            'apd_item_id' => $itemA->id,
            'inspection_type' => 'manual',
            'inspection_date' => now()->format('Y-m-d'),
            'result' => 'layak',
        ])
        ->assertSessionHasErrors('apd_item_id');

    expect(ApdInspection::query()->count())->toBe(0);
});

it('forbids a user without apd.inspect from creating inspection', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $reporter = apdInspectionActor('Employee / Reporter', $site, $dept);
    $manager = apdInspectionActor('QHSSE Manager', $site, $dept);
    $item = apdInspectionStockItem($manager, $site, $dept);

    $this->actingAs($reporter)
        ->get(route('apd.inspections.create'))
        ->assertForbidden();

    $this->actingAs($reporter)
        ->post(route('apd.inspections.store'), [
            'apd_item_id' => $item->id,
            'inspection_type' => 'manual',
            'inspection_date' => now()->format('Y-m-d'),
            'result' => 'layak',
        ])
        ->assertForbidden();

    expect(ApdInspection::query()->count())->toBe(0);
});

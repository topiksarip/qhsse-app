<?php

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Position;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function superAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    return $user;
}

it('creates site area department and position records', function () {
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('core.sites.store'), [
        'code' => 'SITE-JKT',
        'name' => 'Jakarta Site',
        'address' => 'Jakarta',
        'is_active' => true,
    ])->assertRedirect(route('core.sites.index'));

    $site = Site::where('code', 'SITE-JKT')->firstOrFail();

    $this->actingAs($admin)->post(route('core.areas.store'), [
        'site_id' => $site->id,
        'code' => 'AREA-WHS',
        'name' => 'Warehouse',
        'type' => 'warehouse',
        'is_active' => true,
    ])->assertRedirect(route('core.areas.index'));

    $this->actingAs($admin)->post(route('core.departments.store'), [
        'site_id' => $site->id,
        'code' => 'DEPT-QHSSE',
        'name' => 'QHSSE',
        'is_active' => true,
    ])->assertRedirect(route('core.departments.index'));

    $department = Department::where('code', 'DEPT-QHSSE')->firstOrFail();

    $this->actingAs($admin)->post(route('core.positions.store'), [
        'department_id' => $department->id,
        'code' => 'POS-OFC',
        'name' => 'QHSSE Officer',
        'is_active' => true,
    ])->assertRedirect(route('core.positions.index'));

    expect(Area::where('code', 'AREA-WHS')->firstOrFail()->site->is($site))->toBeTrue()
        ->and(Position::where('code', 'POS-OFC')->firstOrFail()->department->is($department))->toBeTrue();
});

it('links employees to organization master records', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $position = Position::factory()->for($department)->create();

    $employee = Employee::factory()->create([
        'site_id' => $site->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);

    expect($employee->site->is($site))->toBeTrue()
        ->and($employee->departmentMaster->is($department))->toBeTrue()
        ->and($employee->positionMaster->is($position))->toBeTrue();
});

it('blocks organization master access without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('core.sites.index'))->assertForbidden();
});

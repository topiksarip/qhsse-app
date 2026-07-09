<?php

use App\Core\Permissions\CorePermissions;
use App\Models\Core\MasterData\Company;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('seeds standard roles and permissions', function () {
    foreach (array_keys(CorePermissions::roleMap()) as $role) {
        expect(Role::where('name', $role)->exists())->toBeTrue();
    }

    expect(Role::findByName('Super Admin')->hasPermissionTo('core.users.update'))->toBeTrue()
        ->and(Role::findByName('Contractor')->hasPermissionTo('core.scope.company'))->toBeTrue();
});

it('blocks users without permission from core user management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('core.users.index'))->assertForbidden();
});

it('allows super admin to access core user management', function () {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $this->actingAs($user)->get(route('core.users.index'))->assertOk();
});

it('assigns roles when creating a user from admin screen', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    $employee = Employee::factory()->create();

    $this->actingAs($admin)->post(route('core.users.store'), [
        'company_id' => $employee->company_id,
        'employee_id' => $employee->id,
        'name' => 'QHSSE Officer',
        'email' => 'qhse.officer@example.com',
        'password' => 'password',
        'is_active' => true,
        'roles' => ['QHSSE Officer'],
    ])->assertRedirect(route('core.users.index'));

    expect(User::where('email', 'qhse.officer@example.com')->firstOrFail()->hasRole('QHSSE Officer'))->toBeTrue();
});

it('allows company-scoped contractor role to keep company scope permission only', function () {
    $company = Company::factory()->contractor()->create();
    $user = User::factory()->linkedToCompany($company)->create();
    $user->assignRole('Contractor');

    expect($user->can('core.scope.company'))->toBeTrue()
        ->and($user->can('core.scope.all'))->toBeFalse()
        ->and($user->company->is($company))->toBeTrue();
});

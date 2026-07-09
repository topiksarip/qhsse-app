<?php

use App\Models\Core\MasterData\Company;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates a company record', function () {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $this->actingAs($user)->post(route('core.companies.store'), [
        'code' => 'INT-001',
        'name' => 'Internal Company',
        'type' => 'internal',
        'email' => 'company@example.com',
        'phone' => '123456',
        'address' => 'Main office',
        'is_active' => true,
    ])->assertRedirect(route('core.companies.index'));

    $this->assertDatabaseHas('companies', [
        'code' => 'INT-001',
        'name' => 'Internal Company',
        'type' => 'internal',
        'is_active' => true,
    ]);
});

it('creates an employee linked to a company', function () {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    $company = Company::factory()->create();

    $this->actingAs($user)->post(route('core.employees.store'), [
        'company_id' => $company->id,
        'employee_no' => 'EMP-00001',
        'name' => 'Safety Officer',
        'email' => 'safety@example.com',
        'phone' => '555-0001',
        'department' => 'QHSSE',
        'position' => 'Officer',
        'is_active' => true,
    ])->assertRedirect(route('core.employees.index'));

    $employee = Employee::where('employee_no', 'EMP-00001')->firstOrFail();

    expect($employee->company->is($company))->toBeTrue();
});

it('creates a user linked to employee and company', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    $employee = Employee::factory()->create();

    $this->actingAs($admin)->post(route('core.users.store'), [
        'company_id' => $employee->company_id,
        'employee_id' => $employee->id,
        'name' => $employee->name,
        'email' => 'linked-user@example.com',
        'password' => 'password',
        'is_active' => true,
    ])->assertRedirect(route('core.users.index'));

    $created = User::where('email', 'linked-user@example.com')->firstOrFail();

    expect($created->employee->is($employee))->toBeTrue()
        ->and($created->company_id)->toBe($employee->company_id)
        ->and(Hash::check('password', $created->password))->toBeTrue();
});

it('updates user active state', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    $target = User::factory()->create();

    $this->actingAs($admin)->put(route('core.users.update', $target), [
        'company_id' => null,
        'employee_id' => null,
        'name' => $target->name,
        'email' => $target->email,
        'password' => '',
        'is_active' => false,
    ])->assertRedirect(route('core.users.index'));

    expect($target->fresh()->is_active)->toBeFalse();
});

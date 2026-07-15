<?php

namespace Tests\Feature\Core;

use App\Models\Core\Audit\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\actingAs;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    Artisan::call('db:seed', ['--class' => 'QhsseMasterDataSeeder']);
});

test('M20 WS-1: creating a user writes an audit log', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    $admin->givePermissionTo(['core.users.view', 'core.users.create']);

    actingAs($admin);
    $this->post(route('core.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'is_active' => true,
        'roles' => ['QHSSE Officer'],
    ])->assertRedirect();

    $created = User::where('email', 'newuser@example.com')->first();
    expect($created)->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'created',
        'module_name' => 'core',
        'reference_id' => $created->id,
        'actor_id' => $admin->id,
    ]);
});

test('M20 WS-1: updating a user writes an audit log', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    $admin->givePermissionTo(['core.users.view', 'core.users.update']);

    $target = User::factory()->create(['name' => 'Before Name', 'is_active' => true]);
    $target->assignRole('QHSSE Officer');

    actingAs($admin);
    $this->put(route('core.users.update', $target), [
        'name' => 'After Name',
        'email' => $target->email,
        'is_active' => true,
        'roles' => ['QHSSE Officer'],
    ])->assertRedirect();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'updated',
        'module_name' => 'core',
        'reference_id' => $target->id,
        'actor_id' => $admin->id,
    ]);
});

test('M20 WS-1: a user cannot deactivate their own account', function (): void {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('Super Admin');
    $admin->givePermissionTo(['core.users.view', 'core.users.update']);

    actingAs($admin);
    $this->put(route('core.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'is_active' => false,
        'roles' => ['Super Admin'],
    ])->assertRedirect();

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('M20 WS-1: cannot deactivate the last active Super Admin', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    $admin->givePermissionTo(['core.users.view', 'core.users.update']);

    $onlySuperAdmin = User::role('Super Admin')->where('is_active', true)->first();
    expect(User::role('Super Admin')->where('is_active', true)->count())->toBe(1);

    actingAs($admin);
    $this->put(route('core.users.update', $onlySuperAdmin), [
        'name' => $onlySuperAdmin->name,
        'email' => $onlySuperAdmin->email,
        'is_active' => false,
        'roles' => ['Super Admin'],
    ])->assertRedirect();

    expect($onlySuperAdmin->fresh()->is_active)->toBeTrue();
});

test('M20 WS-1: cannot lock (destroy) the last active Super Admin', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    $admin->givePermissionTo(['core.users.view', 'core.users.deactivate']);

    $onlySuperAdmin = User::role('Super Admin')->where('is_active', true)->first();
    expect(User::role('Super Admin')->where('is_active', true)->count())->toBe(1);

    actingAs($admin);
    $this->delete(route('core.users.destroy', $onlySuperAdmin))->assertRedirect();

    expect($onlySuperAdmin->fresh()->is_active)->toBeTrue();
});

test('M20 WS-1: locking (destroy) a user writes an audit log', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    $admin->givePermissionTo(['core.users.view', 'core.users.deactivate']);

    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole('QHSSE Officer');

    actingAs($admin);
    $this->delete(route('core.users.destroy', $target))->assertRedirect();

    expect($target->fresh()->is_active)->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'updated',
        'module_name' => 'core',
        'reference_id' => $target->id,
        'actor_id' => $admin->id,
    ]);
});

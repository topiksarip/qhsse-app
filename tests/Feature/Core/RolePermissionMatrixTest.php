<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('authorized admin can view role permission matrix', function () {
    actingAs($this->admin);

    $this->get(route('core.roles.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Core/Roles/Index')
            ->has('roles')
            ->has('permissionGroups')
            ->where('selectedRole.name', 'Admin')
            ->where('selectedRole.immutable', false));
});

test('user without manage permission cannot access matrix', function () {
    $user = User::factory()->create();
    actingAs($user);

    $this->get(route('core.roles.index'))->assertForbidden();
    $this->put(route('core.roles.permissions.update', Role::findByName('Supervisor')), [
        'permissions' => ['incident.reports.view'],
    ])->assertForbidden();
});

test('admin updates role permissions and audit records old and new values', function () {
    $role = Role::findByName('Supervisor');
    actingAs($this->admin);

    $this->put(route('core.roles.permissions.update', $role), [
        'permissions' => ['incident.reports.view', 'core.scope.department'],
    ])->assertRedirect(route('core.roles.index', ['role' => $role->id]));

    expect($role->fresh()->hasPermissionTo('incident.reports.view'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('core.scope.department'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('incident.reports.update'))->toBeFalse();

    $audit = AuditLog::query()->where('module_name', 'core_roles')
        ->where('reference_id', $role->id)
        ->where('event', 'role_permissions_updated')->firstOrFail();
    expect($audit->old_values)->toHaveKey('permissions')
        ->and($audit->new_values['permissions'])->toContain('incident.reports.view');
});

test('critical manage permission cannot be granted through matrix', function () {
    $role = Role::findByName('Supervisor');
    actingAs($this->admin);

    $this->put(route('core.roles.permissions.update', $role), [
        'permissions' => ['incident.reports.view', 'core.roles.manage'],
    ])->assertSessionHasErrors('permissions.1');

    expect($role->fresh()->hasPermissionTo('core.roles.manage'))->toBeFalse();
});

test('role cannot receive multiple data scopes', function () {
    $role = Role::findByName('Supervisor');
    actingAs($this->admin);

    $this->put(route('core.roles.permissions.update', $role), [
        'permissions' => ['core.scope.site', 'core.scope.all'],
    ])->assertSessionHasErrors('permissions');
});

test('super admin role is immutable through matrix', function () {
    $role = Role::findByName('Super Admin');
    $before = $role->permissions()->pluck('name')->sort()->values()->all();
    actingAs($this->admin);

    $this->put(route('core.roles.permissions.update', $role), [
        'permissions' => ['incident.reports.view'],
    ])->assertForbidden();

    expect($role->fresh()->permissions()->pluck('name')->sort()->values()->all())->toBe($before);
});

test('existing protected permission is preserved when admin role is updated', function () {
    $role = Role::findByName('Admin');
    expect(Permission::findByName('core.roles.manage'))->not->toBeNull();
    actingAs($this->admin);

    $this->put(route('core.roles.permissions.update', $role), [
        'permissions' => ['core.scope.all', 'incident.reports.view'],
    ])->assertRedirect();

    expect($role->fresh()->hasPermissionTo('core.roles.manage'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('incident.reports.view'))->toBeTrue();
});

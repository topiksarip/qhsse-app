<?php

declare(strict_types=1);

use App\Core\Permissions\CorePermissions;
use App\Models\User;

uses()->beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

/**
 * P0.1 Regression Test
 *
 * Verifies:
 * - Emergency routes redirect anonymous users to login
 * - Training/Emergency submenus appear based on permissions
 * - QHSSE Manager/Officer receive Training permissions
 */

it('redirects anonymous users to login for emergency routes', function () {
    $emergencyRoutes = [
        '/plans',
        '/plans/create',
        '/drills',
        '/drills/create',
        '/contacts',
        '/contacts/create',
    ];

    foreach ($emergencyRoutes as $path) {
        $response = $this->get($path);

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
});

it('allows users with training permissions to access training routes', function () {
    $user = \App\Models\User::factory()->create();

    // User with training.programs.view can access programs index
    $user->givePermissionTo('training.programs.view');
    $response = $this->actingAs($user)->get(route('training.programs.index'));
    $response->assertSuccessful();

    // User with training.records.view can access records index
    $user->givePermissionTo('training.records.view');
    $response = $this->actingAs($user)->get(route('training.records.index'));
    $response->assertSuccessful();

    // User with training.matrix.index access (uses training.records.view permission)
    $response = $this->actingAs($user)->get(route('training.matrix.index'));
    $response->assertSuccessful();

    // User without permissions gets blocked
    $blockedUser = \App\Models\User::factory()->create();
    $response = $this->actingAs($blockedUser)->get(route('training.programs.index'));
    $response->assertForbidden();
});

it('allows users with emergency permissions to access emergency routes', function () {
    $user = \App\Models\User::factory()->create();

    // User with emergency.plans.view can access plans index
    $user->givePermissionTo('emergency.plans.view');
    $response = $this->actingAs($user)->get(route('emergency.plans.index'));
    $response->assertSuccessful();

    // User with emergency.drills.view can access drills index
    $user->givePermissionTo('emergency.drills.view');
    $response = $this->actingAs($user)->get(route('emergency.drills.index'));
    $response->assertSuccessful();

    // User with emergency.contacts.view can access contacts index
    $user->givePermissionTo('emergency.contacts.view');
    $response = $this->actingAs($user)->get(route('emergency.contacts.index'));
    $response->assertSuccessful();

    // User without permissions gets blocked
    $blockedUser = \App\Models\User::factory()->create();
    $response = $this->actingAs($blockedUser)->get(route('emergency.plans.index'));
    $response->assertForbidden();
});

it('grants QHSSE Manager all training permissions', function () {
    $roleMap = CorePermissions::roleMap();

    expect($roleMap)->toHaveKey('QHSSE Manager');

    $managerPermissions = $roleMap['QHSSE Manager'];

    $expectedTrainingPermissions = [
        'training.programs.view',
        'training.programs.create',
        'training.programs.update',
        'training.records.view',
        'training.records.create',
        'training.records.update',
        'training.records.export',
    ];

    foreach ($expectedTrainingPermissions as $permission) {
        expect($managerPermissions)->toContain($permission);
    }

    // Verify user with QHSSE Manager role
    $user = \App\Models\User::factory()->create();
    $user->assignRole('QHSSE Manager');
    $user->refresh();

    foreach ($expectedTrainingPermissions as $permission) {
        expect($user->hasPermissionTo($permission))->toBeTrue();
    }
});

it('grants QHSSE Officer all training permissions', function () {
    $roleMap = CorePermissions::roleMap();

    expect($roleMap)->toHaveKey('QHSSE Officer');

    $officerPermissions = $roleMap['QHSSE Officer'];

    $expectedTrainingPermissions = [
        'training.programs.view',
        'training.programs.create',
        'training.programs.update',
        'training.records.view',
        'training.records.create',
        'training.records.update',
        'training.records.export',
    ];

    foreach ($expectedTrainingPermissions as $permission) {
        expect($officerPermissions)->toContain($permission);
    }

    // Verify user with QHSSE Officer role
    $user = \App\Models\User::factory()->create();
    $user->assignRole('QHSSE Officer');
    $user->refresh();

    foreach ($expectedTrainingPermissions as $permission) {
        expect($user->hasPermissionTo($permission))->toBeTrue();
    }
});

it('grants Supervisor training view and export permissions only', function () {
    $roleMap = CorePermissions::roleMap();

    expect($roleMap)->toHaveKey('Supervisor');

    $supervisorPermissions = $roleMap['Supervisor'];

    // Supervisor should have view and export permissions
    $expectedTrainingPermissions = [
        'training.programs.view',
        'training.records.view',
        'training.records.export',
    ];

    foreach ($expectedTrainingPermissions as $permission) {
        expect($supervisorPermissions)->toContain($permission);
    }

    // Supervisor should NOT have create/update permissions
    $forbiddenPermissions = [
        'training.programs.create',
        'training.programs.update',
        'training.records.create',
        'training.records.update',
    ];

    foreach ($forbiddenPermissions as $permission) {
        expect($supervisorPermissions)->not->toContain($permission);
    }

    // Verify user with Supervisor role
    $user = \App\Models\User::factory()->create();
    $user->assignRole('Supervisor');
    $user->refresh();

    foreach ($expectedTrainingPermissions as $permission) {
        expect($user->hasPermissionTo($permission))->toBeTrue();
    }

    foreach ($forbiddenPermissions as $permission) {
        expect($user->hasPermissionTo($permission))->toBeFalse();
    }
});

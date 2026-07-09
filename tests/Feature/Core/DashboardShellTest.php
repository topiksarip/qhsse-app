<?php

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function dashboardShellUser(string $role = 'Super Admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('renders dashboard shell with filters and widgets', function () {
    $site = Site::factory()->create(['name' => 'Jakarta Site']);
    Department::factory()->for($site)->create(['name' => 'QHSSE Department']);

    $response = $this->actingAs(dashboardShellUser())->get(route('dashboard', [
        'from' => '2026-07-01',
        'to' => '2026-07-31',
        'site_id' => $site->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('filters.from', '2026-07-01')
        ->where('filters.to', '2026-07-31')
        ->where('filters.site_id', $site->id)
        ->where('filterOptions.sites.0.name', 'Jakarta Site')
        ->where('filterOptions.departments.0.name', 'QHSSE Department')
        ->has('kpis', 4)
        ->has('widgets', 2)
    );
});

it('shares role permissions for role aware menu rendering', function () {
    $response = $this->actingAs(dashboardShellUser('QHSSE Officer'))->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('auth.roles.0', 'QHSSE Officer')
        ->where('auth.permissions.0', 'core.sites.view')
        ->has('auth.permissions')
    );

    $permissions = collect($response->inertiaPage()['props']['auth']['permissions']);

    expect($permissions)->toContain('core.sites.view')
        ->and($permissions)->not->toContain('core.users.create');
});

it('redirects authenticated login to dashboard route', function () {
    $user = dashboardShellUser();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
});

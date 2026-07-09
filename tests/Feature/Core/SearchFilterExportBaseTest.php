<?php

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function phase014Admin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    return $user;
}

it('filters site list with the shared query parameters', function () {
    Site::factory()->create(['code' => 'SITE-JKT', 'name' => 'Jakarta Site', 'is_active' => true]);
    Site::factory()->create(['code' => 'SITE-BDG', 'name' => 'Bandung Site', 'is_active' => false]);

    $response = $this->actingAs(phase014Admin())->get(route('core.sites.index', [
        'search' => 'Jakarta',
        'is_active' => '1',
        'sort' => 'code',
        'direction' => 'desc',
        'per_page' => '5',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Core/Sites/Index')
        ->where('items.data.0.code', 'SITE-JKT')
        ->where('filters.search', 'Jakarta')
        ->where('filters.is_active', '1')
    );
});

it('exports filtered department list as csv', function () {
    $site = Site::factory()->create(['name' => 'Main Site']);
    Department::factory()->for($site)->create(['code' => 'DEPT-QHSSE', 'name' => 'QHSSE', 'is_active' => true]);
    Department::factory()->for($site)->create(['code' => 'DEPT-FIN', 'name' => 'Finance', 'is_active' => true]);

    $response = $this->actingAs(phase014Admin())->get(route('core.departments.export', [
        'search' => 'QHSSE',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Code,Name,Site,Active')
        ->and($csv)->toContain('DEPT-QHSSE,QHSSE,"Main Site",Yes')
        ->and($csv)->not->toContain('DEPT-FIN');
});

it('blocks csv export without export permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('core.departments.view');

    $this->actingAs($user)
        ->get(route('core.departments.export'))
        ->assertForbidden();
});

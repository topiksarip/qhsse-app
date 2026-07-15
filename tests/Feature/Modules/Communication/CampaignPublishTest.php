<?php

namespace Tests\Feature\Modules\Communication;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Communication\Campaign;
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

test('M11 WS-1: publishing to "all" notifies every active user', function (): void {
    $site = Site::factory()->create();
    User::factory()->count(3)->create(['is_active' => true]);
    User::factory()->create(['is_active' => false]); // should be excluded

    $publisher = User::factory()->create();
    $publisher->givePermissionTo('communication.campaigns.publish');

    $campaign = Campaign::factory()->create([
        'status' => 'draft',
        'target_audience' => 'all',
        'site_id' => null,
        'department_id' => null,
        'target_role' => null,
    ]);

    $activeCount = User::where('is_active', true)->count();

    actingAs($publisher);
    $this->post(route('campaigns.publish', $campaign))
        ->assertRedirect(route('campaigns.show', $campaign));

    expect($campaign->fresh()->status)->toBe('published');
    // Every active user (including publisher) receives the blast
    $this->assertDatabaseCount('core_notifications', $activeCount);
    $this->assertDatabaseHas('core_notifications', [
        'type' => 'campaign.published',
        'reference_id' => $campaign->id,
        'module_name' => 'communication',
    ]);
    // Inactive user excluded
    $inactive = User::where('is_active', false)->first();
    $this->assertDatabaseMissing('core_notifications', [
        'recipient_id' => $inactive->id,
    ]);
});

test('M11 WS-1: publishing to specific_site notifies only that site employees', function (): void {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $empA = \App\Models\Core\Users\Employee::factory()->create(['site_id' => $siteA->id]);
    $empB = \App\Models\Core\Users\Employee::factory()->create(['site_id' => $siteB->id]);
    $userA = User::factory()->create(['employee_id' => $empA->id, 'is_active' => true]);
    User::factory()->create(['employee_id' => $empB->id, 'is_active' => true]);

    $publisher = User::factory()->create();
    $publisher->givePermissionTo('communication.campaigns.publish');

    $campaign = Campaign::factory()->create([
        'status' => 'draft',
        'target_audience' => 'specific_site',
        'site_id' => $siteA->id,
        'department_id' => null,
        'target_role' => null,
    ]);

    actingAs($publisher);
    $this->post(route('campaigns.publish', $campaign))
        ->assertRedirect(route('campaigns.show', $campaign));

    $this->assertDatabaseCount('core_notifications', 1);
    $this->assertDatabaseHas('core_notifications', [
        'recipient_id' => $userA->id,
        'type' => 'campaign.published',
        'reference_id' => $campaign->id,
    ]);
});

test('M11 WS-1: publishing to specific_department notifies only that department', function (): void {
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();
    $empA = \App\Models\Core\Users\Employee::factory()->create(['department_id' => $deptA->id]);
    $empB = \App\Models\Core\Users\Employee::factory()->create(['department_id' => $deptB->id]);
    $userA = User::factory()->create(['employee_id' => $empA->id, 'is_active' => true]);
    User::factory()->create(['employee_id' => $empB->id, 'is_active' => true]);

    $publisher = User::factory()->create();
    $publisher->givePermissionTo('communication.campaigns.publish');

    $campaign = Campaign::factory()->create([
        'status' => 'draft',
        'target_audience' => 'specific_department',
        'site_id' => null,
        'department_id' => $deptA->id,
        'target_role' => null,
    ]);

    actingAs($publisher);
    $this->post(route('campaigns.publish', $campaign))
        ->assertRedirect(route('campaigns.show', $campaign));

    $this->assertDatabaseCount('core_notifications', 1);
    $this->assertDatabaseHas('core_notifications', [
        'recipient_id' => $userA->id,
        'type' => 'campaign.published',
        'reference_id' => $campaign->id,
    ]);
});

test('M11 WS-1: publishing to specific_role notifies only users with that role', function (): void {
    $roleName = 'QHSSE Officer';
    $roleUser = User::factory()->create(['is_active' => true]);
    $roleUser->assignRole($roleName);
    $otherUser = User::factory()->create(['is_active' => true]);
    $otherUser->assignRole('Employee / Reporter');

    $publisher = User::factory()->create();
    $publisher->givePermissionTo('communication.campaigns.publish');

    $campaign = Campaign::factory()->create([
        'status' => 'draft',
        'target_audience' => 'specific_role',
        'site_id' => null,
        'department_id' => null,
        'target_role' => $roleName,
    ]);

    actingAs($publisher);
    $this->post(route('campaigns.publish', $campaign))
        ->assertRedirect(route('campaigns.show', $campaign));

    $this->assertDatabaseCount('core_notifications', 1);
    $this->assertDatabaseHas('core_notifications', [
        'recipient_id' => $roleUser->id,
        'type' => 'campaign.published',
        'reference_id' => $campaign->id,
    ]);
});

test('M11 WS-1: non-draft campaign cannot be published (policy)', function (): void {
    $publisher = User::factory()->create();
    $publisher->givePermissionTo('communication.campaigns.publish');

    $campaign = Campaign::factory()->create([
        'status' => 'published',
        'target_audience' => 'all',
    ]);

    actingAs($publisher);
    $this->post(route('campaigns.publish', $campaign))->assertForbidden();
    $this->assertDatabaseCount('core_notifications', 0);
});

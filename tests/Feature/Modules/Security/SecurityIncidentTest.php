<?php

namespace Tests\Feature\Modules\Security;

use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Security\SecurityIncident;
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

test('M10 WS-1: reported incident can be set under investigation', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(['security.incidents.view', 'security.incidents.update']);

    $incident = SecurityIncident::factory()->create(['status' => 'reported']);

    actingAs($user);
    $this->post(route('security.incidents.transition', $incident), ['action' => 'investigate'])
        ->assertRedirect(route('security.incidents.show', $incident));

    expect($incident->fresh()->status)->toBe('under_investigation');
});

test('M10 WS-1: closing requires resolution (min 10 chars)', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(['security.incidents.view', 'security.incidents.update']);

    $incident = SecurityIncident::factory()->create(['status' => 'under_investigation']);

    actingAs($user);
    $this->post(route('security.incidents.transition', $incident), ['action' => 'close', 'resolution' => 'short'])
        ->assertSessionHasErrors('resolution');

    expect($incident->fresh()->status)->toBe('under_investigation');
});

test('M10 WS-1: closing with valid resolution closes and notifies reporter', function (): void {
    $reporter = User::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo(['security.incidents.view', 'security.incidents.update']);

    $incident = SecurityIncident::factory()->create([
        'status' => 'under_investigation',
        'reported_by' => $reporter->id,
    ]);

    actingAs($user);
    $this->post(route('security.incidents.transition', $incident), [
        'action' => 'close',
        'resolution' => 'Resolved after full investigation and review.',
    ])->assertRedirect(route('security.incidents.show', $incident));

    $fresh = $incident->fresh();
    expect($fresh->status)->toBe('closed');
    expect($fresh->resolved_at)->not->toBeNull();
    $this->assertDatabaseHas('core_notifications', [
        'recipient_id' => $reporter->id,
        'type' => 'security.incident.closed',
        'reference_id' => $incident->id,
        'module_name' => 'security',
    ]);
});

test('M10 WS-1: closed incident is terminal (no transition / no update)', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(['security.incidents.view', 'security.incidents.update']);

    $incident = SecurityIncident::factory()->closed()->create();

    actingAs($user);
    $this->post(route('security.incidents.transition', $incident), ['action' => 'investigate'])
        ->assertForbidden();

    $this->put(route('security.incidents.update', $incident), [
        'title' => 'Hacked',
        'type' => 'theft',
        'description' => 'x',
        'site_id' => Site::factory()->create()->id,
        'occurred_at' => now()->toDateTimeString(),
        'severity_id' => 1,
    ])->assertForbidden();
});

test('M10 WS-1: cannot investigate a non-reported incident', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(['security.incidents.view', 'security.incidents.update']);

    $incident = SecurityIncident::factory()->create(['status' => 'under_investigation']);

    actingAs($user);
    $this->post(route('security.incidents.transition', $incident), ['action' => 'investigate'])
        ->assertStatus(422);

    expect($incident->fresh()->status)->toBe('under_investigation');
});

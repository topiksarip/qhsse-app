<?php

namespace Tests\Feature\Modules\EmergencyPreparedness;

use App\Models\Modules\EmergencyPreparedness\EmergencyDrill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\actingAs;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    Artisan::call('db:seed', ['--class' => 'QhsseMasterDataSeeder']);
    Artisan::call('db:seed', ['--class' => 'NumberingFormatSeeder']);
});

test('M15 WS-1: scheduling a drill notifies the assigned observer', function (): void {
    $creator = User::factory()->create();
    $creator->givePermissionTo('emergency.drills.create');

    $observer = User::factory()->create(['is_active' => true]);

    $drill = EmergencyDrill::factory()->make([
        'status' => 'scheduled',
        'observer_id' => $observer->id,
    ]);

    // We directly exercise the notification path by creating then checking.
    // Simulate the store flow via the controller by calling the policy-authorized store.
    actingAs($creator);

    $this->post(route('emergency.drills.store'), [
        'emergency_plan_id' => $drill->emergency_plan_id,
        'scheduled_date' => now()->addDays(5)->format('Y-m-d'),
        'site_id' => $drill->site_id,
        'observer_id' => $observer->id,
    ])
        ->assertRedirect();

    $created = EmergencyDrill::where('observer_id', $observer->id)->latest()->first();
    expect($created)->not->toBeNull();
    expect($created->status)->toBe('scheduled');

    $this->assertDatabaseHas('core_notifications', [
        'recipient_id' => $observer->id,
        'type' => 'emergency.drill.scheduled',
        'reference_id' => $created->id,
        'module_name' => 'emergency',
    ]);
});

test('M15 WS-1: executing a failed drill notifies QHSSE Manager', function (): void {
    $executor = User::factory()->create();
    $executor->givePermissionTo('emergency.drills.execute');

    $manager = User::factory()->create(['is_active' => true]);
    $manager->assignRole('QHSSE Manager');

    $observer = User::factory()->create(['is_active' => true]);
    $drill = EmergencyDrill::factory()->create([
        'status' => 'scheduled',
        'observer_id' => $observer->id,
    ]);

    actingAs($executor);
    $this->post(route('emergency.drills.execute', $drill), [
        'executed_date' => now()->format('Y-m-d'),
        'participants_count' => 20,
        'result' => 'fail',
        'findings' => 'Evacuation delayed',
        'recommendations' => 'Add signage',
    ])
        ->assertRedirect();

    expect($drill->fresh()->status)->toBe('executed');
    expect($drill->fresh()->result)->toBe('fail');

    // Observer notified of execution
    $this->assertDatabaseHas('core_notifications', [
        'recipient_id' => $observer->id,
        'type' => 'emergency.drill.executed',
        'reference_id' => $drill->id,
    ]);
    // QHSSE Manager notified of failure
    $this->assertDatabaseHas('core_notifications', [
        'recipient_id' => $manager->id,
        'type' => 'emergency.drill.failed',
        'reference_id' => $drill->id,
        'module_name' => 'emergency',
    ]);
});

test('M15 WS-1: executing a passed drill notifies observer but NOT QHSSE Manager', function (): void {
    $executor = User::factory()->create();
    $executor->givePermissionTo('emergency.drills.execute');

    $manager = User::factory()->create(['is_active' => true]);
    $manager->assignRole('QHSSE Manager');

    $observer = User::factory()->create(['is_active' => true]);
    $drill = EmergencyDrill::factory()->create([
        'status' => 'scheduled',
        'observer_id' => $observer->id,
    ]);

    actingAs($executor);
    $this->post(route('emergency.drills.execute', $drill), [
        'executed_date' => now()->format('Y-m-d'),
        'participants_count' => 20,
        'result' => 'pass',
        'findings' => 'Smooth',
        'recommendations' => null,
    ])
        ->assertRedirect();

    $this->assertDatabaseHas('core_notifications', [
        'recipient_id' => $observer->id,
        'type' => 'emergency.drill.executed',
        'reference_id' => $drill->id,
    ]);
    $this->assertDatabaseMissing('core_notifications', [
        'recipient_id' => $manager->id,
        'type' => 'emergency.drill.failed',
    ]);
});

test('M15 WS-1: execute is blocked when drill is not scheduled (policy)', function (): void {
    $executor = User::factory()->create();
    $executor->givePermissionTo('emergency.drills.execute');

    $drill = EmergencyDrill::factory()->create([
        'status' => 'executed',
        'observer_id' => User::factory()->create()->id,
        'result' => 'pass',
    ]);

    actingAs($executor);
    $this->post(route('emergency.drills.execute', $drill), [
        'executed_date' => now()->format('Y-m-d'),
        'participants_count' => 20,
        'result' => 'pass',
    ])
        ->assertRedirect(route('emergency.drills.show', $drill))
        ->assertSessionHas('error');

    $this->assertDatabaseMissing('core_notifications', [
        'reference_id' => $drill->id,
        'type' => 'emergency.drill.failed',
    ]);
});

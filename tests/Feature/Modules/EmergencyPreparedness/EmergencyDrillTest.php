<?php

declare(strict_types=1);

use App\Models\Core\MasterData\Site;
use App\Models\Modules\EmergencyPreparedness\EmergencyDrill;
use App\Models\Modules\EmergencyPreparedness\EmergencyPlan;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Database\Seeders\QhsseMasterDataSeeder::class);
    $this->seed(\Database\Seeders\NumberingFormatSeeder::class);
    $this->seed(\Database\Seeders\WorkflowSeeder::class);
    $this->seed(\Database\Seeders\NotificationTemplateSeeder::class);
    
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    
    $this->qhsseManager = User::factory()->create();
    $this->qhsseManager->assignRole('QHSSE Manager');
    
    $this->qhsseOfficer = User::factory()->create();
    $this->qhsseOfficer->assignRole('QHSSE Officer');
    
    $this->site = Site::factory()->create();
    $this->contactPerson = User::factory()->create();
    
    $this->plan = EmergencyPlan::factory()->create([
        'site_id' => $this->site->id,
        'contact_person_id' => $this->contactPerson->id,
    ]);
    
    $this->observer = User::factory()->create();
});

it('can list emergency drills', function () {
    EmergencyDrill::factory()->count(5)->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->admin)
        ->get(route('emergency.drills.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Modules/EmergencyPreparedness/Drills/Index'));
});

it('generates drill number on create', function () {
    $data = [
        'emergency_plan_id' => $this->plan->id,
        'scheduled_date' => now()->addDays(7)->toDateString(),
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ];

    actingAs($this->admin)
        ->post(route('emergency.drills.store'), $data)
        ->assertRedirect()
        ->assertSessionHas('success');

    $drill = EmergencyDrill::first();
    expect($drill->drill_number)->toStartWith('EMG-' . date('Y'));
    expect($drill->status)->toBe('scheduled');
});

it('can create emergency drill', function () {
    actingAs($this->admin)
        ->post(route('emergency.drills.store'), [
            'emergency_plan_id' => $this->plan->id,
            'scheduled_date' => now()->addDays(14)->toDateString(),
            'site_id' => $this->site->id,
            'observer_id' => $this->observer->id,
        ])
        ->assertRedirect();

    assertDatabaseHas('emergency_drills', [
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'status' => 'scheduled',
    ]);
});

it('validates required fields on create', function () {
    actingAs($this->admin)
        ->post(route('emergency.drills.store'), [])
        ->assertSessionHasErrors(['emergency_plan_id', 'scheduled_date', 'site_id', 'observer_id']);
});

it('validates scheduled date is in future', function () {
    actingAs($this->admin)
        ->post(route('emergency.drills.store'), [
            'emergency_plan_id' => $this->plan->id,
            'scheduled_date' => now()->subDays(1)->toDateString(),
            'site_id' => $this->site->id,
            'observer_id' => $this->observer->id,
        ])
        ->assertSessionHasErrors('scheduled_date');
});

it('can show emergency drill', function () {
    $drill = EmergencyDrill::factory()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->admin)
        ->get(route('emergency.drills.show', $drill))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Modules/EmergencyPreparedness/Drills/Show')
            ->has('drill'));
});

it('can update emergency drill', function () {
    $drill = EmergencyDrill::factory()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    $newObserver = User::factory()->create();

    actingAs($this->admin)
        ->put(route('emergency.drills.update', $drill), [
            'emergency_plan_id' => $this->plan->id,
            'scheduled_date' => now()->addDays(30)->toDateString(),
            'site_id' => $this->site->id,
            'observer_id' => $newObserver->id,
        ])
        ->assertRedirect();

    expect($drill->fresh()->observer_id)->toBe($newObserver->id);
});

it('can execute emergency drill', function () {
    $drill = EmergencyDrill::factory()->scheduled()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->qhsseOfficer)
        ->post(route('emergency.drills.execute', $drill), [
            'executed_date' => now()->toDateString(),
            'participants_count' => 25,
            'result' => 'pass',
            'findings' => 'All participants evacuated successfully',
            'recommendations' => 'Continue regular drills',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $drill->refresh();
    expect($drill->status)->toBe('executed');
    expect($drill->result)->toBe('pass');
    expect($drill->participants_count)->toBe(25);
});

it('validates execute drill requires all fields', function () {
    $drill = EmergencyDrill::factory()->scheduled()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->qhsseOfficer)
        ->post(route('emergency.drills.execute', $drill), [])
        ->assertSessionHasErrors(['executed_date', 'participants_count', 'result']);
});

it('cannot execute already executed drill', function () {
    $drill = EmergencyDrill::factory()->executed()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->qhsseOfficer)
        ->post(route('emergency.drills.execute', $drill), [
            'executed_date' => now()->toDateString(),
            'participants_count' => 30,
            'result' => 'pass',
        ])
        ->assertSessionHas('error');
});

it('validates executed date cannot be in future', function () {
    $drill = EmergencyDrill::factory()->scheduled()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->qhsseOfficer)
        ->post(route('emergency.drills.execute', $drill), [
            'executed_date' => now()->addDays(1)->toDateString(),
            'participants_count' => 20,
            'result' => 'pass',
        ])
        ->assertSessionHasErrors('executed_date');
});

it('can delete emergency drill', function () {
    $drill = EmergencyDrill::factory()->scheduled()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->admin)
        ->delete(route('emergency.drills.destroy', $drill))
        ->assertRedirect();

    expect(EmergencyDrill::find($drill->id))->toBeNull();
});

it('enforces permission for viewing drills', function () {
    $user = User::factory()->create();
    $user->assignRole('Contractor');

    actingAs($user)
        ->get(route('emergency.drills.index'))
        ->assertForbidden();
});

it('enforces permission for creating drills', function () {
    $user = User::factory()->create();
    $user->assignRole('Employee / Reporter');

    actingAs($user)
        ->post(route('emergency.drills.store'), [
            'emergency_plan_id' => $this->plan->id,
            'scheduled_date' => now()->addDays(7)->toDateString(),
            'site_id' => $this->site->id,
            'observer_id' => $this->observer->id,
        ])
        ->assertForbidden();
});

it('enforces permission for executing drills', function () {
    $drill = EmergencyDrill::factory()->scheduled()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);
    
    $user = User::factory()->create();
    $user->assignRole('Employee / Reporter');

    actingAs($user)
        ->post(route('emergency.drills.execute', $drill), [
            'executed_date' => now()->toDateString(),
            'participants_count' => 20,
            'result' => 'pass',
        ])
        ->assertForbidden();
});

it('enforces permission for deleting drills', function () {
    $drill = EmergencyDrill::factory()->scheduled()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);
    
    $user = User::factory()->create();
    $user->assignRole('QHSSE Officer');

    actingAs($user)
        ->delete(route('emergency.drills.destroy', $drill))
        ->assertForbidden();
});

it('can filter drills by site', function () {
    $site2 = Site::factory()->create();
    EmergencyDrill::factory()->count(3)->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);
    EmergencyDrill::factory()->count(2)->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $site2->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->admin)
        ->get(route('emergency.drills.index', ['site_id' => $this->site->id]))
        ->assertOk();
});

it('can filter drills by status', function () {
    EmergencyDrill::factory()->scheduled()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);
    EmergencyDrill::factory()->executed()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->admin)
        ->get(route('emergency.drills.index', ['status' => 'scheduled']))
        ->assertOk();
});

it('can filter drills by result', function () {
    EmergencyDrill::factory()->passed()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);
    EmergencyDrill::factory()->failed()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->admin)
        ->get(route('emergency.drills.index', ['result' => 'pass']))
        ->assertOk();
});

it('can export emergency drills', function () {
    EmergencyDrill::factory()->count(3)->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    actingAs($this->qhsseManager)
        ->get(route('emergency.drills.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('enforces permission for export', function () {
    $user = User::factory()->create();
    $user->assignRole('Employee / Reporter');

    actingAs($user)
        ->get(route('emergency.drills.export'))
        ->assertForbidden();
});

it('identifies upcoming drills correctly', function () {
    $upcomingDrill = EmergencyDrill::factory()->upcoming()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    expect($upcomingDrill->isUpcoming())->toBeTrue();
});

it('identifies overdue drills correctly', function () {
    $overdueDrill = EmergencyDrill::factory()->overdue()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    expect($overdueDrill->isOverdue())->toBeTrue();
});

it('returns correct status labels', function () {
    $scheduledDrill = EmergencyDrill::factory()->scheduled()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);
    
    $executedDrill = EmergencyDrill::factory()->executed()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    expect($scheduledDrill->status_label)->toBe('Terjadwal');
    expect($executedDrill->status_label)->toBe('Dilaksanakan');
});

it('returns correct result labels', function () {
    $passedDrill = EmergencyDrill::factory()->passed()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);
    
    $failedDrill = EmergencyDrill::factory()->failed()->create([
        'emergency_plan_id' => $this->plan->id,
        'site_id' => $this->site->id,
        'observer_id' => $this->observer->id,
    ]);

    expect($passedDrill->result_label)->toBe('Lulus');
    expect($failedDrill->result_label)->toBe('Gagal');
});

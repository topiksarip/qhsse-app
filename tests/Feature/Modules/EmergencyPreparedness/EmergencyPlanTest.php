<?php

declare(strict_types=1);

use App\Core\Services\NumberingService;
use App\Models\Core\MasterData\Site;
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
    
    $this->site = Site::factory()->create();
    $this->contactPerson = User::factory()->create();
});

it('can list emergency plans', function () {
    EmergencyPlan::factory()->count(5)->create(['site_id' => $this->site->id, 'contact_person_id' => $this->contactPerson->id]);

    actingAs($this->admin)
        ->get(route('emergency.plans.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Modules/EmergencyPreparedness/Plans/Index'));
});

it('generates plan number on create', function () {
    $data = [
        'name' => 'Fire Emergency Plan',
        'type' => 'fire',
        'site_id' => $this->site->id,
        'description' => 'Fire emergency response plan',
        'response_procedure' => 'Evacuate building immediately',
        'escalation_procedure' => 'Contact fire department',
        'contact_person_id' => $this->contactPerson->id,
    ];

    actingAs($this->admin)
        ->post(route('emergency.plans.store'), $data)
        ->assertRedirect()
        ->assertSessionHas('success');

    $plan = EmergencyPlan::first();
    expect($plan->plan_number)->toStartWith('EMG-' . date('Y'));
});

it('can create emergency plan with all fields', function () {
    $data = [
        'name' => 'Medical Emergency Plan',
        'type' => 'medical',
        'site_id' => $this->site->id,
        'description' => 'Medical emergency response',
        'response_procedure' => 'Call first aider',
        'escalation_procedure' => 'Call ambulance',
        'contact_person_id' => $this->contactPerson->id,
        'emergency_contacts' => [
            ['name' => 'Dr. Smith', 'role' => 'Medical Officer', 'phone' => '+62812345678'],
        ],
        'equipment_needed' => 'First aid kit, AED',
    ];

    actingAs($this->admin)
        ->post(route('emergency.plans.store'), $data)
        ->assertRedirect();

    assertDatabaseHas('emergency_plans', [
        'name' => 'Medical Emergency Plan',
        'type' => 'medical',
    ]);
});

it('validates required fields on create', function () {
    actingAs($this->admin)
        ->post(route('emergency.plans.store'), [
            'type' => 'fire',
        ])
        ->assertSessionHasErrors(['name', 'site_id', 'description', 'response_procedure', 'escalation_procedure', 'contact_person_id']);
});

it('validates plan type', function () {
    actingAs($this->admin)
        ->post(route('emergency.plans.store'), [
            'name' => 'Test Plan',
            'type' => 'invalid_type',
            'site_id' => $this->site->id,
            'description' => 'Test',
            'response_procedure' => 'Test',
            'escalation_procedure' => 'Test',
            'contact_person_id' => $this->contactPerson->id,
        ])
        ->assertSessionHasErrors('type');
});

it('can show emergency plan', function () {
    $plan = EmergencyPlan::factory()->create([
        'site_id' => $this->site->id,
        'contact_person_id' => $this->contactPerson->id,
    ]);

    actingAs($this->admin)
        ->get(route('emergency.plans.show', $plan))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Modules/EmergencyPreparedness/Plans/Show')
            ->has('plan'));
});

it('can update emergency plan', function () {
    $plan = EmergencyPlan::factory()->create([
        'site_id' => $this->site->id,
        'contact_person_id' => $this->contactPerson->id,
    ]);

    actingAs($this->admin)
        ->put(route('emergency.plans.update', $plan), [
            'name' => 'Updated Plan',
            'type' => 'evacuation',
            'site_id' => $this->site->id,
            'description' => 'Updated description',
            'response_procedure' => 'Updated procedure',
            'escalation_procedure' => 'Updated escalation',
            'contact_person_id' => $this->contactPerson->id,
        ])
        ->assertRedirect();

    expect($plan->fresh()->name)->toBe('Updated Plan');
});

it('cannot change plan number on update', function () {
    $plan = EmergencyPlan::factory()->create([
        'site_id' => $this->site->id,
        'contact_person_id' => $this->contactPerson->id,
    ]);
    $originalNumber = $plan->plan_number;

    actingAs($this->admin)
        ->put(route('emergency.plans.update', $plan), [
            'plan_number' => 'DIFFERENT-2026-9999',
            'name' => 'Updated Plan',
            'type' => 'fire',
            'site_id' => $this->site->id,
            'description' => 'Test',
            'response_procedure' => 'Test',
            'escalation_procedure' => 'Test',
            'contact_person_id' => $this->contactPerson->id,
        ])
        ->assertRedirect();

    expect($plan->fresh()->plan_number)->toBe($originalNumber);
});

it('can delete emergency plan', function () {
    $plan = EmergencyPlan::factory()->create([
        'site_id' => $this->site->id,
        'contact_person_id' => $this->contactPerson->id,
    ]);

    actingAs($this->admin)
        ->delete(route('emergency.plans.destroy', $plan))
        ->assertRedirect();

    expect(EmergencyPlan::find($plan->id))->toBeNull();
});

it('enforces permission for viewing plans', function () {
    $user = User::factory()->create();
    $user->assignRole('Contractor');

    actingAs($user)
        ->get(route('emergency.plans.index'))
        ->assertForbidden();
});

it('enforces permission for creating plans', function () {
    $user = User::factory()->create();
    $user->assignRole('Employee / Reporter');

    actingAs($user)
        ->post(route('emergency.plans.store'), [
            'name' => 'Test Plan',
            'type' => 'fire',
            'site_id' => $this->site->id,
            'description' => 'Test',
            'response_procedure' => 'Test',
            'escalation_procedure' => 'Test',
            'contact_person_id' => $this->contactPerson->id,
        ])
        ->assertForbidden();
});

it('enforces permission for deleting plans', function () {
    $plan = EmergencyPlan::factory()->create([
        'site_id' => $this->site->id,
        'contact_person_id' => $this->contactPerson->id,
    ]);
    $user = User::factory()->create();
    $user->assignRole('QHSSE Officer');

    actingAs($user)
        ->delete(route('emergency.plans.destroy', $plan))
        ->assertForbidden();
});

it('can filter plans by site', function () {
    $site2 = Site::factory()->create();
    EmergencyPlan::factory()->count(3)->create(['site_id' => $this->site->id, 'contact_person_id' => $this->contactPerson->id]);
    EmergencyPlan::factory()->count(2)->create(['site_id' => $site2->id, 'contact_person_id' => $this->contactPerson->id]);

    actingAs($this->admin)
        ->get(route('emergency.plans.index', ['site_id' => $this->site->id]))
        ->assertOk();
});

it('can filter plans by type', function () {
    EmergencyPlan::factory()->fire()->create(['site_id' => $this->site->id, 'contact_person_id' => $this->contactPerson->id]);
    EmergencyPlan::factory()->medical()->create(['site_id' => $this->site->id, 'contact_person_id' => $this->contactPerson->id]);

    actingAs($this->admin)
        ->get(route('emergency.plans.index', ['type' => 'fire']))
        ->assertOk();
});

it('can search plans by number', function () {
    $plan = EmergencyPlan::factory()->create(['site_id' => $this->site->id, 'contact_person_id' => $this->contactPerson->id]);

    actingAs($this->admin)
        ->get(route('emergency.plans.index', ['search' => substr($plan->plan_number, 0, 10)]))
        ->assertOk();
});

it('can export emergency plans', function () {
    EmergencyPlan::factory()->count(3)->create(['site_id' => $this->site->id, 'contact_person_id' => $this->contactPerson->id]);

    actingAs($this->qhsseManager)
        ->get(route('emergency.plans.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('enforces permission for export', function () {
    $user = User::factory()->create();
    $user->assignRole('Employee / Reporter');

    actingAs($user)
        ->get(route('emergency.plans.export'))
        ->assertForbidden();
});

it('stores emergency contacts as JSON', function () {
    $contacts = [
        ['name' => 'Contact 1', 'role' => 'Fire Warden', 'phone' => '+62811111111'],
        ['name' => 'Contact 2', 'role' => 'First Aider', 'phone' => '+62822222222'],
    ];

    actingAs($this->admin)
        ->post(route('emergency.plans.store'), [
            'name' => 'Test Plan',
            'type' => 'fire',
            'site_id' => $this->site->id,
            'description' => 'Test',
            'response_procedure' => 'Test',
            'escalation_procedure' => 'Test',
            'contact_person_id' => $this->contactPerson->id,
            'emergency_contacts' => $contacts,
        ])
        ->assertRedirect();

    $plan = EmergencyPlan::first();
    expect($plan->emergency_contacts)->toBeArray();
    expect(count($plan->emergency_contacts))->toBe(2);
});

it('returns correct type label', function () {
    $plan = EmergencyPlan::factory()->fire()->create([
        'site_id' => $this->site->id,
        'contact_person_id' => $this->contactPerson->id,
    ]);

    expect($plan->type_label)->toBe('Kebakaran');
});

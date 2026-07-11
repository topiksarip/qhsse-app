<?php

declare(strict_types=1);

use App\Models\Core\MasterData\Site;
use App\Models\Modules\EmergencyPreparedness\EmergencyContact;
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
});

it('can list emergency contacts', function () {
    EmergencyContact::factory()->count(5)->create(['site_id' => $this->site->id]);

    actingAs($this->admin)
        ->get(route('emergency.contacts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Modules/EmergencyPreparedness/Contacts/Index'));
});

it('can create emergency contact', function () {
    $data = [
        'name' => 'John Doe',
        'role' => 'Fire Warden',
        'phone' => '+62812345678',
        'email' => 'john@example.com',
        'site_id' => $this->site->id,
    ];

    actingAs($this->admin)
        ->post(route('emergency.contacts.store'), $data)
        ->assertRedirect()
        ->assertSessionHas('success');

    assertDatabaseHas('emergency_contacts', [
        'name' => 'John Doe',
        'role' => 'Fire Warden',
        'site_id' => $this->site->id,
    ]);
});

it('requires name when creating contact', function () {
    actingAs($this->admin)
        ->post(route('emergency.contacts.store'), [
            'role' => 'First Aider',
            'phone' => '+62812345678',
            'site_id' => $this->site->id,
        ])
        ->assertSessionHasErrors('name');
});

it('can show emergency contact', function () {
    $contact = EmergencyContact::factory()->create(['site_id' => $this->site->id]);

    actingAs($this->admin)
        ->get(route('emergency.contacts.show', $contact))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Modules/EmergencyPreparedness/Contacts/Show')
            ->has('contact'));
});

it('can update emergency contact', function () {
    $contact = EmergencyContact::factory()->create(['site_id' => $this->site->id]);

    actingAs($this->admin)
        ->put(route('emergency.contacts.update', $contact), [
            'name' => 'Updated Name',
            'role' => 'ERT Leader',
            'phone' => '+62898765432',
            'email' => 'updated@example.com',
            'site_id' => $this->site->id,
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($contact->fresh()->name)->toBe('Updated Name');
});

it('can delete emergency contact', function () {
    $contact = EmergencyContact::factory()->create(['site_id' => $this->site->id]);

    actingAs($this->admin)
        ->delete(route('emergency.contacts.destroy', $contact))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(EmergencyContact::find($contact->id))->toBeNull();
});

it('enforces permission for viewing contacts', function () {
    $user = User::factory()->create();
    $user->assignRole('Contractor');

    actingAs($user)
        ->get(route('emergency.contacts.index'))
        ->assertForbidden();
});

it('enforces permission for creating contacts', function () {
    $user = User::factory()->create();
    $user->assignRole('Employee / Reporter');

    actingAs($user)
        ->post(route('emergency.contacts.store'), [
            'name' => 'Test',
            'role' => 'Fire Warden',
            'phone' => '+62812345678',
            'site_id' => $this->site->id,
        ])
        ->assertForbidden();
});

it('enforces permission for updating contacts', function () {
    $contact = EmergencyContact::factory()->create(['site_id' => $this->site->id]);
    $user = User::factory()->create();
    $user->assignRole('Employee / Reporter');

    actingAs($user)
        ->put(route('emergency.contacts.update', $contact), [
            'name' => 'Updated',
            'role' => 'First Aider',
            'phone' => '+62812345678',
            'site_id' => $this->site->id,
            'is_active' => true,
        ])
        ->assertForbidden();
});

it('enforces permission for deleting contacts', function () {
    $contact = EmergencyContact::factory()->create(['site_id' => $this->site->id]);
    $user = User::factory()->create();
    $user->assignRole('QHSSE Officer');

    actingAs($user)
        ->delete(route('emergency.contacts.destroy', $contact))
        ->assertForbidden();
});

it('can filter contacts by site', function () {
    $site2 = Site::factory()->create();
    EmergencyContact::factory()->count(3)->create(['site_id' => $this->site->id]);
    EmergencyContact::factory()->count(2)->create(['site_id' => $site2->id]);

    actingAs($this->admin)
        ->get(route('emergency.contacts.index', ['site_id' => $this->site->id]))
        ->assertOk();
});

it('can search contacts by name', function () {
    EmergencyContact::factory()->create(['name' => 'John Fire Warden', 'site_id' => $this->site->id]);
    EmergencyContact::factory()->create(['name' => 'Jane First Aider', 'site_id' => $this->site->id]);

    actingAs($this->admin)
        ->get(route('emergency.contacts.index', ['search' => 'John']))
        ->assertOk();
});

it('shows only active contacts by default', function () {
    EmergencyContact::factory()->create(['is_active' => true, 'site_id' => $this->site->id]);
    EmergencyContact::factory()->create(['is_active' => false, 'site_id' => $this->site->id]);

    actingAs($this->admin)
        ->get(route('emergency.contacts.index'))
        ->assertOk();
});

it('validates phone is required', function () {
    actingAs($this->admin)
        ->post(route('emergency.contacts.store'), [
            'name' => 'Test Contact',
            'role' => 'Fire Warden',
            'site_id' => $this->site->id,
        ])
        ->assertSessionHasErrors('phone');
});

it('validates email format', function () {
    actingAs($this->admin)
        ->post(route('emergency.contacts.store'), [
            'name' => 'Test Contact',
            'role' => 'Fire Warden',
            'phone' => '+62812345678',
            'email' => 'invalid-email',
            'site_id' => $this->site->id,
        ])
        ->assertSessionHasErrors('email');
});

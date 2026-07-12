<?php

use App\Models\Core\Activity\ActivityLog;
use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\Modules\LegalCompliance\LegalRegister;
use App\Models\User;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(NumberingFormatSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('renders the Legal register index', function () {
    actingAs($this->admin)
        ->get(route('legal.registers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Modules/LegalCompliance/Index')
            ->has('registers')
            ->has('filters')
        );
});

it('renders the Legal create form with approved controlled documents', function () {
    $document = ControlledDocument::factory()->create(['status' => 'approved']);

    actingAs($this->admin)
        ->get(route('legal.registers.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Modules/LegalCompliance/Create')
            ->where('documents.0.id', $document->id)
            ->where('documents.0.document_number', $document->document_number)
        );
});

it('streams the Legal register export as CSV', function () {
    actingAs($this->admin)
        ->get(route('legal.registers.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('blocks users without Legal permissions', function () {
    $user = User::factory()->create();

    actingAs($user);

    $this->get(route('legal.registers.index'))->assertForbidden();
    $this->get(route('legal.registers.create'))->assertForbidden();
    $this->get(route('legal.registers.export'))->assertForbidden();
});

it('stores a Legal register with a generated number and activity', function () {
    $document = ControlledDocument::factory()->create(['status' => 'approved']);

    actingAs($this->admin)
        ->post(route('legal.registers.store'), [
            'title' => 'Peraturan Keselamatan Kerja',
            'regulation_name' => 'Peraturan Uji',
            'regulation_number' => 'REG-001',
            'issuing_body' => 'Instansi Uji',
            'category' => 'national',
            'compliance_status' => 'in_progress',
            'owner_id' => $this->admin->id,
            'document_id' => $document->id,
        ])
        ->assertRedirect();

    $register = LegalRegister::query()->sole();

    expect($register->register_number)->toMatch('/^LEG-\d{4}-\d{4}$/');
    expect($register->document)->toBeInstanceOf(ControlledDocument::class);
    expect($register->document->is($document))->toBeTrue();
    expect(ActivityLog::query()
        ->where('module_name', 'legal')
        ->where('reference_id', $register->id)
        ->where('event', 'legal.register.created')
        ->exists())->toBeTrue();

    $this->get(route('legal.registers.show', $register))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Modules/LegalCompliance/Show')
            ->where('register.id', $register->id)
        );
});

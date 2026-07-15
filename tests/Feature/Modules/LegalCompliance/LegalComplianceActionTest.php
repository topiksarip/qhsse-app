<?php

use App\Core\Notifications\NotificationService;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Modules\LegalCompliance\LegalObligation;
use App\Models\Modules\LegalCompliance\LegalRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed([
        \Database\Seeders\RolesAndPermissionsSeeder::class,
        \Database\Seeders\NumberingFormatSeeder::class,
        \Database\Seeders\NotificationTemplateSeeder::class,
    ]);
});

it('creates register + audit + notify QHSSE team (WS-1/WS-4)', function () {
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');
    $officer->givePermissionTo(['legal.register.create', 'legal.register.view', 'legal.register.update']);
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');

    $site = \App\Models\Core\MasterData\Site::factory()->create();

    actingAs($officer)
        ->post(route('legal.registers.store'), [
            'register_number' => 'LEG-001',
            'title' => 'Reg A',
            'regulation_name' => 'Reg',
            'regulation_number' => 'RN-001',
            'issuing_body' => 'Kemenaker',
            'category' => 'national',
            'site_id' => $site->id,
            'owner_id' => $officer->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $register = LegalRegister::where('owner_id', $officer->id)->where('title', 'Reg A')->first();
    expect($register)->not->toBeNull();

    // audit created
    expect(AuditLog::where('module_name', 'legal')->where('event', 'created')->where('reference_id', $register->id)->exists())->toBeTrue();

    // notify QHSSE team
    expect(CoreNotification::where('type', 'legal.register.created')->where('reference_id', $register->id)->count())->toBeGreaterThan(0);
});

it('notifies QHSSE team when compliance -> non_compliant (WS-1)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $register = LegalRegister::create([
        'register_number' => 'LEG-002',
        'title' => 'Reg B',
        'regulation_name' => 'Reg',
        'regulation_number' => 'RN-002',
        'issuing_body' => 'Kemenaker',
        'category' => 'national',
        'site_id' => $site->id,
        'owner_id' => $manager->id,
        'status' => 'active',
        'compliance_status' => 'compliant',
    ]);

    actingAs($manager)
        ->put(route('legal.registers.update', $register), [
            'title' => 'Reg B',
            'regulation_name' => 'Reg',
            'regulation_number' => 'RN-002',
            'issuing_body' => 'Kemenaker',
            'category' => 'national',
            'site_id' => $site->id,
            'owner_id' => $manager->id,
            'compliance_status' => 'non_compliant',
        ])
        ->assertRedirect();

    expect(CoreNotification::where('type', 'legal.compliance.changed')->where('reference_id', $register->id)->count())->toBeGreaterThan(0);
});

it('blocks destroy of inactive register (WS-5 G6)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $register = LegalRegister::create([
        'register_number' => 'LEG-003',
        'title' => 'Reg C',
        'regulation_name' => 'Reg',
        'regulation_number' => 'RN-003',
        'issuing_body' => 'Kemenaker',
        'category' => 'national',
        'site_id' => $site->id,
        'owner_id' => $manager->id,
        'status' => 'inactive',
        'compliance_status' => 'compliant',
    ]);

    actingAs($manager)
        ->delete(route('legal.registers.destroy', $register))
        ->assertForbidden();
});

it('blocks update of inactive register (WS-5 G7)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $register = LegalRegister::create([
        'register_number' => 'LEG-004',
        'title' => 'Reg D',
        'regulation_name' => 'Reg',
        'regulation_number' => 'RN-004',
        'issuing_body' => 'Kemenaker',
        'category' => 'national',
        'site_id' => $site->id,
        'owner_id' => $manager->id,
        'status' => 'inactive',
        'compliance_status' => 'compliant',
    ]);

    actingAs($manager)
        ->put(route('legal.registers.update', $register), [
            'title' => 'Reg D',
            'regulation_name' => 'Reg',
            'regulation_number' => 'RN-004',
            'issuing_body' => 'Kemenaker',
            'category' => 'national',
            'site_id' => $site->id,
            'owner_id' => $manager->id,
            'compliance_status' => 'compliant',
        ])
        ->assertForbidden();
});

it('scope hides other-site register in index (WS-3)', function () {
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');
    $officer->givePermissionTo('core.scope.site');
    $siteA = \App\Models\Core\MasterData\Site::factory()->create();
    $siteB = \App\Models\Core\MasterData\Site::factory()->create();
    $dept = \App\Models\Core\MasterData\Department::factory()->create(['site_id' => $siteA->id]);
    $employee = \App\Models\Core\Users\Employee::factory()->create([
        'site_id' => $siteA->id,
        'department_id' => $dept->id,
    ]);
    $officer->update(['employee_id' => $employee->id]);

    LegalRegister::create([
        'register_number' => 'LEG-A',
        'title' => 'Own site',
        'regulation_name' => 'Reg',
        'regulation_number' => 'RN-A',
        'issuing_body' => 'Kemenaker',
        'category' => 'national',
        'site_id' => $siteA->id,
        'owner_id' => $officer->id,
        'status' => 'active',
        'compliance_status' => 'compliant',
    ]);
    LegalRegister::create([
        'register_number' => 'LEG-B',
        'title' => 'Other site',
        'regulation_name' => 'Reg',
        'regulation_number' => 'RN-B',
        'issuing_body' => 'Kemenaker',
        'category' => 'national',
        'site_id' => $siteB->id,
        'owner_id' => $officer->id,
        'status' => 'active',
        'compliance_status' => 'compliant',
    ]);

    actingAs($officer)
        ->get(route('legal.registers.index'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->where('registers.total', 1));
});

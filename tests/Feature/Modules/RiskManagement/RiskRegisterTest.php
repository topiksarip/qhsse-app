<?php

declare(strict_types=1);

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\RiskMatrixLevel;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\RiskManagement\RiskRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\NumberingFormatSeeder']);
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\QhsseMasterDataSeeder']);
});

// Helper functions
function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');
    return $user;
}

function officerUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('QHSSE Officer');
    return $user;
}

function supervisorUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Supervisor');
    return $user;
}

function viewerUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Auditor');
    return $user;
}

function employeeUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Employee / Reporter');
    return $user;
}

function seededRiskLevel(int $likelihood, int $consequence): RiskMatrixLevel
{
    return RiskMatrixLevel::query()
        ->where('likelihood', $likelihood)
        ->where('consequence', $consequence)
        ->firstOrFail();
}

// Category 1: Functional (Happy Path)
test('authorized user can view risk register list', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('risk.registers.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/RiskManagement/Index')
        ->has('items')
        ->has('filters')
    );
});

test('authorized user can create risk register with auto numbering', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $site = Site::factory()->create();

    $response = $this->post(route('risk.registers.store'), [
        'title' => 'Risiko Jatuh dari Ketinggian',
        'type' => 'hiradc',
        'site_id' => $site->id,
        'activity' => 'Bekerja di atas scaffolding',
        'hazard' => 'Jatuh dari ketinggian tanpa harness',
        'existing_controls' => 'Guard rail di scaffolding',
        'owner_id' => $admin->id,
    ]);

    $register = RiskRegister::first();
    expect($register)->not->toBeNull();
    expect($register->register_number)->toStartWith('RSK-');
    expect($register->register_number)->toMatch('/^RSK-\d{4}-\d{4}$/');
    expect($register->title)->toBe('Risiko Jatuh dari Ketinggian');
    expect($register->status)->toBe('identified');
    expect($register->owner_id)->toBe($admin->id);

    $response->assertRedirect(route('risk.registers.show', $register));
});

test('authorized user can assess risk register', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $site = Site::factory()->create();
    $severity = Severity::factory()->create(['level' => 3]);
    $riskLevel = seededRiskLevel(4, 3);

    $riskRegister = RiskRegister::factory()->create([
        'site_id' => $site->id,
        'owner_id' => $admin->id,
        'status' => 'identified',
    ]);

    $response = $this->post(route('risk.registers.assess', $riskRegister), [
        'severity_id' => $severity->id,
        'probability_id' => 4,
        'risk_level_id' => $riskLevel->id,
        'additional_controls' => 'Wajib pakai full body harness',
    ]);

    $riskRegister->refresh();
    expect($riskRegister->status)->toBe('assessed');
    expect($riskRegister->severity_id)->toBe($severity->id);
    expect($riskRegister->probability_id)->toBe(4);
    expect($riskRegister->risk_level_id)->toBe($riskLevel->id);
    expect($riskRegister->additional_controls)->toBe('Wajib pakai full body harness');

    $response->assertRedirect(route('risk.registers.show', $riskRegister));
});

test('risk register can transition through status workflow', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $site = Site::factory()->create();
    $severity = Severity::factory()->create(['level' => 3]);
    $riskLevel = seededRiskLevel(4, 3);

    $riskRegister = RiskRegister::factory()->create([
        'site_id' => $site->id,
        'owner_id' => $admin->id,
        'status' => 'identified',
    ]);

    // Assess
    $this->post(route('risk.registers.assess', $riskRegister), [
        'severity_id' => $severity->id,
        'probability_id' => 4,
        'risk_level_id' => $riskLevel->id,
        'additional_controls' => 'Kontrol tambahan',
    ]);
    $riskRegister->refresh();
    expect($riskRegister->status)->toBe('assessed');

    // Needs controls
    $this->post(route('risk.registers.needs_controls', $riskRegister));
    $riskRegister->refresh();
    expect($riskRegister->status)->toBe('controls_needed');

    // Implement controls
    $this->post(route('risk.registers.implement_controls', $riskRegister));
    $riskRegister->refresh();
    expect($riskRegister->status)->toBe('controls_in_place');

    // Monitor
    $this->post(route('risk.registers.monitor', $riskRegister));
    $riskRegister->refresh();
    expect($riskRegister->status)->toBe('monitored');
});

test('risk register can be set to obsolete', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $riskRegister = RiskRegister::factory()->create([
        'owner_id' => $admin->id,
        'status' => 'assessed',
    ]);

    $response = $this->post(route('risk.registers.obsolete', $riskRegister));

    $riskRegister->refresh();
    expect($riskRegister->status)->toBe('obsolete');

    $response->assertRedirect(route('risk.registers.show', $riskRegister));
});

test('authorized user can update risk register', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $riskRegister = RiskRegister::factory()->create([
        'owner_id' => $admin->id,
        'title' => 'Original Title',
    ]);

    $response = $this->put(route('risk.registers.update', $riskRegister), [
        'title' => 'Updated Title',
        'hazard' => 'Updated hazard description',
    ]);

    $riskRegister->refresh();
    expect($riskRegister->title)->toBe('Updated Title');
    expect($riskRegister->hazard)->toBe('Updated hazard description');

    $response->assertRedirect(route('risk.registers.show', $riskRegister));
});

test('authorized user can export risk register list', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    RiskRegister::factory()->count(3)->create([
        'owner_id' => $admin->id,
    ]);

    $response = $this->get(route('risk.registers.export'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->headers->get('Content-Disposition'))->toContain('risk_registers_export_');
});

test('list page supports search and filters', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $site1 = Site::factory()->create(['name' => 'Site A']);
    $site2 = Site::factory()->create(['name' => 'Site B']);

    RiskRegister::factory()->create([
        'title' => 'Risk Alpha',
        'site_id' => $site1->id,
        'type' => 'hiradc',
        'status' => 'identified',
        'owner_id' => $admin->id,
    ]);

    RiskRegister::factory()->create([
        'title' => 'Risk Beta',
        'site_id' => $site2->id,
        'type' => 'jsa',
        'status' => 'assessed',
        'owner_id' => $admin->id,
    ]);

    // Search
    $response = $this->get(route('risk.registers.index', ['search' => 'Alpha']));
    $response->assertStatus(200);

    // Filter by site
    $response = $this->get(route('risk.registers.index', ['site_id' => $site1->id]));
    $response->assertStatus(200);

    // Filter by type
    $response = $this->get(route('risk.registers.index', ['type' => 'hiradc']));
    $response->assertStatus(200);

    // Filter by status
    $response = $this->get(route('risk.registers.index', ['status' => 'assessed']));
    $response->assertStatus(200);
});

// Category 2: Permission & Authorization
test('unauthorized user cannot view risk register list', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('risk.registers.index'));

    $response->assertStatus(403);
});

test('unauthorized user cannot create risk register', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $site = Site::factory()->create();

    $response = $this->post(route('risk.registers.store'), [
        'title' => 'Test Risk',
        'type' => 'hiradc',
        'site_id' => $site->id,
        'activity' => 'Test activity',
        'hazard' => 'Test hazard',
        'owner_id' => $user->id,
    ]);

    $response->assertStatus(403);
});

test('unauthorized user cannot assess risk register', function (): void {
    $admin = adminUser();
    $employee = employeeUser();

    $riskRegister = RiskRegister::factory()->create([
        'owner_id' => $admin->id,
        'status' => 'identified',
    ]);

    $this->actingAs($employee);

    $severity = Severity::factory()->create(['level' => 3]);
    $riskLevel = seededRiskLevel(4, 3);

    $response = $this->post(route('risk.registers.assess', $riskRegister), [
        'severity_id' => $severity->id,
        'probability_id' => 4,
        'risk_level_id' => $riskLevel->id,
    ]);

    $response->assertStatus(403);
});

test('auditor can view but cannot create risk register', function (): void {
    $auditor = viewerUser();
    $this->actingAs($auditor);

    // Can view list
    $response = $this->get(route('risk.registers.index'));
    $response->assertStatus(200);

    // Cannot create
    $site = Site::factory()->create();
    $response = $this->post(route('risk.registers.store'), [
        'title' => 'Test Risk',
        'type' => 'hiradc',
        'site_id' => $site->id,
        'activity' => 'Test activity',
        'hazard' => 'Test hazard',
        'owner_id' => $auditor->id,
    ]);
    $response->assertStatus(403);
});

test('supervisor can create but cannot assess risk register', function (): void {
    $supervisor = supervisorUser();
    $this->actingAs($supervisor);

    $site = Site::factory()->create();

    // Can create
    $response = $this->post(route('risk.registers.store'), [
        'title' => 'Test Risk',
        'type' => 'hiradc',
        'site_id' => $site->id,
        'activity' => 'Test activity',
        'hazard' => 'Test hazard',
        'owner_id' => $supervisor->id,
    ]);
    $response->assertStatus(302); // Redirect to show

    $riskRegister = RiskRegister::first();

    // Cannot assess
    $severity = Severity::factory()->create(['level' => 3]);
    $riskLevel = seededRiskLevel(4, 3);

    $response = $this->post(route('risk.registers.assess', $riskRegister), [
        'severity_id' => $severity->id,
        'probability_id' => 4,
        'risk_level_id' => $riskLevel->id,
    ]);
    $response->assertStatus(403);
});

// Category 3: Validation & Business Rules
test('create risk register requires mandatory fields', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('risk.registers.store'), [
        // Missing title, type, site_id, activity, hazard, owner_id
    ]);

    $response->assertStatus(302); // Redirect back with errors
    $response->assertSessionHasErrors(['title', 'type', 'site_id', 'activity', 'hazard', 'owner_id']);
});

test('assess requires severity, probability, and risk_level', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $riskRegister = RiskRegister::factory()->create([
        'owner_id' => $admin->id,
        'status' => 'identified',
    ]);

    $response = $this->post(route('risk.registers.assess', $riskRegister), [
        // Missing severity_id, probability_id, risk_level_id
    ]);

    $response->assertStatus(302); // Redirect back with errors
    $response->assertSessionHasErrors(['severity_id', 'probability_id', 'risk_level_id']);
});

test('cannot assess risk register if status is not identified', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $riskRegister = RiskRegister::factory()->create([
        'owner_id' => $admin->id,
        'status' => 'assessed', // Already assessed
    ]);

    $severity = Severity::factory()->create(['level' => 3]);
    $riskLevel = seededRiskLevel(4, 3);

    $response = $this->post(route('risk.registers.assess', $riskRegister), [
        'severity_id' => $severity->id,
        'probability_id' => 4,
        'risk_level_id' => $riskLevel->id,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('error');
});

test('cannot update obsolete risk register', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $riskRegister = RiskRegister::factory()->create([
        'owner_id' => $admin->id,
        'status' => 'obsolete',
    ]);

    $response = $this->put(route('risk.registers.update', $riskRegister), [
        'title' => 'Updated Title',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('error');
});

test('type must be valid enum value', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $site = Site::factory()->create();

    $response = $this->post(route('risk.registers.store'), [
        'title' => 'Test Risk',
        'type' => 'invalid_type', // Invalid
        'site_id' => $site->id,
        'activity' => 'Test activity',
        'hazard' => 'Test hazard',
        'owner_id' => $admin->id,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('type');
});

test('probability_id must be between 1 and 5', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $riskRegister = RiskRegister::factory()->create([
        'owner_id' => $admin->id,
        'status' => 'identified',
    ]);

    $severity = Severity::factory()->create(['level' => 3]);
    $riskLevel = seededRiskLevel(4, 3);

    $response = $this->post(route('risk.registers.assess', $riskRegister), [
        'severity_id' => $severity->id,
        'probability_id' => 10, // Invalid - must be 1-5
        'risk_level_id' => $riskLevel->id,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('probability_id');
});

test('register number is auto-generated and unique', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $site = Site::factory()->create();

    // Create first risk register
    $this->post(route('risk.registers.store'), [
        'title' => 'Risk 1',
        'type' => 'hiradc',
        'site_id' => $site->id,
        'activity' => 'Activity 1',
        'hazard' => 'Hazard 1',
        'owner_id' => $admin->id,
    ]);

    // Create second risk register
    $this->post(route('risk.registers.store'), [
        'title' => 'Risk 2',
        'type' => 'jsa',
        'site_id' => $site->id,
        'activity' => 'Activity 2',
        'hazard' => 'Hazard 2',
        'owner_id' => $admin->id,
    ]);

    $registers = RiskRegister::all();
    expect($registers)->toHaveCount(2);
    expect($registers[0]->register_number)->not->toBe($registers[1]->register_number);
    expect($registers[0]->register_number)->toMatch('/^RSK-\d{4}-\d{4}$/');
    expect($registers[1]->register_number)->toMatch('/^RSK-\d{4}-\d{4}$/');
});

test('cannot implement controls without additional_controls field', function (): void {
    $admin = adminUser();
    $this->actingAs($admin);

    $riskRegister = RiskRegister::factory()->create([
        'owner_id' => $admin->id,
        'status' => 'controls_needed',
        'additional_controls' => null, // No controls specified
    ]);

    $response = $this->post(route('risk.registers.implement_controls', $riskRegister));

    $response->assertStatus(302);
    $response->assertSessionHas('error');
});

// Category 2: Permission & Authorization

// === WS-2: cross-site scope enforced via Policy + index query ===

test('QHSSE Officer in site A cannot view risk register of site B (WS-2 policy)', function (): void {
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');
    $employee = \App\Models\Core\Users\Employee::factory()->create();
    $officer->update(['employee_id' => $employee->id]);
    $officer->givePermissionTo(['risk.registers.view', 'core.scope.site']);

    $otherSite = \App\Models\Core\MasterData\Site::factory()->create();
    $otherDept = \App\Models\Core\MasterData\Department::factory()->create(['site_id' => $otherSite->id]);
    $register = RiskRegister::factory()->create([
        'site_id' => $otherSite->id,
        'department_id' => $otherDept->id,
        'owner_id' => $officer->id,
    ]);

    expect($officer->can('view', $register))->toBeFalse();

    $this->actingAs($officer);
    $response = $this->get(route('risk.registers.index'));
    $response->assertStatus(200);
    $response->assertInertia(fn ($p) => $p->has('items')
        ->where('items.data', fn ($data) => collect($data)->every(fn ($r) => $r['site_id'] !== $otherSite->id)));
});

test('QHSSE Officer can view risk register of own site (WS-2 policy sanity)', function (): void {
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');
    $employee = \App\Models\Core\Users\Employee::factory()->create();
    $officer->update(['employee_id' => $employee->id]);
    $officer->givePermissionTo(['risk.registers.view', 'core.scope.site']);

    $register = RiskRegister::factory()->create([
        'site_id' => $employee->site_id,
        'department_id' => $employee->department_id,
        'owner_id' => $officer->id,
    ]);

    expect($officer->can('view', $register))->toBeTrue();
});

test('Super Admin with scope.all sees all risk registers (WS-2 scope.all)', function (): void {
    $admin = adminUser();
    $admin->givePermissionTo('core.scope.all');

    $otherSite = \App\Models\Core\MasterData\Site::factory()->create();
    $otherDept = \App\Models\Core\MasterData\Department::factory()->create(['site_id' => $otherSite->id]);
    RiskRegister::factory()->create([
        'site_id' => $otherSite->id,
        'department_id' => $otherDept->id,
        'owner_id' => $admin->id,
    ]);

    $this->actingAs($admin);
    $response = $this->get(route('risk.registers.index'));
    $response->assertStatus(200);
    $response->assertInertia(fn ($p) => $p->has('items')
        ->where('items.data', fn ($data) => collect($data)->contains(fn ($r) => $r['site_id'] === $otherSite->id)));
});

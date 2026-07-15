<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Contractor\Contractor;
use App\Models\User;
use Database\Factories\Modules\Contractor\ContractorFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        \Database\Seeders\RolesAndPermissionsSeeder::class,
        \Database\Seeders\NumberingFormatSeeder::class,
        \Database\Seeders\NotificationTemplateSeeder::class,
    ]);
});

function contractorUser(string $role, int $siteId): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    // Link to an employee at the given site so scope can resolve site_id
    $employee = \App\Models\Core\Users\Employee::factory()->create([
        'site_id' => $siteId,
    ]);
    $user->employee_id = $employee->id;
    $user->save();

    return $user;
}

it('hides contractors outside the user site scope (WS-4)', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();

    ContractorFactory::new()->create(['authorized_sites' => [$siteA->id], 'contract_status' => 'active']);
    ContractorFactory::new()->create(['authorized_sites' => [$siteB->id], 'contract_status' => 'active']);

    $officerB = contractorUser('QHSSE Officer', $siteB->id);

    // Direct scope query must exclude site-A-only contractor
    $visibleIds = app(\App\Modules\Contractor\ContractorAccess::class)
        ->scope(Contractor::query(), $officerB)
        ->pluck('id')
        ->all();

    $siteAContractor = Contractor::whereJsonContains('authorized_sites', $siteA->id)->first();
    $siteBContractor = Contractor::whereJsonContains('authorized_sites', $siteB->id)->first();

    expect($visibleIds)->toContain($siteBContractor->id)
        ->and($visibleIds)->not->toContain($siteAContractor->id);

    // Index integration: officer B sees only the site-B contractor
    $this->actingAs($officerB);
    $response = $this->get(route('contractors.index'));
    $response->assertInertia(fn ($page) => $page->has('contractors.data', 1));
});

it('lets scope.all users see every contractor on index (WS-4)', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();

    ContractorFactory::new()->create(['authorized_sites' => [$siteA->id], 'contract_status' => 'active']);
    ContractorFactory::new()->create(['authorized_sites' => [$siteB->id], 'contract_status' => 'active']);

    $manager = contractorUser('QHSSE Manager', $siteA->id); // scope.all
    $this->actingAs($manager);

    $response = $this->get(route('contractors.index'));
    $response->assertInertia(fn ($page) => $page->has('contractors.data', 2));
});

it('audits contractor creation (WS-5)', function () {
    $officer = contractorUser('QHSSE Officer', Site::factory()->create()->id);
    $this->actingAs($officer);

    $this->post(route('contractors.store'), [
        'company_name' => 'PT Audit Test',
        'contact_person' => 'John',
        'contact_phone' => '08123',
        'contract_status' => 'blacklisted',
        'approval_status' => 'draft',
        'business_type' => 'consulting',
        'authorized_sites' => [Site::factory()->create()->id],
    ]);

    expect(AuditLog::where('event', 'created')->where('module_name', 'contractor')->exists())->toBeTrue();
});

it('audits status change on update (WS-5)', function () {
    $officer = contractorUser('QHSSE Officer', Site::factory()->create()->id);
    $this->actingAs($officer);

    $contractor = ContractorFactory::new()->create([
        'contract_status' => 'pending',
        'authorized_sites' => [Site::factory()->create()->id],
    ]);

    $this->put(route('contractors.update', $contractor), [
        'company_name' => $contractor->company_name,
        'contact_person' => $contractor->contact_person,
        'contact_phone' => $contractor->contact_phone ?? '08123',
        'contract_status' => 'active',
        'approval_status' => 'approved',
        'business_type' => $contractor->business_type,
        'authorized_sites' => $contractor->authorized_sites,
    ]);

    expect(AuditLog::where('event', 'contractor.status_changed')->exists())->toBeTrue()
        ->and(AuditLog::where('event', 'updated')->where('module_name', 'contractor')->exists())->toBeTrue();
});

it('blocks blacklisted -> active transition for non-admin (WS-5)', function () {
    $officer = contractorUser('QHSSE Officer', Site::factory()->create()->id);
    $this->actingAs($officer);

    $contractor = ContractorFactory::new()->create([
        'contract_status' => 'blacklisted',
        'authorized_sites' => [Site::factory()->create()->id],
    ]);

    $response = $this->put(route('contractors.update', $contractor), [
        'company_name' => $contractor->company_name,
        'contact_person' => $contractor->contact_person,
        'contact_phone' => $contractor->contact_phone ?? '08123',
        'contract_status' => 'active', // blacklisted -> active
        'approval_status' => $contractor->approval_status,
        'business_type' => $contractor->business_type,
        'authorized_sites' => $contractor->authorized_sites,
    ]);

    $response->assertSessionHasErrors('contract_status');
    expect($contractor->fresh()->contract_status)->toBe('blacklisted');
});

it('allows blacklisted -> active transition for admin (WS-5)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $employee = \App\Models\Core\Users\Employee::factory()->create(['site_id' => Site::factory()->create()->id]);
    $admin->employee_id = $employee->id;
    $admin->save();

    $this->actingAs($admin);

    $contractor = ContractorFactory::new()->create([
        'contract_status' => 'blacklisted',
        'authorized_sites' => [Site::factory()->create()->id],
    ]);

    $this->put(route('contractors.update', $contractor), [
        'company_name' => $contractor->company_name,
        'contact_person' => $contractor->contact_person,
        'contact_phone' => $contractor->contact_phone ?? '08123',
        'contract_status' => 'active',
        'approval_status' => $contractor->approval_status,
        'business_type' => $contractor->business_type,
        'authorized_sites' => $contractor->authorized_sites,
    ]);

    expect($contractor->fresh()->contract_status)->toBe('active');
});

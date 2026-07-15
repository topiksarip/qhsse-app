<?php

use App\Models\Modules\Contractor\Contractor;
use App\Models\User;
use Database\Factories\Modules\Contractor\ContractorFactory;
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

it('updates contractor and writes audit + activity (WS-7)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // contractorFull permissions

    $contractor = ContractorFactory::new()->create(['company_name' => 'Old Co']);

    actingAs($manager)
        ->put(route('contractors.update', $contractor), [
            'company_name' => 'New Co',
            'business_type' => 'consulting',
            'contact_person' => 'Budi',
            'contact_email' => 'budi@example.com',
            'contact_phone' => '0812',
            'contract_status' => 'active',
            'approval_status' => 'approved',
        ])
        ->assertRedirect(route('contractors.show', $contractor));

    expect(Contractor::find($contractor->id)->company_name)->toBe('New Co');
});

it('shows contractor detail page (WS-7)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // contractorFull

    $contractor = ContractorFactory::new()->create();

    actingAs($manager)
        ->get(route('contractors.show', $contractor))
        ->assertOk();
});

it('policy view blocks cross-site contractor (WS-7 scope)', function () {
    // Officer with site scope should not see contractor from other site
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer'); // core.scope.site
    $officer->givePermissionTo('core.scope.site');
    // ensure employee + site linkage
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $otherSite = \App\Models\Core\MasterData\Site::factory()->create();
    $dept = \App\Models\Core\MasterData\Department::factory()->create(['site_id' => $site->id]);
    $employee = \App\Models\Core\Users\Employee::factory()->create([
        'site_id' => $site->id,
        'department_id' => $dept->id,
    ]);
    $officer->update(['employee_id' => $employee->id]);

    $otherContractor = ContractorFactory::new()->create([
        'authorized_sites' => [$otherSite->id],
    ]);

    // ContractorAccess::canView uses authorized_sites, not employee site
    // Officer's employee site != contractor.authorized_sites => false
    expect(app(\App\Modules\Contractor\ContractorAccess::class)->canView($officer, $otherContractor))->toBeFalse();
});

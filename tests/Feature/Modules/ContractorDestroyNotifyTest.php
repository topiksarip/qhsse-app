<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Modules\Contractor\Contractor;
use App\Models\User;
use Database\Factories\Modules\Contractor\ContractorFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed([
        \Database\Seeders\RolesAndPermissionsSeeder::class,
        \Database\Seeders\NumberingFormatSeeder::class,
        \Database\Seeders\NotificationTemplateSeeder::class,
    ]);
});

it('blocks contractor deletion for user without delete permission (WS-6)', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no contractor.management.delete
    $contractor = ContractorFactory::new()->create();

    actingAs($user);
    delete(route('contractors.destroy', $contractor))->assertForbidden();

    expect(Contractor::find($contractor->id))->not->toBeNull();
});

it('deletes contractor + writes audit when authorized (WS-6)', function () {
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer'); // has contractor.management.delete via $contractorFull
    $contractor = ContractorFactory::new()->create();

    actingAs($officer);
    delete(route('contractors.destroy', $contractor))->assertRedirect(route('contractors.index'));

    expect(Contractor::find($contractor->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'contractor')->where('reference_id', $contractor->id)->exists())->toBeTrue();
});

it('notifies QHSSE team on contractor registration (WS-2)', function () {
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');

    actingAs($officer);
    post(route('contractors.store'), [
        'company_name' => 'PT Notify Test',
        'contact_person' => 'Jane',
        'contact_phone' => '08123',
        'contract_status' => 'pending',
        'approval_status' => 'draft',
        'business_type' => 'consulting',
        'authorized_sites' => [\App\Models\Core\MasterData\Site::factory()->create()->id],
    ]);

    expect(CoreNotification::where('type', 'contractor.registered')->exists())->toBeTrue();
});

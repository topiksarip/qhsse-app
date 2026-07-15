<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Security\SecurityIncident;
use App\Models\User;
use Database\Factories\Modules\Security\SecurityIncidentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed([
        \Database\Seeders\RolesAndPermissionsSeeder::class,
        \Database\Seeders\NumberingFormatSeeder::class,
        \Database\Seeders\NotificationTemplateSeeder::class,
    ]);
});

it('blocks security incident deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no security.incidents.delete + no scope
    $incident = SecurityIncidentFactory::new()->create(['status' => 'reported']);

    actingAs($user);
    delete(route('security.incidents.destroy', $incident))->assertForbidden();

    expect(SecurityIncident::find($incident->id))->not->toBeNull();
});

it('deletes security incident + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + security.incidents.delete via $securityFull
    $incident = SecurityIncidentFactory::new()->create(['status' => 'reported']);

    actingAs($manager);
    delete(route('security.incidents.destroy', $incident))->assertRedirect(route('security.incidents.index'));

    expect(SecurityIncident::find($incident->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'security')->where('reference_id', $incident->id)->exists())->toBeTrue();
});

<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Database\Factories\Modules\Incident\IncidentReportFactory;
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

it('blocks incident deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no incident.reports.delete + no scope
    $incident = IncidentReportFactory::new()->create();

    actingAs($user);
    delete(route('incident.reports.destroy', $incident))->assertForbidden();

    expect(IncidentReport::find($incident->id))->not->toBeNull();
});

it('deletes incident + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + incident.reports.delete via $incidentFull
    $incident = IncidentReportFactory::new()->create();

    actingAs($manager);
    delete(route('incident.reports.destroy', $incident))->assertRedirect(route('incident.reports.index'));

    expect(IncidentReport::find($incident->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'incident')->where('reference_id', $incident->id)->exists())->toBeTrue();
});

<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Investigation\Investigation;
use App\Models\User;
use Database\Factories\Modules\Investigation\InvestigationFactory;
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

it('blocks investigation deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no investigation.reports.delete + no scope
    $inv = InvestigationFactory::new()->create();

    actingAs($user);
    delete(route('investigation.reports.destroy', $inv))->assertForbidden();

    expect(Investigation::find($inv->id))->not->toBeNull();
});

it('deletes investigation + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + investigation.reports.delete via $investigationFull
    $inv = InvestigationFactory::new()->create();

    actingAs($manager);
    delete(route('investigation.reports.destroy', $inv))->assertRedirect(route('investigation.reports.index'));

    expect(Investigation::find($inv->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'investigation')->where('reference_id', $inv->id)->exists())->toBeTrue();
});

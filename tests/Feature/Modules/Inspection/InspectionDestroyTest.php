<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Inspection\Inspection;
use App\Models\User;
use Database\Factories\Modules\Inspection\InspectionFactory;
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

it('blocks inspection deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no inspection.checklists.delete + no scope
    $inspection = InspectionFactory::new()->create(['status' => 'draft']);

    actingAs($user);
    delete(route('inspection.checklists.destroy', $inspection))->assertForbidden();

    expect(Inspection::find($inspection->id))->not->toBeNull();
});

it('deletes inspection + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + inspection.checklists.delete via $inspectionFull
    $inspection = InspectionFactory::new()->create(['status' => 'draft']);

    actingAs($manager);
    delete(route('inspection.checklists.destroy', $inspection))->assertRedirect(route('inspection.checklists.index'));

    expect(Inspection::find($inspection->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'inspection')->where('reference_id', $inspection->id)->exists())->toBeTrue();
});

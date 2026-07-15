<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Database\Factories\Modules\Capa\CapaActionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed([
        \Database\Seeders\RolesAndPermissionsSeeder::class,
        \Database\Seeders\NumberingFormatSeeder::class,
        \Database\Seeders\NotificationTemplateSeeder::class,
        \Database\Seeders\WorkflowSeeder::class,
    ]);
});

it('blocks CAPA deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no capa.actions.delete + no scope
    $capa = CapaActionFactory::new()->create();

    actingAs($user);
    delete(route('capa.actions.destroy', $capa))->assertForbidden();

    expect(CapaAction::find($capa->id))->not->toBeNull();
});

it('deletes CAPA + writes audit when authorized (WS-6)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + capa.actions.delete via $capaFull
    $capa = CapaActionFactory::new()->create();

    actingAs($manager);
    delete(route('capa.actions.destroy', $capa))->assertRedirect(route('capa.actions.index'));

    expect(CapaAction::find($capa->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'capa')->where('reference_id', $capa->id)->exists())->toBeTrue();
});

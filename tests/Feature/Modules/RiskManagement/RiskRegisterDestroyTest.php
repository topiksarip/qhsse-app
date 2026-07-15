<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\RiskManagement\RiskRegister;
use App\Models\User;
use Database\Factories\Modules\RiskManagement\RiskRegisterFactory;
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

it('blocks risk register deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no risk.registers.delete + no scope
    $risk = RiskRegisterFactory::new()->create();

    actingAs($user);
    delete(route('risk.registers.destroy', $risk))->assertForbidden();

    expect(RiskRegister::find($risk->id))->not->toBeNull();
});

it('deletes risk register + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + risk.registers.delete via $riskFull
    $risk = RiskRegisterFactory::new()->create();

    actingAs($manager);
    delete(route('risk.registers.destroy', $risk))->assertRedirect(route('risk.registers.index'));

    expect(RiskRegister::find($risk->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'risk')->where('reference_id', $risk->id)->exists())->toBeTrue();
});

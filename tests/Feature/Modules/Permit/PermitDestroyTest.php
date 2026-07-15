<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Permit\Permit;
use App\Models\User;
use Database\Factories\Modules\Permit\PermitFactory;
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

it('blocks permit deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no permit.work.delete + no scope
    $permit = PermitFactory::new()->create();

    actingAs($user);
    delete(route('permit.work.destroy', $permit))->assertForbidden();

    expect(Permit::find($permit->id))->not->toBeNull();
});

it('deletes permit + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + permit.work.delete via $permitFull
    $permit = PermitFactory::new()->create();

    actingAs($manager);
    delete(route('permit.work.destroy', $permit))->assertRedirect(route('permit.work.index'));

    expect(Permit::find($permit->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'permit')->where('reference_id', $permit->id)->exists())->toBeTrue();
});

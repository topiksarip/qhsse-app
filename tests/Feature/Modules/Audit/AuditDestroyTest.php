<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Audit\Audit;
use App\Models\User;
use Database\Factories\Modules\Audit\AuditFactory;
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

it('blocks audit deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no audit.management.delete + no scope
    $audit = AuditFactory::new()->create();

    actingAs($user);
    delete(route('audits.destroy', $audit))->assertForbidden();

    expect(Audit::find($audit->id))->not->toBeNull();
});

it('deletes audit + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + audit.management.delete via $auditFull
    $audit = AuditFactory::new()->create();

    actingAs($manager);
    delete(route('audits.destroy', $audit))->assertRedirect(route('audits.index'));

    expect(Audit::find($audit->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'audit')->where('reference_id', $audit->id)->exists())->toBeTrue();
});

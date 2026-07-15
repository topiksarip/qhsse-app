<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Quality\Ncr;
use App\Models\User;
use Database\Factories\Modules\Quality\NcrFactory;
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

it('blocks NCR deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no quality.ncrs.delete + no scope
    $ncr = NcrFactory::new()->create();

    actingAs($user);
    delete(route('quality.ncrs.destroy', $ncr))->assertForbidden();

    expect(Ncr::find($ncr->id))->not->toBeNull();
});

it('deletes NCR + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + quality.ncrs.delete via $qualityFull
    $ncr = NcrFactory::new()->create();

    actingAs($manager);
    delete(route('quality.ncrs.destroy', $ncr))->assertRedirect(route('quality.ncrs.index'));

    expect(Ncr::find($ncr->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'quality')->where('reference_id', $ncr->id)->exists())->toBeTrue();
});

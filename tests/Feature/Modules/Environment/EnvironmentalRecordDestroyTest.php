<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Environment\EnvironmentalRecord;
use App\Models\User;
use Database\Factories\Modules\Environment\EnvironmentalRecordFactory;
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

it('blocks environmental record deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no environment.records.delete + no scope
    $record = EnvironmentalRecordFactory::new()->create();

    actingAs($user);
    delete(route('environment.records.destroy', $record))->assertForbidden();

    expect(EnvironmentalRecord::find($record->id))->not->toBeNull();
});

it('deletes environmental record + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + environment.records.delete via $environmentFull
    $record = EnvironmentalRecordFactory::new()->create();

    actingAs($manager);
    delete(route('environment.records.destroy', $record))->assertRedirect(route('environment.records.index'));

    expect(EnvironmentalRecord::find($record->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'environment')->where('reference_id', $record->id)->exists())->toBeTrue();
});

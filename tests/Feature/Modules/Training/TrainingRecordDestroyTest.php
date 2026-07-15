<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\Training\TrainingRecord;
use App\Models\User;
use Database\Factories\Modules\Training\TrainingRecordFactory;
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

it('blocks training record deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no training.records.delete + no scope
    $record = TrainingRecordFactory::new()->create();

    actingAs($user);
    delete(route('training.records.destroy', $record))->assertForbidden();

    expect(TrainingRecord::find($record->id))->not->toBeNull();
});

it('deletes training record + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + training.records.delete via $trainingFull
    $record = TrainingRecordFactory::new()->create();

    actingAs($manager);
    delete(route('training.records.destroy', $record))->assertRedirect(route('training.records.index'));

    expect(TrainingRecord::find($record->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'training')->where('reference_id', $record->id)->exists())->toBeTrue();
});

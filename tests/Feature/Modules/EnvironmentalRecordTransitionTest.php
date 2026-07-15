<?php

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Position;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Modules\Capa\CapaAction;
use App\Models\Modules\Environment\EnvironmentalRecord;
use App\Models\User;
use Database\Factories\Modules\Environment\EnvironmentalRecordFactory;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, NumberingFormatSeeder::class, NotificationTemplateSeeder::class]);
    Notification::fake();
});

function envActor(string $role, Site $site, Department $department): User
{
    $position = Position::factory()->for($department)->create();
    $employee = \App\Models\Core\Users\Employee::factory()->create([
        'site_id' => $site->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);
    $user = User::factory()->linkedToEmployee($employee)->create();
    $user->assignRole($role);

    return $user;
}

function envRecord(User $reporter, Site $site, array $overrides = []): EnvironmentalRecord
{
    return EnvironmentalRecordFactory::new()->create(array_merge([
        'site_id' => $site->id,
        'reporter_id' => $reporter->id,
        'status' => 'recorded',
    ], $overrides));
}

it('allows QHSSE Officer to investigate a recorded record', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $reporter = envActor('Employee / Reporter', $site, $department);
    $officer = envActor('QHSSE Officer', $site, $department);
    $record = envRecord($reporter, $site);

    $this->actingAs($officer)
        ->post(route('environment.records.investigate', $record))
        ->assertRedirect();

    expect($record->fresh()->status)->toBe('investigated');
});

it('blocks a reporter from investigating (permission gate)', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $reporter = envActor('Employee / Reporter', $site, $department);
    $record = envRecord($reporter, $site);

    $this->actingAs($reporter)
        ->post(route('environment.records.investigate', $record))
        ->assertForbidden();

    expect($record->fresh()->status)->toBe('recorded');
});

it('opens a CAPA when moving investigated -> action_open', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $reporter = envActor('Employee / Reporter', $site, $department);
    $officer = envActor('QHSSE Officer', $site, $department);
    $record = envRecord($reporter, $site, ['status' => 'investigated']);

    $this->actingAs($officer)
        ->post(route('environment.records.open-action', $record))
        ->assertRedirect();

    $record->refresh();
    expect($record->status)->toBe('action_open');
    expect($record->capa_action_id)->not->toBeNull();
    expect(CapaAction::find($record->capa_action_id)->source_module)->toBe('environment');
});

it('requires a reason and closes a record', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $reporter = envActor('Employee / Reporter', $site, $department);
    $officer = envActor('QHSSE Officer', $site, $department);
    $record = envRecord($reporter, $site, ['status' => 'investigated']);

    // Missing reason -> validation error
    $this->actingAs($officer)
        ->post(route('environment.records.close', $record), [])
        ->assertSessionHasErrors('reason');

    // Valid close
    $this->actingAs($officer)
        ->post(route('environment.records.close', $record), ['reason' => 'Telah selesai ditindaklanjuti.'])
        ->assertRedirect();

    expect($record->fresh()->status)->toBe('closed');

    // Reporter should be notified
    expect(CoreNotification::where('reference_id', $record->id)
        ->where('type', 'environment.closed')
        ->where('recipient_id', $reporter->id)
        ->exists())->toBeTrue();
});

it('supports direct close from recorded status', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $reporter = envActor('Employee / Reporter', $site, $department);
    $officer = envActor('QHSSE Officer', $site, $department);
    $record = envRecord($reporter, $site, ['status' => 'recorded']);

    $this->actingAs($officer)
        ->post(route('environment.records.close', $record), ['reason' => 'Tidak ada exceedance, aman.'])
        ->assertRedirect();

    expect($record->fresh()->status)->toBe('closed');
});

it('rejects invalid transition (investigate on investigated record)', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $reporter = envActor('Employee / Reporter', $site, $department);
    $officer = envActor('QHSSE Officer', $site, $department);
    $record = envRecord($reporter, $site, ['status' => 'investigated']);

    $this->actingAs($officer)
        ->post(route('environment.records.investigate', $record))
        ->assertStatus(400);

    expect($record->fresh()->status)->toBe('investigated');
});

it('rejects closing an already closed record', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $reporter = envActor('Employee / Reporter', $site, $department);
    $officer = envActor('QHSSE Officer', $site, $department);
    $record = envRecord($reporter, $site, ['status' => 'closed']);

    $this->actingAs($officer)
        ->post(route('environment.records.close', $record), ['reason' => 'Sudah ditutup sebelumnya.'])
        ->assertStatus(400);
});

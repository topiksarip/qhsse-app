<?php

use App\Core\Permissions\CorePermissions;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Security\PatrolChecklist;
use App\Models\User;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        NumberingFormatSeeder::class,
        NotificationTemplateSeeder::class,
    ]);

    $this->site = Site::factory()->create();
    $this->area = Area::factory()->for($this->site)->create();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
    $officerEmployee = Employee::factory()->forSite($this->site)->create();
    $this->officer = User::factory()->linkedToEmployee($officerEmployee)->create();
    $this->officer->assignRole('Security Officer');
});

function patrolPayload(object $test, array $overrides = []): array
{
    return array_merge([
        'title' => 'Rute Malam Gerbang ke Gudang',
        'description' => 'Patroli keamanan rutin untuk area perimeter.',
        'site_id' => $test->site->id,
        'area_id' => $test->area->id,
        'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
        'assigned_to' => $test->officer->id,
        'notes' => 'Periksa seluruh akses masuk.',
        'checkpoints' => [
            ['checkpoint' => 'Gerbang Utama'],
            ['checkpoint' => 'Gudang Bahan Baku'],
        ],
    ], $overrides);
}

function scheduledPatrol(object $test, int $checkpointCount = 2): PatrolChecklist
{
    $patrol = PatrolChecklist::create([
        'patrol_number' => 'SPL-'.now()->year.'-9001',
        'title' => 'Patroli Uji Workflow',
        'site_id' => $test->site->id,
        'area_id' => $test->area->id,
        'scheduled_at' => now(),
        'assigned_to' => $test->officer->id,
        'status' => 'scheduled',
    ]);

    foreach (range(1, $checkpointCount) as $index) {
        $patrol->results()->create([
            'checkpoint' => "Checkpoint {$index}",
            'result' => null,
        ]);
    }

    return $patrol;
}

test('authorized user creates a scheduled patrol with generated number and checkpoints', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('security.patrols.store'), patrolPayload($this));

    $patrol = PatrolChecklist::query()->firstOrFail();

    $response->assertRedirect(route('security.patrols.show', $patrol));
    expect($patrol->patrol_number)->toMatch('/^SPL-'.now()->year.'-\d{4}$/')
        ->and($patrol->status)->toBe('scheduled')
        ->and($patrol->results()->count())->toBe(2)
        ->and($patrol->results()->whereNull('result')->count())->toBe(2)
        ->and(AuditLog::where('event', 'security.patrol.created')->exists())->toBeTrue()
        ->and(ActivityLog::where('event', 'created')->where('module_name', 'security_patrol')->exists())->toBeTrue();
});

test('user without patrol create permission is blocked by backend middleware', function () {
    $blocked = User::factory()->create();

    $this->actingAs($blocked)
        ->post(route('security.patrols.store'), patrolPayload($this))
        ->assertForbidden();

    expect(PatrolChecklist::query()->count())->toBe(0);
});

test('patrol cannot be assigned to a user without execute permission', function () {
    $unqualified = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('security.patrols.store'), patrolPayload($this, [
            'assigned_to' => $unqualified->id,
        ]))
        ->assertSessionHasErrors('assigned_to');

    expect(PatrolChecklist::query()->count())->toBe(0);
});

test('patrol executes records results and completes with audit notifications', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $patrol = scheduledPatrol($this);

    $this->actingAs($this->admin)
        ->post(route('security.patrols.start', $patrol))
        ->assertRedirect();

    $patrol->refresh();
    expect($patrol->status)->toBe('in_progress')
        ->and($patrol->started_at)->not->toBeNull()
        ->and(CoreNotification::where('recipient_id', $manager->id)
            ->where('type', 'security.patrol.executed')->exists())->toBeTrue();

    $first = $patrol->results()->firstOrFail();
    $second = $patrol->results()->latest('id')->firstOrFail();

    $this->put(route('security.patrols.results.store', [$patrol, $first]), [
        'result' => 'ok',
        'findings' => 'Semua akses aman.',
    ])->assertRedirect();
    $this->put(route('security.patrols.results.store', [$patrol, $second]), [
        'result' => 'issue',
        'findings' => 'Pintu gudang tidak terkunci.',
    ])->assertRedirect();

    expect(CoreNotification::where('recipient_id', $manager->id)
        ->where('type', 'security.patrol.issue_found')->exists())->toBeTrue()
        ->and(AuditLog::where('event', 'security.patrol.result_recorded')->count())->toBe(2);

    $this->post(route('security.patrols.complete', $patrol))->assertRedirect();

    expect($patrol->fresh()->status)->toBe('completed')
        ->and($patrol->fresh()->completed_by)->toBe($this->admin->id)
        ->and(AuditLog::where('event', 'security.patrol.completed')->exists())->toBeTrue();
});

test('issue result requires meaningful findings', function () {
    $patrol = scheduledPatrol($this, 1);
    $patrol->update(['status' => 'in_progress', 'started_at' => now()]);
    $result = $patrol->results()->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route('security.patrols.results.store', [$patrol, $result]), [
            'result' => 'issue',
            'findings' => 'bad',
        ])
        ->assertSessionHasErrors('findings');

    expect($result->fresh()->result)->toBeNull();
});

test('patrol cannot complete while a checkpoint is pending', function () {
    $patrol = scheduledPatrol($this);
    $patrol->update(['status' => 'in_progress', 'started_at' => now()]);
    $first = $patrol->results()->firstOrFail();
    $first->update(['result' => 'ok', 'checked_at' => now()]);

    $this->actingAs($this->admin)
        ->post(route('security.patrols.complete', $patrol))
        ->assertSessionHasErrors('results');

    expect($patrol->fresh()->status)->toBe('in_progress');
});

test('legacy patrol without checkpoints cannot complete', function () {
    $patrol = scheduledPatrol($this, 1);
    $patrol->results()->delete();
    $patrol->update(['status' => 'in_progress', 'started_at' => now()]);

    $this->actingAs($this->admin)
        ->post(route('security.patrols.complete', $patrol))
        ->assertSessionHasErrors('results');

    expect($patrol->fresh()->status)->toBe('in_progress');
});

test('site scoped security officer cannot access patrol from another site', function () {
    $employee = Employee::factory()->forSite($this->site)->create();
    $security = User::factory()->linkedToEmployee($employee)->create();
    $security->assignRole('Security Officer');
    $otherSite = Site::factory()->create();
    $otherPatrol = PatrolChecklist::create([
        'patrol_number' => 'SPL-'.now()->year.'-9002',
        'title' => 'Patroli Site Lain',
        'site_id' => $otherSite->id,
        'area_id' => null,
        'scheduled_at' => now(),
        'assigned_to' => $this->officer->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($security)
        ->get(route('security.patrols.show', $otherPatrol))
        ->assertForbidden();
});

test('authorized user can open patrol index and export scoped csv', function () {
    scheduledPatrol($this, 1);

    $this->actingAs($this->admin)
        ->get(route('security.patrols.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Modules/Security/Patrols/Index')
            ->has('patrols.data', 1));

    $this->get(route('security.patrols.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload();
});

test('patrol navigation permission and routes are registered', function () {
    expect(CorePermissions::all())
        ->toContain('security.patrols.view')
        ->toContain('security.patrols.create')
        ->toContain('security.patrols.execute')
        ->toContain('security.patrols.export')
        ->and($this->officer->can('security.patrols.execute'))->toBeTrue()
        ->and($this->officer->can('security.incidents.close'))->toBeFalse();

    foreach (['index', 'create', 'store', 'show', 'edit', 'update', 'start', 'complete', 'results.store', 'export'] as $routeName) {
        expect(Route::has("security.patrols.{$routeName}"))->toBeTrue();
    }
});

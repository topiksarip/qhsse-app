<?php

use App\Core\Workflow\WorkflowService;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Database\Seeders\QhsseMasterDataSeeder::class);
    $this->seed(\Database\Seeders\NumberingFormatSeeder::class);
    $this->seed(\Database\Seeders\WorkflowSeeder::class);
    $this->seed(\Database\Seeders\NotificationTemplateSeeder::class);
    $this->seed(\Database\Seeders\IncidentReportingSeeder::class);
    $this->seed(\Database\Seeders\InvestigationSeeder::class);
    $this->seed(\Database\Seeders\CapaSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

// === FUNCTIONAL ===

test('authorized user can view CAPA list', function () {
    actingAs($this->admin);
    $this->get(route('capa.actions.index'))->assertStatus(200)->assertInertia(fn ($p) => $p->component('Modules/Capa/Index'));
});

test('authorized user can create CAPA action', function () {
    actingAs($this->admin);
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();
    $pic = User::factory()->create();

    $this->post(route('capa.actions.store'), [
        'title' => 'Corrective action for incident',
        'description' => 'Fix the leaking pipe',
        'source_module' => 'incident',
        'source_type' => 'corrective',
        'site_id' => $site->id,
        'assigned_to' => $pic->id,
        'priority_id' => $priority->id,
    ]);

    $action = CapaAction::first();
    expect($action)->not->toBeNull();
    expect($action->title)->toBe('Corrective action for incident');
    expect($action->status)->toBe('open');
    expect($action->assigned_by)->toBe($this->admin->id);
});

test('CAPA action number is auto-generated', function () {
    actingAs($this->admin);
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();
    $pic = User::factory()->create();

    $this->post(route('capa.actions.store'), [
        'title' => 'Test', 'description' => 'Test',
        'site_id' => $site->id, 'assigned_to' => $pic->id, 'priority_id' => $priority->id,
    ]);

    expect(CapaAction::first()->action_number)->toMatch('/^ACT-\d{4}-\d{4}$/');
});

test('CAPA with missing title fails validation', function () {
    actingAs($this->admin);
    $this->post(route('capa.actions.store'), [
        'description' => 'Test', 'site_id' => 1, 'assigned_to' => 1, 'priority_id' => 1,
    ])->assertSessionHasErrors(['title']);
});

test('open CAPA can be started', function () {
    actingAs($this->admin);
    $action = CapaAction::factory()->create(['status' => 'open']);
    app(WorkflowService::class)->start('capa', $action->id, $this->admin);

    $this->post(route('capa.actions.start', $action))->assertRedirect();
    expect($action->fresh()->status)->toBe('in_progress');
});

test('in_progress CAPA can be submitted for verification', function () {
    actingAs($this->admin);
    $action = CapaAction::factory()->create(['status' => 'in_progress']);
    app(WorkflowService::class)->start('capa', $action->id, $this->admin);
    WorkflowInstance::where('module_name', 'capa')->where('reference_id', $action->id)->update(['current_status' => 'in_progress']);

    $this->post(route('capa.actions.submit_verification', $action))->assertRedirect();
    expect($action->fresh()->status)->toBe('waiting_verification');
});

test('waiting_verification CAPA can be verified and closed with reason', function () {
    actingAs($this->admin);
    $action = CapaAction::factory()->create(['status' => 'waiting_verification']);
    app(WorkflowService::class)->start('capa', $action->id, $this->admin);
    WorkflowInstance::where('module_name', 'capa')->where('reference_id', $action->id)->update(['current_status' => 'waiting_verification']);

    $this->post(route('capa.actions.verify_close', $action), ['reason' => 'Verified and closed'])->assertRedirect();
    expect($action->fresh()->status)->toBe('closed');
    expect($action->fresh()->closed_at)->not->toBeNull();
});

test('waiting_verification CAPA can be rejected with reason', function () {
    actingAs($this->admin);
    $action = CapaAction::factory()->create(['status' => 'waiting_verification']);
    app(WorkflowService::class)->start('capa', $action->id, $this->admin);
    WorkflowInstance::where('module_name', 'capa')->where('reference_id', $action->id)->update(['current_status' => 'waiting_verification']);

    $this->post(route('capa.actions.reject', $action), ['reason' => 'Incomplete'])->assertRedirect();
    expect($action->fresh()->status)->toBe('rejected');
});

// === PERMISSION ===

test('user without capa.actions.view gets 403', function () {
    $user = User::factory()->create();
    actingAs($user);
    $this->get(route('capa.actions.index'))->assertForbidden();
});

test('employee can view but not create CAPA', function () {
    $emp = User::factory()->create();
    $emp->assignRole('Employee / Reporter');
    actingAs($emp);
    $this->get(route('capa.actions.index'))->assertStatus(200);
    $this->get(route('capa.actions.create'))->assertForbidden();
});

test('supervisor cannot verify-close CAPA', function () {
    $sup = User::factory()->create();
    $sup->assignRole('Supervisor');
    actingAs($sup);
    $action = CapaAction::factory()->create(['status' => 'waiting_verification']);
    $this->post(route('capa.actions.verify_close', $action), ['reason' => 'test'])->assertForbidden();
});

test('export blocked without capa.actions.export', function () {
    $user = User::factory()->create();
    actingAs($user);
    $this->get(route('capa.actions.export'))->assertForbidden();
});

// === INTEGRATION ===

test('audit trail records CAPA creation', function () {
    actingAs($this->admin);
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();
    $pic = User::factory()->create();

    $this->post(route('capa.actions.store'), [
        'title' => 'Audit test', 'description' => 'Test',
        'site_id' => $site->id, 'assigned_to' => $pic->id, 'priority_id' => $priority->id,
    ]);

    $action = CapaAction::first();
    expect(AuditLog::where('module_name', 'capa')->where('reference_id', $action->id)->exists())->toBeTrue();
});

test('activity log records CAPA creation', function () {
    actingAs($this->admin);
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();
    $pic = User::factory()->create();

    $this->post(route('capa.actions.store'), [
        'title' => 'Activity test', 'description' => 'Test',
        'site_id' => $site->id, 'assigned_to' => $pic->id, 'priority_id' => $priority->id,
    ]);

    $action = CapaAction::first();
    expect(ActivityLog::where('module_name', 'capa')->where('reference_id', $action->id)->exists())->toBeTrue();
});

test('notification sent to PIC on assignment', function () {
    actingAs($this->admin);
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();
    $pic = User::factory()->create();

    $this->post(route('capa.actions.store'), [
        'title' => 'Notify test', 'description' => 'Test',
        'site_id' => $site->id, 'assigned_to' => $pic->id, 'priority_id' => $priority->id,
    ]);

    $action = CapaAction::first();
    expect(CoreNotification::where('type', 'capa.assigned')->where('reference_id', $action->id)->exists())->toBeTrue();
});

test('overdue CAPA is detected correctly', function () {
    $action = CapaAction::factory()->create([
        'due_date' => now()->subDays(5)->toDateString(),
        'status' => 'in_progress',
    ]);
    expect($action->is_overdue)->toBeTrue();

    $closed = CapaAction::factory()->create([
        'due_date' => now()->subDays(5)->toDateString(),
        'status' => 'closed',
    ]);
    expect($closed->is_overdue)->toBeFalse();
});

// === NEGATIVE ===

test('cannot start non-open CAPA', function () {
    actingAs($this->admin);
    $action = CapaAction::factory()->create(['status' => 'closed']);
    app(WorkflowService::class)->start('capa', $action->id, $this->admin);
    WorkflowInstance::where('module_name', 'capa')->where('reference_id', $action->id)->update(['current_status' => 'closed']);

    $this->post(route('capa.actions.start', $action))->assertSessionHasErrors(['workflow']);
});

test('verify_close without reason fails validation', function () {
    actingAs($this->admin);
    $action = CapaAction::factory()->create(['status' => 'waiting_verification']);
    app(WorkflowService::class)->start('capa', $action->id, $this->admin);
    WorkflowInstance::where('module_name', 'capa')->where('reference_id', $action->id)->update(['current_status' => 'waiting_verification']);

    $this->post(route('capa.actions.verify_close', $action), [])->assertSessionHasErrors(['reason']);
});

test('duplicate action_number cannot occur', function () {
    actingAs($this->admin);
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();
    $pic = User::factory()->create();

    foreach ([1, 2] as $i) {
        $this->post(route('capa.actions.store'), [
            'title' => "Action {$i}", 'description' => "Test {$i}",
            'site_id' => $site->id, 'assigned_to' => $pic->id, 'priority_id' => $priority->id,
        ]);
    }

    $numbers = CapaAction::pluck('action_number')->toArray();
    expect(count($numbers))->toBe(2);
    expect(count(array_unique($numbers)))->toBe(2);
});

// === WS-1 REGRESSION: CapaAccess no longer hardcodes role + no employee required ===

test('admin WITHOUT employee record can start CAPA (WS-1 root cause 403 fixed)', function () {
    // $this->admin has role Admin, no employee linked -> old CapaAccess returned 403
    $action = CapaAction::factory()->create(['status' => 'open']);

    actingAs($this->admin)
        ->post(route('capa.actions.start', $action))
        ->assertRedirect(route('capa.actions.show', $action));

    expect(CapaAction::find($action->id)->status)->toBe('in_progress');
});

test('QHSSE Officer in different site cannot start cross-site CAPA (WS-1 scope)', function () {
    $user = User::factory()->create();
    $user->assignRole('QHSSE Officer');
    $employee = \App\Models\Core\Users\Employee::factory()->create();
    $user->update(['employee_id' => $employee->id]);

    $ownSite = $employee->site_id;
    $otherSite = \App\Models\Core\MasterData\Site::factory()->create();
    $action = CapaAction::factory()->create(['status' => 'open', 'site_id' => $otherSite->id]);

    $user->givePermissionTo('core.scope.site');

    actingAs($user)
        ->post(route('capa.actions.start', $action))
        ->assertForbidden();

    expect(CapaAction::find($action->id)->status)->toBe('open');

    $sameSiteAction = CapaAction::factory()->create(['status' => 'open', 'site_id' => $ownSite]);
    actingAs($user)
        ->post(route('capa.actions.start', $sameSiteAction))
        ->assertRedirect(route('capa.actions.show', $sameSiteAction));
});

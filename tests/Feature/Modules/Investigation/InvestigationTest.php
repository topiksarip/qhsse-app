<?php

use App\Core\Workflow\WorkflowService;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\Investigation\Investigation;
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

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

// === FUNCTIONAL ===

test('authorized user can view investigation list', function () {
    actingAs($this->admin);
    $this->get(route('investigation.reports.index'))->assertStatus(200)->assertInertia(fn ($p) => $p->component('Modules/Investigation/Index'));
});

test('authorized user can create investigation draft', function () {
    actingAs($this->admin);
    $incident = IncidentReport::factory()->create();

    $response = $this->post(route('investigation.reports.store'), [
        'incident_id' => $incident->id,
        'title' => 'Investigasi kecelakaan',
        'action' => 'draft',
    ]);

    $inv = Investigation::first();
    expect($inv)->not->toBeNull();
    expect($inv->title)->toBe('Investigasi kecelakaan');
    expect($inv->status)->toBe('draft');
    expect($inv->investigator_id)->toBe($this->admin->id);
});

test('investigation number is auto-generated on create', function () {
    actingAs($this->admin);
    $incident = IncidentReport::factory()->create();

    $this->post(route('investigation.reports.store'), [
        'incident_id' => $incident->id,
        'title' => 'Test',
        'action' => 'draft',
    ]);

    $inv = Investigation::first();
    expect($inv->investigation_number)->toMatch('/^INV-\d{4}-\d{4}$/');
});

test('investigation with missing title fails validation', function () {
    actingAs($this->admin);
    $incident = IncidentReport::factory()->create();

    $this->post(route('investigation.reports.store'), [
        'incident_id' => $incident->id,
        'action' => 'draft',
    ])->assertSessionHasErrors(['title']);
});

test('investigation with invalid incident_id fails validation', function () {
    actingAs($this->admin);

    $this->post(route('investigation.reports.store'), [
        'incident_id' => 999999,
        'title' => 'Test',
        'action' => 'draft',
    ])->assertSessionHasErrors(['incident_id']);
});

test('draft investigation can be started', function () {
    actingAs($this->admin);
    $inv = Investigation::factory()->create(['status' => 'draft']);
    app(WorkflowService::class)->start('investigation', $inv->id, $this->admin);

    $this->post(route('investigation.reports.start', $inv))->assertRedirect();
    expect($inv->fresh()->status)->toBe('in_progress');
    expect($inv->fresh()->started_at)->not->toBeNull();
});

test('in_progress investigation can be completed with reason', function () {
    actingAs($this->admin);
    $inv = Investigation::factory()->create([
        'status' => 'in_progress',
        'root_cause' => 'Test root cause',
        'recommendations' => 'Test recommendations',
    ]);
    app(WorkflowService::class)->start('investigation', $inv->id, $this->admin);
    WorkflowInstance::where('module_name', 'investigation')->where('reference_id', $inv->id)->update(['current_status' => 'in_progress']);

    $this->post(route('investigation.reports.complete', $inv), ['reason' => 'Selesai'])->assertRedirect();
    expect($inv->fresh()->status)->toBe('completed');
    expect($inv->fresh()->completed_at)->not->toBeNull();
});

test('in_progress investigation can be cancelled with reason', function () {
    actingAs($this->admin);
    $inv = Investigation::factory()->create(['status' => 'in_progress']);
    app(WorkflowService::class)->start('investigation', $inv->id, $this->admin);
    WorkflowInstance::where('module_name', 'investigation')->where('reference_id', $inv->id)->update(['current_status' => 'in_progress']);

    $this->post(route('investigation.reports.cancel', $inv), ['reason' => 'Tidak relevan'])->assertRedirect();
    expect($inv->fresh()->status)->toBe('cancelled');
});

// === PERMISSION ===

test('user without investigation.reports.view gets 403 on list', function () {
    $user = User::factory()->create();
    actingAs($user);
    $this->get(route('investigation.reports.index'))->assertForbidden();
});

test('supervisor can view but not create investigations', function () {
    $sup = User::factory()->create();
    $sup->assignRole('Supervisor');
    actingAs($sup);
    $this->get(route('investigation.reports.index'))->assertStatus(200);
    $this->get(route('investigation.reports.create'))->assertForbidden();
});

test('user without investigation.reports.close cannot complete', function () {
    $sup = User::factory()->create();
    $sup->assignRole('Supervisor');
    actingAs($sup);
    $inv = Investigation::factory()->create(['status' => 'in_progress', 'root_cause' => 'x', 'recommendations' => 'y']);
    $this->post(route('investigation.reports.complete', $inv), ['reason' => 'test'])->assertForbidden();
});

test('export blocked without investigation.reports.export', function () {
    $user = User::factory()->create();
    actingAs($user);
    $this->get(route('investigation.reports.export'))->assertForbidden();
});

// === INTEGRATION ===

test('audit trail records investigation creation', function () {
    actingAs($this->admin);
    $incident = IncidentReport::factory()->create();

    $this->post(route('investigation.reports.store'), [
        'incident_id' => $incident->id,
        'title' => 'Audit test',
        'action' => 'draft',
    ]);

    $inv = Investigation::first();
    expect(AuditLog::where('module_name', 'investigation')->where('reference_id', $inv->id)->exists())->toBeTrue();
});

test('audit trail records status change on start', function () {
    actingAs($this->admin);
    $inv = Investigation::factory()->create(['status' => 'draft']);
    app(WorkflowService::class)->start('investigation', $inv->id, $this->admin);

    $this->post(route('investigation.reports.start', $inv));

    expect(AuditLog::where('module_name', 'investigation')->where('reference_id', $inv->id)->where('event', 'workflow.transitioned')->exists())->toBeTrue();
});

test('activity log records investigation creation', function () {
    actingAs($this->admin);
    $incident = IncidentReport::factory()->create();

    $this->post(route('investigation.reports.store'), [
        'incident_id' => $incident->id,
        'title' => 'Activity test',
        'action' => 'draft',
    ]);

    $inv = Investigation::first();
    expect(ActivityLog::where('module_name', 'investigation')->where('reference_id', $inv->id)->exists())->toBeTrue();
});

test('notification created on investigation start', function () {
    actingAs($this->admin);
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');

    $inv = Investigation::factory()->create(['status' => 'draft']);
    app(WorkflowService::class)->start('investigation', $inv->id, $this->admin);

    $this->post(route('investigation.reports.start', $inv));

    expect(CoreNotification::where('type', 'investigation.started')->where('module_name', 'investigation')->where('reference_id', $inv->id)->exists())->toBeTrue();
});

// === NEGATIVE ===

test('cannot start non-draft investigation', function () {
    actingAs($this->admin);
    $inv = Investigation::factory()->create(['status' => 'completed']);
    app(WorkflowService::class)->start('investigation', $inv->id, $this->admin);
    WorkflowInstance::where('module_name', 'investigation')->where('reference_id', $inv->id)->update(['current_status' => 'completed']);

    $this->post(route('investigation.reports.start', $inv))->assertSessionHasErrors(['workflow']);
    expect($inv->fresh()->status)->toBe('completed');
});

test('complete without reason fails validation', function () {
    actingAs($this->admin);
    $inv = Investigation::factory()->create(['status' => 'in_progress', 'root_cause' => 'x', 'recommendations' => 'y']);
    app(WorkflowService::class)->start('investigation', $inv->id, $this->admin);
    WorkflowInstance::where('module_name', 'investigation')->where('reference_id', $inv->id)->update(['current_status' => 'in_progress']);

    $this->post(route('investigation.reports.complete', $inv), [])->assertSessionHasErrors(['reason']);
});

test('complete fails without root_cause and recommendations', function () {
    actingAs($this->admin);
    $inv = Investigation::factory()->create(['status' => 'in_progress', 'root_cause' => null, 'recommendations' => null]);
    app(WorkflowService::class)->start('investigation', $inv->id, $this->admin);
    WorkflowInstance::where('module_name', 'investigation')->where('reference_id', $inv->id)->update(['current_status' => 'in_progress']);

    $this->post(route('investigation.reports.complete', $inv), ['reason' => 'test'])->assertSessionHasErrors(['workflow']);
});

<?php

use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Core\Workflow\WorkflowService;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Database\Seeders\QhsseMasterDataSeeder::class);
    $this->seed(\Database\Seeders\NumberingFormatSeeder::class);
    $this->seed(\Database\Seeders\WorkflowSeeder::class);
    $this->seed(\Database\Seeders\NotificationTemplateSeeder::class);
    $this->seed(\Database\Seeders\IncidentReportingSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

// === FUNCTIONAL TESTS ===

test('authorized user can view incident list', function () {
    actingAs($this->admin);
    $response = $this->get(route('incident.reports.index'));
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Modules/Incident/Index'));
});

test('authorized user can create incident draft', function () {
    actingAs($this->admin);

    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $severity = \App\Models\Core\MasterData\Severity::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();

    $response = $this->post(route('incident.reports.store'), [
        'title' => 'Kecelakaan di area produksi',
        'category' => 'accident',
        'occurred_at' => '2026-07-11 14:30:00',
        'site_id' => $site->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'description' => 'Pekerja terpeleset di lantai basah',
        'immediate_action' => 'Pertolongan pertama diberikan',
        'action' => 'draft',
    ]);

    $incident = IncidentReport::first();
    expect($incident)->not->toBeNull();
    expect($incident->title)->toBe('Kecelakaan di area produksi');
    expect($incident->status)->toBe('draft');
    expect($incident->reporter_id)->toBe($this->admin->id);
});

test('incident number is auto-generated on create', function () {
    actingAs($this->admin);

    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $severity = \App\Models\Core\MasterData\Severity::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();

    $this->post(route('incident.reports.store'), [
        'title' => 'Test incident',
        'category' => 'incident',
        'occurred_at' => now()->toDateTimeString(),
        'site_id' => $site->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'description' => 'Test description',
        'action' => 'draft',
    ]);

    $incident = IncidentReport::first();
    expect($incident->incident_number)->not->toBeNull();
    expect($incident->incident_number)->toMatch('/^INC-\d{4}-\d{4}$/');
});

test('incident with missing title fails validation', function () {
    actingAs($this->admin);

    $response = $this->post(route('incident.reports.store'), [
        'category' => 'accident',
        'occurred_at' => now()->toDateTimeString(),
        'site_id' => \App\Models\Core\MasterData\Site::factory()->create()->id,
        'severity_id' => \App\Models\Core\MasterData\Severity::factory()->create()->id,
        'priority_id' => \App\Models\Core\MasterData\Priority::factory()->create()->id,
        'description' => 'Test',
        'action' => 'draft',
    ]);

    $response->assertSessionHasErrors(['title']);
    expect(IncidentReport::count())->toBe(0);
});

test('incident with invalid category fails validation', function () {
    actingAs($this->admin);

    $response = $this->post(route('incident.reports.store'), [
        'title' => 'Test',
        'category' => 'invalid_category',
        'occurred_at' => now()->toDateTimeString(),
        'site_id' => \App\Models\Core\MasterData\Site::factory()->create()->id,
        'severity_id' => \App\Models\Core\MasterData\Severity::factory()->create()->id,
        'priority_id' => \App\Models\Core\MasterData\Priority::factory()->create()->id,
        'description' => 'Test',
        'action' => 'draft',
    ]);

    $response->assertSessionHasErrors(['category']);
});

test('draft incident can be submitted', function () {
    actingAs($this->admin);

    $incident = IncidentReport::factory()->create(['status' => 'draft', 'reporter_id' => $this->admin->id]);
    app(WorkflowService::class)->start('incident', $incident->id, $this->admin);

    $response = $this->post(route('incident.reports.submit', $incident));

    $response->assertRedirect();
    expect($incident->fresh()->status)->toBe('submitted');
});

test('submitted incident can be reviewed', function () {
    actingAs($this->admin);

    $incident = IncidentReport::factory()->create(['status' => 'submitted']);
    $instance = app(WorkflowService::class)->start('incident', $incident->id, $this->admin);
    WorkflowInstance::where('module_name', 'incident')->where('reference_id', $incident->id)->update(['current_status' => 'submitted']);

    $response = $this->post(route('incident.reports.review', $incident));

    $response->assertRedirect();
    expect($incident->fresh()->status)->toBe('under_review');
});

test('under review incident can be closed with reason', function () {
    actingAs($this->admin);

    $incident = IncidentReport::factory()->create(['status' => 'under_review']);
    app(WorkflowService::class)->start('incident', $incident->id, $this->admin);
    WorkflowInstance::where('module_name', 'incident')->where('reference_id', $incident->id)->update(['current_status' => 'under_review']);

    $response = $this->post(route('incident.reports.close', $incident), ['reason' => 'Investigasi selesai.']);

    $response->assertRedirect();
    expect($incident->fresh()->status)->toBe('closed');
});

// === PERMISSION TESTS ===

test('user without incident.reports.view gets 403 on list', function () {
    // Create user with no roles at all — truly no permissions
    $user = User::factory()->create();

    actingAs($user);
    $response = $this->get(route('incident.reports.index'));

    $response->assertForbidden();
});

test('user without incident.reports.close cannot close incident', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('Supervisor');

    actingAs($supervisor);
    $incident = IncidentReport::factory()->create(['status' => 'under_review']);

    $response = $this->post(route('incident.reports.close', $incident), ['reason' => 'Test']);

    $response->assertForbidden();
});

test('export blocked without incident.reports.export', function () {
    $reporter = User::factory()->create();
    $reporter->assignRole('Employee / Reporter');

    actingAs($reporter);
    $response = $this->get(route('incident.reports.export'));

    $response->assertForbidden();
});

test('auditor can view but not create incidents', function () {
    $auditor = User::factory()->create();
    $auditor->assignRole('Auditor');

    actingAs($auditor);
    $this->get(route('incident.reports.index'))->assertStatus(200);
    $this->get(route('incident.reports.create'))->assertForbidden();
});

// === INTEGRATION TESTS ===

test('audit trail records incident creation', function () {
    actingAs($this->admin);

    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $severity = \App\Models\Core\MasterData\Severity::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();

    $this->post(route('incident.reports.store'), [
        'title' => 'Audited incident',
        'category' => 'incident',
        'occurred_at' => now()->toDateTimeString(),
        'site_id' => $site->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'description' => 'Test for audit trail',
        'action' => 'draft',
    ]);

    $incident = IncidentReport::first();
    expect(AuditLog::where('module_name', 'incident')->where('reference_id', $incident->id)->exists())->toBeTrue();
});

test('audit trail records status change on submit', function () {
    actingAs($this->admin);

    $incident = IncidentReport::factory()->create(['status' => 'draft']);
    app(WorkflowService::class)->start('incident', $incident->id, $this->admin);

    $this->post(route('incident.reports.submit', $incident));

    expect(AuditLog::where('module_name', 'incident')->where('reference_id', $incident->id)->where('event', 'workflow.transitioned')->exists())->toBeTrue();
});

test('activity log records incident creation', function () {
    actingAs($this->admin);

    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $severity = \App\Models\Core\MasterData\Severity::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();

    $this->post(route('incident.reports.store'), [
        'title' => 'Activity test',
        'category' => 'incident',
        'occurred_at' => now()->toDateTimeString(),
        'site_id' => $site->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'description' => 'Test',
        'action' => 'draft',
    ]);

    $incident = IncidentReport::first();
    expect(ActivityLog::where('module_name', 'incident')->where('reference_id', $incident->id)->exists())->toBeTrue();
});

test('notification created on incident submit', function () {
    actingAs($this->admin);

    // Create QHSSE Officer to receive notification
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');

    $incident = IncidentReport::factory()->create(['status' => 'draft', 'reporter_id' => $this->admin->id]);
    app(WorkflowService::class)->start('incident', $incident->id, $this->admin);

    $this->post(route('incident.reports.submit', $incident));

    expect(CoreNotification::where('type', 'incident.submitted')->where('module_name', 'incident')->where('reference_id', $incident->id)->exists())->toBeTrue();
});

// === NEGATIVE TESTS ===

test('cannot submit non-draft incident', function () {
    actingAs($this->admin);

    $incident = IncidentReport::factory()->create(['status' => 'draft']);
    app(WorkflowService::class)->start('incident', $incident->id, $this->admin);
    // Close it first
    WorkflowInstance::where('module_name', 'incident')->where('reference_id', $incident->id)->update(['current_status' => 'closed']);
    $incident->update(['status' => 'closed']);

    $response = $this->post(route('incident.reports.submit', $incident));

    $response->assertSessionHasErrors(['workflow']);
    expect($incident->fresh()->status)->toBe('closed');
});

test('close without reason fails validation', function () {
    actingAs($this->admin);

    $incident = IncidentReport::factory()->create(['status' => 'under_review']);
    app(WorkflowService::class)->start('incident', $incident->id, $this->admin);
    WorkflowInstance::where('module_name', 'incident')->where('reference_id', $incident->id)->update(['current_status' => 'under_review']);

    $response = $this->post(route('incident.reports.close', $incident), []);

    $response->assertSessionHasErrors(['reason']);
});

test('duplicate incident_number cannot occur via numbering service', function () {
    actingAs($this->admin);

    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $severity = \App\Models\Core\MasterData\Severity::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();

    foreach ([1, 2] as $i) {
        $this->post(route('incident.reports.store'), [
            'title' => "Incident {$i}",
            'category' => 'incident',
            'occurred_at' => now()->toDateTimeString(),
            'site_id' => $site->id,
            'severity_id' => $severity->id,
            'priority_id' => $priority->id,
            'description' => "Test {$i}",
            'action' => 'draft',
        ]);
    }

    $numbers = IncidentReport::pluck('incident_number')->toArray();
    expect(count($numbers))->toBe(2);
    expect(count(array_unique($numbers)))->toBe(2);
});

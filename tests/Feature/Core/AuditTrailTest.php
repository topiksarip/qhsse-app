<?php

use App\Core\Workflow\WorkflowService;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\Workflow\WorkflowDefinition;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

function auditAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    return $user;
}

it('audits selected core model creates and updates', function () {
    $admin = auditAdmin();

    $this->actingAs($admin)->post(route('core.severities.store'), [
        'code' => 'AUDIT_LOW',
        'name' => 'Audit Low',
        'level' => 1,
        'color' => 'green',
        'description' => 'Initial description',
        'is_active' => true,
    ])->assertRedirect(route('core.severities.index'));

    $severity = Severity::where('code', 'AUDIT_LOW')->firstOrFail();

    $this->actingAs($admin)->put(route('core.severities.update', $severity), [
        'code' => 'AUDIT_LOW',
        'name' => 'Audit Low Updated',
        'level' => 2,
        'color' => 'yellow',
        'description' => 'Updated description',
        'is_active' => true,
    ])->assertRedirect(route('core.severities.index'));

    expect(AuditLog::where('event', 'created')->where('auditable_type', Severity::class)->where('auditable_id', $severity->id)->exists())->toBeTrue()
        ->and(AuditLog::where('event', 'updated')->where('auditable_type', Severity::class)->where('auditable_id', $severity->id)->exists())->toBeTrue();

    $updateLog = AuditLog::where('event', 'updated')->where('auditable_type', Severity::class)->where('auditable_id', $severity->id)->latest()->firstOrFail();

    expect($updateLog->old_values['name'])->toBe('Audit Low')
        ->and($updateLog->new_values['name'])->toBe('Audit Low Updated')
        ->and($updateLog->actor_id)->toBe($admin->id);
});

it('audits workflow transitions', function () {
    $this->seed(WorkflowSeeder::class);
    $admin = auditAdmin();
    $service = app(WorkflowService::class);

    $service->start('incident', 2001, $admin);
    $service->transition('incident', 2001, 'submit', $admin);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'workflow.transitioned',
        'module_name' => 'incident',
        'reference_id' => 2001,
        'actor_id' => $admin->id,
    ]);
});

it('audits permission and workflow definition changes', function () {
    $admin = auditAdmin();

    $this->actingAs($admin)->post(route('core.workflow.run'), [
        'module_name' => 'incident',
        'reference_id' => 2002,
    ])->assertNotFound();

    WorkflowDefinition::factory()->create([
        'module_name' => 'core.audit-test',
        'code' => 'CORE_AUDIT_TEST',
        'name' => 'Core Audit Test Workflow',
    ]);

    expect(AuditLog::where('event', 'created')->where('auditable_type', WorkflowDefinition::class)->exists())->toBeTrue();
});

it('allows authorized users to view audit logs and blocks unauthorized users', function () {
    $admin = auditAdmin();
    $plainUser = User::factory()->create();

    Severity::factory()->create(['code' => 'VIEW_AUDIT']);
    $log = AuditLog::latest()->firstOrFail();

    $this->actingAs($admin)->get(route('core.audit-logs.index'))->assertOk();
    $this->actingAs($admin)->get(route('core.audit-logs.show', $log))->assertOk();
    $this->actingAs($plainUser)->get(route('core.audit-logs.index'))->assertForbidden();
});

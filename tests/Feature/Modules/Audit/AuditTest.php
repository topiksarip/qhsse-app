<?php

use App\Core\Workflow\WorkflowService;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Modules\Audit\Audit;
use App\Models\Modules\Audit\AuditFinding;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(NumberingFormatSeeder::class);
    $this->seed(WorkflowSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->site = Site::factory()->create(['name' => 'Main Site']);
    $this->department = Department::factory()->create([
        'site_id' => $this->site->id,
        'name' => 'Production',
    ]);

    $this->employee = Employee::factory()->create([
        'department_id' => $this->department->id,
        'site_id' => $this->site->id,
    ]);
    $this->admin->update(['employee_id' => $this->employee->id]);
});

function startAuditWorkflow(Audit $audit, User $actor): void
{
    app(WorkflowService::class)->start('audit', $audit->id, $actor);
}

function transitionAuditTo(Audit $audit, string $status): void
{
    WorkflowInstance::query()
        ->where('module_name', 'audit')
        ->where('reference_id', $audit->id)
        ->update(['current_status' => $status]);
}

test('authorized user can view audit register', function () {
    actingAs($this->admin);

    $this->get(route('audits.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Modules/Audit/Index')
            ->has('audits')
            ->has('filters')
        );
});

test('authorized user can create numbered audit with atomic numbering', function () {
    actingAs($this->admin);

    $this->post(route('audits.store'), [
        'title' => 'ISO 45001 Internal Audit',
        'audit_type' => 'internal',
        'scope' => 'Production department safety management',
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'scheduled_date' => now()->addWeek()->toDateString(),
    ])->assertRedirect();

    $audit = Audit::query()->firstOrFail();
    expect($audit->audit_number)->toMatch('/^AUD-\d{4}-\d{5}$/')
        ->and($audit->title)->toBe('ISO 45001 Internal Audit')
        ->and($audit->status)->toBe('planned')
        ->and($audit->lead_auditor_id)->toBe($this->admin->id)
        ->and($audit->created_by)->toBe($this->admin->id);
});

test('audit workflow transitions: planned → in_progress → report_ready → closed', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'created_by' => $this->admin->id,
        'status' => 'planned',
    ]);

    startAuditWorkflow($audit, $this->admin);

    // planned → in_progress
    $this->post(route('audits.start', $audit))
        ->assertRedirect();
    expect($audit->fresh()->status)->toBe('in_progress')
        ->and($audit->fresh()->start_date)->not->toBeNull();

    // in_progress → report_ready
    $this->post(route('audits.generate-report', $audit), [
        'summary' => 'Audit completed with 2 findings',
    ])->assertRedirect();
    expect($audit->fresh()->status)->toBe('report_ready')
        ->and($audit->fresh()->summary)->toContain('2 findings')
        ->and($audit->fresh()->report_date)->not->toBeNull();

    // report_ready → closed
    $this->post(route('audits.close', $audit))
        ->assertRedirect();
    expect($audit->fresh()->status)->toBe('closed')
        ->and($audit->fresh()->close_date)->not->toBeNull();
});

test('cannot start audit that is not planned', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'created_by' => $this->admin->id,
        'status' => 'in_progress',
    ]);

    $this->post(route('audits.start', $audit))
        ->assertForbidden();
});

test('cannot generate report for audit that is not in progress', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'created_by' => $this->admin->id,
        'status' => 'planned',
    ]);

    $this->post(route('audits.generate-report', $audit), [
        'summary' => 'Test summary',
    ])->assertForbidden();
});

test('cannot close audit that is not report ready', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'created_by' => $this->admin->id,
        'status' => 'in_progress',
    ]);

    $this->post(route('audits.close', $audit))
        ->assertForbidden();
});

test('can create finding with atomic finding number', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'audit_number' => 'AUD-2026-00001',
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'created_by' => $this->admin->id,
        'status' => 'in_progress',
    ]);

    $this->post(route('audits.findings.store', $audit), [
        'classification' => 'major_nc',
        'description' => 'Missing safety signage in hazardous area',
        'recommendation' => 'Install proper safety signs',
        'due_date' => now()->addMonth()->toDateString(),
    ])->assertRedirect();

    $finding = AuditFinding::query()->firstOrFail();
    expect($finding->finding_number)->toBe('AUD-2026-00001-F01')
        ->and($finding->classification)->toBe('major_nc')
        ->and($finding->status)->toBe('open')
        ->and($finding->audit_id)->toBe($audit->id);
});

test('finding numbers increment sequentially per audit', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'audit_number' => 'AUD-2026-00001',
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'created_by' => $this->admin->id,
        'status' => 'in_progress',
    ]);

    $this->post(route('audits.findings.store', $audit), [
        'classification' => 'major_nc',
        'description' => 'Finding 1',
    ]);

    $this->post(route('audits.findings.store', $audit), [
        'classification' => 'minor_nc',
        'description' => 'Finding 2',
    ]);

    $this->post(route('audits.findings.store', $audit), [
        'classification' => 'observation',
        'description' => 'Finding 3',
    ]);

    $findings = AuditFinding::query()->orderBy('id')->get();
    expect($findings)->toHaveCount(3)
        ->and($findings[0]->finding_number)->toBe('AUD-2026-00001-F01')
        ->and($findings[1]->finding_number)->toBe('AUD-2026-00001-F02')
        ->and($findings[2]->finding_number)->toBe('AUD-2026-00001-F03');
});

test('can link finding to existing CAPA action', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'created_by' => $this->admin->id,
        'status' => 'in_progress',
    ]);

    $capaAction = CapaAction::factory()->create([
        'title' => 'Install safety signage',
        'status' => 'open',
    ]);

    $this->post(route('audits.findings.store', $audit), [
        'classification' => 'major_nc',
        'description' => 'Missing safety signage',
        'capa_action_id' => $capaAction->id,
    ])->assertRedirect();

    $finding = AuditFinding::query()->firstOrFail();
    expect($finding->capa_action_id)->toBe($capaAction->id)
        ->and($finding->capaAction->title)->toBe('Install safety signage');
});

test('can close finding with closed date and closed by', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'created_by' => $this->admin->id,
        'status' => 'in_progress',
    ]);

    $finding = AuditFinding::factory()->create([
        'audit_id' => $audit->id,
        'status' => 'open',
    ]);

    $this->post(route('audits.findings.close', [$audit, $finding]))
        ->assertRedirect();

    expect($finding->fresh()->status)->toBe('closed')
        ->and($finding->fresh()->closed_date)->not->toBeNull()
        ->and($finding->fresh()->closed_by)->toBe($this->admin->id);
});

test('cannot close already closed finding', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'created_by' => $this->admin->id,
    ]);

    $finding = AuditFinding::factory()->create([
        'audit_id' => $audit->id,
        'status' => 'closed',
        'closed_by' => $this->admin->id,
        'closed_date' => now(),
    ]);

    $this->post(route('audits.findings.close', [$audit, $finding]))
        ->assertForbidden();
});

test('QHSSE Manager can view all audits regardless of department', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $empManager = Employee::factory()->create([
        'department_id' => $this->department->id,
        'site_id' => $this->site->id,
    ]);
    $manager->update(['employee_id' => $empManager->id]);

    $otherDept = Department::factory()->create(['site_id' => $this->site->id]);
    $audit1 = Audit::factory()->create(['department_id' => $this->department->id]);
    $audit2 = Audit::factory()->create(['department_id' => $otherDept->id]);

    actingAs($manager);
    $response = $this->get(route('audits.index'));

    $response->assertOk();
    expect($response->viewData('audits')->pluck('id'))
        ->toContain($audit1->id)
        ->toContain($audit2->id);
});

test('QHSSE Officer can only view audits in same site', function () {
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');
    $empOfficer = Employee::factory()->create([
        'department_id' => $this->department->id,
        'site_id' => $this->site->id,
    ]);
    $officer->update(['employee_id' => $empOfficer->id]);

    $otherSite = Site::factory()->create();
    $otherDept = Department::factory()->create(['site_id' => $otherSite->id]);

    $auditSameSite = Audit::factory()->create(['department_id' => $this->department->id]);
    $auditOtherSite = Audit::factory()->create(['department_id' => $otherDept->id]);

    actingAs($officer);
    $response = $this->get(route('audits.index'));

    $response->assertOk();
    $audits = $response->viewData('audits');
    expect($audits->pluck('id'))
        ->toContain($auditSameSite->id)
        ->not->toContain($auditOtherSite->id);
});

test('Supervisor can only view audits in same department', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('Supervisor');
    $empSuper = Employee::factory()->create([
        'department_id' => $this->department->id,
        'site_id' => $this->site->id,
    ]);
    $supervisor->update(['employee_id' => $empSuper->id]);

    $otherDept = Department::factory()->create(['site_id' => $this->site->id]);
    $auditSameDept = Audit::factory()->create(['department_id' => $this->department->id]);
    $auditOtherDept = Audit::factory()->create(['department_id' => $otherDept->id]);

    actingAs($supervisor);
    $response = $this->get(route('audits.index'));

    $response->assertOk();
    $audits = $response->viewData('audits');
    expect($audits->pluck('id'))
        ->toContain($auditSameDept->id)
        ->not->toContain($auditOtherDept->id);
});

test('Employee can only view audits they created', function () {
    $employee = User::factory()->create();
    $employee->assignRole('Employee / Reporter');
    $empRecord = Employee::factory()->create([
        'department_id' => $this->department->id,
        'site_id' => $this->site->id,
    ]);
    $employee->update(['employee_id' => $empRecord->id]);

    $ownAudit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'created_by' => $employee->id,
    ]);
    $otherAudit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    actingAs($employee);
    $response = $this->get(route('audits.index'));

    $response->assertOk();
    $audits = $response->viewData('audits');
    expect($audits->pluck('id'))
        ->toContain($ownAudit->id)
        ->not->toContain($otherAudit->id);
});

test('user without audit permission cannot view audit register', function () {
    $contractor = User::factory()->create();
    $contractor->assignRole('Contractor');

    actingAs($contractor);
    $this->get(route('audits.index'))
        ->assertForbidden();
});

test('user without create permission cannot create audit', function () {
    $auditor = User::factory()->create();
    $auditor->assignRole('Auditor');

    actingAs($auditor);
    $this->post(route('audits.store'), [
        'title' => 'Test Audit',
        'audit_type' => 'internal',
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'scheduled_date' => now()->addWeek()->toDateString(),
    ])->assertForbidden();
});

test('required fields validation', function () {
    actingAs($this->admin);

    $this->post(route('audits.store'), [])
        ->assertSessionHasErrors(['title', 'audit_type', 'lead_auditor_id', 'scheduled_date']);
});

test('invalid audit type validation', function () {
    actingAs($this->admin);

    $this->post(route('audits.store'), [
        'title' => 'Test',
        'audit_type' => 'invalid_type',
        'lead_auditor_id' => $this->admin->id,
        'scheduled_date' => now()->toDateString(),
    ])->assertSessionHasErrors(['audit_type']);
});

test('invalid finding classification validation', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'status' => 'in_progress',
    ]);

    $this->post(route('audits.findings.store', $audit), [
        'classification' => 'invalid_class',
        'description' => 'Test finding',
    ])->assertSessionHasErrors(['classification']);
});

test('can upload evidence file to audit', function () {
    Storage::fake('local');
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'created_by' => $this->admin->id,
    ]);

    $file = UploadedFile::fake()->create('audit-evidence.pdf', 512, 'application/pdf');

    $this->post(route('audits.show', $audit), [
        'file' => $file,
    ]);

    $managedFile = ManagedFile::query()
        ->where('module_name', 'audit')
        ->where('reference_id', $audit->id)
        ->first();

    expect($managedFile)->not->toBeNull()
        ->and($managedFile->original_name)->toBe('audit-evidence.pdf');
    Storage::disk('local')->assertExists($managedFile->path);
});

test('audit creation records audit trail', function () {
    actingAs($this->admin);

    $this->post(route('audits.store'), [
        'title' => 'Test Audit',
        'audit_type' => 'internal',
        'department_id' => $this->department->id,
        'lead_auditor_id' => $this->admin->id,
        'scheduled_date' => now()->addWeek()->toDateString(),
    ]);

    $audit = Audit::query()->firstOrFail();
    $auditLog = AuditLog::query()
        ->where('module_name', 'audit')
        ->where('reference_id', $audit->id)
        ->where('event', 'audit.created')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($this->admin->id);
});

test('can export audits to CSV', function () {
    actingAs($this->admin);

    Audit::factory()->count(3)->create([
        'department_id' => $this->department->id,
    ]);

    $response = $this->get(route('audits.export'));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertHeader('content-disposition', 'attachment; filename=audits-export-*.csv');
});

test('can filter audits by status', function () {
    actingAs($this->admin);

    $planned = Audit::factory()->create([
        'department_id' => $this->department->id,
        'status' => 'planned',
    ]);
    $inProgress = Audit::factory()->create([
        'department_id' => $this->department->id,
        'status' => 'in_progress',
    ]);

    $response = $this->get(route('audits.index', ['status' => 'planned']));

    $audits = $response->viewData('audits');
    expect($audits->pluck('id'))
        ->toContain($planned->id)
        ->not->toContain($inProgress->id);
});

test('can filter audits by audit type', function () {
    actingAs($this->admin);

    $internal = Audit::factory()->create([
        'department_id' => $this->department->id,
        'audit_type' => 'internal',
    ]);
    $external = Audit::factory()->create([
        'department_id' => $this->department->id,
        'audit_type' => 'external',
    ]);

    $response = $this->get(route('audits.index', ['audit_type' => 'internal']));

    $audits = $response->viewData('audits');
    expect($audits->pluck('id'))
        ->toContain($internal->id)
        ->not->toContain($external->id);
});

test('can add comment to audit', function () {
    actingAs($this->admin);

    $audit = Audit::factory()->create([
        'department_id' => $this->department->id,
    ]);

    $this->post(route('audits.comment', $audit), [
        'content' => 'This is a test comment',
    ])->assertRedirect();

    expect($audit->comments()->count())->toBe(1)
        ->and($audit->comments()->first()->content)->toContain('test comment');
});


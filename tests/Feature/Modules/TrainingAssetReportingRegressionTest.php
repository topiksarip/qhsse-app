<?php

use App\Core\Activity\ActivityService;
use App\Jobs\Modules\Reporting\GenerateReportJob;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Audit\Audit;
use App\Models\Modules\Capa\CapaAction;
use App\Models\Modules\Inspection\Inspection;
use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\Modules\Reporting\SavedReport;
use App\Models\Modules\Training\TrainingProgram;
use App\Models\Modules\Training\TrainingRecord;
use App\Models\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed();
    $this->admin = User::query()->where('email', 'test@example.com')->firstOrFail();
});

it('uses the Laravel paginator contract on the asset index page', function () {
    $page = file_get_contents(resource_path('js/Pages/Modules/Asset/Index.tsx'));

    expect($page)
        ->toContain('from: number | null')
        ->toContain('assets.from')
        ->toContain('assets.to')
        ->toContain('assets.total')
        ->not->toContain('assets.meta.');
});

it('provides the complete runtime contract for the training records page', function () {
    $employee = Employee::factory()->create();
    $program = TrainingProgram::factory()->create();
    $record = TrainingRecord::factory()->create([
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('training.records.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Modules/Training/Records/Index')
            ->has('can')
            ->where('can.create', true)
            ->where('can.update', true)
            ->where('can.view', true)
            ->where('records.data.0.id', $record->id)
            ->where('records.data.0.employee.employee_no', $employee->employee_no)
            ->where('records.data.0.training_program.id', $program->id)
        );
});

it('provides employees and a keyed record matrix for the training matrix page', function () {
    $employee = Employee::factory()->create();
    $program = TrainingProgram::factory()->create(['is_active' => true]);
    $record = TrainingRecord::factory()->create([
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
        'status' => 'completed',
    ]);

    $this->actingAs($this->admin)
        ->get(route('training.matrix.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Modules/Training/Matrix/Index')
            ->where('employees.0.id', $employee->id)
            ->where("matrix.emp_{$employee->id}.prog_{$program->id}.id", $record->id)
            ->has('sites')
            ->has('departments')
        );
});

it('stores a saved report, records activity, and queues generation', function () {
    Queue::fake();
    $template = ReportTemplate::query()->where('type', 'training_compliance')->firstOrFail();

    $response = $this->actingAs($this->admin)->post(route('saved-reports.store'), [
        'template_id' => $template->id,
        'name' => 'Kepatuhan Training Regression',
        'date_from' => now()->subWeek()->toDateString(),
        'date_to' => now()->toDateString(),
        'format' => 'pdf',
        'include_charts' => true,
    ]);

    $report = SavedReport::query()->sole();

    $response->assertRedirect(route('saved-reports.show', $report));
    expect($report->status)->toBe('pending');
    expect(ActivityLog::query()
        ->where('module_name', 'reporting')
        ->where('reference_id', $report->id)
        ->where('event', 'report.generated')
        ->exists())->toBeTrue();
    Queue::assertPushed(GenerateReportJob::class, fn (GenerateReportJob $job) => $job->report->is($report));
});

it('marks a committed report as failed when queue dispatch fails', function () {
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->andThrow(new RuntimeException('queue unavailable'));
    app()->instance(Dispatcher::class, $dispatcher);
    $template = ReportTemplate::query()->where('type', 'training_compliance')->firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('saved-reports.store'), scopedReportPayload($template))
        ->assertSessionHas('error');

    $report = SavedReport::query()->sole();
    expect($report->status)->toBe('failed')
        ->and($report->error_message)->toBe('Report generation could not be queued.');
});

it('neutralizes spreadsheet formulas in csv and excel artifacts', function () {
    Storage::fake();
    foreach (['=2+2', '+2+2', '-2+2', '@SUM(1)'] as $title) {
        CapaAction::factory()->create(['title' => $title, 'created_at' => now()]);
    }
    $template = ReportTemplate::query()->where('type', 'capa_summary')->firstOrFail();

    $csvReport = createScopedReport($template, $this->admin, []);
    $csvReport->update(['status' => 'pending']);
    (new GenerateReportJob($csvReport, $this->admin))->handle();
    $csv = Storage::get($csvReport->fresh()->file_path);

    foreach (["'=2+2", "'+2+2", "'-2+2", "'@SUM(1)"] as $safeValue) {
        expect($csv)->toContain($safeValue);
    }

    $excelReport = createScopedReport($template, $this->admin, []);
    $excelReport->update(['status' => 'pending', 'format' => 'excel']);
    (new GenerateReportJob($excelReport, $this->admin))->handle();
    $zip = new ZipArchive;
    expect($zip->open(Storage::path($excelReport->fresh()->file_path)))->toBeTrue();
    $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    foreach (['&apos;=2+2', '&apos;+2+2', '&apos;-2+2', '&apos;@SUM(1)'] as $safeValue) {
        expect($worksheet)->toContain($safeValue);
    }
});

it('generates a report file and sends a completion notification', function () {
    Storage::fake();
    $template = ReportTemplate::query()->where('type', 'training_compliance')->firstOrFail();
    $report = SavedReport::query()->create([
        'name' => 'Kepatuhan Training Job Regression',
        'template_id' => $template->id,
        'status' => 'pending',
        'parameters' => [
            'date_from' => now()->subWeek()->toDateString(),
            'date_to' => now()->toDateString(),
            'site_id' => null,
            'department_id' => null,
            'include_charts' => true,
        ],
        'format' => 'pdf',
        'generated_by' => $this->admin->id,
        'generated_at' => now(),
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    (new GenerateReportJob($report, $this->admin))->handle();
    $report->refresh();

    expect($report->status)->toBe('completed')
        ->and($report->file_path)->not->toBeNull();
    Storage::assertExists($report->file_path);
    $this->assertDatabaseHas('core_notifications', [
        'recipient_id' => $this->admin->id,
        'type' => 'report.completed',
        'reference_id' => $report->id,
    ]);
});

it('generates valid artifacts for every predefined template and format', function () {
    Storage::fake();

    $templates = ReportTemplate::query()->predefined()->orderBy('type')->get();
    expect($templates)->toHaveCount(7);

    foreach ($templates as $template) {
        foreach (['csv', 'pdf', 'excel'] as $format) {
            $report = SavedReport::query()->create([
                'name' => "{$template->type} {$format}",
                'template_id' => $template->id,
                'status' => 'pending',
                'parameters' => [
                    'date_from' => now()->subWeek()->toDateString(),
                    'date_to' => now()->toDateString(),
                    'site_id' => null,
                    'department_id' => null,
                    'include_charts' => true,
                ],
                'format' => $format,
                'generated_by' => $this->admin->id,
                'generated_at' => now(),
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);

            (new GenerateReportJob($report, $this->admin))->handle();
            $report->refresh();

            expect($report->status)
                ->toBe('completed', "{$template->type} {$format}: {$report->error_message}")
                ->and($report->canDownload())->toBeTrue();

            $content = Storage::get($report->file_path);
            expect($content)->not->toContain('not implemented')->not->toContain('placeholder');

            if ($format === 'pdf') {
                expect($content)->toStartWith('%PDF-');
            } elseif ($format === 'excel') {
                expect($content)->toStartWith('PK');
            } else {
                expect($content)->toContain('REPORT');
            }
        }
    }
});

it('enforces site scope when listing, configuring, and generating reports', function () {
    Queue::fake();
    $site = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $employee = Employee::factory()->create([
        'site_id' => $site->id,
        'department_id' => $department->id,
    ]);
    $officer = User::factory()->linkedToEmployee($employee)->create();
    $officer->assignRole('QHSSE Officer');
    $template = ReportTemplate::query()->where('type', 'incident_summary')->firstOrFail();
    $visible = createScopedReport($template, $this->admin, ['site_id' => $site->id]);
    $hidden = createScopedReport($template, $this->admin, ['site_id' => $otherSite->id]);

    $this->actingAs($officer)->get(route('saved-reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('reports.data.0.id', $visible->id)
            ->missing('reports.data.1'));
    $this->get(route('saved-reports.show', $hidden))->assertForbidden();
    $this->get(route('saved-reports.create'))->assertInertia(fn (Assert $page) => $page
        ->has('sites', 1)
        ->where('sites.0.id', $site->id));

    $this->post(route('saved-reports.store'), scopedReportPayload($template))
        ->assertRedirect();
    expect(SavedReport::query()->where('generated_by', $officer->id)->sole()->parameters)
        ->site_id->toBe($site->id);

    $this->post(route('saved-reports.store'), scopedReportPayload($template, ['site_id' => $otherSite->id]))
        ->assertForbidden();
    expect(SavedReport::query()->count())->toBe(3);
});

it('enforces department scope when listing, configuring, and generating reports', function () {
    Queue::fake();
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $otherDepartment = Department::factory()->for($site)->create();
    $employee = Employee::factory()->create([
        'site_id' => $site->id,
        'department_id' => $department->id,
    ]);
    $supervisor = User::factory()->linkedToEmployee($employee)->create();
    $supervisor->assignRole('Supervisor');
    $template = ReportTemplate::query()->where('type', 'training_compliance')->firstOrFail();
    $visible = createScopedReport($template, $this->admin, ['department_id' => $department->id]);
    $hidden = createScopedReport($template, $this->admin, ['department_id' => $otherDepartment->id]);

    $this->actingAs($supervisor)->get(route('saved-reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('reports.data.0.id', $visible->id)
            ->missing('reports.data.1'));
    $this->get(route('saved-reports.show', $hidden))->assertForbidden();
    $this->get(route('saved-reports.create'))->assertInertia(fn (Assert $page) => $page
        ->has('departments', 1)
        ->where('departments.0.id', $department->id));

    $this->post(route('saved-reports.store'), scopedReportPayload($template))
        ->assertRedirect();
    $parameters = SavedReport::query()->where('generated_by', $supervisor->id)->sole()->parameters;
    expect($parameters)
        ->site_id->toBe($site->id)
        ->department_id->toBe($department->id);

    $this->post(route('saved-reports.store'), scopedReportPayload($template, ['department_id' => $otherDepartment->id]))
        ->assertForbidden();
    expect(SavedReport::query()->count())->toBe(3);
});

it('keeps report deletion inside a manager customized organization scope', function () {
    $site = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $employee = Employee::factory()->create(['site_id' => $site->id]);
    $manager = User::factory()->linkedToEmployee($employee)->create();
    $manager->assignRole('QHSSE Manager');
    Role::findByName('QHSSE Manager')->revokePermissionTo('core.scope.all');
    $manager->givePermissionTo('core.scope.site');
    $manager->forgetCachedPermissions();

    $template = ReportTemplate::query()->where('type', 'incident_summary')->firstOrFail();
    $hidden = createScopedReport($template, $this->admin, ['site_id' => $otherSite->id]);

    $this->actingAs($manager)
        ->delete(route('saved-reports.destroy', $hidden))
        ->assertForbidden();

    expect($hidden->fresh())->not->toBeNull();
});

it('preserves report metadata and private artifact when delete activity fails', function () {
    Storage::fake();
    $template = ReportTemplate::query()->where('type', 'incident_summary')->firstOrFail();
    $report = createScopedReport($template, $this->admin, []);
    $path = "reports/{$report->id}/evidence.csv";
    Storage::put($path, 'private report');
    $report->update(['file_path' => $path, 'file_size' => 14]);
    $this->mock(ActivityService::class)
        ->shouldReceive('log')
        ->once()
        ->andThrow(new RuntimeException('activity unavailable'));

    $this->actingAs($this->admin)
        ->delete(route('saved-reports.destroy', $report))
        ->assertSessionHas('error');

    expect($report->fresh())->not->toBeNull();
    Storage::assertExists($path);
});

it('keeps audit and inspection artifacts inside their requested organization scope', function () {
    Storage::fake();
    $site = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $otherDepartment = Department::factory()->for($otherSite)->create();
    $employee = Employee::factory()->create(['site_id' => $site->id, 'department_id' => $department->id]);
    $otherEmployee = Employee::factory()->create(['site_id' => $otherSite->id, 'department_id' => $otherDepartment->id]);
    $inspector = User::factory()->linkedToEmployee($employee)->create();
    $otherInspector = User::factory()->linkedToEmployee($otherEmployee)->create();

    Inspection::factory()->create([
        'inspection_number' => 'INS-VISIBLE-SCOPE',
        'site_id' => $site->id,
        'inspector_id' => $inspector->id,
        'scheduled_at' => today(),
    ]);
    Inspection::factory()->create([
        'inspection_number' => 'INS-HIDDEN-SCOPE',
        'site_id' => $otherSite->id,
        'inspector_id' => $otherInspector->id,
        'scheduled_at' => today(),
    ]);
    Audit::factory()->create([
        'audit_number' => 'AUD-VISIBLE-SCOPE',
        'department_id' => $department->id,
        'scheduled_date' => today(),
    ]);
    Audit::factory()->create([
        'audit_number' => 'AUD-HIDDEN-SCOPE',
        'department_id' => $otherDepartment->id,
        'scheduled_date' => today(),
    ]);

    $inspectionTemplate = ReportTemplate::query()->where('type', 'inspection_summary')->firstOrFail();
    $inspectionReport = createScopedReport($inspectionTemplate, $this->admin, [
        'site_id' => $site->id,
        'department_id' => $department->id,
    ]);
    $inspectionReport->update(['status' => 'pending']);
    (new GenerateReportJob($inspectionReport, $this->admin))->handle();
    $inspectionCsv = Storage::get($inspectionReport->fresh()->file_path);
    expect($inspectionCsv)->toContain('INS-VISIBLE-SCOPE')->not->toContain('INS-HIDDEN-SCOPE');

    $auditTemplate = ReportTemplate::query()->where('type', 'audit_summary')->firstOrFail();
    $auditReport = createScopedReport($auditTemplate, $this->admin, ['site_id' => $site->id]);
    $auditReport->update(['status' => 'pending']);
    (new GenerateReportJob($auditReport, $this->admin))->handle();
    $auditCsv = Storage::get($auditReport->fresh()->file_path);
    expect($auditCsv)->toContain('AUD-VISIBLE-SCOPE')->not->toContain('AUD-HIDDEN-SCOPE');
});

function createScopedReport(ReportTemplate $template, User $user, array $parameters): SavedReport
{
    return SavedReport::query()->create([
        'name' => 'Scoped fixture '.json_encode($parameters),
        'template_id' => $template->id,
        'status' => 'completed',
        'parameters' => array_merge([
            'date_from' => now()->subWeek()->toDateString(),
            'date_to' => now()->toDateString(),
            'site_id' => null,
            'department_id' => null,
            'include_charts' => false,
        ], $parameters),
        'format' => 'csv',
        'generated_by' => $user->id,
        'generated_at' => now(),
        'completed_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function scopedReportPayload(ReportTemplate $template, array $overrides = []): array
{
    return array_merge([
        'template_id' => $template->id,
        'name' => 'Scoped report regression',
        'date_from' => now()->subWeek()->toDateString(),
        'date_to' => now()->toDateString(),
        'format' => 'csv',
        'include_charts' => false,
    ], $overrides);
}

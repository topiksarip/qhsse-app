<?php

use App\Core\Workflow\WorkflowService;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Users\Employee;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Database\Seeders\IncidentReportingSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\QhsseMasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(QhsseMasterDataSeeder::class);
    $this->seed(NumberingFormatSeeder::class);
    $this->seed(WorkflowSeeder::class);
    $this->seed(NotificationTemplateSeeder::class);
    $this->seed(IncidentReportingSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
    $this->site = Site::factory()->create();
    $this->severity = Severity::factory()->create();
    $this->priority = Priority::factory()->create();
});

function incidentFor(object $test, array $attributes = []): IncidentReport
{
    return IncidentReport::factory()->create($attributes + [
        'site_id' => $test->site->id,
        'severity_id' => $test->severity->id,
        'priority_id' => $test->priority->id,
        'reporter_id' => $test->admin->id,
    ]);
}

function workflowAt(object $test, IncidentReport $incident, string $status): void
{
    app(WorkflowService::class)->start('incident', $incident->id, $test->admin);
    WorkflowInstance::query()
        ->where('module_name', 'incident')
        ->where('reference_id', $incident->id)
        ->update(['current_status' => $status]);
}

test('own scope only lists and opens incidents reported by the user', function () {
    $reporter = User::factory()->create();
    $reporter->assignRole('Employee / Reporter');
    $own = incidentFor($this, ['reporter_id' => $reporter->id]);
    $other = incidentFor($this);

    actingAs($reporter);

    $this->get(route('incident.reports.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.id', $own->id));
    $this->get(route('incident.reports.show', $other))->assertForbidden();
});

test('site scope cannot view or export incidents from another site', function () {
    $employee = Employee::factory()->forSite($this->site)->create();
    $officer = User::factory()->create(['employee_id' => $employee->id]);
    $officer->assignRole('QHSSE Officer');
    $visible = incidentFor($this);
    $hidden = incidentFor($this, ['site_id' => Site::factory()->create()->id]);

    actingAs($officer);

    $this->get(route('incident.reports.show', $visible))->assertOk();
    $this->get(route('incident.reports.show', $hidden))->assertForbidden();
    $csv = $this->get(route('incident.reports.export'))->streamedContent();
    expect($csv)->toContain($visible->incident_number)->not->toContain($hidden->incident_number);
});

test('create rejects area department and involved employee from another site', function () {
    $otherSite = Site::factory()->create();
    $area = Area::factory()->for($otherSite)->create();
    $department = Department::factory()->for($otherSite)->create();
    $employee = Employee::factory()->forSite($otherSite)->create();

    actingAs($this->admin);

    $this->post(route('incident.reports.store'), [
        'title' => 'Tampered location',
        'category' => 'incident',
        'occurred_at' => now()->toDateTimeString(),
        'site_id' => $this->site->id,
        'area_id' => $area->id,
        'department_id' => $department->id,
        'severity_id' => $this->severity->id,
        'priority_id' => $this->priority->id,
        'description' => 'Invalid cross-site relations',
        'involved_persons' => [['employee_id' => $employee->id, 'note' => 'Cross site']],
    ])->assertSessionHasErrors(['area_id', 'department_id', 'involved_persons.0.employee_id']);

    expect(IncidentReport::count())->toBe(0);
});

test('draft update synchronizes involved persons without writing unknown columns', function () {
    $incident = incidentFor($this, ['status' => 'draft']);
    $employee = Employee::factory()->forSite($this->site)->create();

    actingAs($this->admin);

    $this->put(route('incident.reports.update', $incident), [
        'title' => 'Updated incident',
        'site_id' => $this->site->id,
        'involved_persons' => [['employee_id' => $employee->id, 'note' => 'Witness']],
    ])->assertRedirect(route('incident.reports.show', $incident));

    expect($incident->fresh()->title)->toBe('Updated incident')
        ->and($incident->involvedPersons()->whereKey($employee->id)->exists())->toBeTrue()
        ->and($incident->involvedPersons()->first()->pivot->note)->toBe('Witness');
});

test('submitted incident can be rejected only with reason and notifies reporter', function () {
    $reporter = User::factory()->create();
    $incident = incidentFor($this, ['status' => 'submitted', 'reporter_id' => $reporter->id]);
    workflowAt($this, $incident, 'submitted');

    actingAs($this->admin);

    $this->post(route('incident.reports.reject', $incident))->assertSessionHasErrors('reason');
    $this->post(route('incident.reports.reject', $incident), ['reason' => 'Laporan tidak memiliki informasi yang valid.'])
        ->assertRedirect(route('incident.reports.show', $incident));

    expect($incident->fresh()->status)->toBe('rejected')
        ->and(CoreNotification::query()->where('recipient_id', $reporter->id)->where('type', 'incident.rejected')->exists())->toBeTrue();
});

test('evidence is stored privately and nested download enforces ownership', function () {
    Storage::fake('local');
    $incident = incidentFor($this, ['status' => 'draft']);
    $other = incidentFor($this, ['status' => 'draft']);

    actingAs($this->admin);

    $this->post(route('incident.reports.evidence.store', $incident), [
        'file' => UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $file = ManagedFile::query()->where('module_name', 'incident')->firstOrFail();
    Storage::disk('local')->assertExists($file->path);
    $this->get(route('incident.reports.evidence.download', [$incident, $file]))->assertOk();
    $this->get(route('incident.reports.evidence.download', [$other, $file]))->assertNotFound();
});

test('terminal incident rejects new evidence and non draft edit', function () {
    Storage::fake('local');
    $incident = incidentFor($this, ['status' => 'closed']);

    actingAs($this->admin);

    $this->get(route('incident.reports.edit', $incident))->assertStatus(409);
    $this->post(route('incident.reports.evidence.store', $incident), [
        'file' => UploadedFile::fake()->create('late.pdf', 10, 'application/pdf'),
    ])->assertStatus(409);
    expect(ManagedFile::count())->toBe(0);
});

test('authorized export user can open printable incident detail', function () {
    $incident = incidentFor($this);

    actingAs($this->admin);

    $this->get(route('incident.reports.print', $incident))
        ->assertOk()
        ->assertSee($incident->incident_number)
        ->assertSee('Cetak / Simpan PDF');
});

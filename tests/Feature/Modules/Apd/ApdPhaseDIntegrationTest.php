<?php

namespace Tests\Feature\Modules\Apd;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Apd\ApdCatalog;
use App\Models\Modules\Apd\ApdInspection;
use App\Models\Modules\Apd\ApdItem;
use App\Models\Modules\Apd\ApdRequirement;
use App\Models\Modules\Capa\CapaAction;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\RiskManagement\RiskRegister;
use App\Models\Modules\Training\TrainingRecord;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\UsesRefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, \Database\Seeders\NumberingFormatSeeder::class, \Database\Seeders\WorkflowSeeder::class]);
});

function phaseDActor(string $role): User
{
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $employee = \App\Models\Core\Users\Employee::factory()->create([
        'site_id' => $site->id,
        'department_id' => $dept->id,
    ]);
    $user = User::factory()->linkedToEmployee($employee)->create();
    $user->assignRole($role);

    return $user;
}

function makeCatalog(User $actor, Site $site, Department $department): ApdCatalog
{
    return ApdCatalog::create([
        'catalog_code' => 'PPE-REQ-' . random_int(1000, 9999),
        'name' => 'Helm Safety',
        'category' => 'head_protection',
        'track_type' => 'serial',
        'site_id' => $site->id,
        'department_id' => $department->id,
        'min_stock' => 0,
        'reorder_point' => 0,
        'is_active' => true,
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ]);
}

it('links an APD requirement to a risk register and lists it', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = phaseDActor('QHSSE Manager');
    $manager->employee->update(['site_id' => $site->id, 'department_id' => $dept->id]);

    $risk = RiskRegister::factory()->create([
        'site_id' => $site->id,
        'department_id' => $dept->id,
        'created_by' => $manager->id,
    ]);
    $catalog = makeCatalog($manager, $site, $dept);

    $this->actingAs($manager)
        ->post(route('apd.requirements.store'), [
            'risk_register_id' => $risk->id,
            'apd_catalog_id' => $catalog->id,
            'quantity' => 5,
            'notes' => 'Butuh helm tambahan',
        ])
        ->assertRedirect();

    expect(ApdRequirement::where('risk_register_id', $risk->id)->count())->toBe(1);
    $req = ApdRequirement::first();
    expect($req->apd_catalog_id)->toBe($catalog->id);

    // Reverse relationship from catalog
    expect($catalog->apdRequirements()->count())->toBe(1);
    // Reverse relationship from risk register
    expect($risk->apdRequirements()->count())->toBe(1);

    // Destroy
    $this->actingAs($manager)
        ->delete(route('apd.requirements.destroy', $req->id))
        ->assertRedirect();
    expect(ApdRequirement::find($req->id))->toBeNull();
});

it('blocks APD requirement creation without manage permission', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $reporter = phaseDActor('Employee / Reporter');
    $reporter->employee->update(['site_id' => $site->id, 'department_id' => $dept->id]);

    $risk = RiskRegister::factory()->create([
        'site_id' => $site->id,
        'department_id' => $dept->id,
        'created_by' => $reporter->id,
    ]);
    $catalog = makeCatalog($reporter, $site, $dept);

    $this->actingAs($reporter)
        ->post(route('apd.requirements.store'), [
            'risk_register_id' => $risk->id,
            'apd_catalog_id' => $catalog->id,
            'quantity' => 5,
        ])
        ->assertForbidden();
});

it('escalates an unfit APD inspection to a CAPA action', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = phaseDActor('QHSSE Manager');
    $manager->employee->update(['site_id' => $site->id, 'department_id' => $dept->id]);

    $catalog = makeCatalog($manager, $site, $dept);
    $item = ApdItem::create([
        'item_number' => 'PPE-IT-REQ01',
        'catalog_id' => $catalog->id,
        'site_id' => $site->id,
        'department_id' => $dept->id,
        'track_type' => 'serial',
        'status' => 'issued',
        'quantity' => 1,
        'created_by' => $manager->id,
        'updated_by' => $manager->id,
    ]);

    $inspection = ApdInspection::create([
        'apd_item_id' => $item->id,
        'inspection_type' => 'routine',
        'inspection_date' => now()->toDateString(),
        'result' => 'tidak_layak',
        'condition' => 'rusak',
        'next_inspection_date' => null,
        'notes' => 'Retak',
        'inspected_by' => $manager->id,
        'site_id' => $site->id,
        'department_id' => $dept->id,
        'created_by' => $manager->id,
        'updated_by' => $manager->id,
    ]);

    $priority = Priority::factory()->create();

    $this->actingAs($manager)
        ->post(route('apd.inspections.escalate', $inspection->id), [
            'assigned_to' => $manager->id,
            'priority_id' => $priority->id,
        ])
        ->assertRedirect();

    $capa = CapaAction::where('source_module', 'apd_inspection')
        ->where('source_reference_id', $inspection->id)
        ->first();
    expect($capa)->not->toBeNull();
    expect($capa->source_module)->toBe('apd_inspection');
    expect($inspection->fresh()->capaActions()->count())->toBe(1);
});

it('escalates a PPE-failure incident to a CAPA action', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = phaseDActor('QHSSE Manager');
    $manager->employee->update(['site_id' => $site->id, 'department_id' => $dept->id]);

    $catalog = makeCatalog($manager, $site, $dept);
    $item = ApdItem::create([
        'item_number' => 'PPE-IT-INC01',
        'catalog_id' => $catalog->id,
        'site_id' => $site->id,
        'department_id' => $dept->id,
        'track_type' => 'serial',
        'status' => 'issued',
        'quantity' => 1,
        'created_by' => $manager->id,
        'updated_by' => $manager->id,
    ]);

    $incident = IncidentReport::factory()->create([
        'site_id' => $site->id,
        'department_id' => $dept->id,
        'ppe_involved' => true,
        'apd_item_id' => $item->id,
        'ppe_failure' => true,
        'ppe_notes' => 'Tali patah',
        'created_by' => $manager->id,
        'reporter_id' => $manager->id,
    ]);

    $priority = Priority::factory()->create();

    $this->actingAs($manager)
        ->post(route('incident.reports.escalate', $incident->id), [
            'assigned_to' => $manager->id,
            'priority_id' => $priority->id,
        ])
        ->assertRedirect();

    $capa = CapaAction::where('source_module', 'incident')
        ->where('source_reference_id', $incident->id)
        ->first();
    expect($capa)->not->toBeNull();
    expect($incident->fresh()->capaActions()->count())->toBe(1);
});

it('stores a PPE fit-test training record with APD link', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = phaseDActor('QHSSE Manager');
    $manager->employee->update(['site_id' => $site->id, 'department_id' => $dept->id]);

    $catalog = makeCatalog($manager, $site, $dept);
    $item = ApdItem::create([
        'item_number' => 'PPE-IT-FIT01',
        'catalog_id' => $catalog->id,
        'site_id' => $site->id,
        'department_id' => $dept->id,
        'track_type' => 'serial',
        'status' => 'issued',
        'quantity' => 1,
        'created_by' => $manager->id,
        'updated_by' => $manager->id,
    ]);

    $record = TrainingRecord::create([
        'training_number' => 'TR-'.now()->year.'-0001',
        'employee_id' => $manager->employee->id,
        'training_program_id' => \App\Models\Modules\Training\TrainingProgram::factory()->create()->id,
        'start_date' => now()->toDateString(),
        'status' => 'completed',
        'training_type' => 'ppe_fit_test',
        'apd_item_id' => $item->id,
        'fit_test_result' => 'pass',
        'created_by' => $manager->id,
        'updated_by' => $manager->id,
    ]);

    expect($record->training_type)->toBe('ppe_fit_test');
    expect($record->apd_item_id)->toBe($item->id);
    expect($record->fit_test_result)->toBe('pass');
    expect($item->fresh()->trainingRecords()->count())->toBe(1);
});

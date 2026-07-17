<?php

use App\Core\Workflow\WorkflowService;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Modules\Inspection\Inspection;
use App\Models\Modules\Inspection\InspectionItem;
use App\Models\Modules\Inspection\InspectionTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

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
    $this->seed(\Database\Seeders\InspectionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

// === TEMPLATE ===

test('authorized user can view template list', function () {
    actingAs($this->admin);
    $this->get(route('inspection.templates.index'))->assertStatus(200)->assertInertia(fn ($p) => $p->component('Modules/Inspection/Templates/Index'));
});

test('authorized user can create inspection template', function () {
    actingAs($this->admin);
    $this->post(route('inspection.templates.store'), [
        'code' => 'SAF-001', 'name' => 'Safety Checklist', 'category' => 'safety',
        'items' => [
            ['question' => 'PPE lengkap?', 'type' => 'yes_no', 'is_required' => true, 'order' => 0],
            ['question' => 'Fire extinguisher tersedia?', 'type' => 'safe_unsafe', 'is_required' => true, 'order' => 1],
        ],
    ]);
    $tpl = InspectionTemplate::first();
    expect($tpl)->not->toBeNull();
    expect($tpl->name)->toBe('Safety Checklist');
    expect($tpl->items)->toHaveCount(2);
});

test('template with missing code fails validation', function () {
    actingAs($this->admin);
    $this->post(route('inspection.templates.store'), ['name' => 'Test', 'category' => 'safety'])->assertSessionHasErrors(['code']);
});

// === INSPECTION ===

test('authorized user can view inspection list', function () {
    actingAs($this->admin);
    $this->get(route('inspection.checklists.index'))->assertStatus(200)->assertInertia(fn ($p) => $p->component('Modules/Inspection/Index'));
});

test('authorized user can create inspection', function () {
    actingAs($this->admin);
    $tpl = InspectionTemplate::factory()->create();
    $site = \App\Models\Core\MasterData\Site::factory()->create();
    $inspector = User::factory()->create();

    $this->post(route('inspection.checklists.store'), [
        'inspection_template_id' => $tpl->id, 'site_id' => $site->id, 'inspector_id' => $inspector->id, 'scheduled_at' => now()->addDays(7)->format('Y-m-d'),
    ]);
    $insp = Inspection::first();
    expect($insp)->not->toBeNull();
    expect($insp->status)->toBe('pending');
    expect($insp->inspection_number)->toMatch('/^INS-\d{4}-\d{4}$/');
});

test('pending inspection can be started', function () {
    actingAs($this->admin);
    $insp = Inspection::factory()->create(['status' => 'pending']);
    app(WorkflowService::class)->start('inspection', $insp->id, $this->admin);
    $this->post(route('inspection.checklists.start', $insp))->assertRedirect();
    expect($insp->fresh()->status)->toBe('in_progress');
    expect($insp->fresh()->executed_at)->not->toBeNull();
});

test('in_progress inspection can be completed with pass result', function () {
    actingAs($this->admin);
    $tpl = InspectionTemplate::factory()->create();
    $item = InspectionItem::create(['inspection_template_id' => $tpl->id, 'question' => 'Test?', 'type' => 'yes_no', 'is_required' => true, 'order' => 0]);
    $insp = Inspection::factory()->create(['status' => 'in_progress', 'inspection_template_id' => $tpl->id]);
    app(WorkflowService::class)->start('inspection', $insp->id, $this->admin);
    WorkflowInstance::where('module_name', 'inspection')->where('reference_id', $insp->id)->update(['current_status' => 'in_progress']);
    $insp->results()->create(['inspection_item_id' => $item->id, 'answer' => 'yes', 'is_unsafe' => false]);

    $this->post(route('inspection.checklists.complete', $insp))->assertRedirect();
    expect($insp->fresh()->status)->toBe('completed');
    expect($insp->fresh()->overall_result)->toBe('pass');
});

test('in_progress inspection with unsafe item completes with fail result', function () {
    actingAs($this->admin);
    $tpl = InspectionTemplate::factory()->create();
    $item = InspectionItem::create(['inspection_template_id' => $tpl->id, 'question' => 'Safe?', 'type' => 'safe_unsafe', 'is_required' => true, 'order' => 0]);
    $insp = Inspection::factory()->create(['status' => 'in_progress', 'inspection_template_id' => $tpl->id]);
    app(WorkflowService::class)->start('inspection', $insp->id, $this->admin);
    WorkflowInstance::where('module_name', 'inspection')->where('reference_id', $insp->id)->update(['current_status' => 'in_progress']);
    $insp->results()->create(['inspection_item_id' => $item->id, 'answer' => 'unsafe', 'is_unsafe' => true]);

    $this->post(route('inspection.checklists.complete', $insp))->assertRedirect();
    expect($insp->fresh()->overall_result)->toBe('fail');
});

test('inspection results can be saved', function () {
    actingAs($this->admin);
    $tpl = InspectionTemplate::factory()->create();
    $item = InspectionItem::create(['inspection_template_id' => $tpl->id, 'question' => 'Test?', 'type' => 'yes_no', 'is_required' => true, 'order' => 0]);
    $insp = Inspection::factory()->create(['status' => 'in_progress', 'inspection_template_id' => $tpl->id]);
    app(WorkflowService::class)->start('inspection', $insp->id, $this->admin);
    WorkflowInstance::where('module_name', 'inspection')->where('reference_id', $insp->id)->update(['current_status' => 'in_progress']);

    $this->put(route('inspection.checklists.update', $insp), [
        'results' => [['inspection_item_id' => $item->id, 'answer' => 'yes', 'is_unsafe' => false]],
        'notes' => 'All good',
    ])->assertRedirect();

    expect($insp->fresh()->notes)->toBe('All good');
    expect($insp->results()->count())->toBe(1);
});

// === PERMISSION ===

test('user without inspection.checklists.view gets 403', function () {
    $user = User::factory()->create();
    actingAs($user);
    $this->get(route('inspection.checklists.index'))->assertForbidden();
    $this->get(route('inspection.templates.index'))->assertForbidden();
});

test('employee can view but not create inspections', function () {
    $emp = User::factory()->create();
    $emp->assignRole('Employee / Reporter');
    actingAs($emp);
    $this->get(route('inspection.checklists.index'))->assertStatus(200);
    $this->get(route('inspection.checklists.create'))->assertForbidden();
});

test('export blocked without inspection.checklists.export', function () {
    $user = User::factory()->create();
    actingAs($user);
    $this->get(route('inspection.checklists.export'))->assertForbidden();
});

// === NEGATIVE ===

test('cannot start non-pending inspection', function () {
    actingAs($this->admin);
    $insp = Inspection::factory()->create(['status' => 'completed']);
    app(WorkflowService::class)->start('inspection', $insp->id, $this->admin);
    WorkflowInstance::where('module_name', 'inspection')->where('reference_id', $insp->id)->update(['current_status' => 'completed']);
    $this->post(route('inspection.checklists.start', $insp))->assertSessionHasErrors(['workflow']);
});

test('inspection with missing template fails validation', function () {
    actingAs($this->admin);
    $this->post(route('inspection.checklists.store'), ['site_id' => 1, 'inspector_id' => 1, 'scheduled_at' => '2026-07-11'])->assertSessionHasErrors(['inspection_template_id']);
});

test('template can store yes_no_na item type', function () {
    actingAs($this->admin);
    $code = 'T-YNNA-' . uniqid();
    $this->post(route('inspection.templates.store'), [
        'code' => $code,
        'name' => 'Template YNNA',
        'category' => 'safety',
        'items' => [
            ['question' => 'Guard on?', 'type' => 'yes_no_na', 'is_required' => true, 'order' => 0],
        ],
    ])->assertRedirect();

    $tpl = InspectionTemplate::where('code', $code)->first();
    expect($tpl)->not->toBeNull();
    expect($tpl->items()->where('type', 'yes_no_na')->count())->toBe(1);
});

test('inspection result can store a photo upload', function () {
    Storage::fake('local');
    actingAs($this->admin);
    $tpl = InspectionTemplate::factory()->create();
    $item = InspectionItem::create(['inspection_template_id' => $tpl->id, 'question' => 'Snap?', 'type' => 'photo', 'is_required' => true, 'order' => 0]);
    $insp = Inspection::factory()->create(['status' => 'in_progress', 'inspection_template_id' => $tpl->id]);
    app(WorkflowService::class)->start('inspection', $insp->id, $this->admin);
    WorkflowInstance::where('module_name', 'inspection')->where('reference_id', $insp->id)->update(['current_status' => 'in_progress']);

    $file = \Illuminate\Http\UploadedFile::fake()->image('evidence.jpg', 100, 100);

    $this->put(route('inspection.checklists.update', $insp), [
        'results' => [['inspection_item_id' => $item->id, 'answer' => null, 'is_unsafe' => false, 'photo' => $file]],
        'notes' => 'with photo',
    ])->assertRedirect();

    $result = $insp->fresh()->results()->where('inspection_item_id', $item->id)->first();
    expect($result)->not->toBeNull();
    expect($result->photo)->not->toBeNull();
    expect(\App\Models\Core\Files\ManagedFile::where('collection', 'inspection_result')->count())->toBe(1);
});

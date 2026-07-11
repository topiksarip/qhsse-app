<?php

use App\Core\Workflow\WorkflowService;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\MasterData\Department;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\Modules\DocumentControl\DocumentReview;
use App\Models\User;
use Database\Seeders\DocumentControlSeeder;
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
    $this->seed(DocumentControlSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

function documentWorkflowAt(ControlledDocument $document, User $actor, string $status): void
{
    app(WorkflowService::class)->start('document', $document->id, $actor);
    WorkflowInstance::query()
        ->where('module_name', 'document')
        ->where('reference_id', $document->id)
        ->update(['current_status' => $status]);
}

function documentFile(ControlledDocument $document, User $uploader): ManagedFile
{
    Storage::fake('local');
    Storage::disk('local')->put("managed-files/document/{$document->id}/document_file/test.pdf", 'private document');

    return ManagedFile::factory()->create([
        'module_name' => 'document',
        'reference_id' => $document->id,
        'collection' => 'document_file',
        'disk' => 'local',
        'path' => "managed-files/document/{$document->id}/document_file/test.pdf",
        'original_name' => 'document.pdf',
        'uploaded_by' => $uploader->id,
    ]);
}

test('authorized user can view document register', function () {
    actingAs($this->admin);

    $this->get(route('document.control.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Modules/DocumentControl/Index')
            ->has('items')
            ->has('filters')
            ->has('departments')
        );
});

test('authorized user can create numbered draft with private file and audit trail', function () {
    Storage::fake('local');
    actingAs($this->admin);

    $this->post(route('document.control.store'), [
        'title' => 'SOP Penggunaan APD',
        'type' => 'sop',
        'version' => '1.0',
        'revision_notes' => 'Penerbitan awal',
        'review_date' => now()->addYear()->toDateString(),
        'is_confidential' => true,
        'action' => 'draft',
        'file' => UploadedFile::fake()->create('sop-apd.pdf', 256, 'application/pdf'),
    ])->assertRedirect();

    $document = ControlledDocument::query()->firstOrFail();
    expect($document->document_number)->toMatch('/^DOC-\d{4}-\d{4}$/')
        ->and($document->status)->toBe('draft')
        ->and($document->owner_id)->toBe($this->admin->id)
        ->and($document->is_confidential)->toBeTrue()
        ->and($document->reviews)->toHaveCount(0);

    $file = ManagedFile::query()->where('module_name', 'document')->where('reference_id', $document->id)->firstOrFail();
    Storage::disk('local')->assertExists($file->path);
    expect($file->collection)->toBe('document_file')
        ->and(AuditLog::query()->where('module_name', 'document')->where('reference_id', $document->id)->where('event', 'created')->exists())->toBeTrue();
});

test('document validation rejects missing title invalid type and invalid dates', function () {
    actingAs($this->admin);

    $this->post(route('document.control.store'), [
        'type' => 'invalid',
        'version' => '1.0',
        'review_date' => now()->subDay()->toDateString(),
        'expiry_date' => now()->subDays(2)->toDateString(),
    ])->assertSessionHasErrors(['title', 'type', 'review_date', 'expiry_date']);

    expect(ControlledDocument::query()->count())->toBe(0);
});

test('draft document with file can be submitted for review and manager is notified', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $document = ControlledDocument::factory()->create(['status' => 'draft', 'owner_id' => $this->admin->id]);
    documentWorkflowAt($document, $this->admin, 'draft');
    documentFile($document, $this->admin);
    actingAs($this->admin);

    $this->post(route('document.control.submitReview', $document), [
        'review_notes' => 'Siap untuk ditinjau.',
    ])->assertRedirect();

    expect($document->fresh()->status)->toBe('review')
        ->and(DocumentReview::query()->where('document_id', $document->id)->where('decision', 'pending')->exists())->toBeTrue()
        ->and(CoreNotification::query()->where('recipient_id', $manager->id)->where('type', 'document.submitted')->exists())->toBeTrue();
});

test('document cannot be submitted without controlled file', function () {
    $document = ControlledDocument::factory()->create(['status' => 'draft', 'owner_id' => $this->admin->id]);
    documentWorkflowAt($document, $this->admin, 'draft');
    actingAs($this->admin);

    $this->post(route('document.control.submitReview', $document))
        ->assertSessionHasErrors(['file']);

    expect($document->fresh()->status)->toBe('draft');
});

test('manager can approve and make document effective', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $document = ControlledDocument::factory()->create(['status' => 'review', 'effective_date' => null]);
    documentWorkflowAt($document, $manager, 'review');
    DocumentReview::factory()->create(['document_id' => $document->id, 'decision' => 'pending']);
    actingAs($manager);

    $this->post(route('document.control.approve', $document), ['review_notes' => 'Memenuhi standar.'])->assertRedirect();
    expect($document->fresh()->status)->toBe('approved')
        ->and($document->fresh()->approver_id)->toBe($manager->id)
        ->and($document->reviews()->latest('id')->first()->decision)->toBe('approve');

    $this->post(route('document.control.makeEffective', $document), ['effective_date' => '2026-08-01'])->assertRedirect();
    expect($document->fresh()->status)->toBe('effective')
        ->and($document->fresh()->effective_date->format('Y-m-d'))->toBe('2026-08-01');
});

test('manager can reject and owner can revise then resubmit as a new review cycle', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $owner = User::factory()->create();
    $owner->assignRole('QHSSE Officer');
    $document = ControlledDocument::factory()->create(['status' => 'review', 'owner_id' => $owner->id]);
    documentWorkflowAt($document, $manager, 'review');
    DocumentReview::factory()->create(['document_id' => $document->id, 'decision' => 'pending']);
    documentFile($document, $owner);
    actingAs($manager);

    $this->post(route('document.control.reject', $document), [
        'reason' => 'Prosedur keadaan darurat perlu diperjelas.',
    ])->assertRedirect();
    expect($document->fresh()->status)->toBe('rejected')
        ->and($document->reviews()->latest('id')->first()->decision)->toBe('reject');

    actingAs($owner);
    $this->post(route('document.control.revise', $document))->assertRedirect();
    $this->post(route('document.control.submitReview', $document), ['review_notes' => 'Revisi sudah dilengkapi.'])->assertRedirect();

    expect($document->fresh()->status)->toBe('review')
        ->and($document->reviews()->count())->toBe(2)
        ->and($document->reviews()->latest('id')->first()->decision)->toBe('pending');
});

test('obsolete and reject require a meaningful reason', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $effective = ControlledDocument::factory()->create(['status' => 'effective']);
    documentWorkflowAt($effective, $manager, 'effective');
    actingAs($manager);

    $this->post(route('document.control.obsolete', $effective), ['reason' => 'pendek'])
        ->assertSessionHasErrors(['reason']);
    expect($effective->fresh()->status)->toBe('effective');

    $this->post(route('document.control.obsolete', $effective), [
        'reason' => 'Digantikan oleh prosedur operasional terbaru.',
    ])->assertRedirect();
    expect($effective->fresh()->status)->toBe('obsolete')
        ->and(AuditLog::query()->where('module_name', 'document')->where('reference_id', $effective->id)->where('event', 'workflow.transitioned')->exists())->toBeTrue();
});

test('only draft or rejected documents can be edited', function () {
    actingAs($this->admin);
    $effective = ControlledDocument::factory()->create(['status' => 'effective']);

    $this->put(route('document.control.update', $effective), [
        'title' => 'Tidak boleh berubah',
        'type' => 'sop',
        'version' => '2.0',
    ])->assertForbidden();
});

test('user without document view permission is forbidden', function () {
    actingAs(User::factory()->create());
    $this->get(route('document.control.index'))->assertForbidden();
});

test('officer cannot approve and auditor cannot create', function () {
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');
    $auditor = User::factory()->create();
    $auditor->assignRole('Auditor');
    $document = ControlledDocument::factory()->create(['status' => 'review']);

    actingAs($officer);
    $this->post(route('document.control.approve', $document))->assertForbidden();
    actingAs($auditor);
    $this->get(route('document.control.create'))->assertForbidden();
});

test('create permission alone cannot bypass submit review permission', function () {
    $creator = User::factory()->create();
    $creator->givePermissionTo('document.control.create');
    actingAs($creator);

    $this->post(route('document.control.store'), [
        'title' => 'Crafted submit',
        'type' => 'sop',
        'version' => '1.0',
        'action' => 'submit_review',
        'file' => UploadedFile::fake()->create('crafted.pdf', 20, 'application/pdf'),
    ])->assertForbidden();

    $this->assertDatabaseMissing('controlled_documents', ['title' => 'Crafted submit']);
});

test('employee can view effective documents but not drafts', function () {
    $employee = User::factory()->create();
    $employee->assignRole('Employee / Reporter');
    $effective = ControlledDocument::factory()->create(['status' => 'effective']);
    $draft = ControlledDocument::factory()->create(['status' => 'draft']);
    actingAs($employee);

    $this->get(route('document.control.show', $effective))->assertOk();
    $this->get(route('document.control.show', $draft))->assertForbidden();
});

test('confidential file download is limited to owner approver or privileged roles', function () {
    $owner = User::factory()->create();
    $owner->assignRole('Employee / Reporter');
    $viewer = User::factory()->create();
    $viewer->assignRole('Employee / Reporter');
    $document = ControlledDocument::factory()->create([
        'status' => 'effective',
        'owner_id' => $owner->id,
        'is_confidential' => true,
    ]);
    $file = documentFile($document, $owner);

    actingAs($viewer);
    $this->get(route('document.control.files.download', [$document, $file]))->assertForbidden();

    actingAs($owner);
    $this->get(route('document.control.files.download', [$document, $file]))
        ->assertOk()
        ->assertDownload('document.pdf');
});

test('document download rejects a file linked to another reference', function () {
    $first = ControlledDocument::factory()->create(['status' => 'effective']);
    $second = ControlledDocument::factory()->create(['status' => 'effective']);
    $file = documentFile($second, $this->admin);
    actingAs($this->admin);

    $this->get(route('document.control.files.download', [$first, $file]))->assertNotFound();
});

test('document register supports status type and department filters', function () {
    $department = Department::factory()->create();
    ControlledDocument::factory()->create(['type' => 'sop', 'status' => 'effective', 'department_id' => $department->id]);
    ControlledDocument::factory()->create(['type' => 'wi', 'status' => 'draft']);
    actingAs($this->admin);

    $response = $this->get(route('document.control.index', [
        'type' => 'sop',
        'status' => 'effective',
        'department_id' => $department->id,
    ]));

    $response->assertInertia(fn ($page) => $page
        ->has('items.data', 1)
        ->where('items.data.0.type', 'sop')
        ->where('filters.status', 'effective')
    );
});

test('document export is permission protected and streams csv', function () {
    ControlledDocument::factory()->create(['title' => 'SOP Export']);
    actingAs($this->admin);
    $this->get(route('document.control.export'))->assertOk()->assertDownload('documents-export.csv');

    $employee = User::factory()->create();
    $employee->assignRole('Employee / Reporter');
    actingAs($employee);
    $this->get(route('document.control.export'))->assertForbidden();
});

test('two created documents receive unique numbers', function () {
    actingAs($this->admin);
    foreach (['Dokumen pertama', 'Dokumen kedua'] as $title) {
        $this->post(route('document.control.store'), [
            'title' => $title,
            'type' => 'sop',
            'version' => '1.0',
            'action' => 'draft',
        ])->assertRedirect();
    }

    $numbers = ControlledDocument::query()->pluck('document_number');
    expect($numbers)->toHaveCount(2)
        ->and($numbers->unique())->toHaveCount(2);
});

test('document comment uses shared service and returns to detail page', function () {
    $document = ControlledDocument::factory()->create(['status' => 'draft']);
    actingAs($this->admin);

    $this->from(route('document.control.show', $document))
        ->post(route('document.control.comments.store', $document), ['body' => 'Mohon verifikasi referensi regulasinya.'])
        ->assertRedirect(route('document.control.show', $document));

    $this->assertDatabaseHas('comments', [
        'module_name' => 'document',
        'reference_id' => $document->id,
        'body' => 'Mohon verifikasi referensi regulasinya.',
    ]);
});

test('document comment requires shared comment create permission', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('document.control.view');
    $document = ControlledDocument::factory()->create(['status' => 'effective']);

    actingAs($viewer);
    $this->post(route('document.control.comments.store', $document), ['body' => 'Tidak boleh tersimpan.'])
        ->assertForbidden();

    $this->assertDatabaseMissing('comments', [
        'module_name' => 'document',
        'reference_id' => $document->id,
        'body' => 'Tidak boleh tersimpan.',
    ]);
});

test('expiry reminder command notifies owner once at configured threshold', function () {
    $document = ControlledDocument::factory()->create([
        'status' => 'effective',
        'owner_id' => $this->admin->id,
        'review_date' => today()->addDays(30),
        'expiry_date' => today()->addYear(),
    ]);

    $this->artisan('documents:check-expiry')->assertSuccessful();
    $this->artisan('documents:check-expiry')->assertSuccessful();

    $notifications = CoreNotification::query()
        ->where('recipient_id', $this->admin->id)
        ->where('type', 'document.expiry_reminder')
        ->where('reference_id', $document->id)
        ->get();

    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->data['threshold_days'])->toBe(30);
});

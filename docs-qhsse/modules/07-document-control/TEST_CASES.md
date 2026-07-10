# Test Cases — Document Control

> Pest PHP 3 + PHPUnit. Tests run on SQLite in-memory via `.env.testing`.

## Test Environment

File: `.env.testing`

```ini
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
```

Test file: `tests/Feature/Modules/Document/DocumentControlTest.php`

## Factory Definition

File: `database/factories/Modules/Document/ControlledDocumentFactory.php`

```php
public function definition(): array
{
    return [
        'document_number' => 'DOC-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'title' => fake()->sentence(4),
        'type' => fake()->randomElement([
            'sop', 'wi', 'jsa', 'hiradc', 'msds',
            'policy', 'form', 'manual', 'other',
        ]),
        'version' => fake()->randomElement(['1.0', '1.1', '2.0', '3.0']),
        'revision_notes' => fake()->optional(0.7)->paragraph(2),
        'effective_date' => fake()->optional(0.5)->dateTimeBetween('-1 month', '+1 month')?->format('Y-m-d'),
        'review_date' => fake()->optional(0.5)->dateTimeBetween('+1 month', '+1 year')?->format('Y-m-d'),
        'expiry_date' => null,
        'department_id' => null,
        'owner_id' => User::factory(),
        'approver_id' => null,
        'status' => 'draft',
        'is_confidential' => fake()->boolean(20),
    ];
}
```

File: `database/factories/Modules/Document/DocumentReviewFactory.php`

```php
public function definition(): array
{
    return [
        'document_id' => ControlledDocument::factory(),
        'reviewer_id' => null,
        'review_date' => null,
        'review_notes' => fake()->optional(0.7)->paragraph(2),
        'decision' => 'pending',
    ];
}
```

## Helper Trait

```php
trait CreatesDocumentTestUser
{
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        return $user;
    }

    protected function qhsseManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Manager');
        return $user;
    }

    protected function qhsseOfficer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Officer');
        return $user;
    }

    protected function reporterUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Employee / Reporter');
        return $user;
    }

    protected function auditorUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Auditor');
        return $user;
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view document list

```php
test('authorized user can view document list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('document.control.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Document/Index')
        ->has('items')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create document draft

```php
test('authorized user can create document draft', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('document.control.store'), [
        'title' => 'SOP Penggunaan APD di Area Produksi',
        'type' => 'sop',
        'version' => '1.0',
        'revision_notes' => 'Pembuatan SOP baru',
        'is_confidential' => false,
        'action' => 'draft',
    ]);

    $response->assertRedirect(route('document.control.show', ControlledDocument::first()));

    $document = ControlledDocument::first();
    expect($document)->not->toBeNull();
    expect($document->title)->toBe('SOP Penggunaan APD di Area Produksi');
    expect($document->status)->toBe('draft');
    expect($document->owner_id)->toBe($admin->id);
});
```

### 1.3 Document number is auto-generated on create

```php
test('document number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $this->post(route('document.control.store'), [
        'title' => 'Test SOP',
        'type' => 'sop',
        'version' => '1.0',
        'action' => 'draft',
    ]);

    $document = ControlledDocument::first();
    expect($document->document_number)->not->toBeNull();
    expect($document->document_number)->toMatch('/^DOC-\d{4}-\d{4}$/');
});
```

### 1.4 Document with missing title fails validation

```php
test('document with missing title fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('document.control.store'), [
        'type' => 'sop',
        'version' => '1.0',
        'action' => 'draft',
    ]);

    $response->assertSessionHasErrors(['title']);
    expect(ControlledDocument::count())->toBe(0);
});
```

### 1.5 Document with invalid type fails validation

```php
test('document with invalid type fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('document.control.store'), [
        'title' => 'Test Document',
        'type' => 'invalid_type',
        'version' => '1.0',
        'action' => 'draft',
    ]);

    $response->assertSessionHasErrors(['type']);
});
```

### 1.6 Draft document can be submitted for review

```php
test('draft document can be submitted for review', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $document = ControlledDocument::factory()->create([
        'status' => 'draft',
        'owner_id' => $admin->id,
    ]);
    WorkflowService::start('document', $document->id, $admin);

    // Attach a file (required for submit_review)
    ManagedFile::factory()->create([
        'module_name' => 'document',
        'reference_id' => $document->id,
        'collection' => 'document_file',
    ]);

    $response = $this->post(route('document.control.submitReview', $document), [
        'review_notes' => 'Dokumen siap untuk review.',
    ]);

    $response->assertRedirect();
    $document->refresh();
    expect($document->status)->toBe('review');

    // Document review record created
    expect(DocumentReview::where('document_id', $document->id)->count())->toBe(1);
    expect(DocumentReview::first()->decision)->toBe('pending');
});
```

### 1.7 Review document can be approved

```php
test('review document can be approved', function () {
    $manager = $this->qhsseManager();
    $this->actingAs($manager);

    $document = ControlledDocument::factory()->create(['status' => 'review']);
    WorkflowService::start('document', $document->id, $manager);
    WorkflowInstance::where('module_name', 'document')
        ->where('reference_id', $document->id)
        ->update(['current_status' => 'review']);

    // Create pending review record
    DocumentReview::create([
        'document_id' => $document->id,
        'decision' => 'pending',
    ]);

    $response = $this->post(route('document.control.approve', $document), [
        'review_notes' => 'Dokumen sudah memenuhi standar.',
    ]);

    $response->assertRedirect();
    $document->refresh();
    expect($document->status)->toBe('approved');
    expect($document->approver_id)->toBe($manager->id);

    // Review record updated
    $review = DocumentReview::where('document_id', $document->id)->latest()->first();
    expect($review->reviewer_id)->toBe($manager->id);
    expect($review->decision)->toBe('approve');
});
```

### 1.8 Approved document can be made effective

```php
test('approved document can be made effective', function () {
    $manager = $this->qhsseManager();
    $this->actingAs($manager);

    $document = ControlledDocument::factory()->create([
        'status' => 'approved',
        'effective_date' => null,
    ]);
    WorkflowService::start('document', $document->id, $manager);
    WorkflowInstance::where('module_name', 'document')
        ->where('reference_id', $document->id)
        ->update(['current_status' => 'approved']);

    $effectiveDate = '2026-08-01';

    $response = $this->post(route('document.control.makeEffective', $document), [
        'effective_date' => $effectiveDate,
    ]);

    $response->assertRedirect();
    $document->refresh();
    expect($document->status)->toBe('effective');
    expect($document->effective_date->format('Y-m-d'))->toBe($effectiveDate);
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without document.control.view gets 403 on list

```php
test('user without document.control.view gets 403 on list', function () {
    $reporter = $this->reporterUser();
    // Employee / Reporter role may not have document.control.view in default roleMap
    // If it does, use a user with no roles
    $this->actingAs($reporter);

    $response = $this->get(route('document.control.index'));

    $response->assertForbidden();
});
```

### 2.2 User without document.control.create gets 403 on create form

```php
test('user without document.control.create gets 403 on create form', function () {
    $auditor = $this->auditorUser();
    // Auditor only has view + export
    $this->actingAs($auditor);

    $response = $this->get(route('document.control.create'));

    $response->assertForbidden();
});
```

### 2.3 QHSSE Officer cannot approve document

```php
test('qhsse officer cannot approve document', function () {
    $officer = $this->qhsseOfficer();
    // QHSSE Officer has create, update, submit_review but NOT approve
    $this->actingAs($officer);

    $document = ControlledDocument::factory()->create(['status' => 'review']);

    $response = $this->post(route('document.control.approve', $document), [
        'review_notes' => 'Approved by officer',
    ]);

    $response->assertForbidden();
});
```

### 2.4 Export blocked without document.control.export

```php
test('export blocked without document.control.export', function () {
    $reporter = $this->reporterUser();
    $this->actingAs($reporter);

    $response = $this->get(route('document.control.export'));

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 File can be attached to document

```php
test('file can be attached to document', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $document = ControlledDocument::factory()->create();

    $file = UploadedFile::fake()->create('SOP_Penggunaan_APD.pdf', 1024, 'application/pdf');

    $response = $this->post('/core/files', [
        'file' => $file,
        'module_name' => 'document',
        'reference_id' => $document->id,
        'collection' => 'document_file',
    ]);

    $response->assertStatus(200); // or 201

    expect(
        ManagedFile::where('module_name', 'document')
            ->where('reference_id', $document->id)
            ->where('collection', 'document_file')
            ->count()
    )->toBe(1);
});
```

### 3.2 Audit trail records document creation

```php
test('audit trail records document creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $this->post(route('document.control.store'), [
        'title' => 'Audited document',
        'type' => 'sop',
        'version' => '1.0',
        'action' => 'draft',
    ]);

    $document = ControlledDocument::first();

    expect(
        AuditLog::where('module_name', 'document')
            ->where('reference_id', $document->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Submit review creates document_reviews record

```php
test('submit review creates document_reviews record', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $document = ControlledDocument::factory()->create([
        'status' => 'draft',
        'owner_id' => $admin->id,
    ]);
    WorkflowService::start('document', $document->id, $admin);

    // Attach file
    ManagedFile::factory()->create([
        'module_name' => 'document',
        'reference_id' => $document->id,
        'collection' => 'document_file',
    ]);

    $this->post(route('document.control.submitReview', $document), [
        'review_notes' => 'Ready for review.',
    ]);

    expect(
        DocumentReview::where('document_id', $document->id)
            ->where('decision', 'pending')
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Notification created on submit review

```php
test('notification created on document submit review', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create a QHSSE Manager to receive notification
    $manager = $this->qhsseManager();

    $document = ControlledDocument::factory()->create([
        'status' => 'draft',
        'owner_id' => $admin->id,
    ]);
    WorkflowService::start('document', $document->id, $admin);

    ManagedFile::factory()->create([
        'module_name' => 'document',
        'reference_id' => $document->id,
        'collection' => 'document_file',
    ]);

    $this->post(route('document.control.submitReview', $document));

    expect(
        CoreNotification::where('type', 'document.submitted')
            ->where('module_name', 'document')
            ->where('reference_id', $document->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.5 Obsolete requires reason and creates audit trail

```php
test('obsolete requires reason and creates audit trail', function () {
    $manager = $this->qhsseManager();
    $this->actingAs($manager);

    $document = ControlledDocument::factory()->create(['status' => 'effective']);
    WorkflowService::start('document', $document->id, $manager);
    WorkflowInstance::where('module_name', 'document')
        ->where('reference_id', $document->id)
        ->update(['current_status' => 'effective']);

    // Without reason — should fail
    $response = $this->post(route('document.control.obsolete', $document), []);
    $response->assertSessionHasErrors(['reason']);

    // With reason
    $response = $this->post(route('document.control.obsolete', $document), [
        'reason' => 'Dokumen sudah tidak relevan dengan proses terbaru.',
    ]);

    $response->assertRedirect();
    $document->refresh();
    expect($document->status)->toBe('obsolete');

    expect(
        AuditLog::where('module_name', 'document')
            ->where('reference_id', $document->id)
            ->where('event', 'document.obsolete')
            ->exists()
    )->toBeTrue();
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot submit review for non-draft document

```php
test('cannot submit review for non-draft document', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $document = ControlledDocument::factory()->create(['status' => 'effective']);

    $response = $this->post(route('document.control.submitReview', $document));

    $response->assertSessionHasErrors(['workflow']);
    expect($document->fresh()->status)->toBe('effective');
});
```

### 4.2 Reject without reason fails validation

```php
test('reject without reason fails validation', function () {
    $manager = $this->qhsseManager();
    $this->actingAs($manager);

    $document = ControlledDocument::factory()->create(['status' => 'review']);
    WorkflowService::start('document', $document->id, $manager);
    WorkflowInstance::where('module_name', 'document')
        ->where('reference_id', $document->id)
        ->update(['current_status' => 'review']);

    $response = $this->post(route('document.control.reject', $document), []);

    $response->assertSessionHasErrors(['reason']);
});
```

### 4.3 Duplicate document_number cannot occur via numbering service

```php
test('duplicate document_number cannot occur via numbering service', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create first document
    $this->post(route('document.control.store'), [
        'title' => 'First document',
        'type' => 'sop',
        'version' => '1.0',
        'action' => 'draft',
    ]);

    // Create second document
    $this->post(route('document.control.store'), [
        'title' => 'Second document',
        'type' => 'wi',
        'version' => '1.0',
        'action' => 'draft',
    ]);

    $numbers = ControlledDocument::pluck('document_number')->toArray();
    expect(count($numbers))->toBe(2);
    expect(count(array_unique($numbers)))->toBe(2); // All unique
});
```

---

## Test Execution

```bash
# Run all tests
php artisan test

# Run only document control tests
php artisan test --filter=Document

# Run with parallel (faster)
php artisan test --parallel

# Run with coverage
php artisan test --coverage
```

### Expected result after Phase 7:

```
Tests: 20 passed (Phase 7 Document Control)
```

---

## Notes

- Tests use SQLite in-memory for speed and isolation
- Each test starts with a fresh database (Laravel's `RefreshDatabase` trait is auto-applied via Pest)
- Factory creates minimal valid data; tests add specific fields as needed
- `WorkflowService::start()` must be called after creating a ControlledDocument in tests that need workflow transitions
- For tests that need a specific status, manually update the `workflow_instances.current_status` field
- `CorePermissions::roleMap()` defines which permissions each role gets — tests assume this is seeded
- Notification tests require at least one QHSSE Manager user to exist for `notifyMany` to send to
- File upload tests use `UploadedFile::fake()->create()` to simulate PDF/DOCX files
- The `document.control.approve` permission is used for both approve AND reject actions (same permission gate)

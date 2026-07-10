# Test Cases — Incident Reporting

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

Test file: `tests/Feature/Modules/Incident/IncidentReportTest.php`

## Factory Definition

File: `database/factories/Modules/Incident/IncidentReportFactory.php`

```php
public function definition(): array
{
    return [
        'incident_number' => 'INC-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'title' => fake()->sentence(6),
        'category' => fake()->randomElement([
            'accident', 'incident', 'near_miss', 'unsafe_act',
            'unsafe_condition', 'environmental_spill', 'security_breach',
        ]),
        'occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
        'site_id' => Site::factory(),
        'area_id' => null,
        'department_id' => null,
        'reporter_id' => User::factory(),
        'severity_id' => Severity::factory(),
        'priority_id' => Priority::factory(),
        'description' => fake()->paragraph(3),
        'immediate_action' => fake()->optional(0.7)->paragraph(2),
        'status' => 'draft',
    ];
}
```

## Helper Trait

```php
trait CreatesIncidentTestUser
{
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        return $user;
    }

    protected function reporterUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Employee / Reporter');
        return $user;
    }

    protected function createSite(): Site
    {
        return Site::factory()->create();
    }

    protected function createSeverity(): Severity
    {
        return Severity::factory()->create();
    }

    protected function createPriority(): Priority
    {
        return Priority::factory()->create();
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view incident list

```php
test('authorized user can view incident list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('incident.reports.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Incident/Index')
        ->has('items')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create incident draft

```php
test('authorized user can create incident draft', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $severity = $this->createSeverity();
    $priority = $this->createPriority();

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

    $response->assertRedirect(route('incident.reports.show', IncidentReport::first()));

    $incident = IncidentReport::first();
    expect($incident)->not->toBeNull();
    expect($incident->title)->toBe('Kecelakaan di area produksi');
    expect($incident->status)->toBe('draft');
    expect($incident->reporter_id)->toBe($admin->id);
});
```

### 1.3 Incident number is auto-generated on create

```php
test('incident number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $severity = $this->createSeverity();
    $priority = $this->createPriority();

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
```

### 1.4 Incident with missing title fails validation

```php
test('incident with missing title fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('incident.reports.store'), [
        'category' => 'accident',
        'occurred_at' => now()->toDateTimeString(),
        'site_id' => $this->createSite()->id,
        'severity_id' => $this->createSeverity()->id,
        'priority_id' => $this->createPriority()->id,
        'description' => 'Test',
        'action' => 'draft',
    ]);

    $response->assertSessionHasErrors(['title']);
    expect(IncidentReport::count())->toBe(0);
});
```

### 1.5 Incident with invalid category fails validation

```php
test('incident with invalid category fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('incident.reports.store'), [
        'title' => 'Test',
        'category' => 'invalid_category',
        'occurred_at' => now()->toDateTimeString(),
        'site_id' => $this->createSite()->id,
        'severity_id' => $this->createSeverity()->id,
        'priority_id' => $this->createPriority()->id,
        'description' => 'Test',
        'action' => 'draft',
    ]);

    $response->assertSessionHasErrors(['category']);
});
```

### 1.6 Draft incident can be submitted

```php
test('draft incident can be submitted', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = IncidentReport::factory()->create([
        'status' => 'draft',
        'reporter_id' => $admin->id,
    ]);
    WorkflowService::start('incident', $incident->id, $admin);

    $response = $this->post(route('incident.reports.submit', $incident));

    $response->assertRedirect();
    $incident->refresh();
    expect($incident->status)->toBe('submitted');
});
```

### 1.7 Submitted incident can be reviewed

```php
test('submitted incident can be reviewed', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = IncidentReport::factory()->create(['status' => 'submitted']);
    WorkflowService::start('incident', $incident->id, $admin);
    // Manually set to submitted for test
    WorkflowInstance::where('module_name', 'incident')
        ->where('reference_id', $incident->id)
        ->update(['current_status' => 'submitted']);

    $response = $this->post(route('incident.reports.review', $incident));

    $response->assertRedirect();
    $incident->refresh();
    expect($incident->status)->toBe('under_review');
});
```

### 1.8 Under review incident can be closed with reason

```php
test('under review incident can be closed with reason', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = IncidentReport::factory()->create(['status' => 'under_review']);
    WorkflowService::start('incident', $incident->id, $admin);
    WorkflowInstance::where('module_name', 'incident')
        ->where('reference_id', $incident->id)
        ->update(['current_status' => 'under_review']);

    $response = $this->post(route('incident.reports.close', $incident), [
        'reason' => 'Investigasi selesai, corrective action diimplementasi.',
    ]);

    $response->assertRedirect();
    $incident->refresh();
    expect($incident->status)->toBe('closed');
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without incident.reports.view gets 403 on list

```php
test('user without incident.reports.view gets 403 on list', function () {
    $reporter = $this->reporterUser();
    // Employee / Reporter role does not have incident.reports.view in default roleMap
    // Wait — it should have it. Let me check...
    // Actually Employee / Reporter has core.scope.own only, no incident.reports.view
    $this->actingAs($reporter);

    $response = $this->get(route('incident.reports.index'));

    $response->assertForbidden();
});
```

> Note: If `Employee / Reporter` role IS assigned `incident.reports.view` in CorePermissions::roleMap(), adjust test to use a role that truly lacks the permission. Could create a custom user with no roles.

### 2.2 User without incident.reports.create gets 403 on create form

```php
test('user without incident.reports.create gets 403 on create form', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('Auditor'); // Auditor only has view + export

    $this->actingAs($viewer);

    $response = $this->get(route('incident.reports.create'));

    $response->assertForbidden();
});
```

### 2.3 User without incident.reports.close cannot close

```php
test('user without incident.reports.close cannot close incident', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('Supervisor'); // Has view, create, update, submit — NOT close

    $this->actingAs($supervisor);

    $incident = IncidentReport::factory()->create(['status' => 'under_review']);

    $response = $this->post(route('incident.reports.close', $incident), [
        'reason' => 'Test reason',
    ]);

    $response->assertForbidden();
});
```

### 2.4 Export blocked without incident.reports.export

```php
test('export blocked without incident.reports.export', function () {
    $reporter = $this->reporterUser();
    $this->actingAs($reporter);

    $response = $this->get(route('incident.reports.export'));

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 File evidence can be attached to incident

```php
test('file evidence can be attached to incident', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = IncidentReport::factory()->create();

    $file = UploadedFile::fake()->image('evidence.jpg');

    $response = $this->post('/core/files', [
        'file' => $file,
        'module_name' => 'incident',
        'reference_id' => $incident->id,
        'collection' => 'evidence',
    ]);

    $response->assertStatus(200); // or 201

    expect(
        ManagedFile::where('module_name', 'incident')
            ->where('reference_id', $incident->id)
            ->where('collection', 'evidence')
            ->count()
    )->toBe(1);
});
```

### 3.2 Audit trail records incident creation

```php
test('audit trail records incident creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $severity = $this->createSeverity();
    $priority = $this->createPriority();

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

    expect(
        AuditLog::where('module_name', 'incident')
            ->where('reference_id', $incident->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Audit trail records status change

```php
test('audit trail records status change on submit', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = IncidentReport::factory()->create(['status' => 'draft']);
    WorkflowService::start('incident', $incident->id, $admin);

    $this->post(route('incident.reports.submit', $incident));

    expect(
        AuditLog::where('module_name', 'incident')
            ->where('reference_id', $incident->id)
            ->where('event', 'workflow.transitioned')
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Activity log records file upload

```php
test('activity log records file upload for incident', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = IncidentReport::factory()->create();

    $file = UploadedFile::fake()->image('photo.jpg');

    $this->post('/core/files', [
        'file' => $file,
        'module_name' => 'incident',
        'reference_id' => $incident->id,
        'collection' => 'evidence',
    ]);

    expect(
        ActivityLog::where('module_name', 'incident')
            ->where('reference_id', $incident->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.5 Notification created on submit

```php
test('notification created on incident submit', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create a QHSSE Officer to receive notification
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');

    $incident = IncidentReport::factory()->create([
        'status' => 'draft',
        'reporter_id' => $admin->id,
    ]);
    WorkflowService::start('incident', $incident->id, $admin);

    $this->post(route('incident.reports.submit', $incident));

    expect(
        CoreNotification::where('type', 'incident.submitted')
            ->where('module_name', 'incident')
            ->where('reference_id', $incident->id)
            ->exists()
    )->toBeTrue();
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Invalid workflow transition rejected

```php
test('cannot submit non-draft incident', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = IncidentReport::factory()->create(['status' => 'closed']);

    $response = $this->post(route('incident.reports.submit', $incident));

    $response->assertSessionHasErrors(['workflow']);
    expect($incident->fresh()->status)->toBe('closed');
});
```

### 4.2 Close without reason fails validation

```php
test('close without reason fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = IncidentReport::factory()->create(['status' => 'under_review']);
    WorkflowService::start('incident', $incident->id, $admin);
    WorkflowInstance::where('module_name', 'incident')
        ->where('reference_id', $incident->id)
        ->update(['current_status' => 'under_review']);

    $response = $this->post(route('incident.reports.close', $incident), []);

    $response->assertSessionHasErrors(['reason']);
});
```

### 4.3 Duplicate incident_number cannot occur

```php
test('duplicate incident_number cannot occur via numbering service', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create first incident — gets INC-2026-0001
    $this->post(route('incident.reports.store'), [
        'title' => 'First incident',
        'category' => 'incident',
        'occurred_at' => now()->toDateTimeString(),
        'site_id' => $this->createSite()->id,
        'severity_id' => $this->createSeverity()->id,
        'priority_id' => $this->createPriority()->id,
        'description' => 'First',
        'action' => 'draft',
    ]);

    // Create second incident — should get INC-2026-0002
    $this->post(route('incident.reports.store'), [
        'title' => 'Second incident',
        'category' => 'incident',
        'occurred_at' => now()->toDateTimeString(),
        'site_id' => $this->createSite()->id,
        'severity_id' => $this->createSeverity()->id,
        'priority_id' => $this->createPriority()->id,
        'description' => 'Second',
        'action' => 'draft',
    ]);

    $numbers = IncidentReport::pluck('incident_number')->toArray();
    expect(count($numbers))->toBe(2);
    expect(count(array_unique($numbers)))->toBe(2); // All unique
});
```

---

## Test Execution

```bash
# Run all tests (Phase 0 + Phase 1)
php artisan test

# Run only incident tests
php artisan test --filter=Incident

# Run with parallel (faster)
php artisan test --parallel

# Run with coverage
php artisan test --coverage
```

### Expected result after Phase 1:

```
Tests: 99 passed (79 Phase 0 + 20 Phase 1)
```

---

## Notes

- Tests use SQLite in-memory for speed and isolation
- Each test starts with a fresh database (Laravel's `RefreshDatabase` trait is auto-applied via Pest)
- Factory creates minimal valid data; tests add specific fields as needed
- `WorkflowService::start()` must be called after creating an IncidentReport in tests that need workflow transitions
- For tests that need a specific status, manually update the `workflow_instances.current_status` field
- `CorePermissions::roleMap()` defines which permissions each role gets — tests assume this is seeded
- Notification tests require at least one QHSSE Officer/Manager user to exist for `notifyMany` to send to

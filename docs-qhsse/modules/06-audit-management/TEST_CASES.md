# Test Cases — Audit Management

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

Test file: `tests/Feature/Modules/Audit/AuditManagementTest.php`

## Factory Definition

File: `database/factories/Modules/Audit/AuditFactory.php`

```php
public function definition(): array
{
    return [
        'audit_number' => 'AUD-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'title' => fake()->sentence(6),
        'type' => fake()->randomElement(['internal', 'external', 'supplier']),
        'standard' => fake()->optional(0.7)->randomElement([
            'ISO 45001:2018', 'ISO 9001:2015', 'ISO 14001:2015', 'SMK3',
        ]),
        'scope' => fake()->paragraph(2),
        'site_id' => Site::factory(),
        'department_id' => null,
        'lead_auditor_id' => User::factory(),
        'start_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
        'end_date' => fake()->optional(0.6)->dateTimeBetween('+1 month', '+2 months')->format('Y-m-d'),
        'status' => 'planned',
        'summary' => null,
    ];
}
```

File: `database/factories/Modules/Audit/AuditFindingFactory.php`

```php
public function definition(): array
{
    return [
        'audit_id' => Audit::factory(),
        'finding_number' => 'AUD-' . now()->year . '-0001-F01',
        'description' => fake()->paragraph(3),
        'classification' => fake()->randomElement(['major', 'minor', 'observation', 'ofi']),
        'area' => fake()->optional(0.7)->sentence(3),
        'recommendation' => fake()->optional(0.5)->paragraph(2),
        'capa_action_id' => null,
        'status' => 'open',
    ];
}
```

## Helper Trait

```php
trait CreatesAuditTestUser
{
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        return $user;
    }

    protected function qhsseOfficer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Officer');
        return $user;
    }

    protected function viewerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Auditor');
        return $user;
    }

    protected function noRoleUser(): User
    {
        return User::factory()->create();
    }

    protected function createSite(): Site
    {
        return Site::factory()->create();
    }

    protected function createDepartment(): Department
    {
        return Department::factory()->create();
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view audit list

```php
test('authorized user can view audit list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('audits.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Audit/Index')
        ->has('audits')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create audit

```php
test('authorized user can create audit', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $auditor = User::factory()->create();

    $response = $this->post(route('audits.store'), [
        'title' => 'Audit Internal QHSSE Q3 2026',
        'type' => 'internal',
        'standard' => 'ISO 45001:2018',
        'scope' => 'Audit mencakup sistem manajemen K3 di area produksi.',
        'site_id' => $site->id,
        'department_id' => null,
        'lead_auditor_id' => $auditor->id,
        'start_date' => '2026-07-15',
        'end_date' => '2026-07-17',
    ]);

    $response->assertRedirect(route('audits.show', Audit::first()));

    $audit = Audit::first();
    expect($audit)->not->toBeNull();
    expect($audit->title)->toBe('Audit Internal QHSSE Q3 2026');
    expect($audit->status)->toBe('planned');
    expect($audit->type)->toBe('internal');
    expect($audit->standard)->toBe('ISO 45001:2018');
});
```

### 1.3 Audit number is auto-generated on create

```php
test('audit number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $auditor = User::factory()->create();

    $this->post(route('audits.store'), [
        'title' => 'Test Audit',
        'type' => 'internal',
        'scope' => 'Test scope for audit numbering',
        'site_id' => $site->id,
        'lead_auditor_id' => $auditor->id,
        'start_date' => '2026-07-15',
    ]);

    $audit = Audit::first();
    expect($audit->audit_number)->not->toBeNull();
    expect($audit->audit_number)->toMatch('/^AUD-\d{4}-\d{4}$/');
});
```

### 1.4 Audit with missing title fails validation

```php
test('audit with missing title fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $auditor = User::factory()->create();

    $response = $this->post(route('audits.store'), [
        'type' => 'internal',
        'scope' => 'Test scope',
        'site_id' => $site->id,
        'lead_auditor_id' => $auditor->id,
        'start_date' => '2026-07-15',
    ]);

    $response->assertSessionHasErrors(['title']);
    expect(Audit::count())->toBe(0);
});
```

### 1.5 Audit with invalid type fails validation

```php
test('audit with invalid type fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $auditor = User::factory()->create();

    $response = $this->post(route('audits.store'), [
        'title' => 'Test Audit',
        'type' => 'invalid_type',
        'scope' => 'Test scope for validation',
        'site_id' => $site->id,
        'lead_auditor_id' => $auditor->id,
        'start_date' => '2026-07-15',
    ]);

    $response->assertSessionHasErrors(['type']);
});
```

### 1.6 Planned audit can be started

```php
test('planned audit can be started', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $audit = Audit::factory()->create(['status' => 'planned']);
    WorkflowService::start('audit', $audit->id, $admin);

    $response = $this->post(route('audits.start', $audit));

    $response->assertRedirect();
    $audit->refresh();
    expect($audit->status)->toBe('in_progress');
});
```

### 1.7 In progress audit can generate report with summary

```php
test('in progress audit can generate report with summary', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $audit = Audit::factory()->create(['status' => 'in_progress']);
    WorkflowService::start('audit', $audit->id, $admin);
    WorkflowInstance::where('module_name', 'audit')
        ->where('reference_id', $audit->id)
        ->update(['current_status' => 'in_progress']);

    $response = $this->post(route('audits.generateReport', $audit), [
        'summary' => 'Audit telah dilaksanakan selama 3 hari. Ditemukan 3 temuan dengan berbagai klasifikasi.',
    ]);

    $response->assertRedirect();
    $audit->refresh();
    expect($audit->status)->toBe('report_ready');
    expect($audit->summary)->not->toBeNull();
    expect(strlen($audit->summary))->toBeGreaterThanOrEqual(20);
});
```

### 1.8 Report ready audit can be closed when all findings resolved

```php
test('report ready audit can be closed when all findings resolved', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $audit = Audit::factory()->create(['status' => 'report_ready']);
    WorkflowService::start('audit', $audit->id, $admin);
    WorkflowInstance::where('module_name', 'audit')
        ->where('reference_id', $audit->id)
        ->update(['current_status' => 'report_ready']);

    // Create a Major finding with CAPA linked and closed
    $capa = CapaAction::factory()->create();
    AuditFinding::factory()->create([
        'audit_id' => $audit->id,
        'classification' => 'major',
        'capa_action_id' => $capa->id,
        'status' => 'closed',
    ]);

    $response = $this->post(route('audits.close', $audit));

    $response->assertRedirect();
    $audit->refresh();
    expect($audit->status)->toBe('closed');
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without audit.management.view gets 403 on list

```php
test('user without audit.management.view gets 403 on list', function () {
    $noRole = $this->noRoleUser();
    $this->actingAs($noRole);

    $response = $this->get(route('audits.index'));

    $response->assertForbidden();
});
```

### 2.2 User without audit.management.create gets 403 on create form

```php
test('user without audit.management.create gets 403 on create form', function () {
    $viewer = $this->viewerUser(); // Auditor role: view + export only
    $this->actingAs($viewer);

    $response = $this->get(route('audits.create'));

    $response->assertForbidden();
});
```

### 2.3 User without audit.management.execute cannot start audit

```php
test('user without audit.management.execute cannot start audit', function () {
    $viewer = $this->viewerUser(); // Auditor: no execute permission
    $this->actingAs($viewer);

    $audit = Audit::factory()->create(['status' => 'planned']);

    $response = $this->post(route('audits.start', $audit));

    $response->assertForbidden();
});
```

### 2.4 User without audit.findings.create cannot create finding

```php
test('user without audit.findings.create cannot create finding', function () {
    $viewer = $this->viewerUser(); // Auditor: no findings.create
    $this->actingAs($viewer);

    $audit = Audit::factory()->create(['status' => 'in_progress']);

    $response = $this->post(route('audits.findings.store', $audit), [
        'description' => 'Test finding description here.',
        'classification' => 'minor',
    ]);

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Finding can be created for an audit

```php
test('finding can be created for an audit', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $audit = Audit::factory()->create(['status' => 'in_progress']);

    $response = $this->post(route('audits.findings.store', $audit), [
        'description' => 'Prosedur LOTO tidak diimplementasikan di area mesin produksi.',
        'classification' => 'major',
        'area' => 'Produksi — Mesin CNC',
        'recommendation' => 'Implementasi prosedur LOTO untuk semua mesin.',
    ]);

    $response->assertRedirect();
    $finding = AuditFinding::where('audit_id', $audit->id)->first();
    expect($finding)->not->toBeNull();
    expect($finding->classification)->toBe('major');
    expect($finding->status)->toBe('open');
    expect($finding->finding_number)->toMatch('/^AUD-\d{4}-\d{4}-F\d{2}$/');
});
```

### 3.2 Audit trail records audit creation

```php
test('audit trail records audit creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $auditor = User::factory()->create();

    $this->post(route('audits.store'), [
        'title' => 'Audit for trail test',
        'type' => 'internal',
        'scope' => 'Testing audit trail on creation',
        'site_id' => $site->id,
        'lead_auditor_id' => $auditor->id,
        'start_date' => '2026-07-15',
    ]);

    $audit = Audit::first();

    expect(
        AuditLog::where('module_name', 'audit')
            ->where('reference_id', $audit->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Audit trail records finding creation

```php
test('audit trail records finding creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $audit = Audit::factory()->create(['status' => 'in_progress']);

    $this->post(route('audits.findings.store', $audit), [
        'description' => 'Test finding for audit trail verification.',
        'classification' => 'minor',
    ]);

    expect(
        AuditLog::where('module_name', 'audit')
            ->where('reference_id', $audit->id)
            ->where('event', 'finding.created')
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Notification created on audit start

```php
test('notification created on audit start', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create a Department Head to receive notification
    $deptHead = User::factory()->create();
    $deptHead->assignRole('Department Head');

    $audit = Audit::factory()->create(['status' => 'planned']);
    WorkflowService::start('audit', $audit->id, $admin);

    $this->post(route('audits.start', $audit));

    expect(
        CoreNotification::where('type', 'audit.started')
            ->where('module_name', 'audit')
            ->where('reference_id', $audit->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.5 Finding can link to CAPA and unlink

```php
test('finding can link to CAPA and unlink', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $audit = Audit::factory()->create(['status' => 'in_progress']);
    $finding = AuditFinding::factory()->create([
        'audit_id' => $audit->id,
        'classification' => 'major',
        'capa_action_id' => null,
    ]);
    $capa = CapaAction::factory()->create();

    // Link CAPA
    $response = $this->post(route('audits.findings.linkCapa', [$audit, $finding]), [
        'capa_action_id' => $capa->id,
    ]);

    $response->assertRedirect();
    $finding->refresh();
    expect($finding->capa_action_id)->toBe($capa->id);

    // Unlink CAPA
    $response = $this->delete(route('audits.findings.unlinkCapa', [$audit, $finding]));
    $response->assertRedirect();
    $finding->refresh();
    expect($finding->capa_action_id)->toBeNull();
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot start non-planned audit

```php
test('cannot start non-planned audit', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $audit = Audit::factory()->create(['status' => 'closed']);
    WorkflowService::start('audit', $audit->id, $admin);
    WorkflowInstance::where('module_name', 'audit')
        ->where('reference_id', $audit->id)
        ->update(['current_status' => 'closed']);

    $response = $this->post(route('audits.start', $audit));

    $response->assertSessionHasErrors(['workflow']);
    expect($audit->fresh()->status)->toBe('closed');
});
```

### 4.2 Cannot generate report without summary

```php
test('cannot generate report without summary', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $audit = Audit::factory()->create(['status' => 'in_progress']);
    WorkflowService::start('audit', $audit->id, $admin);
    WorkflowInstance::where('module_name', 'audit')
        ->where('reference_id', $audit->id)
        ->update(['current_status' => 'in_progress']);

    $response = $this->post(route('audits.generateReport', $audit), []);

    $response->assertSessionHasErrors(['summary']);
    $audit->refresh();
    expect($audit->status)->toBe('in_progress');
});
```

### 4.3 Cannot close audit with open findings

```php
test('cannot close audit with open findings', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $audit = Audit::factory()->create(['status' => 'report_ready']);
    WorkflowService::start('audit', $audit->id, $admin);
    WorkflowInstance::where('module_name', 'audit')
        ->where('reference_id', $audit->id)
        ->update(['current_status' => 'report_ready']);

    // Create an open finding (not closed)
    AuditFinding::factory()->create([
        'audit_id' => $audit->id,
        'classification' => 'minor',
        'status' => 'open',
    ]);

    $response = $this->post(route('audits.close', $audit));

    $response->assertSessionHasErrors(['close']);
    $audit->refresh();
    expect($audit->status)->toBe('report_ready');
});
```

---

## Test Execution

```bash
# Run all tests
php artisan test

# Run only audit management tests
php artisan test --filter=AuditManagement

# Run with parallel
php artisan test --parallel

# Run with coverage
php artisan test --coverage
```

### Expected test count:

```
Tests: 20 passed (for Audit Management module)
```

| Category | Count | Tests |
|---|---|---|
| Functional | 8 | 1.1–1.8 |
| Permission | 4 | 2.1–2.4 |
| Integration | 5 | 3.1–3.5 |
| Negative | 3 | 4.1–4.3 |
| **Total** | **20** | |

---

## Notes

- Tests use SQLite in-memory for speed and isolation.
- Each test starts with a fresh database (Laravel's `RefreshDatabase` trait is auto-applied via Pest).
- Factory creates minimal valid data; tests add specific fields as needed.
- `WorkflowService::start()` must be called after creating an Audit in tests that need workflow transitions.
- For tests that need a specific status, manually update the `workflow_instances.current_status` field.
- `CorePermissions::roleMap()` defines which permissions each role gets — tests assume this is seeded.
- Notification tests require at least one recipient user with the target role to exist.
- CAPA integration tests require the `capa_actions` table to exist (Phase 4 dependency).
- Finding number format: `{audit_number}-F{NN}` (e.g., `AUD-2026-0001-F01`).

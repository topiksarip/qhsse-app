# Test Cases — Risk Management (HIRADC/JSA)

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

Test file: `tests/Feature/Modules/RiskManagement/RiskRegisterTest.php`

## Factory Definition

File: `database/factories/Modules/RiskManagement/RiskRegisterFactory.php`

```php
public function definition(): array
{
    return [
        'register_number' => 'RSK-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'title' => fake()->sentence(6),
        'type' => fake()->randomElement([
            'hazard_identification', 'jsa', 'hiradc', 'risk_assessment',
        ]),
        'site_id' => Site::factory(),
        'area_id' => null,
        'department_id' => null,
        'activity' => fake()->sentence(4),
        'hazard' => fake()->paragraph(2),
        'existing_controls' => fake()->optional(0.7)->paragraph(2),
        'severity_id' => null,
        'probability_id' => null,
        'risk_level_id' => null,
        'additional_controls' => null,
        'residual_severity_id' => null,
        'residual_probability_id' => null,
        'residual_risk_level_id' => null,
        'owner_id' => User::factory(),
        'status' => 'identified',
        'review_date' => fake()->optional()->dateTimeBetween('+1 month', '+6 months'),
    ];
}
```

## Helper Trait

```php
trait CreatesRiskTestUser
{
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        return $user;
    }

    protected function officerUser(): User
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

    protected function createSite(): Site
    {
        return Site::factory()->create();
    }

    protected function createSeverity(): Severity
    {
        return Severity::factory()->create();
    }

    protected function createRiskMatrixLevel(int $severityLevel, int $probabilityLevel, string $riskLevel): RiskMatrixLevel
    {
        return RiskMatrixLevel::factory()->create([
            'severity_level' => $severityLevel,
            'probability_level' => $probabilityLevel,
            'risk_level' => $riskLevel,
            'is_active' => true,
        ]);
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view risk register list

```php
test('authorized user can view risk register list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('risk.registers.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/RiskManagement/Index')
        ->has('items')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create risk register

```php
test('authorized user can create risk register', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $response = $this->post(route('risk.registers.store'), [
        'title' => 'Risiko Jatuh dari Ketinggian',
        'type' => 'hiradc',
        'site_id' => $site->id,
        'activity' => 'Bekerja di atas scaffolding',
        'hazard' => 'Jatuh dari ketinggian tanpa harness',
        'existing_controls' => 'Guard rail di scaffolding',
        'owner_id' => $admin->id,
    ]);

    $response->assertRedirect(route('risk.registers.show', RiskRegister::first()));

    $register = RiskRegister::first();
    expect($register)->not->toBeNull();
    expect($register->title)->toBe('Risiko Jatuh dari Ketinggian');
    expect($register->status)->toBe('identified');
    expect($register->owner_id)->toBe($admin->id);
});
```

### 1.3 Register number is auto-generated on create

```php
test('register number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('risk.registers.store'), [
        'title' => 'Test risk register',
        'type' => 'risk_assessment',
        'site_id' => $site->id,
        'activity' => 'Test activity',
        'hazard' => 'Test hazard',
        'owner_id' => $admin->id,
    ]);

    $register = RiskRegister::first();
    expect($register->register_number)->not->toBeNull();
    expect($register->register_number)->toMatch('/^RSK-\d{4}-\d{4}$/');
});
```

### 1.4 Risk register with missing title fails validation

```php
test('risk register with missing title fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('risk.registers.store'), [
        'type' => 'hiradc',
        'site_id' => $this->createSite()->id,
        'activity' => 'Test activity',
        'hazard' => 'Test hazard',
        'owner_id' => $admin->id,
    ]);

    $response->assertSessionHasErrors(['title']);
    expect(RiskRegister::count())->toBe(0);
});
```

### 1.5 Risk register with invalid type fails validation

```php
test('risk register with invalid type fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('risk.registers.store'), [
        'title' => 'Test risk register',
        'type' => 'invalid_type',
        'site_id' => $this->createSite()->id,
        'activity' => 'Test activity',
        'hazard' => 'Test hazard',
        'owner_id' => $admin->id,
    ]);

    $response->assertSessionHasErrors(['type']);
});
```

### 1.6 Risk register can be assessed with severity and probability

```php
test('risk register can be assessed with severity and probability', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $severity = Severity::factory()->create(['level' => 4]); // CRITICAL
    $matrixLevel = $this->createRiskMatrixLevel(4, 4, 'RED');

    $register = RiskRegister::factory()->create([
        'status' => 'identified',
        'site_id' => $site->id,
        'owner_id' => $admin->id,
    ]);

    $response = $this->post(route('risk.registers.assess', $register), [
        'severity_id' => $severity->id,
        'probability_id' => 4,
        'risk_level_id' => $matrixLevel->id,
    ]);

    $response->assertRedirect();
    $register->refresh();
    expect($register->status)->toBe('assessed');
    expect($register->severity_id)->toBe($severity->id);
    expect($register->probability_id)->toBe(4);
    expect($register->risk_level_id)->toBe($matrixLevel->id);
});
```

### 1.7 Assessed register can transition to controls_needed

```php
test('assessed register can transition to controls_needed', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = RiskRegister::factory()->create([
        'status' => 'assessed',
        'owner_id' => $admin->id,
    ]);

    $response = $this->post(route('risk.registers.needs_controls', $register));

    $response->assertRedirect();
    $register->refresh();
    expect($register->status)->toBe('controls_needed');
});
```

### 1.8 Controls needed register can implement controls

```php
test('controls needed register can implement controls', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = RiskRegister::factory()->create([
        'status' => 'controls_needed',
        'owner_id' => $admin->id,
        'additional_controls' => null,
    ]);

    $response = $this->post(route('risk.registers.implement_controls', $register), [
        'additional_controls' => "1. Wajib pakai full body harness\n2. Inspection scaffolding sebelum digunakan",
    ]);

    $response->assertRedirect();
    $register->refresh();
    expect($register->status)->toBe('controls_in_place');
    expect($register->additional_controls)->toContain('Wajib pakai full body harness');
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without risk.registers.view gets 403 on list

```php
test('user without risk.registers.view gets 403 on list', function () {
    $viewer = User::factory()->create();
    // No role assigned — no permissions
    $this->actingAs($viewer);

    $response = $this->get(route('risk.registers.index'));

    $response->assertForbidden();
});
```

### 2.2 User without risk.registers.create gets 403 on create form

```php
test('user without risk.registers.create gets 403 on create form', function () {
    $viewer = $this->viewerUser(); // Auditor: view + export only
    $this->actingAs($viewer);

    $response = $this->get(route('risk.registers.create'));

    $response->assertForbidden();
});
```

### 2.3 User without risk.registers.assess cannot assess

```php
test('user without risk.registers.assess cannot assess', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('Supervisor'); // Has view, create, update — NOT assess

    $this->actingAs($supervisor);

    $register = RiskRegister::factory()->create([
        'status' => 'identified',
        'owner_id' => $supervisor->id,
    ]);

    $severity = Severity::factory()->create(['level' => 3]);
    $matrixLevel = $this->createRiskMatrixLevel(3, 3, 'ORANGE');

    $response = $this->post(route('risk.registers.assess', $register), [
        'severity_id' => $severity->id,
        'probability_id' => 3,
        'risk_level_id' => $matrixLevel->id,
    ]);

    $response->assertForbidden();
});
```

### 2.4 Export blocked without risk.registers.export

```php
test('export blocked without risk.registers.export', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('Employee / Reporter');

    $this->actingAs($viewer);

    $response = $this->get(route('risk.registers.export'));

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Risk level lookup from risk_matrix_levels works

```php
test('risk level lookup from risk_matrix_levels works', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $severity = Severity::factory()->create(['level' => 3]); // HIGH
    $matrixLevel = $this->createRiskMatrixLevel(3, 4, 'RED');

    $register = RiskRegister::factory()->create([
        'status' => 'identified',
        'site_id' => $site->id,
        'owner_id' => $admin->id,
    ]);

    $this->post(route('risk.registers.assess', $register), [
        'severity_id' => $severity->id,
        'probability_id' => 4,
        'risk_level_id' => $matrixLevel->id,
    ]);

    $register->refresh();
    expect($register->risk_level_id)->toBe($matrixLevel->id);
    expect($register->riskLevel->risk_level)->toBe('RED');
});
```

### 3.2 Audit trail records risk register creation

```php
test('audit trail records risk register creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('risk.registers.store'), [
        'title' => 'Audited risk register',
        'type' => 'hiradc',
        'site_id' => $site->id,
        'activity' => 'Test activity',
        'hazard' => 'Test hazard',
        'owner_id' => $admin->id,
    ]);

    $register = RiskRegister::first();

    expect(
        AuditLog::where('module_name', 'risk')
            ->where('reference_id', $register->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Audit trail records status change on assess

```php
test('audit trail records status change on assess', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $severity = Severity::factory()->create(['level' => 4]);
    $matrixLevel = $this->createRiskMatrixLevel(4, 4, 'RED');

    $register = RiskRegister::factory()->create([
        'status' => 'identified',
        'owner_id' => $admin->id,
    ]);

    $this->post(route('risk.registers.assess', $register), [
        'severity_id' => $severity->id,
        'probability_id' => 4,
        'risk_level_id' => $matrixLevel->id,
    ]);

    expect(
        AuditLog::where('module_name', 'risk')
            ->where('reference_id', $register->id)
            ->where('event', 'updated')
            ->exists()
    )->toBeTrue();

    expect(
        ActivityLog::where('module_name', 'risk')
            ->where('reference_id', $register->id)
            ->where('event', 'risk.assessed')
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Activity log records file upload

```php
test('activity log records file upload for risk register', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = RiskRegister::factory()->create();

    $file = UploadedFile::fake()->image('hazard_photo.jpg');

    $this->post('/core/files', [
        'file' => $file,
        'module_name' => 'risk',
        'reference_id' => $register->id,
        'collection' => 'attachments',
    ]);

    expect(
        ManagedFile::where('module_name', 'risk')
            ->where('reference_id', $register->id)
            ->where('collection', 'attachments')
            ->count()
    )->toBe(1);
});
```

### 3.5 Notification created on assess

```php
test('notification created on risk assess', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create a QHSSE Manager to receive notification
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');

    $severity = Severity::factory()->create(['level' => 4]);
    $matrixLevel = $this->createRiskMatrixLevel(4, 4, 'RED');

    $register = RiskRegister::factory()->create([
        'status' => 'identified',
        'owner_id' => $admin->id,
    ]);

    $this->post(route('risk.registers.assess', $register), [
        'severity_id' => $severity->id,
        'probability_id' => 4,
        'risk_level_id' => $matrixLevel->id,
    ]);

    expect(
        CoreNotification::where('type', 'risk.assessed')
            ->where('module_name', 'risk')
            ->where('reference_id', $register->id)
            ->exists()
    )->toBeTrue();
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot assess non-identified risk register

```php
test('cannot assess non-identified risk register', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $severity = Severity::factory()->create(['level' => 3]);
    $matrixLevel = $this->createRiskMatrixLevel(3, 3, 'ORANGE');

    $register = RiskRegister::factory()->create([
        'status' => 'assessed',  // Already assessed
        'owner_id' => $admin->id,
    ]);

    $response = $this->post(route('risk.registers.assess', $register), [
        'severity_id' => $severity->id,
        'probability_id' => 3,
        'risk_level_id' => $matrixLevel->id,
    ]);

    $response->assertSessionHasErrors(['status']);
    expect($register->fresh()->status)->toBe('assessed');
});
```

### 4.2 Implement controls without additional_controls fails validation

```php
test('implement controls without additional_controls fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = RiskRegister::factory()->create([
        'status' => 'controls_needed',
        'owner_id' => $admin->id,
    ]);

    $response = $this->post(route('risk.registers.implement_controls', $register), [
        'additional_controls' => '',
    ]);

    $response->assertSessionHasErrors(['additional_controls']);
    $register->refresh();
    expect($register->status)->toBe('controls_needed');
});
```

### 4.3 Duplicate register_number cannot occur via numbering service

```php
test('duplicate register_number cannot occur via numbering service', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    // Create first risk register — gets RSK-2026-0001
    $this->post(route('risk.registers.store'), [
        'title' => 'First risk register',
        'type' => 'hiradc',
        'site_id' => $site->id,
        'activity' => 'First activity',
        'hazard' => 'First hazard',
        'owner_id' => $admin->id,
    ]);

    // Create second risk register — should get RSK-2026-0002
    $this->post(route('risk.registers.store'), [
        'title' => 'Second risk register',
        'type' => 'jsa',
        'site_id' => $site->id,
        'activity' => 'Second activity',
        'hazard' => 'Second hazard',
        'owner_id' => $admin->id,
    ]);

    $numbers = RiskRegister::pluck('register_number')->toArray();
    expect(count($numbers))->toBe(2);
    expect(count(array_unique($numbers)))->toBe(2); // All unique
});
```

---

## Test Execution

```bash
# Run all tests
php artisan test

# Run only risk management tests
php artisan test --filter=RiskRegister

# Run with parallel (faster)
php artisan test --parallel

# Run with coverage
php artisan test --coverage
```

### Expected result after Phase 2:

```
Tests: 20 passed (Risk Management)
```

---

## Notes

- Tests use SQLite in-memory for speed and isolation
- Each test starts with a fresh database (Laravel's `RefreshDatabase` trait is auto-applied via Pest)
- Factory creates minimal valid data; tests add specific fields as needed
- No `WorkflowService::start()` needed — this module does not use the workflow engine
- Status transitions are direct controller actions (POST to specific endpoints)
- `RiskMatrixLevel` factory must be created in tests that need risk level lookup
- `Severity` factory must set `level` explicitly (1-4) for matrix lookup to work
- Notification tests require at least one QHSSE Manager user to exist for `notifyMany` to send to
- Permission tests assume `CorePermissions::roleMap()` is seeded with the 5 `risk.registers.*` keys

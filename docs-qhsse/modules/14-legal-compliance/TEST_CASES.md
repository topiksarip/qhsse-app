# Test Cases — Legal & Compliance Register

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

Test file: `tests/Feature/Modules/Legal/LegalComplianceTest.php`

## Factory Definition

File: `database/factories/Modules/Legal/LegalRegisterFactory.php`

```php
public function definition(): array
{
    return [
        'register_number' => 'LEG-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'title' => fake()->sentence(6),
        'regulation_name' => fake()->sentence(4),
        'regulation_number' => 'UU No. ' . fake()->numberBetween(1, 50) . ' Tahun ' . fake()->year(),
        'issuing_body' => fake()->randomElement(['Pemerintah RI', 'Kemenaker', 'Pemda DKI Jakarta', 'Kemenperin']),
        'category' => fake()->randomElement(['national', 'regional', 'industry', 'internal']),
        'compliance_status' => fake()->randomElement(['compliant', 'non_compliant', 'in_progress', 'not_applicable']),
        'site_id' => Site::factory(),
        'department_id' => null,
        'owner_id' => User::factory(),
        'next_review_date' => fake()->optional(0.6)->dateTimeBetween('+1 month', '+6 months')->format('Y-m-d'),
        'document_id' => null,
        'notes' => fake()->optional(0.5)->paragraph(2),
        'status' => 'active',
    ];
}
```

File: `database/factories/Modules/Legal/LegalObligationFactory.php`

```php
public function definition(): array
{
    return [
        'legal_register_id' => LegalRegister::factory(),
        'obligation_description' => fake()->paragraph(3),
        'frequency' => fake()->randomElement(['monthly', 'quarterly', 'annual']),
        'last_completed' => fake()->optional(0.5)->dateTimeBetween('-3 months', '-1 month')->format('Y-m-d'),
        'next_due' => function (array $attrs) {
            if (!$attrs['last_completed']) return null;
            $date = \Carbon\Carbon::parse($attrs['last_completed']);
            return match ($attrs['frequency']) {
                'monthly' => $date->addMonth()->format('Y-m-d'),
                'quarterly' => $date->addMonths(3)->format('Y-m-d'),
                'annual' => $date->addYear()->format('Y-m-d'),
                default => null,
            };
        },
        'evidence_file_id' => null,
        'status' => 'pending',
    ];
}
```

## Helper Trait

```php
trait CreatesLegalTestUser
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

    protected function qhsseManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Manager');
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

### 1.1 Authorized user can view register list

```php
test('authorized user can view legal register list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('legal-register.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Legal/Index')
        ->has('registers')
        ->has('filters')
        ->has('kpiSummary')
    );
});
```

### 1.2 Authorized user can create register

```php
test('authorized user can create legal register', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $owner = User::factory()->create();

    $response = $this->post(route('legal-register.store'), [
        'title' => 'UU No. 1 Tahun 1970 tentang Keselamatan Kerja',
        'regulation_name' => 'Undang-Undang Keselamatan Kerja',
        'regulation_number' => 'UU No. 1 Tahun 1970',
        'issuing_body' => 'Pemerintah RI',
        'category' => 'national',
        'compliance_status' => 'in_progress',
        'site_id' => $site->id,
        'department_id' => null,
        'owner_id' => $owner->id,
        'next_review_date' => '2026-08-15',
        'document_id' => null,
        'notes' => 'Regulasi ini mencakup ketentuan keselamatan kerja.',
    ]);

    $response->assertRedirect(route('legal-register.show', LegalRegister::first()));

    $register = LegalRegister::first();
    expect($register)->not->toBeNull();
    expect($register->title)->toBe('UU No. 1 Tahun 1970 tentang Keselamatan Kerja');
    expect($register->compliance_status)->toBe('in_progress');
    expect($register->category)->toBe('national');
    expect($register->status)->toBe('active');
});
```

### 1.3 Register number is auto-generated on create

```php
test('legal register number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $owner = User::factory()->create();

    $this->post(route('legal-register.store'), [
        'title' => 'Test Register Legal',
        'regulation_name' => 'Test Regulation Name',
        'regulation_number' => 'UU No. 99 Tahun 2026',
        'issuing_body' => 'Pemerintah RI',
        'category' => 'national',
        'compliance_status' => 'in_progress',
        'owner_id' => $owner->id,
        'site_id' => $site->id,
    ]);

    $register = LegalRegister::first();
    expect($register->register_number)->not->toBeNull();
    expect($register->register_number)->toMatch('/^LEG-\d{4}-\d{4}$/');
});
```

### 1.4 Register with missing title fails validation

```php
test('legal register with missing title fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $owner = User::factory()->create();

    $response = $this->post(route('legal-register.store'), [
        'regulation_name' => 'Test Regulation Name',
        'regulation_number' => 'UU No. 99 Tahun 2026',
        'issuing_body' => 'Pemerintah RI',
        'category' => 'national',
        'owner_id' => $owner->id,
    ]);

    $response->assertSessionHasErrors(['title']);
    expect(LegalRegister::count())->toBe(0);
});
```

### 1.5 Register with invalid category fails validation

```php
test('legal register with invalid category fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $owner = User::factory()->create();

    $response = $this->post(route('legal-register.store'), [
        'title' => 'Test Register Legal',
        'regulation_name' => 'Test Regulation Name',
        'regulation_number' => 'UU No. 99 Tahun 2026',
        'issuing_body' => 'Pemerintah RI',
        'category' => 'invalid_category',
        'compliance_status' => 'in_progress',
        'owner_id' => $owner->id,
    ]);

    $response->assertSessionHasErrors(['category']);
});
```

### 1.6 Compliance status can be updated

```php
test('compliance status can be updated on legal register', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = LegalRegister::factory()->create([
        'compliance_status' => 'in_progress',
        'status' => 'active',
    ]);

    $response = $this->put(route('legal-register.update', $register), [
        'title' => $register->title,
        'regulation_name' => $register->regulation_name,
        'regulation_number' => $register->regulation_number,
        'issuing_body' => $register->issuing_body,
        'category' => $register->category,
        'compliance_status' => 'compliant',
        'owner_id' => $register->owner_id,
    ]);

    $response->assertRedirect();
    $register->refresh();
    expect($register->compliance_status)->toBe('compliant');
});
```

### 1.7 Obligation can be created for a register

```php
test('obligation can be created for a legal register', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = LegalRegister::factory()->create(['status' => 'active']);

    $response = $this->post(route('legal-obligations.store', $register), [
        'obligation_description' => 'Lapor kepatuhan K3 bulanan ke Disnaker setiap akhir bulan.',
        'frequency' => 'monthly',
        'last_completed' => '2026-06-01',
        'next_due' => null, // Should be auto-calculated
    ]);

    $response->assertRedirect();
    $obligation = LegalObligation::where('legal_register_id', $register->id)->first();
    expect($obligation)->not->toBeNull();
    expect($obligation->frequency)->toBe('monthly');
    expect($obligation->status)->toBe('pending');
    expect($obligation->next_due)->toBe('2026-07-01'); // Auto-calculated
});
```

### 1.8 Obligation due date is auto-calculated based on frequency

```php
test('obligation next_due is auto-calculated based on frequency', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = LegalRegister::factory()->create(['status' => 'active']);

    // Test monthly
    $this->post(route('legal-obligations.store', $register), [
        'obligation_description' => 'Lapor kepatuhan K3 bulanan ke Disnaker setiap akhir bulan.',
        'frequency' => 'monthly',
        'last_completed' => '2026-01-15',
    ]);
    $monthly = LegalObligation::where('legal_register_id', $register->id)->first();
    expect($monthly->next_due)->toBe('2026-02-15');

    // Test quarterly
    $this->post(route('legal-obligations.store', $register), [
        'obligation_description' => 'Inspeksi alarm kebakaran secara triwulanan.',
        'frequency' => 'quarterly',
        'last_completed' => '2026-01-15',
    ]);
    $quarterly = LegalObligation::where('legal_register_id', $register->id)
        ->where('frequency', 'quarterly')->first();
    expect($quarterly->next_due)->toBe('2026-04-15');

    // Test annual
    $this->post(route('legal-obligations.store', $register), [
        'obligation_description' => 'Audit internal kepatuhan SMK3 secara tahunan.',
        'frequency' => 'annual',
        'last_completed' => '2026-01-15',
    ]);
    $annual = LegalObligation::where('legal_register_id', $register->id)
        ->where('frequency', 'annual')->first();
    expect($annual->next_due)->toBe('2027-01-15');
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without legal.register.view gets 403 on list

```php
test('user without legal.register.view gets 403 on register list', function () {
    $noRole = $this->noRoleUser();
    $this->actingAs($noRole);

    $response = $this->get(route('legal-register.index'));

    $response->assertForbidden();
});
```

### 2.2 User without legal.register.create gets 403 on create form

```php
test('user without legal.register.create gets 403 on create form', function () {
    $viewer = $this->viewerUser(); // Auditor role: view + export only
    $this->actingAs($viewer);

    $response = $this->get(route('legal-register.create'));

    $response->assertForbidden();
});
```

### 2.3 User without legal.obligations.create cannot create obligation

```php
test('user without legal.obligations.create cannot create obligation', function () {
    $viewer = $this->viewerUser(); // Auditor: no obligations.create
    $this->actingAs($viewer);

    $register = LegalRegister::factory()->create(['status' => 'active']);

    $response = $this->post(route('legal-obligations.store', $register), [
        'obligation_description' => 'Test obligation description here.',
        'frequency' => 'monthly',
    ]);

    $response->assertForbidden();
});
```

### 2.4 Contractor cannot view obligations

```php
test('contractor cannot view legal obligations', function () {
    $contractor = User::factory()->create();
    $contractor->assignRole('Contractor');
    $this->actingAs($contractor);

    $register = LegalRegister::factory()->create(['status' => 'active']);
    LegalObligation::factory()->create(['legal_register_id' => $register->id]);

    $response = $this->get(route('legal-register.show', $register));

    // Contractor can view register but obligations should not be included
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('register')
        ->missing('register.obligations')
    );
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Obligation can be completed with evidence

```php
test('obligation can be completed with evidence file', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = LegalRegister::factory()->create(['status' => 'active']);
    $obligation = LegalObligation::factory()->create([
        'legal_register_id' => $register->id,
        'status' => 'pending',
        'last_completed' => '2026-06-01',
        'next_due' => '2026-07-01',
        'evidence_file_id' => null,
    ]);

    // Create a managed file for evidence
    $file = ManagedFile::factory()->create([
        'module_name' => 'legal',
        'reference_id' => $obligation->id,
        'collection' => 'obligation_evidence',
    ]);

    $response = $this->post(route('legal-obligations.complete', [$register, $obligation]), [
        'last_completed' => '2026-07-01',
        'evidence_file_id' => $file->id,
    ]);

    $response->assertRedirect();
    $obligation->refresh();
    expect($obligation->status)->toBe('completed');
    expect($obligation->last_completed)->toBe('2026-07-01');
    expect($obligation->evidence_file_id)->toBe($file->id);
    // next_due should be recalculated: 2026-07-01 + 1 month (monthly frequency)
    expect($obligation->next_due)->not->toBeNull();
});
```

### 3.2 Audit trail records register creation

```php
test('audit trail records legal register creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $owner = User::factory()->create();

    $this->post(route('legal-register.store'), [
        'title' => 'Register for audit trail test',
        'regulation_name' => 'Test Regulation',
        'regulation_number' => 'UU No. 99 Tahun 2026',
        'issuing_body' => 'Pemerintah RI',
        'category' => 'national',
        'compliance_status' => 'in_progress',
        'owner_id' => $owner->id,
    ]);

    $register = LegalRegister::first();

    expect(
        AuditLog::where('module_name', 'legal')
            ->where('reference_id', $register->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Compliance status change triggers audit trail and notification

```php
test('compliance status change triggers audit trail and notification', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create a QHSSE Manager to receive notification
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');

    $register = LegalRegister::factory()->create([
        'compliance_status' => 'in_progress',
        'status' => 'active',
    ]);

    $this->put(route('legal-register.update', $register), [
        'title' => $register->title,
        'regulation_name' => $register->regulation_name,
        'regulation_number' => $register->regulation_number,
        'issuing_body' => $register->issuing_body,
        'category' => $register->category,
        'compliance_status' => 'non_compliant',
        'owner_id' => $register->owner_id,
    ]);

    // Check audit trail
    expect(
        AuditLog::where('module_name', 'legal')
            ->where('reference_id', $register->id)
            ->where('event', 'updated')
            ->exists()
    )->toBeTrue();

    // Check notification
    expect(
        CoreNotification::where('type', 'legal.compliance.changed')
            ->where('module_name', 'legal')
            ->where('reference_id', $register->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Overdue obligation is detected correctly

```php
test('overdue obligation is detected correctly', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = LegalRegister::factory()->create(['status' => 'active']);

    // Create overdue obligation (next_due in the past)
    $overdue = LegalObligation::factory()->create([
        'legal_register_id' => $register->id,
        'status' => 'pending',
        'next_due' => now()->subDays(10)->toDateString(),
    ]);

    // Create non-overdue obligation (next_due in the future)
    $notOverdue = LegalObligation::factory()->create([
        'legal_register_id' => $register->id,
        'status' => 'pending',
        'next_due' => now()->addDays(30)->toDateString(),
    ]);

    expect($overdue->isOverdue())->toBeTrue();
    expect($notOverdue->isOverdue())->toBeFalse();

    // Test scope
    $overdueCount = LegalObligation::overdue()->count();
    expect($overdueCount)->toBe(1);
});
```

### 3.5 Obligation cannot be completed without evidence

```php
test('obligation cannot be completed without evidence file', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = LegalRegister::factory()->create(['status' => 'active']);
    $obligation = LegalObligation::factory()->create([
        'legal_register_id' => $register->id,
        'status' => 'pending',
        'next_due' => now()->addDays(10)->toDateString(),
    ]);

    $response = $this->post(route('legal-obligations.complete', [$register, $obligation]), [
        'last_completed' => '2026-07-01',
        'evidence_file_id' => null, // Missing evidence
    ]);

    $response->assertSessionHasErrors(['evidence_file_id']);
    $obligation->refresh();
    expect($obligation->status)->toBe('pending');
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot create obligation on inactive register

```php
test('cannot create obligation on inactive register', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = LegalRegister::factory()->create(['status' => 'inactive']);

    $response = $this->post(route('legal-obligations.store', $register), [
        'obligation_description' => 'Test obligation for inactive register.',
        'frequency' => 'monthly',
    ]);

    $response->assertSessionHasErrors(['register']);
    expect(LegalObligation::where('legal_register_id', $register->id)->count())->toBe(0);
});
```

### 4.2 Cannot complete already-completed obligation

```php
test('cannot complete already-completed obligation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = LegalRegister::factory()->create(['status' => 'active']);
    $file = ManagedFile::factory()->create();
    $obligation = LegalObligation::factory()->create([
        'legal_register_id' => $register->id,
        'status' => 'completed',
        'evidence_file_id' => $file->id,
    ]);

    $response = $this->post(route('legal-obligations.complete', [$register, $obligation]), [
        'last_completed' => '2026-07-15',
        'evidence_file_id' => $file->id,
    ]);

    $response->assertSessionHasErrors(['obligation']);
});
```

### 4.3 Cannot update inactive register

```php
test('cannot update inactive legal register', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $register = LegalRegister::factory()->create([
        'status' => 'inactive',
        'compliance_status' => 'in_progress',
    ]);

    $response = $this->put(route('legal-register.update', $register), [
        'title' => $register->title,
        'regulation_name' => $register->regulation_name,
        'regulation_number' => $register->regulation_number,
        'issuing_body' => $register->issuing_body,
        'category' => $register->category,
        'compliance_status' => 'compliant',
        'owner_id' => $register->owner_id,
    ]);

    $response->assertSessionHasErrors(['register']);
    $register->refresh();
    expect($register->compliance_status)->toBe('in_progress');
});
```

---

## Test Execution

```bash
# Run all tests
php artisan test

# Run only legal compliance tests
php artisan test --filter=LegalCompliance

# Run with parallel
php artisan test --parallel

# Run with coverage
php artisan test --coverage
```

### Expected test count:

```
Tests: 20 passed (for Legal & Compliance module)
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
- `NumberingService::generate()` must be called/mocked after creating a LegalRegister in tests that need the number.
- For tests that need obligations with specific due dates, manually set `next_due` in factory overrides.
- `CorePermissions::roleMap()` defines which permissions each role gets — tests assume this is seeded.
- Notification tests require at least one recipient user with the target role to exist.
- Evidence file tests require the `managed_files` table to exist (Phase 0 dependency).
- Due date calculation tests rely on `Carbon\Carbon` date arithmetic.
- Overdue detection tests use `now()` for relative date comparisons.

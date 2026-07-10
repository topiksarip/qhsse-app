# Test Cases — Environmental Management

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

Test file: `tests/Feature/Modules/Environmental/EnvironmentalRecordTest.php`

## Factory Definition

File: `database/factories/Modules/Environmental/EnvironmentalRecordFactory.php`

```php
public function definition(): array
{
    return [
        'record_number' => 'ENV-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'type' => fake()->randomElement([
            'waste', 'spill', 'emission', 'noise', 'water_monitoring', 'other',
        ]),
        'title' => fake()->sentence(6),
        'description' => fake()->paragraph(3),
        'site_id' => Site::factory(),
        'area_id' => null,
        'occurred_at' => fake()->optional(0.8)->dateTimeBetween('-1 month', 'now'),
        'measured_value' => null,
        'unit' => null,
        'limit_value' => null,
        'is_exceedance' => false,
        'waste_type' => null,
        'quantity' => null,
        'disposal_method' => null,
        'material' => null,
        'volume' => null,
        'containment' => null,
        'parameter' => null,
        'location' => null,
        'reporter_id' => User::factory(),
        'status' => 'recorded',
        'capa_action_id' => null,
    ];
}
```

### Factory States

```php
// Emission with exceedance
public function emissionExceedance(): static
{
    return $this->state(fn (array $attrs) => [
        'type' => 'emission',
        'parameter' => 'SOx',
        'measured_value' => 450.0000,
        'unit' => 'mg/m³',
        'limit_value' => 300.0000,
        'is_exceedance' => true,
    ]);
}

// Waste record
public function waste(): static
{
    return $this->state(fn (array $attrs) => [
        'type' => 'waste',
        'waste_type' => 'Limbah B3',
        'quantity' => 150.5000,
        'disposal_method' => 'Incinerasi',
        'unit' => 'kg',
    ]);
}

// Spill record
public function spill(): static
{
    return $this->state(fn (array $attrs) => [
        'type' => 'spill',
        'material' => 'Minyak Solar',
        'volume' => 50.0000,
        'unit' => 'liter',
        'containment' => 'Boom oil',
    ]);
}

// Noise record
public function noise(): static
{
    return $this->state(fn (array $attrs) => [
        'type' => 'noise',
        'measured_value' => 85.0000,
        'unit' => 'dB',
        'location' => 'Genset Room',
        'occurred_at' => now(),
        'limit_value' => 75.0000,
        'is_exceedance' => true,
    ]);
}

// Water monitoring record
public function waterMonitoring(): static
{
    return $this->state(fn (array $attrs) => [
        'type' => 'water_monitoring',
        'parameter' => 'pH',
        'measured_value' => 7.2,
        'unit' => 'pH',
        'limit_value' => 9.0,
        'is_exceedance' => false,
    ]);
}
```

## Helper Trait

```php
trait CreatesEnvironmentalTestUser
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

    protected function managerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Manager');
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

    protected function createArea(): Area
    {
        return Area::factory()->create();
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view environmental record list

```php
test('authorized user can view environmental record list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('environmental-records.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Environmental/Index')
        ->has('records')
        ->has('filters')
        ->has('sites')
    );
});
```

### 1.2 Authorized user can create emission record

```php
test('authorized user can create emission record', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $response = $this->post(route('environmental-records.store'), [
        'type' => 'emission',
        'title' => 'Emisi SOx Stack #1',
        'description' => 'Pengukuran emisi SOx dari stack #1',
        'site_id' => $site->id,
        'occurred_at' => '2026-07-11 14:30:00',
        'parameter' => 'SOx',
        'measured_value' => 450.0000,
        'unit' => 'mg/m³',
        'limit_value' => 300.0000,
    ]);

    $response->assertRedirect(route('environmental-records.show', EnvironmentalRecord::first()));

    $record = EnvironmentalRecord::first();
    expect($record)->not->toBeNull();
    expect($record->title)->toBe('Emisi SOx Stack #1');
    expect($record->type)->toBe('emission');
    expect($record->status)->toBe('recorded');
    expect($record->reporter_id)->toBe($admin->id);
    expect($record->parameter)->toBe('SOx');
    expect($record->measured_value)->toBe('450.0000');
    expect($record->limit_value)->toBe('300.0000');
    expect($record->is_exceedance)->toBeTrue();
});
```

### 1.3 Record number is auto-generated on create

```php
test('record number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('environmental-records.store'), [
        'type' => 'waste',
        'title' => 'Limbah B3 dari produksi',
        'description' => 'Pembuangan limbah B3 harian',
        'site_id' => $site->id,
        'waste_type' => 'Limbah B3',
        'quantity' => 100.5,
        'unit' => 'kg',
        'disposal_method' => 'Pihak Ketiga',
    ]);

    $record = EnvironmentalRecord::first();
    expect($record->record_number)->not->toBeNull();
    expect($record->record_number)->toMatch('/^ENV-\d{4}-\d{4}$/');
});
```

### 1.4 Exceedance is auto-detected when measured_value exceeds limit_value

```php
test('exceedance is auto-detected when measured_value exceeds limit_value', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('environmental-records.store'), [
        'type' => 'noise',
        'title' => 'Kebisingan Genset',
        'description' => 'Pengukuran kebisingan di area genset',
        'site_id' => $site->id,
        'occurred_at' => now()->toDateTimeString(),
        'measured_value' => 85.0,
        'unit' => 'dB',
        'location' => 'Genset Room',
        'limit_value' => 75.0,
    ]);

    $record = EnvironmentalRecord::first();
    expect($record->is_exceedance)->toBeTrue();
});
```

### 1.5 Exceedance is false when measured_value does not exceed limit_value

```php
test('exceedance is false when measured_value does not exceed limit_value', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('environmental-records.store'), [
        'type' => 'water_monitoring',
        'title' => 'pH Air Limbah',
        'description' => 'Monitoring pH air limbah harian',
        'site_id' => $site->id,
        'parameter' => 'pH',
        'measured_value' => 7.2,
        'unit' => 'pH',
        'limit_value' => 9.0,
    ]);

    $record = EnvironmentalRecord::first();
    expect($record->is_exceedance)->toBeFalse();
});
```

### 1.6 Waste type record can be created with type-specific fields

```php
test('waste type record can be created with type-specific fields', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $response = $this->post(route('environmental-records.store'), [
        'type' => 'waste',
        'title' => 'Limbah Medis',
        'description' => 'Pembuangan limbah medis dari klinik',
        'site_id' => $site->id,
        'waste_type' => 'Limbah Medis',
        'quantity' => 25.5,
        'unit' => 'kg',
        'disposal_method' => 'Incinerasi',
    ]);

    $response->assertRedirect();

    $record = EnvironmentalRecord::first();
    expect($record->waste_type)->toBe('Limbah Medis');
    expect($record->quantity)->toBe('25.5000');
    expect($record->disposal_method)->toBe('Incinerasi');
    expect($record->is_exceedance)->toBeFalse();
});
```

### 1.7 Record can be investigated by QHSSE Officer

```php
test('record can be investigated by qhsse officer', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $record = EnvironmentalRecord::factory()->create([
        'status' => 'recorded',
        'reporter_id' => $officer->id,
    ]);

    $response = $this->post(route('environmental-records.investigate', $record));

    $response->assertRedirect();
    $record->refresh();
    expect($record->status)->toBe('investigated');
});
```

### 1.8 Record can be closed with reason

```php
test('record can be closed with reason', function () {
    $manager = $this->managerUser();
    $this->actingAs($manager);

    $record = EnvironmentalRecord::factory()->create([
        'status' => 'investigated',
        'reporter_id' => $manager->id,
    ]);

    $response = $this->post(route('environmental-records.close', $record), [
        'reason' => 'Investigasi selesai, corrective action telah diimplementasi.',
    ]);

    $response->assertRedirect();
    $record->refresh();
    expect($record->status)->toBe('closed');
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without environment.records.view gets 403 on list

```php
test('user without environment.records.view gets 403 on list', function () {
    $user = User::factory()->create();
    // No role assigned — no permissions

    $this->actingAs($user);

    $response = $this->get(route('environmental-records.index'));

    $response->assertForbidden();
});
```

### 2.2 User without environment.records.create gets 403 on create form

```php
test('user without environment.records.create gets 403 on create form', function () {
    $auditor = User::factory()->create();
    $auditor->assignRole('Auditor'); // View + export only

    $this->actingAs($auditor);

    $response = $this->get(route('environmental-records.create'));

    $response->assertForbidden();
});
```

### 2.3 Supervisor cannot investigate record

```php
test('supervisor cannot investigate record', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('Supervisor'); // Has create, update — NOT investigate

    $this->actingAs($supervisor);

    $record = EnvironmentalRecord::factory()->create([
        'status' => 'recorded',
        'reporter_id' => $supervisor->id,
    ]);

    $response = $this->post(route('environmental-records.investigate', $record));

    $response->assertForbidden();
});
```

### 2.4 Export blocked without environment.records.export

```php
test('export blocked without environment.records.export', function () {
    $reporter = $this->reporterUser();
    // Employee / Reporter does not have export permission

    $this->actingAs($reporter);

    $response = $this->get(route('environmental-records.export'));

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 File evidence can be attached to environmental record

```php
test('file evidence can be attached to environmental record', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $record = EnvironmentalRecord::factory()->create();

    $file = UploadedFile::fake()->image('evidence.jpg');

    $response = $this->post('/core/files', [
        'file' => $file,
        'module_name' => 'environment',
        'reference_id' => $record->id,
        'collection' => 'evidence',
    ]);

    $response->assertStatus(200);

    expect(
        ManagedFile::where('module_name', 'environment')
            ->where('reference_id', $record->id)
            ->where('collection', 'evidence')
            ->count()
    )->toBe(1);
});
```

### 3.2 Audit trail records environmental record creation

```php
test('audit trail records environmental record creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('environmental-records.store'), [
        'type' => 'waste',
        'title' => 'Limbah B3 harian',
        'description' => 'Pembuangan limbah B3',
        'site_id' => $site->id,
        'waste_type' => 'Limbah B3',
        'quantity' => 50,
        'unit' => 'kg',
        'disposal_method' => 'Pihak Ketiga',
    ]);

    $record = EnvironmentalRecord::first();

    expect(
        AuditLog::where('module_name', 'environment')
            ->where('reference_id', $record->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Activity log records exceedance detection

```php
test('activity log records exceedance detection', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('environmental-records.store'), [
        'type' => 'emission',
        'title' => 'Emisi NOx tinggi',
        'description' => 'Pengukuran emisi NOx melebihi batas',
        'site_id' => $site->id,
        'parameter' => 'NOx',
        'measured_value' => 500.0,
        'unit' => 'mg/m³',
        'limit_value' => 300.0,
    ]);

    $record = EnvironmentalRecord::first();

    expect(
        ActivityLog::where('module_name', 'environment')
            ->where('reference_id', $record->id)
            ->where('event', 'environment.exceedance_detected')
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Notification created on exceedance detection

```php
test('notification created on exceedance detection', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create a QHSSE Officer to receive notification
    $officer = $this->officerUser();
    $officer->update(['site_id' => 1]);

    $site = Site::factory()->create(['id' => 1]);
    $officer->update(['site_id' => $site->id]);

    $this->post(route('environmental-records.store'), [
        'type' => 'emission',
        'title' => 'Emisi SOx exceedance',
        'description' => 'Exceedance emisi SOx',
        'site_id' => $site->id,
        'parameter' => 'SOx',
        'measured_value' => 450.0,
        'unit' => 'mg/m³',
        'limit_value' => 300.0,
    ]);

    $record = EnvironmentalRecord::first();

    expect(
        CoreNotification::where('type', 'environment.exceedance_detected')
            ->where('module_name', 'environment')
            ->where('reference_id', $record->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.5 CAPA can be linked to environmental record

```php
test('capa can be linked to environmental record', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $record = EnvironmentalRecord::factory()->create([
        'status' => 'investigated',
        'reporter_id' => $officer->id,
    ]);

    $response = $this->post(route('environmental-records.open-action', $record), [
        'capa_title' => 'Investigasi emisi SOx',
        'capa_description' => 'CAPA untuk menangani exceedance emisi SOx dari stack.',
    ]);

    $response->assertRedirect();
    $record->refresh();

    expect($record->status)->toBe('action_open');
    expect($record->capa_action_id)->not->toBeNull();

    expect(
        CapaAction::where('source_module', 'environment')
            ->where('source_reference_id', $record->id)
            ->exists()
    )->toBeTrue();
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot investigate non-recorded record

```php
test('cannot investigate non-recorded record', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $record = EnvironmentalRecord::factory()->create([
        'status' => 'closed',
        'reporter_id' => $officer->id,
    ]);

    $response = $this->post(route('environmental-records.investigate', $record));

    $response->assertSessionHasErrors(['status']);
    expect($record->fresh()->status)->toBe('closed');
});
```

### 4.2 Close without reason fails validation

```php
test('close without reason fails validation', function () {
    $manager = $this->managerUser();
    $this->actingAs($manager);

    $record = EnvironmentalRecord::factory()->create([
        'status' => 'investigated',
        'reporter_id' => $manager->id,
    ]);

    $response = $this->post(route('environmental-records.close', $record), []);

    $response->assertSessionHasErrors(['reason']);
    expect($record->fresh()->status)->toBe('investigated');
});
```

### 4.3 Emission record without parameter fails validation

```php
test('emission record without parameter fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $response = $this->post(route('environmental-records.store'), [
        'type' => 'emission',
        'title' => 'Emisi tanpa parameter',
        'description' => 'Test emisi tanpa parameter',
        'site_id' => $site->id,
        'measured_value' => 100.0,
        'unit' => 'mg/m³',
        'limit_value' => 50.0,
        // parameter is missing — should fail
    ]);

    $response->assertSessionHasErrors(['parameter']);
    expect(EnvironmentalRecord::count())->toBe(0);
});
```

---

## Test Execution

```bash
# Run all tests
php artisan test

# Run only environmental tests
php artisan test --filter=Environmental

# Run with parallel (faster)
php artisan test --parallel

# Run with coverage
php artisan test --coverage
```

### Expected result after Phase 10:

```
Tests: 20 passed (environmental module)
```

---

## Test Summary

| # | Category | Test Name | Description |
|---|---|---|---|
| 1.1 | Functional | Authorized user can view list | Index page loads with correct props |
| 1.2 | Functional | Create emission record | Emission record with exceedance created successfully |
| 1.3 | Functional | Record number auto-generated | ENV-YYYY-NNNN format generated on create |
| 1.4 | Functional | Exceedance auto-detected (over limit) | measured_value > limit_value → is_exceedance=true |
| 1.5 | Functional | Exceedance false (under limit) | measured_value <= limit_value → is_exceedance=false |
| 1.6 | Functional | Waste type-specific fields | waste_type, quantity, disposal_method stored correctly |
| 1.7 | Functional | Investigate by QHSSE Officer | recorded → investigated transition |
| 1.8 | Functional | Close with reason | investigated → closed with reason |
| 2.1 | Permission | No view permission → 403 | User without role cannot access list |
| 2.2 | Permission | No create permission → 403 | Auditor cannot access create form |
| 2.3 | Permission | Supervisor cannot investigate | Supervisor lacks investigate permission |
| 2.4 | Permission | Export blocked without permission | Employee/Reporter cannot export |
| 3.1 | Integration | File evidence attached | ManagedFile linked to environmental record |
| 3.2 | Integration | Audit trail on create | AuditLog entry created for record creation |
| 3.3 | Integration | Activity log on exceedance | ActivityLog entry for exceedance detection |
| 3.4 | Integration | Notification on exceedance | CoreNotification sent to QHSSE team |
| 3.5 | Integration | CAPA linked to record | CAPA created with source_module='environment' |
| 4.1 | Negative | Cannot investigate closed record | Status transition blocked from non-recorded |
| 4.2 | Negative | Close without reason fails | Validation requires reason (min:10) |
| 4.3 | Negative | Emission without parameter fails | Type-specific validation enforced |

---

## Notes

- Tests use SQLite in-memory for speed and isolation.
- Each test starts with a fresh database (Laravel's `RefreshDatabase` trait is auto-applied via Pest).
- Factory creates minimal valid data; tests add specific fields as needed.
- `is_exceedance` is auto-calculated by the model observer — tests verify this behavior.
- For exceedance notification tests, at least one QHSSE Officer/Manager user must exist with matching site scope.
- Type-specific validation tests verify that `waste_type`, `parameter`, `location`, etc. are conditionally required.
- CAPA integration tests require the CAPA module's `capa_actions` table to exist.
- All test descriptions and assertions use the actual route names and permission keys.

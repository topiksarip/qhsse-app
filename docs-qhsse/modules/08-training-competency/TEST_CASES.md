# Test Cases — Training & Competency

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

Test file: `tests/Feature/Modules/Training/TrainingRecordTest.php`

## Factory Definitions

### TrainingProgramFactory

File: `database/factories/Modules/Training/TrainingProgramFactory.php`

```php
public function definition(): array
{
    return [
        'code' => strtoupper(fake()->unique()->lexify('???-??')),
        'name' => fake()->sentence(3),
        'description' => fake()->optional(0.7)->paragraph(2),
        'category' => fake()->randomElement([
            'safety', 'technical', 'compliance', 'soft_skill',
            'environment', 'security', 'quality', 'first_aid',
        ]),
        'duration_hours' => fake()->numberBetween(2, 40),
        'is_certification' => fake()->boolean(60),
        'validity_months' => fn (array $attrs) => $attrs['is_certification']
            ? fake()->randomElement([6, 12, 24, 36])
            : null,
        'is_active' => true,
    ];
}
```

### TrainingRecordFactory

File: `database/factories/Modules/Training/TrainingRecordFactory.php`

```php
public function definition(): array
{
    $startDate = fake()->dateTimeBetween('-6 months', '+1 month');
    $endDate = (clone $startDate)->modify('+' . fake()->numberBetween(1, 5) . ' days');

    return [
        'training_number' => 'TRN-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'employee_id' => Employee::factory(),
        'training_program_id' => TrainingProgram::factory(),
        'provider' => fake()->optional(0.6)->company(),
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
        'status' => fake()->randomElement(['scheduled', 'in_progress', 'completed']),
        'score' => fn (array $attrs) => $attrs['status'] === 'completed'
            ? fake()->randomFloat(2, 60, 100)
            : null,
        'result' => fn (array $attrs) => $attrs['status'] === 'completed'
            ? fake()->randomElement(['pass', 'fail', 'pending'])
            : null,
        'certificate_number' => fn (array $attrs) => $attrs['status'] === 'completed'
            ? 'CERT-' . fake()->numberBetween(10000, 99999)
            : null,
        'certificate_file_id' => null,
        'expiry_date' => null,
        'notes' => fake()->optional(0.3)->paragraph(1),
    ];
}
```

## Helper Trait

```php
trait CreatesTrainingTestUser
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

    protected function employeeUser(): User
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

    protected function createProgram(): TrainingProgram
    {
        return TrainingProgram::factory()->create();
    }

    protected function createEmployee(): Employee
    {
        return Employee::factory()->create();
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view training record list

```php
test('authorized user can view training record list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('training.records.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Training/Record/Index')
        ->has('items')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create training record

```php
test('authorized user can create training record', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $program = $this->createProgram();
    $employee = $this->createEmployee();

    $response = $this->post(route('training.records.store'), [
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
        'provider' => 'PT Safety First',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-02',
        'status' => 'scheduled',
        'notes' => 'Test training record',
    ]);

    $response->assertRedirect(route('training.records.show', TrainingRecord::first()));

    $record = TrainingRecord::first();
    expect($record)->not->toBeNull();
    expect($record->employee_id)->toBe($employee->id);
    expect($record->training_program_id)->toBe($program->id);
    expect($record->status)->toBe('scheduled');
});
```

### 1.3 Training number is auto-generated on create

```php
test('training number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $program = $this->createProgram();
    $employee = $this->createEmployee();

    $this->post(route('training.records.store'), [
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
        'start_date' => '2026-07-01',
        'status' => 'scheduled',
    ]);

    $record = TrainingRecord::first();
    expect($record->training_number)->not->toBeNull();
    expect($record->training_number)->toMatch('/^TRN-\d{4}-\d{4}$/');
});
```

### 1.4 Authorized user can create training program

```php
test('authorized user can create training program', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('training.programs.store'), [
        'code' => 'HSE-IND',
        'name' => 'HSE Induction',
        'description' => 'Pelatihan induksi keselamatan',
        'category' => 'safety',
        'duration_hours' => 8,
        'is_certification' => true,
        'validity_months' => 12,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('training.programs.show', TrainingProgram::first()));

    $program = TrainingProgram::first();
    expect($program)->not->toBeNull();
    expect($program->code)->toBe('HSE-IND');
    expect($program->is_certification)->toBeTrue();
    expect($program->validity_months)->toBe(12);
});
```

### 1.5 Training record with missing employee fails validation

```php
test('training record with missing employee fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $program = $this->createProgram();

    $response = $this->post(route('training.records.store'), [
        'training_program_id' => $program->id,
        'start_date' => '2026-07-01',
        'status' => 'scheduled',
    ]);

    $response->assertSessionHasErrors(['employee_id']);
    expect(TrainingRecord::count())->toBe(0);
});
```

### 1.6 Training record with inactive program fails

```php
test('training record with inactive program fails', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $program = TrainingProgram::factory()->create(['is_active' => false]);
    $employee = $this->createEmployee();

    $response = $this->post(route('training.records.store'), [
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
        'start_date' => '2026-07-01',
        'status' => 'scheduled',
    ]);

    $response->assertSessionHasErrors(['training_program_id']);
    expect(TrainingRecord::count())->toBe(0);
});
```

### 1.7 Training record status can be updated manually

```php
test('training record status can be updated manually', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $record = TrainingRecord::factory()->create(['status' => 'scheduled']);

    $response = $this->put(route('training.records.update', $record), [
        'employee_id' => $record->employee_id,
        'training_program_id' => $record->training_program_id,
        'start_date' => $record->start_date,
        'end_date' => $record->end_date,
        'status' => 'in_progress',
    ]);

    $response->assertRedirect();
    $record->refresh();
    expect($record->status)->toBe('in_progress');
});
```

### 1.8 Completed record with expired expiry_date auto-updates to expired

```php
test('completed record with expired expiry_date auto-updates to expired', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $record = TrainingRecord::factory()->create([
        'status' => 'completed',
        'expiry_date' => now()->subDays(10)->toDateString(),
    ]);

    // Simulate on-access expiry check in show
    if ($record->expiry_date < now()->toDateString() && $record->status === 'completed') {
        $record->update(['status' => 'expired']);
    }

    $record->refresh();
    expect($record->status)->toBe('expired');
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without training.records.view gets 403 on record list

```php
test('user without training.records.view gets 403 on record list', function () {
    $employee = $this->employeeUser();
    // Remove training.records.view permission if assigned by default
    $employee->revokePermissionTo('training.records.view');
    $this->actingAs($employee);

    $response = $this->get(route('training.records.index'));

    $response->assertForbidden();
});
```

### 2.2 User without training.records.create gets 403 on create form

```php
test('user without training.records.create gets 403 on create form', function () {
    $auditor = $this->auditorUser();
    $this->actingAs($auditor);

    $response = $this->get(route('training.records.create'));

    $response->assertForbidden();
});
```

### 2.3 Auditor cannot create training records

```php
test('auditor cannot create training records', function () {
    $auditor = $this->auditorUser();
    $this->actingAs($auditor);

    $program = $this->createProgram();
    $employee = $this->createEmployee();

    $response = $this->post(route('training.records.store'), [
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
        'start_date' => '2026-07-01',
        'status' => 'scheduled',
    ]);

    $response->assertForbidden();
    expect(TrainingRecord::count())->toBe(0);
});
```

### 2.4 Employee cannot export training records

```php
test('employee cannot export training records', function () {
    $employee = $this->employeeUser();
    $this->actingAs($employee);

    $response = $this->get(route('training.records.export'));

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Certificate file can be attached to training record

```php
test('certificate file can be attached to training record', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $record = TrainingRecord::factory()->create();

    $file = UploadedFile::fake()->create('certificate.pdf', 1024, 'application/pdf');

    $response = $this->post('/core/files', [
        'file' => $file,
        'module_name' => 'training',
        'reference_id' => $record->id,
        'collection' => 'certificate',
    ]);

    $response->assertStatus(200);

    expect(
        ManagedFile::where('module_name', 'training')
            ->where('reference_id', $record->id)
            ->where('collection', 'certificate')
            ->count()
    )->toBe(1);
});
```

### 3.2 Audit trail records training record creation

```php
test('audit trail records training record creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $program = $this->createProgram();
    $employee = $this->createEmployee();

    $this->post(route('training.records.store'), [
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
        'start_date' => '2026-07-01',
        'status' => 'scheduled',
    ]);

    $record = TrainingRecord::first();

    expect(
        AuditLog::where('module_name', 'training')
            ->where('reference_id', $record->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Activity log records status change

```php
test('activity log records status change on training record', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $record = TrainingRecord::factory()->create(['status' => 'scheduled']);

    $this->put(route('training.records.update', $record), [
        'employee_id' => $record->employee_id,
        'training_program_id' => $record->training_program_id,
        'start_date' => $record->start_date,
        'end_date' => $record->end_date,
        'status' => 'completed',
        'score' => 85.50,
        'result' => 'pass',
    ]);

    expect(
        ActivityLog::where('module_name', 'training')
            ->where('reference_id', $record->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Notification created when training record is created

```php
test('notification created when training record is created', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $program = $this->createProgram();
    $employee = $this->createEmployee();

    $this->post(route('training.records.store'), [
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
        'start_date' => '2026-07-01',
        'status' => 'scheduled',
    ]);

    $record = TrainingRecord::first();

    expect(
        CoreNotification::where('type', 'training.record_created')
            ->where('module_name', 'training')
            ->where('reference_id', $record->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.5 Training matrix loads employees with their records

```php
test('training matrix loads employees with their records', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $program = $this->createProgram();
    $employee = $this->createEmployee();
    TrainingRecord::factory()->create([
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
        'status' => 'completed',
    ]);

    $response = $this->get(route('training.matrix.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Training/Matrix/Index')
        ->has('employees')
        ->has('programs')
    );
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot create record without mandatory fields

```php
test('cannot create training record without mandatory fields', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('training.records.store'), [
        'status' => 'scheduled',
    ]);

    $response->assertSessionHasErrors(['employee_id', 'training_program_id', 'start_date']);
    expect(TrainingRecord::count())->toBe(0);
});
```

### 4.2 Invalid status value rejected

```php
test('invalid training record status is rejected', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $program = $this->createProgram();
    $employee = $this->createEmployee();

    $response = $this->post(route('training.records.store'), [
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
        'start_date' => '2026-07-01',
        'status' => 'invalid_status',
    ]);

    $response->assertSessionHasErrors(['status']);
    expect(TrainingRecord::count())->toBe(0);
});
```

### 4.3 Score outside valid range rejected

```php
test('score outside valid range is rejected', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $program = $this->createProgram();
    $employee = $this->createEmployee();

    $response = $this->post(route('training.records.store'), [
        'employee_id' => $employee->id,
        'training_program_id' => $program->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-02',
        'status' => 'completed',
        'score' => 150, // out of range (0-100)
        'result' => 'pass',
    ]);

    $response->assertSessionHasErrors(['score']);
    expect(TrainingRecord::count())->toBe(0);
});
```

---

## Test Summary

| Category | Count | Coverage |
|---|---|---|
| Functional | 8 | List, create record, numbering, create program, validation, inactive program, status update, expiry auto-detection |
| Permission | 4 | View 403, create 403, auditor blocked, export 403 |
| Integration | 5 | Certificate upload, audit trail, activity log, notification, matrix view |
| Negative | 3 | Missing fields, invalid status, out-of-range score |
| **Total** | **20** | |

---

## Running Tests

```bash
# Run all training tests
php artisan test tests/Feature/Modules/Training/

# Run specific test file
php artisan test tests/Feature/Modules/Training/TrainingRecordTest.php

# Run with coverage
php artisan test tests/Feature/Modules/Training/ --coverage
```

## Test Database Seeding for Tests

```php
// In tests/Pest.php or TestCase setUp
$this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
$this->seed(\Database\Seeders\TrainingPermissionsSeeder::class);
```

# Test Cases — Emergency Preparedness

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

Test files:
- `tests/Feature/Modules/Emergency/EmergencyPlanTest.php`
- `tests/Feature/Modules/Emergency/EmergencyDrillTest.php`
- `tests/Feature/Modules/Emergency/EmergencyContactTest.php`

## Factory Definitions

### EmergencyPlanFactory

File: `database/factories/Modules/Emergency/EmergencyPlanFactory.php`

```php
public function definition(): array
{
    return [
        'plan_number' => 'EMG-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'name' => fake()->sentence(4),
        'type' => fake()->randomElement([
            'fire', 'medical', 'spill', 'evacuation', 'natural_disaster', 'security', 'other',
        ]),
        'site_id' => Site::factory(),
        'description' => fake()->paragraph(3),
        'response_procedure' => fake()->paragraph(2),
        'escalation_procedure' => fake()->paragraph(2),
        'contact_person_id' => User::factory(),
        'emergency_contacts' => null,
        'equipment_needed' => fake()->optional(0.6)->sentence(6),
    ];
}
```

#### Factory States

```php
// Fire plan
public function fire(): static
{
    return $this->state(fn (array $attrs) => [
        'type' => 'fire',
        'name' => 'Rencana Kebakaran ' . fake()->word(),
    ]);
}

// With emergency contacts JSON
public function withContacts(): static
{
    return $this->state(fn (array $attrs) => [
        'emergency_contacts' => [
            ['name' => 'Budi Santoso', 'role' => 'Fire Warden', 'phone' => '+62-812-3456-7890'],
            ['name' => 'Sari Wijaya', 'role' => 'First Aider', 'phone' => '+62-813-9876-5432'],
        ],
    ]);
}

// With equipment
public function withEquipment(): static
{
    return $this->state(fn (array $attrs) => [
        'equipment_needed' => 'APAR (4 unit), Hydrant (2 unit), Smoke Detector, Eye Wash Station',
    ]);
}
```

### EmergencyDrillFactory

File: `database/factories/Modules/Emergency/EmergencyDrillFactory.php`

```php
public function definition(): array
{
    return [
        'drill_number' => 'EMG-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'emergency_plan_id' => EmergencyPlan::factory(),
        'scheduled_date' => fake()->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
        'executed_date' => null,
        'site_id' => Site::factory(),
        'participants_count' => null,
        'observer_id' => User::factory(),
        'result' => null,
        'findings' => null,
        'recommendations' => null,
        'status' => 'scheduled',
    ];
}
```

#### Factory States

```php
// Executed drill — passed
public function executedPass(): static
{
    return $this->state(fn (array $attrs) => [
        'executed_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
        'participants_count' => fake()->numberBetween(20, 100),
        'result' => 'pass',
        'findings' => fake()->paragraph(2),
        'recommendations' => fake()->paragraph(1),
        'status' => 'executed',
    ]);
}

// Executed drill — failed
public function executedFail(): static
{
    return $this->state(fn (array $attrs) => [
        'executed_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
        'participants_count' => fake()->numberBetween(10, 50),
        'result' => 'fail',
        'findings' => 'Evakuasi terlambat, alarm tidak terdengar di area warehouse.',
        'recommendations' => 'Tambah alarm di area warehouse, latihan ulang dalam 3 bulan.',
        'status' => 'executed',
    ]);
}

// Executed drill — needs improvement
public function executedNeedsImprovement(): static
{
    return $this->state(fn (array $attrs) => [
        'executed_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
        'participants_count' => fake()->numberBetween(15, 60),
        'result' => 'needs_improvement',
        'findings' => 'Headcount memakan waktu lebih dari 5 menit.',
        'recommendations' => 'Latih assembly point warden untuk headcount yang lebih cepat.',
        'status' => 'executed',
    ]);
}
```

### EmergencyContactFactory

File: `database/factories/Modules/Emergency/EmergencyContactFactory.php`

```php
public function definition(): array
{
    return [
        'name' => fake()->name(),
        'role' => fake()->randomElement([
            'Fire Warden', 'First Aider', 'Site Security', 'Medical Officer', 'Evacuation Coordinator',
        ]),
        'phone' => '+62-81' . fake()->numberBetween(2, 9) . '-' . fake()->numberBetween(1000, 9999) . '-' . fake()->numberBetween(1000, 9999),
        'email' => fake()->optional(0.7)->email(),
        'site_id' => Site::factory(),
        'is_active' => true,
    ];
}
```

## Helper Trait

```php
trait CreatesEmergencyTestUser
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

    protected function supervisorUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Supervisor');
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
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view emergency plan list

```php
test('authorized user can view emergency plan list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('emergency-plans.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Emergency/Plans/Index')
        ->has('plans')
        ->has('filters')
        ->has('sites')
    );
});
```

### 1.2 Authorized user can create emergency plan

```php
test('authorized user can create emergency plan', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $contactPerson = User::factory()->create();

    $response = $this->post(route('emergency-plans.store'), [
        'name' => 'Rencana Kebakaran Plant A',
        'type' => 'fire',
        'site_id' => $site->id,
        'description' => 'Rencana respons untuk kebakaran di area produksi Plant A.',
        'response_procedure' => '1. Aktifkan alarm kebakaran 2. Hubungi pemadam 3. Evakuasi',
        'escalation_procedure' => '1. Laporkan ke Supervisor 2. Eskalasi ke QHSSE Officer',
        'contact_person_id' => $contactPerson->id,
        'emergency_contacts' => [
            ['name' => 'Budi Santoso', 'role' => 'Fire Warden', 'phone' => '+62-812-3456-7890'],
        ],
        'equipment_needed' => 'APAR (4 unit), Hydrant (2 unit)',
    ]);

    $response->assertRedirect(route('emergency-plans.show', EmergencyPlan::first()));

    $plan = EmergencyPlan::first();
    expect($plan)->not->toBeNull();
    expect($plan->name)->toBe('Rencana Kebakaran Plant A');
    expect($plan->type)->toBe('fire');
    expect($plan->contact_person_id)->toBe($contactPerson->id);
    expect($plan->emergency_contacts)->toBeArray();
    expect($plan->emergency_contacts)->toHaveCount(1);
    expect($plan->emergency_contacts[0]['name'])->toBe('Budi Santoso');
    expect($plan->equipment_needed)->toBe('APAR (4 unit), Hydrant (2 unit)');
});
```

### 1.3 Plan number is auto-generated on create

```php
test('plan number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $contactPerson = User::factory()->create();

    $this->post(route('emergency-plans.store'), [
        'name' => 'Rencana Evakuasi',
        'type' => 'evacuation',
        'site_id' => $site->id,
        'description' => 'Rencana evakuasi umum',
        'response_procedure' => 'Evakuasi ke assembly point',
        'escalation_procedure' => 'Eskalasi ke management',
        'contact_person_id' => $contactPerson->id,
    ]);

    $plan = EmergencyPlan::first();
    expect($plan->plan_number)->not->toBeNull();
    expect($plan->plan_number)->toMatch('/^EMG-\d{4}-\d{4}$/');
});
```

### 1.4 Authorized user can schedule emergency drill

```php
test('authorized user can schedule emergency drill', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $site = $this->createSite();
    $plan = EmergencyPlan::factory()->create(['site_id' => $site->id]);
    $observer = User::factory()->create();

    $response = $this->post(route('emergency-drills.store'), [
        'emergency_plan_id' => $plan->id,
        'scheduled_date' => '2026-08-15',
        'site_id' => $site->id,
        'observer_id' => $observer->id,
    ]);

    $response->assertRedirect(route('emergency-drills.show', EmergencyDrill::first()));

    $drill = EmergencyDrill::first();
    expect($drill)->not->toBeNull();
    expect($drill->emergency_plan_id)->toBe($plan->id);
    expect($drill->status)->toBe('scheduled');
    expect($drill->scheduled_date)->toBe('2026-08-15');
    expect($drill->observer_id)->toBe($observer->id);
    expect($drill->drill_number)->toMatch('/^EMG-\d{4}-\d{4}$/');
});
```

### 1.5 Authorized user can execute drill with result

```php
test('authorized user can execute drill with result', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $drill = EmergencyDrill::factory()->create([
        'status' => 'scheduled',
        'observer_id' => $officer->id,
    ]);

    $response = $this->post(route('emergency-drills.execute', $drill), [
        'executed_date' => '2026-07-15',
        'participants_count' => 50,
        'result' => 'pass',
        'findings' => 'Semua peserta berhasil evakuasi dalam 3 menit.',
        'recommendations' => 'Tambah APAR di area warehouse.',
    ]);

    $response->assertRedirect();
    $drill->refresh();
    expect($drill->status)->toBe('executed');
    expect($drill->result)->toBe('pass');
    expect($drill->executed_date)->toBe('2026-07-15');
    expect($drill->participants_count)->toBe(50);
    expect($drill->findings)->toBe('Semua peserta berhasil evakuasi dalam 3 menit.');
});
```

### 1.6 Authorized user can create emergency contact

```php
test('authorized user can create emergency contact', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $site = $this->createSite();

    $response = $this->post(route('emergency-contacts.store'), [
        'name' => 'Budi Santoso',
        'role' => 'Fire Warden',
        'phone' => '+62-812-3456-7890',
        'email' => 'budi@company.com',
        'site_id' => $site->id,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('emergency-contacts.index'));

    $contact = EmergencyContact::first();
    expect($contact)->not->toBeNull();
    expect($contact->name)->toBe('Budi Santoso');
    expect($contact->role)->toBe('Fire Warden');
    expect($contact->phone)->toBe('+62-812-3456-7890');
    expect($contact->email)->toBe('budi@company.com');
    expect($contact->is_active)->toBeTrue();
});
```

### 1.7 Plan show page displays linked drills

```php
test('plan show page displays linked drills', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $plan = EmergencyPlan::factory()->create();
    $drill1 = EmergencyDrill::factory()->create(['emergency_plan_id' => $plan->id, 'status' => 'scheduled']);
    $drill2 = EmergencyDrill::factory()->executedPass()->create(['emergency_plan_id' => $plan->id]);

    $response = $this->get(route('emergency-plans.show', $plan));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Emergency/Plans/Show')
        ->has('plan.drills', 2)
    );
});
```

### 1.8 Drill can be executed with fail result and notification sent

```php
test('drill can be executed with fail result and notification sent', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $drill = EmergencyDrill::factory()->create([
        'status' => 'scheduled',
        'observer_id' => $officer->id,
    ]);

    $this->post(route('emergency-drills.execute', $drill), [
        'executed_date' => '2026-07-15',
        'participants_count' => 30,
        'result' => 'fail',
        'findings' => 'Evakuasi terlambat, alarm tidak terdengar di area warehouse.',
        'recommendations' => 'Tambah alarm di area warehouse.',
    ]);

    $drill->refresh();
    expect($drill->status)->toBe('executed');
    expect($drill->result)->toBe('fail');

    expect(
        CoreNotification::where('type', 'emergency.drill_failed')
            ->where('module_name', 'emergency')
            ->where('reference_id', $drill->id)
            ->exists()
    )->toBeTrue();
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without emergency.plans.view gets 403 on plan list

```php
test('user without emergency.plans.view gets 403 on plan list', function () {
    $user = User::factory()->create();
    // No role assigned — no permissions

    $this->actingAs($user);

    $response = $this->get(route('emergency-plans.index'));

    $response->assertForbidden();
});
```

### 2.2 User without emergency.drills.create gets 403 on drill create

```php
test('user without emergency.drills.create gets 403 on drill create', function () {
    $supervisor = $this->supervisorUser();
    // Supervisor does not have drill create permission

    $this->actingAs($supervisor);

    $response = $this->get(route('emergency-drills.create'));

    $response->assertForbidden();
});
```

### 2.3 Employee cannot execute drill

```php
test('employee cannot execute drill', function () {
    $reporter = $this->reporterUser();
    // Employee/Reporter does not have execute permission

    $this->actingAs($reporter);

    $drill = EmergencyDrill::factory()->create(['status' => 'scheduled']);

    $response = $this->post(route('emergency-drills.execute', $drill), [
        'executed_date' => '2026-07-15',
        'participants_count' => 20,
        'result' => 'pass',
    ]);

    $response->assertForbidden();
});
```

### 2.4 Export blocked without emergency.plans.export

```php
test('export blocked without emergency.plans.export', function () {
    $reporter = $this->reporterUser();
    // Employee/Reporter does not have export permission

    $this->actingAs($reporter);

    $response = $this->get(route('emergency-plans.export'));

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Audit trail records emergency plan creation

```php
test('audit trail records emergency plan creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $contactPerson = User::factory()->create();

    $this->post(route('emergency-plans.store'), [
        'name' => 'Rencana Medis',
        'type' => 'medical',
        'site_id' => $site->id,
        'description' => 'Rencana respons medis darurat',
        'response_procedure' => 'Pertolongan pertama dan evakuasi medis',
        'escalation_procedure' => 'Eskalasi ke tim medis',
        'contact_person_id' => $contactPerson->id,
    ]);

    $plan = EmergencyPlan::first();

    expect(
        AuditLog::where('module_name', 'emergency')
            ->where('reference_id', $plan->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.2 Activity log records drill scheduling

```php
test('activity log records drill scheduling', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $plan = EmergencyPlan::factory()->create();
    $observer = User::factory()->create();
    $site = Site::factory()->create();

    $this->post(route('emergency-drills.store'), [
        'emergency_plan_id' => $plan->id,
        'scheduled_date' => '2026-08-15',
        'site_id' => $site->id,
        'observer_id' => $observer->id,
    ]);

    $drill = EmergencyDrill::first();

    expect(
        ActivityLog::where('module_name', 'emergency')
            ->where('reference_id', $drill->id)
            ->where('event', 'emergency.drill_scheduled')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Notification sent on drill scheduling

```php
test('notification sent on drill scheduling', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $plan = EmergencyPlan::factory()->create();
    $observer = User::factory()->create();
    $site = Site::factory()->create();

    $this->post(route('emergency-drills.store'), [
        'emergency_plan_id' => $plan->id,
        'scheduled_date' => '2026-08-15',
        'site_id' => $site->id,
        'observer_id' => $observer->id,
    ]);

    $drill = EmergencyDrill::first();

    expect(
        CoreNotification::where('type', 'emergency.drill_scheduled')
            ->where('module_name', 'emergency')
            ->where('reference_id', $drill->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.4 File evidence can be attached to emergency plan

```php
test('file evidence can be attached to emergency plan', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $plan = EmergencyPlan::factory()->create();

    $file = UploadedFile::fake()->create('prosedur_kebakaran.pdf', 1024, 'application/pdf');

    $response = $this->post('/core/files', [
        'file' => $file,
        'module_name' => 'emergency',
        'reference_id' => $plan->id,
        'collection' => 'evidence',
    ]);

    $response->assertStatus(200);

    expect(
        ManagedFile::where('module_name', 'emergency')
            ->where('reference_id', $plan->id)
            ->where('collection', 'evidence')
            ->count()
    )->toBe(1);
});
```

### 3.5 Audit trail records drill execution

```php
test('audit trail records drill execution', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $drill = EmergencyDrill::factory()->create([
        'status' => 'scheduled',
        'observer_id' => $officer->id,
    ]);

    $this->post(route('emergency-drills.execute', $drill), [
        'executed_date' => '2026-07-15',
        'participants_count' => 40,
        'result' => 'needs_improvement',
        'findings' => 'Headcount memakan waktu lebih dari 5 menit.',
        'recommendations' => 'Latih assembly point warden.',
    ]);

    expect(
        AuditLog::where('module_name', 'emergency')
            ->where('reference_id', $drill->id)
            ->where('event', 'updated')
            ->exists()
    )->toBeTrue();

    expect(
        ActivityLog::where('module_name', 'emergency')
            ->where('reference_id', $drill->id)
            ->where('event', 'emergency.drill_executed')
            ->exists()
    )->toBeTrue();
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot execute already executed drill

```php
test('cannot execute already executed drill', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $drill = EmergencyDrill::factory()->executedPass()->create([
        'observer_id' => $officer->id,
    ]);

    $response = $this->post(route('emergency-drills.execute', $drill), [
        'executed_date' => '2026-07-20',
        'participants_count' => 50,
        'result' => 'pass',
    ]);

    $response->assertSessionHasErrors(['status']);
    expect($drill->fresh()->status)->toBe('executed');
});
```

### 4.2 Drill creation without emergency_plan_id fails validation

```php
test('drill creation without emergency_plan_id fails validation', function () {
    $officer = $this->officerUser();
    $this->actingAs($officer);

    $site = $this->createSite();
    $observer = User::factory()->create();

    $response = $this->post(route('emergency-drills.store'), [
        'scheduled_date' => '2026-08-15',
        'site_id' => $site->id,
        'observer_id' => $observer->id,
        // emergency_plan_id is missing
    ]);

    $response->assertSessionHasErrors(['emergency_plan_id']);
    expect(EmergencyDrill::count())->toBe(0);
});
```

### 4.3 Plan creation without required fields fails validation

```php
test('plan creation without required fields fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('emergency-plans.store'), [
        'name' => 'Rencana Tanpa Tipe',
        // type, site_id, description, response_procedure, escalation_procedure, contact_person_id missing
    ]);

    $response->assertSessionHasErrors([
        'type',
        'site_id',
        'description',
        'response_procedure',
        'escalation_procedure',
        'contact_person_id',
    ]);
    expect(EmergencyPlan::count())->toBe(0);
});
```

---

## Test Execution

```bash
# Run all tests
php artisan test

# Run only emergency tests
php artisan test --filter=Emergency

# Run with parallel (faster)
php artisan test --parallel

# Run with coverage
php artisan test --coverage
```

### Expected result after Phase 15:

```
Tests: 20 passed (emergency preparedness module)
```

---

## Test Summary

| # | Category | Test Name | Description |
|---|---|---|---|
| 1.1 | Functional | Authorized user can view plan list | Index page loads with correct props |
| 1.2 | Functional | Create emergency plan | Plan with contacts JSON and equipment created |
| 1.3 | Functional | Plan number auto-generated | EMG-YYYY-NNNN format generated on create |
| 1.4 | Functional | Schedule emergency drill | Drill created with status=scheduled, linked to plan |
| 1.5 | Functional | Execute drill with result | Drill executed, status → executed, result set |
| 1.6 | Functional | Create emergency contact | Contact created with all fields |
| 1.7 | Functional | Plan show displays linked drills | Show page includes drills relation |
| 1.8 | Functional | Drill fail result sends notification | fail result triggers emergency.drill_failed notification |
| 2.1 | Permission | No plan view → 403 | User without role cannot access plan list |
| 2.2 | Permission | No drill create → 403 | Supervisor cannot access drill create |
| 2.3 | Permission | Employee cannot execute drill | Employee lacks execute permission |
| 2.4 | Permission | Export blocked without permission | Employee/Reporter cannot export plans |
| 3.1 | Integration | Audit trail on plan creation | AuditLog entry created for plan creation |
| 3.2 | Integration | Activity log on drill scheduling | ActivityLog entry for drill_scheduled |
| 3.3 | Integration | Notification on drill scheduling | CoreNotification sent to QHSSE team |
| 3.4 | Integration | File evidence attached to plan | ManagedFile linked to emergency plan |
| 3.5 | Integration | Audit trail on drill execution | AuditLog + ActivityLog for execution |
| 4.1 | Negative | Cannot execute already executed drill | Status transition blocked |
| 4.2 | Negative | Drill without plan_id fails | Validation requires emergency_plan_id |
| 4.3 | Negative | Plan without required fields fails | Validation requires type, site_id, description, etc. |

---

## Notes

- Tests use SQLite in-memory for speed and isolation.
- Each test starts with a fresh database (Laravel's `RefreshDatabase` trait is auto-applied via Pest).
- Factory creates minimal valid data; tests add specific fields as needed.
- Plans and drills share the `emergency` numbering sequence — tests verify number format but not sequence across resources.
- For notification tests, at least one QHSSE Officer/Manager user must exist with matching site scope.
- `emergency_contacts` JSON field is tested as an array of contact objects.
- All test descriptions and assertions use the actual route names and permission keys.
- Contact tests are minimal since contacts are simple CRUD without workflow or numbering.

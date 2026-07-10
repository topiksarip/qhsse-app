# Test Cases — CAPA / Corrective & Preventive Action Tracking

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

Test file: `tests/Feature/Modules/Capa/CapaActionTest.php`

## Factory Definition

File: `database/factories/Modules/Capa/CapaActionFactory.php`

```php
<?php

namespace Database\Factories\Modules\Capa;

use App\Models\Modules\Capa\CapaAction;
use App\Models\Site;
use App\Models\Department;
use App\Models\User;
use App\Models\Severity;
use App\Models\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;

class CapaActionFactory extends Factory
{
    protected $model = CapaAction::class;

    public function definition(): array
    {
        return [
            'action_number' => 'ACT-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(3),
            'source_module' => fake()->randomElement(['incident', 'inspection', 'audit', 'manual']),
            'source_reference_id' => null, // Set in state or test
            'source_type' => fake()->randomElement(['corrective', 'preventive', null]),
            'site_id' => Site::factory(),
            'department_id' => null,
            'assigned_to' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => null,
            'due_date' => fake()->optional(0.7)->dateTimeBetween('+1 day', '+30 days'),
            'severity_id' => null,
            'priority_id' => Priority::factory(),
            'status' => 'open',
            'verification_note' => null,
            'verified_by' => null,
            'verified_at' => null,
            'closed_at' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->subDays(5),
            'status' => 'in_progress',
        ]);
    }

    public function withSource(string $module, int $referenceId): static
    {
        return $this->state(fn (array $attributes) => [
            'source_module' => $module,
            'source_reference_id' => $referenceId,
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_module' => 'manual',
            'source_reference_id' => null,
        ]);
    }
}
```

## Helper Trait

```php
<?php

namespace Tests\Feature\Modules\Capa;

use App\Models\User;
use App\Models\Site;
use App\Models\Severity;
use App\Models\Priority;

trait CreatesCapaTestUser
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

    protected function supervisor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Supervisor');
        return $user;
    }

    protected function employeeUser(): User
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

### 1.1 Authorized user can view CAPA list

```php
test('authorized user can view capa action list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('capa.actions.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Capa/Index')
        ->has('items')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create CAPA action

```php
test('authorized user can create capa action', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $priority = $this->createPriority();
    $assignee = User::factory()->create();

    $response = $this->post(route('capa.actions.store'), [
        'title' => 'Perbaikan pipa bocor di area produksi',
        'description' => 'Pipa bocor di area produksi menyebabkan tumpahan minyak. Perlu diganti segmen pipa sepanjang 5 meter.',
        'source_module' => 'manual',
        'source_reference_id' => null,
        'source_type' => 'corrective',
        'site_id' => $site->id,
        'department_id' => null,
        'assigned_to' => $assignee->id,
        'due_date' => now()->addDays(14)->format('Y-m-d'),
        'priority_id' => $priority->id,
        'severity_id' => null,
    ]);

    $response->assertRedirect(route('capa.actions.show', CapaAction::first()));

    $action = CapaAction::first();
    expect($action)->not->toBeNull();
    expect($action->title)->toBe('Perbaikan pipa bocor di area produksi');
    expect($action->status)->toBe('open');
    expect($action->assigned_by)->toBe($admin->id);
    expect($action->source_module)->toBe('manual');
    expect($action->source_reference_id)->toBeNull();
});
```

### 1.3 CAPA action number is auto-generated on create

```php
test('capa action number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $priority = $this->createPriority();
    $assignee = User::factory()->create();

    $this->post(route('capa.actions.store'), [
        'title' => 'Test CAPA action',
        'description' => 'Test description for CAPA action numbering validation.',
        'source_module' => 'manual',
        'site_id' => $site->id,
        'assigned_to' => $assignee->id,
        'due_date' => now()->addDays(14)->format('Y-m-d'),
        'priority_id' => $priority->id,
    ]);

    $action = CapaAction::first();
    expect($action->action_number)->not->toBeNull();
    expect($action->action_number)->toMatch('/^ACT-\d{4}-\d{4}$/');
});
```

### 1.4 CAPA action with missing title fails validation

```php
test('capa action with missing title fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $priority = $this->createPriority();
    $assignee = User::factory()->create();

    $response = $this->post(route('capa.actions.store'), [
        'description' => 'Test description for CAPA action without title.',
        'source_module' => 'manual',
        'site_id' => $site->id,
        'assigned_to' => $assignee->id,
        'due_date' => now()->addDays(14)->format('Y-m-d'),
        'priority_id' => $priority->id,
    ]);

    $response->assertSessionHasErrors(['title']);
    expect(CapaAction::count())->toBe(0);
});
```

### 1.5 Open CAPA action can be started

```php
test('open capa action can be started', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $action = CapaAction::factory()->create([
        'status' => 'open',
        'assigned_to' => $admin->id,
        'assigned_by' => $admin->id,
    ]);

    $response = $this->post(route('capa.actions.start', $action));

    $response->assertRedirect();
    $action->refresh();
    expect($action->status)->toBe('in_progress');
    expect($action->assigned_at)->not->toBeNull();
});
```

### 1.6 In progress CAPA can be submitted for verification

```php
test('in progress capa can be submitted for verification', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $action = CapaAction::factory()->create([
        'status' => 'in_progress',
        'assigned_to' => $admin->id,
        'assigned_by' => $admin->id,
    ]);

    // Attach evidence file
    ManagedFile::factory()->create([
        'module_name' => 'capa',
        'reference_id' => $action->id,
        'collection' => 'evidence',
        'deleted_at' => null,
    ]);

    $response = $this->post(route('capa.actions.submit', $action));

    $response->assertRedirect();
    $action->refresh();
    expect($action->status)->toBe('waiting_verification');
});
```

### 1.7 Waiting verification CAPA can be verified and closed

```php
test('waiting verification capa can be verified and closed', function () {
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $action = CapaAction::factory()->create([
        'status' => 'waiting_verification',
        'assigned_to' => User::factory()->create()->id,
        'assigned_by' => $officer->id,
    ]);

    $response = $this->post(route('capa.actions.verify', $action), [
        'verification_note' => 'Pipa sudah diganti dan area dibersihkan dengan baik.',
    ]);

    $response->assertRedirect();
    $action->refresh();
    expect($action->status)->toBe('closed');
    expect($action->verification_note)->toBe('Pipa sudah diganti dan area dibersihkan dengan baik.');
    expect($action->verified_by)->toBe($officer->id);
    expect($action->verified_at)->not->toBeNull();
    expect($action->closed_at)->not->toBeNull();
});
```

### 1.8 Rejected CAPA can be restarted

```php
test('rejected capa can be restarted', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $action = CapaAction::factory()->create([
        'status' => 'rejected',
        'assigned_to' => $admin->id,
        'assigned_by' => $admin->id,
    ]);

    $response = $this->post(route('capa.actions.restart', $action));

    $response->assertRedirect();
    $action->refresh();
    expect($action->status)->toBe('in_progress');
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without capa.actions.view gets 403 on list

```php
test('user without capa.actions.view gets 403 on capa list', function () {
    $user = User::factory()->create();
    // No role assigned — no permissions at all

    $this->actingAs($user);

    $response = $this->get(route('capa.actions.index'));

    $response->assertForbidden();
});
```

### 2.2 Employee cannot create CAPA action

```php
test('employee cannot create capa action', function () {
    $employee = $this->employeeUser();
    $this->actingAs($employee);

    $response = $this->get(route('capa.actions.create'));

    $response->assertForbidden();
});
```

### 2.3 Supervisor cannot verify/close CAPA action

```php
test('supervisor cannot verify capa action', function () {
    $supervisor = $this->supervisor();
    $this->actingAs($supervisor);

    $action = CapaAction::factory()->create([
        'status' => 'waiting_verification',
    ]);

    $response = $this->post(route('capa.actions.verify', $action), [
        'verification_note' => 'Test verification note by supervisor.',
    ]);

    $response->assertForbidden();
    $action->refresh();
    expect($action->status)->toBe('waiting_verification');
});
```

### 2.4 Employee cannot reject CAPA action

```php
test('employee cannot reject capa action', function () {
    $employee = $this->employeeUser();
    $this->actingAs($employee);

    $action = CapaAction::factory()->create([
        'status' => 'waiting_verification',
        'assigned_to' => $employee->id,
    ]);

    $response = $this->post(route('capa.actions.reject', $action), [
        'reason' => 'Test reject reason by employee.',
    ]);

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Evidence file can be attached to CAPA action

```php
test('evidence file can be attached to capa action', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $action = CapaAction::factory()->create();

    $file = UploadedFile::fake()->image('evidence.jpg');

    $response = $this->post('/core/files', [
        'file' => $file,
        'module_name' => 'capa',
        'reference_id' => $action->id,
        'collection' => 'evidence',
    ]);

    $response->assertStatus(200);

    expect(
        ManagedFile::where('module_name', 'capa')
            ->where('reference_id', $action->id)
            ->where('collection', 'evidence')
            ->count()
    )->toBe(1);
});
```

### 3.2 Audit trail records CAPA creation

```php
test('audit trail records capa creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $priority = $this->createPriority();
    $assignee = User::factory()->create();

    $this->post(route('capa.actions.store'), [
        'title' => 'Audited CAPA action',
        'description' => 'Test description for audit trail verification.',
        'source_module' => 'manual',
        'site_id' => $site->id,
        'assigned_to' => $assignee->id,
        'due_date' => now()->addDays(14)->format('Y-m-d'),
        'priority_id' => $priority->id,
    ]);

    $action = CapaAction::first();

    expect(
        AuditLog::where('module_name', 'capa')
            ->where('reference_id', $action->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Audit trail records status change on submit

```php
test('audit trail records status change on capa submit', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $action = CapaAction::factory()->create([
        'status' => 'in_progress',
        'assigned_to' => $admin->id,
        'assigned_by' => $admin->id,
    ]);

    ManagedFile::factory()->create([
        'module_name' => 'capa',
        'reference_id' => $action->id,
        'collection' => 'evidence',
        'deleted_at' => null,
    ]);

    $this->post(route('capa.actions.submit', $action));

    expect(
        AuditLog::where('module_name', 'capa')
            ->where('reference_id', $action->id)
            ->where('event', 'workflow.transitioned')
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Notification created on CAPA assignment

```php
test('notification created on capa assignment', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $priority = $this->createPriority();
    $assignee = User::factory()->create();

    $this->post(route('capa.actions.store'), [
        'title' => 'Test assignment notification',
        'description' => 'Test description for notification on assignment.',
        'source_module' => 'manual',
        'site_id' => $site->id,
        'assigned_to' => $assignee->id,
        'due_date' => now()->addDays(14)->format('Y-m-d'),
        'priority_id' => $priority->id,
    ]);

    $action = CapaAction::first();

    expect(
        CoreNotification::where('type', 'capa.assigned')
            ->where('module_name', 'capa')
            ->where('reference_id', $action->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.5 Cross-module link: CAPA created from incident source

```php
test('cross-module link capa created from incident source', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $priority = $this->createPriority();
    $assignee = User::factory()->create();

    // Create a fake incident record as source
    $incident = IncidentReport::factory()->create();

    $response = $this->post(route('capa.actions.store'), [
        'title' => 'Corrective action for incident',
        'description' => 'Corrective action derived from incident investigation findings.',
        'source_module' => 'incident',
        'source_reference_id' => $incident->id,
        'source_type' => 'corrective',
        'site_id' => $site->id,
        'assigned_to' => $assignee->id,
        'due_date' => now()->addDays(14)->format('Y-m-d'),
        'priority_id' => $priority->id,
    ]);

    $response->assertRedirect(route('capa.actions.show', CapaAction::first()));

    $action = CapaAction::first();
    expect($action->source_module)->toBe('incident');
    expect($action->source_reference_id)->toBe($incident->id);
    expect($action->source_type)->toBe('corrective');

    // Verify the show page includes source record link
    $showResponse = $this->get(route('capa.actions.show', $action));
    $showResponse->assertStatus(200);
    $showResponse->assertInertia(fn ($page) => $page
        ->has('action.sourceRecord')
        ->where('action.sourceRecord.number', $incident->incident_number)
    );
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot submit CAPA without evidence

```php
test('cannot submit capa without evidence', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $action = CapaAction::factory()->create([
        'status' => 'in_progress',
        'assigned_to' => $admin->id,
        'assigned_by' => $admin->id,
    ]);

    // No evidence file attached

    $response = $this->post(route('capa.actions.submit', $action));

    $response->assertSessionHasErrors(['evidence']);
    $action->refresh();
    expect($action->status)->toBe('in_progress');
});
```

### 4.2 Cannot start non-open CAPA action

```php
test('cannot start non-open capa action', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $action = CapaAction::factory()->create([
        'status' => 'waiting_verification',
        'assigned_to' => $admin->id,
    ]);

    $response = $this->post(route('capa.actions.start', $action));

    $response->assertRedirect();
    $response->assertSessionHasErrors(['workflow']);
    $action->refresh();
    expect($action->status)->toBe('waiting_verification');
});
```

### 4.3 Cannot verify rejected CAPA action

```php
test('cannot verify rejected capa action', function () {
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $action = CapaAction::factory()->create([
        'status' => 'rejected',
    ]);

    $response = $this->post(route('capa.actions.verify', $action), [
        'verification_note' => 'Attempting to verify a rejected action.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['workflow']);
    $action->refresh();
    expect($action->status)->toBe('rejected');
});
```

---

## Test Run Commands

```bash
# Run all CAPA tests
php artisan test tests/Feature/Modules/Capa/CapaActionTest.php

# Run with coverage
php artisan test tests/Feature/Modules/Capa/CapaActionTest.php --coverage

# Run only permission tests
php artisan test tests/Feature/Modules/Capa/CapaActionTest.php --filter='Permission'
```

## Test Summary

| # | Category | Test Name | Key Assertion |
|---|---|---|---|
| 1.1 | Functional | Authorized user can view capa action list | Status 200, Inertia component |
| 1.2 | Functional | Authorized user can create capa action | Redirect, DB record, status=open |
| 1.3 | Functional | CAPA action number is auto-generated on create | Matches `/^ACT-\d{4}-\d{4}$/` |
| 1.4 | Functional | CAPA action with missing title fails validation | Session errors, no DB record |
| 1.5 | Functional | Open CAPA action can be started | status=in_progress, assigned_at set |
| 1.6 | Functional | In progress CAPA can be submitted for verification | status=waiting_verification |
| 1.7 | Functional | Waiting verification CAPA can be verified and closed | status=closed, verified_by set |
| 1.8 | Functional | Rejected CAPA can be restarted | status=in_progress |
| 2.1 | Permission | User without capa.actions.view gets 403 | 403 Forbidden |
| 2.2 | Permission | Employee cannot create CAPA action | 403 Forbidden |
| 2.3 | Permission | Supervisor cannot verify CAPA action | 403 Forbidden, status unchanged |
| 2.4 | Permission | Employee cannot reject CAPA action | 403 Forbidden |
| 3.1 | Integration | Evidence file can be attached to CAPA action | managed_files count = 1 |
| 3.2 | Integration | Audit trail records CAPA creation | AuditLog exists |
| 3.3 | Integration | Audit trail records status change on submit | AuditLog with workflow.transitioned |
| 3.4 | Integration | Notification created on CAPA assignment | CoreNotification exists |
| 3.5 | Integration | Cross-module link: CAPA created from incident source | source_module=incident, sourceRecord.number matches |
| 4.1 | Negative | Cannot submit CAPA without evidence | Session error, status unchanged |
| 4.2 | Negative | Cannot start non-open CAPA action | Session error, status unchanged |
| 4.3 | Negative | Cannot verify rejected CAPA action | Session error, status unchanged |

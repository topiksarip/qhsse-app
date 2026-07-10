# Test Cases — Investigation & RCA

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

Test file: `tests/Feature/Modules/Investigation/InvestigationTest.php`

## Factory Definition

File: `database/factories/Modules/Investigation/InvestigationFactory.php`

```php
<?php

namespace Database\Factories\Modules\Investigation;

use App\Models\Modules\Incident\Incident;
use App\Models\Modules\Investigation\Investigation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestigationFactory extends Factory
{
    protected $model = Investigation::class;

    public function definition(): array
    {
        return [
            'investigation_number'  => 'INV-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'incident_id'           => Incident::factory(),
            'title'                 => fake()->sentence(6),
            'status'                => 'draft',
            'root_cause'            => null,
            'five_whys'             => null,
            'fishbone'              => null,
            'contributing_factors'  => null,
            'timeline_events'       => null,
            'recommendations'       => null,
            'investigator_id'       => User::factory(),
            'started_at'            => null,
            'completed_at'          => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attrs) => [
            'status'     => 'in_progress',
            'started_at' => now()->subDays(3),
            'five_whys'  => [
                ['level' => 1, 'question' => 'Mengapa?', 'answer' => 'Jawaban awal.', 'is_root_cause' => false],
                ['level' => 2, 'question' => 'Mengapa?', 'answer' => 'Root cause.', 'is_root_cause' => true],
            ],
            'fishbone' => [
                ['category' => 'Man', 'causes' => ['Test cause']],
            ],
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attrs) => [
            'status'          => 'completed',
            'started_at'      => now()->subDays(10),
            'completed_at'    => now()->subDays(2),
            'root_cause'      => 'Root cause identified during investigation.',
            'recommendations' => 'Implement improved maintenance schedule.',
            'five_whys'  => [
                ['level' => 1, 'question' => 'Mengapa?', 'answer' => 'Jawaban awal.', 'is_root_cause' => false],
                ['level' => 2, 'question' => 'Mengapa?', 'answer' => 'Root cause.', 'is_root_cause' => true],
            ],
            'fishbone' => [
                ['category' => 'Man', 'causes' => ['Test cause']],
                ['category' => 'Method', 'causes' => ['Test method cause']],
            ],
        ]);
    }
}
```

## Helper Trait

```php
<?php

namespace Tests\Feature\Modules\Investigation;

use App\Models\Modules\Incident\Incident;
use App\Models\Modules\Investigation\Investigation;
use App\Models\Site;
use App\Models\User;
use App\Models\Severity;
use App\Models\Priority;
use App\Core\Workflow\WorkflowService;
use App\Models\Core\WorkflowInstance;

trait CreatesInvestigationTestUser
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

    protected function supervisor(): User
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

    protected function createSeverity(): Severity
    {
        return Severity::factory()->create();
    }

    protected function createPriority(): Priority
    {
        return Priority::factory()->create();
    }

    protected function createIncident(array $overrides = []): Incident
    {
        return Incident::factory()->create(array_merge([
            'status' => 'under_review',
        ], $overrides));
    }

    protected function createInvestigation(array $overrides = []): Investigation
    {
        return Investigation::factory()->create(array_merge([
            'status' => 'draft',
        ], $overrides));
    }

    protected function startWorkflow(Investigation $investigation, User $actor): void
    {
        WorkflowService::start('investigation', $investigation->id, $actor);
    }

    protected function setWorkflowStatus(Investigation $investigation, string $status): void
    {
        WorkflowInstance::where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->update(['current_status' => $status]);
    }

    protected function validFiveWhys(): array
    {
        return [
            ['level' => 1, 'question' => 'Mengapa kecelakaan terjadi?', 'answer' => 'Pekerja terpeleset di lantai basah.', 'is_root_cause' => false],
            ['level' => 2, 'question' => 'Mengapa lantai basah?', 'answer' => 'Terdapat tumpahan oli dari mesin.', 'is_root_cause' => false],
            ['level' => 3, 'question' => 'Mengapa terjadi tumpahan oli?', 'answer' => 'Seal pada mesin rusak dan tidak terdeteksi.', 'is_root_cause' => true],
        ];
    }

    protected function validFishbone(): array
    {
        return [
            ['category' => 'Man', 'causes' => ['Operator tidak mendapat training SOP terbaru']],
            ['category' => 'Method', 'causes' => ['Prosedur LOTO tidak diikuti']],
            ['category' => 'Machine', 'causes' => ['Seal mesin rusak']],
            ['category' => 'Material', 'causes' => []],
            ['category' => 'Environment', 'causes' => ['Pencahayaan area kurang optimal']],
            ['category' => 'Management', 'causes' => ['Tidak ada sistem monitoring maintenance']],
        ];
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view investigation list

```php
test('authorized user can view investigation list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('investigation.reports.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Investigation/Index')
        ->has('items')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create investigation draft

```php
test('authorized user can create investigation draft', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = $this->createIncident(['status' => 'under_review']);

    $response = $this->post(route('investigation.reports.store'), [
        'incident_id'     => $incident->id,
        'title'           => 'Analisis Kecelakaan di Area Produksi',
        'investigator_id' => $admin->id,
        'five_whys'       => $this->validFiveWhys(),
        'fishbone'        => $this->validFishbone(),
        'action'          => 'draft',
    ]);

    $response->assertRedirect(route('investigation.reports.show', Investigation::first()));

    $investigation = Investigation::first();
    expect($investigation)->not->toBeNull();
    expect($investigation->title)->toBe('Analisis Kecelakaan di Area Produksi');
    expect($investigation->status)->toBe('draft');
    expect($investigation->investigator_id)->toBe($admin->id);
});
```

### 1.3 Investigation number is auto-generated on create

```php
test('investigation number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = $this->createIncident();

    $this->post(route('investigation.reports.store'), [
        'incident_id'     => $incident->id,
        'title'           => 'Test investigation',
        'investigator_id' => $admin->id,
        'action'          => 'draft',
    ]);

    $investigation = Investigation::first();
    expect($investigation->investigation_number)->not->toBeNull();
    expect($investigation->investigation_number)->toMatch('/^INV-\d{4}-\d{4}$/');
});
```

### 1.4 Draft investigation can be started with valid data

```php
test('draft investigation can be started with valid data', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $investigation = $this->createInvestigation([
        'status'          => 'draft',
        'investigator_id' => $admin->id,
        'five_whys'       => $this->validFiveWhys(),
        'fishbone'        => $this->validFishbone(),
    ]);
    $this->startWorkflow($investigation, $admin);

    $response = $this->post(route('investigation.reports.start', $investigation));

    $response->assertRedirect();
    $investigation->refresh();
    expect($investigation->status)->toBe('in_progress');
    expect($investigation->started_at)->not->toBeNull();
});
```

### 1.5 Start fails without five_whys data

```php
test('start fails without five_whys data', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $investigation = $this->createInvestigation([
        'status'          => 'draft',
        'investigator_id' => $admin->id,
        'five_whys'       => null,
        'fishbone'        => $this->validFishbone(),
    ]);
    $this->startWorkflow($investigation, $admin);

    $response = $this->post(route('investigation.reports.start', $investigation));

    $response->assertSessionHasErrors(['five_whys']);
    expect($investigation->fresh()->status)->toBe('draft');
});
```

### 1.6 Start fails without fishbone cause

```php
test('start fails without fishbone cause', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $investigation = $this->createInvestigation([
        'status'          => 'draft',
        'investigator_id' => $admin->id,
        'five_whys'       => $this->validFiveWhys(),
        'fishbone'        => [
            ['category' => 'Man', 'causes' => []],
            ['category' => 'Method', 'causes' => []],
            ['category' => 'Machine', 'causes' => []],
            ['category' => 'Material', 'causes' => []],
            ['category' => 'Environment', 'causes' => []],
            ['category' => 'Management', 'causes' => []],
        ],
    ]);
    $this->startWorkflow($investigation, $admin);

    $response = $this->post(route('investigation.reports.start', $investigation));

    $response->assertSessionHasErrors(['fishbone']);
    expect($investigation->fresh()->status)->toBe('draft');
});
```

### 1.7 In progress investigation can be completed with reason

```php
test('in progress investigation can be completed with reason', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $investigation = $this->createInvestigation([
        'status'          => 'in_progress',
        'investigator_id' => $admin->id,
        'root_cause'      => 'Tidak ada sistem monitoring maintenance yang efektif.',
        'recommendations' => 'Implementasi sistem monitoring maintenance terjadwal.',
        'started_at'      => now()->subDays(5),
    ]);
    $this->startWorkflow($investigation, $admin);
    $this->setWorkflowStatus($investigation, 'in_progress');

    $response = $this->post(route('investigation.reports.complete', $investigation), [
        'reason' => 'Investigasi selesai, root cause teridentifikasi, rekomendasi telah disusun.',
    ]);

    $response->assertRedirect();
    $investigation->refresh();
    expect($investigation->status)->toBe('completed');
    expect($investigation->completed_at)->not->toBeNull();
});
```

### 1.8 Investigation can be cancelled with reason

```php
test('investigation can be cancelled with reason', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $investigation = $this->createInvestigation([
        'status'          => 'in_progress',
        'investigator_id' => $admin->id,
        'started_at'      => now()->subDays(3),
    ]);
    $this->startWorkflow($investigation, $admin);
    $this->setWorkflowStatus($investigation, 'in_progress');

    $response = $this->post(route('investigation.reports.cancel', $investigation), [
        'reason' => 'Investigasi tidak dapat dilanjutkan karena keterbatasan data dan saksi.',
    ]);

    $response->assertRedirect();
    $investigation->refresh();
    expect($investigation->status)->toBe('cancelled');
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without investigation.reports.view gets 403 on list

```php
test('user without investigation.reports.view gets 403 on list', function () {
    $reporter = $this->reporterUser();
    // Employee / Reporter role does not have investigation.reports.view by default
    $this->actingAs($reporter);

    $response = $this->get(route('investigation.reports.index'));

    $response->assertForbidden();
});
```

### 2.2 User without investigation.reports.create gets 403 on create form

```php
test('user without investigation.reports.create gets 403 on create form', function () {
    $supervisor = $this->supervisor();
    // Supervisor has view only, not create
    $this->actingAs($supervisor);

    $response = $this->get(route('investigation.reports.create'));

    $response->assertForbidden();
});
```

### 2.3 User without investigation.reports.close cannot complete

```php
test('user without investigation.reports.close cannot complete investigation', function () {
    $supervisor = $this->supervisor();
    // Supervisor does not have investigation.reports.close
    $this->actingAs($supervisor);

    $investigation = $this->createInvestigation(['status' => 'in_progress']);

    $response = $this->post(route('investigation.reports.complete', $investigation), [
        'reason' => 'Test reason for completion.',
    ]);

    $response->assertForbidden();
});
```

### 2.4 Export blocked without investigation.reports.export

```php
test('export blocked without investigation.reports.export', function () {
    $reporter = $this->reporterUser();
    $this->actingAs($reporter);

    $response = $this->get(route('investigation.reports.export'));

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Audit trail records investigation creation

```php
test('audit trail records investigation creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = $this->createIncident();

    $this->post(route('investigation.reports.store'), [
        'incident_id'     => $incident->id,
        'title'           => 'Audited investigation',
        'investigator_id' => $admin->id,
        'action'          => 'draft',
    ]);

    $investigation = Investigation::first();

    expect(
        \App\Models\Core\AuditLog::where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.2 Activity log records status change on start

```php
test('activity log records status change on start', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $investigation = $this->createInvestigation([
        'status'          => 'draft',
        'investigator_id' => $admin->id,
        'five_whys'       => $this->validFiveWhys(),
        'fishbone'        => $this->validFishbone(),
    ]);
    $this->startWorkflow($investigation, $admin);

    $this->post(route('investigation.reports.start', $investigation));

    expect(
        \App\Models\Core\ActivityLog::where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->where('event', 'investigation.started')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Notification created on investigation start

```php
test('notification created on investigation start', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create a QHSSE Manager to receive notification
    $manager = $this->qhsseManager();

    $incident = $this->createIncident([
        'reporter_id' => $admin->id,
    ]);

    $investigation = $this->createInvestigation([
        'incident_id'     => $incident->id,
        'status'          => 'draft',
        'investigator_id' => $admin->id,
        'five_whys'       => $this->validFiveWhys(),
        'fishbone'        => $this->validFishbone(),
    ]);
    $this->startWorkflow($investigation, $admin);

    $this->post(route('investigation.reports.start', $investigation));

    expect(
        \App\Models\Core\CoreNotification::where('type', 'investigation.started')
            ->where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Investigation links to incident correctly

```php
test('investigation links to incident correctly', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $incident = $this->createIncident(['status' => 'under_review']);

    $this->post(route('investigation.reports.store'), [
        'incident_id'     => $incident->id,
        'title'           => 'Linked investigation',
        'investigator_id' => $admin->id,
        'action'          => 'draft',
    ]);

    $investigation = Investigation::first();
    expect($investigation->incident_id)->toBe($incident->id);
    expect($investigation->incident->id)->toBe($incident->id);
});
```

### 3.5 Team members can be attached to investigation

```php
test('team members can be attached to investigation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $officer2 = $this->qhsseOfficer();
    $incident = $this->createIncident();

    $this->post(route('investigation.reports.store'), [
        'incident_id'     => $incident->id,
        'title'           => 'Team investigation',
        'investigator_id' => $admin->id,
        'team_members'    => [
            ['user_id' => $admin->id, 'role' => 'lead_investigator'],
            ['user_id' => $officer2->id, 'role' => 'investigator'],
        ],
        'action' => 'draft',
    ]);

    $investigation = Investigation::first();
    expect($investigation->teamMembers)->toHaveCount(2);
    expect($investigation->teamMembers->pluck('id'))->toContain($admin->id);
    expect($investigation->teamMembers->pluck('id'))->toContain($officer2->id);
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot start non-draft investigation

```php
test('cannot start non-draft investigation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $investigation = $this->createInvestigation([
        'status'     => 'completed',
        'started_at' => now()->subDays(10),
        'completed_at' => now()->subDays(2),
    ]);

    $response = $this->post(route('investigation.reports.start', $investigation));

    $response->assertSessionHasErrors(['workflow']);
    expect($investigation->fresh()->status)->toBe('completed');
});
```

### 4.2 Complete without reason fails validation

```php
test('complete without reason fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $investigation = $this->createInvestigation([
        'status'          => 'in_progress',
        'investigator_id' => $admin->id,
        'root_cause'      => 'Root cause identified.',
        'recommendations' => 'Recommendations provided.',
        'started_at'      => now()->subDays(5),
    ]);
    $this->startWorkflow($investigation, $admin);
    $this->setWorkflowStatus($investigation, 'in_progress');

    $response = $this->post(route('investigation.reports.complete', $investigation), []);

    $response->assertSessionHasErrors(['reason']);
});
```

### 4.3 Complete without root_cause fails

```php
test('complete without root_cause fails', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $investigation = $this->createInvestigation([
        'status'          => 'in_progress',
        'investigator_id' => $admin->id,
        'root_cause'      => null,
        'recommendations' => 'Recommendations provided.',
        'started_at'      => now()->subDays(5),
    ]);
    $this->startWorkflow($investigation, $admin);
    $this->setWorkflowStatus($investigation, 'in_progress');

    $response = $this->post(route('investigation.reports.complete', $investigation), [
        'reason' => 'Investigasi selesai dengan alasan yang valid.',
    ]);

    $response->assertSessionHasErrors(['root_cause']);
    expect($investigation->fresh()->status)->toBe('in_progress');
});
```

---

## Test Execution

```bash
# Run all tests
php artisan test

# Run only investigation tests
php artisan test --filter=Investigation

# Run with parallel (faster)
php artisan test --parallel

# Run with coverage
php artisan test --coverage
```

### Expected result after Phase 2:

```
Tests: 119 passed (99 Phase 0+1 + 20 Phase 2)
```

---

## Notes

- Tests use SQLite in-memory for speed and isolation.
- Each test starts with a fresh database (Laravel's `RefreshDatabase` trait is auto-applied via Pest).
- Factory creates minimal valid data; tests add specific fields as needed.
- `WorkflowService::start()` must be called after creating an Investigation in tests that need workflow transitions.
- For tests that need a specific status, manually update the `workflow_instances.current_status` field.
- `CorePermissions::roleMap()` defines which permissions each role gets — tests assume this is seeded.
- Notification tests require at least one QHSSE Manager user to exist for `notifyMany` to send to.
- The `Investigation` model uses JSON casts for `five_whys`, `fishbone`, `contributing_factors`, `timeline_events` — ensure the model has proper `$casts` definitions.
- The `validFiveWhys()` and `validFishbone()` helper methods return properly structured arrays matching the JSON column structures defined in DATA_MODEL.md.

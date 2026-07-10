# Test Cases — Inspection Checklist

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
- `tests/Feature/Modules/Inspection/InspectionTemplateTest.php`
- `tests/Feature/Modules/Inspection/InspectionTest.php`

## Factory Definitions

### InspectionTemplateFactory

File: `database/factories/Modules/Inspection/InspectionTemplateFactory.php`

```php
public function definition(): array
{
    return [
        'code' => strtoupper(fake()->lexify('???-???')),
        'name' => fake()->sentence(4),
        'description' => fake()->optional(0.7)->paragraph(2),
        'category' => fake()->randomElement([
            'safety', 'environment', 'equipment', 'fire',
            'housekeeping', 'security', 'quality', 'compliance',
        ]),
        'is_active' => true,
    ];
}
```

### InspectionItemFactory

File: `database/factories/Modules/Inspection/InspectionItemFactory.php`

```php
public function definition(): array
{
    return [
        'inspection_template_id' => InspectionTemplate::factory(),
        'question' => fake()->sentence(6) . '?',
        'type' => fake()->randomElement(['yes_no', 'safe_unsafe', 'na', 'scale', 'text']),
        'category' => fake()->optional(0.5)->word(),
        'is_required' => fake()->boolean(80),
        'order' => fake()->numberBetween(1, 20),
    ];
}
```

### InspectionFactory

File: `database/factories/Modules/Inspection/InspectionFactory.php`

```php
public function definition(): array
{
    return [
        'inspection_number' => 'INS-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'inspection_template_id' => InspectionTemplate::factory(),
        'site_id' => Site::factory(),
        'area_id' => null,
        'inspector_id' => User::factory(),
        'scheduled_at' => fake()->dateTimeBetween('-1 week', '+1 week')->format('Y-m-d'),
        'executed_at' => null,
        'status' => 'pending',
        'overall_result' => 'pending',
        'notes' => fake()->optional(0.5)->paragraph(2),
    ];
}
```

### InspectionResultFactory

File: `database/factories/Modules/Inspection/InspectionResultFactory.php`

```php
public function definition(): array
{
    return [
        'inspection_id' => Inspection::factory(),
        'inspection_item_id' => InspectionItem::factory(),
        'answer' => null,
        'remark' => fake()->optional(0.3)->paragraph(1),
        'is_unsafe' => false,
    ];
}
```

## Helper Trait

```php
trait CreatesInspectionTestUser
{
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        return $user;
    }

    protected function qhsseManagerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Manager');
        return $user;
    }

    protected function qhsseOfficerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Officer');
        return $user;
    }

    protected function supervisorUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Supervisor');
        return $user;
    }

    protected function createSite(): Site
    {
        return Site::factory()->create();
    }

    protected function createTemplate(array $overrides = []): InspectionTemplate
    {
        $template = InspectionTemplate::factory()->create($overrides);
        // Create 3 items by default
        InspectionItem::factory()->count(3)->create([
            'inspection_template_id' => $template->id,
        ]);
        return $template;
    }

    protected function createInspection(array $overrides = []): Inspection
    {
        $template = $overrides['inspection_template_id'] ?? null
            ? InspectionTemplate::find($overrides['inspection_template_id'])
            : $this->createTemplate();

        $inspection = Inspection::factory()->create(array_merge([
            'inspection_template_id' => $template->id,
            'site_id' => $this->createSite()->id,
            'inspector_id' => $this->qhsseOfficerUser()->id,
        ], $overrides));

        // Create empty results for each item
        foreach ($template->items as $item) {
            InspectionResult::factory()->create([
                'inspection_id' => $inspection->id,
                'inspection_item_id' => $item->id,
            ]);
        }

        return $inspection;
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view template list

```php
test('authorized user can view inspection template list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('inspection.templates.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Inspection/Template/Index')
        ->has('templates')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create template with items

```php
test('authorized user can create inspection template with items', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('inspection.templates.store'), [
        'code' => 'SAF-001',
        'name' => 'Inspeksi Safety Harian',
        'description' => 'Template inspeksi keselamatan kerja harian.',
        'category' => 'safety',
        'is_active' => true,
        'items' => [
            [
                'question' => 'Apakah semua pekerja memakai APD?',
                'type' => 'yes_no',
                'category' => 'PPE',
                'is_required' => true,
                'order' => 1,
            ],
            [
                'question' => 'Apakah fire extinguisher dalam kondisi baik?',
                'type' => 'safe_unsafe',
                'category' => 'Fire Safety',
                'is_required' => true,
                'order' => 2,
            ],
        ],
    ]);

    $response->assertRedirect(route('inspection.templates.show', InspectionTemplate::first()));

    $template = InspectionTemplate::first();
    expect($template)->not->toBeNull();
    expect($template->code)->toBe('SAF-001');
    expect($template->name)->toBe('Inspeksi Safety Harian');
    expect($template->items)->toHaveCount(2);
    expect($template->items[0]->question)->toBe('Apakah semua pekerja memakai APD?');
    expect($template->items[0]->type)->toBe('yes_no');
});
```

### 1.3 Authorized user can create inspection from template

```php
test('authorized user can create inspection from active template', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $template = $this->createTemplate();
    $site = $this->createSite();
    $inspector = $this->qhsseOfficerUser();

    $response = $this->post(route('inspection.inspections.store'), [
        'inspection_template_id' => $template->id,
        'site_id' => $site->id,
        'area_id' => null,
        'inspector_id' => $inspector->id,
        'scheduled_at' => '2026-07-15',
        'notes' => 'Inspeksi rutin mingguan.',
    ]);

    $response->assertRedirect(route('inspection.inspections.show', Inspection::first()));

    $inspection = Inspection::first();
    expect($inspection)->not->toBeNull();
    expect($inspection->status)->toBe('pending');
    expect($inspection->overall_result)->toBe('pending');
    expect($inspection->inspection_number)->toMatch('/^INS-\d{4}-\d{4}$/');
    expect($inspection->results)->toHaveCount(3); // 3 items from template
});
```

### 1.4 Inspection number is auto-generated on create

```php
test('inspection number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $template = $this->createTemplate();
    $site = $this->createSite();
    $inspector = $this->qhsseOfficerUser();

    $this->post(route('inspection.inspections.store'), [
        'inspection_template_id' => $template->id,
        'site_id' => $site->id,
        'inspector_id' => $inspector->id,
        'scheduled_at' => '2026-07-15',
    ]);

    $inspection = Inspection::first();
    expect($inspection->inspection_number)->not->toBeNull();
    expect($inspection->inspection_number)->toMatch('/^INS-\d{4}-\d{4}$/');
});
```

### 1.5 Pending inspection can be started

```php
test('pending inspection can be started', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $inspection = $this->createInspection(['status' => 'pending']);
    WorkflowService::start('inspection', $inspection->id, $admin);

    $response = $this->post(route('inspection.inspections.start', $inspection));

    $response->assertRedirect();
    $inspection->refresh();
    expect($inspection->status)->toBe('in_progress');
    expect($inspection->executed_at)->not->toBeNull();
});
```

### 1.6 Inspection results can be saved during execution

```php
test('inspection results can be saved during execution', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $inspection = $this->createInspection(['status' => 'in_progress']);
    $itemId = $inspection->template->items[0]->id;

    $response = $this->put(route('inspection.inspections.update', $inspection), [
        'results' => [
            [
                'inspection_item_id' => $itemId,
                'answer' => 'yes',
                'remark' => 'Semua pekerja memakai APD lengkap.',
            ],
        ],
        'notes' => 'Inspeksi berjalan baik.',
    ]);

    $response->assertStatus(200);
    $result = InspectionResult::where('inspection_id', $inspection->id)
        ->where('inspection_item_id', $itemId)
        ->first();
    expect($result->answer)->toBe('yes');
    expect($result->remark)->toBe('Semua pekerja memakai APD lengkap.');
    expect($result->is_unsafe)->toBeFalse();
});
```

### 1.7 In progress inspection can be completed with all required items answered

```php
test('in progress inspection can be completed when all required items answered', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create template with required items
    $template = InspectionTemplate::factory()->create();
    InspectionItem::factory()->create([
        'inspection_template_id' => $template->id,
        'type' => 'yes_no',
        'is_required' => true,
        'order' => 1,
    ]);
    InspectionItem::factory()->create([
        'inspection_template_id' => $template->id,
        'type' => 'safe_unsafe',
        'is_required' => true,
        'order' => 2,
    ]);

    $inspection = $this->createInspection([
        'inspection_template_id' => $template->id,
        'status' => 'in_progress',
    ]);
    WorkflowService::start('inspection', $inspection->id, $admin);
    WorkflowInstance::where('module_name', 'inspection')
        ->where('reference_id', $inspection->id)
        ->update(['current_status' => 'in_progress']);

    // Answer all required items
    $items = $template->items;
    InspectionResult::where('inspection_id', $inspection->id)
        ->where('inspection_item_id', $items[0]->id)
        ->update(['answer' => 'yes', 'is_unsafe' => false]);
    InspectionResult::where('inspection_id', $inspection->id)
        ->where('inspection_item_id', $items[1]->id)
        ->update(['answer' => 'safe', 'is_unsafe' => false]);

    $response = $this->post(route('inspection.inspections.complete', $inspection), [
        'notes' => 'Inspeksi selesai.',
    ]);

    $response->assertRedirect();
    $inspection->refresh();
    expect($inspection->status)->toBe('completed');
    expect($inspection->overall_result)->toBe('pass');
});
```

### 1.8 Unsafe answer sets is_unsafe flag automatically

```php
test('unsafe answer sets is_unsafe flag automatically', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $inspection = $this->createInspection(['status' => 'in_progress']);
    $unsafeItem = $inspection->template->items->firstWhere('type', 'safe_unsafe')
        ?? InspectionItem::factory()->create([
            'inspection_template_id' => $inspection->inspection_template_id,
            'type' => 'safe_unsafe',
            'is_required' => true,
        ]);

    // Create result for this item if not exists
    InspectionResult::firstOrCreate([
        'inspection_id' => $inspection->id,
        'inspection_item_id' => $unsafeItem->id,
    ]);

    $this->put(route('inspection.inspections.update', $inspection), [
        'results' => [
            [
                'inspection_item_id' => $unsafeItem->id,
                'answer' => 'unsafe',
                'remark' => 'APAR kedaluwarsa.',
            ],
        ],
    ]);

    $result = InspectionResult::where('inspection_id', $inspection->id)
        ->where('inspection_item_id', $unsafeItem->id)
        ->first();

    expect($result->answer)->toBe('unsafe');
    expect($result->is_unsafe)->toBeTrue();
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without inspection.checklists.view gets 403 on template list

```php
test('user without inspection.checklists.view gets 403 on template list', function () {
    $supervisor = $this->supervisorUser();
    // Supervisor does not have inspection.checklists.view
    $this->actingAs($supervisor);

    $response = $this->get(route('inspection.templates.index'));

    $response->assertForbidden();
});
```

### 2.2 User without inspection.checklists.create gets 403 on template create

```php
test('user without inspection.checklists.create gets 403 on template create', function () {
    $supervisor = $this->supervisorUser();
    $this->actingAs($supervisor);

    $response = $this->get(route('inspection.templates.create'));

    $response->assertForbidden();
});
```

### 2.3 User without inspection.checklists.execute gets 403 on inspection create

```php
test('user without inspection.checklists.execute gets 403 on inspection create', function () {
    $supervisor = $this->supervisorUser();
    $this->actingAs($supervisor);

    $response = $this->get(route('inspection.inspections.create'));

    $response->assertForbidden();
});
```

### 2.4 User without inspection.checklists.delete cannot delete template

```php
test('user without inspection.checklists.delete cannot delete template', function () {
    $officer = $this->qhsseOfficerUser();
    // QHSSE Officer has view, create, update but NOT delete
    $this->actingAs($officer);

    $template = $this->createTemplate();

    $response = $this->delete(route('inspection.templates.destroy', $template));

    $response->assertForbidden();
    expect(InspectionTemplate::find($template->id))->not->toBeNull();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Creating inspection generates empty results for each template item

```php
test('creating inspection generates empty results for each template item', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $template = $this->createTemplate();
    // Template has 3 items

    $inspection = $this->createInspection([
        'inspection_template_id' => $template->id,
    ]);

    $results = InspectionResult::where('inspection_id', $inspection->id)->get();
    expect($results)->toHaveCount(3);
    expect($results->every(fn ($r) => $r->answer === null))->toBeTrue();
    expect($results->every(fn ($r) => $r->is_unsafe === false))->toBeTrue();
});
```

### 3.2 Audit trail records template creation

```php
test('audit trail records inspection template creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $this->post(route('inspection.templates.store'), [
        'code' => 'AUD-001',
        'name' => 'Audit Template',
        'category' => 'safety',
        'is_active' => true,
        'items' => [
            ['question' => 'Test question?', 'type' => 'yes_no', 'is_required' => true, 'order' => 1],
        ],
    ]);

    $template = InspectionTemplate::where('code', 'AUD-001')->first();

    expect(
        AuditLog::where('module_name', 'inspection')
            ->where('reference_id', $template->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Audit trail records inspection completion

```php
test('audit trail records inspection completion', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $template = InspectionTemplate::factory()->create();
    InspectionItem::factory()->create([
        'inspection_template_id' => $template->id,
        'type' => 'yes_no',
        'is_required' => true,
    ]);

    $inspection = $this->createInspection([
        'inspection_template_id' => $template->id,
        'status' => 'in_progress',
    ]);
    WorkflowService::start('inspection', $inspection->id, $admin);
    WorkflowInstance::where('module_name', 'inspection')
        ->where('reference_id', $inspection->id)
        ->update(['current_status' => 'in_progress']);

    // Answer the required item
    InspectionResult::where('inspection_id', $inspection->id)
        ->update(['answer' => 'yes', 'is_unsafe' => false]);

    $this->post(route('inspection.inspections.complete', $inspection), [
        'notes' => 'Selesai.',
    ]);

    expect(
        AuditLog::where('module_name', 'inspection')
            ->where('reference_id', $inspection->id)
            ->where('event', 'completed')
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Notification created on inspection completion with unsafe items

```php
test('notification created when inspection completed with unsafe items', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create a QHSSE Manager to receive notification
    $manager = $this->qhsseManagerUser();

    $template = InspectionTemplate::factory()->create();
    InspectionItem::factory()->create([
        'inspection_template_id' => $template->id,
        'type' => 'safe_unsafe',
        'is_required' => true,
    ]);

    $inspection = $this->createInspection([
        'inspection_template_id' => $template->id,
        'status' => 'in_progress',
    ]);
    WorkflowService::start('inspection', $inspection->id, $admin);
    WorkflowInstance::where('module_name', 'inspection')
        ->where('reference_id', $inspection->id)
        ->update(['current_status' => 'in_progress']);

    // Answer with unsafe
    InspectionResult::where('inspection_id', $inspection->id)
        ->update(['answer' => 'unsafe', 'is_unsafe' => true]);

    $this->post(route('inspection.inspections.complete', $inspection));

    expect(
        CoreNotification::where('type', 'inspection.unsafe_found')
            ->where('module_name', 'inspection')
            ->where('reference_id', $inspection->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.5 File evidence can be attached to inspection

```php
test('file evidence can be attached to inspection', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $inspection = $this->createInspection();

    $file = UploadedFile::fake()->image('evidence.jpg');

    $response = $this->post('/core/files', [
        'file' => $file,
        'module_name' => 'inspection',
        'reference_id' => $inspection->id,
        'collection' => 'evidence',
    ]);

    $response->assertStatus(200);

    expect(
        ManagedFile::where('module_name', 'inspection')
            ->where('reference_id', $inspection->id)
            ->where('collection', 'evidence')
            ->count()
    )->toBe(1);
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot complete inspection with unanswered required items

```php
test('cannot complete inspection with unanswered required items', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $template = InspectionTemplate::factory()->create();
    InspectionItem::factory()->create([
        'inspection_template_id' => $template->id,
        'type' => 'yes_no',
        'is_required' => true,
    ]);

    $inspection = $this->createInspection([
        'inspection_template_id' => $template->id,
        'status' => 'in_progress',
    ]);
    WorkflowService::start('inspection', $inspection->id, $admin);
    WorkflowInstance::where('module_name', 'inspection')
        ->where('reference_id', $inspection->id)
        ->update(['current_status' => 'in_progress']);

    // Don't answer any items — all required items have null answer

    $response = $this->post(route('inspection.inspections.complete', $inspection), []);

    $response->assertSessionHasErrors(['complete']);
    $inspection->refresh();
    expect($inspection->status)->toBe('in_progress');
});
```

### 4.2 Cannot start non-pending inspection

```php
test('cannot start non-pending inspection', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $inspection = $this->createInspection(['status' => 'completed']);

    $response = $this->post(route('inspection.inspections.start', $inspection));

    $response->assertSessionHasErrors(['workflow']);
    expect($inspection->fresh()->status)->toBe('completed');
});
```

### 4.3 Duplicate inspection_number cannot occur via numbering service

```php
test('duplicate inspection_number cannot occur via numbering service', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $template = $this->createTemplate();
    $site = $this->createSite();
    $inspector = $this->qhsseOfficerUser();

    // Create first inspection
    $this->post(route('inspection.inspections.store'), [
        'inspection_template_id' => $template->id,
        'site_id' => $site->id,
        'inspector_id' => $inspector->id,
        'scheduled_at' => '2026-07-15',
    ]);

    // Create second inspection
    $this->post(route('inspection.inspections.store'), [
        'inspection_template_id' => $template->id,
        'site_id' => $site->id,
        'inspector_id' => $inspector->id,
        'scheduled_at' => '2026-07-16',
    ]);

    $numbers = Inspection::pluck('inspection_number')->toArray();
    expect(count($numbers))->toBe(2);
    expect(count(array_unique($numbers)))->toBe(2); // All unique
});
```

---

## Test Execution

```bash
# Run all tests
php artisan test

# Run only inspection tests
php artisan test --filter=Inspection

# Run with parallel (faster)
php artisan test --parallel

# Run with coverage
php artisan test --coverage
```

### Expected result:

```
Tests: 20 passed (Inspection Checklist module)
```

---

## Notes

- Tests use SQLite in-memory for speed and isolation.
- Each test starts with a fresh database (Laravel's `RefreshDatabase` trait is auto-applied via Pest).
- Factory creates minimal valid data; tests add specific fields as needed.
- `WorkflowService::start()` must be called after creating an Inspection in tests that need workflow transitions.
- For tests that need a specific status, manually update the `workflow_instances.current_status` field.
- `CorePermissions::roleMap()` defines which permissions each role gets — tests assume this is seeded.
- Notification tests require at least one QHSSE Manager user to exist for `notifyMany` to send to.
- The `createInspection` helper auto-creates empty `InspectionResult` records for each template item.

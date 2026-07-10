# Test Cases — Permit to Work

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

Test file: `tests/Feature/Modules/PermitToWork/PermitTest.php`

## Factory Definition

File: `database/factories/Modules/PermitToWork/PermitFactory.php`

```php
public function definition(): array
{
    $start = fake()->dateTimeBetween('now', '+2 days');
    $end = (clone $start)->modify('+' . fake()->numberBetween(1, 12) . ' hours');

    return [
        'permit_number' => 'PTW-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'type' => fake()->randomElement([
            'hot_work', 'working_at_height', 'confined_space',
            'electrical', 'excavation', 'lifting', 'other',
        ]),
        'title' => fake()->sentence(6),
        'description' => fake()->paragraph(2),
        'site_id' => Site::factory(),
        'area_id' => null,
        'department_id' => null,
        'contractor_id' => null,
        'work_location' => fake()->streetAddress(),
        'work_description' => fake()->paragraph(3),
        'start_datetime' => $start,
        'end_datetime' => $end,
        'validity_hours' => fake()->numberBetween(1, 12),
        'status' => 'draft',
        'risk_level' => fake()->optional(0.7)->randomElement(['low', 'medium', 'high', 'critical']),
        'jsa_reference' => fake()->optional(0.3)->bothify('RSK-####-####'),
        'approved_by' => null,
        'approved_at' => null,
        'closed_by' => null,
        'closed_at' => null,
        'cancellation_reason' => null,
        'created_by' => User::factory(),
    ];
}
```

File: `database/factories/Modules/PermitToWork/PermitChecklistFactory.php`

```php
public function definition(): array
{
    return [
        'permit_id' => Permit::factory(),
        'item_text' => fake()->sentence(8),
        'is_checked' => false,
        'checked_by' => null,
        'checked_at' => null,
    ];
}
```

## Helper Trait

```php
trait CreatesPermitTestUser
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

    protected function createPermit(User $creator, array $overrides = []): Permit
    {
        return Permit::factory()->create(array_merge([
            'created_by' => $creator->id,
            'site_id' => $this->createSite()->id,
        ], $overrides));
    }

    protected function signAllChecklists(Permit $permit, User $signer): void
    {
        $permit->checklists()->update([
            'is_checked' => true,
            'checked_by' => $signer->id,
            'checked_at' => now(),
        ]);
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view permit list

```php
test('authorized user can view permit list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('permits.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/PermitToWork/Index')
        ->has('permits')
        ->has('filters')
        ->has('summary')
    );
});
```

### 1.2 Authorized user can create permit draft

```php
test('authorized user can create permit draft', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $response = $this->post(route('permits.store'), [
        'type' => 'hot_work',
        'title' => 'Pengelasan Strip Plate Tower B',
        'description' => 'Pengelasan strip plate pada struktur Tower B',
        'site_id' => $site->id,
        'work_location' => 'Tower B Lantai 3, Welding Bay',
        'work_description' => 'Pengelasan strip plate sepanjang 15 meter',
        'start_datetime' => now()->addHours(2)->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addHours(6)->format('Y-m-d H:i:s'),
        'risk_level' => 'high',
        'action' => 'draft',
    ]);

    $response->assertRedirect(route('permits.show', Permit::first()));

    $permit = Permit::first();
    expect($permit)->not->toBeNull();
    expect($permit->title)->toBe('Pengelasan Strip Plate Tower B');
    expect($permit->status)->toBe('draft');
    expect($permit->type)->toBe('hot_work');
    expect($permit->created_by)->toBe($admin->id);
    expect($permit->validity_hours)->toBe(4);
});
```

### 1.3 Permit number is auto-generated on create with site code

```php
test('permit number is auto-generated on create with site code', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = Site::factory()->create(['code' => 'JKT']);

    $this->post(route('permits.store'), [
        'type' => 'hot_work',
        'title' => 'Test permit',
        'description' => 'Test description',
        'site_id' => $site->id,
        'work_location' => 'Tower B',
        'work_description' => 'Test work description',
        'start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addDay()->addHours(4)->format('Y-m-d H:i:s'),
        'action' => 'draft',
    ]);

    $permit = Permit::first();
    expect($permit->permit_number)->not->toBeNull();
    expect($permit->permit_number)->toMatch('/^PTW-JKT-\d{4}-\d{4}$/');
});
```

### 1.4 Checklist items are auto-generated based on permit type

```php
test('checklist items are auto-generated based on permit type', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('permits.store'), [
        'type' => 'confined_space',
        'title' => 'Tank cleaning',
        'description' => 'Cleaning confined space tank',
        'site_id' => $site->id,
        'work_location' => 'Tank B-12',
        'work_description' => 'Internal tank cleaning',
        'start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addDay()->addHours(8)->format('Y-m-d H:i:s'),
        'action' => 'draft',
    ]);

    $permit = Permit::first();
    $checklists = $permit->checklists;

    expect($checklists)->not->toBeEmpty();
    expect($checklists->count())->toBe(8); // confined_space has 8 items
    expect($checklists->first()->item_text)->toBe('Gas test dilakukan (O2, LEL, H2S, CO)');
    expect($checklists->every(fn ($c) => $c->is_checked === false))->toBeTrue();
});
```

### 1.5 Draft permit can be submitted

```php
test('draft permit can be submitted', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $permit = $this->createPermit($admin, ['status' => 'draft']);
    WorkflowService::start('permit', $permit->id, $admin);

    $response = $this->post(route('permits.submit', $permit));

    $response->assertRedirect();
    $permit->refresh();
    expect($permit->status)->toBe('submitted');
});
```

### 1.6 Submitted permit can be reviewed and approved

```php
test('submitted permit can be reviewed and approved', function () {
    $reporter = $this->reporterUser();
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $permit = $this->createPermit($reporter, ['status' => 'submitted']);
    WorkflowService::start('permit', $permit->id, $reporter);
    WorkflowInstance::where('module_name', 'permit')
        ->where('reference_id', $permit->id)
        ->update(['current_status' => 'submitted']);

    // Review
    $this->post(route('permits.review', $permit));
    $permit->refresh();
    expect($permit->status)->toBe('under_review');

    // Approve
    $this->post(route('permits.approve', $permit));
    $permit->refresh();
    expect($permit->status)->toBe('approved');
    expect($permit->approved_by)->toBe($officer->id);
    expect($permit->approved_at)->not->toBeNull();
});
```

### 1.7 Permit cannot be activated without all checklists signed

```php
test('permit cannot be activated without all checklists signed', function () {
    $reporter = $this->reporterUser();
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $permit = $this->createPermit($reporter, ['status' => 'approved']);
    WorkflowService::start('permit', $permit->id, $reporter);
    WorkflowInstance::where('module_name', 'permit')
        ->where('reference_id', $permit->id)
        ->update(['current_status' => 'approved']);

    // Create some checklist items, leave unsigned
    PermitChecklist::factory()->count(3)->create(['permit_id' => $permit->id]);

    $response = $this->post(route('permits.activate', $permit));

    $response->assertSessionHasErrors(['checklist']);
    $permit->refresh();
    expect($permit->status)->toBe('approved'); // still approved, not activated
});
```

### 1.8 Active permit can be closed with reason

```php
test('active permit can be closed with reason', function () {
    $reporter = $this->reporterUser();
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $permit = $this->createPermit($reporter, ['status' => 'active']);
    WorkflowService::start('permit', $permit->id, $reporter);
    WorkflowInstance::where('module_name', 'permit')
        ->where('reference_id', $permit->id)
        ->update(['current_status' => 'active']);

    $response = $this->post(route('permits.close', $permit), [
        'reason' => 'Pekerjaan selesai, area telah dibersihkan dan diverifikasi aman.',
    ]);

    $response->assertRedirect();
    $permit->refresh();
    expect($permit->status)->toBe('closed');
    expect($permit->closed_by)->toBe($officer->id);
    expect($permit->closed_at)->not->toBeNull();
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without permit.work.view gets 403 on list

```php
test('user without permit.work.view gets 403 on list', function () {
    $viewer = User::factory()->create();
    // No role assigned — no permissions at all
    $this->actingAs($viewer);

    $response = $this->get(route('permits.index'));

    $response->assertForbidden();
});
```

### 2.2 User without permit.work.create gets 403 on create form

```php
test('user without permit.work.create gets 403 on create form', function () {
    $auditor = User::factory()->create();
    $auditor->assignRole('Auditor'); // View + export only
    $this->actingAs($auditor);

    $response = $this->get(route('permits.create'));

    $response->assertForbidden();
});
```

### 2.3 User without permit.work.approve cannot approve

```php
test('user without permit.work.approve cannot approve', function () {
    $reporter = $this->reporterUser();
    $this->actingAs($reporter);

    $permit = $this->createPermit($reporter, ['status' => 'under_review']);

    $response = $this->post(route('permits.approve', $permit));

    $response->assertForbidden();
});
```

### 2.4 Export blocked without permit.work.export

```php
test('export blocked without permit.work.export', function () {
    $reporter = $this->reporterUser();
    $this->actingAs($reporter);

    $response = $this->get(route('permits.export'));

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Approver cannot approve own permit (conflict of interest)

```php
test('approver cannot approve own permit due to conflict of interest', function () {
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    // Officer creates the permit themselves
    $permit = $this->createPermit($officer, ['status' => 'under_review', 'created_by' => $officer->id]);
    WorkflowService::start('permit', $permit->id, $officer);
    WorkflowInstance::where('module_name', 'permit')
        ->where('reference_id', $permit->id)
        ->update(['current_status' => 'under_review']);

    $response = $this->post(route('permits.approve', $permit));

    $response->assertSessionHasErrors(['approve']);
    $permit->refresh();
    expect($permit->status)->toBe('under_review'); // not approved
    expect($permit->approved_by)->toBeNull();
});
```

### 3.2 Checklist signing records audit trail

```php
test('checklist signing records audit trail', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $permit = $this->createPermit($admin, ['status' => 'approved']);
    $checklist = PermitChecklist::factory()->create([
        'permit_id' => $permit->id,
        'is_checked' => false,
    ]);

    $response = $this->post(route('permits.checklists.sign', [$permit, $checklist]));

    $response->assertRedirect();
    $checklist->refresh();
    expect($checklist->is_checked)->toBeTrue();
    expect($checklist->checked_by)->toBe($admin->id);
    expect($checklist->checked_at)->not->toBeNull();

    expect(
        AuditLog::where('module_name', 'permit')
            ->where('reference_id', $permit->id)
            ->where('event', 'checklist.signed')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Notification created on permit submit

```php
test('notification created on permit submit', function () {
    $reporter = $this->reporterUser();
    $officer = $this->qhsseOfficer();
    $this->actingAs($reporter);

    $permit = $this->createPermit($reporter, ['status' => 'draft']);
    WorkflowService::start('permit', $permit->id, $reporter);

    $this->post(route('permits.submit', $permit));

    expect(
        CoreNotification::where('type', 'permit.submitted')
            ->where('module_name', 'permit')
            ->where('reference_id', $permit->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Audit trail records permit creation

```php
test('audit trail records permit creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('permits.store'), [
        'type' => 'hot_work',
        'title' => 'Audited permit',
        'description' => 'Test for audit trail',
        'site_id' => $site->id,
        'work_location' => 'Area A',
        'work_description' => 'Test work',
        'start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addDay()->addHours(4)->format('Y-m-d H:i:s'),
        'action' => 'draft',
    ]);

    $permit = Permit::first();

    expect(
        AuditLog::where('module_name', 'permit')
            ->where('reference_id', $permit->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.5 File evidence can be attached to permit

```php
test('file evidence can be attached to permit', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $permit = $this->createPermit($admin);

    $file = UploadedFile::fake()->image('evidence.jpg');

    $response = $this->post('/core/files', [
        'file' => $file,
        'module_name' => 'permit',
        'reference_id' => $permit->id,
        'collection' => 'evidence',
    ]);

    $response->assertStatus(200);

    expect(
        ManagedFile::where('module_name', 'permit')
            ->where('reference_id', $permit->id)
            ->where('collection', 'evidence')
            ->count()
    )->toBe(1);
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot submit non-draft permit

```php
test('cannot submit non-draft permit', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $permit = $this->createPermit($admin, ['status' => 'closed']);
    WorkflowService::start('permit', $permit->id, $admin);
    WorkflowInstance::where('module_name', 'permit')
        ->where('reference_id', $permit->id)
        ->update(['current_status' => 'closed']);

    $response = $this->post(route('permits.submit', $permit));

    $response->assertSessionHasErrors(['workflow']);
    $permit->refresh();
    expect($permit->status)->toBe('closed');
});
```

### 4.2 Reject without reason fails validation

```php
test('reject without reason fails validation', function () {
    $reporter = $this->reporterUser();
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $permit = $this->createPermit($reporter, ['status' => 'under_review']);
    WorkflowService::start('permit', $permit->id, $reporter);
    WorkflowInstance::where('module_name', 'permit')
        ->where('reference_id', $permit->id)
        ->update(['current_status' => 'under_review']);

    $response = $this->post(route('permits.reject', $permit), [
        'reason' => 'short', // less than 10 chars
    ]);

    $response->assertSessionHasErrors(['reason']);
    $permit->refresh();
    expect($permit->status)->toBe('under_review'); // not rejected
});
```

### 4.3 Permit with end_datetime before start_datetime fails validation

```php
test('permit with end datetime before start datetime fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $response = $this->post(route('permits.store'), [
        'type' => 'hot_work',
        'title' => 'Invalid time permit',
        'description' => 'Test description',
        'site_id' => $site->id,
        'work_location' => 'Area A',
        'work_description' => 'Test work',
        'start_datetime' => '2026-07-11 18:00:00',
        'end_datetime' => '2026-07-11 14:00:00', // before start
        'action' => 'draft',
    ]);

    $response->assertSessionHasErrors(['end_datetime']);
    expect(Permit::count())->toBe(0);
});
```

---

## Test Summary

| # | Category | Test Name | Description |
|---|---|---|---|
| 1.1 | Functional | Authorized user can view permit list | Index page renders with permits, filters, summary |
| 1.2 | Functional | Authorized user can create permit draft | Store creates draft with correct fields |
| 1.3 | Functional | Permit number is auto-generated with site code | PTW-JKT-YYYY-NNNN format |
| 1.4 | Functional | Checklist items auto-generated by type | Confined space → 8 items, correct text |
| 1.5 | Functional | Draft permit can be submitted | draft → submitted transition |
| 1.6 | Functional | Submitted permit can be reviewed and approved | submitted → under_review → approved |
| 1.7 | Functional | Cannot activate without all checklists signed | Activation blocked, error message shown |
| 1.8 | Functional | Active permit can be closed with reason | active → closed, closed_by set |
| 2.1 | Permission | User without view gets 403 | No role → forbidden |
| 2.2 | Permission | User without create gets 403 on form | Auditor → forbidden on create form |
| 2.3 | Permission | User without approve cannot approve | Reporter → forbidden on approve |
| 2.4 | Permission | Export blocked without export permission | Reporter → forbidden on export |
| 3.1 | Integration | Approver cannot approve own permit | Conflict of interest check |
| 3.2 | Integration | Checklist signing records audit trail | Audit log with event=checklist.signed |
| 3.3 | Integration | Notification created on submit | CoreNotification with type=permit.submitted |
| 3.4 | Integration | Audit trail records creation | AuditLog with event=created |
| 3.5 | Integration | File evidence can be attached | ManagedFile with module_name=permit |
| 4.1 | Negative | Cannot submit non-draft permit | Workflow transition blocked |
| 4.2 | Negative | Reject without reason fails validation | min:10 enforced |
| 4.3 | Negative | End before start fails validation | end_datetime after:start_datetime enforced |
```

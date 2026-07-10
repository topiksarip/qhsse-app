# Test Cases — Security Management

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

Test file: `tests/Feature/Modules/Security/SecurityManagementTest.php`

## Factory Definitions

File: `database/factories/Modules/Security/SecurityIncidentFactory.php`

```php
public function definition(): array
{
    return [
        'security_number' => 'SEC-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'type' => fake()->randomElement([
            'unauthorized_access', 'theft', 'vandalism',
            'trespass', 'suspicious_activity', 'other',
        ]),
        'title' => fake()->sentence(6),
        'description' => fake()->paragraph(3),
        'site_id' => Site::factory(),
        'area_id' => null,
        'occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
        'reported_by' => User::factory(),
        'severity_id' => Severity::factory(),
        'status' => 'reported',
        'resolution' => null,
        'resolved_at' => null,
    ];
}
```

File: `database/factories/Modules/Security/VisitorLogFactory.php`

```php
public function definition(): array
{
    return [
        'visitor_name' => fake()->name(),
        'visitor_company' => fake()->optional(0.7)->company(),
        'purpose' => fake()->sentence(4),
        'host_id' => User::factory(),
        'site_id' => Site::factory(),
        'check_in_at' => fake()->dateTimeBetween('-1 day', 'now'),
        'check_out_at' => null,
        'id_type' => fake()->randomElement(['KTP', 'SIM', 'Passport']),
        'id_number' => fake()->numerify('################'),
        'vehicle_plate' => fake()->optional(0.4)->regexify('[A-Z]{1,2} \d{4} [A-Z]{3}'),
    ];
}
```

File: `database/factories/Modules/Security/PatrolChecklistFactory.php`

```php
public function definition(): array
{
    return [
        'patrol_number' => 'SPL-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'site_id' => Site::factory(),
        'patrol_route' => fake()->randomElement(['Rute Pagi', 'Rute Siang', 'Rute Malam']) . ' — ' . fake()->streetName(),
        'officer_id' => User::factory(),
        'scheduled_at' => fake()->dateTimeBetween('-1 week', '+1 week'),
        'executed_at' => null,
        'status' => 'scheduled',
        'notes' => fake()->optional(0.5)->sentence(),
    ];
}
```

File: `database/factories/Modules/Security/PatrolResultFactory.php`

```php
public function definition(): array
{
    return [
        'patrol_checklist_id' => PatrolChecklist::factory(),
        'checkpoint' => fake()->randomElement(['Gerbang Utama', 'Area Parkir', 'Gudang', 'Pintu Belakang']),
        'status' => fake()->randomElement(['ok', 'issue', 'na']),
        'remark' => fn (array $attrs) => $attrs['status'] === 'issue' ? fake()->sentence() : null,
    ];
}
```

## Helper Trait

```php
trait CreatesSecurityTestUser
{
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        return $user;
    }

    protected function securityOfficer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Security Officer');
        return $user;
    }

    protected function qhsseOfficer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Officer');
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
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view security incident list

```php
test('authorized user can view security incident list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('security.incidents.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Security/Incident/Index')
        ->has('incidents')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create security incident

```php
test('authorized user can create security incident', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $severity = $this->createSeverity();

    $response = $this->post(route('security.incidents.store'), [
        'type' => 'unauthorized_access',
        'title' => 'Akses Tidak Sah ke Server Room',
        'description' => 'Terdeteksi akses tidak sah ke Server Room melalui CCTV pada jam non-operasional.',
        'site_id' => $site->id,
        'occurred_at' => '2026-07-11 14:30:00',
        'severity_id' => $severity->id,
    ]);

    $response->assertRedirect(route('security.incidents.show', SecurityIncident::first()));

    $incident = SecurityIncident::first();
    expect($incident)->not->toBeNull();
    expect($incident->title)->toBe('Akses Tidak Sah ke Server Room');
    expect($incident->status)->toBe('reported');
    expect($incident->reported_by)->toBe($admin->id);
});
```

### 1.3 Security incident number is auto-generated on create

```php
test('security incident number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $severity = $this->createSeverity();

    $this->post(route('security.incidents.store'), [
        'type' => 'theft',
        'title' => 'Pencurian di Gudang Bahan Baku',
        'description' => 'Dilaporkan hilangnya material dari gudang bahan baku pada malam tanggal 10 Juli.',
        'site_id' => $site->id,
        'occurred_at' => now()->toDateTimeString(),
        'severity_id' => $severity->id,
    ]);

    $incident = SecurityIncident::first();
    expect($incident->security_number)->not->toBeNull();
    expect($incident->security_number)->toMatch('/^SEC-\d{4}-\d{4}$/');
});
```

### 1.4 Visitor can check-in and check-out

```php
test('visitor can check-in and check-out', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $host = User::factory()->create();

    // Check-in
    $response = $this->post(route('security.visitors.store'), [
        'visitor_name' => 'Andi Pratama',
        'visitor_company' => 'PT Maju Jaya',
        'purpose' => 'Meeting dengan tim engineering',
        'host_id' => $host->id,
        'site_id' => $site->id,
        'id_type' => 'KTP',
        'id_number' => '3201234567890001',
        'vehicle_plate' => 'B 1234 ABC',
    ]);

    $response->assertRedirect(route('security.visitors.show', VisitorLog::first()));

    $visitor = VisitorLog::first();
    expect($visitor)->not->toBeNull();
    expect($visitor->visitor_name)->toBe('Andi Pratama');
    expect($visitor->check_in_at)->not->toBeNull();
    expect($visitor->check_out_at)->toBeNull();

    // Check-out
    $response = $this->post(route('security.visitors.check-out', $visitor));

    $response->assertRedirect();
    $visitor->refresh();
    expect($visitor->check_out_at)->not->toBeNull();
});
```

### 1.5 Patrol checklist can be created with checkpoints

```php
test('patrol checklist can be created with checkpoints', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $officer = User::factory()->create();

    $response = $this->post(route('security.patrols.store'), [
        'site_id' => $site->id,
        'patrol_route' => 'Rute Malam — Gerbang ke Gudang',
        'officer_id' => $officer->id,
        'scheduled_at' => '2026-07-11 22:00:00',
        'checkpoints' => [
            ['checkpoint' => 'Gerbang Utama'],
            ['checkpoint' => 'Area Parkir'],
            ['checkpoint' => 'Gudang Bahan Baku'],
        ],
        'notes' => 'Patroli rutin malam',
    ]);

    $response->assertRedirect(route('security.patrols.show', PatrolChecklist::first()));

    $patrol = PatrolChecklist::first();
    expect($patrol)->not->toBeNull();
    expect($patrol->status)->toBe('scheduled');
    expect($patrol->patrol_number)->toMatch('/^SPL-\d{4}-\d{4}$/');
    expect($patrol->results()->count())->toBe(3);
});
```

### 1.6 Patrol can be executed and completed

```php
test('patrol can be executed and completed', function () {
    $officer = $this->securityOfficer();
    $this->actingAs($officer);

    $patrol = PatrolChecklist::factory()->create([
        'status' => 'scheduled',
        'officer_id' => $officer->id,
    ]);
    // Create patrol results with null status
    PatrolResult::factory()->count(2)->create([
        'patrol_checklist_id' => $patrol->id,
        'status' => null,
        'remark' => null,
    ]);

    // Execute
    $response = $this->post(route('security.patrols.execute', $patrol));
    $response->assertRedirect();
    $patrol->refresh();
    expect($patrol->status)->toBe('in_progress');
    expect($patrol->executed_at)->not->toBeNull();

    // Fill results
    $results = $patrol->results;
    foreach ($results as $result) {
        $this->post(route('security.patrols.results.store', $patrol), [
            'patrol_result_id' => $result->id,
            'status' => 'ok',
            'remark' => 'Semua aman',
        ]);
    }

    // Complete
    $response = $this->post(route('security.patrols.complete', $patrol));
    $response->assertRedirect();
    $patrol->refresh();
    expect($patrol->status)->toBe('completed');
});
```

### 1.7 Security incident can be closed with resolution

```php
test('security incident can be closed with resolution', function () {
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $incident = SecurityIncident::factory()->create([
        'status' => 'under_investigation',
        'reported_by' => $officer->id,
    ]);

    $response = $this->post(route('security.incidents.close', $incident), [
        'resolution' => 'Investigasi selesai. Pintu darurat telah diperbaiki dan prosedur akses diperbarui.',
    ]);

    $response->assertRedirect();
    $incident->refresh();
    expect($incident->status)->toBe('closed');
    expect($incident->resolution)->not->toBeNull();
    expect($incident->resolved_at)->not->toBeNull();
});
```

### 1.8 Patrol result with issue requires remark

```php
test('patrol result with issue requires remark', function () {
    $officer = $this->securityOfficer();
    $this->actingAs($officer);

    $patrol = PatrolChecklist::factory()->create([
        'status' => 'in_progress',
        'officer_id' => $officer->id,
        'executed_at' => now(),
    ]);
    $result = PatrolResult::factory()->create([
        'patrol_checklist_id' => $patrol->id,
        'status' => null,
        'remark' => null,
    ]);

    // Issue without remark should fail
    $response = $this->post(route('security.patrols.results.store', $patrol), [
        'patrol_result_id' => $result->id,
        'status' => 'issue',
        'remark' => '',
    ]);

    $response->assertSessionHasErrors(['remark']);
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without security.incidents.view gets 403 on incident list

```php
test('user without security.incidents.view gets 403 on incident list', function () {
    $employee = User::factory()->create();
    $employee->assignRole('Employee / Reporter');

    $this->actingAs($employee);

    $response = $this->get(route('security.incidents.index'));

    $response->assertForbidden();
});
```

### 2.2 User without security.incidents.create gets 403 on create form

```php
test('user without security.incidents.create gets 403 on create form', function () {
    $auditor = User::factory()->create();
    $auditor->assignRole('Auditor');

    $this->actingAs($auditor);

    $response = $this->get(route('security.incidents.create'));

    $response->assertForbidden();
});
```

### 2.3 Security Officer cannot close incident

```php
test('security officer cannot close incident', function () {
    $officer = $this->securityOfficer();
    $this->actingAs($officer);

    $incident = SecurityIncident::factory()->create(['status' => 'under_investigation']);

    $response = $this->post(route('security.incidents.close', $incident), [
        'resolution' => 'Test resolution yang cukup panjang.',
    ]);

    $response->assertForbidden();
});
```

### 2.4 Export blocked without security.incidents.export

```php
test('export blocked without security.incidents.export', function () {
    $officer = $this->securityOfficer();
    $this->actingAs($officer);

    $response = $this->get(route('security.incidents.export'));

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Audit trail records security incident creation

```php
test('audit trail records security incident creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $severity = $this->createSeverity();

    $this->post(route('security.incidents.store'), [
        'type' => 'vandalism',
        'title' => 'Vandalisme Pagar Timur',
        'description' => 'Ditemukan vandalisme pada pagar timur area Plant A oleh petugas patroli.',
        'site_id' => $site->id,
        'occurred_at' => now()->toDateTimeString(),
        'severity_id' => $severity->id,
    ]);

    $incident = SecurityIncident::first();

    expect(
        AuditLog::where('module_name', 'security')
            ->where('reference_id', $incident->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.2 Notification created on security incident report

```php
test('notification created on security incident report', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    // Create a Security Officer to receive notification
    $officer = User::factory()->create();
    $officer->assignRole('Security Officer');

    $site = $this->createSite();
    $severity = $this->createSeverity();

    $this->post(route('security.incidents.store'), [
        'type' => 'trespass',
        'title' => 'Penyusup di Area Gudang',
        'description' => 'Ditemukan orang tidak dikenal memasuki area gudang pada malam hari.',
        'site_id' => $site->id,
        'occurred_at' => now()->toDateTimeString(),
        'severity_id' => $severity->id,
    ]);

    $incident = SecurityIncident::first();

    expect(
        CoreNotification::where('type', 'security.incident.reported')
            ->where('module_name', 'security')
            ->where('reference_id', $incident->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Activity log records visitor check-in

```php
test('activity log records visitor check-in', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $host = User::factory()->create();

    $this->post(route('security.visitors.store'), [
        'visitor_name' => 'Cindy Lestari',
        'visitor_company' => 'PT Sukses Mandiri',
        'purpose' => 'Interview dengan HRD',
        'host_id' => $host->id,
        'site_id' => $site->id,
        'id_type' => 'KTP',
        'id_number' => '3201987654321000',
    ]);

    $visitor = VisitorLog::first();

    expect(
        ActivityLog::where('module_name', 'security')
            ->where('reference_id', $visitor->id)
            ->where('event', 'security.visitor.checked_in')
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Notification sent to host on visitor check-in

```php
test('notification sent to host on visitor check-in', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $host = User::factory()->create();

    $this->post(route('security.visitors.store'), [
        'visitor_name' => 'Doni Hartono',
        'visitor_company' => 'PT Teknologi Nusantara',
        'purpose' => 'Service AC ruang server',
        'host_id' => $host->id,
        'site_id' => $site->id,
        'id_type' => 'KTP',
        'id_number' => '3201111222333000',
    ]);

    expect(
        CoreNotification::where('type', 'security.visitor.checked_in')
            ->where('module_name', 'security')
            ->exists()
    )->toBeTrue();
});
```

### 3.5 Notification sent on patrol issue found

```php
test('notification sent on patrol issue found', function () {
    $officer = $this->securityOfficer();
    $this->actingAs($officer);

    // Create QHSSE Officer to receive issue notification
    $qhsseOfficer = User::factory()->create();
    $qhsseOfficer->assignRole('QHSSE Officer');

    $patrol = PatrolChecklist::factory()->create([
        'status' => 'in_progress',
        'officer_id' => $officer->id,
        'executed_at' => now(),
    ]);
    $result = PatrolResult::factory()->create([
        'patrol_checklist_id' => $patrol->id,
        'status' => null,
        'remark' => null,
    ]);

    $this->post(route('security.patrols.results.store', $patrol), [
        'patrol_result_id' => $result->id,
        'status' => 'issue',
        'remark' => 'Pintu belakang tidak terkunci, perlu perbaikan segera.',
    ]);

    expect(
        CoreNotification::where('type', 'security.patrol.issue_found')
            ->where('module_name', 'security')
            ->where('reference_id', $patrol->id)
            ->exists()
    )->toBeTrue();
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot check-out visitor who already checked out

```php
test('cannot check-out visitor who already checked out', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $visitor = VisitorLog::factory()->create([
        'check_out_at' => now()->subHour(),
    ]);

    $response = $this->post(route('security.visitors.check-out', $visitor));

    $response->assertSessionHasErrors(['check_out_at']);
});
```

### 4.2 Cannot complete patrol with unfilled checkpoints

```php
test('cannot complete patrol with unfilled checkpoints', function () {
    $officer = $this->securityOfficer();
    $this->actingAs($officer);

    $patrol = PatrolChecklist::factory()->create([
        'status' => 'in_progress',
        'officer_id' => $officer->id,
        'executed_at' => now(),
    ]);
    // Create results with null status (unfilled)
    PatrolResult::factory()->count(2)->create([
        'patrol_checklist_id' => $patrol->id,
        'status' => null,
        'remark' => null,
    ]);

    $response = $this->post(route('security.patrols.complete', $patrol));

    $response->assertSessionHasErrors(['checkpoints']);
    $patrol->refresh();
    expect($patrol->status)->toBe('in_progress');
});
```

### 4.3 Close without resolution fails validation

```php
test('close without resolution fails validation', function () {
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $incident = SecurityIncident::factory()->create([
        'status' => 'under_investigation',
    ]);

    $response = $this->post(route('security.incidents.close', $incident), []);

    $response->assertSessionHasErrors(['resolution']);
    $incident->refresh();
    expect($incident->status)->toBe('under_investigation');
});
```

---

## Test Execution

```bash
# Run all security module tests
php artisan test --filter=Security

# Run with coverage
php artisan test --coverage --filter=Security
```

### Expected result:

```
Tests: 20 passed
```

---

## Notes

- Tests use SQLite in-memory for speed and isolation
- Each test starts with a fresh database (Laravel's `RefreshDatabase` trait)
- Factory creates minimal valid data; tests add specific fields as needed
- `Security Officer` is a new role that must be added to `RolesAndPermissionsSeeder`
- Notification tests require at least one user with the target role to exist
- For patrol execution tests, `PatrolResult` records with `status = null` represent unfilled checkpoints
- `CorePermissions::roleMap()` defines which permissions each role gets — tests assume this is seeded

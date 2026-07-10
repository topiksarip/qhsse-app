# Test Cases — Asset & Equipment Safety

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

Test file: `tests/Feature/Modules/Asset/AssetEquipmentSafetyTest.php`

## Factory Definition

File: `database/factories/Modules/Asset/AssetFactory.php`

```php
public function definition(): array
{
    return [
        'asset_number' => 'AST-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'name' => fake()->randomElement([
            'Crane 50 Ton', 'APAR CO2', 'Forklift Toyota', 'Harness Safety Set',
            'Mesin CNC Line 2', 'Hydrant Pump', 'Sling Chain 10Ton',
        ]) . ' #' . fake()->numberBetween(1, 999),
        'category' => fake()->randomElement([
            'equipment', 'machinery', 'vehicle', 'safety_equipment',
            'fire_equipment', 'lifting', 'other',
        ]),
        'serial_number' => fake()->optional(0.8)->bothify('??-####-???'),
        'model' => fake()->optional(0.7)->bothify('MODEL-####'),
        'manufacturer' => fake()->optional(0.7)->company(),
        'site_id' => Site::factory(),
        'area_id' => null,
        'department_id' => null,
        'purchase_date' => fake()->optional(0.6)->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
        'installation_date' => fake()->optional(0.5)->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        'warranty_expiry' => fake()->optional(0.4)->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
        'status' => 'active',
        'safety_critical' => fake()->boolean(30),
    ];
}
```

File: `database/factories/Modules/Asset/AssetCertificateFactory.php`

```php
public function definition(): array
{
    $issuedDate = fake()->dateTimeBetween('-1 year', 'now');
    $expiryDate = fake()->optional(0.8)->dateTimeBetween('now', '+1 year');

    return [
        'asset_id' => Asset::factory(),
        'certificate_type' => fake()->randomElement([
            'Sertifikat Kalibrasi', 'Surat Kelayakan Operasi', 'Sertifikat K3',
            'SIO Pengangkatan', 'Sertifikat Pemadam', 'Inspeksi Tahunan',
        ]),
        'certificate_number' => fake()->bothify('??-####-2025'),
        'issued_date' => $issuedDate->format('Y-m-d'),
        'expiry_date' => $expiryDate?->format('Y-m-d'),
        'issuing_body' => fake()->randomElement([
            'Sucofindo', 'Disnaker', 'Kemnaker RI', 'SGS', 'Bureau Veritas',
        ]),
        'certificate_file_id' => null,
        'status' => 'valid',
    ];
}
```

File: `database/factories/Modules/Asset/AssetInspectionFactory.php`

```php
public function definition(): array
{
    $inspectionDate = fake()->dateTimeBetween('-6 months', 'now');

    return [
        'asset_id' => Asset::factory(),
        'inspection_date' => $inspectionDate->format('Y-m-d'),
        'inspector_id' => User::factory(),
        'result' => fake()->randomElement(['pass', 'fail', 'maintenance_required']),
        'notes' => fake()->optional(0.7)->paragraph(2),
        'next_inspection_date' => fake()->optional(0.8)
            ->dateTimeBetween('now', '+6 months')
            ->format('Y-m-d'),
    ];
}
```

## Helper Trait

```php
trait CreatesAssetTestUser
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

    protected function createArea(): Area
    {
        return Area::factory()->create();
    }

    protected function createDepartment(): Department
    {
        return Department::factory()->create();
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view asset list

```php
test('authorized user can view asset list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('assets.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Asset/Index')
        ->has('assets')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create asset

```php
test('authorized user can create asset', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $response = $this->post(route('assets.store'), [
        'name' => 'Crane 50 Ton Kapasitas',
        'category' => 'lifting',
        'serial_number' => 'CRN-50T-001',
        'model' => 'XCMC-QY50K',
        'manufacturer' => 'XCMG',
        'site_id' => $site->id,
        'area_id' => null,
        'department_id' => null,
        'purchase_date' => '2025-01-15',
        'installation_date' => '2025-02-20',
        'warranty_expiry' => '2027-01-15',
        'safety_critical' => true,
    ]);

    $response->assertRedirect(route('assets.show', Asset::first()));

    $asset = Asset::first();
    expect($asset)->not->toBeNull();
    expect($asset->name)->toBe('Crane 50 Ton Kapasitas');
    expect($asset->status)->toBe('active');
    expect($asset->category)->toBe('lifting');
    expect($asset->safety_critical)->toBeTrue();
});
```

### 1.3 Asset number is auto-generated on create

```php
test('asset number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('assets.store'), [
        'name' => 'Test Asset',
        'category' => 'equipment',
        'site_id' => $site->id,
        'safety_critical' => false,
    ]);

    $asset = Asset::first();
    expect($asset->asset_number)->not->toBeNull();
    expect($asset->asset_number)->toMatch('/^AST-\d{4}-\d{4}$/');
});
```

### 1.4 Asset with missing name fails validation

```php
test('asset with missing name fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $response = $this->post(route('assets.store'), [
        'category' => 'equipment',
        'site_id' => $site->id,
    ]);

    $response->assertSessionHasErrors(['name']);
    expect(Asset::count())->toBe(0);
});
```

### 1.5 Asset with invalid category fails validation

```php
test('asset with invalid category fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $response = $this->post(route('assets.store'), [
        'name' => 'Test Asset',
        'category' => 'invalid_category',
        'site_id' => $site->id,
    ]);

    $response->assertSessionHasErrors(['category']);
});
```

### 1.6 Certificate can be created for an asset

```php
test('certificate can be created for an asset', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $asset = Asset::factory()->create(['status' => 'active']);

    $response = $this->post(route('assets.certificates.store', $asset), [
        'certificate_type' => 'Sertifikat Kalibrasi',
        'certificate_number' => 'SK-KAL-2025-001',
        'issued_date' => '2025-01-15',
        'expiry_date' => '2026-01-15',
        'issuing_body' => 'Sucofindo',
    ]);

    $response->assertRedirect();
    $certificate = AssetCertificate::where('asset_id', $asset->id)->first();
    expect($certificate)->not->toBeNull();
    expect($certificate->certificate_type)->toBe('Sertifikat Kalibrasi');
    expect($certificate->status)->toBe('valid');
});
```

### 1.7 Certificate status is expired when expiry_date is in the past

```php
test('certificate status is expired when expiry_date is in the past', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $asset = Asset::factory()->create(['status' => 'active']);

    $this->post(route('assets.certificates.store', $asset), [
        'certificate_type' => 'Sertifikat Kalibrasi',
        'certificate_number' => 'SK-KAL-2024-OLD',
        'issued_date' => '2024-01-01',
        'expiry_date' => '2024-06-01',
        'issuing_body' => 'Sucofindo',
    ]);

    $certificate = AssetCertificate::where('asset_id', $asset->id)->first();
    expect($certificate->status)->toBe('expired');
});
```

### 1.8 Inspection can be created for an asset

```php
test('inspection can be created for an asset', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $asset = Asset::factory()->create(['status' => 'active']);
    $inspector = User::factory()->create();

    $response = $this->post(route('assets.inspections.store', $asset), [
        'inspection_date' => '2026-02-15',
        'inspector_id' => $inspector->id,
        'result' => 'pass',
        'notes' => 'Semua komponen dalam kondisi baik.',
        'next_inspection_date' => '2026-05-15',
    ]);

    $response->assertRedirect();
    $inspection = AssetInspection::where('asset_id', $asset->id)->first();
    expect($inspection)->not->toBeNull();
    expect($inspection->result)->toBe('pass');
    expect($inspection->inspector_id)->toBe($inspector->id);
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without asset.management.view gets 403 on list

```php
test('user without asset.management.view gets 403 on list', function () {
    $noRole = $this->noRoleUser();
    $this->actingAs($noRole);

    $response = $this->get(route('assets.index'));

    $response->assertForbidden();
});
```

### 2.2 User without asset.management.create gets 403 on create form

```php
test('user without asset.management.create gets 403 on create form', function () {
    $viewer = $this->viewerUser(); // Auditor role: view + export only
    $this->actingAs($viewer);

    $response = $this->get(route('assets.create'));

    $response->assertForbidden();
});
```

### 2.3 User without asset.certificates.create cannot create certificate

```php
test('user without asset.certificates.create cannot create certificate', function () {
    $viewer = $this->viewerUser(); // Auditor: no certificates.create
    $this->actingAs($viewer);

    $asset = Asset::factory()->create(['status' => 'active']);

    $response = $this->post(route('assets.certificates.store', $asset), [
        'certificate_type' => 'Sertifikat Kalibrasi',
        'certificate_number' => 'SK-TEST-001',
        'issued_date' => '2025-01-15',
        'expiry_date' => '2026-01-15',
        'issuing_body' => 'Sucofindo',
    ]);

    $response->assertForbidden();
});
```

### 2.4 User without asset.inspections.create cannot create inspection

```php
test('user without asset.inspections.create cannot create inspection', function () {
    $viewer = $this->viewerUser(); // Auditor: no inspections.create
    $this->actingAs($viewer);

    $asset = Asset::factory()->create(['status' => 'active']);
    $inspector = User::factory()->create();

    $response = $this->post(route('assets.inspections.store', $asset), [
        'inspection_date' => '2026-02-15',
        'inspector_id' => $inspector->id,
        'result' => 'pass',
    ]);

    $response->assertForbidden();
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Audit trail records asset creation

```php
test('audit trail records asset creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();

    $this->post(route('assets.store'), [
        'name' => 'Asset for trail test',
        'category' => 'equipment',
        'site_id' => $site->id,
    ]);

    $asset = Asset::first();

    expect(
        AuditLog::where('module_name', 'asset')
            ->where('reference_id', $asset->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.2 Audit trail records certificate creation

```php
test('audit trail records certificate creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $asset = Asset::factory()->create(['status' => 'active']);

    $this->post(route('assets.certificates.store', $asset), [
        'certificate_type' => 'Sertifikat K3',
        'certificate_number' => 'SK3-TEST-001',
        'issued_date' => '2025-06-01',
        'expiry_date' => '2026-06-01',
        'issuing_body' => 'Kemnaker RI',
    ]);

    expect(
        AuditLog::where('module_name', 'asset')
            ->where('reference_id', $asset->id)
            ->where('event', 'certificate.created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Activity log records inspection creation

```php
test('activity log records inspection creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $asset = Asset::factory()->create(['status' => 'active']);
    $inspector = User::factory()->create();

    $this->post(route('assets.inspections.store', $asset), [
        'inspection_date' => '2026-02-15',
        'inspector_id' => $inspector->id,
        'result' => 'fail',
        'notes' => 'Ditemukan crack pada hook crane.',
    ]);

    expect(
        ActivityLog::where('module_name', 'asset')
            ->where('reference_id', $asset->id)
            ->where('event', 'inspection.created')
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Certificate status calculates correctly based on expiry_date

```php
test('certificate status calculates correctly based on expiry_date', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $asset = Asset::factory()->create(['status' => 'active']);

    // Expired certificate (expiry in the past)
    $this->post(route('assets.certificates.store', $asset), [
        'certificate_type' => 'Expired Cert',
        'certificate_number' => 'EXP-001',
        'issued_date' => now()->subYear()->format('Y-m-d'),
        'expiry_date' => now()->subMonth()->format('Y-m-d'),
        'issuing_body' => 'Test Body',
    ]);

    $expiredCert = AssetCertificate::where('certificate_number', 'EXP-001')->first();
    expect($expiredCert->status)->toBe('expired');

    // Valid certificate (expiry > 30 days)
    $this->post(route('assets.certificates.store', $asset), [
        'certificate_type' => 'Valid Cert',
        'certificate_number' => 'VAL-001',
        'issued_date' => now()->format('Y-m-d'),
        'expiry_date' => now()->addMonths(6)->format('Y-m-d'),
        'issuing_body' => 'Test Body',
    ]);

    $validCert = AssetCertificate::where('certificate_number', 'VAL-001')->first();
    expect($validCert->status)->toBe('valid');
});
```

### 3.5 Failed inspection can link to CAPA

```php
test('failed inspection can link to CAPA', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $asset = Asset::factory()->create(['status' => 'active']);
    $inspector = User::factory()->create();

    // Create failed inspection
    $this->post(route('assets.inspections.store', $asset), [
        'inspection_date' => '2026-02-15',
        'inspector_id' => $inspector->id,
        'result' => 'fail',
        'notes' => 'Crack pada hook crane.',
        'next_inspection_date' => '2026-05-15',
    ]);

    $inspection = AssetInspection::where('asset_id', $asset->id)->first();
    expect($inspection->result)->toBe('fail');

    // Verify CAPA can be created with source_module='asset_inspection'
    $capa = CapaAction::create([
        'capa_number' => 'ACT-2026-0001',
        'title' => 'CAPA untuk inspeksi gagal aset ' . $asset->asset_number,
        'source_module' => 'asset_inspection',
        'source_reference_id' => $inspection->id,
        'status' => 'open',
    ]);

    expect($capa->source_module)->toBe('asset_inspection');
    expect($capa->source_reference_id)->toBe($inspection->id);

    // Verify linkage from CAPA side
    $linkedCapa = CapaAction::where('source_module', 'asset_inspection')
        ->where('source_reference_id', $inspection->id)
        ->first();
    expect($linkedCapa)->not->toBeNull();
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Cannot create certificate for decommissioned asset

```php
test('cannot create certificate for decommissioned asset', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $asset = Asset::factory()->create(['status' => 'decommissioned']);

    $response = $this->post(route('assets.certificates.store', $asset), [
        'certificate_type' => 'Sertifikat Test',
        'certificate_number' => 'TEST-001',
        'issued_date' => '2025-01-15',
        'expiry_date' => '2026-01-15',
        'issuing_body' => 'Test Body',
    ]);

    $response->assertForbidden();
    expect(AssetCertificate::where('asset_id', $asset->id)->count())->toBe(0);
});
```

### 4.2 Cannot create inspection for decommissioned asset

```php
test('cannot create inspection for decommissioned asset', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $asset = Asset::factory()->create(['status' => 'decommissioned']);
    $inspector = User::factory()->create();

    $response = $this->post(route('assets.inspections.store', $asset), [
        'inspection_date' => '2026-02-15',
        'inspector_id' => $inspector->id,
        'result' => 'pass',
    ]);

    $response->assertForbidden();
    expect(AssetInspection::where('asset_id', $asset->id)->count())->toBe(0);
});
```

### 4.3 Certificate with expiry_date before issued_date fails validation

```php
test('certificate with expiry_date before issued_date fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $asset = Asset::factory()->create(['status' => 'active']);

    $response = $this->post(route('assets.certificates.store', $asset), [
        'certificate_type' => 'Sertifikat Test',
        'certificate_number' => 'TEST-001',
        'issued_date' => '2025-06-01',
        'expiry_date' => '2025-01-01', // Before issued_date
        'issuing_body' => 'Test Body',
    ]);

    $response->assertSessionHasErrors(['expiry_date']);
    expect(AssetCertificate::where('asset_id', $asset->id)->count())->toBe(0);
});
```

---

## Test Summary

| # | Category | Test Name | Description |
|---|---|---|---|
| 1.1 | Functional | Authorized user can view asset list | Verifies index page renders |
| 1.2 | Functional | Authorized user can create asset | Verifies store creates asset with correct fields |
| 1.3 | Functional | Asset number is auto-generated on create | Verifies AST-YYYY-NNNN format |
| 1.4 | Functional | Asset with missing name fails validation | Required field validation |
| 1.5 | Functional | Asset with invalid category fails validation | Enum check constraint |
| 1.6 | Functional | Certificate can be created for an asset | Certificate CRUD |
| 1.7 | Functional | Certificate status is expired when expiry_date is in the past | Expiry tracking logic |
| 1.8 | Functional | Inspection can be created for an asset | Inspection CRUD |
| 2.1 | Permission | User without asset.management.view gets 403 on list | RBAC enforcement |
| 2.2 | Permission | User without asset.management.create gets 403 on create form | RBAC enforcement |
| 2.3 | Permission | User without asset.certificates.create cannot create certificate | RBAC enforcement |
| 2.4 | Permission | User without asset.inspections.create cannot create inspection | RBAC enforcement |
| 3.1 | Integration | Audit trail records asset creation | AuditService integration |
| 3.2 | Integration | Audit trail records certificate creation | AuditService for certificates |
| 3.3 | Integration | Activity log records inspection creation | ActivityService integration |
| 3.4 | Integration | Certificate status calculates correctly based on expiry_date | Status calculation logic |
| 3.5 | Integration | Failed inspection can link to CAPA | Cross-module CAPA linkage |
| 4.1 | Negative | Cannot create certificate for decommissioned asset | Status lock enforcement |
| 4.2 | Negative | Cannot create inspection for decommissioned asset | Status lock enforcement |
| 4.3 | Negative | Certificate with expiry_date before issued_date fails validation | Date validation |

**Total: 20 tests** (8 functional + 4 permission + 5 integration + 3 negative)

# Test Cases — Contractor Management

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

Test file: `tests/Feature/Modules/ContractorManagement/ContractorManagementTest.php`

## Factory Definition

File: `database/factories/Modules/ContractorManagement/ContractorFactory.php`

```php
public function definition(): array
{
    return [
        'contractor_number'   => 'CTR-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'company_id'          => Company::factory(),
        'contact_person'      => fake()->name(),
        'contact_phone'       => fake()->phoneNumber(),
        'contact_email'       => fake()->optional(0.8)->email(),
        'service_type'        => fake()->randomElement([
            'Konstruksi Sipil', 'Mechanical & Piping', 'Electrical',
            'Scaffolding', 'Cleaning Service', 'Security',
            'Transportasi', 'Maintenance', 'General Contractor',
        ]),
        'safety_rating'       => fake()->optional(0.6)->randomElement(['excellent', 'good', 'fair', 'poor']),
        'is_prequalified'     => fake()->boolean(40),
        'prequalified_until'  => fn (array $attrs) => $attrs['is_prequalified']
            ? fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d')
            : null,
        'status'              => 'active',
    ];
}
```

File: `database/factories/Modules/ContractorManagement/ContractorEvaluationFactory.php`

```php
public function definition(): array
{
    $criteria = [
        'compliance_dokumen'         => fake()->numberBetween(10, 20),
        'rekam_jejak_keselamatan'    => fake()->numberBetween(10, 25),
        'kompetensi_personel'        => fake()->numberBetween(10, 20),
        'ketersediaan_apd'           => fake()->numberBetween(5, 15),
        'program_k3'                => fake()->numberBetween(10, 20),
    ];
    $totalScore = array_sum($criteria);

    return [
        'contractor_id'    => Contractor::factory(),
        'evaluation_date'  => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        'evaluator_id'     => User::factory(),
        'criteria'         => $criteria,
        'total_score'      => $totalScore,
        'result'           => match (true) {
            $totalScore >= 80 => 'pass',
            $totalScore >= 60 => 'conditional',
            default           => 'fail',
        },
        'notes'            => fake()->optional(0.5)->paragraph(2),
    ];
}
```

## Helper Trait

```php
trait CreatesContractorTestUser
{
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        return $user;
    }

    protected function qhsseManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Manager');
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

    protected function createCompany(): Company
    {
        return Company::factory()->create(['type' => 'contractor', 'is_active' => true]);
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view contractor list

```php
test('authorized user can view contractor list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('contractors.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/ContractorManagement/Index')
        ->has('contractors')
        ->has('filters')
        ->has('summary')
    );
});
```

### 1.2 Authorized user can create contractor

```php
test('authorized user can create contractor', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $company = $this->createCompany();

    $response = $this->post(route('contractors.store'), [
        'company_id'         => $company->id,
        'contact_person'     => 'Budi Santoso',
        'contact_phone'      => '0812-3456-7890',
        'contact_email'      => 'budi@karya.com',
        'service_type'       => 'Konstruksi Sipil',
        'is_prequalified'    => false,
        'prequalified_until' => null,
    ]);

    $response->assertRedirect(route('contractors.show', Contractor::first()));

    $contractor = Contractor::first();
    expect($contractor)->not->toBeNull();
    expect($contractor->contact_person)->toBe('Budi Santoso');
    expect($contractor->status)->toBe('active');
    expect($contractor->service_type)->toBe('Konstruksi Sipil');
    expect($contractor->is_prequalified)->toBe(false);
});
```

### 1.3 Contractor number is auto-generated on create

```php
test('contractor number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $company = $this->createCompany();

    $this->post(route('contractors.store'), [
        'company_id'      => $company->id,
        'contact_person'  => 'Sari Wulandari',
        'contact_phone'   => '0813-9999-0000',
        'service_type'    => 'Mechanical & Piping',
        'is_prequalified' => false,
    ]);

    $contractor = Contractor::first();
    expect($contractor->contractor_number)->not->toBeNull();
    expect($contractor->contractor_number)->toMatch('/^CTR-\d{4}-\d{4}$/');
});
```

### 1.4 Contractor with missing company_id fails validation

```php
test('contractor with missing company_id fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('contractors.store'), [
        'contact_person'  => 'Test Person',
        'contact_phone'   => '0812-0000-0000',
        'service_type'    => 'Electrical',
        'is_prequalified' => false,
    ]);

    $response->assertSessionHasErrors(['company_id']);
    expect(Contractor::count())->toBe(0);
});
```

### 1.5 Duplicate active contractor for same company is rejected

```php
test('duplicate active contractor for same company is rejected', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $company = $this->createCompany();

    // Create first contractor
    Contractor::factory()->create([
        'company_id' => $company->id,
        'status'     => 'active',
    ]);

    // Attempt to create second contractor for same company
    $response = $this->post(route('contractors.store'), [
        'company_id'      => $company->id,
        'contact_person'  => 'Second Person',
        'contact_phone'   => '0812-1111-1111',
        'service_type'    => 'Electrical',
        'is_prequalified' => false,
    ]);

    $response->assertSessionHasErrors(['company_id']);
    expect(Contractor::where('company_id', $company->id)->count())->toBe(1);
});
```

### 1.6 Authorized user can create evaluation for contractor

```php
test('authorized user can create evaluation for contractor', function () {
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $contractor = Contractor::factory()->create([
        'safety_rating' => null,
    ]);

    $response = $this->post(route('contractors.evaluations.store', $contractor), [
        'evaluation_date' => '2026-07-11',
        'criteria' => [
            'compliance_dokumen'       => 18,
            'rekam_jejak_keselamatan'  => 22,
            'kompetensi_personel'      => 17,
            'ketersediaan_apd'         => 13,
            'program_k3'              => 16,
        ],
        'notes' => 'Kontraktor menunjukkan komitmen tinggi terhadap keselamatan.',
    ]);

    $response->assertRedirect();

    $evaluation = ContractorEvaluation::where('contractor_id', $contractor->id)->first();
    expect($evaluation)->not->toBeNull();
    expect($evaluation->total_score)->toBe(86.00);
    expect($evaluation->result)->toBe('pass');
    expect($evaluation->evaluator_id)->toBe($officer->id);

    // Safety rating should be updated
    $contractor->refresh();
    expect($contractor->safety_rating)->toBe('good'); // 86 >= 70 but < 85
});
```

### 1.7 Evaluation with high score sets safety_rating to excellent

```php
test('evaluation with high score sets safety_rating to excellent', function () {
    $manager = $this->qhsseManager();
    $this->actingAs($manager);

    $contractor = Contractor::factory()->create([
        'safety_rating' => 'fair',
    ]);

    // Create 3 evaluations with high scores
    for ($i = 0; $i < 3; $i++) {
        $this->post(route('contractors.evaluations.store', $contractor), [
            'evaluation_date' => now()->subDays($i * 30)->format('Y-m-d'),
            'criteria' => [
                'compliance_dokumen'       => 20,
                'rekam_jejak_keselamatan'  => 25,
                'kompetensi_personel'      => 20,
                'ketersediaan_apd'         => 15,
                'program_k3'               => 20,
            ],
            'notes' => 'Evaluasi sempurna.',
        ]);
    }

    $contractor->refresh();
    expect($contractor->safety_rating)->toBe('excellent'); // avg = 100 >= 85
});
```

### 1.8 Set prequalification updates is_prequalified and prequalified_until

```php
test('set prequalification updates is_prequalified and prequalified_until', function () {
    $manager = $this->qhsseManager();
    $this->actingAs($manager);

    $contractor = Contractor::factory()->create([
        'is_prequalified'    => false,
        'prequalified_until' => null,
    ]);

    $response = $this->post(route('contractors.prequalify', $contractor), [
        'prequalified_until' => '2026-12-31',
    ]);

    $response->assertRedirect();
    $contractor->refresh();
    expect($contractor->is_prequalified)->toBe(true);
    expect($contractor->prequalified_until)->toBe('2026-12-31');
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without contractor.management.view gets 403 on list

```php
test('user without contractor.management.view gets 403 on list', function () {
    $noRole = $this->noRoleUser();
    $this->actingAs($noRole);

    $response = $this->get(route('contractors.index'));

    $response->assertForbidden();
});
```

### 2.2 User without contractor.management.create gets 403 on create form

```php
test('user without contractor.management.create gets 403 on create form', function () {
    $viewer = $this->viewerUser(); // Auditor: view + export only
    $this->actingAs($viewer);

    $response = $this->get(route('contractors.create'));

    $response->assertForbidden();
});
```

### 2.3 User without contractor.management.evaluate cannot evaluate

```php
test('user without contractor.management.evaluate cannot evaluate', function () {
    $viewer = $this->viewerUser(); // Auditor: no evaluate permission
    $this->actingAs($viewer);

    $contractor = Contractor::factory()->create();

    $response = $this->post(route('contractors.evaluations.store', $contractor), [
        'evaluation_date' => '2026-07-11',
        'criteria' => [
            'compliance_dokumen'       => 15,
            'rekam_jejak_keselamatan'  => 20,
            'kompetensi_personel'      => 15,
            'ketersediaan_apd'         => 10,
            'program_k3'               => 15,
        ],
    ]);

    $response->assertForbidden();
    expect(ContractorEvaluation::where('contractor_id', $contractor->id)->count())->toBe(0);
});
```

### 2.4 User without contractor.management.update cannot set prequalification

```php
test('user without contractor.management.update cannot set prequalification', function () {
    $viewer = $this->viewerUser(); // Auditor: no update permission
    $this->actingAs($viewer);

    $contractor = Contractor::factory()->create([
        'is_prequalified' => false,
    ]);

    $response = $this->post(route('contractors.prequalify', $contractor), [
        'prequalified_until' => '2026-12-31',
    ]);

    $response->assertForbidden();
    $contractor->refresh();
    expect($contractor->is_prequalified)->toBe(false);
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Evaluation calculates total_score and result correctly

```php
test('evaluation calculates total_score and result correctly', function () {
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $contractor = Contractor::factory()->create();

    $this->post(route('contractors.evaluations.store', $contractor), [
        'evaluation_date' => '2026-07-11',
        'criteria' => [
            'compliance_dokumen'       => 10, // low
            'rekam_jejak_keselamatan'  => 10,
            'kompetensi_personel'      => 10,
            'ketersediaan_apd'         => 5,
            'program_k3'               => 5,
        ],
    ]);

    $evaluation = ContractorEvaluation::where('contractor_id', $contractor->id)->first();
    expect($evaluation->total_score)->toBe(40.00);
    expect($evaluation->result)->toBe('fail'); // < 60
});
```

### 3.2 Audit trail records contractor creation

```php
test('audit trail records contractor creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $company = $this->createCompany();

    $this->post(route('contractors.store'), [
        'company_id'      => $company->id,
        'contact_person'  => 'Audit Trail Test',
        'contact_phone'   => '0812-0000-0000',
        'service_type'    => 'Transportasi',
        'is_prequalified' => false,
    ]);

    $contractor = Contractor::first();

    expect(
        AuditLog::where('module_name', 'contractor')
            ->where('reference_id', $contractor->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Audit trail records evaluation creation and safety_rating change

```php
test('audit trail records evaluation creation and safety_rating change', function () {
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $contractor = Contractor::factory()->create([
        'safety_rating' => 'fair',
    ]);

    $this->post(route('contractors.evaluations.store', $contractor), [
        'evaluation_date' => '2026-07-11',
        'criteria' => [
            'compliance_dokumen'       => 20,
            'rekam_jejak_keselamatan'  => 25,
            'kompetensi_personel'      => 20,
            'ketersediaan_apd'         => 15,
            'program_k3'               => 20,
        ],
    ]);

    // Check evaluation creation audit log
    expect(
        AuditLog::where('module_name', 'contractor')
            ->where('reference_id', $contractor->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();

    // Check safety_rating_updated audit log
    expect(
        AuditLog::where('module_name', 'contractor')
            ->where('reference_id', $contractor->id)
            ->where('event', 'contractor.safety_rating_updated')
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Activity log records prequalification set and revoke

```php
test('activity log records prequalification set and revoke', function () {
    $manager = $this->qhsseManager();
    $this->actingAs($manager);

    $contractor = Contractor::factory()->create([
        'is_prequalified'    => false,
        'prequalified_until' => null,
    ]);

    // Set prequalification
    $this->post(route('contractors.prequalify', $contractor), [
        'prequalified_until' => '2026-12-31',
    ]);

    expect(
        ActivityLog::where('module_name', 'contractor')
            ->where('reference_id', $contractor->id)
            ->where('event', 'contractor.prequalified')
            ->exists()
    )->toBeTrue();

    // Revoke prequalification
    $this->delete(route('contractors.prequalify.revoke', $contractor));

    expect(
        ActivityLog::where('module_name', 'contractor')
            ->where('reference_id', $contractor->id)
            ->where('event', 'contractor.prequalification_revoked')
            ->exists()
    )->toBeTrue();
});
```

### 3.5 Show page displays linked permits and incidents

```php
test('show page displays linked permits and incidents', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $company = $this->createCompany();
    $contractor = Contractor::factory()->create([
        'company_id' => $company->id,
    ]);

    // Create a linked permit (using company_id as contractor_id in permits)
    $permit = Permit::factory()->create([
        'contractor_id' => $company->id,
    ]);

    // Create a linked incident
    $incident = Incident::factory()->create([
        'contractor_id' => $company->id,
    ]);

    $response = $this->get(route('contractors.show', $contractor));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/ContractorManagement/Show')
        ->has('linkedPermits.data')
        ->has('linkedIncidents.data')
    );

    $permits = $response->inertiaProps()['linkedPermits']['data'];
    expect(count($permits))->toBe(1);
    expect($permits[0]['id'])->toBe($permit->id);

    $incidents = $response->inertiaProps()['linkedIncidents']['data'];
    expect(count($incidents))->toBe(1);
    expect($incidents[0]['id'])->toBe($incident->id);
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Revoke prequalification when not prequalified returns error

```php
test('revoke prequalification when not prequalified returns error', function () {
    $manager = $this->qhsseManager();
    $this->actingAs($manager);

    $contractor = Contractor::factory()->create([
        'is_prequalified'    => false,
        'prequalified_until' => null,
    ]);

    $response = $this->delete(route('contractors.prequalify.revoke', $contractor));

    $response->assertSessionHasErrors(['prequalify']);
});
```

### 4.2 Set prequalification with past date fails validation

```php
test('set prequalification with past date fails validation', function () {
    $manager = $this->qhsseManager();
    $this->actingAs($manager);

    $contractor = Contractor::factory()->create([
        'is_prequalified' => false,
    ]);

    $response = $this->post(route('contractors.prequalify', $contractor), [
        'prequalified_until' => '2020-01-01',
    ]);

    $response->assertSessionHasErrors(['prequalified_until']);

    $contractor->refresh();
    expect($contractor->is_prequalified)->toBe(false);
});
```

### 4.3 Evaluation with empty criteria fails validation

```php
test('evaluation with empty criteria fails validation', function () {
    $officer = $this->qhsseOfficer();
    $this->actingAs($officer);

    $contractor = Contractor::factory()->create();

    $response = $this->post(route('contractors.evaluations.store', $contractor), [
        'evaluation_date' => '2026-07-11',
        'criteria' => [],
    ]);

    $response->assertSessionHasErrors(['criteria']);

    expect(ContractorEvaluation::where('contractor_id', $contractor->id)->count())->toBe(0);
});
```

---

## Test Coverage Summary

| # | Test | Category | What It Verifies |
|---|---|---|---|
| 1.1 | Authorized user can view contractor list | Functional | Index page renders with correct props |
| 1.2 | Authorized user can create contractor | Functional | Store creates record with correct fields |
| 1.3 | Contractor number is auto-generated | Functional | CTR-YYYY-NNNN format on create |
| 1.4 | Missing company_id fails validation | Functional | Validation rejects missing required field |
| 1.5 | Duplicate active contractor rejected | Functional | Business rule: one active contractor per company |
| 1.6 | Authorized user can create evaluation | Functional | Evaluation stores with correct total_score + result + safety_rating |
| 1.7 | High score sets safety_rating to excellent | Functional | Safety rating recalculation with 3 evaluations |
| 1.8 | Set prequalification updates fields | Functional | Prequalification activation |
| 2.1 | No view permission → 403 on list | Permission | RBAC enforcement on index |
| 2.2 | No create permission → 403 on form | Permission | RBAC enforcement on create |
| 2.3 | No evaluate permission → 403 on evaluate | Permission | RBAC enforcement on evaluation |
| 2.4 | No update permission → 403 on prequalify | Permission | RBAC enforcement on prequalification |
| 3.1 | Evaluation calculates score correctly | Integration | Score computation + result derivation logic |
| 3.2 | Audit trail records contractor creation | Integration | AuditService integration |
| 3.3 | Audit trail records evaluation + rating change | Integration | AuditService + safety_rating update |
| 3.4 | Activity log records prequalify set/revoke | Integration | ActivityService integration |
| 3.5 | Show page displays linked permits + incidents | Integration | Cross-module data display |
| 4.1 | Revoke when not prequalified → error | Negative | Business rule guard on revoke |
| 4.2 | Past date for prequalified_until fails | Negative | Date validation on prequalification |
| 4.3 | Empty criteria fails validation | Negative | Minimum criteria requirement |

---

## Running Tests

```bash
# Run all contractor management tests
php artisan test --filter=ContractorManagement

# Run specific category
php artisan test --filter=ContractorManagementTest --filter='authorized user'
php artisan test --filter=ContractorManagementTest --filter='permission'
php artisan test --filter=ContractorManagementTest --filter='integration'
php artisan test --filter=ContractorManagementTest --filter='negative'

# Run with coverage
php artisan test --filter=ContractorManagement --coverage
```

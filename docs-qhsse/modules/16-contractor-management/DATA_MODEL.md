# Data Model — Contractor Management

> Phase 16 schema for the Contractor Management (CSMS) module.  
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs).

---

## 1. Table of Contents

1. [Main Table: `contractors`](#2-main-table-contractors)
2. [Child Table: `contractor_evaluations`](#3-child-table-contractor_evaluations)
3. [ERD Diagram (ASCII)](#4-erd-diagram-ascii)
4. [Index Specifications](#5-index-specifications)
5. [Shared Relations](#6-shared-relations)
6. [Migration File Naming Convention](#7-migration-file-naming-convention)

---

## 2. Main Table: `contractors`

Stores the core contractor record — links to an existing company, tracks prequalification status and safety rating.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `contractor_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `CTR-{YYYY}-{0001}`) |
| 3 | `company_id` | `bigint` | NO | — | **FK → `companies.id`**. Links to existing company record |
| 4 | `contact_person` | `varchar(255)` | NO | — | Name of primary contact person at contractor |
| 5 | `contact_phone` | `varchar(50)` | NO | — | Phone number of contact person |
| 6 | `contact_email` | `varchar(255)` | YES | `NULL` | Email of contact person (nullable) |
| 7 | `service_type` | `varchar(255)` | NO | — | Type of service (e.g., "Konstruksi Sipil", "Mechanical & Piping") |
| 8 | `safety_rating` | `varchar(20)` | YES | `NULL` | Derived from evaluation scores: `excellent`, `good`, `fair`, `poor` |
| 9 | `is_prequalified` | `boolean` | NO | `false` | Prequalification status |
| 10 | `prequalified_until` | `date` | YES | `NULL` | Prequalification expiry date (required when `is_prequalified = true`) |
| 11 | `status` | `varchar(20)` | NO | `'active'` | Lifecycle state: `active`, `inactive`, `blacklisted` |
| 12 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 13 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE contractors (
    id                   BIGSERIAL       PRIMARY KEY,
    contractor_number    VARCHAR(50)     NOT NULL UNIQUE,
    company_id           BIGINT          NOT NULL REFERENCES companies(id),
    contact_person       VARCHAR(255)    NOT NULL,
    contact_phone        VARCHAR(50)     NOT NULL,
    contact_email        VARCHAR(255)    NULL,
    service_type         VARCHAR(255)    NOT NULL,
    safety_rating        VARCHAR(20)     NULL,
    is_prequalified      BOOLEAN         NOT NULL DEFAULT false,
    prequalified_until   DATE            NULL,
    status               VARCHAR(20)     NOT NULL DEFAULT 'active',
    created_at           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT contractors_safety_rating_check CHECK (
        safety_rating IS NULL OR safety_rating IN ('excellent', 'good', 'fair', 'poor')
    ),

    CONSTRAINT contractors_status_check CHECK (
        status IN ('active', 'inactive', 'blacklisted')
    ),

    CONSTRAINT contractors_prequalified_until_check CHECK (
        (is_prequalified = false) OR (is_prequalified = true AND prequalified_until IS NOT NULL)
    )
);

-- Partial unique index: one active contractor per company
CREATE UNIQUE INDEX contractors_company_id_active_unique
    ON contractors (company_id)
    WHERE status = 'active';
```

### Laravel Migration (Reference)

```php
Schema::create('contractors', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('contractor_number', 50)->unique();
    $table->foreignId('company_id')->constrained('companies');
    $table->string('contact_person', 255);
    $table->string('contact_phone', 50);
    $table->string('contact_email', 255)->nullable();
    $table->string('service_type', 255);
    $table->string('safety_rating', 20)->nullable();
    $table->boolean('is_prequalified')->default(false);
    $table->date('prequalified_until')->nullable();
    $table->string('status', 20)->default('active');
    $table->timestamps();

    // Check constraint for safety_rating enum
    $table->check("safety_rating IS NULL OR safety_rating IN ('excellent','good','fair','poor')", 'contractors_safety_rating_check');

    // Check constraint for status enum
    $table->check("status IN ('active','inactive','blacklisted')", 'contractors_status_check');

    // Check constraint: prequalified_until must be set when is_prequalified is true
    $table->check("(is_prequalified = false) OR (is_prequalified = true AND prequalified_until IS NOT NULL)", 'contractors_prequalified_until_check');
});

// Partial unique index: one active contractor per company
DB::statement(
    'CREATE UNIQUE INDEX contractors_company_id_active_unique ON contractors (company_id) WHERE status = \'active\''
);
```

### Design Notes

- **No soft deletes** (`deleted_at`) — contractor records use `status` lifecycle (`active`, `inactive`, `blacklisted`). Soft deletes can be added if regulatory retention requires it.
- **`contractor_number`** is generated at create time via `NumberingService::generate('contractor', ...)`. Unique constraint prevents duplicates.
- **`company_id`** is FK to existing `companies` table (Core master data). Company must already exist.
- **Partial unique index** on `company_id` WHERE `status = 'active'` ensures one company cannot be registered as two active contractors simultaneously. A company can have multiple inactive/blacklisted records (historical).
- **`safety_rating`** is nullable — NULL until the first evaluation is submitted. Updated automatically by `ContractorEvaluationController::store()` after each evaluation.
- **`is_prequalified`** + **`prequalified_until`** have a CHECK constraint: if prequalified is true, `prequalified_until` must not be NULL.
- **`contact_email`** is nullable — some contractors may not have email.
- **`service_type`** is free-text (not FK, not enum) to allow flexibility in service categorization.

---

## 3. Child Table: `contractor_evaluations`

Stores individual evaluation records for a contractor. Each evaluation contains criteria scores (JSON), a total score, and a pass/conditional/fail result.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `contractor_id` | `bigint` | NO | — | **FK → `contractors.id`**, `ON DELETE CASCADE` |
| 3 | `evaluation_date` | `date` | NO | — | Date when evaluation was conducted |
| 4 | `evaluator_id` | `bigint` | NO | — | **FK → `users.id`**. User who performed the evaluation |
| 5 | `criteria` | `json` | NO | — | JSON object of criteria scores (e.g., `{"compliance": 20, "safety_record": 25, ...}`) |
| 6 | `total_score` | `decimal(5,2)` | NO | — | Sum of all criteria scores (0.00–100.00) |
| 7 | `result` | `varchar(20)` | NO | — | **Check constraint** enum: `pass`, `conditional`, `fail` |
| 8 | `notes` | `text` | YES | `NULL` | Additional notes from evaluator |
| 9 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 10 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE contractor_evaluations (
    id               BIGSERIAL       PRIMARY KEY,
    contractor_id    BIGINT          NOT NULL REFERENCES contractors(id) ON DELETE CASCADE,
    evaluation_date  DATE            NOT NULL,
    evaluator_id     BIGINT          NOT NULL REFERENCES users(id),
    criteria         JSON            NOT NULL,
    total_score      DECIMAL(5,2)    NOT NULL,
    result           VARCHAR(20)     NOT NULL,
    notes            TEXT            NULL,
    created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT contractor_evaluations_result_check CHECK (
        result IN ('pass', 'conditional', 'fail')
    ),

    CONSTRAINT contractor_evaluations_total_score_check CHECK (
        total_score >= 0 AND total_score <= 100
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('contractor_evaluations', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('contractor_id')->constrained('contractors')->cascadeOnDelete();
    $table->date('evaluation_date');
    $table->foreignId('evaluator_id')->constrained('users');
    $table->json('criteria');
    $table->decimal('total_score', 5, 2);
    $table->string('result', 20);
    $table->text('notes')->nullable();
    $table->timestamps();

    // Check constraint for result enum
    $table->check("result IN ('pass','conditional','fail')", 'contractor_evaluations_result_check');

    // Check constraint for total_score range
    $table->check("total_score >= 0 AND total_score <= 100", 'contractor_evaluations_total_score_check');
});
```

### Design Notes

- **Cascade delete** on `contractor_id` — when a contractor is deleted, all its evaluations go with it.
- **`criteria`** is a JSON column storing per-criterion scores. Example structure:
  ```json
  {
    "compliance_dokumen": 20,
    "rekam_jejak_keselamatan": 25,
    "kompetensi_personel": 20,
    "ketersediaan_apd": 15,
    "program_k3": 20
  }
  ```
  The keys are configurable and not fixed by the schema. The controller sums all values to compute `total_score`.
- **`total_score`** is `DECIMAL(5,2)` — supports 0.00 to 999.99, constrained to 0–100 via CHECK.
- **`result`** is derived from `total_score` in the controller:
  - `pass` — total_score ≥ 80
  - `conditional` — total_score 60–79.99
  - `fail` — total_score < 60
  The CHECK constraint ensures only valid enum values are stored.
- **`evaluator_id`** is FK to `users.id` — the user who performed the evaluation.
- **`notes`** is nullable — optional free-text notes from evaluator.
- No soft deletes — evaluations are permanent records.

---

## 4. ERD Diagram (ASCII)

```
┌──────────────────────────┐         ┌──────────────────────────────┐         ┌──────────────────────────────┐
│       companies           │         │         contractors            │         │   contractor_evaluations      │
├──────────────────────────┤         ├──────────────────────────────┤         ├──────────────────────────────┤
│ id          BIGINT PK     │◄────────│ company_id      BIGINT FK     │◄────────│ contractor_id  BIGINT FK      │
│ code        VARCHAR       │         │ id              BIGINT PK     │         │ id              BIGINT PK     │
│ name        VARCHAR       │         │ contractor_number VARCHAR(50)  │         │ evaluation_date DATE          │
│ type        VARCHAR       │         │ contact_person  VARCHAR(255)  │         │ evaluator_id    BIGINT FK ────┼──► users
│ is_active   BOOLEAN       │         │ contact_phone   VARCHAR(50)   │         │ criteria        JSON         │
└──────────────────────────┘         │ contact_email   VARCHAR(255)  │         │ total_score     DECIMAL(5,2)  │
                                     │ service_type    VARCHAR(255)  │         │ result          VARCHAR(20)   │
                                     │ safety_rating   VARCHAR(20)   │         │ notes           TEXT (null)   │
                                     │ is_prequalified BOOLEAN        │         │ created_at      TIMESTAMP     │
                                     │ prequalified_until DATE (null) │         │ updated_at      TIMESTAMP     │
                                     │ status          VARCHAR(20)    │         └──────────────────────────────┘
                                     │ created_at      TIMESTAMP     │
                                     │ updated_at      TIMESTAMP     │
                                     └──────────────────────────────┘
                                                    │
                                                    │ 1:N (cascade)
                                                    ▼
                                     ┌──────────────────────────────┐
                                     │   contractor_evaluations      │
                                     │   (see above)                  │
                                     └──────────────────────────────┘

┌──────────────────────────────┐
│           users               │
├──────────────────────────────┤
│ id          BIGINT PK     ◄────── (evaluator_id)
│ name        VARCHAR           │
│ email       VARCHAR           │
│ is_active   BOOLEAN           │
│ company_id  BIGINT FK ────────┼──► companies (for Contractor role scope)
└──────────────────────────────┘
```

### Relationship Summary (Text)

```
                         ┌─────────────────────┐
                         │     companies       │
                         │   (Core master)      │
                         └──────────┬──────────┘
                                    │ 1:1 (active)
                                    ▼
                         ┌─────────────────────┐         ┌──────────────────────────┐
                         │     contractors      │ 1:N     │  contractor_evaluations   │
                         │   (main table)       ├────────►│  (child table)            │
                         └──────────┬──────────┘          └──────────┬───────────────┘
                                    │                                │
                                    │ N:1                             │ N:1
                                    ▼                                ▼
                         ┌─────────────────────┐          ┌─────────────────────┐
                         │      users           │          │      users           │
                         │  (evaluator_id)      │          │ (evaluator_id)        │
                         └─────────────────────┘          └─────────────────────┘
```

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `companies` | `contractors` | `company_id` | 1:N (but 1:1 for active) | RESTRICT (default) |
| `contractors` | `contractor_evaluations` | `contractor_id` | 1:N | CASCADE |
| `users` | `contractor_evaluations` | `evaluator_id` | 1:N | RESTRICT (default) |

### Cross-Module Links (Logical, not FK)

```
contractors.company_id ──► permits.contractor_id       (module 09-permit-to-work)
                            (permits.contractor_id references companies.id)

contractors.company_id ──► incidents.contractor_id     (module 01-incident-reporting)
                            (incidents.contractor_id references companies.id)

contractors.company_id ──► audits.supplier_id          (module 06-audit-management)
                            (future: audit type=supplier linked to company)
```

These are logical relationships resolved at query time in the Show page controller, not physical FKs from this module's tables.

---

## 5. Index Specifications

### `contractors` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `contractors_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `contractors_contractor_number_unique` | `contractor_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `contractors_company_id_index` | `company_id` | btree | Lookup contractors by company |
| 4 | `contractors_company_id_active_unique` | `company_id` | UNIQUE (partial, WHERE status='active') | Prevent duplicate active contractor per company |
| 5 | `contractors_status_index` | `status` | btree | Filter by status (active/inactive/blacklisted) |
| 6 | `contractors_is_prequalified_index` | `is_prequalified` | btree | Filter by prequalification status |
| 7 | `contractors_prequalified_until_index` | `prequalified_until` | btree | Scheduled job expiry check |
| 8 | `contractors_safety_rating_index` | `safety_rating` | btree | Filter/sort by safety rating |
| 9 | `contractors_service_type_index` | `service_type` | btree | Filter by service type |
| 10 | `contractors_created_at_index` | `created_at` | btree | Sort by creation date |

### `contractor_evaluations` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `contractor_evaluations_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `contractor_evaluations_contractor_id_index` | `contractor_id` | btree | Find all evaluations for a contractor |
| 3 | `contractor_evaluations_evaluator_id_index` | `evaluator_id` | btree | Find evaluations by a specific user |
| 4 | `contractor_evaluations_evaluation_date_index` | `evaluation_date` | btree | Sort/filter by date |
| 5 | `contractor_evaluations_result_index` | `result` | btree | Filter by result (pass/conditional/fail) |
| 6 | `contractor_evaluations_contractor_id_evaluation_date_index` | `contractor_id, evaluation_date DESC` | btree (composite) | Get latest evaluations per contractor (for safety_rating calculation) |

### Laravel Migration Indexes

```php
// contractors table
$table->index('company_id');
$table->index('status');
$table->index('is_prequalified');
$table->index('prequalified_until');
$table->index('safety_rating');
$table->index('service_type');
$table->index('created_at');

// contractor_evaluations table
$table->index('contractor_id');
$table->index('evaluator_id');
$table->index('evaluation_date');
$table->index('result');
$table->index(['contractor_id', 'evaluation_date'], 'contractor_evaluations_contractor_date_index');
```

---

## 6. Shared Relations

The Contractor Management module uses the **polymorphic `module_name + reference_id`** pattern for all cross-cutting platform services:

- `module_name = 'contractor'`
- `reference_id = contractors.id`

### 6.1 Managed Files (`managed_files`)

File attachments (prequalification docs, evaluation docs, supporting docs) for a contractor.

| Column | Value |
|---|---|
| `module_name` | `'contractor'` |
| `reference_id` | `contractors.id` |
| `collection` | `'prequalification'`, `'evaluation'`, `'supporting_docs'` |
| `uploaded_by` | `users.id` (FK) |

```
contractors.id ──► managed_files.reference_id
                   managed_files.module_name = 'contractor'
```

**Usage**: `Contractor::files()` returns all files where `module_name='contractor'` AND `reference_id=$this->id`.

### 6.2 Comments (`comments`)

Threaded comments / discussion on a contractor record.

| Column | Value |
|---|---|
| `module_name` | `'contractor'` |
| `reference_id` | `contractors.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` — internal-only (QHSSE discussion) vs visible to contractor |

### 6.3 Activity Logs (`activity_logs`)

Timeline of actions performed on a contractor (created, updated, evaluated, prequalified, revoked).

| Column | Value |
|---|---|
| `module_name` | `'contractor'` |
| `reference_id` | `contractors.id` |
| `event` | `'created'`, `'updated'`, `'evaluated'`, `'prequalified'`, `'prequalification_revoked'`, `'safety_rating_updated'`, `'status_changed'` |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot, metadata) |

### 6.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes on contractor and evaluation records.

| Column | Value |
|---|---|
| `module_name` | `'contractor'` |
| `reference_id` | `contractors.id` |
| `auditable_type` | `'Contractor'` or `'ContractorEvaluation'` |
| `auditable_id` | `contractors.id` or `contractor_evaluations.id` |
| `old_values` | JSON (previous field values) |
| `new_values` | JSON (new field values) |
| `actor_id` | `users.id` (FK) |

### Shared Relations Summary

```
                          ┌──────────────┐
                          │  contractors  │
                          │  (id: PK)    │
                          └──────┬───────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='contractor'        │
              reference_id=contractors.id     │
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │  comments   │  │ activity_logs  │
    │  (prequalification│  │ (discussion)│  │  (timeline)    │
    │   evaluation,     │  │             │  │                │
    │   supporting_docs)│  │             │  │                │
    └───────────────────┘  └─────────────┘  └────────────────┘
                    │            │            │
    ┌───────────────▼──┐
    │  audit_logs      │
    │  (field changes) │
    └──────────────────┘

                    ┌──────────────────────┐
                    │  contractor_evaluations│
                    │  (contractor_id: FK)   │
                    └────────┬─────────────┘
                             │
                     evaluator_id (FK → users)
                     Child record, cascaded on contractor delete
```

---

## 7. Migration File Naming Convention

### Convention

Migrations follow Laravel's standard naming pattern:

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

### Files for This Module

| # | Migration File | Description |
|---|---|---|
| 1 | `2026_07_11_000001_create_contractors_table.php` | Creates `contractors` table |
| 2 | `2026_07_11_000002_create_contractor_evaluations_table.php` | Creates `contractor_evaluations` table |

### Seeder

File: `database/seeders/ContractorManagementSeeder.php`

```php
class ContractorManagementSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed permissions (if not auto-registered via CorePermissions)
        // 2. Seed sample contractors
        // 3. Seed sample evaluations
    }
}
```

### Factory

File: `database/factories/Modules/ContractorManagement/ContractorFactory.php`

```php
public function definition(): array
{
    return [
        'contractor_number' => 'CTR-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'company_id' => Company::factory(),
        'contact_person' => fake()->name(),
        'contact_phone' => fake()->phoneNumber(),
        'contact_email' => fake()->optional(0.8)->email(),
        'service_type' => fake()->randomElement([
            'Konstruksi Sipil', 'Mechanical & Piping', 'Electrical',
            'Scaffolding', 'Cleaning Service', 'Security',
            'Transportasi', 'Maintenance', 'General Contractor',
        ]),
        'safety_rating' => fake()->optional(0.6)->randomElement(['excellent', 'good', 'fair', 'poor']),
        'is_prequalified' => fake()->boolean(40),
        'prequalified_until' => fn (array $attrs) => $attrs['is_prequalified']
            ? fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d')
            : null,
        'status' => 'active',
    ];
}
```

File: `database/factories/Modules/ContractorManagement/ContractorEvaluationFactory.php`

```php
public function definition(): array
{
    $criteria = [
        'compliance_dokumen' => fake()->numberBetween(10, 20),
        'rekam_jejak_keselamatan' => fake()->numberBetween(10, 25),
        'kompetensi_personel' => fake()->numberBetween(10, 20),
        'ketersediaan_apd' => fake()->numberBetween(5, 15),
        'program_k3' => fake()->numberBetween(10, 20),
    ];
    $totalScore = array_sum($criteria);

    return [
        'contractor_id' => Contractor::factory(),
        'evaluation_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        'evaluator_id' => User::factory(),
        'criteria' => $criteria,
        'total_score' => $totalScore,
        'result' => match (true) {
            $totalScore >= 80 => 'pass',
            $totalScore >= 60 => 'conditional',
            default => 'fail',
        },
        'notes' => fake()->optional(0.5)->paragraph(2),
    ];
}
```

# Data Model — Legal & Compliance Register

> Phase 14 schema for the Legal & Compliance Register module.  
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs).

---

## 1. Table of Contents

1. [Main Table: `legal_register`](#2-main-table-legal_register)
2. [Child Table: `legal_obligations`](#3-child-table-legal_obligations)
3. [ERD Diagram (ASCII)](#4-erd-diagram-ascii)
4. [Index Specifications](#5-index-specifications)
5. [Shared Relations](#6-shared-relations)
6. [Migration File Naming Convention](#7-migration-file-naming-convention)

---

## 2. Main Table: `legal_register`

Stores the core legal register record — regulations and compliance tracking.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `register_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `LEG-{YYYY}-{0001}`) |
| 3 | `title` | `varchar(255)` | NO | — | Short title / description of the regulation entry |
| 4 | `regulation_name` | `varchar(255)` | NO | — | Official name of the regulation |
| 5 | `regulation_number` | `varchar(255)` | NO | — | Official number of the regulation (e.g., "UU No. 1 Tahun 1970") |
| 6 | `issuing_body` | `varchar(255)` | NO | — | Authority that issued the regulation (e.g., "Pemerintah RI", "Kemenaker") |
| 7 | `category` | `varchar(30)` | NO | — | **Check constraint** enum: `national`, `regional`, `industry`, `internal` |
| 8 | `compliance_status` | `varchar(30)` | NO | `'in_progress'` | **Check constraint** enum: `compliant`, `non_compliant`, `in_progress`, `not_applicable` |
| 9 | `site_id` | `bigint` | YES | `NULL` | **FK → `sites.id`**. Site where regulation applies (nullable for company-wide) |
| 10 | `department_id` | `bigint` | YES | `NULL` | **FK → `departments.id`**. Department responsible for compliance (nullable) |
| 11 | `owner_id` | `bigint` | NO | — | **FK → `users.id`**. User assigned as compliance owner |
| 12 | `next_review_date` | `date` | YES | `NULL` | Next scheduled compliance review date |
| 13 | `document_id` | `bigint` | YES | `NULL` | **FK → `documents.id`**. Linked controlled document (from module 07) |
| 14 | `notes` | `text` | YES | `NULL` | Additional notes / remarks |
| 15 | `status` | `varchar(20)` | NO | `'active'` | Record status: `active`, `inactive` |
| 16 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 17 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE legal_register (
    id                  BIGSERIAL       PRIMARY KEY,
    register_number     VARCHAR(50)     NOT NULL UNIQUE,
    title               VARCHAR(255)    NOT NULL,
    regulation_name     VARCHAR(255)    NOT NULL,
    regulation_number   VARCHAR(255)    NOT NULL,
    issuing_body        VARCHAR(255)    NOT NULL,
    category            VARCHAR(30)     NOT NULL,
    compliance_status   VARCHAR(30)     NOT NULL DEFAULT 'in_progress',
    site_id             BIGINT          NULL REFERENCES sites(id),
    department_id       BIGINT          NULL REFERENCES departments(id),
    owner_id            BIGINT          NOT NULL REFERENCES users(id),
    next_review_date    DATE            NULL,
    document_id         BIGINT          NULL REFERENCES documents(id),
    notes               TEXT            NULL,
    status              VARCHAR(20)     NOT NULL DEFAULT 'active',
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT legal_register_category_check CHECK (
        category IN ('national', 'regional', 'industry', 'internal')
    ),

    CONSTRAINT legal_register_compliance_status_check CHECK (
        compliance_status IN ('compliant', 'non_compliant', 'in_progress', 'not_applicable')
    ),

    CONSTRAINT legal_register_status_check CHECK (
        status IN ('active', 'inactive')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('legal_register', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('register_number', 50)->unique();
    $table->string('title', 255);
    $table->string('regulation_name', 255);
    $table->string('regulation_number', 255);
    $table->string('issuing_body', 255);
    $table->string('category', 30);
    $table->string('compliance_status', 30)->default('in_progress');
    $table->foreignId('site_id')->nullable()->constrained('sites');
    $table->foreignId('department_id')->nullable()->constrained('departments');
    $table->foreignId('owner_id')->constrained('users');
    $table->date('next_review_date')->nullable();
    $table->foreignId('document_id')->nullable()->constrained('documents');
    $table->text('notes')->nullable();
    $table->string('status', 20)->default('active');
    $table->timestamps();

    // Check constraint for category enum
    $table->check("category IN ('national','regional','industry','internal')", 'legal_register_category_check');

    // Check constraint for compliance_status enum
    $table->check("compliance_status IN ('compliant','non_compliant','in_progress','not_applicable')", 'legal_register_compliance_status_check');

    // Check constraint for status enum
    $table->check("status IN ('active','inactive')", 'legal_register_status_check');
});
```

### Design Notes

- **No soft deletes** (`deleted_at`) — register records use `status` field (`active`/`inactive`) for archiving. Soft deletes can be added if regulatory retention requires it.
- **`register_number`** is generated at create time via `NumberingService::generate('legal', ...)`. Unique constraint prevents duplicates.
- **`category`** is stored as `varchar` with CHECK constraint — 4 values: `national`, `regional`, `industry`, `internal`.
- **`compliance_status`** is stored as `varchar` with CHECK constraint — 4 values: `compliant`, `non_compliant`, `in_progress`, `not_applicable`. Default: `in_progress`.
- **`site_id`** nullable — some regulations apply company-wide, not site-specific.
- **`department_id`** nullable — some regulations are not department-specific.
- **`document_id`** nullable FK to `documents` table (module 07 — Document Control). Links register to controlled document (e.g., regulation PDF, internal procedure).
- **`next_review_date`** nullable — optional scheduling for periodic compliance review.
- **`status`** has 2 values: `active` (default), `inactive` (archived). Inactive registers hidden from default list view.

---

## 3. Child Table: `legal_obligations`

Stores individual obligations associated with a legal register entry. Each obligation tracks recurring compliance tasks with due dates.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `legal_register_id` | `bigint` | NO | — | **FK → `legal_register.id`**, `ON DELETE CASCADE` |
| 3 | `obligation_description` | `text` | NO | — | Detailed description of the obligation |
| 4 | `frequency` | `varchar(20)` | NO | — | **Check constraint** enum: `monthly`, `quarterly`, `annual` |
| 5 | `last_completed` | `date` | YES | `NULL` | Date when obligation was last completed |
| 6 | `next_due` | `date` | YES | `NULL` | Next due date (auto-calculated from last_completed + frequency) |
| 7 | `evidence_file_id` | `bigint` | YES | `NULL` | **FK → `managed_files.id`** (nullable). Evidence file for last completion |
| 8 | `status` | `varchar(20)` | NO | `'pending'` | Obligation status: `pending`, `completed` |
| 9 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 10 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE legal_obligations (
    id                      BIGSERIAL       PRIMARY KEY,
    legal_register_id       BIGINT          NOT NULL REFERENCES legal_register(id) ON DELETE CASCADE,
    obligation_description TEXT            NOT NULL,
    frequency               VARCHAR(20)     NOT NULL,
    last_completed          DATE            NULL,
    next_due                DATE            NULL,
    evidence_file_id        BIGINT          NULL REFERENCES managed_files(id),
    status                  VARCHAR(20)     NOT NULL DEFAULT 'pending',
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT legal_obligations_frequency_check CHECK (
        frequency IN ('monthly', 'quarterly', 'annual')
    ),

    CONSTRAINT legal_obligations_status_check CHECK (
        status IN ('pending', 'completed')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('legal_obligations', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('legal_register_id')->constrained('legal_register')->cascadeOnDelete();
    $table->text('obligation_description');
    $table->string('frequency', 20);
    $table->date('last_completed')->nullable();
    $table->date('next_due')->nullable();
    $table->foreignId('evidence_file_id')->nullable()->constrained('managed_files');
    $table->string('status', 20)->default('pending');
    $table->timestamps();

    // Check constraint for frequency enum
    $table->check("frequency IN ('monthly','quarterly','annual')", 'legal_obligations_frequency_check');

    // Check constraint for status enum
    $table->check("status IN ('pending','completed')", 'legal_obligations_status_check');
});
```

### Design Notes

- **Cascade delete** on `legal_register_id` — when a register is deleted, all its obligations go with it.
- **`frequency`** uses CHECK constraint with 3 values: `monthly`, `quarterly`, `annual`.
- **`last_completed`** nullable — initially null when obligation is first created.
- **`next_due`** nullable but auto-calculated when `last_completed` is set:
  - `monthly`: `next_due = last_completed + INTERVAL '1 month'`
  - `quarterly`: `next_due = last_completed + INTERVAL '3 months'`
  - `annual`: `next_due = last_completed + INTERVAL '1 year'`
- **`evidence_file_id`** nullable FK to `managed_files` table. Set when obligation is completed (evidence required).
- **`status`** has 2 values: `pending` (default), `completed`. After completion, status can be reset to `pending` when the next cycle begins.
- No soft deletes — obligations are part of the register record permanently.

### Overdue Detection Logic

```php
// In LegalObligation model:
public function isOverdue(): bool
{
    return $this->status === 'pending'
        && $this->next_due !== null
        && $this->next_due < now()->toDateString();
}

public function isDueSoon(int $days = 7): bool
{
    return $this->status === 'pending'
        && $this->next_due !== null
        && $this->next_due <= now()->addDays($days)->toDateString()
        && $this->next_due >= now()->toDateString();
}

// Scopes:
public function scopeOverdue(Builder $query): Builder
{
    return $query->where('status', 'pending')
        ->whereNotNull('next_due')
        ->where('next_due', '<', now()->toDateString());
}

public function scopeDueSoon(Builder $query, int $days = 7): Builder
{
    return $query->where('status', 'pending')
        ->whereNotNull('next_due')
        ->where('next_due', '<=', now()->addDays($days)->toDateString())
        ->where('next_due', '>=', now()->toDateString());
}
```

---

## 4. ERD Diagram (ASCII)

```
┌─────────────────────┐         ┌──────────────────────────┐         ┌─────────────────────┐
│      sites           │         │     legal_register        │         │    departments      │
├─────────────────────┤         ├──────────────────────────┤         ├─────────────────────┤
│ id          BIGINT PK│◄──┐    │ id              BIGINT PK │──┐    │ id          BIGINT PK │
│ code        VARCHAR   │   │    │ register_number VARCHAR   │  │    │ code        VARCHAR  │
│ name        VARCHAR   │   │    │ title           VARCHAR   │  │    │ name        VARCHAR  │
│ address     TEXT      │   │    │ regulation_name VARCHAR   │  │    │ site_id     BIGINT FK│──► sites
│ is_active   BOOLEAN   │   │    │ regulation_number VARCHAR │  │    │ is_active   BOOLEAN  │
└─────────────────────┘   │    │ issuing_body    VARCHAR   │  │    └─────────────────────┘
                          │    │ category        VARCHAR   │  │
                          │    │ compliance_status VARCHAR  │  │
                          │    │ site_id         BIGINT FK │──┘
                          │    │ department_id   BIGINT FK │──┼────► departments
                          │    │ owner_id        BIGINT FK │──┼────► users
                          │    │ next_review_date DATE     │  │
                          │    │ document_id     BIGINT FK │──┼────► documents
                          │    │ notes           TEXT      │  │
                          │    │ status          VARCHAR   │  │
                          │    │ created_at      TIMESTAMP │  │
                          │    │ updated_at      TIMESTAMP │  │
                          │    └──────────────────────────┘   │
                          │              │  ▲                │
                          │              │  │ 1:N             │
                          │              ▼  │                │
                          │    ┌──────────────────────────────┤
                          │    │    legal_obligations          │
                          │    ├──────────────────────────────┤
                          │    │ id                  BIGINT PK│
                          │    │ legal_register_id   BIGINT FK ┘ (cascade)
                          │    │ obligation_description TEXT   │
                          │    │ frequency           VARCHAR   │
                          │    │ last_completed      DATE       │
                          │    │ next_due            DATE       │
                          │    │ evidence_file_id    BIGINT FK ──► managed_files
                          │    │ status              VARCHAR    │
                          │    │ created_at          TIMESTAMP  │
                          │    │ updated_at          TIMESTAMP  │
                          │    └──────────────────────────────┘
                          │
                          └── (site_id references sites)

                ┌──────────────────────┐         ┌──────────────────────┐
                │       users           │         │     documents        │
                ├──────────────────────┤         ├──────────────────────┤
                │ id          BIGINT PK │◄── (owner_id)    │ id          BIGINT PK │
                │ name        VARCHAR   │                 │ doc_number  VARCHAR   │
                │ email       VARCHAR   │                 │ title       VARCHAR   │
                │ is_active   BOOLEAN   │                 │ status      VARCHAR   │
                └──────────────────────┘                 └──────────────────────┘

                ┌──────────────────────┐
                │   managed_files      │
                ├──────────────────────┤
                │ id          BIGINT PK │◄── (evidence_file_id)
                │ module_name VARCHAR   │
                │ reference_id BIGINT  │
                │ collection  VARCHAR   │
                │ ...                   │
                └──────────────────────┘
```

### Relationship Summary

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `legal_register` | `site_id` | 1:N | SET NULL |
| `departments` | `legal_register` | `department_id` | 1:N | SET NULL |
| `users` | `legal_register` | `owner_id` | 1:N | RESTRICT (default) |
| `documents` | `legal_register` | `document_id` | 1:N | SET NULL |
| `legal_register` | `legal_obligations` | `legal_register_id` | 1:N | CASCADE |
| `managed_files` | `legal_obligations` | `evidence_file_id` | 1:1 | SET NULL |

---

## 5. Index Specifications

### `legal_register` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `legal_register_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `legal_register_register_number_unique` | `register_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `legal_register_site_id_index` | `site_id` | btree | Filter registers by site |
| 4 | `legal_register_department_id_index` | `department_id` | btree | Filter by department |
| 5 | `legal_register_owner_id_index` | `owner_id` | btree | List registers by owner |
| 6 | `legal_register_compliance_status_index` | `compliance_status` | btree | Filter by compliance status |
| 7 | `legal_register_category_index` | `category` | btree | Filter by regulation category |
| 8 | `legal_register_status_index` | `status` | btree | Filter active/inactive |
| 9 | `legal_register_next_review_date_index` | `next_review_date` | btree | Upcoming review queries |
| 10 | `legal_register_created_at_index` | `created_at` | btree | Sort by creation date |

### `legal_obligations` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `legal_obligations_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `legal_obligations_legal_register_id_index` | `legal_register_id` | btree | Find all obligations for a register |
| 3 | `legal_obligations_status_index` | `status` | btree | Filter by pending/completed |
| 4 | `legal_obligations_next_due_index` | `next_due` | btree | Overdue & due soon queries |
| 5 | `legal_obligations_frequency_index` | `frequency` | btree | Filter by frequency type |
| 6 | `legal_obligations_evidence_file_id_index` | `evidence_file_id` | btree | Find obligation by evidence file |

### Laravel Migration Indexes

```php
// legal_register table
$table->index('site_id');
$table->index('department_id');
$table->index('owner_id');
$table->index('compliance_status');
$table->index('category');
$table->index('status');
$table->index('next_review_date');
$table->index('created_at');

// legal_obligations table
$table->index('legal_register_id');
$table->index('status');
$table->index('next_due');
$table->index('frequency');
$table->index('evidence_file_id');
```

---

## 6. Shared Relations

The Legal & Compliance module uses the **polymorphic `module_name + reference_id`** pattern for all cross-cutting platform services:

- `module_name = 'legal'`
- `reference_id = legal_register.id` (for register-level shared data)
- `reference_id = legal_obligations.id` with `module_name = 'legal_obligation'` (for obligation-level shared data)

### 6.1 Managed Files (`managed_files`)

File attachments (evidence, regulation documents) for a register.

| Column | Value |
|---|---|
| `module_name` | `'legal'` |
| `reference_id` | `legal_register.id` |
| `collection` | `'evidence'` |
| `uploaded_by` | `users.id` (FK) |

```
legal_register.id ──► managed_files.reference_id
                      managed_files.module_name = 'legal'
```

**Usage**: `LegalRegister::files()` returns all files where `module_name='legal'` AND `reference_id=$this->id`.

### 6.2 Comments (`comments`)

Threaded comments / discussion on a register.

| Column | Value |
|---|---|
| `module_name` | `'legal'` |
| `reference_id` | `legal_register.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` — internal-only vs visible |

### 6.3 Activity Logs (`activity_logs`)

Timeline of actions performed on a register (created, updated, compliance changed, obligation added, etc.).

| Column | Value |
|---|---|
| `module_name` | `'legal'` |
| `reference_id` | `legal_register.id` |
| `event` | `'created'`, `'updated'`, `'compliance.changed'`, `'obligation.created'`, `'obligation.completed'`, etc. |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot, metadata) |

### 6.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes on register and obligation records.

| Column | Value |
|---|---|
| `module_name` | `'legal'` |
| `reference_id` | `legal_register.id` |
| `auditable_type` | `'LegalRegister'` or `'LegalObligation'` |
| `auditable_id` | `legal_register.id` or `legal_obligations.id` |
| `old_values` | JSON (previous field values) |
| `new_values` | JSON (new field values) |
| `actor_id` | `users.id` (FK) |

### 6.5 Notifications (`core_notifications`)

Notifications for register events (created, compliance changed, obligation overdue, obligation due soon).

| Column | Value |
|---|---|
| `module_name` | `'legal'` |
| `reference_id` | `legal_register.id` |
| `type` | `'legal.register.created'`, `'legal.compliance.changed'`, `'legal.obligation.overdue'`, `'legal.obligation.due_soon'` |

### Shared Relations Summary

```
                          ┌──────────────────┐
                          │  legal_register   │
                          │  (id: PK)        │
                          └──────┬───────────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='legal'              │
              reference_id=legal_register.id   │
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │  comments   │  │ activity_logs  │
    │  (evidence)        │  │ (discussion)│  │  (timeline)    │
    └───────────────────┘  └─────────────┘  └────────────────┘
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐
    │  audit_logs      │  │ core_       │
    │  (field changes)  │  │ notifications│
    └──────────────────┘  │ (alerts)    │
                          └─────────────┘

    All linked via: module_name='legal' AND reference_id=legal_register.id
    No hard FKs — application-layer validated polymorphic relation.

                    ┌──────────────────────┐
                    │  legal_obligations    │
                    │  (legal_register_id:  │
                    │   FK → legal_register)│
                    └────────┬─────────────┘
                             │
                     evidence_file_id (FK → managed_files)
                     Cross-module link to File Service (core)
```

---

## 7. Migration File Naming Convention

### Convention

Migrations follow Laravel's standard naming pattern:

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

### This Module's Migrations

| # | Migration File | Description |
|---|---|---|
| 1 | `YYYY_MM_DD_HHMMSS_create_legal_register_table.php` | Create `legal_register` table |
| 2 | `YYYY_MM_DD_HHMMSS_create_legal_obligations_table.php` | Create `legal_obligations` table |

### Seeder Files

| # | Seeder File | Description |
|---|---|---|
| 1 | `LegalComplianceSeeder.php` | Seed permissions, role assignments |
| 2 | `LegalNumberingFormatSeeder.php` | Already seeded in Phase 0 (`LEG-2026-0001`) |

### Model File Structure

```
app/Models/Modules/Legal/
├── LegalRegister.php
└── LegalObligation.php

app/Http/Controllers/Modules/Legal/
├── LegalRegisterController.php
└── LegalObligationController.php

app/Http/Requests/Modules/Legal/
├── StoreLegalRegisterRequest.php
├── UpdateLegalRegisterRequest.php
├── StoreLegalObligationRequest.php
└── UpdateLegalObligationRequest.php

database/factories/Modules/Legal/
├── LegalRegisterFactory.php
└── LegalObligationFactory.php

resources/js/Pages/Modules/Legal/
├── Index.tsx
├── Form.tsx
└── Show.tsx

tests/Feature/Modules/Legal/
└── LegalComplianceTest.php
```

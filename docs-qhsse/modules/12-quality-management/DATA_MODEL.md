# Data Model — Quality Management

> Phase 1 schema for the Quality Management module.
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs, workflow).

---

## 1. Table of Contents

1. [Main Table: `ncrs`](#2-main-table-ncrs)
2. [Main Table: `customer_complaints`](#3-main-table-customer_complaints)
3. [ERD Diagram (ASCII)](#4-erd-diagram-ascii)
4. [Index Specifications](#5-index-specifications)
5. [Shared Relations](#6-shared-relations)
6. [Migration File Naming Convention](#7-migration-file-naming-convention)

---

## 2. Main Table: `ncrs`

Stores the core Non-Conformance Report record — internal, external, customer complaint, audit finding, or supplier non-conformance.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `ncr_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create via `NumberingService::generate('quality', ...)`. Format: `NCR-{YYYY}-{0001}` |
| 3 | `title` | `varchar(255)` | NO | — | Short summary of the non-conformance |
| 4 | `source` | `varchar(50)` | NO | — | **Check constraint** enum: `internal`, `external`, `customer_complaint`, `audit`, `supplier` |
| 5 | `description` | `text` | NO | — | Detailed description of the non-conformance |
| 6 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where non-conformance occurred |
| 7 | `department_id` | `bigint` | YES | `NULL` | **FK → `departments.id`**. Owning department |
| 8 | `product_service` | `varchar(255)` | YES | `NULL` | Product or service related to the non-conformance |
| 9 | `batch_lot` | `varchar(100)` | YES | `NULL` | Batch/lot number |
| 10 | `customer_name` | `varchar(255)` | YES | `NULL` | Customer name (if source = customer_complaint or external) |
| 11 | `severity_id` | `bigint` | NO | — | **FK → `severities.id`**. Severity classification |
| 12 | `status` | `varchar(50)` | NO | `'open'` | Lifecycle state: `open`, `under_review`, `in_progress`, `closed`, `rejected` |
| 13 | `root_cause` | `text` | YES | `NULL` | Root Cause Analysis result. Required before close. |
| 14 | `corrective_action` | `text` | YES | `NULL` | Corrective action taken. Required before close. |
| 15 | `preventive_action` | `text` | YES | `NULL` | Preventive action taken. Required before close. |
| 16 | `capa_action_id` | `bigint` | YES | `NULL` | **FK → `capa_actions.id`**. Link to CAPA module |
| 17 | `closed_at` | `timestamp` | YES | `NULL` | Set when NCR is closed |
| 18 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 19 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE ncrs (
    id                  BIGSERIAL       PRIMARY KEY,
    ncr_number          VARCHAR(50)     NOT NULL UNIQUE,
    title               VARCHAR(255)    NOT NULL,
    source              VARCHAR(50)     NOT NULL,
    description         TEXT            NOT NULL,
    site_id             BIGINT          NOT NULL REFERENCES sites(id),
    department_id       BIGINT          NULL REFERENCES departments(id),
    product_service     VARCHAR(255)    NULL,
    batch_lot           VARCHAR(100)    NULL,
    customer_name       VARCHAR(255)    NULL,
    severity_id         BIGINT          NOT NULL REFERENCES severities(id),
    status              VARCHAR(50)     NOT NULL DEFAULT 'open',
    root_cause          TEXT            NULL,
    corrective_action   TEXT            NULL,
    preventive_action   TEXT            NULL,
    capa_action_id      BIGINT          NULL REFERENCES capa_actions(id),
    closed_at           TIMESTAMP       NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT ncrs_source_check CHECK (
        source IN (
            'internal',
            'external',
            'customer_complaint',
            'audit',
            'supplier'
        )
    ),

    CONSTRAINT ncrs_status_check CHECK (
        status IN (
            'open',
            'under_review',
            'in_progress',
            'closed',
            'rejected'
        )
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('ncrs', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('ncr_number', 50)->unique();
    $table->string('title', 255);
    $table->string('source', 50);
    $table->text('description');
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('department_id')->nullable()->constrained('departments');
    $table->string('product_service', 255)->nullable();
    $table->string('batch_lot', 100)->nullable();
    $table->string('customer_name', 255)->nullable();
    $table->foreignId('severity_id')->constrained('severities');
    $table->string('status', 50)->default('open');
    $table->text('root_cause')->nullable();
    $table->text('corrective_action')->nullable();
    $table->text('preventive_action')->nullable();
    $table->foreignId('capa_action_id')->nullable()->constrained('capa_actions');
    $table->timestamp('closed_at')->nullable();
    $table->timestamps();

    // Check constraint for source enum
    $table->check("source IN ('internal','external','customer_complaint','audit','supplier')", 'ncrs_source_check');

    // Check constraint for status enum
    $table->check("status IN ('open','under_review','in_progress','closed','rejected')", 'ncrs_status_check');
});
```

### Design Notes

- **No soft deletes** in Phase 1 — NCRs are never hard-deleted; use `status = 'rejected'` or keep as-is.
- **`ncr_number`** is generated at create time (not at submit) via `NumberingService::generate('quality', $actor, ...)`.
- **`source`** is stored as varchar with CHECK constraint for flexibility.
- **`capa_action_id`** is nullable — NCR can exist without a linked CAPA. When set, links to the `capa_actions` table from Module 04 (CAPA).
- **`root_cause`, `corrective_action`, `preventive_action`** are filled during the `in_progress` phase. At least `root_cause` must be non-null before `close` transition.
- **`closed_at`** is set by the controller when the `close` workflow transition succeeds.

---

## 3. Main Table: `customer_complaints`

Stores customer complaint records. Can optionally link to an NCR via `ncr_id`.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `complaint_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated via `NumberingService::generate('quality', ...)`. Format: `NCR-{YYYY}-{0001}` |
| 3 | `ncr_id` | `bigint` | YES | `NULL` | **FK → `ncrs.id`**. Link to related NCR |
| 4 | `customer_name` | `varchar(255)` | NO | — | Name of the complaining customer |
| 5 | `customer_contact` | `varchar(255)` | YES | `NULL` | Customer contact info (phone/email) |
| 6 | `complaint_date` | `date` | NO | — | Date the complaint was received |
| 7 | `description` | `text` | NO | — | Detailed description of the complaint |
| 8 | `severity_id` | `bigint` | NO | — | **FK → `severities.id`**. Severity classification |
| 9 | `status` | `varchar(50)` | NO | `'open'` | Lifecycle state: `open`, `in_progress`, `closed` |
| 10 | `resolution` | `text` | YES | `NULL` | Resolution description. Required before close. |
| 11 | `resolved_at` | `timestamp` | YES | `NULL` | Set when complaint is closed |
| 12 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 13 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE customer_complaints (
    id                  BIGSERIAL       PRIMARY KEY,
    complaint_number    VARCHAR(50)     NOT NULL UNIQUE,
    ncr_id              BIGINT          NULL REFERENCES ncrs(id),
    customer_name       VARCHAR(255)    NOT NULL,
    customer_contact    VARCHAR(255)    NULL,
    complaint_date      DATE            NOT NULL,
    description         TEXT            NOT NULL,
    severity_id         BIGINT          NOT NULL REFERENCES severities(id),
    status              VARCHAR(50)     NOT NULL DEFAULT 'open',
    resolution          TEXT            NULL,
    resolved_at         TIMESTAMP       NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT customer_complaints_status_check CHECK (
        status IN (
            'open',
            'in_progress',
            'closed'
        )
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('customer_complaints', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('complaint_number', 50)->unique();
    $table->foreignId('ncr_id')->nullable()->constrained('ncrs');
    $table->string('customer_name', 255);
    $table->string('customer_contact', 255)->nullable();
    $table->date('complaint_date');
    $table->text('description');
    $table->foreignId('severity_id')->constrained('severities');
    $table->string('status', 50)->default('open');
    $table->text('resolution')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();

    // Check constraint for status enum
    $table->check("status IN ('open','in_progress','closed')", 'customer_complaints_status_check');
});
```

### Design Notes

- **`complaint_number`** uses the `quality` numbering module, generating `NCR-{YYYY}-{0001}` format. In Phase 2, a separate `CC` prefix may be added via NumberingSeeder.
- **`ncr_id`** is nullable — a complaint can exist standalone or be linked to an NCR. When linked, the NCR show page displays a "Related Complaints" section.
- **`resolution`** must be non-null before the `close` workflow transition.
- **`resolved_at`** is set by the controller when the `close` workflow transition succeeds.
- **No soft deletes** in Phase 1.

---

## 4. ERD Diagram (ASCII)

```
┌─────────────────────┐         ┌──────────────────────┐         ┌─────────────────────┐
│      sites           │         │       ncrs            │         │    departments      │
├─────────────────────┤         ├──────────────────────┤         ├─────────────────────┤
│ id          BIGINT PK│◄──┐    │ id            BIGINT PK│──┐    │ id          BIGINT PK │
│ code        VARCHAR   │   │    │ ncr_number    VARCHAR   │  │    │ code        VARCHAR  │
│ name        VARCHAR   │   │    │ title         VARCHAR   │  │    │ name        VARCHAR  │
│ address     TEXT      │   │    │ source        VARCHAR   │  │    │ site_id     BIGINT FK│──► sites
│ is_active   BOOLEAN   │   │    │ description   TEXT       │  │    │ is_active   BOOLEAN  │
└─────────────────────┘   │    │ site_id       BIGINT FK │──┘    └─────────────────────┘
                          │    │ department_id BIGINT FK │──► departments
                          │    │ product_service VARCHAR  │
                          │    │ batch_lot     VARCHAR   │
                          │    │ customer_name VARCHAR   │
                          │    │ severity_id   BIGINT FK │──► severities
                          │    │ status        VARCHAR   │
                          │    │ root_cause    TEXT       │
                          │    │ corrective_action TEXT   │
                          │    │ preventive_action TEXT  │
                          │    │ capa_action_id BIGINT FK│──► capa_actions (Module 04)
                          │    │ closed_at     TIMESTAMP │
                          │    │ created_at    TIMESTAMP │
                          │    │ updated_at    TIMESTAMP │
                          │    └──────────────────────┘
                          │              │  ▲
                          │              │  │
                          │              ▼  │
                          │    ┌──────────────────────────┐
                          │    │  customer_complaints      │
                          │    ├──────────────────────────┤
                          │    │ id               BIGINT PK│
                          │    │ complaint_number VARCHAR  │
                          │    │ ncr_id           BIGINT FK│──► ncrs (nullable)
                          │    │ customer_name    VARCHAR  │
                          │    │ customer_contact VARCHAR  │
                          │    │ complaint_date   DATE     │
                          │    │ description      TEXT     │
                          │    │ severity_id      BIGINT FK│──► severities
                          │    │ status           VARCHAR  │
                          │    │ resolution       TEXT     │
                          │    │ resolved_at      TIMESTAMP│
                          │    │ created_at       TIMESTAMP│
                          │    │ updated_at       TIMESTAMP│
                          │    └──────────────────────────┘
                          │
                          └── (shared tables via module_name + reference_id)
```

### Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `ncrs` | `site_id` | 1:N | RESTRICT |
| `departments` | `ncrs` | `department_id` | 1:N | SET NULL |
| `severities` | `ncrs` | `severity_id` | 1:N | RESTRICT |
| `capa_actions` | `ncrs` | `capa_action_id` | 1:N | SET NULL |
| `ncrs` | `customer_complaints` | `ncr_id` | 1:N | SET NULL |
| `severities` | `customer_complaints` | `severity_id` | 1:N | RESTRICT |

### Relationship Flow

```
capa_actions (Module 04)                sites (Core)
       │                                    │
       │ 1:N (nullable)                     │ 1:N
       ▼                                    ▼
   ┌────────┐     severity_id FK    ┌──────────────┐
   │  ncrs   │◄─────────────────────│  severities  │
   │  (PK)   │                      │  (Core)      │
   └────┬───┘                      └──────────────┘
        │ 1:N (nullable)
        ▼
   ┌─────────────────────┐          severity_id FK
   │ customer_complaints │◄─────────────────────► severities
   │ (PK)                │
   └─────────────────────┘

   Shared (polymorphic):
   ncrs.id ──► managed_files (module_name='quality', reference_id=ncr.id)
   ncrs.id ──► comments (module_name='quality', reference_id=ncr.id)
   ncrs.id ──► activity_logs (module_name='quality', reference_id=ncr.id)
   ncrs.id ──► audit_logs (module_name='quality', reference_id=ncr.id)
   ncrs.id ──► workflow_instances (module_name='quality', reference_id=ncr.id)

   customer_complaints.id ──► managed_files (module_name='quality_complaint', reference_id=complaint.id)
   customer_complaints.id ──► comments (module_name='quality_complaint', reference_id=complaint.id)
   customer_complaints.id ──► activity_logs (module_name='quality_complaint', reference_id=complaint.id)
   customer_complaints.id ──► audit_logs (module_name='quality_complaint', reference_id=complaint.id)
   customer_complaints.id ──► workflow_instances (module_name='quality_complaint', reference_id=complaint.id)
```

---

## 5. Index Specifications

### `ncrs` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `ncrs_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `ncrs_ncr_number_unique` | `ncr_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `ncrs_site_id_index` | `site_id` | btree | Filter by site |
| 4 | `ncrs_department_id_index` | `department_id` | btree | Filter by department |
| 5 | `ncrs_severity_id_index` | `severity_id` | btree | Filter by severity |
| 6 | `ncrs_status_index` | `status` | btree | Filter/list by workflow status |
| 7 | `ncrs_source_index` | `source` | btree | Filter by source type |
| 8 | `ncrs_capa_action_id_index` | `capa_action_id` | btree | Find NCRs linked to a CAPA |
| 9 | `ncrs_created_at_index` | `created_at` | btree | Sort by creation date |
| 10 | `ncrs_closed_at_index` | `closed_at` | btree | Filter by close date |

### `customer_complaints` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `customer_complaints_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `customer_complaints_complaint_number_unique` | `complaint_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `customer_complaints_ncr_id_index` | `ncr_id` | btree | Find complaints linked to NCR |
| 4 | `customer_complaints_severity_id_index` | `severity_id` | btree | Filter by severity |
| 5 | `customer_complaints_status_index` | `status` | btree | Filter/list by status |
| 6 | `customer_complaints_complaint_date_index` | `complaint_date` | btree | Filter/sort by complaint date |
| 7 | `customer_complaints_created_at_index` | `created_at` | btree | Sort by creation date |

### Laravel Migration Indexes

```php
// ncrs table
$table->index('site_id');
$table->index('department_id');
$table->index('severity_id');
$table->index('status');
$table->index('source');
$table->index('capa_action_id');
$table->index('created_at');
$table->index('closed_at');

// customer_complaints table
$table->index('ncr_id');
$table->index('severity_id');
$table->index('status');
$table->index('complaint_date');
$table->index('created_at');
```

---

## 6. Shared Relations

The Quality Management module uses the **polymorphic `module_name + reference_id`** pattern for all cross-cutting concerns. No duplicated tables.

### 6.1 NCR Shared Relations

- `module_name = 'quality'`
- `reference_id = ncrs.id`

| Shared Table | module_name | collection / event values |
|---|---|---|
| `managed_files` | `'quality'` | `'evidence'`, `'photos'`, `'documents'` |
| `comments` | `'quality'` | threaded discussion, `is_internal` flag |
| `activity_logs` | `'quality'` | `'created'`, `'submitted'`, `'reviewed'`, `'closed'`, etc. |
| `audit_logs` | `'quality'` | field-level change tracking |
| `workflow_instances` | `'quality'` | lifecycle tracking |
| `workflow_histories` | `'quality'` | transition log |

### 6.2 Customer Complaint Shared Relations

- `module_name = 'quality_complaint'`
- `reference_id = customer_complaints.id`

| Shared Table | module_name | collection / event values |
|---|---|---|
| `managed_files` | `'quality_complaint'` | `'evidence'`, `'resolution'` |
| `comments` | `'quality_complaint'` | threaded discussion |
| `activity_logs` | `'quality_complaint'` | `'created'`, `'closed'`, etc. |
| `audit_logs` | `'quality_complaint'` | field-level change tracking |
| `workflow_instances` | `'quality_complaint'` | lifecycle tracking |
| `workflow_histories` | `'quality_complaint'` | transition log |

### Shared Relations Summary

```
                          ┌──────────────┐
                          │    ncrs      │
                          │  (id: PK)    │
                          └──────┬───────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='quality'           │
              reference_id=ncrs.id           │
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │  comments   │  │ activity_logs  │
    │  (evidence, docs) │  │ (discussion)│  │  (timeline)    │
    └───────────────────┘  └─────────────┘  └────────────────┘
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐
    │  audit_logs      │  │ workflow_   │
    │  (field changes) │  │ instances   │
    └──────────────────┘  │ (lifecycle) │
                          └─────┬───────┘
                                │ 1:N
                          ┌─────▼───────┐
                          │ workflow_   │
                          │ histories   │
                          │ (transitions│
                          │  log)       │
                          └─────────────┘

    All linked via: module_name='quality' AND reference_id=ncrs.id


    ┌──────────────────────┐
    │ customer_complaints  │     module_name='quality_complaint'
    │  (id: PK)            │     reference_id=customer_complaints.id
    └──────────┬───────────┘
               │
         Same shared tables (managed_files, comments, activity_logs,
         audit_logs, workflow_instances, workflow_histories)
```

---

## 7. Migration File Naming Convention

### Convention

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

### Phase 1 Migration Files

| # | Migration File | Description |
|---|---|---|
| 1 | `2026_07_11_000001_create_ncrs_table.php` | Create `ncrs` table |
| 2 | `2026_07_11_000002_create_customer_complaints_table.php` | Create `customer_complaints` table |

### Seeder Files

| # | Seeder File | Description |
|---|---|---|
| 1 | `QualityManagementSeeder.php` | Seed workflow definitions, permissions via CorePermissions, numbering already seeded (`quality` → `NCR` prefix) |

### Factory Files

| # | Factory File | Description |
|---|---|---|
| 1 | `database/factories/Modules/Quality/NcrFactory.php` | Factory for `ncrs` table |
| 2 | `database/factories/Modules/Quality/CustomerComplaintFactory.php` | Factory for `customer_complaints` table |

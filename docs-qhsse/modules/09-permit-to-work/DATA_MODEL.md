# Data Model — Permit to Work

> Phase 9 schema for the Permit to Work module.  
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs, workflow).

---

## 1. Table of Contents

1. [Main Table: `permits`](#2-main-table-permits)
2. [Child Table: `permit_checklists`](#3-child-table-permit_checklists)
3. [ERD Diagram (ASCII)](#4-erd-diagram-ascii)
4. [Index Specifications](#5-index-specifications)
5. [Shared Relations](#6-shared-relations)
6. [Migration File Naming Convention](#7-migration-file-naming-convention)

---

## 2. Main Table: `permits`

Stores the core permit-to-work record — hot work, working at height, confined space, electrical, excavation, lifting, or other.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `permit_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `PTW-{SITE_CODE-}{YYYY}-{0001}`) |
| 3 | `type` | `varchar(50)` | NO | — | **Check constraint** enum: `hot_work`, `working_at_height`, `confined_space`, `electrical`, `excavation`, `lifting`, `other` |
| 4 | `title` | `varchar(255)` | NO | — | Short summary of the permit / work title |
| 5 | `description` | `text` | NO | — | General description of the permit |
| 6 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where work will be performed |
| 7 | `area_id` | `bigint` | YES | `NULL` | **FK → `areas.id`**. Specific area within site |
| 8 | `department_id` | `bigint` | YES | `NULL` | **FK → `departments.id`**. Owning department |
| 9 | `contractor_id` | `bigint` | YES | `NULL` | **FK → `companies.id`**. Contractor company if applicable |
| 10 | `work_location` | `varchar(255)` | NO | — | Specific physical work location description |
| 11 | `work_description` | `text` | NO | — | Detailed description of the work to be performed |
| 12 | `start_datetime` | `timestamp` | NO | — | When the permit validity starts |
| 13 | `end_datetime` | `timestamp` | NO | — | When the permit validity ends |
| 14 | `validity_hours` | `integer` | NO | — | Duration in hours (auto-calculated: end - start) |
| 15 | `status` | `varchar(50)` | NO | `'draft'` | Lifecycle state: `draft`, `submitted`, `under_review`, `approved`, `active`, `closed`, `rejected` |
| 16 | `risk_level` | `varchar(50)` | YES | `NULL` | Risk classification: `low`, `medium`, `high`, `critical` |
| 17 | `jsa_reference` | `varchar(255)` | YES | `NULL` | Reference to JSA/Risk assessment document number |
| 18 | `approved_by` | `bigint` | YES | `NULL` | **FK → `users.id`**. User who approved the permit |
| 19 | `approved_at` | `timestamp` | YES | `NULL` | When the permit was approved |
| 20 | `closed_by` | `bigint` | YES | `NULL` | **FK → `users.id`**. User who closed the permit |
| 21 | `closed_at` | `timestamp` | YES | `NULL` | When the permit was closed |
| 22 | `cancellation_reason` | `text` | YES | `NULL` | Reason for rejection (used when status = rejected) |
| 23 | `created_by` | `bigint` | NO | — | **FK → `users.id`**. User who created the permit (requester) |
| 24 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 25 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE permits (
    id                  BIGSERIAL       PRIMARY KEY,
    permit_number       VARCHAR(50)     NOT NULL UNIQUE,
    type                VARCHAR(50)     NOT NULL,
    title               VARCHAR(255)    NOT NULL,
    description         TEXT            NOT NULL,
    site_id             BIGINT          NOT NULL REFERENCES sites(id),
    area_id             BIGINT          NULL REFERENCES areas(id),
    department_id       BIGINT          NULL REFERENCES departments(id),
    contractor_id       BIGINT          NULL REFERENCES companies(id),
    work_location       VARCHAR(255)    NOT NULL,
    work_description    TEXT            NOT NULL,
    start_datetime      TIMESTAMP       NOT NULL,
    end_datetime        TIMESTAMP       NOT NULL,
    validity_hours      INTEGER         NOT NULL,
    status              VARCHAR(50)     NOT NULL DEFAULT 'draft',
    risk_level          VARCHAR(50)     NULL,
    jsa_reference       VARCHAR(255)    NULL,
    approved_by         BIGINT          NULL REFERENCES users(id),
    approved_at         TIMESTAMP       NULL,
    closed_by           BIGINT          NULL REFERENCES users(id),
    closed_at           TIMESTAMP       NULL,
    cancellation_reason TEXT            NULL,
    created_by          BIGINT          NOT NULL REFERENCES users(id),
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT permits_type_check CHECK (
        type IN (
            'hot_work',
            'working_at_height',
            'confined_space',
            'electrical',
            'excavation',
            'lifting',
            'other'
        )
    ),

    CONSTRAINT permits_status_check CHECK (
        status IN (
            'draft',
            'submitted',
            'under_review',
            'approved',
            'active',
            'closed',
            'rejected'
        )
    ),

    CONSTRAINT permits_risk_level_check CHECK (
        risk_level IS NULL OR risk_level IN ('low', 'medium', 'high', 'critical')
    ),

    CONSTRAINT permits_datetime_check CHECK (end_datetime > start_datetime),
    CONSTRAINT permits_validity_hours_check CHECK (validity_hours >= 1)
);
```

### Laravel Migration (Reference)

```php
Schema::create('permits', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('permit_number', 50)->unique();
    $table->string('type', 50);
    $table->string('title', 255);
    $table->text('description');
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('area_id')->nullable()->constrained('areas');
    $table->foreignId('department_id')->nullable()->constrained('departments');
    $table->foreignId('contractor_id')->nullable()->constrained('companies');
    $table->string('work_location', 255);
    $table->text('work_description');
    $table->timestamp('start_datetime');
    $table->timestamp('end_datetime');
    $table->integer('validity_hours');
    $table->string('status', 50)->default('draft');
    $table->string('risk_level', 50)->nullable();
    $table->string('jsa_reference', 255)->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('closed_by')->nullable()->constrained('users');
    $table->timestamp('closed_at')->nullable();
    $table->text('cancellation_reason')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    // Indexes
    $table->index('site_id');
    $table->index('area_id');
    $table->index('department_id');
    $table->index('contractor_id');
    $table->index('type');
    $table->index('status');
    $table->index('risk_level');
    $table->index('start_datetime');
    $table->index('end_datetime');
    $table->index('approved_by');
    $table->index('closed_by');
    $table->index('created_by');
    $table->index('created_at');

    // Check constraints
    $table->check("type IN ('hot_work','working_at_height','confined_space','electrical','excavation','lifting','other')", 'permits_type_check');
    $table->check("status IN ('draft','submitted','under_review','approved','active','closed','rejected')", 'permits_status_check');
    $table->check("risk_level IS NULL OR risk_level IN ('low','medium','high','critical')", 'permits_risk_level_check');
    $table->check('end_datetime > start_datetime', 'permits_datetime_check');
    $table->check('validity_hours >= 1', 'permits_validity_hours_check');
});
```

### Design Notes

- **No soft deletes** (`deleted_at`) — permits are never hard-deleted; use `status = 'rejected'` for cancelled permits.
- **`created_by`** tracks the requester/creator separately from Laravel's `timestamps()`.
- **`permit_number`** is unique and generated at **create** (POST store), not at draft save. Generated via `NumberingService::generate('permit', ...)`.
- **`type`** is stored as a `varchar` with a CHECK constraint — simplifies application-level validation.
- **`validity_hours`** is auto-calculated as `round((end_datetime - start_datetime) / 3600)` and stored for query efficiency.
- **`risk_level`** is nullable — not all permits require a formal risk classification.
- **`jsa_reference`** is a free-text reference to an external JSA/Risk assessment document (future: FK to risk module).
- **`cancellation_reason`** is used when status = `rejected` (stores the reject reason from the workflow transition).
- **`approved_by`** and `closed_by` are nullable, set only when the respective transition occurs.

---

## 3. Child Table: `permit_checklists`

Stores checklist items for each permit. Items are auto-generated based on permit type at creation time. Each item must be signed (checked) before the permit can be activated.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `permit_id` | `bigint` | NO | — | **FK → `permits.id`**, `ON DELETE CASCADE` |
| 3 | `item_text` | `text` | NO | — | Checklist item description (e.g., "APD tahan api tersedia dan dipakai") |
| 4 | `is_checked` | `boolean` | NO | `false` | Whether the item has been signed/checked |
| 5 | `checked_by` | `bigint` | YES | `NULL` | **FK → `users.id`**. User who signed this item |
| 6 | `checked_at` | `timestamp` | YES | `NULL` | When the item was signed |
| 7 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 8 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE permit_checklists (
    id              BIGSERIAL       PRIMARY KEY,
    permit_id       BIGINT          NOT NULL REFERENCES permits(id) ON DELETE CASCADE,
    item_text       TEXT            NOT NULL,
    is_checked      BOOLEAN         NOT NULL DEFAULT false,
    checked_by      BIGINT          NULL REFERENCES users(id),
    checked_at      TIMESTAMP       NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

### Laravel Migration (Reference)

```php
Schema::create('permit_checklists', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('permit_id')->constrained('permits')->cascadeOnDelete();
    $table->text('item_text');
    $table->boolean('is_checked')->default(false);
    $table->foreignId('checked_by')->nullable()->constrained('users');
    $table->timestamp('checked_at')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('permit_id');
    $table->index('is_checked');
    $table->index('checked_by');
});
```

### Design Notes

- **Cascade delete** on `permit_id` — when a permit is deleted, all checklist items go with it.
- **`item_text`** is free-text, populated from checklist templates at creation time based on permit `type`.
- **`is_checked`** defaults to `false` — must be explicitly set to `true` by a user with appropriate permission.
- **`checked_by`** and `checked_at` are NULL until the item is signed.
- No unique constraint needed — each row is a distinct checklist item.
- Checklist templates are defined in PHP config (see MODULE_SPEC.md Section 5) and seeded at permit creation time.

---

## 4. ERD Diagram (ASCII)

```
┌─────────────────────┐         ┌──────────────────────┐         ┌─────────────────────┐
│      sites           │         │       permits          │         │    departments      │
├─────────────────────┤         ├──────────────────────┤         ├─────────────────────┤
│ id          BIGINT PK│◄──┐    │ id            BIGINT PK│──┐    │ id          BIGINT PK │
│ code        VARCHAR   │   │    │ permit_number VARCHAR UQ │  │    │ code        VARCHAR  │
│ name        VARCHAR   │   │    │ type          VARCHAR   │  │    │ name        VARCHAR  │
│ address     TEXT      │   │    │ title         VARCHAR   │  │    │ site_id     BIGINT FK│──► sites
│ is_active   BOOLEAN   │   │    │ description   TEXT      │  │    │ is_active   BOOLEAN  │
└─────────────────────┘   │    │ site_id       BIGINT FK │──┘    └─────────────────────┘
                          │    │ area_id       BIGINT FK │──┐
                          │    │ department_id BIGINT FK │──┼────► departments
                          │    │ contractor_id BIGINT FK │──┼────► companies
                          │    │ work_location VARCHAR   │  │
                          │    │ work_description TEXT   │  │
                          │    │ start_datetime TIMESTAMP│  │
                          │    │ end_datetime   TIMESTAMP│  │
                          │    │ validity_hours INTEGER  │  │
                          │    │ status        VARCHAR   │  │
                          │    │ risk_level    VARCHAR   │  │
                          │    │ jsa_reference VARCHAR   │  │
                          │    │ approved_by   BIGINT FK │──┼────► users
                          │    │ approved_at   TIMESTAMP │  │
                          │    │ closed_by     BIGINT FK │──┼────► users
                          │    │ closed_at     TIMESTAMP │  │
                          │    │ cancellation_reason TEXT│  │
                          │    │ created_by    BIGINT FK │──┼────► users
                          │    │ created_at    TIMESTAMP│  │
                          │    │ updated_at    TIMESTAMP│  │
                          │    └──────────────────────┘  │
                          │              │  ▲             │
                          │              │  │             │
                          │              ▼  │             │
                          │    ┌──────────────────────────┤
                          │    │  permit_checklists        │
                          │    ├──────────────────────────┤
                          │    │ id           BIGINT PK   │
                          │    │ permit_id    BIGINT FK ──┘ (cascade)
                          │    │ item_text    TEXT        │
                          │    │ is_checked   BOOLEAN     │
                          │    │ checked_by   BIGINT FK ──► users
                          │    │ checked_at   TIMESTAMP   │
                          │    │ created_at   TIMESTAMP   │
                          │    │ updated_at   TIMESTAMP   │
                          │    └──────────────────────────┘
                          │
                          │    ┌─────────────────────┐
                          └────│  companies           │
                               │  (contractor)        │
                               ├─────────────────────┤
                               │ id          BIGINT PK │
                               │ code        VARCHAR  │
                               │ name        VARCHAR  │
                               │ type        VARCHAR  │
                               │ is_active   BOOLEAN  │
                               └─────────────────────┘
```

### Relationship Summary

```
                        ┌──────────────┐
                        │   users      │
                        │ (created_by) │
                        └──────┬───────┘
                               │ 1
                               │
                          ┌────▼────┐          ┌──────────────────┐
                  N:1     │ permits  │     1:N  │ permit_checklists │
                ┌────────►│(main)    │◄─────────│  (child)         │
                │         └──┬─┬─┬───┘          └──────┬───────────┘
                │            │ │ │                     │
                │            │ │ │                N:1  │
                │     ┌──────┘ │ └──────┐             │
                │     │ N:1    │ N:1   │ N:1         │ N:1
                │  ┌──▼───┐ ┌─▼────┐ ┌─▼─────┐  ┌────▼─────┐
                │  │sites │ │users │ │compa- │  │  users   │
                │  │      │ │(appr)│ │nies   │  │(checker) │
                │  └──┬───┘ └──────┘ └───────┘  └──────────┘
                │     │
                │  ┌──▼───┐
                │  │areas │
                │  └──────┘
                │
          ┌─────▼─────┐
          │departments│
          └───────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `permits` | `site_id` | 1:N | RESTRICT (default) |
| `areas` | `permits` | `area_id` | 1:N | SET NULL |
| `departments` | `permits` | `department_id` | 1:N | SET NULL |
| `companies` | `permits` | `contractor_id` | 1:N | SET NULL |
| `users` | `permits` | `created_by` | 1:N | RESTRICT (default) |
| `users` | `permits` | `approved_by` | 1:N | SET NULL |
| `users` | `permits` | `closed_by` | 1:N | SET NULL |
| `permits` | `permit_checklists` | `permit_id` | 1:N | CASCADE |
| `users` | `permit_checklists` | `checked_by` | 1:N | SET NULL |

---

## 5. Index Specifications

### `permits` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `permits_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `permits_permit_number_unique` | `permit_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `permits_site_id_index` | `site_id` | btree | Filter permits by site |
| 4 | `permits_area_id_index` | `area_id` | btree | Filter permits by area |
| 5 | `permits_department_id_index` | `department_id` | btree | Filter permits by department |
| 6 | `permits_contractor_id_index` | `contractor_id` | btree | Filter by contractor |
| 7 | `permits_type_index` | `type` | btree | Filter by permit type |
| 8 | `permits_status_index` | `status` | btree | Filter/list by workflow status |
| 9 | `permits_risk_level_index` | `risk_level` | btree | Filter by risk level |
| 10 | `permits_start_datetime_index` | `start_datetime` | btree | Sort/filter by start date |
| 11 | `permits_end_datetime_index` | `end_datetime` | btree | Sort/filter by end date / expiry check |
| 12 | `permits_approved_by_index` | `approved_by` | btree | Find permits by approver |
| 13 | `permits_closed_by_index` | `closed_by` | btree | Find permits by closer |
| 14 | `permits_created_by_index` | `created_by` | btree | List permits by requester (scope: own) |
| 15 | `permits_created_at_index` | `created_at` | btree | Sort by creation date |

### `permit_checklists` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `permit_checklists_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `permit_checklists_permit_id_index` | `permit_id` | btree | Find all checklist items for a permit |
| 3 | `permit_checklists_is_checked_index` | `is_checked` | btree | Filter checked/unchecked items |
| 4 | `permit_checklists_checked_by_index` | `checked_by` | btree | Find items checked by a user |

### Laravel Migration Indexes

```php
// permits table
$table->index('site_id');
$table->index('area_id');
$table->index('department_id');
$table->index('contractor_id');
$table->index('type');
$table->index('status');
$table->index('risk_level');
$table->index('start_datetime');
$table->index('end_datetime');
$table->index('approved_by');
$table->index('closed_by');
$table->index('created_by');
$table->index('created_at');

// permit_checklists table
$table->index('permit_id');
$table->index('is_checked');
$table->index('checked_by');
```

---

## 6. Shared Relations

The Permit to Work module does **not** duplicate file, comment, log, or workflow tables. Instead, all cross-cutting platform services use the **polymorphic `module_name + reference_id`** pattern. For this module:

- `module_name = 'permit'`
- `reference_id = permits.id`

### 6.1 Managed Files (`managed_files`)

File attachments (evidence, JSA documents, photos) for a permit.

| Column | Value |
|---|---|
| `module_name` | `'permit'` |
| `reference_id` | `permits.id` |
| `collection` | `'evidence'` |
| `uploaded_by` | `users.id` (FK) |

```
permits.id ──► managed_files.reference_id
                    managed_files.module_name = 'permit'
```

**Usage**: `Permit::files()` returns all files where `module_name='permit'` AND `reference_id=$this->id`.

### 6.2 Comments (`comments`)

Threaded comments / discussion on a permit.

| Column | Value |
|---|---|
| `module_name` | `'permit'` |
| `reference_id` | `permits.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` — internal-only vs visible to requester |

### 6.3 Activity Logs (`activity_logs`)

Timeline of actions performed on a permit (created, submitted, reviewed, approved, activated, etc.).

| Column | Value |
|---|---|
| `module_name` | `'permit'` |
| `reference_id` | `permits.id` |
| `event` | `'created'`, `'submitted'`, `'reviewed'`, `'approved'`, `'activated'`, `'rejected'`, `'closed'`, `'checklist.signed'` |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot, metadata) |

### 6.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes on permit records.

| Column | Value |
|---|---|
| `module_name` | `'permit'` |
| `reference_id` | `permits.id` |
| `auditable_type` | `'Permit'` (or fully-qualified model class) |
| `auditable_id` | `permits.id` (mirrors `reference_id` for ORM compatibility) |
| `old_values` | JSON (previous field values) |
| `new_values` | JSON (new field values) |
| `actor_id` | `users.id` (FK) |

### 6.5 Workflow Instances (`workflow_instances`)

Each permit that enters a workflow gets a workflow instance tracking its progression.

| Column | Value |
|---|---|
| `module_name` | `'permit'` |
| `reference_id` | `permits.id` |
| `workflow_definition_id` | FK to `workflow_definitions.id` |
| `current_status` | Mirrors `permits.status` |
| `started_by` | `users.id` (FK) |
| `completed_at` | nullable, set when permit is closed/rejected |

### 6.6 Workflow Histories (`workflow_histories`)

Every workflow transition (status change) for a permit is logged here.

| Column | Value |
|---|---|
| `module_name` | `'permit'` |
| `reference_id` | `permits.id` |
| `workflow_instance_id` | FK to `workflow_instances.id` |
| `from_status` | Previous status (e.g., `'draft'`) |
| `to_status` | New status (e.g., `'submitted'`) |
| `action_key` | `'submit'`, `'review'`, `'approve'`, `'activate'`, `'reject'`, `'close'` |
| `actor_id` | `users.id` (FK) |

### Shared Relations Summary

```
                          ┌──────────────┐
                          │   permits    │
                          │  (id: PK)    │
                          └──────┬───────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='permit'            │
              reference_id=permits.id        │
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

    All linked via: module_name='permit' AND reference_id=permits.id
    No hard FKs — application-layer validated polymorphic relation.
```

---

## 7. Migration File Naming Convention

### Convention

Migrations follow Laravel's standard naming pattern with module-prefixed descriptions:

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

| Segment | Description |
|---|---|
| `YYYY_MM_DD_HHMMSS` | Timestamp (auto-generated by `php artisan make:migration`) |
| `verb` | `create` for new tables, `add`, `update`, `drop` for modifications |

### Migration Files for This Module

| File | Description |
|---|---|
| `2026_07_11_000001_create_permits_table.php` | Create `permits` table |
| `2026_07_11_000002_create_permit_checklists_table.php` | Create `permit_checklists` table |

### Seeder Files

| File | Description |
|---|---|
| `PermitToWorkSeeder.php` | Register permissions `permit.work.*` in CorePermissions, assign to roles |
| `PermitWorkflowSeeder.php` | Create `PERMIT_WORKFLOW` definition with transitions |
| `PermitNumberingSeeder.php` | Confirm numbering format `PTW` (already seeded, verify `include_site_code=true`) |
| `PermitChecklistTemplateSeeder.php` | Seed checklist templates per permit type (if stored in DB; otherwise define in config) |

### Model File Structure

```
app/Models/Modules/PermitToWork/
    Permit.php
    PermitChecklist.php
```

### Factory File Structure

```
database/factories/Modules/PermitToWork/
    PermitFactory.php
    PermitChecklistFactory.php
```

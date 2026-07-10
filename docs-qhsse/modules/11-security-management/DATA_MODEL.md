# Data Model — Security Management

> Phase 3 schema for the Security Management module.  
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs, workflow).

---

## 1. Table of Contents

1. [Table: `security_incidents`](#2-table-security_incidents)
2. [Table: `visitor_logs`](#3-table-visitor_logs)
3. [Table: `patrol_checklists`](#4-table-patrol_checklists)
4. [Table: `patrol_results`](#5-table-patrol_results)
5. [ERD Diagram (ASCII)](#6-erd-diagram-ascii)
6. [Index Specifications](#7-index-specifications)
7. [Shared Relations](#8-shared-relations)
8. [Migration File Naming Convention](#9-migration-file-naming-convention)

---

## 2. Table: `security_incidents`

Stores security incident records — unauthorized access, theft, vandalism, trespass, suspicious activity, or other security-related events.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `security_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `SEC-{YYYY}-{0001}`) via NumberingService |
| 3 | `type` | `varchar(50)` | NO | — | **Check constraint** enum: `unauthorized_access`, `theft`, `vandalism`, `trespass`, `suspicious_activity`, `other` |
| 4 | `title` | `varchar(255)` | NO | — | Short summary of the incident |
| 5 | `description` | `text` | NO | — | Detailed narrative of what happened |
| 6 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where incident occurred |
| 7 | `area_id` | `bigint` | YES | `NULL` | **FK → `areas.id`**. Specific area within site |
| 8 | `occurred_at` | `timestamp` | NO | — | When the incident actually happened |
| 9 | `reported_by` | `bigint` | NO | — | **FK → `users.id`**. User who reported the incident |
| 10 | `severity_id` | `bigint` | NO | — | **FK → `severities.id`**. Severity classification |
| 11 | `status` | `varchar(50)` | NO | `'reported'` | Lifecycle: `reported`, `under_investigation`, `closed` |
| 12 | `resolution` | `text` | YES | `NULL` | Resolution narrative, filled on close |
| 13 | `resolved_at` | `timestamp` | YES | `NULL` | Set when status transitions to `closed` |
| 14 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 15 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE security_incidents (
    id                  BIGSERIAL       PRIMARY KEY,
    security_number     VARCHAR(50)     NOT NULL UNIQUE,
    type                VARCHAR(50)     NOT NULL,
    title               VARCHAR(255)    NOT NULL,
    description         TEXT            NOT NULL,
    site_id             BIGINT          NOT NULL REFERENCES sites(id),
    area_id             BIGINT          NULL REFERENCES areas(id),
    occurred_at         TIMESTAMP       NOT NULL,
    reported_by         BIGINT          NOT NULL REFERENCES users(id),
    severity_id         BIGINT          NOT NULL REFERENCES severities(id),
    status              VARCHAR(50)     NOT NULL DEFAULT 'reported',
    resolution          TEXT            NULL,
    resolved_at         TIMESTAMP       NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT security_incidents_type_check CHECK (
        type IN (
            'unauthorized_access',
            'theft',
            'vandalism',
            'trespass',
            'suspicious_activity',
            'other'
        )
    ),

    CONSTRAINT security_incidents_status_check CHECK (
        status IN (
            'reported',
            'under_investigation',
            'closed'
        )
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('security_incidents', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('security_number', 50)->unique();
    $table->string('type', 50);
    $table->string('title', 255);
    $table->text('description');
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('area_id')->nullable()->constrained('areas');
    $table->timestamp('occurred_at');
    $table->foreignId('reported_by')->constrained('users');
    $table->foreignId('severity_id')->constrained('severities');
    $table->string('status', 50)->default('reported');
    $table->text('resolution')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();

    // Check constraint for type enum
    $table->check("type IN ('unauthorized_access','theft','vandalism','trespass','suspicious_activity','other')", 'security_incidents_type_check');

    // Check constraint for status enum
    $table->check("status IN ('reported','under_investigation','closed')", 'security_incidents_status_check');
});
```

### Design Notes

- **No soft deletes** (`deleted_at`) in Phase 1 — security incidents are never hard-deleted; use `status = 'closed'` instead.
- **`security_number`** is unique and generated at **create**, not at submit. Every record gets a number immediately.
- **`type`** is stored as a `varchar` with a CHECK constraint for enum validation.
- **`resolution`** and **`resolved_at`** are nullable until the incident is closed.
- **`area_id`** references `areas` table (specific area within site).

---

## 3. Table: `visitor_logs`

Stores visitor check-in/check-out records for tracking all visitors entering and leaving the facility.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `visitor_name` | `varchar(255)` | NO | — | Full name of the visitor |
| 3 | `visitor_company` | `varchar(255)` | YES | `NULL` | Company/organization the visitor represents |
| 4 | `purpose` | `text` | NO | — | Purpose of visit |
| 5 | `host_id` | `bigint` | NO | — | **FK → `users.id`** (or `employees.id`). The person being visited |
| 6 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where visitor checked in |
| 7 | `check_in_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | When visitor checked in |
| 8 | `check_out_at` | `timestamp` | YES | `NULL` | When visitor checked out. NULL = still on-site |
| 9 | `id_type` | `varchar(50)` | NO | — | Type of ID: KTP, SIM, Passport, etc. |
| 10 | `id_number` | `varchar(100)` | NO | — | ID number |
| 11 | `vehicle_plate` | `varchar(20)` | YES | `NULL` | Vehicle license plate number |
| 12 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 13 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE visitor_logs (
    id                  BIGSERIAL       PRIMARY KEY,
    visitor_name        VARCHAR(255)    NOT NULL,
    visitor_company     VARCHAR(255)    NULL,
    purpose             TEXT            NOT NULL,
    host_id             BIGINT          NOT NULL REFERENCES users(id),
    site_id             BIGINT          NOT NULL REFERENCES sites(id),
    check_in_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    check_out_at        TIMESTAMP       NULL,
    id_type             VARCHAR(50)     NOT NULL,
    id_number           VARCHAR(100)    NOT NULL,
    vehicle_plate       VARCHAR(20)     NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

### Laravel Migration (Reference)

```php
Schema::create('visitor_logs', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('visitor_name', 255);
    $table->string('visitor_company', 255)->nullable();
    $table->text('purpose');
    $table->foreignId('host_id')->constrained('users');
    $table->foreignId('site_id')->constrained('sites');
    $table->timestamp('check_in_at')->useCurrent();
    $table->timestamp('check_out_at')->nullable();
    $table->string('id_type', 50);
    $table->string('id_number', 100);
    $table->string('vehicle_plate', 20)->nullable();
    $table->timestamps();
});
```

### Design Notes

- **No soft deletes** — visitor logs are immutable audit records. Once checked in, they cannot be deleted.
- **`check_out_at`** is NULL when visitor is still on-site. Used to filter "visitors on-site" in dashboard.
- **`host_id`** references `users` table (the employee/user hosting the visitor). Can be extended to `employees` table in future.
- **`id_type`** is free-text (KTP, SIM, Passport, Visitor Pass, etc.) — not a FK to a master table in Phase 1.
- **`vehicle_plate`** is nullable — only filled if visitor arrives by vehicle.

---

## 4. Table: `patrol_checklists`

Stores scheduled patrol checklists — a planned security patrol with route, officer, and checkpoints.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `patrol_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `SPL-{YYYY}-{0001}`) via NumberingService |
| 3 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where patrol is conducted |
| 4 | `patrol_route` | `varchar(255)` | NO | — | Name/description of the patrol route |
| 5 | `officer_id` | `bigint` | NO | — | **FK → `users.id`**. Security officer assigned to patrol |
| 6 | `scheduled_at` | `timestamp` | NO | — | When the patrol is scheduled to start |
| 7 | `executed_at` | `timestamp` | YES | `NULL` | When patrol execution actually started |
| 8 | `status` | `varchar(50)` | NO | `'scheduled'` | Lifecycle: `scheduled`, `in_progress`, `completed` |
| 9 | `notes` | `text` | YES | `NULL` | General notes about the patrol |
| 10 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 11 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE patrol_checklists (
    id                  BIGSERIAL       PRIMARY KEY,
    patrol_number       VARCHAR(50)     NOT NULL UNIQUE,
    site_id             BIGINT          NOT NULL REFERENCES sites(id),
    patrol_route        VARCHAR(255)    NOT NULL,
    officer_id          BIGINT          NOT NULL REFERENCES users(id),
    scheduled_at        TIMESTAMP       NOT NULL,
    executed_at         TIMESTAMP       NULL,
    status              VARCHAR(50)     NOT NULL DEFAULT 'scheduled',
    notes               TEXT            NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT patrol_checklists_status_check CHECK (
        status IN (
            'scheduled',
            'in_progress',
            'completed'
        )
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('patrol_checklists', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('patrol_number', 50)->unique();
    $table->foreignId('site_id')->constrained('sites');
    $table->string('patrol_route', 255);
    $table->foreignId('officer_id')->constrained('users');
    $table->timestamp('scheduled_at');
    $table->timestamp('executed_at')->nullable();
    $table->string('status', 50)->default('scheduled');
    $table->text('notes')->nullable();
    $table->timestamps();

    // Check constraint for status enum
    $table->check("status IN ('scheduled','in_progress','completed')", 'patrol_checklists_status_check');
});
```

### Design Notes

- **`patrol_number`** uses prefix `SPL` (Security Patrol Log) to distinguish from security incidents (`SEC`). Numbering format: `SPL-2026-0001`.
- **`officer_id`** references `users` table — the security officer assigned to conduct the patrol.
- **`executed_at`** is NULL until patrol execution starts (status → `in_progress`).
- **`status`** has 3 states: `scheduled` (initial), `in_progress` (execution started), `completed` (all checkpoints filled).
- No soft deletes — patrol records are audit trail items.

---

## 5. Table: `patrol_results`

Stores individual checkpoint results for each patrol checklist — one row per checkpoint per patrol.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `patrol_checklist_id` | `bigint` | NO | — | **FK → `patrol_checklists.id`**, `ON DELETE CASCADE` |
| 3 | `checkpoint` | `varchar(255)` | NO | — | Name/description of the checkpoint |
| 4 | `status` | `varchar(20)` | NO | — | **Check constraint** enum: `ok`, `issue`, `na` |
| 5 | `remark` | `text` | YES | `NULL` | Remark about the checkpoint. Required if status = `issue` |
| 6 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 7 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE patrol_results (
    id                      BIGSERIAL       PRIMARY KEY,
    patrol_checklist_id     BIGINT          NOT NULL REFERENCES patrol_checklists(id) ON DELETE CASCADE,
    checkpoint              VARCHAR(255)    NOT NULL,
    status                  VARCHAR(20)     NOT NULL,
    remark                  TEXT            NULL,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT patrol_results_status_check CHECK (
        status IN ('ok', 'issue', 'na')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('patrol_results', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('patrol_checklist_id')->constrained('patrol_checklists')->cascadeOnDelete();
    $table->string('checkpoint', 255);
    $table->string('status', 20);
    $table->text('remark')->nullable();
    $table->timestamps();

    // Check constraint for status enum
    $table->check("status IN ('ok','issue','na')", 'patrol_results_status_check');
});
```

### Design Notes

- **Cascade delete** on `patrol_checklist_id` — when a patrol checklist is deleted, all its results go with it.
- **`checkpoint`** is free-text (e.g., "Gerbang Utama", "Gudang Bahan Baku", "Area Parkir") — defined at patrol creation or execution time. Not a FK to a master table in Phase 1.
- **`status`** has 3 values: `ok` (all clear), `issue` (problem found), `na` (not applicable / not checked).
- **`remark`** is required when `status = 'issue'` (enforced at application/validation layer, not DB level, for flexibility).
- One patrol checklist can have N patrol results (1:N relationship).

---

## 6. ERD Diagram (ASCII)

```
┌─────────────────────────┐
│        sites             │
├─────────────────────────┤
│ id          BIGINT  PK  │
│ code        VARCHAR      │
│ name        VARCHAR      │
│ is_active   BOOLEAN      │
└───────────┬─────────────┘
            │
            │ 1:N (site_id)
            │
    ┌───────┼───────────────────────────┐
    │       │                           │
    ▼       ▼                           ▼
┌──────────────────────────────┐  ┌──────────────────────────────┐
│     security_incidents        │  │      visitor_logs             │
├──────────────────────────────┤  ├──────────────────────────────┤
│ id               BIGINT  PK  │  │ id               BIGINT  PK  │
│ security_number  VARCHAR UQ  │  │ visitor_name     VARCHAR     │
│ type             VARCHAR     │  │ visitor_company  VARCHAR     │
│ title            VARCHAR     │  │ purpose          TEXT        │
│ description      TEXT        │  │ host_id    BIGINT FK→users   │
│ site_id    BIGINT FK→sites   │  │ site_id    BIGINT FK→sites   │
│ area_id    BIGINT FK→areas   │  │ check_in_at      TIMESTAMP   │
│ occurred_at       TIMESTAMP  │  │ check_out_at     TIMESTAMP   │
│ reported_by BIGINT FK→users  │  │ id_type          VARCHAR     │
│ severity_id BIGINT FK→sev    │  │ id_number        VARCHAR     │
│ status           VARCHAR     │  │ vehicle_plate    VARCHAR     │
│ resolution       TEXT (null) │  │ created_at       TIMESTAMP   │
│ resolved_at      TIMESTAMP   │  │ updated_at       TIMESTAMP   │
│ created_at       TIMESTAMP   │  └──────────────────────────────┘
│ updated_at       TIMESTAMP   │
└──────────────┬──────────────┘
               │
               │ module_name='security'
               │ reference_id=security_incidents.id
               │
               ▼
    ┌──────────────────────────┐
    │    managed_files         │
    │    (evidence)            │
    └──────────────────────────┘


┌─────────────────────────┐
│        users              │
├─────────────────────────┤
│ id          BIGINT  PK  │
│ name        VARCHAR     │
│ email       VARCHAR     │
│ is_active   BOOLEAN     │
└───────────┬─────────────┘
            │
            │ 1:N (officer_id / reported_by / host_id)
            │
    ┌───────┼───────────────────────────────┐
    │       │                               │
    ▼       ▼                               ▼
┌──────────────────────────────┐  ┌──────────────────────────────┐
│     patrol_checklists         │  │   (security_incidents        │
├──────────────────────────────┤  │    reported_by→users)        │
│ id               BIGINT  PK  │  └──────────────────────────────┘
│ patrol_number    VARCHAR UQ  │
│ site_id    BIGINT FK→sites   │
│ patrol_route     VARCHAR     │
│ officer_id BIGINT FK→users   │
│ scheduled_at     TIMESTAMP   │
│ executed_at      TIMESTAMP   │
│ status           VARCHAR     │
│ notes            TEXT (null) │
│ created_at       TIMESTAMP   │
│ updated_at       TIMESTAMP   │
└──────────────┬──────────────┘
               │
               │ 1:N (patrol_checklist_id, CASCADE)
               │
               ▼
    ┌──────────────────────────────┐
    │       patrol_results          │
    ├──────────────────────────────┤
    │ id                   BIGINT PK│
    │ patrol_checklist_id  BIGINT FK│ (cascade)
    │ checkpoint           VARCHAR  │
    │ status               VARCHAR  │ (ok/issue/na)
    │ remark               TEXT     │ (null, req if issue)
    │ created_at           TIMESTAMP│
    │ updated_at           TIMESTAMP│
    └──────────────────────────────┘
```

### Entity Relationship Summary

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `security_incidents` | `site_id` | 1:N | RESTRICT |
| `areas` | `security_incidents` | `area_id` | 1:N | SET NULL |
| `users` | `security_incidents` | `reported_by` | 1:N | RESTRICT |
| `severities` | `security_incidents` | `severity_id` | 1:N | RESTRICT |
| `sites` | `visitor_logs` | `site_id` | 1:N | RESTRICT |
| `users` | `visitor_logs` | `host_id` | 1:N | RESTRICT |
| `sites` | `patrol_checklists` | `site_id` | 1:N | RESTRICT |
| `users` | `patrol_checklists` | `officer_id` | 1:N | RESTRICT |
| `patrol_checklists` | `patrol_results` | `patrol_checklist_id` | 1:N | CASCADE |

---

## 7. Index Specifications

### `security_incidents` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `security_incidents_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `security_incidents_security_number_unique` | `security_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `security_incidents_site_id_index` | `site_id` | btree | Filter by site |
| 4 | `security_incidents_area_id_index` | `area_id` | btree | Filter by area |
| 5 | `security_incidents_reported_by_index` | `reported_by` | btree | List by reporter |
| 6 | `security_incidents_severity_id_index` | `severity_id` | btree | Filter by severity |
| 7 | `security_incidents_status_index` | `status` | btree | Filter by status |
| 8 | `security_incidents_type_index` | `type` | btree | Filter by type |
| 9 | `security_incidents_occurred_at_index` | `occurred_at` | btree | Sort/filter by date |
| 10 | `security_incidents_created_at_index` | `created_at` | btree | Sort by creation date |

### `visitor_logs` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `visitor_logs_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `visitor_logs_site_id_index` | `site_id` | btree | Filter by site |
| 3 | `visitor_logs_host_id_index` | `host_id` | btree | List by host |
| 4 | `visitor_logs_check_in_at_index` | `check_in_at` | btree | Filter/sort by check-in date |
| 5 | `visitor_logs_check_out_at_index` | `check_out_at` | btree | Filter on-site visitors (IS NULL) |
| 6 | `visitor_logs_created_at_index` | `created_at` | btree | Sort by creation date |

### `patrol_checklists` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `patrol_checklists_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `patrol_checklists_patrol_number_unique` | `patrol_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `patrol_checklists_site_id_index` | `site_id` | btree | Filter by site |
| 4 | `patrol_checklists_officer_id_index` | `officer_id` | btree | List by officer |
| 5 | `patrol_checklists_status_index` | `status` | btree | Filter by status |
| 6 | `patrol_checklists_scheduled_at_index` | `scheduled_at` | btree | Filter/sort by schedule date |
| 7 | `patrol_checklists_created_at_index` | `created_at` | btree | Sort by creation date |

### `patrol_results` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `patrol_results_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `patrol_results_patrol_checklist_id_index` | `patrol_checklist_id` | btree | Find all results for a patrol |
| 3 | `patrol_results_status_index` | `status` | btree | Filter by status (ok/issue/na) |

### Laravel Migration Indexes

```php
// security_incidents table
$table->index('site_id');
$table->index('area_id');
$table->index('reported_by');
$table->index('severity_id');
$table->index('status');
$table->index('type');
$table->index('occurred_at');
$table->index('created_at');

// visitor_logs table
$table->index('site_id');
$table->index('host_id');
$table->index('check_in_at');
$table->index('check_out_at');
$table->index('created_at');

// patrol_checklists table
$table->index('site_id');
$table->index('officer_id');
$table->index('status');
$table->index('scheduled_at');
$table->index('created_at');

// patrol_results table
$table->index('patrol_checklist_id');
$table->index('status');
```

---

## 8. Shared Relations

The Security Management module uses the **polymorphic `module_name + reference_id`** pattern for cross-cutting concerns:

- `module_name = 'security'`
- `reference_id = security_incidents.id`

### 8.1 Managed Files (`managed_files`)

File attachments (evidence, photos, documents) for security incidents.

| Column | Value |
|---|---|
| `module_name` | `'security'` |
| `reference_id` | `security_incidents.id` |
| `collection` | `'evidence'` |
| `uploaded_by` | `users.id` (FK) |

**Usage**: `SecurityIncident::files()` returns all files where `module_name='security'` AND `reference_id=$this->id`.

### 8.2 Comments (`comments`)

Threaded comments on security incidents.

| Column | Value |
|---|---|
| `module_name` | `'security'` |
| `reference_id` | `security_incidents.id` |
| `parent_id` | `comments.id` (nullable) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` |

### 8.3 Activity Logs (`activity_logs`)

Timeline of actions performed on security records.

| Column | Value |
|---|---|
| `module_name` | `'security'` |
| `reference_id` | `security_incidents.id` |
| `event` | `'created'`, `'updated'`, `'investigation_started'`, `'closed'`, `'checked_in'`, `'checked_out'`, `'patrol_executed'`, `'patrol_completed'` |
| `actor_id` | `users.id` (FK) |

### 8.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes on security records.

| Column | Value |
|---|---|
| `module_name` | `'security'` |
| `reference_id` | `security_incidents.id` |
| `auditable_type` | `'SecurityIncident'` |
| `auditable_id` | `security_incidents.id` |
| `old_values` | JSON |
| `new_values` | JSON |
| `actor_id` | `users.id` (FK) |

### Shared Relations Summary

```
                          ┌──────────────────────┐
                          │ security_incidents   │
                          │ (id: PK)             │
                          └──────────┬───────────┘
                                     │
                    ┌────────────────┼────────────────┐
                    │                │                │
              module_name='security'  │                │
              reference_id=security_incidents.id      │
                    │                │                │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │  comments   │  │ activity_logs  │
    │  (evidence)       │  │ (discussion)│  │ (timeline)     │
    └───────────────────┘  └─────────────┘  └────────────────┘
                    │                │                │
    ┌───────────────▼──┐
    │  audit_logs      │
    │  (field changes) │
    └──────────────────┘

    All linked via: module_name='security' AND reference_id=security_incidents.id
    No hard FKs — application-layer validated polymorphic relation.
```

> **Note:** Visitor logs and patrol checklists do NOT use shared relations (no files, comments, or workflow). They use direct activity_logs and audit_logs via `module_name='security'` and their respective IDs, but with simpler tracking.

---

## 9. Migration File Naming Convention

Migrations follow Laravel's standard naming pattern:

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

| Migration | Description |
|---|---|
| `create_security_incidents_table` | Creates `security_incidents` table |
| `create_visitor_logs_table` | Creates `visitor_logs` table |
| `create_patrol_checklists_table` | Creates `patrol_checklists` table |
| `create_patrol_results_table` | Creates `patrol_results` table |

### Model File Locations

```
app/Models/Modules/Security/
    SecurityIncident.php
    VisitorLog.php
    PatrolChecklist.php
    PatrolResult.php
```

### Factory File Locations

```
database/factories/Modules/Security/
    SecurityIncidentFactory.php
    VisitorLogFactory.php
    PatrolChecklistFactory.php
    PatrolResultFactory.php
```

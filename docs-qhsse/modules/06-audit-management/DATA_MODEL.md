# Data Model — Audit Management

> Phase 6 schema for the Audit Management module.  
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs, workflow).

---

## 1. Table of Contents

1. [Main Table: `audits`](#2-main-table-audits)
2. [Child Table: `audit_findings`](#3-child-table-audit_findings)
3. [ERD Diagram (ASCII)](#4-erd-diagram-ascii)
4. [Index Specifications](#5-index-specifications)
5. [Shared Relations](#6-shared-relations)
6. [Migration File Naming Convention](#7-migration-file-naming-convention)

---

## 2. Main Table: `audits`

Stores the core audit record — internal, external, or supplier audit.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `audit_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `AUD-{YYYY}-{0001}`) |
| 3 | `title` | `varchar(255)` | NO | — | Short title of the audit |
| 4 | `type` | `varchar(30)` | NO | — | **Check constraint** enum: `internal`, `external`, `supplier` |
| 5 | `standard` | `varchar(100)` | YES | `NULL` | Standard reference (e.g., `ISO 45001:2018`, `SMK3`) |
| 6 | `scope` | `text` | NO | — | Audit scope description (what is being audited) |
| 7 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where audit is conducted |
| 8 | `department_id` | `bigint` | YES | `NULL` | **FK → `departments.id`**. Audited department (nullable for site-wide audit) |
| 9 | `lead_auditor_id` | `bigint` | NO | — | **FK → `users.id`**. User assigned as lead auditor |
| 10 | `start_date` | `date` | NO | — | Planned/actual start date of audit |
| 11 | `end_date` | `date` | YES | `NULL` | Planned/actual end date (nullable if single-day or ongoing) |
| 12 | `status` | `varchar(30)` | NO | `'planned'` | Lifecycle state: `planned`, `in_progress`, `report_ready`, `closed` |
| 13 | `summary` | `text` | YES | `NULL` | Audit report summary (required when generating report) |
| 14 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 15 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE audits (
    id                  BIGSERIAL       PRIMARY KEY,
    audit_number        VARCHAR(50)     NOT NULL UNIQUE,
    title               VARCHAR(255)    NOT NULL,
    type                VARCHAR(30)     NOT NULL,
    standard            VARCHAR(100)    NULL,
    scope               TEXT            NOT NULL,
    site_id             BIGINT          NOT NULL REFERENCES sites(id),
    department_id       BIGINT          NULL REFERENCES departments(id),
    lead_auditor_id     BIGINT          NOT NULL REFERENCES users(id),
    start_date          DATE            NOT NULL,
    end_date            DATE            NULL,
    status              VARCHAR(30)     NOT NULL DEFAULT 'planned',
    summary             TEXT            NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT audits_type_check CHECK (
        type IN ('internal', 'external', 'supplier')
    ),

    CONSTRAINT audits_status_check CHECK (
        status IN ('planned', 'in_progress', 'report_ready', 'closed')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('audits', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('audit_number', 50)->unique();
    $table->string('title', 255);
    $table->string('type', 30);
    $table->string('standard', 100)->nullable();
    $table->text('scope');
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('department_id')->nullable()->constrained('departments');
    $table->foreignId('lead_auditor_id')->constrained('users');
    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->string('status', 30)->default('planned');
    $table->text('summary')->nullable();
    $table->timestamps();

    // Check constraint for type enum
    $table->check("type IN ('internal','external','supplier')", 'audits_type_check');

    // Check constraint for status enum
    $table->check("status IN ('planned','in_progress','report_ready','closed')", 'audits_status_check');
});
```

### Design Notes

- **No soft deletes** (`deleted_at`) — audit records are never hard-deleted; use status lifecycle instead. Soft deletes can be added if regulatory retention requires it.
- **`audit_number`** is generated at create time via `NumberingService::generate('audit', ...)`. Unique constraint prevents duplicates.
- **`type`** is stored as `varchar` with CHECK constraint — simplifies application-level validation.
- **`standard`** is free-text (not FK) to allow flexibility in standard naming (ISO 45001:2018, SMK3, custom internal standards).
- **`department_id`** nullable — some audits are site-wide and not department-specific.
- **`end_date`** nullable — audit may be single-day or end date may not be known at planning time.
- **`summary`** nullable — filled when generating report (transition `in_progress` → `report_ready`).

---

## 3. Child Table: `audit_findings`

Stores individual findings identified during an audit. Each finding can link to a CAPA action.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `audit_id` | `bigint` | NO | — | **FK → `audits.id`**, `ON DELETE CASCADE` |
| 3 | `finding_number` | `varchar(60)` | NO | — | **Unique per audit**. Format: `{audit_number}-F{NN}` (e.g., `AUD-2026-0001-F01`) |
| 4 | `description` | `text` | NO | — | Detailed description of the finding |
| 5 | `classification` | `varchar(20)` | NO | — | **Check constraint** enum: `major`, `minor`, `observation`, `ofi` |
| 6 | `area` | `varchar(255)` | YES | `NULL` | Specific area/process where finding was identified |
| 7 | `recommendation` | `text` | YES | `NULL` | Recommended corrective action or improvement |
| 8 | `capa_action_id` | `bigint` | YES | `NULL` | **FK → `capa_actions.id`** (nullable). Linked CAPA record |
| 9 | `status` | `varchar(20)` | NO | `'open'` | Finding status: `open`, `closed` |
| 10 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 11 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE audit_findings (
    id                  BIGSERIAL       PRIMARY KEY,
    audit_id            BIGINT          NOT NULL REFERENCES audits(id) ON DELETE CASCADE,
    finding_number      VARCHAR(60)     NOT NULL,
    description         TEXT            NOT NULL,
    classification      VARCHAR(20)     NOT NULL,
    area                VARCHAR(255)    NULL,
    recommendation      TEXT            NULL,
    capa_action_id      BIGINT          NULL REFERENCES capa_actions(id),
    status              VARCHAR(20)     NOT NULL DEFAULT 'open',
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT audit_findings_classification_check CHECK (
        classification IN ('major', 'minor', 'observation', 'ofi')
    ),

    CONSTRAINT audit_findings_status_check CHECK (
        status IN ('open', 'closed')
    ),

    CONSTRAINT audit_findings_audit_number_unique UNIQUE (audit_id, finding_number)
);
```

### Laravel Migration (Reference)

```php
Schema::create('audit_findings', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
    $table->string('finding_number', 60);
    $table->text('description');
    $table->string('classification', 20);
    $table->string('area', 255)->nullable();
    $table->text('recommendation')->nullable();
    $table->foreignId('capa_action_id')->nullable()->constrained('capa_actions');
    $table->string('status', 20)->default('open');
    $table->timestamps();

    // Check constraint for classification enum
    $table->check("classification IN ('major','minor','observation','ofi')", 'audit_findings_classification_check');

    // Check constraint for status enum
    $table->check("status IN ('open','closed')", 'audit_findings_status_check');

    // Finding number unique per audit
    $table->unique(['audit_id', 'finding_number']);
});
```

### Design Notes

- **Cascade delete** on `audit_id` — when an audit is deleted, all its findings go with it.
- **`finding_number`** is unique per audit (composite unique: `audit_id` + `finding_number`). Format: `{audit_number}-F{NN}` where NN is a per-audit sequence starting at 01.
- **`classification`** uses CHECK constraint with 4 values: `major`, `minor`, `observation`, `ofi`.
- **`capa_action_id`** is nullable FK to `capa_actions` table (module 04). Set when user clicks "Create CAPA" or "Link CAPA" on a finding.
- **`status`** has 2 values: `open` (default), `closed`. Finding is closed after CAPA is completed or finding is addressed.
- No soft deletes — findings are part of the audit record permanently.

---

## 4. ERD Diagram (ASCII)

```
┌─────────────────────┐         ┌──────────────────────┐         ┌─────────────────────┐
│      sites           │         │       audits          │         │    departments      │
├─────────────────────┤         ├──────────────────────┤         ├─────────────────────┤
│ id          BIGINT PK│◄──┐    │ id            BIGINT PK│──┐    │ id          BIGINT PK │
│ code        VARCHAR   │   │    │ audit_number  VARCHAR  │  │    │ code        VARCHAR  │
│ name        VARCHAR   │   │    │ title         VARCHAR   │  │    │ name        VARCHAR  │
│ address     TEXT      │   │    │ type          VARCHAR   │  │    │ site_id     BIGINT FK│──► sites
│ is_active   BOOLEAN   │   │    │ standard      VARCHAR   │  │    │ is_active   BOOLEAN  │
└─────────────────────┘   │    │ scope         TEXT       │  │    └─────────────────────┘
                          │    │ site_id       BIGINT FK │──┘
                          │    │ department_id BIGINT FK │──┼────► departments
                          │    │ lead_auditor_id BIGINT FK│──┼────► users
                          │    │ start_date    DATE       │  │
                          │    │ end_date      DATE       │  │
                          │    │ status        VARCHAR    │  │
                          │    │ summary       TEXT       │  │
                          │    │ created_at    TIMESTAMP  │  │
                          │    │ updated_at    TIMESTAMP  │  │
                          │    └──────────────────────┘   │
                          │              │  ▲              │
                          │              │  │ 1:N          │
                          │              ▼  │              │
                          │    ┌───────────────────────────┤
                          │    │    audit_findings          │
                          │    ├───────────────────────────┤
                          │    │ id            BIGINT PK    │
                          │    │ audit_id      BIGINT FK ───┘ (cascade)
                          │    │ finding_number VARCHAR(60)  │
                          │    │ description   TEXT          │
                          │    │ classification VARCHAR(20) │
                          │    │ area          VARCHAR(255)  │
                          │    │ recommendation TEXT         │
                          │    │ capa_action_id BIGINT FK ───► capa_actions
                          │    │ status        VARCHAR(20)  │
                          │    │ created_at    TIMESTAMP     │
                          │    │ updated_at    TIMESTAMP     │
                          │    └───────────────────────────┘
                          │                   │
                          │                   │ N:1
                          │                   ▼
                          │    ┌───────────────────────────┐
                          │    │    capa_actions            │
                          │    ├───────────────────────────┤
                          │    │ id            BIGINT PK    │
                          │    │ capa_number   VARCHAR(50)  │ (ACT-YYYY-NNNN)
                          │    │ title         VARCHAR(255) │
                          │    │ source_module VARCHAR(50)  │ = 'audit'
                          │    │ source_reference_id BIGINT │ = audit_finding.id
                          │    │ ...                        │
                          │    └───────────────────────────┘
                          │
                          └── (site_id references sites)

                ┌──────────────────────┐
                │       users           │
                ├──────────────────────┤
                │ id          BIGINT PK │◄── (lead_auditor_id)
                │ name        VARCHAR   │
                │ email       VARCHAR   │
                │ is_active   BOOLEAN   │
                └──────────────────────┘
```

### Relationship Summary

```
                        ┌──────────────┐
                        │    users      │
                        │ (lead_auditor)│
                        └──────┬───────┘
                               │ 1
                               │
                          ┌────▼────┐          ┌──────────────┐
                  N:1     │ audits   │     1:N  │audit_findings│
                ┌────────►│(main)    │◄─────────│  (child)     │
                │         └──┬───┬───┘          └──────┬───────┘
                │            │   │                     │
                │            │   │                N:1  │
                │     ┌──────┘   └──────┐             │
                │     │ N:1             │ N:1          │ N:1
                │  ┌──▼───┐      ┌──▼─────┐    ┌─────▼─────┐
                │  │sites │      │depart-  │    │capa_actions│
                │  │      │      │ments    │    │           │
                │  └──────┘      └─────────┘    └───────────┘
                │
          ┌─────▼─────┐
          │  users     │ (also lead_auditor_id → users)
          └───────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `audits` | `site_id` | 1:N | RESTRICT (default) |
| `departments` | `audits` | `department_id` | 1:N | SET NULL |
| `users` | `audits` | `lead_auditor_id` | 1:N | RESTRICT (default) |
| `audits` | `audit_findings` | `audit_id` | 1:N | CASCADE |
| `capa_actions` | `audit_findings` | `capa_action_id` | 1:N | SET NULL |

---

## 5. Index Specifications

### `audits` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `audits_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `audits_audit_number_unique` | `audit_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `audits_site_id_index` | `site_id` | btree | Filter audits by site |
| 4 | `audits_department_id_index` | `department_id` | btree | Filter audits by department |
| 5 | `audits_lead_auditor_id_index` | `lead_auditor_id` | btree | List audits by lead auditor |
| 6 | `audits_status_index` | `status` | btree | Filter/list by workflow status |
| 7 | `audits_type_index` | `type` | btree | Filter by audit type |
| 8 | `audits_start_date_index` | `start_date` | btree | Sort/filter by date range |
| 9 | `audits_created_at_index` | `created_at` | btree | Sort by creation date |

### `audit_findings` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `audit_findings_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `audit_findings_audit_id_index` | `audit_id` | btree | Find all findings for an audit |
| 3 | `audit_findings_audit_id_finding_number_unique` | `audit_id, finding_number` | UNIQUE (btree) | Prevent duplicate finding numbers per audit |
| 4 | `audit_findings_classification_index` | `classification` | btree | Filter by classification (major/minor/observation/ofi) |
| 5 | `audit_findings_status_index` | `status` | btree | Filter by open/closed |
| 6 | `audit_findings_capa_action_id_index` | `capa_action_id` | btree | Find findings linked to a specific CAPA |

### Laravel Migration Indexes

```php
// audits table
$table->index('site_id');
$table->index('department_id');
$table->index('lead_auditor_id');
$table->index('status');
$table->index('type');
$table->index('start_date');
$table->index('created_at');

// audit_findings table
$table->index('audit_id');
$table->index('classification');
$table->index('status');
$table->index('capa_action_id');
$table->unique(['audit_id', 'finding_number']);
```

---

## 6. Shared Relations

The Audit Management module uses the **polymorphic `module_name + reference_id`** pattern for all cross-cutting platform services:

- `module_name = 'audit'`
- `reference_id = audits.id` (for audit-level shared data)
- `reference_id = audit_findings.id` with `module_name = 'audit_finding'` (for finding-level shared data, if needed)

### 6.1 Managed Files (`managed_files`)

File attachments (evidence, reports, checklists) for an audit.

| Column | Value |
|---|---|
| `module_name` | `'audit'` |
| `reference_id` | `audits.id` |
| `collection` | `'evidence'`, `'report'`, `'checklist'` |
| `uploaded_by` | `users.id` (FK) |

```
audits.id ──► managed_files.reference_id
                managed_files.module_name = 'audit'
```

**Usage**: `Audit::files()` returns all files where `module_name='audit'` AND `reference_id=$this->id`.

### 6.2 Comments (`comments`)

Threaded comments / discussion on an audit.

| Column | Value |
|---|---|
| `module_name` | `'audit'` |
| `reference_id` | `audits.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` — internal-only (auditor discussion) vs visible to auditee |

```
audits.id ──► comments.reference_id
                comments.module_name = 'audit'
```

### 6.3 Activity Logs (`activity_logs`)

Timeline of actions performed on an audit (created, started, report generated, closed, finding added, etc.).

| Column | Value |
|---|---|
| `module_name` | `'audit'` |
| `reference_id` | `audits.id` |
| `event` | `'created'`, `'started'`, `'report_generated'`, `'closed'`, `'finding.created'`, `'finding.closed'`, `'capa.linked'`, etc. |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot, metadata) |

### 6.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes on audit and finding records.

| Column | Value |
|---|---|
| `module_name` | `'audit'` |
| `reference_id` | `audits.id` |
| `auditable_type` | `'Audit'` or `'AuditFinding'` |
| `auditable_id` | `audits.id` or `audit_findings.id` |
| `old_values` | JSON (previous field values) |
| `new_values` | JSON (new field values) |
| `actor_id` | `users.id` (FK) |

### 6.5 Workflow Instances (`workflow_instances`)

Each audit that enters a workflow gets a workflow instance tracking its progression.

| Column | Value |
|---|---|
| `module_name` | `'audit'` |
| `reference_id` | `audits.id` |
| `workflow_definition_id` | FK to `workflow_definitions.id` |
| `current_status` | Mirrors `audits.status` |
| `started_by` | `users.id` (FK) |
| `completed_at` | nullable, set when audit is closed |

### 6.6 Workflow Histories (`workflow_histories`)

Every workflow transition (status change) for an audit is logged here.

| Column | Value |
|---|---|
| `module_name` | `'audit'` |
| `reference_id` | `audits.id` |
| `workflow_instance_id` | FK to `workflow_instances.id` |
| `from_status` | Previous status (e.g., `'planned'`) |
| `to_status` | New status (e.g., `'in_progress'`) |
| `action_key` | `'start'`, `'generate_report'`, `'close'` |
| `actor_id` | `users.id` (FK) |

### Shared Relations Summary

```
                          ┌──────────────┐
                          │   audits     │
                          │  (id: PK)    │
                          └──────┬───────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='audit'              │
              reference_id=audits.id          │
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │  comments   │  │ activity_logs  │
    │  (evidence, report│  │ (discussion)│  │  (timeline)    │
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

    All linked via: module_name='audit' AND reference_id=audits.id
    No hard FKs — application-layer validated polymorphic relation.

                    ┌──────────────────┐
                    │  audit_findings   │
                    │  (audit_id: FK)   │
                    └────────┬─────────┘
                             │
                     capa_action_id (FK → capa_actions)
                     Cross-module link to CAPA module (04)
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
| 1 | `YYYY_MM_DD_HHMMSS_create_audits_table.php` | Create `audits` table |
| 2 | `YYYY_MM_DD_HHMMSS_create_audit_findings_table.php` | Create `audit_findings` table |

### Seeder Files

| # | Seeder File | Description |
|---|---|---|
| 1 | `AuditManagementSeeder.php` | Seed workflow definition, permissions, role assignments |
| 2 | `AuditNumberingFormatSeeder.php` | Already seeded in Phase 0 (`AUD-2026-0001`) |

### Model File Structure

```
app/Models/Modules/Audit/
├── Audit.php
└── AuditFinding.php

app/Http/Controllers/Modules/Audit/
├── AuditController.php
└── AuditFindingController.php

app/Http/Requests/Modules/Audit/
├── StoreAuditRequest.php
├── UpdateAuditRequest.php
├── GenerateAuditReportRequest.php
├── StoreAuditFindingRequest.php
└── UpdateAuditFindingRequest.php

database/factories/Modules/Audit/
├── AuditFactory.php
└── AuditFindingFactory.php

resources/js/Pages/Modules/Audit/
├── Index.tsx
├── Form.tsx
└── Show.tsx

tests/Feature/Modules/Audit/
└── AuditManagementTest.php
```

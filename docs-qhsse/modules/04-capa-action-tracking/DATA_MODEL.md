# Data Model — CAPA / Corrective & Preventive Action Tracking

> Phase 3 schema for the CAPA module.
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs, workflow).

---

## 1. Table of Contents

1. [Main Table: `capa_actions`](#2-main-table-capa_actions)
2. [ERD Diagram (ASCII)](#3-erd-diagram-ascii)
3. [Index Specifications](#4-index-specifications)
4. [Shared Relations](#5-shared-relations)
5. [Migration File Naming Convention](#6-migration-file-naming-convention)

---

## 2. Main Table: `capa_actions`

Stores the core CAPA action record — corrective or preventive action tracked from creation through verification and closure.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `action_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `ACT-{YYYY}-{0001}`) |
| 3 | `title` | `varchar(255)` | NO | — | Short summary of the action |
| 4 | `description` | `text` | NO | — | Detailed description of the corrective/preventive action |
| 5 | `source_module` | `varchar(50)` | YES | `NULL` | Source module: `incident`, `inspection`, `audit`, `manual` |
| 6 | `source_reference_id` | `bigint` | YES | `NULL` | FK reference to source module's table (incidents.id, inspections.id, audit_findings.id). NULL if source_module='manual'. No hard FK — polymorphic. |
| 7 | `source_type` | `varchar(50)` | YES | `NULL` | Type of action: `corrective`, `preventive` |
| 8 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where action applies |
| 9 | `department_id` | `bigint` | YES | `NULL` | **FK → `departments.id`**. Owning department |
| 10 | `assigned_to` | `bigint` | NO | — | **FK → `users.id`**. Person In Charge (PIC) |
| 11 | `assigned_by` | `bigint` | NO | — | **FK → `users.id`**. User who assigned the action |
| 12 | `assigned_at` | `timestamp` | YES | `NULL` | When action was assigned to PIC |
| 13 | `due_date` | `date` | YES | `NULL` | Deadline for completion. Used for overdue calculation. |
| 14 | `severity_id` | `bigint` | YES | `NULL` | **FK → `severities.id`**. Severity classification (nullable) |
| 15 | `priority_id` | `bigint` | NO | — | **FK → `priorities.id`**. Priority for triage |
| 16 | `status` | `varchar(50)` | NO | `'open'` | Lifecycle state: `open`, `in_progress`, `waiting_verification`, `closed`, `rejected` |
| 17 | `verification_note` | `text` | YES | `NULL` | QHSSE verification note (required on verify_close) |
| 18 | `verified_by` | `bigint` | YES | `NULL` | **FK → `users.id`**. User who verified the action |
| 19 | `verified_at` | `timestamp` | YES | `NULL` | When action was verified |
| 20 | `closed_at` | `timestamp` | YES | `NULL` | When action was closed |
| 21 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 22 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE capa_actions (
    id                  BIGSERIAL       PRIMARY KEY,
    action_number       VARCHAR(50)     NOT NULL UNIQUE,
    title               VARCHAR(255)    NOT NULL,
    description         TEXT            NOT NULL,
    source_module       VARCHAR(50)     NULL,
    source_reference_id BIGINT          NULL,
    source_type         VARCHAR(50)     NULL,
    site_id             BIGINT          NOT NULL REFERENCES sites(id),
    department_id       BIGINT          NULL REFERENCES departments(id),
    assigned_to         BIGINT          NOT NULL REFERENCES users(id),
    assigned_by         BIGINT          NOT NULL REFERENCES users(id),
    assigned_at         TIMESTAMP       NULL,
    due_date            DATE            NULL,
    severity_id         BIGINT          NULL REFERENCES severities(id),
    priority_id         BIGINT          NOT NULL REFERENCES priorities(id),
    status              VARCHAR(50)     NOT NULL DEFAULT 'open',
    verification_note   TEXT            NULL,
    verified_by         BIGINT          NULL REFERENCES users(id),
    verified_at         TIMESTAMP       NULL,
    closed_at           TIMESTAMP       NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT capa_actions_source_module_check CHECK (
        source_module IN ('incident', 'inspection', 'audit', 'manual')
    ),
    CONSTRAINT capa_actions_source_type_check CHECK (
        source_type IS NULL OR source_type IN ('corrective', 'preventive')
    ),
    CONSTRAINT capa_actions_status_check CHECK (
        status IN ('open', 'in_progress', 'waiting_verification', 'closed', 'rejected')
    ),
    CONSTRAINT capa_actions_manual_source_check CHECK (
        (source_module = 'manual' AND source_reference_id IS NULL)
        OR
        (source_module != 'manual' AND source_reference_id IS NOT NULL)
        OR
        (source_module IS NULL AND source_reference_id IS NULL)
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('capa_actions', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('action_number', 50)->unique();
    $table->string('title', 255);
    $table->text('description');
    $table->string('source_module', 50)->nullable();
    $table->bigInteger('source_reference_id')->nullable();
    $table->string('source_type', 50)->nullable();
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('department_id')->nullable()->constrained('departments');
    $table->foreignId('assigned_to')->constrained('users');
    $table->foreignId('assigned_by')->constrained('users');
    $table->timestamp('assigned_at')->nullable();
    $table->date('due_date')->nullable();
    $table->foreignId('severity_id')->nullable()->constrained('severities');
    $table->foreignId('priority_id')->constrained('priorities');
    $table->string('status', 50)->default('open');
    $table->text('verification_note')->nullable();
    $table->foreignId('verified_by')->nullable()->constrained('users');
    $table->timestamp('verified_at')->nullable();
    $table->timestamp('closed_at')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('source_module');
    $table->index('source_reference_id');
    $table->index('site_id');
    $table->index('department_id');
    $table->index('assigned_to');
    $table->index('assigned_by');
    $table->index('status');
    $table->index('due_date');
    $table->index('priority_id');
    $table->index('severity_id');
    $table->index('created_at');

    // Composite index for overdue query optimization
    $table->index(['due_date', 'status']);
});
```

### Design Notes

- **No soft deletes** in Phase 3 — CAPA actions are never hard-deleted; use status management instead.
- **`source_module` + `source_reference_id`** is a polymorphic link — no hard FK constraint on `source_reference_id`. Application layer validates that the referenced record exists in the appropriate table based on `source_module`.
- **`action_number`** is unique and generated at create time (not deferred to submit).
- **`status`** is stored as varchar with CHECK constraint rather than PostgreSQL native enum — simplifies app-level validation.
- **Overdue** is calculated dynamically: `due_date < now() AND status NOT IN ('closed', 'rejected')`. No stored `is_overdue` column — computed in query.
- **`verification_note`** is only set when QHSSE verifies & closes the action.
- **`verified_by` / `verified_at` / `closed_at`** are set together on the `verify_close` transition.

---

## 3. ERD Diagram (ASCII)

```
┌─────────────────────┐         ┌──────────────────────┐         ┌─────────────────────┐
│      sites           │         │     capa_actions      │         │    departments      │
├─────────────────────┤         ├──────────────────────┤         ├─────────────────────┤
│ id          BIGINT PK│◄──┐    │ id            BIGINT PK│──┐    │ id          BIGINT PK │
│ code        VARCHAR   │   │    │ action_number VARCHAR  │  │    │ code        VARCHAR  │
│ name        VARCHAR   │   │    │ title         VARCHAR  │  │    │ name        VARCHAR  │
│ address     TEXT      │   │    │ description   TEXT     │  │    │ site_id     BIGINT FK│──► sites
│ is_active   BOOLEAN   │   │    │ source_module VARCHAR  │  │    │ is_active   BOOLEAN  │
└─────────────────────┘   │    │ source_ref_id BIGINT   │  │    └─────────────────────┘
                          │    │ source_type   VARCHAR  │  │
                          │    │ site_id       BIGINT FK │──┘
                          │    │ department_id BIGINT FK│──────► departments
                          │    │ assigned_to   BIGINT FK │──────► users (PIC)
                          │    │ assigned_by   BIGINT FK │──────► users (assigner)
                          │    │ assigned_at   TIMESTAMP │
                          │    │ due_date      DATE      │
                          │    │ severity_id   BIGINT FK │──────► severities
                          │    │ priority_id   BIGINT FK │──────► priorities
                          │    │ status        VARCHAR   │
                          │    │ verification_note TEXT  │
                          │    │ verified_by   BIGINT FK │──────► users (verifier)
                          │    │ verified_at   TIMESTAMP │
                          │    │ closed_at     TIMESTAMP │
                          │    │ created_at    TIMESTAMP │
                          │    │ updated_at    TIMESTAMP │
                          │    └──────────────────────┘
                          │              │  ▲
                          │              │  │
                          │              ▼  │
                          │    ┌──────────────────────────┐
                          │    │  managed_files            │
                          │    │  (module_name='capa')     │
                          │    │  reference_id=capa_actions│
                          │    │  collection='evidence'    │
                          │    └──────────────────────────┘
                          │              │
                          │              ▼
                          │    ┌──────────────────────────┐
                          │    │  comments                 │
                          │    │  (module_name='capa')     │
                          │    │  reference_id=capa_actions│
                          │    └──────────────────────────┘
                          │              │
                          │              ▼
                          │    ┌──────────────────────────┐
                          │    │  activity_logs           │
                          │    │  (module_name='capa')    │
                          │    └──────────────────────────┘
                          │              │
                          │              ▼
                          │    ┌──────────────────────────┐
                          │    │  audit_logs              │
                          │    │  (module_name='capa')    │
                          │    └──────────────────────────┘
                          │              │
                          │              ▼
                          │    ┌──────────────────────────┐
                          │    │  workflow_instances      │
                          │    │  (module_name='capa')    │
                          │    └─────────┬────────────────┘
                          │              │ 1:N
                          │              ▼
                          │    ┌──────────────────────────┐
                          │    │  workflow_histories      │
                          │    │  (module_name='capa')    │
                          │    └──────────────────────────┘
                          │
                          └── (source_module + source_reference_id polymorphic link)
```

### Cross-Module Source Links (Polymorphic)

```
                    ┌──────────────────────┐
                    │     capa_actions     │
                    │  source_module       │
                    │  source_reference_id │
                    └──────┬───────────────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
     source_module    source_module  source_module
       = 'incident'   = 'inspection'  = 'audit'     = 'manual'
              │            │            │               │
              ▼            ▼            ▼               ▼
     ┌─────────────┐ ┌────────────┐ ┌──────────────┐  (no link)
     │  incidents  │ │inspections │ │audit_findings│  source_ref=NULL
     │  (id)       │ │  (id)      │ │  (id)        │
     └─────────────┘ └────────────┘ └──────────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `capa_actions` | `site_id` | 1:N | RESTRICT |
| `departments` | `capa_actions` | `department_id` | 1:N | SET NULL |
| `users` | `capa_actions` | `assigned_to` | 1:N | RESTRICT |
| `users` | `capa_actions` | `assigned_by` | 1:N | RESTRICT |
| `severities` | `capa_actions` | `severity_id` | 1:N | SET NULL |
| `priorities` | `capa_actions` | `priority_id` | 1:N | RESTRICT |
| `users` | `capa_actions` | `verified_by` | 1:N | SET NULL |
| `incidents` | `capa_actions` | `source_reference_id` (polymorphic) | 1:N | Application-level |
| `inspections` | `capa_actions` | `source_reference_id` (polymorphic) | 1:N | Application-level |
| `audit_findings` | `capa_actions` | `source_reference_id` (polymorphic) | 1:N | Application-level |

---

## 4. Index Specifications

### `capa_actions` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `capa_actions_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `capa_actions_action_number_unique` | `action_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `capa_actions_source_module_index` | `source_module` | btree | Filter by source module |
| 4 | `capa_actions_source_reference_id_index` | `source_reference_id` | btree | Lookup by source reference |
| 5 | `capa_actions_site_id_index` | `site_id` | btree | Filter by site |
| 6 | `capa_actions_department_id_index` | `department_id` | btree | Filter by department |
| 7 | `capa_actions_assigned_to_index` | `assigned_to` | btree | Find actions assigned to user |
| 8 | `capa_actions_assigned_by_index` | `assigned_by` | btree | Find actions assigned by user |
| 9 | `capa_actions_status_index` | `status` | btree | Filter/list by workflow status |
| 10 | `capa_actions_due_date_index` | `due_date` | btree | Sort/filter by due date |
| 11 | `capa_actions_priority_id_index` | `priority_id` | btree | Filter by priority |
| 12 | `capa_actions_severity_id_index` | `severity_id` | btree | Filter by severity |
| 13 | `capa_actions_created_at_index` | `created_at` | btree | Sort by creation date |
| 14 | `capa_actions_due_date_status_index` | `due_date, status` | btree (composite) | Overdue query optimization |

### Overdue Query Pattern

```sql
-- Efficient overdue query using composite index
SELECT * FROM capa_actions
WHERE due_date IS NOT NULL
  AND due_date < CURRENT_DATE
  AND status NOT IN ('closed', 'rejected')
ORDER BY due_date ASC;
```

```php
// Laravel equivalent
CapaAction::whereNotNull('due_date')
    ->where('due_date', '<', now())
    ->whereNotIn('status', ['closed', 'rejected'])
    ->orderBy('due_date', 'asc');
```

---

## 5. Shared Relations

The CAPA module does **not** duplicate file, comment, log, or workflow tables. All cross-cutting platform services use the **polymorphic `module_name + reference_id`** pattern.

- `module_name = 'capa'`
- `reference_id = capa_actions.id`

### 5.1 Managed Files (`managed_files`)

| Column | Value |
|---|---|
| `module_name` | `'capa'` |
| `reference_id` | `capa_actions.id` |
| `collection` | `'evidence'` |
| `uploaded_by` | `users.id` (FK) |

```
capa_actions.id ──► managed_files.reference_id
                      managed_files.module_name = 'capa'
```

**Usage**: `CapaAction::files()` returns all files where `module_name='capa'` AND `reference_id=$this->id`.

### 5.2 Comments (`comments`)

| Column | Value |
|---|---|
| `module_name` | `'capa'` |
| `reference_id` | `capa_actions.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` — internal-only (QHSSE) vs visible to PIC |

### 5.3 Activity Logs (`activity_logs`)

| Column | Value |
|---|---|
| `module_name` | `'capa'` |
| `reference_id` | `capa_actions.id` |
| `event` | `'created'`, `'started'`, `'submitted_verification'`, `'verified_closed'`, `'rejected'`, `'restarted'` |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON |

### 5.4 Audit Logs (`audit_logs`)

| Column | Value |
|---|---|
| `module_name` | `'capa'` |
| `reference_id` | `capa_actions.id` |
| `auditable_type` | `'CapaAction'` |
| `auditable_id` | `capa_actions.id` |
| `old_values` | JSON |
| `new_values` | JSON |
| `actor_id` | `users.id` (FK) |

### 5.5 Workflow Instances (`workflow_instances`)

| Column | Value |
|---|---|
| `module_name` | `'capa'` |
| `reference_id` | `capa_actions.id` |
| `workflow_definition_id` | FK to `workflow_definitions.id` (CAPA_WORKFLOW) |
| `current_status` | Mirrors `capa_actions.status` |
| `started_by` | `users.id` (FK) |
| `completed_at` | nullable, set when closed or rejected |

### 5.6 Workflow Histories (`workflow_histories`)

| Column | Value |
|---|---|
| `module_name` | `'capa'` |
| `reference_id` | `capa_actions.id` |
| `from_status` | Previous status |
| `to_status` | New status |
| `action_key` | `'start'`, `'submit_verification'`, `'verify_close'`, `'reject'`, `'restart'` |
| `actor_id` | `users.id` (FK) |
| `reason` | Required for `reject` and `verify_close` |

### Shared Relations Summary

```
                          ┌──────────────┐
                          │  capa_actions │
                          │  (id: PK)    │
                          └──────┬───────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='capa'  │            │
              reference_id=capa_actions.id   │
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │  comments   │  │ activity_logs  │
    │  (evidence)       │  │ (discussion)│  │  (timeline)    │
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

    All linked via: module_name='capa' AND reference_id=capa_actions.id
    No hard FKs — application-layer validated polymorphic relation.

    Cross-module source link:
    capa_actions.source_module + source_reference_id
    → polymorphic link to incidents / inspections / audit_findings
```

---

## 6. Migration File Naming Convention

### Convention

Migrations follow Laravel's standard naming pattern with module-prefixed descriptions:

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

| Segment | Description |
|---|---|
| `YYYY_MM_DD_HHMMSS` | Timestamp (auto-generated by `php artisan make:migration`) |
| `verb` | `create` for new tables, `add`, `update`, `drop` for modifications |
| `table_name` | Target table name (e.g., `capa_actions`) |

### Expected Migration File

```
database/migrations/2026_07_15_000000_create_capa_actions_table.php
```

### Eloquent Model

File: `app/Models/Modules/Capa/CapaAction.php`

```php
<?php

namespace App\Models\Modules\Capa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CapaAction extends Model
{
    protected $table = 'capa_actions';

    protected $fillable = [
        'action_number',
        'title',
        'description',
        'source_module',
        'source_reference_id',
        'source_type',
        'site_id',
        'department_id',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'due_date',
        'severity_id',
        'priority_id',
        'status',
        'verification_note',
        'verified_by',
        'verified_at',
        'closed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'date',
        'verified_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function severity(): BelongsTo
    {
        return $this->belongsTo(Severity::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['closed', 'rejected']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['closed', 'rejected']);
    }

    // Helpers
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date < now()
            && !in_array($this->status, ['closed', 'rejected']);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['open', 'in_progress', 'rejected']);
    }
}
```

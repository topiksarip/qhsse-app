# Data Model — Incident / Accident / Near Miss Reporting

> Phase 1 schema for the Incident Reporting module.  
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs, workflow).

---

## 1. Table of Contents

1. [Main Table: `incidents`](#2-main-table-incidents)
2. [Pivot Table: `incident_involved_persons`](#3-pivot-table-incident_involved_persons)
3. [ERD Diagram (ASCII)](#4-erd-diagram-ascii)
4. [Index Specifications](#5-index-specifications)
5. [Shared Relations](#6-shared-relations)
6. [Migration File Naming Convention](#7-migration-file-naming-convention)

---

## 2. Main Table: `incidents`

Stores the core incident record — accident, near miss, unsafe act/condition, environmental spill, or security breach.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `incident_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at submit (format: `INC-{YYYY}-{0001}`), not at draft |
| 3 | `title` | `varchar(255)` | NO | — | Short summary of the incident |
| 4 | `category` | `varchar(50)` | NO | — | **Check constraint** enum: `accident`, `incident`, `near_miss`, `unsafe_act`, `unsafe_condition`, `environmental_spill`, `security_breach` |
| 5 | `occurred_at` | `timestamp` | NO | — | When the incident actually happened (not when reported) |
| 6 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where incident occurred |
| 7 | `area_id` | `bigint` | YES | `NULL` | **FK → `sites.id`** (areas table). Specific area within site |
| 8 | `department_id` | `bigint` | YES | `NULL` | **FK → `departments.id`**. Owning department |
| 9 | `reporter_id` | `bigint` | NO | — | **FK → `users.id`**. User who created the report |
| 10 | `severity_id` | `bigint` | NO | — | **FK → `severities.id`**. Severity classification |
| 11 | `priority_id` | `bigint` | NO | — | **FK → `priorities.id`**. Priority for triage |
| 12 | `description` | `text` | NO | — | Detailed narrative of what happened |
| 13 | `immediate_action` | `text` | YES | `NULL` | Immediate corrective action taken on-site |
| 14 | `status` | `varchar(50)` | NO | `'draft'` | Lifecycle state: `draft`, `submitted`, `under_review`, `in_progress`, `rejected`, `cancelled`, `closed` |
| 15 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 16 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE incidents (
    id                  BIGSERIAL       PRIMARY KEY,
    incident_number     VARCHAR(50)     NOT NULL UNIQUE,
    title               VARCHAR(255)    NOT NULL,
    category            VARCHAR(50)     NOT NULL,
    occurred_at         TIMESTAMP       NOT NULL,
    site_id             BIGINT          NOT NULL REFERENCES sites(id),
    area_id             BIGINT          NULL REFERENCES sites(id),
    department_id       BIGINT          NULL REFERENCES departments(id),
    reporter_id         BIGINT          NOT NULL REFERENCES users(id),
    severity_id         BIGINT          NOT NULL REFERENCES severities(id),
    priority_id         BIGINT          NOT NULL REFERENCES priorities(id),
    description         TEXT            NOT NULL,
    immediate_action    TEXT            NULL,
    status              VARCHAR(50)     NOT NULL DEFAULT 'draft',
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT incidents_category_check CHECK (
        category IN (
            'accident',
            'incident',
            'near_miss',
            'unsafe_act',
            'unsafe_condition',
            'environmental_spill',
            'security_breach'
        )
    ),

    CONSTRAINT incidents_status_check CHECK (
        status IN (
            'draft',
            'submitted',
            'under_review',
            'in_progress',
            'rejected',
            'cancelled',
            'closed'
        )
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('incidents', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('incident_number', 50)->unique();
    $table->string('title', 255);
    $table->string('category', 50);
    $table->timestamp('occurred_at');
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('area_id')->nullable()->constrained('sites');
    $table->foreignId('department_id')->nullable()->constrained('departments');
    $table->foreignId('reporter_id')->constrained('users');
    $table->foreignId('severity_id')->constrained('severities');
    $table->foreignId('priority_id')->constrained('priorities');
    $table->text('description');
    $table->text('immediate_action')->nullable();
    $table->string('status', 50)->default('draft');
    $table->timestamps();

    // Check constraint for category enum
    $table->check("category IN ('accident','incident','near_miss','unsafe_act','unsafe_condition','environmental_spill','security_breach')", 'incidents_category_check');

    // Check constraint for status enum
    $table->check("status IN ('draft','submitted','under_review','in_progress','rejected','cancelled','closed')", 'incidents_status_check');
});
```

### Design Notes

- **No soft deletes** (`deleted_at`) in Phase 1 — incidents are never hard-deleted; use `status = 'cancelled'` instead.
- **No `created_by` / `updated_by`** — the `reporter_id` column already tracks the reporting user; Laravel's `timestamps()` handles `created_at`/`updated_at`.
- **`incident_number`** is unique but generated at **submit**, not at draft creation. Drafts have `status = 'draft'` and `incident_number` is assigned via the Numbering Service (`INC-{YYYY}-{0001}`).
- **`category`** is stored as a `varchar` with a CHECK constraint rather than a PostgreSQL native enum — this simplifies application-level validation and future category additions without migration.
- **`area_id`** references `sites` (areas are stored as child sites or in a separate `areas` table — here constrained to `sites` per spec).

---

## 3. Pivot Table: `incident_involved_persons`

Many-to-many relationship between incidents and employees who were involved in or witnessed the incident.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `incident_id` | `bigint` | NO | — | **FK → `incidents.id`**, `ON DELETE CASCADE` |
| 3 | `employee_id` | `bigint` | NO | — | **FK → `employees.id`** |
| 4 | `note` | `varchar(255)` | YES | `NULL` | Optional note about the person's role (witness, injured party, etc.) |
| 5 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 6 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE incident_involved_persons (
    id              BIGSERIAL       PRIMARY KEY,
    incident_id     BIGINT          NOT NULL REFERENCES incidents(id) ON DELETE CASCADE,
    employee_id     BIGINT          NOT NULL REFERENCES employees(id),
    note            VARCHAR(255)    NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT incident_involved_unique UNIQUE (incident_id, employee_id)
);
```

### Laravel Migration (Reference)

```php
Schema::create('incident_involved_persons', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
    $table->foreignId('employee_id')->constrained('employees');
    $table->string('note', 255)->nullable();
    $table->timestamps();

    // Prevent duplicate person per incident
    $table->unique(['incident_id', 'employee_id']);
});
```

### Design Notes

- **Cascade delete** on `incident_id` — when an incident is deleted, all involved persons go with it.
- **Unique composite** (`incident_id`, `employee_id`) prevents duplicate entries for the same person on the same incident.
- **`note`** is free-text — used to describe involvement (e.g., "witness", "injured", "first responder").
- No soft deletes — keeping the pivot simple for Phase 1.

---

## 4. ERD Diagram (ASCII)

```
┌─────────────────────┐         ┌──────────────────────┐         ┌─────────────────────┐
│      sites           │         │      incidents        │         │    departments      │
├─────────────────────┤         ├──────────────────────┤         ├─────────────────────┤
│ id          BIGINT  PK│◄──┐    │ id            BIGINT PK│──┐    │ id          BIGINT PK │
│ code        VARCHAR   │   │    │ incident_number VARCHAR │  │    │ code        VARCHAR  │
│ name        VARCHAR   │   │    │ title         VARCHAR   │  │    │ name        VARCHAR  │
│ address     TEXT      │   │    │ category      VARCHAR   │  │    │ site_id     BIGINT FK│──► sites
│ is_active   BOOLEAN   │   │    │ occurred_at   TIMESTAMP │  │    │ is_active   BOOLEAN  │
└─────────────────────┘   │    │ site_id       BIGINT FK │──┘    └─────────────────────┘
                          │    │ area_id       BIGINT FK │──┐
                          │    │ department_id BIGINT FK │──┼────► departments
                          │    │ reporter_id   BIGINT FK │──┼────► users
                          │    │ severity_id   BIGINT FK │──┼────► severities
                          │    │ priority_id   BIGINT FK │──┼────► priorities
                          │    │ description   TEXT       │  │
                          │    │ immediate_action TEXT    │  │
                          │    │ status        VARCHAR   │  │
                          │    │ created_at    TIMESTAMP │  │
                          │    │ updated_at    TIMESTAMP │  │
                          │    └──────────────────────┘  │
                          │              │  ▲             │
                          │              │  │             │
                          │              ▼  │             │
                          │    ┌──────────────────────────┤
                          │    │ incident_involved_persons│
                          │    ├──────────────────────────┤
                          │    │ id           BIGINT PK   │
                          │    │ incident_id  BIGINT FK ──┘ (cascade)
                          │    │ employee_id  BIGINT FK ──► employees
                          │    │ note         VARCHAR     │
                          │    │ created_at   TIMESTAMP   │
                          │    │ updated_at   TIMESTAMP   │
                          │    └──────────────────────────┘
                          │              │
                          │              │  employees
                          │              ▼
                          │    ┌─────────────────────┐
                          │    │     employees        │
                          │    ├─────────────────────┤
                          │    │ id          BIGINT PK │
                          │    │ company_id  BIGINT FK│──► companies
                          │    │ name        VARCHAR  │
                          │    │ email       VARCHAR  │
                          │    │ phone       VARCHAR  │
                          │    │ site_id     BIGINT FK│──► sites
                          │    │ department_id BIGINT│──► departments
                          │    │ position_id BIGINT  │──► positions
                          │    │ is_active   BOOLEAN  │
                          │    └─────────────────────┘
                          │
                          └── (area_id also references sites)
```

### Relationship Summary

```
                        ┌──────────────┐
                        │   users      │
                        │  (reporter)  │
                        └──────┬───────┘
                               │ 1
                               │
                          ┌────▼────┐          ┌──────────────┐
                  N:1     │incidents │     1:N  │involved_persons│
                ┌────────►│(main)    │◄─────────│  (pivot)     │
                │         └──┬─┬─┬───┘          └──────┬───────┘
                │            │ │ │                     │
                │            │ │ │                N:1  │
                │     ┌──────┘ │ └──────┐             │
                │     │ N:1    │ N:1   │ N:1          │ N:1
                │  ┌──▼───┐ ┌─▼────┐ ┌─▼─────┐  ┌─────▼─────┐
                │  │sites │ │users │ │sever- │  │employees  │
                │  │      │ │      │ │ities  │  │           │
                │  └──┬───┘ └──────┘ └───────┘  └───────────┘
                │     │
                │  ┌──▼───┐
                │  │areas │ (also sites table)
                │  └──────┘
                │
          ┌─────▼─────┐
          │departments│
          └───────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `incidents` | `site_id` | 1:N | RESTRICT (default) |
| `sites` | `incidents` | `area_id` | 1:N | SET NULL |
| `departments` | `incidents` | `department_id` | 1:N | SET NULL |
| `users` | `incidents` | `reporter_id` | 1:N | RESTRICT (default) |
| `severities` | `incidents` | `severity_id` | 1:N | RESTRICT (default) |
| `priorities` | `incidents` | `priority_id` | 1:N | RESTRICT (default) |
| `incidents` | `incident_involved_persons` | `incident_id` | 1:N | CASCADE |
| `employees` | `incident_involved_persons` | `employee_id` | 1:N | RESTRICT (default) |

---

## 5. Index Specifications

### `incidents` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `incidents_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `incidents_incident_number_unique` | `incident_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `incidents_site_id_index` | `site_id` | btree | Filter incidents by site |
| 4 | `incidents_area_id_index` | `area_id` | btree | Filter incidents by area |
| 5 | `incidents_department_id_index` | `department_id` | btree | Filter incidents by department |
| 6 | `incidents_reporter_id_index` | `reporter_id` | btree | List incidents by reporter |
| 7 | `incidents_severity_id_index` | `severity_id` | btree | Filter by severity level |
| 8 | `incidents_priority_id_index` | `priority_id` | btree | Filter by priority |
| 9 | `incidents_status_index` | `status` | btree | Filter/list by workflow status |
| 10 | `incidents_category_index` | `category` | btree | Filter by incident type |
| 11 | `incidents_occurred_at_index` | `occurred_at` | btree | Sort/filter by date range |
| 12 | `incidents_created_at_index` | `created_at` | btree | Sort by creation date |

### `incident_involved_persons` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `incident_involved_persons_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `incident_involved_persons_incident_id_index` | `incident_id` | btree | Find all persons for an incident |
| 3 | `incident_involved_persons_employee_id_index` | `employee_id` | btree | Find all incidents for an employee |
| 4 | `incident_involved_persons_incident_id_employee_id_unique` | `incident_id, employee_id` | UNIQUE (btree) | Prevent duplicate person per incident |

### Laravel Migration Indexes

```php
// incidents table
$table->index('site_id');
$table->index('area_id');
$table->index('department_id');
$table->index('reporter_id');
$table->index('severity_id');
$table->index('priority_id');
$table->index('status');
$table->index('category');
$table->index('occurred_at');
$table->index('created_at');

// incident_involved_persons table
$table->index('incident_id');
$table->index('employee_id');
$table->unique(['incident_id', 'employee_id']);
```

---

## 6. Shared Relations

The Incident Reporting module does **not** duplicate file, comment, log, or workflow tables. Instead, all cross-cutting platform services use the **polymorphic `module_name + reference_id`** pattern. For this module:

- `module_name = 'incident'`
- `reference_id = incidents.id`

This means every shared table row links back to an incident record without a hard foreign key — the application layer validates the relationship.

### 6.1 Managed Files (`managed_files`)

File attachments (evidence, photos, documents) for an incident.

| Column | Value |
|---|---|
| `module_name` | `'incident'` |
| `reference_id` | `incidents.id` |
| `collection` | `'evidence'`, `'photos'`, `'documents'` (configurable) |
| `uploaded_by` | `users.id` (FK) |

```
incidents.id ──► managed_files.reference_id
                    managed_files.module_name = 'incident'
```

**Usage**: `Incident::files()` returns all files where `module_name='incident'` AND `reference_id=$this->id`.

### 6.2 Comments (`comments`)

Threaded comments / discussion on an incident.

| Column | Value |
|---|---|
| `module_name` | `'incident'` |
| `reference_id` | `incidents.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` — internal-only vs visible to reporters |

```
incidents.id ──► comments.reference_id
                    comments.module_name = 'incident'
```

**Usage**: `Incident::comments()` returns all comments where `module_name='incident'` AND `reference_id=$this->id`.

### 6.3 Activity Logs (`activity_logs`)

Timeline of actions performed on an incident (created, submitted, reviewed, etc.).

| Column | Value |
|---|---|
| `module_name` | `'incident'` |
| `reference_id` | `incidents.id` |
| `event` | `'created'`, `'submitted'`, `'reviewed'`, `'approved'`, `'rejected'`, `'closed'`, etc. |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot, metadata) |

```
incidents.id ──► activity_logs.reference_id
                    activity_logs.module_name = 'incident'
```

**Usage**: `Incident::activities()` returns all activity log entries for this incident.

### 6.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes on incident records.

| Column | Value |
|---|---|
| `module_name` | `'incident'` |
| `reference_id` | `incidents.id` |
| `auditable_type` | `'Incident'` (or fully-qualified model class) |
| `auditable_id` | `incidents.id` (mirrors `reference_id` for ORM compatibility) |
| `old_values` | JSON (previous field values) |
| `new_values` | JSON (new field values) |
| `actor_id` | `users.id` (FK) |

```
incidents.id ──► audit_logs.reference_id
                    audit_logs.module_name = 'incident'
                    audit_logs.auditable_id = incidents.id
```

**Usage**: `Incident::audits()` returns all audit log entries for this incident.

### 6.5 Workflow Instances (`workflow_instances`)

Each incident that enters a workflow gets a workflow instance tracking its progression.

| Column | Value |
|---|---|
| `module_name` | `'incident'` |
| `reference_id` | `incidents.id` |
| `workflow_definition_id` | FK to `workflow_definitions.id` |
| `current_status` | Mirrors `incidents.status` |
| `started_by` | `users.id` (FK) |
| `completed_at` | nullable, set when incident is closed/cancelled |

### 6.6 Workflow Histories (`workflow_histories`)

Every workflow transition (status change) for an incident is logged here.

| Column | Value |
|---|---|
| `module_name` | `'incident'` |
| `reference_id` | `incidents.id` |
| `workflow_instance_id` | FK to `workflow_instances.id` |
| `from_status` | Previous status (e.g., `'draft'`) |
| `to_status` | New status (e.g., `'submitted'`) |
| `action_key` | `'submit'`, `'review'`, `'approve'`, `'reject'`, `'close'`, etc. |
| `actor_id` | `users.id` (FK) |

### Shared Relations Summary

```
                          ┌──────────────┐
                          │  incidents   │
                          │  (id: PK)    │
                          └──────┬───────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='incident'          │
              reference_id=incidents.id       │
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

    All linked via: module_name='incident' AND reference_id=incidents.id
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
| `table_name` | Snake-cased table name |
| `_table.php` | Suffix for table-level migrations |

### Incident Module Migration Files

| Migration File | Description |
|---|---|
| `YYYY_MM_DD_HHMMSS_create_incidents_table.php` | Creates the `incidents` main table |
| `YYYY_MM_DD_HHMMSS_create_incident_involved_persons_table.php` | Creates the `incident_involved_persons` pivot table |

### Examples (with realistic timestamps)

```
database/migrations/
├── 2026_07_09_083957_create_sites_table.php              ← existing (core)
├── 2026_07_09_083960_create_departments_table.php        ← existing (core)
├── 2026_07_09_094930_create_severities_table.php          ← existing (core)
├── 2026_07_09_094930_create_priorities_table.php          ← existing (core)
├── 2026_07_09_104000_create_comments_table.php           ← existing (shared)
├── 2026_07_09_104001_create_activity_logs_table.php      ← existing (shared)
├── 2026_07_09_103000_create_audit_logs_table.php         ← existing (shared)
├── 2026_07_09_095858_create_managed_files_table.php      ← existing (shared)
├── 2026_07_09_102002_create_workflow_instances_table.php ← existing (shared)
├── 2026_07_09_102003_create_workflow_histories_table.php ← existing (shared)
│
├── 2026_07_11_100000_create_incidents_table.php                  ← NEW (this module)
├── 2026_07_11_100001_create_incident_involved_persons_table.php  ← NEW (this module)
```

### Generation Commands

```bash
# Create main incidents table
php artisan make:migration create_incidents_table

# Create pivot table for involved persons
php artisan make:migration create_incident_involved_persons_table
```

### Naming Rules

1. **Table names** are snake_case, plural for main tables (`incidents`), descriptive for pivots (`incident_involved_persons`).
2. **Migration class names** are PascalCase: `CreateIncidentsTable`, `CreateIncidentInvolvedPersonsTable`.
3. **Order matters** — `incidents` must exist before `incident_involved_persons` (pivot FK). Shared tables (`sites`, `departments`, `users`, `severities`, `priorities`, `employees`) must already exist.
4. **Down migration** must drop in reverse order: pivot first, then main table.

```php
// down() method
public function down(): void
{
    Schema::dropIfExists('incident_involved_persons');
    Schema::dropIfExists('incidents');
}
```

---

## 8. Eloquent Model Relationships (Reference)

### `Incident` Model

```php
class Incident extends Model
{
    protected $table = 'incidents';

    protected $fillable = [
        'incident_number',
        'title',
        'category',
        'occurred_at',
        'site_id',
        'area_id',
        'department_id',
        'reporter_id',
        'severity_id',
        'priority_id',
        'description',
        'immediate_action',
        'status',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    // --- BelongsTo relations ---
    public function site(): BelongsTo      { return $this->belongsTo(Site::class); }
    public function area(): BelongsTo      { return $this->belongsTo(Site::class, 'area_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function reporter(): BelongsTo  { return $this->belongsTo(User::class, 'reporter_id'); }
    public function severity(): BelongsTo  { return $this->belongsTo(Severity::class); }
    public function priority(): BelongsTo  { return $this->belongsTo(Priority::class); }

    // --- Many-to-many ---
    public function involvedPersons(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'incident_involved_persons')
            ->withPivot(['note'])
            ->withTimestamps();
    }

    // --- Polymorphic shared relations (module_name + reference_id) ---
    public function files(): MorphMany
    {
        return $this->morphMany(ManagedFile::class, 'reference', 'module_name', 'reference_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'reference', 'module_name', 'reference_id');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'reference', 'module_name', 'reference_id');
    }

    public function audits(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'reference', 'module_name', 'reference_id');
    }

    public function workflowInstance(): MorphOne
    {
        return $this->morphOne(WorkflowInstance::class, 'reference', 'module_name', 'reference_id');
    }
}
```

### `IncidentInvolvedPerson` Model (Pivot)

```php
class IncidentInvolvedPerson extends Pivot
{
    protected $table = 'incident_involved_persons';

    protected $fillable = [
        'incident_id',
        'employee_id',
        'note',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
```

---

## 9. Phase 1 Scope Exclusions

The following are intentionally **excluded** from Phase 1 per the task spec:

| Excluded Field / Feature | Reason |
|---|---|
| `created_by` | `reporter_id` already tracks the reporting user |
| `updated_by` | Not needed for Phase 1 simplicity |
| `deleted_at` (soft deletes) | Use `status = 'cancelled'` instead of deleting |
| `company_id` | Derived from reporter/employee relationship; not stored directly |
| `risk_level_id` | Phase 2 — investigation/RCA module will own risk assessment |
| `assigned_to` / `reviewer_id` / `approver_id` / `verifier_id` | Handled via `workflow_instances` + `workflow_histories` at the platform level |
| `due_date` | Phase 2 — SLA tracking deferred |

---

*Last updated: 2026-07-11*

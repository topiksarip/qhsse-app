# Data Model — Emergency Preparedness

> Phase 15 schema for the Emergency Preparedness module.
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs).

---

## 1. Table of Contents

1. [Table: `emergency_plans`](#2-table-emergency_plans)
2. [Table: `emergency_drills`](#3-table-emergency_drills)
3. [Table: `emergency_contacts`](#4-table-emergency_contacts)
4. [ERD Diagram (ASCII)](#5-erd-diagram-ascii)
5. [Index Specifications](#6-index-specifications)
6. [Shared Relations](#7-shared-relations)
7. [Migration File Naming Convention](#8-migration-file-naming-convention)

---

## 2. Table: `emergency_plans`

Stores the core emergency plan — fire, medical, spill, evacuation, natural disaster, security, or other.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `plan_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `EMG-{YYYY}-{0001}`) via NumberingService |
| 3 | `name` | `varchar(255)` | NO | — | Name/title of the emergency plan |
| 4 | `type` | `varchar(50)` | NO | — | **Check constraint** enum: `fire`, `medical`, `spill`, `evacuation`, `natural_disaster`, `security`, `other` |
| 5 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where the plan applies |
| 6 | `description` | `text` | NO | — | Detailed description of the emergency plan |
| 7 | `response_procedure` | `text` | NO | — | Step-by-step response procedure |
| 8 | `escalation_procedure` | `text` | NO | — | Escalation procedure (who to contact, when) |
| 9 | `contact_person_id` | `bigint` | NO | — | **FK → `users.id`**. Primary contact person for this plan |
| 10 | `emergency_contacts` | `json` | YES | `NULL` | Additional contacts specific to this plan (JSON array of {name, role, phone}) |
| 11 | `equipment_needed` | `text` | YES | `NULL` | List of equipment needed for this emergency response |
| 12 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 13 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE emergency_plans (
    id                    BIGSERIAL       PRIMARY KEY,
    plan_number           VARCHAR(50)     NOT NULL UNIQUE,
    name                  VARCHAR(255)    NOT NULL,
    type                  VARCHAR(50)     NOT NULL,
    site_id               BIGINT          NOT NULL REFERENCES sites(id),
    description           TEXT            NOT NULL,
    response_procedure    TEXT            NOT NULL,
    escalation_procedure  TEXT            NOT NULL,
    contact_person_id     BIGINT          NOT NULL REFERENCES users(id),
    emergency_contacts    JSON            NULL,
    equipment_needed      TEXT            NULL,
    created_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT emergency_plans_type_check CHECK (
        type IN ('fire', 'medical', 'spill', 'evacuation', 'natural_disaster', 'security', 'other')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('emergency_plans', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('plan_number', 50)->unique();
    $table->string('name', 255);
    $table->string('type', 50);
    $table->foreignId('site_id')->constrained('sites');
    $table->text('description');
    $table->text('response_procedure');
    $table->text('escalation_procedure');
    $table->foreignId('contact_person_id')->constrained('users');
    $table->json('emergency_contacts')->nullable();
    $table->text('equipment_needed')->nullable();
    $table->timestamps();

    $table->check("type IN ('fire','medical','spill','evacuation','natural_disaster','security','other')", 'emergency_plans_type_check');
});
```

### Design Notes

- **No soft deletes** (`deleted_at`) — records are managed via status on drills, not plans. If delete is needed, use Laravel soft deletes with `deleted_at` column added later.
- **`plan_number`** is unique and generated at **create** time via `NumberingService::generate('emergency', $actor, ...)`.
- **`type`** is stored as `varchar` with CHECK constraint.
- **`emergency_contacts`** JSON allows storing additional plan-specific contacts without creating separate records. The primary contact directory is in the `emergency_contacts` table.
- **`equipment_needed`** is free text — future enhancement could link to asset module.

---

## 3. Table: `emergency_drills`

Stores emergency drill records — scheduled drills and their execution results.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `drill_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `EMG-{YYYY}-{0001}`) via NumberingService |
| 3 | `emergency_plan_id` | `bigint` | NO | — | **FK → `emergency_plans.id`**. The plan this drill is based on |
| 4 | `scheduled_date` | `date` | NO | — | Date the drill is scheduled for |
| 5 | `executed_date` | `date` | YES | `NULL` | Date the drill was actually executed. NULL until execution |
| 6 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where drill is conducted |
| 7 | `participants_count` | `integer` | YES | `NULL` | Number of participants. Set during execution |
| 8 | `observer_id` | `bigint` | NO | — | **FK → `users.id`**. User who observed/evaluated the drill |
| 9 | `result` | `varchar(50)` | YES | `NULL` | **Check constraint** enum: `pass`, `fail`, `needs_improvement`. Set during execution |
| 10 | `findings` | `text` | YES | `NULL` | Observations/findings from the drill |
| 11 | `recommendations` | `text` | YES | `NULL` | Recommendations for improvement |
| 12 | `status` | `varchar(50)` | NO | `'scheduled'` | Lifecycle state: `scheduled`, `executed` |
| 13 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 14 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE emergency_drills (
    id                    BIGSERIAL       PRIMARY KEY,
    drill_number          VARCHAR(50)     NOT NULL UNIQUE,
    emergency_plan_id     BIGINT          NOT NULL REFERENCES emergency_plans(id),
    scheduled_date        DATE            NOT NULL,
    executed_date         DATE            NULL,
    site_id               BIGINT          NOT NULL REFERENCES sites(id),
    participants_count    INTEGER         NULL,
    observer_id           BIGINT          NOT NULL REFERENCES users(id),
    result                VARCHAR(50)     NULL,
    findings              TEXT            NULL,
    recommendations       TEXT            NULL,
    status                VARCHAR(50)     NOT NULL DEFAULT 'scheduled',
    created_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT emergency_drills_result_check CHECK (
        result IS NULL OR result IN ('pass', 'fail', 'needs_improvement')
    ),
    CONSTRAINT emergency_drills_status_check CHECK (
        status IN ('scheduled', 'executed')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('emergency_drills', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('drill_number', 50)->unique();
    $table->foreignId('emergency_plan_id')->constrained('emergency_plans');
    $table->date('scheduled_date');
    $table->date('executed_date')->nullable();
    $table->foreignId('site_id')->constrained('sites');
    $table->integer('participants_count')->nullable();
    $table->foreignId('observer_id')->constrained('users');
    $table->string('result', 50)->nullable();
    $table->text('findings')->nullable();
    $table->text('recommendations')->nullable();
    $table->string('status', 50)->default('scheduled');
    $table->timestamps();

    $table->check("result IS NULL OR result IN ('pass','fail','needs_improvement')", 'emergency_drills_result_check');
    $table->check("status IN ('scheduled','executed')", 'emergency_drills_status_check');
});
```

### Design Notes

- **`drill_number`** shares the same numbering sequence as `emergency_plans.plan_number` — both use `module_name='emergency'` in `NumberingService`.
- **`result`** is nullable until execution. CHECK constraint allows NULL or valid enum values.
- **`status`** defaults to `scheduled` on create. Only transitions to `executed` via the execute action.
- **`executed_date`** is NULL until the drill is executed.
- **`participants_count`** is NULL until execution.
- **`observer_id`** is required at creation — the user responsible for evaluating the drill.

---

## 4. Table: `emergency_contacts`

Stores emergency contact directory — simple CRUD, scoped by site.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `name` | `varchar(255)` | NO | — | Contact name |
| 3 | `role` | `varchar(255)` | NO | — | Role/title (e.g., "Fire Warden", "First Aider", "Site Security") |
| 4 | `phone` | `varchar(50)` | NO | — | Phone number |
| 5 | `email` | `varchar(255)` | YES | `NULL` | Email address (optional) |
| 6 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site this contact belongs to |
| 7 | `is_active` | `boolean` | NO | `true` | Active status. Inactive contacts are hidden from default lists |
| 8 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 9 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE emergency_contacts (
    id            BIGSERIAL       PRIMARY KEY,
    name          VARCHAR(255)    NOT NULL,
    role          VARCHAR(255)    NOT NULL,
    phone         VARCHAR(50)     NOT NULL,
    email         VARCHAR(255)    NULL,
    site_id       BIGINT          NOT NULL REFERENCES sites(id),
    is_active     BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

### Laravel Migration (Reference)

```php
Schema::create('emergency_contacts', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('name', 255);
    $table->string('role', 255);
    $table->string('phone', 50);
    $table->string('email', 255)->nullable();
    $table->foreignId('site_id')->constrained('sites');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### Design Notes

- **Simple CRUD** — no workflow, no numbering, no status transitions.
- **`is_active`** allows soft-deactivation without losing data.
- **`phone`** is stored as varchar to accommodate international formats (e.g., `+62-812-3456-7890`).
- Contacts are scoped by `site_id` — each site manages its own contact directory.

---

## 5. ERD Diagram (ASCII)

```
┌─────────────────────┐         ┌──────────────────────────────┐         ┌─────────────────────┐
│      sites           │         │      emergency_plans          │         │      users           │
├─────────────────────┤         ├──────────────────────────────┤         ├─────────────────────┤
│ id          BIGINT  PK│◄──┐   │ id               BIGINT PK   │──┐    │ id          BIGINT PK│
│ code        VARCHAR   │   │   │ plan_number      VARCHAR(50)  │  │    │ name        VARCHAR  │
│ name        VARCHAR   │   │   │ name             VARCHAR(255) │  │    │ email       VARCHAR  │
│ address     TEXT      │   │   │ type             VARCHAR(50)  │  │    └─────────────────────┘
│ is_active   BOOLEAN   │   │   │ site_id          BIGINT FK ───┘──┘         ▲  ▲
└─────────────────────┘   │   │ description      TEXT         │             │  │
                          │   │ response_procedure TEXT      │             │  │
                          │   │ escalation_procedure TEXT    │    contact_ │  │ observer
                          │   │ contact_person_id BIGINT FK ─┼──── person │  │
                          │   │ emergency_contacts JSON       │      id    │  │
                          │   │ equipment_needed  TEXT       │             │  │
                          │   │ created_at       TIMESTAMP   │             │  │
                          │   │ updated_at       TIMESTAMP   │             │  │
                          │   └──────────────┬───────────────┘             │  │
                          │                  │ 1                            │  │
                          │                  │                              │  │
                          │                  │ N                            │  │
                          │   ┌──────────────▼───────────────┐             │  │
                          │   │      emergency_drills        │             │  │
                          │   ├──────────────────────────────┤             │  │
                          │   │ id               BIGINT PK   │             │  │
                          │   │ drill_number     VARCHAR(50)  │             │  │
                          │   │ emergency_plan_id BIGINT FK ─┘             │  │
                          │   │ scheduled_date   DATE         │                │
                          │   │ executed_date    DATE NULL    │                │
                          │   │ site_id          BIGINT FK ───┐                │
                          │   │ participants_count INTEGER   │ │               │
                          │   │ observer_id      BIGINT FK ──┼─┼───────────────┘
                          │   │ result           VARCHAR(50)  │
                          │   │ findings         TEXT         │
                          │   │ recommendations   TEXT         │
                          │   │ status           VARCHAR(50)  │
                          │   │ created_at       TIMESTAMP    │
                          │   │ updated_at       TIMESTAMP    │
                          │   └───────────────────────────────┘
                          │
                          │   ┌──────────────────────────────┐
                          └──►│      emergency_contacts       │
                              ├──────────────────────────────┤
                              │ id            BIGINT PK      │
                              │ name          VARCHAR(255)   │
                              │ role          VARCHAR(255)   │
                              │ phone         VARCHAR(50)     │
                              │ email         VARCHAR(255) NULL│
                              │ site_id       BIGINT FK ─────┘──► sites
                              │ is_active     BOOLEAN         │
                              │ created_at    TIMESTAMP      │
                              │ updated_at    TIMESTAMP      │
                              └──────────────────────────────┘
```

### Relationship Summary

```
                        ┌──────────────┐
                        │    users     │
                        │ (contact_    │
                        │  person,     │
                        │  observer)  │
                        └──────┬───────┘
                               │ 1
                               │
                          ┌────▼─────────────────┐
                  1:N     │  emergency_plans      │     1:N  ┌──────────────────┐
                ┌────────►│  (main)               │◄────────│ emergency_drills  │
                │         └──┬──────────────────┘          └──────────────────┘
                │            │
                │     ┌──────┘
                │     │ N:1
                │  ┌──▼───┐
                │  │sites │
                │  └──────┘
                │
          ┌─────▼─────┐
          │  sites     │
          │ (contacts │
          │  FK)      │
          └───────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `emergency_plans` | `site_id` | 1:N | RESTRICT |
| `users` | `emergency_plans` | `contact_person_id` | 1:N | RESTRICT |
| `emergency_plans` | `emergency_drills` | `emergency_plan_id` | 1:N | CASCADE |
| `sites` | `emergency_drills` | `site_id` | 1:N | RESTRICT |
| `users` | `emergency_drills` | `observer_id` | 1:N | RESTRICT |
| `sites` | `emergency_contacts` | `site_id` | 1:N | RESTRICT |

---

## 6. Index Specifications

### `emergency_plans` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `emergency_plans_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `emergency_plans_plan_number_unique` | `plan_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `emergency_plans_site_id_index` | `site_id` | btree | Filter by site |
| 4 | `emergency_plans_type_index` | `type` | btree | Filter by type |
| 5 | `emergency_plans_contact_person_id_index` | `contact_person_id` | btree | Find plans by contact person |
| 6 | `emergency_plans_created_at_index` | `created_at` | btree | Sort by creation date |

```php
$table->index('site_id');
$table->index('type');
$table->index('contact_person_id');
$table->index('created_at');
```

### `emergency_drills` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `emergency_drills_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `emergency_drills_drill_number_unique` | `drill_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `emergency_drills_emergency_plan_id_index` | `emergency_plan_id` | btree | Find drills by plan |
| 4 | `emergency_drills_site_id_index` | `site_id` | btree | Filter by site |
| 5 | `emergency_drills_observer_id_index` | `observer_id` | btree | Find drills by observer |
| 6 | `emergency_drills_status_index` | `status` | btree | Filter by status |
| 7 | `emergency_drills_result_index` | `result` | btree | Filter by result |
| 8 | `emergency_drills_scheduled_date_index` | `scheduled_date` | btree | Sort/filter by scheduled date |
| 9 | `emergency_drills_executed_date_index` | `executed_date` | btree | Sort/filter by execution date |
| 10 | `emergency_drills_created_at_index` | `created_at` | btree | Sort by creation date |

```php
$table->index('emergency_plan_id');
$table->index('site_id');
$table->index('observer_id');
$table->index('status');
$table->index('result');
$table->index('scheduled_date');
$table->index('executed_date');
$table->index('created_at');
```

### `emergency_contacts` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `emergency_contacts_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `emergency_contacts_site_id_index` | `site_id` | btree | Filter by site |
| 3 | `emergency_contacts_is_active_index` | `is_active` | btree | Filter active contacts |
| 4 | `emergency_contacts_created_at_index` | `created_at` | btree | Sort by creation date |

```php
$table->index('site_id');
$table->index('is_active');
$table->index('created_at');
```

---

## 7. Shared Relations

The Emergency Preparedness module does **not** duplicate file, comment, log, or workflow tables. Instead, all cross-cutting platform services use the **polymorphic `module_name + reference_id`** pattern. For this module:

- `module_name = 'emergency'`
- `reference_id = emergency_plans.id` (for plan-level relations)

### 7.1 Managed files (`managed_files`)

File attachments (evidence, photos, procedure documents) for an emergency plan.

| Column | Value |
|---|---|
| `module_name` | `'emergency'` |
| `reference_id` | `emergency_plans.id` |
| `collection` | `'evidence'` |
| `uploaded_by` | `users.id` (FK) |

```
emergency_plans.id ──► managed_files.reference_id
                      managed_files.module_name = 'emergency'
```

**Usage**: `EmergencyPlan::files()` returns all files where `module_name='emergency'` AND `reference_id=$this->id`.

### 7.2 Comments (`comments`)

Threaded comments / discussion on an emergency plan.

| Column | Value |
|---|---|
| `module_name` | `'emergency'` |
| `reference_id` | `emergency_plans.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` |

### 7.3 Activity Logs (`activity_logs`)

Timeline of actions performed on an emergency plan or drill.

| Column | Value |
|---|---|
| `module_name` | `'emergency'` |
| `reference_id` | `emergency_plans.id` or `emergency_drills.id` |
| `event` | `'plan_created'`, `'plan_updated'`, `'drill_scheduled'`, `'drill_executed'`, `'drill_updated'`, `'contact_created'`, `'contact_updated'` |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot, metadata) |

### 7.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes on emergency plans and drills.

| Column | Value |
|---|---|
| `module_name` | `'emergency'` |
| `reference_id` | `emergency_plans.id` or `emergency_drills.id` |
| `auditable_type` | `'EmergencyPlan'` or `'EmergencyDrill'` |
| `auditable_id` | `emergency_plans.id` or `emergency_drills.id` |
| `old_values` | JSON (previous field values) |
| `new_values` | JSON (new field values) |
| `actor_id` | `users.id` (FK) |

### 7.5 Core Notifications (`core_notifications`)

In-app notifications for emergency events.

| Column | Value |
|---|---|
| `module_name` | `'emergency'` |
| `reference_id` | `emergency_plans.id` or `emergency_drills.id` |
| `type` | `'emergency.plan_created'`, `'emergency.drill_scheduled'`, `'emergency.drill_executed'`, `'emergency.drill_failed'` |

### Shared Relations Summary

```
                          ┌──────────────────────────┐
                          │   emergency_plans        │
                          │   (id: PK)               │
                          └────────────┬─────────────┘
                                       │
                    ┌──────────────────┼──────────────────┐
                    │                  │                  │
              module_name='emergency'                     │
              reference_id=emergency_plans.id              │
                    │                  │                  │
    ┌───────────────▼──┐  ┌────────────▼─────┐  ┌────────▼────────┐
    │  managed_files    │  │   comments       │  │ activity_logs   │
    │  (evidence, docs) │  │  (discussion)    │  │  (timeline)     │
    └───────────────────┘  └─────────────────┘  └─────────────────┘
                    │                  │                  │
    ┌───────────────▼──┐  ┌────────────▼─────┐
    │  audit_logs       │  │ core_notifications│
    │  (field changes)  │  │ (in-app alerts)  │
    └───────────────────┘  └──────────────────┘

    All linked via: module_name='emergency' AND reference_id=emergency_plans.id
    No hard FKs — application-layer validated polymorphic relation.
```

---

## 8. Migration File Naming Convention

### Convention

Migrations follow Laravel's standard naming pattern with module-prefixed descriptions:

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

### Phase 15 Migrations

| # | Migration File | Description |
|---|---|---|
| 1 | `2026_07_11_150000_create_emergency_plans_table.php` | Create the `emergency_plans` table |
| 2 | `2026_07_11_150100_create_emergency_drills_table.php` | Create the `emergency_drills` table |
| 3 | `2026_07_11_150200_create_emergency_contacts_table.php` | Create the `emergency_contacts` table |

### Seeder

| # | Seeder File | Description |
|---|---|---|
| 1 | `EmergencyPreparednessSeeder.php` | Seed permissions (`emergency.plans.*`, `emergency.drills.*`, `emergency.contacts.*`), assign to roles via `CorePermissions::roleMap()` |

### Factory

| # | Factory File | Description |
|---|---|---|
| 1 | `database/factories/Modules/Emergency/EmergencyPlanFactory.php` | Factory for emergency plans |
| 2 | `database/factories/Modules/Emergency/EmergencyDrillFactory.php` | Factory for emergency drills |
| 3 | `database/factories/Modules/Emergency/EmergencyContactFactory.php` | Factory for emergency contacts |

### Models

| # | Model File | Description |
|---|---|---|
| 1 | `app/Models/Modules/Emergency/EmergencyPlan.php` | Eloquent model for plans |
| 2 | `app/Models/Modules/Emergency/EmergencyDrill.php` | Eloquent model for drills |
| 3 | `app/Models/Modules/Emergency/EmergencyContact.php` | Eloquent model for contacts |

### EmergencyPlan — Fillable & Casts (Reference)

```php
protected $fillable = [
    'plan_number', 'name', 'type', 'site_id',
    'description', 'response_procedure', 'escalation_procedure',
    'contact_person_id', 'emergency_contacts', 'equipment_needed',
];

protected $casts = [
    'emergency_contacts' => 'array',
];

protected $with = ['site', 'contactPerson'];
```

### EmergencyPlan — Relationships (Reference)

```php
public function site(): BelongsTo
{
    return $this->belongsTo(Site::class);
}

public function contactPerson(): BelongsTo
{
    return $this->belongsTo(User::class, 'contact_person_id');
}

public function drills(): HasMany
{
    return $this->hasMany(EmergencyDrill::class, 'emergency_plan_id');
}

// Shared relations via module_name + reference_id
public function files(): MorphMany
{
    return ManagedFile::where('module_name', 'emergency')
        ->where('reference_id', $this->id);
}

public function comments(): MorphMany
{
    return Comment::where('module_name', 'emergency')
        ->where('reference_id', $this->id);
}

public function activities(): MorphMany
{
    return ActivityLog::where('module_name', 'emergency')
        ->where('reference_id', $this->id);
}
```

### EmergencyDrill — Fillable & Casts (Reference)

```php
protected $fillable = [
    'drill_number', 'emergency_plan_id', 'scheduled_date', 'executed_date',
    'site_id', 'participants_count', 'observer_id',
    'result', 'findings', 'recommendations', 'status',
];

protected $casts = [
    'scheduled_date' => 'date',
    'executed_date' => 'date',
];

protected $with = ['emergencyPlan', 'site', 'observer'];
```

### EmergencyDrill — Relationships (Reference)

```php
public function emergencyPlan(): BelongsTo
{
    return $this->belongsTo(EmergencyPlan::class, 'emergency_plan_id');
}

public function site(): BelongsTo
{
    return $this->belongsTo(Site::class);
}

public function observer(): BelongsTo
{
    return $this->belongsTo(User::class, 'observer_id');
}
```

### EmergencyContact — Fillable & Casts (Reference)

```php
protected $fillable = [
    'name', 'role', 'phone', 'email', 'site_id', 'is_active',
];

protected $casts = [
    'is_active' => 'boolean',
];

protected $with = ['site'];
```

### EmergencyContact — Relationships (Reference)

```php
public function site(): BelongsTo
{
    return $this->belongsTo(Site::class);
}
```

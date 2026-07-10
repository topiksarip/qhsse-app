# Data Model — Risk Management (HIRADC/JSA)

> Phase 2 schema for the Risk Management module.  
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for files, comments, logs.

---

## 1. Table of Contents

1. [Main Table: `risk_registers`](#2-main-table-risk_registers)
2. [Risk Matrix Integration (`risk_matrix_levels`)](#3-risk-matrix-integration-risk_matrix_levels)
3. [ERD Diagram (ASCII)](#4-erd-diagram-ascii)
4. [Index Specifications](#5-index-specifications)
5. [Shared Relations](#6-shared-relations)
6. [Migration File Naming Convention](#7-migration-file-naming-convention)

---

## 2. Main Table: `risk_registers`

Stores the core risk register record — hazard identification, JSA, HIRADC, or risk assessment.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `register_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `RSK-{YYYY}-{0001}`) |
| 3 | `title` | `varchar(255)` | NO | — | Short summary of the risk |
| 4 | `type` | `varchar(50)` | NO | — | **Check constraint** enum: `hazard_identification`, `jsa`, `hiradc`, `risk_assessment` |
| 5 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where risk is identified |
| 6 | `area_id` | `bigint` | YES | `NULL` | **FK → `areas.id`**. Specific area within site |
| 7 | `department_id` | `bigint` | YES | `NULL` | **FK → `departments.id`**. Owning department |
| 8 | `activity` | `varchar(500)` | NO | — | Work activity being assessed |
| 9 | `hazard` | `text` | NO | — | Identified hazard(s) |
| 10 | `existing_controls` | `text` | YES | `NULL` | Existing controls already in place |
| 11 | `severity_id` | `bigint` | YES | `NULL` | **FK → `severities.id`**. Initial severity (before controls) |
| 12 | `probability_id` | `integer` | YES | `NULL` | Initial probability level (maps to `risk_matrix_levels.probability_level`). No FK — integer reference. |
| 13 | `risk_level_id` | `bigint` | YES | `NULL` | **FK → `risk_matrix_levels.id`**. Initial risk level (lookup from severity × probability) |
| 14 | `additional_controls` | `text` | YES | `NULL` | Additional controls to be implemented |
| 15 | `residual_severity_id` | `bigint` | YES | `NULL` | **FK → `severities.id`**. Residual severity (after controls) |
| 16 | `residual_probability_id` | `integer` | YES | `NULL` | Residual probability level (maps to `risk_matrix_levels.probability_level`) |
| 17 | `residual_risk_level_id` | `bigint` | YES | `NULL` | **FK → `risk_matrix_levels.id`**. Residual risk level (lookup from residual severity × residual probability) |
| 18 | `owner_id` | `bigint` | NO | — | **FK → `users.id`**. Risk owner (responsible person) |
| 19 | `status` | `varchar(50)` | NO | `'identified'` | Lifecycle state: `identified`, `assessed`, `controls_needed`, `controls_in_place`, `monitored`, `obsolete` |
| 20 | `review_date` | `date` | YES | `NULL` | Next review date |
| 21 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 22 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE risk_registers (
    id                      BIGSERIAL       PRIMARY KEY,
    register_number         VARCHAR(50)     NOT NULL UNIQUE,
    title                   VARCHAR(255)    NOT NULL,
    type                    VARCHAR(50)     NOT NULL,
    site_id                 BIGINT          NOT NULL REFERENCES sites(id),
    area_id                 BIGINT          NULL REFERENCES areas(id),
    department_id           BIGINT          NULL REFERENCES departments(id),
    activity                VARCHAR(500)    NOT NULL,
    hazard                  TEXT            NOT NULL,
    existing_controls       TEXT            NULL,
    severity_id             BIGINT          NULL REFERENCES severities(id),
    probability_id          INTEGER         NULL,
    risk_level_id           BIGINT          NULL REFERENCES risk_matrix_levels(id),
    additional_controls     TEXT            NULL,
    residual_severity_id    BIGINT          NULL REFERENCES severities(id),
    residual_probability_id INTEGER         NULL,
    residual_risk_level_id  BIGINT          NULL REFERENCES risk_matrix_levels(id),
    owner_id                BIGINT          NOT NULL REFERENCES users(id),
    status                  VARCHAR(50)     NOT NULL DEFAULT 'identified',
    review_date             DATE            NULL,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT risk_registers_type_check CHECK (
        type IN (
            'hazard_identification',
            'jsa',
            'hiradc',
            'risk_assessment'
        )
    ),

    CONSTRAINT risk_registers_status_check CHECK (
        status IN (
            'identified',
            'assessed',
            'controls_needed',
            'controls_in_place',
            'monitored',
            'obsolete'
        )
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('risk_registers', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('register_number', 50)->unique();
    $table->string('title', 255);
    $table->string('type', 50);
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('area_id')->nullable()->constrained('areas');
    $table->foreignId('department_id')->nullable()->constrained('departments');
    $table->string('activity', 500);
    $table->text('hazard');
    $table->text('existing_controls')->nullable();
    $table->foreignId('severity_id')->nullable()->constrained('severities');
    $table->integer('probability_id')->nullable();
    $table->foreignId('risk_level_id')->nullable()->constrained('risk_matrix_levels');
    $table->text('additional_controls')->nullable();
    $table->foreignId('residual_severity_id')->nullable()->constrained('severities');
    $table->integer('residual_probability_id')->nullable();
    $table->foreignId('residual_risk_level_id')->nullable()->constrained('risk_matrix_levels');
    $table->foreignId('owner_id')->constrained('users');
    $table->string('status', 50)->default('identified');
    $table->date('review_date')->nullable();
    $table->timestamps();

    // Check constraint for type enum
    $table->check("type IN ('hazard_identification','jsa','hiradc','risk_assessment')", 'risk_registers_type_check');

    // Check constraint for status enum
    $table->check("status IN ('identified','assessed','controls_needed','controls_in_place','monitored','obsolete')", 'risk_registers_status_check');
});
```

### Design Notes

- **No soft deletes** in Phase 1 — risk registers are never hard-deleted; use `status = 'obsolete'` instead. If hard delete is needed by Admin, it cascades to shared relations via `module_name` pattern.
- **No `created_by` / `updated_by`** — `owner_id` tracks the responsible person; Laravel's `timestamps()` handles `created_at`/`updated_at`. The `actor` is tracked in `audit_logs` and `activity_logs`.
- **`register_number`** is unique and generated at **create** (not at submit). Uses `NumberingService::generate('risk', ...)`.
- **`probability_id`** is an integer (not FK to a table) — maps to `risk_matrix_levels.probability_level`. This avoids a separate `probabilities` table and keeps the matrix lookup simple. The frontend renders probability labels from distinct values in `risk_matrix_levels`.
- **No workflow engine** — status changes are direct controller actions, not `WorkflowService` transitions. Each change is logged in `activity_logs` and `audit_logs`.
- **`severity_id`** and **`residual_severity_id`** both reference the existing `severities` table (code: LOW/MEDIUM/HIGH/CRITICAL, level: 1-4, color).
- **`risk_level_id`** and **`residual_risk_level_id`** reference `risk_matrix_levels` which stores the resolved risk level (RED/ORANGE/YELLOW/GREEN) for a given severity_level × probability_level combination.

---

## 3. Risk Matrix Integration (`risk_matrix_levels`)

The `risk_matrix_levels` table is already seeded in Phase 0. It maps severity_level × probability_level → risk_level.

### Existing Table Schema

```sql
risk_matrix_levels(
    id                BIGSERIAL PRIMARY KEY,
    code              VARCHAR(50),
    name              VARCHAR(100),
    severity_level    INTEGER,      -- 1-4 (LOW=1, MEDIUM=2, HIGH=3, CRITICAL=4)
    probability_level INTEGER,      -- 1-5 (Almost Certain=5, Likely=4, Possible=3, Unlikely=2, Rare=1)
    risk_level        VARCHAR(50),   -- RED, ORANGE, YELLOW, GREEN
    is_active         BOOLEAN
)
```

### Lookup Logic

When a user selects `severity_id` and `probability_id` (integer):

1. Resolve `severity.level` from `severities` table where `id = severity_id`.
2. Use `probability_id` as `probability_level` directly.
3. Query `risk_matrix_levels` where `severity_level = severity.level` AND `probability_level = probability_id` AND `is_active = true`.
4. Set `risk_level_id` to the found `risk_matrix_levels.id`.

```php
$riskLevel = RiskMatrixLevel::where('severity_level', $severity->level)
    ->where('probability_level', $probabilityId)
    ->where('is_active', true)
    ->first();
```

### Sample Matrix (4 severity × 5 probability = 20 cells)

|  | P1 (Rare) | P2 (Unlikely) | P3 (Possible) | P4 (Likely) | P5 (Almost Certain) |
|---|---|---|---|---|---|
| **S4 (Critical)** | ORANGE | RED | RED | RED | RED |
| **S3 (High)** | YELLOW | ORANGE | ORANGE | RED | RED |
| **S2 (Medium)** | GREEN | YELLOW | ORANGE | ORANGE | RED |
| **S1 (Low)** | GREEN | GREEN | YELLOW | YELLOW | ORANGE |

### Probability Levels (derived from risk_matrix_levels)

| probability_level | Label (Indonesian) | Description |
|---|---|---|
| 1 | Jarang | Sangat kecil kemungkinan terjadi |
| 2 | Tidak Mungkin | Tidak diharapkan terjadi |
| 3 | Mungkin | Bisa terjadi kadang-kadang |
| 4 | Kemungkinan Besar | Akan sering terjadi |
| 5 | Hampir Pasti | Terjadi terus menerus |

---

## 4. ERD Diagram (ASCII)

```
┌─────────────────────┐         ┌──────────────────────────┐         ┌─────────────────────┐
│      sites           │         │     risk_registers        │         │    departments      │
├─────────────────────┤         ├──────────────────────────┤         ├─────────────────────┤
│ id          BIGINT PK│◄──┐    │ id                BIGINT PK│──┐    │ id          BIGINT PK │
│ code        VARCHAR   │   │    │ register_number   VARCHAR   │  │    │ code        VARCHAR  │
│ name        VARCHAR   │   │    │ title             VARCHAR   │  │    │ name        VARCHAR  │
│ address     TEXT      │   │    │ type              VARCHAR   │  │    │ site_id     BIGINT FK│──► sites
│ is_active   BOOLEAN   │   │    │ site_id           BIGINT FK │──┘    │ is_active   BOOLEAN  │
└─────────────────────┘   │    │ area_id           BIGINT FK │──┐    └─────────────────────┘
                          │    │ department_id     BIGINT FK │──┼────► departments
                          │    │ activity          VARCHAR   │  │
                          │    │ hazard            TEXT       │  │
                          │    │ existing_controls TEXT      │  │
                          │    │ severity_id        BIGINT FK │──┼────► severities
                          │    │ probability_id    INTEGER   │  │
                          │    │ risk_level_id      BIGINT FK│──┼────► risk_matrix_levels
                          │    │ additional_controls TEXT    │  │
                          │    │ residual_severity_id BIGINT │──┼────► severities (again)
                          │    │ residual_probability_id INT │  │
                          │    │ residual_risk_level_id BIGINT│──┼──► risk_matrix_levels (again)
                          │    │ owner_id           BIGINT FK│──┼────► users
                          │    │ status             VARCHAR  │  │
                          │    │ review_date        DATE      │  │
                          │    │ created_at         TIMESTAMP │  │
                          │    │ updated_at         TIMESTAMP │  │
                          │    └──────────────────────────┘  │
                          │              │  ▲                 │
                          │              │  │                 │
                          │              ▼  │                 │
                          │    ┌──────────────────────────┐  │
                          └──►│      areas               │  │
                              ├──────────────────────────┤  │
                              │ id          BIGINT PK    │  │
                              │ site_id     BIGINT FK ───┘  │
                              │ code        VARCHAR        │
                              │ name        VARCHAR        │
                              │ is_active   BOOLEAN        │
                              └──────────────────────────┘

          ┌─────────────────────┐     ┌──────────────────────────┐
          │    severities        │     │  risk_matrix_levels      │
          ├─────────────────────┤     ├──────────────────────────┤
          │ id          BIGINT PK│     │ id               BIGINT PK│
          │ code        VARCHAR   │     │ code             VARCHAR  │
          │ name        VARCHAR   │     │ name             VARCHAR  │
          │ level       INTEGER   │     │ severity_level   INTEGER  │
          │ color       VARCHAR   │     │ probability_level INTEGER │
          │ is_active   BOOLEAN   │     │ risk_level       VARCHAR  │
          └─────────────────────┘     │ is_active        BOOLEAN  │
                                      └──────────────────────────┘

          ┌─────────────────────┐
          │      users           │
          ├─────────────────────┤
          │ id          BIGINT PK│
          │ name        VARCHAR   │
          │ email       VARCHAR   │
          │ is_active   BOOLEAN   │
          └─────────────────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `risk_registers` | `site_id` | 1:N | RESTRICT |
| `areas` | `risk_registers` | `area_id` | 1:N | SET NULL |
| `departments` | `risk_registers` | `department_id` | 1:N | SET NULL |
| `severities` | `risk_registers` | `severity_id` | 1:N | SET NULL |
| `risk_matrix_levels` | `risk_registers` | `risk_level_id` | 1:N | SET NULL |
| `severities` | `risk_registers` | `residual_severity_id` | 1:N | SET NULL |
| `risk_matrix_levels` | `risk_registers` | `residual_risk_level_id` | 1:N | SET NULL |
| `users` | `risk_registers` | `owner_id` | 1:N | RESTRICT |

---

## 5. Index Specifications

### `risk_registers` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `risk_registers_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `risk_registers_register_number_unique` | `register_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `risk_registers_site_id_index` | `site_id` | btree | Filter by site |
| 4 | `risk_registers_area_id_index` | `area_id` | btree | Filter by area |
| 5 | `risk_registers_department_id_index` | `department_id` | btree | Filter by department |
| 6 | `risk_registers_type_index` | `type` | btree | Filter by type |
| 7 | `risk_registers_status_index` | `status` | btree | Filter by status |
| 8 | `risk_registers_severity_id_index` | `severity_id` | btree | Filter by severity |
| 9 | `risk_registers_risk_level_id_index` | `risk_level_id` | btree | Filter by risk level |
| 10 | `risk_registers_owner_id_index` | `owner_id` | btree | List by owner |
| 11 | `risk_registers_review_date_index` | `review_date` | btree | Sort/filter by review date |
| 12 | `risk_registers_created_at_index` | `created_at` | btree | Sort by creation date |

### Laravel Migration Indexes

```php
$table->index('site_id');
$table->index('area_id');
$table->index('department_id');
$table->index('type');
$table->index('status');
$table->index('severity_id');
$table->index('risk_level_id');
$table->index('owner_id');
$table->index('review_date');
$table->index('created_at');
```

---

## 6. Shared Relations

The Risk Management module does **not** duplicate file, comment, or log tables. Instead, all cross-cutting platform services use the **polymorphic `module_name + reference_id`** pattern. For this module:

- `module_name = 'risk'`
- `reference_id = risk_registers.id`

### 6.1 Managed Files (`managed_files`)

| Column | Value |
|---|---|
| `module_name` | `'risk'` |
| `reference_id` | `risk_registers.id` |
| `collection` | `'attachments'` |
| `uploaded_by` | `users.id` (FK) |

```
risk_registers.id ──► managed_files.reference_id
                        managed_files.module_name = 'risk'
```

**Usage**: `RiskRegister::files()` returns all files where `module_name='risk'` AND `reference_id=$this->id`.

### 6.2 Comments (`comments`)

| Column | Value |
|---|---|
| `module_name` | `'risk'` |
| `reference_id` | `risk_registers.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` |

**Usage**: `RiskRegister::comments()` returns all comments where `module_name='risk'` AND `reference_id=$this->id`.

### 6.3 Activity Logs (`activity_logs`)

| Column | Value |
|---|---|
| `module_name` | `'risk'` |
| `reference_id` | `risk_registers.id` |
| `event` | `'created'`, `'updated'`, `'assessed'`, `'status_changed'`, `'file.uploaded'`, etc. |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot) |

**Usage**: `RiskRegister::activities()` returns all activity log entries.

### 6.4 Audit Logs (`audit_logs`)

| Column | Value |
|---|---|
| `module_name` | `'risk'` |
| `reference_id` | `risk_registers.id` |
| `auditable_type` | `'RiskRegister'` |
| `auditable_id` | `risk_registers.id` |
| `old_values` | JSON |
| `new_values` | JSON |
| `actor_id` | `users.id` (FK) |

**Usage**: `RiskRegister::audits()` returns all audit log entries.

### Shared Relations Summary

```
                          ┌──────────────────┐
                          │  risk_registers   │
                          │  (id: PK)         │
                          └──────┬───────────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='risk'               │
              reference_id=risk_registers.id   │
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │  comments   │  │ activity_logs  │
    │  (attachments)    │  │ (discussion)│  │  (timeline)    │
    └───────────────────┘  └─────────────┘  └────────────────┘
                    │
    ┌───────────────▼──┐
    │  audit_logs       │
    │  (field changes)  │
    └──────────────────┘

    All linked via: module_name='risk' AND reference_id=risk_registers.id
    No hard FKs — application-layer validated polymorphic relation.
```

---

## 7. Migration File Naming Convention

### Convention

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

| Segment | Description |
|---|---|
| `YYYY_MM_DD_HHMMSS` | Timestamp (auto-generated by `php artisan make:migration`) |
| `verb` | `create` for new tables, `add`, `update`, `drop` for modifications |

### This Module's Migrations

| # | Migration File | Description |
|---|---|---|
| 1 | `2026_07_11_000001_create_risk_registers_table.php` | Create `risk_registers` table |

### Eloquent Model

File: `app/Models/Modules/RiskManagement/RiskRegister.php`

```php
class RiskRegister extends Model
{
    protected $table = 'risk_registers';

    protected $fillable = [
        'register_number',
        'title',
        'type',
        'site_id',
        'area_id',
        'department_id',
        'activity',
        'hazard',
        'existing_controls',
        'severity_id',
        'probability_id',
        'risk_level_id',
        'additional_controls',
        'residual_severity_id',
        'residual_probability_id',
        'residual_risk_level_id',
        'owner_id',
        'status',
        'review_date',
    ];

    protected $casts = [
        'review_date' => 'date',
    ];

    // Relationships
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function severity(): BelongsTo
    {
        return $this->belongsTo(Severity::class, 'severity_id');
    }

    public function residualSeverity(): BelongsTo
    {
        return $this->belongsTo(Severity::class, 'residual_severity_id');
    }

    public function riskLevel(): BelongsTo
    {
        return $this->belongsTo(RiskMatrixLevel::class, 'risk_level_id');
    }

    public function residualRiskLevel(): BelongsTo
    {
        return $this->belongsTo(RiskMatrixLevel::class, 'residual_risk_level_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Shared relations (polymorphic via module_name + reference_id)
    public function files(): MorphMany
    {
        return ManagedFile::where('module_name', 'risk')
            ->where('reference_id', $this->id);
    }

    public function comments(): MorphMany
    {
        return Comment::where('module_name', 'risk')
            ->where('reference_id', $this->id);
    }

    public function activities(): MorphMany
    {
        return ActivityLog::where('module_name', 'risk')
            ->where('reference_id', $this->id);
    }

    public function audits(): MorphMany
    {
        return AuditLog::where('module_name', 'risk')
            ->where('reference_id', $this->id);
    }

    // Helpers
    public function isTerminal(): bool
    {
        return $this->status === 'obsolete';
    }

    // Risk level lookup
    public static function lookupRiskLevel(int $severityId, int $probabilityId): ?RiskMatrixLevel
    {
        $severity = Severity::find($severityId);
        if (!$severity) return null;

        return RiskMatrixLevel::where('severity_level', $severity->level)
            ->where('probability_level', $probabilityId)
            ->where('is_active', true)
            ->first();
    }
}
```

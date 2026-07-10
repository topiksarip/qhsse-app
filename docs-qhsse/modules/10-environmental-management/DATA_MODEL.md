# Data Model — Environmental Management

> Phase 10 schema for the Environmental Management module.
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs).

---

## 1. Table of Contents

1. [Main Table: `environmental_records`](#2-main-table-environmental_records)
2. [ERD Diagram (ASCII)](#3-erd-diagram-ascii)
3. [Index Specifications](#4-index-specifications)
4. [Shared Relations](#5-shared-relations)
5. [Migration File Naming Convention](#6-migration-file-naming-convention)

---

## 2. Main Table: `environmental_records`

Stores the core environmental record — waste, spill, emission, noise, water monitoring, or other environmental observation.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `record_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `ENV-{YYYY}-{0001}`) via NumberingService |
| 3 | `type` | `varchar(50)` | NO | — | **Check constraint** enum: `waste`, `spill`, `emission`, `noise`, `water_monitoring`, `other` |
| 4 | `title` | `varchar(255)` | NO | — | Short summary of the environmental record |
| 5 | `description` | `text` | NO | — | Detailed narrative / observation |
| 6 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where record occurred |
| 7 | `area_id` | `bigint` | YES | `NULL` | **FK → `areas.id`**. Specific area within site |
| 8 | `occurred_at` | `timestamp` | YES | `NULL` | When the event/measurement occurred. Nullable for records without specific timing |
| 9 | `measured_value` | `decimal(15,4)` | YES | `NULL` | Measured value (for emission, noise, water_monitoring). Used in exceedance detection |
| 10 | `unit` | `varchar(50)` | YES | `NULL` | Unit of measurement (e.g., `mg/m³`, `dB`, `pH`, `mg/L`) |
| 11 | `limit_value` | `decimal(15,4)` | YES | `NULL` | Regulatory or threshold limit. If `measured_value > limit_value` → `is_exceedance = true` |
| 12 | `is_exceedance` | `boolean` | NO | `false` | Auto-calculated: true if `measured_value > limit_value` (both not null) |
| 13 | `waste_type` | `varchar(255)` | YES | `NULL` | **Type-specific** (type=`waste`): jenis limbah (B3, non-B3, medis) |
| 14 | `quantity` | `decimal(15,4)` | YES | `NULL` | **Type-specific** (type=`waste`): jumlah limbah |
| 15 | `disposal_method` | `varchar(255)` | YES | `NULL` | **Type-specific** (type=`waste`): metode pembuangan (Incinerasi, TPA, Pihak Ketiga) |
| 16 | `material` | `varchar(255)` | YES | `NULL` | **Type-specific** (type=`spill`): jenis material yang tumpah |
| 17 | `volume` | `decimal(15,4)` | YES | `NULL` | **Type-specific** (type=`spill`): volume tumpahan |
| 18 | `containment` | `varchar(255)` | YES | `NULL` | **Type-specific** (type=`spill`): tindakan penahanan (Boom oil, Absorbent) |
| 19 | `parameter` | `varchar(255)` | YES | `NULL` | **Type-specific** (type=`emission`/`water_monitoring`): parameter yang diukur (SOx, pH, TSS, dll) |
| 20 | `location` | `varchar(255)` | YES | `NULL` | **Type-specific** (type=`noise`): lokasi spesifik pengukuran kebisingan |
| 21 | `reporter_id` | `bigint` | NO | — | **FK → `users.id`**. User who created the record |
| 22 | `status` | `varchar(50)` | NO | `'recorded'` | Lifecycle state: `recorded`, `investigated`, `action_open`, `closed` |
| 23 | `capa_action_id` | `bigint` | YES | `NULL` | **FK → `capa_actions.id`**. Linked CAPA record (nullable) |
| 24 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 25 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE environmental_records (
    id                  BIGSERIAL       PRIMARY KEY,
    record_number       VARCHAR(50)     NOT NULL UNIQUE,
    type                VARCHAR(50)     NOT NULL,
    title               VARCHAR(255)    NOT NULL,
    description         TEXT            NOT NULL,
    site_id             BIGINT          NOT NULL REFERENCES sites(id),
    area_id             BIGINT          NULL REFERENCES areas(id),
    occurred_at         TIMESTAMP       NULL,
    measured_value      DECIMAL(15,4)   NULL,
    unit                VARCHAR(50)     NULL,
    limit_value         DECIMAL(15,4)   NULL,
    is_exceedance       BOOLEAN         NOT NULL DEFAULT FALSE,
    waste_type          VARCHAR(255)    NULL,
    quantity            DECIMAL(15,4)   NULL,
    disposal_method     VARCHAR(255)    NULL,
    material            VARCHAR(255)    NULL,
    volume              DECIMAL(15,4)   NULL,
    containment         VARCHAR(255)    NULL,
    parameter           VARCHAR(255)    NULL,
    location            VARCHAR(255)    NULL,
    reporter_id         BIGINT          NOT NULL REFERENCES users(id),
    status              VARCHAR(50)     NOT NULL DEFAULT 'recorded',
    capa_action_id      BIGINT          NULL REFERENCES capa_actions(id),
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT env_records_type_check CHECK (
        type IN ('waste', 'spill', 'emission', 'noise', 'water_monitoring', 'other')
    ),
    CONSTRAINT env_records_status_check CHECK (
        status IN ('recorded', 'investigated', 'action_open', 'closed')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('environmental_records', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('record_number', 50)->unique();
    $table->string('type', 50);
    $table->string('title', 255);
    $table->text('description');
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('area_id')->nullable()->constrained('areas');
    $table->timestamp('occurred_at')->nullable();
    $table->decimal('measured_value', 15, 4)->nullable();
    $table->string('unit', 50)->nullable();
    $table->decimal('limit_value', 15, 4)->nullable();
    $table->boolean('is_exceedance')->default(false);
    // Type-specific fields
    $table->string('waste_type', 255)->nullable();
    $table->decimal('quantity', 15, 4)->nullable();
    $table->string('disposal_method', 255)->nullable();
    $table->string('material', 255)->nullable();
    $table->decimal('volume', 15, 4)->nullable();
    $table->string('containment', 255)->nullable();
    $table->string('parameter', 255)->nullable();
    $table->string('location', 255)->nullable();
    // Ownership and status
    $table->foreignId('reporter_id')->constrained('users');
    $table->string('status', 50)->default('recorded');
    $table->foreignId('capa_action_id')->nullable()->constrained('capa_actions');
    $table->timestamps();

    // Check constraints
    $table->check("type IN ('waste','spill','emission','noise','water_monitoring','other')", 'env_records_type_check');
    $table->check("status IN ('recorded','investigated','action_open','closed')", 'env_records_status_check');
});
```

### Design Notes

- **No soft deletes** (`deleted_at`) — records are never hard-deleted; use status `closed` instead. If delete is needed, use Laravel soft deletes with `deleted_at` column added later.
- **`record_number`** is unique and generated at **create** time via `NumberingService::generate('environment', $actor, ...)`.
- **`type`** is stored as `varchar` with CHECK constraint — simplifies application-level validation and future type additions without migration.
- **Type-specific fields** (`waste_type`, `quantity`, `disposal_method`, `material`, `volume`, `containment`, `parameter`, `location`) are all nullable. They are conditionally required based on `type` via Form Request validation rules, not database constraints.
- **`is_exceedance`** is auto-calculated in model observer or service layer. Never set directly by user input.
- **`capa_action_id`** links to the CAPA module's `capa_actions` table. Set when status transitions to `action_open`.
- **`measured_value` and `limit_value`** use `decimal(15,4)` for high-precision environmental measurements.

---

## 3. ERD Diagram (ASCII)

```
┌─────────────────────┐         ┌──────────────────────────────┐         ┌─────────────────────┐
│      sites           │         │   environmental_records       │         │     areas           │
├─────────────────────┤         ├──────────────────────────────┤         ├─────────────────────┤
│ id          BIGINT  PK│◄──┐   │ id               BIGINT PK   │──┐    │ id          BIGINT PK│
│ code        VARCHAR   │   │   │ record_number    VARCHAR(50)  │  │    │ site_id     BIGINT FK│──► sites
│ name        VARCHAR   │   │   │ type             VARCHAR(50)  │  │    │ code        VARCHAR  │
│ address     TEXT      │   │   │ title            VARCHAR(255) │  │    │ name        VARCHAR  │
│ is_active   BOOLEAN   │   │   │ description      TEXT         │  │    │ is_active   BOOLEAN  │
└─────────────────────┘   │   │ site_id          BIGINT FK ────┘──┘    └─────────────────────┘
                          │   │ area_id          BIGINT FK ────┐
                          │   │ occurred_at      TIMESTAMP     │
                          │   │ measured_value   DECIMAL(15,4) │
                          │   │ unit             VARCHAR(50)    │
                          │   │ limit_value      DECIMAL(15,4) │
                          │   │ is_exceedance    BOOLEAN        │
                          │   │ waste_type       VARCHAR(255)  │    ┌─────────────────────┐
                          │   │ quantity         DECIMAL(15,4) │    │     users           │
                          │   │ disposal_method  VARCHAR(255)  │    ├─────────────────────┤
                          │   │ material         VARCHAR(255)  │    │ id          BIGINT PK│
                          │   │ volume           DECIMAL(15,4) │◄───│ reporter_id  FK     │
                          │   │ containment      VARCHAR(255)  │    │ name        VARCHAR  │
                          │   │ parameter        VARCHAR(255)  │    │ email       VARCHAR  │
                          │   │ location         VARCHAR(255)  │    └─────────────────────┘
                          │   │ reporter_id      BIGINT FK ────┘
                          │   │ status           VARCHAR(50)   │
                          │   │ capa_action_id   BIGINT FK ────┐
                          │   │ created_at       TIMESTAMP     │
                          │   │ updated_at       TIMESTAMP     │
                          │   └──────────────────────────────┘  │
                          │                                     │
                          │              ┌──────────────────────┘
                          │              ▼
                          │   ┌─────────────────────┐
                          │   │   capa_actions      │
                          │   ├─────────────────────┤
                          │   │ id          BIGINT PK│
                          └───│ (FK target)         │
                              │ number     VARCHAR  │
                              │ status     VARCHAR  │
                              └─────────────────────┘
```

### Relationship Summary

```
                        ┌──────────────┐
                        │   users      │
                        │  (reporter)  │
                        └──────┬───────┘
                               │ 1
                               │
                          ┌────▼─────────────────┐
                  N:1     │ environmental_records │     1:0..1  ┌──────────────┐
                ┌────────►│ (main)               │◄────────────│ capa_actions │
                │         └──┬──┬──┬────────────┘             └──────────────┘
                │            │  │  │
                │            │  │  │
                │     ┌──────┘  │  └──────┐
                │     │ N:1     │ N:1     │ N:1
                │  ┌──▼───┐ ┌──▼───┐ ┌───▼────┐
                │  │sites │ │areas │ │ capa_  │
                │  │      │ │      │ │ actions│
                │  └──────┘ └──────┘ └────────┘
                │
          ┌─────▼─────┐
          │  sites     │
          │ (areas FK) │
          └────────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `environmental_records` | `site_id` | 1:N | RESTRICT |
| `areas` | `environmental_records` | `area_id` | 1:N | SET NULL |
| `users` | `environmental_records` | `reporter_id` | 1:N | RESTRICT |
| `capa_actions` | `environmental_records` | `capa_action_id` | 1:0..1 | SET NULL |

---

## 4. Index Specifications

### `environmental_records` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `environmental_records_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `environmental_records_record_number_unique` | `record_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `environmental_records_site_id_index` | `site_id` | btree | Filter by site |
| 4 | `environmental_records_area_id_index` | `area_id` | btree | Filter by area |
| 5 | `environmental_records_reporter_id_index` | `reporter_id` | btree | List records by reporter |
| 6 | `environmental_records_type_index` | `type` | btree | Filter by record type |
| 7 | `environmental_records_status_index` | `status` | btree | Filter/list by status |
| 8 | `environmental_records_is_exceedance_index` | `is_exceedance` | btree | Filter exceedance records |
| 9 | `environmental_records_occurred_at_index` | `occurred_at` | btree | Sort/filter by date range |
| 10 | `environmental_records_created_at_index` | `created_at` | btree | Sort by creation date |
| 11 | `environmental_records_capa_action_id_index` | `capa_action_id` | btree | Find record linked to CAPA |

### Laravel Migration Indexes

```php
$table->index('site_id');
$table->index('area_id');
$table->index('reporter_id');
$table->index('type');
$table->index('status');
$table->index('is_exceedance');
$table->index('occurred_at');
$table->index('created_at');
$table->index('capa_action_id');
```

---

## 5. Shared Relations

The Environmental Management module does **not** duplicate file, comment, log, or workflow tables. Instead, all cross-cutting platform services use the **polymorphic `module_name + reference_id`** pattern. For this module:

- `module_name = 'environment'`
- `reference_id = environmental_records.id`

### 5.1 Managed Files (`managed_files`)

File attachments (evidence, photos, documents) for an environmental record.

| Column | Value |
|---|---|
| `module_name` | `'environment'` |
| `reference_id` | `environmental_records.id` |
| `collection` | `'evidence'` |
| `uploaded_by` | `users.id` (FK) |

```
environmental_records.id ──► managed_files.reference_id
                              managed_files.module_name = 'environment'
```

**Usage**: `EnvironmentalRecord::files()` returns all files where `module_name='environment'` AND `reference_id=$this->id`.

### 5.2 Comments (`comments`)

Threaded comments / discussion on an environmental record.

| Column | Value |
|---|---|
| `module_name` | `'environment'` |
| `reference_id` | `environmental_records.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` |

```
environmental_records.id ──► comments.reference_id
                              comments.module_name = 'environment'
```

### 5.3 Activity Logs (`activity_logs`)

Timeline of actions performed on a record (created, investigated, closed, etc.).

| Column | Value |
|---|---|
| `module_name` | `'environment'` |
| `reference_id` | `environmental_records.id` |
| `event` | `'created'`, `'updated'`, `'investigated'`, `'action_opened'`, `'closed'`, `'exceedance_detected'` |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot, metadata) |

### 5.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes on environmental record.

| Column | Value |
|---|---|
| `module_name` | `'environment'` |
| `reference_id` | `environmental_records.id` |
| `auditable_type` | `'EnvironmentalRecord'` |
| `auditable_id` | `environmental_records.id` |
| `old_values` | JSON (previous field values) |
| `new_values` | JSON (new field values) |
| `actor_id` | `users.id` (FK) |

### 5.5 Core Notifications (`core_notifications`)

In-app notifications for environmental events.

| Column | Value |
|---|---|
| `module_name` | `'environment'` |
| `reference_id` | `environmental_records.id` |
| `type` | `'environment.exceedance_detected'`, `'environment.investigated'`, `'environment.closed'` |

### Shared Relations Summary

```
                          ┌──────────────────────────┐
                          │  environmental_records   │
                          │  (id: PK)                │
                          └────────────┬─────────────┘
                                       │
                    ┌──────────────────┼──────────────────┐
                    │                  │                  │
              module_name='environment'                    │
              reference_id=environmental_records.id        │
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

    All linked via: module_name='environment' AND reference_id=environmental_records.id
    No hard FKs — application-layer validated polymorphic relation.
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
| `table_name` | snake_case table name |

### Phase 10 Migration

| # | Migration File | Description |
|---|---|---|
| 1 | `2026_07_11_100000_create_environmental_records_table.php` | Create the `environmental_records` table |

### Seeder

| # | Seeder File | Description |
|---|---|---|
| 1 | `EnvironmentalManagementSeeder.php` | Seed permissions (`environment.records.*`), assign to roles via `CorePermissions::roleMap()` |

### Factory

| # | Factory File | Description |
|---|---|---|
| 1 | `database/factories/Modules/Environmental/EnvironmentalRecordFactory.php` | Factory for test data |

### Model

| # | Model File | Description |
|---|---|---|
| 1 | `app/Models/Modules/Environmental/EnvironmentalRecord.php` | Eloquent model |

### Fillable & Casts (Reference)

```php
protected $fillable = [
    'record_number', 'type', 'title', 'description',
    'site_id', 'area_id', 'occurred_at',
    'measured_value', 'unit', 'limit_value', 'is_exceedance',
    'waste_type', 'quantity', 'disposal_method',
    'material', 'volume', 'containment',
    'parameter', 'location',
    'reporter_id', 'status', 'capa_action_id',
];

protected $casts = [
    'occurred_at' => 'datetime',
    'measured_value' => 'decimal:4',
    'limit_value' => 'decimal:4',
    'quantity' => 'decimal:4',
    'volume' => 'decimal:4',
    'is_exceedance' => 'boolean',
];

protected $with = ['site', 'area', 'reporter'];
```

### Relationships (Reference)

```php
public function site(): BelongsTo
{
    return $this->belongsTo(Site::class);
}

public function area(): BelongsTo
{
    return $this->belongsTo(Area::class);
}

public function reporter(): BelongsTo
{
    return $this->belongsTo(User::class, 'reporter_id');
}

public function capaAction(): BelongsTo
{
    return $this->belongsTo(CapaAction::class);
}

// Shared relations via module_name + reference_id
public function files(): MorphMany
{
    return ManagedFile::where('module_name', 'environment')
        ->where('reference_id', $this->id);
}

public function comments(): MorphMany
{
    return Comment::where('module_name', 'environment')
        ->where('reference_id', $this->id);
}

public function activities(): MorphMany
{
    return ActivityLog::where('module_name', 'environment')
        ->where('reference_id', $this->id);
}
```

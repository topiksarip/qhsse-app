# Data Model — Inspection Checklist

> Phase 4 schema for the Inspection Checklist module.
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs, workflow).

---

## 1. Table of Contents

1. [Table: `inspection_templates`](#2-table-inspection_templates)
2. [Table: `inspection_items`](#3-table-inspection_items)
3. [Table: `inspections`](#4-table-inspections)
4. [Table: `inspection_results`](#5-table-inspection_results)
5. [ERD Diagram (ASCII)](#6-erd-diagram-ascii)
6. [Index Specifications](#7-index-specifications)
7. [Shared Relations](#8-shared-relations)
8. [Migration File Naming Convention](#9-migration-file-naming-convention)

---

## 2. Table: `inspection_templates`

Stores the template definition for inspection checklists. Each template contains multiple inspection items.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `code` | `varchar(50)` | NO | — | **Unique**. Human-readable code (e.g., `SAF-001`) |
| 3 | `name` | `varchar(255)` | NO | — | Template name |
| 4 | `description` | `text` | YES | `NULL` | Detailed description of the template |
| 5 | `category` | `varchar(50)` | NO | — | **Check constraint** enum: `safety`, `environment`, `equipment`, `fire`, `housekeeping`, `security`, `quality`, `compliance` |
| 6 | `is_active` | `boolean` | NO | `true` | Active/inactive flag |
| 7 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 8 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE inspection_templates (
    id              BIGSERIAL       PRIMARY KEY,
    code            VARCHAR(50)     NOT NULL UNIQUE,
    name            VARCHAR(255)    NOT NULL,
    description     TEXT            NULL,
    category        VARCHAR(50)     NOT NULL,
    is_active       BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT inspection_templates_category_check CHECK (
        category IN (
            'safety', 'environment', 'equipment', 'fire',
            'housekeeping', 'security', 'quality', 'compliance'
        )
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('inspection_templates', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('code', 50)->unique();
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->string('category', 50);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index('category');
    $table->index('is_active');
});
```

### Design Notes

- **No soft deletes** in Phase 4 — templates are deactivated (`is_active=false`) instead of deleted when in use by inspections. Hard delete is allowed only by Admin/QHSSE Manager when no inspections reference the template.
- **`code`** is unique and human-readable for easy reference.
- **`category`** is stored as `varchar` with CHECK constraint for flexibility.

---

## 3. Table: `inspection_items`

Stores individual checklist items within a template. Each item defines a question and the type of answer expected.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `inspection_template_id` | `bigint` | NO | — | **FK → `inspection_templates.id`**, `ON DELETE CASCADE` |
| 3 | `question` | `text` | NO | — | The inspection question/item text |
| 4 | `type` | `varchar(20)` | NO | — | **Check constraint** enum: `yes_no`, `safe_unsafe`, `na`, `scale`, `text` |
| 5 | `category` | `varchar(50)` | YES | `NULL` | Optional category for grouping items in reports |
| 6 | `is_required` | `boolean` | NO | `true` | Whether this item must be answered to complete inspection |
| 7 | `order` | `integer` | NO | `0` | Display order within template |
| 8 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 9 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE inspection_items (
    id                       BIGSERIAL    PRIMARY KEY,
    inspection_template_id   BIGINT       NOT NULL REFERENCES inspection_templates(id) ON DELETE CASCADE,
    question                 TEXT         NOT NULL,
    type                     VARCHAR(20)  NOT NULL,
    category                 VARCHAR(50)  NULL,
    is_required              BOOLEAN      NOT NULL DEFAULT TRUE,
    "order"                  INTEGER      NOT NULL DEFAULT 0,
    created_at               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT inspection_items_type_check CHECK (
        type IN ('yes_no', 'safe_unsafe', 'na', 'scale', 'text')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('inspection_items', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('inspection_template_id')->constrained('inspection_templates')->cascadeOnDelete();
    $table->text('question');
    $table->string('type', 20);
    $table->string('category', 50)->nullable();
    $table->boolean('is_required')->default(true);
    $table->integer('order')->default(0);
    $table->timestamps();

    $table->index('inspection_template_id');
    $table->index('type');
    $table->index('order');
});
```

### Design Notes

- **Cascade delete** on `inspection_template_id` — when a template is deleted, all its items are deleted too.
- **`order`** is an integer for sorting items within a template. Default `0`, items displayed in ascending order.
- **`type`** determines the UI rendering and answer validation at execution time.
- **`category`** on items is separate from template category — allows grouping items within a template (e.g., "PPE", "Fire Safety", "Housekeeping" within a Safety Inspection template).

---

## 4. Table: `inspections`

Stores the executed instance of an inspection — created from a template, assigned to an inspector, and executed at a specific site/area.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `inspection_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `INS-{YYYY}-{0001}`) |
| 3 | `inspection_template_id` | `bigint` | NO | — | **FK → `inspection_templates.id`**. Template used |
| 4 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where inspection occurs |
| 5 | `area_id` | `bigint` | YES | `NULL` | **FK → `areas.id`**. Specific area within site |
| 6 | `inspector_id` | `bigint` | NO | — | **FK → `users.id`**. User assigned to perform inspection |
| 7 | `scheduled_at` | `date` | NO | — | Scheduled inspection date |
| 8 | `executed_at` | `timestamp` | YES | `NULL` | When inspection was actually started (set on `pending → in_progress`) |
| 9 | `status` | `varchar(50)` | NO | `'pending'` | Lifecycle: `pending`, `in_progress`, `completed` |
| 10 | `overall_result` | `varchar(20)` | NO | `'pending'` | Result: `pass`, `fail`, `pending` |
| 11 | `notes` | `text` | YES | `NULL` | General notes by inspector |
| 12 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 13 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE inspections (
    id                        BIGSERIAL    PRIMARY KEY,
    inspection_number         VARCHAR(50)  NOT NULL UNIQUE,
    inspection_template_id    BIGINT       NOT NULL REFERENCES inspection_templates(id),
    site_id                   BIGINT       NOT NULL REFERENCES sites(id),
    area_id                   BIGINT       NULL REFERENCES areas(id),
    inspector_id              BIGINT       NOT NULL REFERENCES users(id),
    scheduled_at              DATE         NOT NULL,
    executed_at               TIMESTAMP    NULL,
    status                    VARCHAR(50)  NOT NULL DEFAULT 'pending',
    overall_result            VARCHAR(20)  NOT NULL DEFAULT 'pending',
    notes                     TEXT         NULL,
    created_at                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT inspections_status_check CHECK (
        status IN ('pending', 'in_progress', 'completed')
    ),
    CONSTRAINT inspections_overall_result_check CHECK (
        overall_result IN ('pass', 'fail', 'pending')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('inspections', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('inspection_number', 50)->unique();
    $table->foreignId('inspection_template_id')->constrained('inspection_templates');
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('area_id')->nullable()->constrained('areas');
    $table->foreignId('inspector_id')->constrained('users');
    $table->date('scheduled_at');
    $table->timestamp('executed_at')->nullable();
    $table->string('status', 50)->default('pending');
    $table->string('overall_result', 20)->default('pending');
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index('inspection_template_id');
    $table->index('site_id');
    $table->index('area_id');
    $table->index('inspector_id');
    $table->index('status');
    $table->index('overall_result');
    $table->index('scheduled_at');
});
```

### Design Notes

- **No soft deletes** — inspections are never deleted; they remain as historical records.
- **`inspection_number`** is unique and generated at create time via `NumberingService`.
- **`executed_at`** is NULL until the inspector starts the inspection (transition `pending → in_progress`).
- **`overall_result`** is `pending` until the inspection is completed. Set to `pass` or `fail` on complete.
- **`status`** has only 3 states for Phase 4 simplicity: `pending`, `in_progress`, `completed`.

---

## 5. Table: `inspection_results`

Stores the answer for each item within an executed inspection. One row per inspection item per inspection.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `inspection_id` | `bigint` | NO | — | **FK → `inspections.id`**, `ON DELETE CASCADE` |
| 3 | `inspection_item_id` | `bigint` | NO | — | **FK → `inspection_items.id`**. The template item this answers |
| 4 | `answer` | `varchar(255)` | YES | `NULL` | The answer value (e.g., `yes`, `no`, `safe`, `unsafe`, `na`, `1`-`5`, free text) |
| 5 | `remark` | `text` | YES | `NULL` | Optional remark/observation for this item |
| 6 | `is_unsafe` | `boolean` | NO | `false` | Flag: true if answer indicates unsafe condition |
| 7 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 8 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE inspection_results (
    id                    BIGSERIAL    PRIMARY KEY,
    inspection_id         BIGINT       NOT NULL REFERENCES inspections(id) ON DELETE CASCADE,
    inspection_item_id    BIGINT       NOT NULL REFERENCES inspection_items(id),
    answer                VARCHAR(255) NULL,
    remark                TEXT         NULL,
    is_unsafe             BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT inspection_results_unique UNIQUE (inspection_id, inspection_item_id)
);
```

### Laravel Migration (Reference)

```php
Schema::create('inspection_results', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
    $table->foreignId('inspection_item_id')->constrained('inspection_items');
    $table->string('answer', 255)->nullable();
    $table->text('remark')->nullable();
    $table->boolean('is_unsafe')->default(false);
    $table->timestamps();

    $table->unique(['inspection_id', 'inspection_item_id']);
    $table->index('inspection_id');
    $table->index('inspection_item_id');
    $table->index('is_unsafe');
});
```

### Design Notes

- **Cascade delete** on `inspection_id` — when an inspection is deleted, all results go with it.
- **Unique composite** (`inspection_id`, `inspection_item_id`) ensures one answer per item per inspection.
- **`answer`** is nullable to support items not yet answered (during `in_progress` state).
- **`is_unsafe`** is auto-calculated based on item `type` and `answer`:
  - `safe_unsafe` + `unsafe` → `is_unsafe=true`
  - `yes_no` + `no` → `is_unsafe=true`
  - `scale` + `1` or `2` → `is_unsafe=true` (configurable)
  - `na` + `na` → `is_unsafe=false`
  - `text` → `is_unsafe=false`
- **`inspection_item_id`** does NOT cascade delete — if a template item is deleted after inspection creation, the result should persist (use `RESTRICT` or `SET NULL`). For Phase 4, we use `RESTRICT` (default) and template items should not be deleted if inspections reference them.

---

## 6. ERD Diagram (ASCII)

```
┌──────────────────────────┐
│  inspection_templates    │
├──────────────────────────┤
│ id            BIGINT  PK │
│ code          VARCHAR  UQ │
│ name          VARCHAR     │
│ description   TEXT        │
│ category      VARCHAR     │
│ is_active     BOOLEAN     │
│ created_at    TIMESTAMP   │
│ updated_at    TIMESTAMP   │
└────────────┬─────────────┘
             │ 1:N
             │
             ▼
┌──────────────────────────┐         ┌──────────────────────────┐
│  inspection_items        │         │  sites                    │
├──────────────────────────┤         ├──────────────────────────┤
│ id            BIGINT  PK │         │ id            BIGINT  PK │
│ template_id   BIGINT FK ◄┘         │ code          VARCHAR    │
│ question      TEXT                 │ name          VARCHAR    │
│ type          VARCHAR              │ address       TEXT       │
│ category      VARCHAR              │ is_active     BOOLEAN    │
│ is_required   BOOLEAN              └────────────┬─────────────┘
│ order         INTEGER                           │
│ created_at    TIMESTAMP                        │ 1:N
│ updated_at    TIMESTAMP                        │
└──────────────────────────┘                     │
                                                 ▼
                                         ┌──────────────────────────┐
                                         │  inspections             │
                                         ├──────────────────────────┤
                                         │ id              BIGINT PK │
                                         │ inspection_number VARCHAR │
                                         │                 UNIQUE   │
                                         │ template_id    BIGINT FK │──► inspection_templates
                                         │ site_id        BIGINT FK │──► sites
                                         │ area_id        BIGINT FK │──► areas (nullable)
                                         │ inspector_id   BIGINT FK │──► users
                                         │ scheduled_at   DATE      │
                                         │ executed_at    TIMESTAMP  │
                                         │ status         VARCHAR   │
                                         │ overall_result VARCHAR   │
                                         │ notes          TEXT      │
                                         │ created_at     TIMESTAMP │
                                         │ updated_at     TIMESTAMP │
                                         └────────────┬─────────────┘
                                                      │ 1:N
                                                      │
                                                      ▼
                                         ┌──────────────────────────┐
                                         │  inspection_results      │
                                         ├──────────────────────────┤
                                         │ id              BIGINT PK│
                                         │ inspection_id  BIGINT FK │──► inspections (cascade)
                                         │ item_id        BIGINT FK │──► inspection_items
                                         │ answer         VARCHAR   │
                                         │ remark         TEXT      │
                                         │ is_unsafe      BOOLEAN   │
                                         │ created_at     TIMESTAMP │
                                         │ updated_at     TIMESTAMP │
                                         └──────────────────────────┘
                                                      │
                                                      │ module_name='inspection'
                                                      │ reference_id=inspections.id
                                                      ▼
                                         ┌──────────────────────────┐
                                         │  managed_files           │
                                         │  comments                │
                                         │  activity_logs           │
                                         │  audit_logs              │
                                         │  workflow_instances      │
                                         │  workflow_histories      │
                                         └──────────────────────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `inspection_templates` | `inspection_items` | `inspection_template_id` | 1:N | CASCADE |
| `inspection_templates` | `inspections` | `inspection_template_id` | 1:N | RESTRICT |
| `sites` | `inspections` | `site_id` | 1:N | RESTRICT |
| `areas` | `inspections` | `area_id` | 1:N | SET NULL |
| `users` | `inspections` | `inspector_id` | 1:N | RESTRICT |
| `inspections` | `inspection_results` | `inspection_id` | 1:N | CASCADE |
| `inspection_items` | `inspection_results` | `inspection_item_id` | 1:N | RESTRICT |

---

## 7. Index Specifications

### `inspection_templates` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `inspection_templates_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `inspection_templates_code_unique` | `code` | UNIQUE (btree) | Code lookup, prevent duplicates |
| 3 | `inspection_templates_category_index` | `category` | btree | Filter by category |
| 4 | `inspection_templates_is_active_index` | `is_active` | btree | Filter active/inactive |

### `inspection_items` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `inspection_items_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `inspection_items_template_id_index` | `inspection_template_id` | btree | Find items for a template |
| 3 | `inspection_items_type_index` | `type` | btree | Filter by item type |
| 4 | `inspection_items_order_index` | `order` | btree | Sort items by order |

### `inspections` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `inspections_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `inspections_inspection_number_unique` | `inspection_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `inspections_template_id_index` | `inspection_template_id` | btree | Filter by template |
| 4 | `inspections_site_id_index` | `site_id` | btree | Filter by site |
| 5 | `inspections_area_id_index` | `area_id` | btree | Filter by area |
| 6 | `inspections_inspector_id_index` | `inspector_id` | btree | Find inspections by inspector |
| 7 | `inspections_status_index` | `status` | btree | Filter/list by status |
| 8 | `inspections_overall_result_index` | `overall_result` | btree | Filter by result |
| 9 | `inspections_scheduled_at_index` | `scheduled_at` | btree | Sort/filter by date range |

### `inspection_results` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `inspection_results_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `inspection_results_inspection_id_index` | `inspection_id` | btree | Find results for an inspection |
| 3 | `inspection_results_item_id_index` | `inspection_item_id` | btree | Find results for an item |
| 4 | `inspection_results_is_unsafe_index` | `is_unsafe` | btree | Find unsafe items quickly |
| 5 | `inspection_results_inspection_id_item_id_unique` | `inspection_id, inspection_item_id` | UNIQUE (btree) | Prevent duplicate result per item per inspection |

---

## 8. Shared Relations

The Inspection Checklist module does **not** duplicate file, comment, log, or workflow tables. All cross-cutting platform services use the **polymorphic `module_name + reference_id`** pattern. For this module:

- `module_name = 'inspection'`
- `reference_id = inspections.id`

### 8.1 Managed Files (`managed_files`)

Evidence photos for an inspection.

| Column | Value |
|---|---|
| `module_name` | `'inspection'` |
| `reference_id` | `inspections.id` |
| `collection` | `'evidence'` |
| `uploaded_by` | `users.id` (FK) |

**Usage**: `Inspection::files()` returns all files where `module_name='inspection'` AND `reference_id=$this->id`.

### 8.2 Comments (`comments`)

Threaded comments on an inspection.

| Column | Value |
|---|---|
| `module_name` | `'inspection'` |
| `reference_id` | `inspections.id` |
| `author_id` | `users.id` (FK) |

### 8.3 Activity Logs (`activity_logs`)

Timeline of actions on an inspection.

| Column | Value |
|---|---|
| `module_name` | `'inspection'` |
| `reference_id` | `inspections.id` |
| `event` | `'created'`, `'started'`, `'completed'`, `'result_saved'`, etc. |

### 8.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes.

| Column | Value |
|---|---|
| `module_name` | `'inspection'` |
| `reference_id` | `inspections.id` |
| `auditable_type` | `'Inspection'` (or model class) |
| `auditable_id` | `inspections.id` |

### 8.5 Workflow Instances (`workflow_instances`)

Each inspection gets a workflow instance.

| Column | Value |
|---|---|
| `module_name` | `'inspection'` |
| `reference_id` | `inspections.id` |
| `current_status` | Mirrors `inspections.status` |
| `completed_at` | Set when inspection is completed |

### 8.6 Workflow Histories (`workflow_histories`)

Every transition logged.

| Column | Value |
|---|---|
| `module_name` | `'inspection'` |
| `reference_id` | `inspections.id` |
| `from_status` | e.g., `'pending'` |
| `to_status` | e.g., `'in_progress'` |
| `action_key` | `'start'`, `'complete'` |

### Shared Relations Summary

```
                          ┌──────────────┐
                          │  inspections  │
                          │  (id: PK)     │
                          └──────┬───────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='inspection'        │
              reference_id=inspections.id     │
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │  comments   │  │ activity_logs  │
    │  (evidence)       │  │ (discussion)│  │  (timeline)    │
    └───────────────────┘  └─────────────┘  └────────────────┘
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐
    │  audit_logs       │  │ workflow_    │
    │  (field changes)  │  │ instances   │
    └───────────────────┘  │ (lifecycle) │
                           └─────┬───────┘
                                 │ 1:N
                           ┌─────▼───────┐
                           │ workflow_   │
                           │ histories   │
                           │ (transitions│
                           │  log)       │
                           └─────────────┘

    All linked via: module_name='inspection' AND reference_id=inspections.id
```

---

## 9. Migration File Naming Convention

### Convention

Migrations follow Laravel's standard naming pattern:

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

### Migration Files for This Module

| # | Migration File | Description |
|---|---|---|
| 1 | `2026_07_11_000001_create_inspection_templates_table.php` | Create templates table |
| 2 | `2026_07_11_000002_create_inspection_items_table.php` | Create items table |
| 3 | `2026_07_11_000003_create_inspections_table.php` | Create inspections table |
| 4 | `2026_07_11_000004_create_inspection_results_table.php` | Create results table |

### Model File Locations

```
app/Models/Modules/Inspection/
├── InspectionTemplate.php
├── InspectionItem.php
├── Inspection.php
└── InspectionResult.php
```

### Factory File Locations

```
database/factories/Modules/Inspection/
├── InspectionTemplateFactory.php
├── InspectionItemFactory.php
├── InspectionFactory.php
└── InspectionResultFactory.php
```

# Data Model — Asset & Equipment Safety

> Phase 17 schema for the Asset & Equipment Safety module.
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs).

---

## 1. Table of Contents

1. [Main Table: `assets`](#2-main-table-assets)
2. [Child Table: `asset_certificates`](#3-child-table-asset_certificates)
3. [Child Table: `asset_inspections`](#4-child-table-asset_inspections)
4. [ERD Diagram (ASCII)](#5-erd-diagram-ascii)
5. [Index Specifications](#6-index-specifications)
6. [Shared Relations](#7-shared-relations)
7. [Migration File Naming Convention](#8-migration-file-naming-convention)

---

## 2. Main Table: `assets`

Stores the core asset record — equipment, machinery, vehicle, safety equipment, fire equipment, lifting, or other.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `asset_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `AST-{YYYY}-{0001}`) |
| 3 | `name` | `varchar(255)` | NO | — | Name of the asset |
| 4 | `category` | `varchar(30)` | NO | — | **Check constraint** enum: `equipment`, `machinery`, `vehicle`, `safety_equipment`, `fire_equipment`, `lifting`, `other` |
| 5 | `serial_number` | `varchar(255)` | YES | `NULL` | Manufacturer serial number |
| 6 | `model` | `varchar(255)` | YES | `NULL` | Model name/number |
| 7 | `manufacturer` | `varchar(255)` | YES | `NULL` | Manufacturer name |
| 8 | `site_id` | `bigint` | NO | — | **FK → `sites.id`**. Site where asset is located |
| 9 | `area_id` | `bigint` | YES | `NULL` | **FK → `areas.id`**. Specific area within site |
| 10 | `department_id` | `bigint` | YES | `NULL` | **FK → `departments.id`**. Department that owns the asset |
| 11 | `purchase_date` | `date` | YES | `NULL` | Date of purchase |
| 12 | `installation_date` | `date` | YES | `NULL` | Date of installation/commissioning |
| 13 | `warranty_expiry` | `date` | YES | `NULL` | Warranty expiry date |
| 14 | `status` | `varchar(30)` | NO | `'active'` | Lifecycle state: `active`, `inactive`, `decommissioned` |
| 15 | `safety_critical` | `boolean` | NO | `false` | Flag for safety-critical assets |
| 16 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 17 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE assets (
    id                  BIGSERIAL       PRIMARY KEY,
    asset_number        VARCHAR(50)     NOT NULL UNIQUE,
    name                VARCHAR(255)    NOT NULL,
    category            VARCHAR(30)     NOT NULL,
    serial_number       VARCHAR(255)    NULL,
    model               VARCHAR(255)    NULL,
    manufacturer        VARCHAR(255)    NULL,
    site_id             BIGINT          NOT NULL REFERENCES sites(id),
    area_id             BIGINT          NULL REFERENCES areas(id),
    department_id       BIGINT          NULL REFERENCES departments(id),
    purchase_date       DATE            NULL,
    installation_date   DATE            NULL,
    warranty_expiry     DATE            NULL,
    status              VARCHAR(30)     NOT NULL DEFAULT 'active',
    safety_critical     BOOLEAN         NOT NULL DEFAULT false,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT assets_category_check CHECK (
        category IN ('equipment', 'machinery', 'vehicle', 'safety_equipment', 'fire_equipment', 'lifting', 'other')
    ),

    CONSTRAINT assets_status_check CHECK (
        status IN ('active', 'inactive', 'decommissioned')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('assets', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('asset_number', 50)->unique();
    $table->string('name', 255);
    $table->string('category', 30);
    $table->string('serial_number', 255)->nullable();
    $table->string('model', 255)->nullable();
    $table->string('manufacturer', 255)->nullable();
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('area_id')->nullable()->constrained('areas');
    $table->foreignId('department_id')->nullable()->constrained('departments');
    $table->date('purchase_date')->nullable();
    $table->date('installation_date')->nullable();
    $table->date('warranty_expiry')->nullable();
    $table->string('status', 30)->default('active');
    $table->boolean('safety_critical')->default(false);
    $table->timestamps();

    // Check constraint for category enum
    $table->check("category IN ('equipment','machinery','vehicle','safety_equipment','fire_equipment','lifting','other')", 'assets_category_check');

    // Check constraint for status enum
    $table->check("status IN ('active','inactive','decommissioned')", 'assets_status_check');
});
```

### Design Notes

- **No soft deletes** (`deleted_at`) — asset records use status lifecycle (`decommissioned`) instead. Soft deletes can be added if regulatory retention requires it.
- **`asset_number`** is generated at create time via `NumberingService::generate('asset', ...)`. Unique constraint prevents duplicates.
- **`category`** is stored as `varchar` with CHECK constraint — 7 values covering all asset types.
- **`serial_number`**, **`model`**, **`manufacturer`** are nullable — not all assets have these (e.g., generic safety equipment).
- **`area_id`** nullable — some assets are site-wide and not area-specific.
- **`department_id`** nullable — some assets are not department-specific.
- **`safety_critical`** boolean flag — drives visual highlighting and notification priority.
- **`status`** has 3 values: `active` (default), `inactive`, `decommissioned`. Decommissioned locks the record from further edits.

---

## 3. Child Table: `asset_certificates`

Stores certificate records for assets. Each certificate tracks issuance and expiry dates for compliance.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `asset_id` | `bigint` | NO | — | **FK → `assets.id`**, `ON DELETE CASCADE` |
| 3 | `certificate_type` | `varchar(100)` | NO | — | Type of certificate (e.g., "Sertifikat Kalibrasi", "Sertifikat K3", "Surat Kelayakan Operasi") |
| 4 | `certificate_number` | `varchar(100)` | NO | — | Official certificate number |
| 5 | `issued_date` | `date` | NO | — | Date certificate was issued |
| 6 | `expiry_date` | `date` | YES | `NULL` | Expiry date. NULL = no expiry (permanent) |
| 7 | `issuing_body` | `varchar(255)` | NO | — | Organization that issued the certificate |
| 8 | `certificate_file_id` | `bigint` | YES | `NULL` | **FK → `managed_files.id`** (nullable). Uploaded certificate file |
| 9 | `status` | `varchar(30)` | NO | `'valid'` | Auto-calculated: `valid`, `expiring_soon`, `expiring_critical`, `expired` |
| 10 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 11 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE asset_certificates (
    id                      BIGSERIAL       PRIMARY KEY,
    asset_id                BIGINT          NOT NULL REFERENCES assets(id) ON DELETE CASCADE,
    certificate_type        VARCHAR(100)    NOT NULL,
    certificate_number      VARCHAR(100)    NOT NULL,
    issued_date             DATE            NOT NULL,
    expiry_date             DATE            NULL,
    issuing_body            VARCHAR(255)    NOT NULL,
    certificate_file_id     BIGINT          NULL REFERENCES managed_files(id),
    status                  VARCHAR(30)     NOT NULL DEFAULT 'valid',
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT asset_certificates_status_check CHECK (
        status IN ('valid', 'expiring_soon', 'expiring_critical', 'expired')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('asset_certificates', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
    $table->string('certificate_type', 100);
    $table->string('certificate_number', 100);
    $table->date('issued_date');
    $table->date('expiry_date')->nullable();
    $table->string('issuing_body', 255);
    $table->foreignId('certificate_file_id')->nullable()->constrained('managed_files');
    $table->string('status', 30)->default('valid');
    $table->timestamps();

    // Check constraint for status enum
    $table->check("status IN ('valid','expiring_soon','expiring_critical','expired')", 'asset_certificates_status_check');
});
```

### Design Notes

- **Cascade delete** on `asset_id` — when an asset is deleted, all its certificates go with it.
- **`certificate_type`** is free-text (not FK) to allow flexibility (e.g., "Sertifikat Kalibrasi", "Surat Kelayakan", "SIO Pengangkatan", etc.).
- **`expiry_date`** is nullable — some certificates are permanent (no expiry).
- **`certificate_file_id`** is nullable FK to `managed_files`. Stores the uploaded PDF/image of the certificate.
- **`status`** is auto-calculated by `AssetCertificateStatusJob` (scheduled daily):
  - `expired` — `expiry_date < now()`
  - `expiring_critical` — `expiry_date` within 1–7 days
  - `expiring_soon` — `expiry_date` within 8–30 days
  - `valid` — `expiry_date >= now() + 30 days` OR `expiry_date IS NULL`
- No soft deletes — certificates are part of the asset record permanently.

---

## 4. Child Table: `asset_inspections`

Stores inspection records for assets. Each inspection records the result and schedules the next inspection.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `asset_id` | `bigint` | NO | — | **FK → `assets.id`**, `ON DELETE CASCADE` |
| 3 | `inspection_date` | `date` | NO | — | Date the inspection was performed |
| 4 | `inspector_id` | `bigint` | NO | — | **FK → `users.id`**. User who performed the inspection |
| 5 | `result` | `varchar(30)` | NO | — | **Check constraint** enum: `pass`, `fail`, `maintenance_required` |
| 6 | `notes` | `text` | YES | `NULL` | Inspection notes/observations |
| 7 | `next_inspection_date` | `date` | YES | `NULL` | Scheduled date for next inspection |
| 8 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 9 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE asset_inspections (
    id                      BIGSERIAL       PRIMARY KEY,
    asset_id                BIGINT          NOT NULL REFERENCES assets(id) ON DELETE CASCADE,
    inspection_date         DATE            NOT NULL,
    inspector_id            BIGINT          NOT NULL REFERENCES users(id),
    result                  VARCHAR(30)     NOT NULL,
    notes                   TEXT            NULL,
    next_inspection_date    DATE            NULL,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT asset_inspections_result_check CHECK (
        result IN ('pass', 'fail', 'maintenance_required')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('asset_inspections', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
    $table->date('inspection_date');
    $table->foreignId('inspector_id')->constrained('users');
    $table->string('result', 30);
    $table->text('notes')->nullable();
    $table->date('next_inspection_date')->nullable();
    $table->timestamps();

    // Check constraint for result enum
    $table->check("result IN ('pass','fail','maintenance_required')", 'asset_inspections_result_check');
});
```

### Design Notes

- **Cascade delete** on `asset_id` — when an asset is deleted, all its inspections go with it.
- **`inspector_id`** is FK to `users` — the user who performed the inspection.
- **`result`** uses CHECK constraint with 3 values: `pass`, `fail`, `maintenance_required`.
- **`next_inspection_date`** is nullable — not all inspections schedule a next one (e.g., final inspection before decommission).
- **CAPA linkage** — inspections with `result='fail'` can be linked to CAPA actions. The linkage is stored in `capa_actions` table with `source_module='asset_inspection'` and `source_reference_id=asset_inspections.id`. No direct FK column on `asset_inspections` to keep the table clean; the link is one-directional from CAPA side.
- No soft deletes — inspections are part of the asset record permanently.

---

## 5. ERD Diagram (ASCII)

```
┌─────────────────────┐         ┌──────────────────────────┐         ┌─────────────────────┐
│      sites           │         │        assets             │         │    departments      │
├─────────────────────┤         ├──────────────────────────┤         ├─────────────────────┤
│ id          BIGINT PK│◄──┐    │ id            BIGINT PK    │──┐    │ id          BIGINT PK │
│ code        VARCHAR   │   │    │ asset_number  VARCHAR(50)  │  │    │ code        VARCHAR  │
│ name        VARCHAR   │   │    │ name          VARCHAR(255) │  │    │ name        VARCHAR  │
│ address     TEXT      │   │    │ category      VARCHAR(30)  │  │    │ site_id     BIGINT FK│──► sites
│ is_active   BOOLEAN   │   │    │ serial_number VARCHAR(255) │  │    │ is_active   BOOLEAN  │
└─────────────────────┘   │    │ model         VARCHAR(255)  │  │    └─────────────────────┘
                          │    │ manufacturer  VARCHAR(255)  │  │
                          │    │ site_id       BIGINT FK ────┼──┘
                          │    │ area_id       BIGINT FK ────┼──────► areas
                          │    │ department_id BIGINT FK ────┼──────► departments
                          │    │ purchase_date DATE           │  │
                          │    │ installation_date DATE      │  │
                          │    │ warranty_expiry DATE        │  │
                          │    │ status        VARCHAR(30)   │  │
                          │    │ safety_critical BOOLEAN     │  │
                          │    │ created_at    TIMESTAMP      │  │
                          │    │ updated_at    TIMESTAMP      │  │
                          │    └──────────────────────────┘   │
                          │              │  ▲                  │
                          │              │  │ 1:N              │
                          │     ┌────────┘  │           ┌──────┘
                          │     │           │           │
                          │     ▼           │           ▼
                          │  ┌──────────────────────┐  ┌──────────────────────┐
                          │  │  asset_certificates   │  │  asset_inspections    │
                          │  ├──────────────────────┤  ├──────────────────────┤
                          │  │ id            BIGINT PK│  │ id            BIGINT PK│
                          │  │ asset_id      BIGINT FK│  │ asset_id      BIGINT FK│
                          │  │ certificate_type        │  │ inspection_date DATE   │
                          │  │ certificate_number     │  │ inspector_id  BIGINT FK│──► users
                          │  │ issued_date  DATE       │  │ result        VARCHAR  │
                          │  │ expiry_date  DATE       │  │ notes         TEXT     │
                          │  │ issuing_body VARCHAR    │  │ next_inspection_date   │
                          │  │ certificate_file_id FK │  │ created_at    TIMESTAMP│
                          │  │ status        VARCHAR  │  │ updated_at    TIMESTAMP│
                          │  │ created_at    TIMESTAMP│  └──────────────────────┘
                          │  │ updated_at    TIMESTAMP│           │
                          │  └──────────────────────┘           │
                          │           │                         │ N:1
                          │           │ N:1                     │ (source_reference_id)
                          │           ▼                         ▼
                          │  ┌──────────────────────┐  ┌──────────────────────┐
                          │  │  managed_files        │  │  capa_actions         │
                          │  ├──────────────────────┤  ├──────────────────────┤
                          │  │ id            BIGINT PK│  │ id            BIGINT PK│
                          │  │ module_name   VARCHAR  │  │ capa_number   VARCHAR  │
                          │  │ reference_id BIGINT   │  │ source_module VARCHAR  │
                          │  │ collection    VARCHAR  │  │ source_reference_id    │
                          │  │ disk/path/...          │  │ = 'asset_inspection'   │
                          │  └──────────────────────┘  │ = asset_inspections.id  │
                          │                              └──────────────────────┘
                          │
                          └── (site_id references sites)

                ┌──────────────────────┐         ┌──────────────────────┐
                │       users           │         │       areas          │
                ├──────────────────────┤         ├──────────────────────┤
                │ id          BIGINT PK │◄── (inspector_id) │ id          BIGINT PK │
                │ name        VARCHAR   │         │ site_id     BIGINT FK│──► sites
                │ email       VARCHAR   │         │ code        VARCHAR  │
                │ is_active   BOOLEAN   │         │ name        VARCHAR  │
                └──────────────────────┘         │ is_active   BOOLEAN  │
                                                  └──────────────────────┘
```

### Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `sites` | `assets` | `site_id` | 1:N | RESTRICT (default) |
| `areas` | `assets` | `area_id` | 1:N | SET NULL |
| `departments` | `assets` | `department_id` | 1:N | SET NULL |
| `assets` | `asset_certificates` | `asset_id` | 1:N | CASCADE |
| `assets` | `asset_inspections` | `asset_id` | 1:N | CASCADE |
| `users` | `asset_inspections` | `inspector_id` | 1:N | RESTRICT (default) |
| `managed_files` | `asset_certificates` | `certificate_file_id` | 1:N | SET NULL |

---

## 6. Index Specifications

### `assets` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `assets_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `assets_asset_number_unique` | `asset_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `assets_site_id_index` | `site_id` | btree | Filter assets by site |
| 4 | `assets_area_id_index` | `area_id` | btree | Filter assets by area |
| 5 | `assets_department_id_index` | `department_id` | btree | Filter assets by department |
| 6 | `assets_status_index` | `status` | btree | Filter/list by status |
| 7 | `assets_category_index` | `category` | btree | Filter by category |
| 8 | `assets_safety_critical_index` | `safety_critical` | btree | Filter safety-critical assets |
| 9 | `assets_created_at_index` | `created_at` | btree | Sort by creation date |

### `asset_certificates` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `asset_certificates_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `asset_certificates_asset_id_index` | `asset_id` | btree | Find all certificates for an asset |
| 3 | `asset_certificates_status_index` | `status` | btree | Filter by status (valid/expiring/expired) |
| 4 | `asset_certificates_expiry_date_index` | `expiry_date` | btree | Scheduled job queries for expiry tracking |
| 5 | `asset_certificates_certificate_file_id_index` | `certificate_file_id` | btree | Find certificates with attached files |

### `asset_inspections` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `asset_inspections_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `asset_inspections_asset_id_index` | `asset_id` | btree | Find all inspections for an asset |
| 3 | `asset_inspections_inspector_id_index` | `inspector_id` | btree | List inspections by inspector |
| 4 | `asset_inspections_result_index` | `result` | btree | Filter by result (pass/fail/maintenance_required) |
| 5 | `asset_inspections_inspection_date_index` | `inspection_date` | btree | Sort/filter by date range |
| 6 | `asset_inspections_next_inspection_date_index` | `next_inspection_date` | btree | Scheduled job queries for due inspections |

### Laravel Migration Indexes

```php
// assets table
$table->index('site_id');
$table->index('area_id');
$table->index('department_id');
$table->index('status');
$table->index('category');
$table->index('safety_critical');
$table->index('created_at');

// asset_certificates table
$table->index('asset_id');
$table->index('status');
$table->index('expiry_date');
$table->index('certificate_file_id');

// asset_inspections table
$table->index('asset_id');
$table->index('inspector_id');
$table->index('result');
$table->index('inspection_date');
$table->index('next_inspection_date');
```

---

## 7. Shared Relations

The Asset & Equipment Safety module uses the **polymorphic `module_name + reference_id`** pattern for all cross-cutting platform services:

- `module_name = 'asset'`
- `reference_id = assets.id` (for asset-level shared data)
- `reference_id = asset_certificates.id` with `module_name = 'asset_certificate'` (for certificate-level shared data, if needed)
- `reference_id = asset_inspections.id` with `module_name = 'asset_inspection'` (for inspection-level shared data)

### 7.1 Managed Files (`managed_files`)

File attachments (certificate files, asset photos) for an asset.

| Column | Value |
|---|---|
| `module_name` | `'asset'` |
| `reference_id` | `assets.id` |
| `collection` | `'certificate'`, `'attachment'` |
| `uploaded_by` | `users.id` (FK) |

```
assets.id ──► managed_files.reference_id
                managed_files.module_name = 'asset'
```

**Usage**: `Asset::files()` returns all files where `module_name='asset'` AND `reference_id=$this->id`.

### 7.2 Comments (`comments`)

Threaded comments / discussion on an asset.

| Column | Value |
|---|---|
| `module_name` | `'asset'` |
| `reference_id` | `assets.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` — internal-only vs visible |

### 7.3 Activity Logs (`activity_logs`)

Timeline of actions performed on an asset (created, updated, certificate added, inspection created, etc.).

| Column | Value |
|---|---|
| `module_name` | `'asset'` |
| `reference_id` | `assets.id` |
| `event` | `'created'`, `'updated'`, `'certificate.created'`, `'certificate.expired'`, `'inspection.created'`, `'inspection.capa_linked'`, etc. |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot, metadata) |

### 7.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes on asset, certificate, and inspection records.

| Column | Value |
|---|---|
| `module_name` | `'asset'` |
| `reference_id` | `assets.id` |
| `auditable_type` | `'Asset'`, `'AssetCertificate'`, or `'AssetInspection'` |
| `auditable_id` | `assets.id`, `asset_certificates.id`, or `asset_inspections.id` |
| `old_values` | JSON (previous field values) |
| `new_values` | JSON (new field values) |
| `actor_id` | `users.id` (FK) |

### 7.5 Cross-Module: CAPA Linkage

Inspections with `result='fail'` can be linked to CAPA actions. The link is stored in the `capa_actions` table (module 04):

| Column | Value |
|---|---|
| `source_module` | `'asset_inspection'` |
| `source_reference_id` | `asset_inspections.id` |
| `capa_number` | `ACT-YYYY-NNNN` |

```
asset_inspections.id ──► capa_actions.source_reference_id
                          capa_actions.source_module = 'asset_inspection'
```

No hard FK from `asset_inspections` to `capa_actions` — application-layer validated polymorphic relation.

### Shared Relations Summary

```
                          ┌──────────────┐
                          │   assets     │
                          │  (id: PK)    │
                          └──────┬───────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='asset'              │
              reference_id=assets.id          │
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │  comments   │  │ activity_logs  │
    │  (certificate,    │  │ (discussion)│  │  (timeline)    │
    │   attachment)     │  │             │  │                │
    └───────────────────┘  └─────────────┘  └────────────────┘
                    │            │            │
    ┌───────────────▼──┐
    │  audit_logs      │
    │  (field changes) │
    └──────────────────┘

                    ┌──────────────────┐
                    │  asset_certificates│
                    │  (asset_id: FK)   │
                    └────────┬─────────┘
                             │
                     certificate_file_id (FK → managed_files)

                    ┌──────────────────┐
                    │  asset_inspections │
                    │  (asset_id: FK)    │
                    └────────┬─────────┘
                             │
                     source_reference_id in capa_actions
                     (source_module = 'asset_inspection')
                     Cross-module link to CAPA module (04)
```

---

## 8. Migration File Naming Convention

### Convention

Migrations follow Laravel's standard naming pattern:

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

### Migration Files for This Module

| # | File Name | Description |
|---|---|---|
| 1 | `2026_07_11_000001_create_assets_table.php` | Main assets table |
| 2 | `2026_07_11_000002_create_asset_certificates_table.php` | Asset certificates table |
| 3 | `2026_07_11_000003_create_asset_inspections_table.php` | Asset inspections table |

### Seeder

| # | File Name | Description |
|---|---|---|
| 1 | `AssetEquipmentSafetySeeder.php` | Seeds numbering format (already seeded), permissions, role mappings, sample data |

### Model Locations

```
app/Models/Modules/Asset/
    Asset.php
    AssetCertificate.php
    AssetInspection.php
```

### Factory Locations

```
database/factories/Modules/Asset/
    AssetFactory.php
    AssetCertificateFactory.php
    AssetInspectionFactory.php
```

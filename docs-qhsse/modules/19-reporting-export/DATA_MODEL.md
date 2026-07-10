# Data Model — Reporting & Export

> Phase 5 schema for the Reporting module.
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, JSON config columns, and the shared `module_name + reference_id` polymorphic pattern for file storage.

---

## 1. Table of Contents

1. [Table: `report_templates`](#2-table-report_templates)
2. [Table: `saved_reports`](#3-table-saved_reports)
3. [ERD Diagram (ASCII)](#4-erd-diagram-ascii)
4. [Index Specifications](#5-index-specifications)
5. [Shared Relations](#6-shared-relations)
6. [Migration File Naming Convention](#7-migration-file-naming-convention)

---

## 2. Table: `report_templates`

Stores report template definitions — both pre-defined (seeded) and custom templates created by QHSSE Manager/Admin.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `name` | `varchar(255)` | NO | — | Nama template laporan |
| 3 | `type` | `varchar(50)` | NO | — | Tipe: `incident_summary`, `capa_summary`, `inspection_summary`, `audit_summary`, `training_compliance`, `monthly_qhsse`, `annual_qhsse`, `custom` |
| 4 | `config` | `json` | NO | `'{}'` | Konfigurasi template (sections, data sources, default parameters) |
| 5 | `description` | `text` | YES | `NULL` | Deskripsi template |
| 6 | `is_active` | `boolean` | NO | `true` | Status aktif template |
| 7 | `created_by` | `bigint` | NO | — | **FK → `users.id`**. User yang membuat template |
| 8 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 9 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE report_templates (
    id            BIGSERIAL       PRIMARY KEY,
    name          VARCHAR(255)    NOT NULL,
    type          VARCHAR(50)     NOT NULL,
    config        JSON            NOT NULL DEFAULT '{}',
    description   TEXT            NULL,
    is_active     BOOLEAN         NOT NULL DEFAULT true,
    created_by    BIGINT          NOT NULL REFERENCES users(id),
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT report_templates_type_check CHECK (
        type IN (
            'incident_summary',
            'capa_summary',
            'inspection_summary',
            'audit_summary',
            'training_compliance',
            'monthly_qhsse',
            'annual_qhsse',
            'custom'
        )
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('report_templates', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('name', 255);
    $table->string('type', 50);
    $table->json('config')->default('{}');
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    // Indexes
    $table->index('type');
    $table->index('is_active');
    $table->index('created_by');
    $table->unique(['type', 'name']);
});
```

### Design Notes

- **No soft deletes** — template yang sudah dipakai oleh saved_reports tidak boleh hilang. Non-aktifkan dengan `is_active = false`.
- **`type`** menggunakan varchar dengan CHECK constraint, bukan PostgreSQL enum — untuk kemudahan penambahan tipe baru.
- **`config`** JSON menyimpan struktur sections, data sources, dan default parameters. Lihat MODULE_SPEC.md BR-10 untuk struktur JSON.
- **Pre-defined templates** di-seed oleh `ReportTemplateSeeder` dengan config yang sudah ditentukan.
- **`unique(['type', 'name'])`** — mencegah duplikat nama untuk tipe yang sama.

---

## 3. Table: `saved_reports`

Stores generated report instances — records of report generation requests with their parameters, status, and file output.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `report_template_id` | `bigint` | NO | — | **FK → `report_templates.id`**. Template yang dipakai |
| 3 | `name` | `varchar(255)` | NO | — | Nama laporan (user input atau auto-generated) |
| 4 | `parameters` | `json` | NO | `'{}'` | Parameter saat generate: date_from, date_to, site_id, department_id, format, include_charts |
| 5 | `generated_by` | `bigint` | NO | — | **FK → `users.id`**. User yang meminta generate |
| 6 | `generated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Waktu generate diminta |
| 7 | `file_path` | `varchar(500)` | YES | `NULL` | Path file hasil generate. NULL jika gagal atau belum selesai. |
| 8 | `format` | `varchar(10)` | NO | `'csv'` | Format output: `csv`, `pdf`, `excel` |
| 9 | `status` | `varchar(20)` | NO | `'pending'` | Status: `pending`, `processing`, `completed`, `failed` |
| 10 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 11 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE saved_reports (
    id                  BIGSERIAL       PRIMARY KEY,
    report_template_id  BIGINT          NOT NULL REFERENCES report_templates(id),
    name                VARCHAR(255)    NOT NULL,
    parameters          JSON            NOT NULL DEFAULT '{}',
    generated_by        BIGINT          NOT NULL REFERENCES users(id),
    generated_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    file_path           VARCHAR(500)    NULL,
    format              VARCHAR(10)     NOT NULL DEFAULT 'csv',
    status              VARCHAR(20)     NOT NULL DEFAULT 'pending',
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT saved_reports_format_check CHECK (
        format IN ('csv', 'pdf', 'excel')
    ),
    CONSTRAINT saved_reports_status_check CHECK (
        status IN ('pending', 'processing', 'completed', 'failed')
    ),
    CONSTRAINT saved_reports_file_path_check CHECK (
        (status IN ('completed') AND file_path IS NOT NULL)
        OR
        (status IN ('pending', 'processing', 'failed') AND file_path IS NULL)
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('saved_reports', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('report_template_id')->constrained('report_templates');
    $table->string('name', 255);
    $table->json('parameters')->default('{}');
    $table->foreignId('generated_by')->constrained('users');
    $table->timestamp('generated_at')->useCurrent();
    $table->string('file_path', 500)->nullable();
    $table->string('format', 10)->default('csv');
    $table->string('status', 20)->default('pending');
    $table->timestamps();

    // Indexes
    $table->index('report_template_id');
    $table->index('generated_by');
    $table->index('status');
    $table->index('format');
    $table->index('generated_at');
    $table->index(['status', 'generated_at']);
});
```

### Design Notes

- **No soft deletes** — saved_reports dihapus secara permanent oleh Admin/QHSSE Manager. File juga dihapus dari disk.
- **`parameters`** JSON menyimpan: `date_from`, `date_to`, `site_id`, `department_id`, `format`, `include_charts`, dan optional `error` (jika failed).
- **`file_path`** nullable — NULL saat status `pending` atau `processing`. Diisi saat `completed`.
- **`status` CHECK constraint** memastikan konsistensi: `file_path` hanya NOT NULL saat `completed`.
- **`generated_at`** berbeda dari `created_at` — `generated_at` adalah waktu user request generate, `created_at` adalah waktu record dibuat (biasanya sama, tapi berbeda jika ada retry).
- Tidak ada kolom `error_message` — error disimpan di `parameters.error` JSON field untuk fleksibilitas.

### Parameters JSON Structure

```json
{
  "date_from": "2026-01-01",
  "date_to": "2026-01-31",
  "site_id": 1,
  "department_id": null,
  "format": "pdf",
  "include_charts": true,
  "error": null
}
```

Jika report gagal, `error` diisi:

```json
{
  "date_from": "2026-01-01",
  "date_to": "2026-01-31",
  "site_id": 1,
  "department_id": null,
  "format": "pdf",
  "include_charts": true,
  "error": "Connection timeout saat meng-query data inspection."
}
```

---

## 4. ERD Diagram (ASCII)

```
┌─────────────────────────────┐         ┌──────────────────────────────┐         ┌─────────────────────┐
│       report_templates       │         │         saved_reports         │         │       users          │
├─────────────────────────────┤         ├──────────────────────────────┤         ├─────────────────────┤
│ id           BIGINT PK       │◄──┐    │ id                BIGINT PK   │──┐     │ id          BIGINT PK │
│ name         VARCHAR(255)    │   │    │ report_template_id BIGINT FK │  │     │ name        VARCHAR  │
│ type         VARCHAR(50)     │   │    │ name              VARCHAR(255)│  │     │ email       VARCHAR  │
│ config       JSON            │   │    │ parameters        JSON        │  │     │ is_active   BOOLEAN  │
│ description  TEXT            │   │    │ generated_by      BIGINT FK   │──┼─┼──►│ (created_by FK)      │
│ is_active    BOOLEAN         │   │    │ generated_at      TIMESTAMP   │  │     └─────────────────────┘
│ created_by   BIGINT FK ──────│───┼──► │ file_path         VARCHAR(500)│  │
│ created_at   TIMESTAMP       │   │    │ format            VARCHAR(10) │  │
│ updated_at   TIMESTAMP       │   │    │ status            VARCHAR(20) │  │
└─────────────────────────────┘   │    │ created_at        TIMESTAMP   │  │
                                  │    │ updated_at        TIMESTAMP   │  │
                                  │    └──────────────────────────────┘  │
                                  └──────────────────────────────────────┘
                                                                              │
                                                                              │
                    ┌────────────────────────────────────────────────────────┘
                    │
                    ▼
          ┌──────────────────────────┐
          │  managed_files            │
          │  (module_name='reporting')│
          │  reference_id=saved_reports.id
          │  collection='generated_report'
          └──────────────────────────┘
                    │
                    ▼
          ┌──────────────────────────┐
          │  activity_logs           │
          │  (module_name='reporting')│
          │  reference_id=saved_reports.id
          └──────────────────────────┘
                    │
                    ▼
          ┌──────────────────────────┐
          │  audit_logs              │
          │  (module_name='reporting')│
          │  reference_id=saved_reports.id
          └──────────────────────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `users` | `report_templates` | `created_by` | 1:N | RESTRICT |
| `report_templates` | `saved_reports` | `report_template_id` | 1:N | RESTRICT |
| `users` | `saved_reports` | `generated_by` | 1:N | RESTRICT |

### Cross-Module Data Aggregation (Read-Only)

```
                    ┌──────────────────────┐
                    │     saved_reports     │
                    │  (report_template_id) │
                    │  (parameters JSON)    │
                    └──────┬───────────────┘
                           │
              ┌────────────┼────────────────────────────┐
              │            │                            │
              ▼            ▼                            ▼
    ┌─────────────┐ ┌─────────────┐            ┌─────────────┐
    │  incidents  │ │ capa_actions│            │ inspections │
    │  (read-only)│ │ (read-only) │            │ (read-only) │
    └─────────────┘ └─────────────┘            └─────────────┘
              │            │                            │
              ▼            ▼                            ▼
    ┌─────────────┐ ┌─────────────┐            ┌─────────────┐
    │audit_findings│ │training_rec.│           │... ALL modul│
    │ (read-only) │ │ (read-only) │            │ (read-only) │
    └─────────────┘ └─────────────┘            └─────────────┘

    GenerateReportJob membaca data dari semua modul
    berdasarkan parameters (date range, site, department).
    Tidak ada FK ke tabel modul — hanya read-only query.
```

---

## 5. Index Specifications

### `report_templates` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `report_templates_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `report_templates_type_name_unique` | `type, name` | UNIQUE (btree) | Prevent duplicate names per type |
| 3 | `report_templates_type_index` | `type` | btree | Filter by report type |
| 4 | `report_templates_is_active_index` | `is_active` | btree | Filter active templates only |
| 5 | `report_templates_created_by_index` | `created_by` | btree | Find templates by creator |

### `saved_reports` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `saved_reports_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `saved_reports_report_template_id_index` | `report_template_id` | btree | Find reports by template |
| 3 | `saved_reports_generated_by_index` | `generated_by` | btree | Find reports by generator |
| 4 | `saved_reports_status_index` | `status` | btree | Filter by status (pending/processing/completed/failed) |
| 5 | `saved_reports_format_index` | `format` | btree | Filter by format (csv/pdf/excel) |
| 6 | `saved_reports_generated_at_index` | `generated_at` | btree | Sort by generation date |
| 7 | `saved_reports_status_generated_at_index` | `status, generated_at` | btree (composite) | Dashboard: find pending/processing reports sorted by date |

### Status Query Pattern

```sql
-- Efficient dashboard query: find pending and processing reports
SELECT * FROM saved_reports
WHERE status IN ('pending', 'processing')
ORDER BY generated_at ASC;
```

```php
// Laravel equivalent
SavedReport::whereIn('status', ['pending', 'processing'])
    ->orderBy('generated_at', 'asc')
    ->get();
```

---

## 6. Shared Relations

The Reporting module uses the **polymorphic `module_name + reference_id`** pattern for file storage and logging.

- `module_name = 'reporting'`
- `reference_id = saved_reports.id`

### 6.1 Managed Files (`managed_files`)

| Column | Value |
|---|---|
| `module_name` | `'reporting'` |
| `reference_id` | `saved_reports.id` |
| `collection` | `'generated_report'` |
| `uploaded_by` | `users.id` (FK) — system user or `generated_by` |

```
saved_reports.id ──► managed_files.reference_id
                      managed_files.module_name = 'reporting'
                      managed_files.collection = 'generated_report'
```

**Usage**: `SavedReport::file()` returns the generated report file. One file per saved_report (1:1).

### 6.2 Activity Logs (`activity_logs`)

| Column | Value |
|---|---|
| `module_name` | `'reporting'` |
| `reference_id` | `saved_reports.id` (atau `report_templates.id`) |
| `event` | `'report.generated'`, `'report.completed'`, `'report.failed'`, `'report.downloaded'`, `'template.created'`, `'template.updated'` |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON |

### 6.3 Audit Logs (`audit_logs`)

| Column | Value |
|---|---|
| `module_name` | `'reporting'` |
| `reference_id` | `saved_reports.id` (atau `report_templates.id`) |
| `auditable_type` | `'SavedReport'` atau `'ReportTemplate'` |
| `auditable_id` | `saved_reports.id` atau `report_templates.id` |
| `old_values` | JSON |
| `new_values` | JSON |
| `actor_id` | `users.id` (FK) |

### Shared Relations Summary

```
                          ┌──────────────────┐
                          │   saved_reports   │
                          │   (id: PK)       │
                          └──────┬───────────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='reporting'         │
              reference_id=saved_reports.id   │
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │ activity_   │  │  audit_logs    │
    │  (generated_      │  │ logs        │  │  (field        │
    │   report file)    │  │ (timeline)  │  │   changes)     │
    └───────────────────┘  └─────────────┘  └────────────────┘

    report_templates juga menggunakan module_name='reporting'
    untuk activity_logs dan audit_logs
    (reference_id = report_templates.id)
```

---

## 7. Migration File Naming Convention

### Convention

Migrations follow Laravel's standard naming pattern with module-prefixed descriptions:

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

### Expected Migration Files

```
database/migrations/2026_07_20_000000_create_report_templates_table.php
database/migrations/2026_07_20_000001_create_saved_reports_table.php
```

### Eloquent Models

File: `app/Models/Modules/Reporting/ReportTemplate.php`

```php
<?php

namespace App\Models\Modules\Reporting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    protected $table = 'report_templates';

    protected $fillable = [
        'name',
        'type',
        'config',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    // Constants for report types
    public const TYPE_INCIDENT_SUMMARY = 'incident_summary';
    public const TYPE_CAPA_SUMMARY = 'capa_summary';
    public const TYPE_INSPECTION_SUMMARY = 'inspection_summary';
    public const TYPE_AUDIT_SUMMARY = 'audit_summary';
    public const TYPE_TRAINING_COMPLIANCE = 'training_compliance';
    public const TYPE_MONTHLY_QHSSE = 'monthly_qhsse';
    public const TYPE_ANNUAL_QHSSE = 'annual_qhsse';
    public const TYPE_CUSTOM = 'custom';

    public const PREDEFINED_TYPES = [
        self::TYPE_INCIDENT_SUMMARY,
        self::TYPE_CAPA_SUMMARY,
        self::TYPE_INSPECTION_SUMMARY,
        self::TYPE_AUDIT_SUMMARY,
        self::TYPE_TRAINING_COMPLIANCE,
        self::TYPE_MONTHLY_QHSSE,
        self::TYPE_ANNUAL_QHSSE,
    ];

    public static function typeLabels(): array
    {
        return [
            'incident_summary'     => 'Ringkasan Insiden',
            'capa_summary'         => 'Ringkasan CAPA',
            'inspection_summary'   => 'Ringkasan Inspection',
            'audit_summary'        => 'Ringkasan Audit',
            'training_compliance'  => 'Kepatuhan Training',
            'monthly_qhsse'        => 'Laporan Bulanan QHSSE',
            'annual_qhsse'         => 'Laporan Tahunan QHSSE',
            'custom'               => 'Laporan Custom',
        ];
    }

    public function isPredefined(): bool
    {
        return in_array($this->type, self::PREDEFINED_TYPES);
    }

    public function isCustom(): bool
    {
        return $this->type === self::TYPE_CUSTOM;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function savedReports(): HasMany
    {
        return $this->hasMany(SavedReport::class, 'report_template_id');
    }
}
```

File: `app/Models/Modules/Reporting/SavedReport.php`

```php
<?php

namespace App\Models\Modules\Reporting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SavedReport extends Model
{
    protected $table = 'saved_reports';

    protected $fillable = [
        'report_template_id',
        'name',
        'parameters',
        'generated_by',
        'generated_at',
        'file_path',
        'format',
        'status',
    ];

    protected $casts = [
        'parameters' => 'array',
        'generated_at' => 'datetime',
    ];

    // Constants for status
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // Constants for format
    public const FORMAT_CSV = 'csv';
    public const FORMAT_PDF = 'pdf';
    public const FORMAT_EXCEL = 'excel';

    public static function statusLabels(): array
    {
        return [
            'pending'    => 'Menunggu',
            'processing' => 'Sedang Diproses',
            'completed'  => 'Selesai',
            'failed'     => 'Gagal',
        ];
    }

    public static function formatLabels(): array
    {
        return [
            'csv'   => 'CSV',
            'pdf'   => 'PDF',
            'excel' => 'Excel',
        ];
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isDownloadable(): bool
    {
        return $this->isCompleted() && $this->file_path !== null;
    }

    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Scopes
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }
}
```

### Seeder: `ReportTemplateSeeder`

File: `database/seeders/ReportTemplateSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@qhsse.local')->first()
            ?? User::factory()->create(['email' => 'admin@qhsse.local']);

        $templates = [
            [
                'name'        => 'Ringkasan Insiden',
                'type'        => 'incident_summary',
                'description' => 'Laporan ringkasan insiden per periode.',
                'config'      => [
                    'sections' => [
                        ['key' => 'summary', 'label' => 'Ringkasan', 'enabled' => true],
                        ['key' => 'by_severity', 'label' => 'By Severity', 'enabled' => true, 'data_source' => 'incident'],
                        ['key' => 'by_site', 'label' => 'By Site', 'enabled' => true, 'data_source' => 'incident'],
                        ['key' => 'by_month', 'label' => 'Trend Bulanan', 'enabled' => true, 'data_source' => 'incident'],
                        ['key' => 'status_dist', 'label' => 'Distribusi Status', 'enabled' => true, 'data_source' => 'incident'],
                    ],
                    'default_parameters' => [
                        'date_range' => 'last_month',
                        'format' => 'pdf',
                    ],
                ],
            ],
            [
                'name'        => 'Ringkasan CAPA',
                'type'        => 'capa_summary',
                'description' => 'Laporan status CAPA: open, overdue, closure rate.',
                'config'      => [
                    'sections' => [
                        ['key' => 'summary', 'label' => 'Ringkasan', 'enabled' => true],
                        ['key' => 'by_status', 'label' => 'By Status', 'enabled' => true, 'data_source' => 'capa'],
                        ['key' => 'by_priority', 'label' => 'By Priority', 'enabled' => true, 'data_source' => 'capa'],
                        ['key' => 'by_source', 'label' => 'By Source Module', 'enabled' => true, 'data_source' => 'capa'],
                        ['key' => 'overdue', 'label' => 'Daftar Overdue', 'enabled' => true, 'data_source' => 'capa'],
                    ],
                    'default_parameters' => ['format' => 'pdf'],
                ],
            ],
            [
                'name'        => 'Ringkasan Inspection',
                'type'        => 'inspection_summary',
                'description' => 'Laporan hasil inspection dan compliance rate.',
                'config'      => [
                    'sections' => [
                        ['key' => 'summary', 'label' => 'Ringkasan', 'enabled' => true],
                        ['key' => 'pass_fail', 'label' => 'Pass/Fail Rate', 'enabled' => true, 'data_source' => 'inspection'],
                        ['key' => 'findings', 'label' => 'Findings', 'enabled' => true, 'data_source' => 'inspection'],
                    ],
                    'default_parameters' => ['format' => 'pdf'],
                ],
            ],
            [
                'name'        => 'Ringkasan Audit',
                'type'        => 'audit_summary',
                'description' => 'Laporan audit findings dan status.',
                'config'      => [
                    'sections' => [
                        ['key' => 'summary', 'label' => 'Ringkasan', 'enabled' => true],
                        ['key' => 'by_severity', 'label' => 'By Severity', 'enabled' => true, 'data_source' => 'audit'],
                        ['key' => 'closure', 'label' => 'Closure Rate', 'enabled' => true, 'data_source' => 'audit'],
                    ],
                    'default_parameters' => ['format' => 'pdf'],
                ],
            ],
            [
                'name'        => 'Kepatuhan Training',
                'type'        => 'training_compliance',
                'description' => 'Laporan status kelengkapan training per karyawan/departemen.',
                'config'      => [
                    'sections' => [
                        ['key' => 'summary', 'label' => 'Ringkasan', 'enabled' => true],
                        ['key' => 'completion', 'label' => 'Completion Rate', 'enabled' => true, 'data_source' => 'training'],
                        ['key' => 'overdue', 'label' => 'Overdue Training', 'enabled' => true, 'data_source' => 'training'],
                        ['key' => 'by_dept', 'label' => 'By Departemen', 'enabled' => true, 'data_source' => 'training'],
                    ],
                    'default_parameters' => ['format' => 'excel'],
                ],
            ],
            [
                'name'        => 'Laporan Bulanan QHSSE',
                'type'        => 'monthly_qhsse',
                'description' => 'Laporan komprehensif bulanan: insiden, CAPA, inspection, audit, training, permit, environment, security.',
                'config'      => [
                    'sections' => [
                        ['key' => 'executive', 'label' => 'Ringkasan Eksekutif', 'enabled' => true],
                        ['key' => 'incident', 'label' => 'Statistik Insiden', 'enabled' => true, 'data_source' => 'incident'],
                        ['key' => 'capa', 'label' => 'Status CAPA', 'enabled' => true, 'data_source' => 'capa'],
                        ['key' => 'inspection', 'label' => 'Hasil Inspection', 'enabled' => true, 'data_source' => 'inspection'],
                        ['key' => 'audit', 'label' => 'Audit Findings', 'enabled' => true, 'data_source' => 'audit'],
                        ['key' => 'training', 'label' => 'Training Compliance', 'enabled' => true, 'data_source' => 'training'],
                        ['key' => 'permit', 'label' => 'Permit to Work', 'enabled' => true, 'data_source' => 'permit'],
                        ['key' => 'environment', 'label' => 'Environmental', 'enabled' => true, 'data_source' => 'environment'],
                        ['key' => 'security', 'label' => 'Security', 'enabled' => true, 'data_source' => 'security'],
                    ],
                    'default_parameters' => [
                        'date_range' => 'last_month',
                        'format' => 'pdf',
                        'include_charts' => true,
                    ],
                ],
            ],
            [
                'name'        => 'Laporan Tahunan QHSSE',
                'type'        => 'annual_qhsse',
                'description' => 'Laporan komprehensif tahunan dengan tren 12 bulan dan analisis.',
                'config'      => [
                    'sections' => [
                        ['key' => 'executive', 'label' => 'Ringkasan Eksekutif', 'enabled' => true],
                        ['key' => 'annual_trend', 'label' => 'Tren Tahunan', 'enabled' => true],
                        ['key' => 'incident', 'label' => 'Statistik Insiden', 'enabled' => true, 'data_source' => 'incident'],
                        ['key' => 'capa', 'label' => 'Status CAPA', 'enabled' => true, 'data_source' => 'capa'],
                        ['key' => 'inspection', 'label' => 'Hasil Inspection', 'enabled' => true, 'data_source' => 'inspection'],
                        ['key' => 'audit', 'label' => 'Audit Findings', 'enabled' => true, 'data_source' => 'audit'],
                        ['key' => 'training', 'label' => 'Training Compliance', 'enabled' => true, 'data_source' => 'training'],
                        ['key' => 'risk', 'label' => 'Risk Management', 'enabled' => true, 'data_source' => 'risk'],
                        ['key' => 'legal', 'label' => 'Legal Compliance', 'enabled' => true, 'data_source' => 'legal'],
                    ],
                    'default_parameters' => [
                        'date_range' => 'last_year',
                        'format' => 'pdf',
                        'include_charts' => true,
                    ],
                ],
            ],
        ];

        foreach ($templates as $template) {
            ReportTemplate::create(array_merge($template, [
                'created_by' => $admin->id,
                'is_active'  => true,
            ]));
        }
    }
}
```

### Factory: `ReportTemplateFactory`

File: `database/factories/Modules/Reporting/ReportTemplateFactory.php`

```php
<?php

namespace Database\Factories\Modules\Reporting;

use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportTemplateFactory extends Factory
{
    protected $model = ReportTemplate::class;

    public function definition(): array
    {
        return [
            'name'        => fake()->words(3, true),
            'type'        => fake()->randomElement(ReportTemplate::PREDEFINED_TYPES),
            'config'      => ['sections' => [], 'default_parameters' => []],
            'description' => fake()->optional(0.7)->sentence(),
            'is_active'   => true,
            'created_by'  => User::factory(),
        ];
    }

    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ReportTemplate::TYPE_CUSTOM,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
```

### Factory: `SavedReportFactory`

File: `database/factories/Modules/Reporting/SavedReportFactory.php`

```php
<?php

namespace Database\Factories\Modules\Reporting;

use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\Modules\Reporting\SavedReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedReportFactory extends Factory
{
    protected $model = SavedReport::class;

    public function definition(): array
    {
        return [
            'report_template_id' => ReportTemplate::factory(),
            'name'              => fake()->words(4, true),
            'parameters'        => [
                'date_from'      => now()->subMonth()->format('Y-m-d'),
                'date_to'        => now()->format('Y-m-d'),
                'site_id'        => null,
                'department_id'  => null,
                'format'         => 'pdf',
                'include_charts' => true,
            ],
            'generated_by'      => User::factory(),
            'generated_at'      => now(),
            'file_path'         => null,
            'format'            => 'pdf',
            'status'            => 'pending',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => 'completed',
            'file_path' => 'reports/' . fake()->numberBetween(1, 1000) . '/report.pdf',
            'format'    => $attributes['format'] ?? 'pdf',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => 'failed',
            'file_path' => null,
            'parameters' => array_merge($attributes['parameters'] ?? [], [
                'error' => 'Simulated failure for testing.',
            ]),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => 'processing',
            'file_path' => null,
        ]);
    }
}
```

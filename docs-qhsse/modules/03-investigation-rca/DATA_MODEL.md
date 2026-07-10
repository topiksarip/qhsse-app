# Data Model — Investigation & RCA

> Phase 2 schema for the Investigation & RCA module.  
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, JSON columns for flexible RCA data, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs, workflow).

---

## 1. Table of Contents

1. [Main Table: `investigations`](#2-main-table-investigations)
2. [Pivot Table: `investigation_team`](#3-pivot-table-investigation_team)
3. [ERD Diagram (ASCII)](#4-erd-diagram-ascii)
4. [Index Specifications](#5-index-specifications)
5. [Shared Relations](#6-shared-relations)
6. [Migration File Naming Convention](#7-migration-file-naming-convention)

---

## 2. Main Table: `investigations`

Stores the core investigation record — root cause analysis for an incident.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `investigation_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `INV-{YYYY}-{0001}`) |
| 3 | `incident_id` | `bigint` | NO | — | **FK → `incidents.id`**. The incident being investigated |
| 4 | `title` | `varchar(255)` | NO | — | Short summary of the investigation |
| 5 | `status` | `varchar(50)` | NO | `'draft'` | Lifecycle state: `draft`, `in_progress`, `completed`, `cancelled` |
| 6 | `root_cause` | `text` | YES | `NULL` | Summary of root cause (required before `completed`) |
| 7 | `five_whys` | `json` | YES | `NULL` | 5-Why analysis data (array of `{level, question, answer, is_root_cause}`) |
| 8 | `fishbone` | `json` | YES | `NULL` | Fishbone diagram data (array of `{category, causes[]}`) |
| 9 | `contributing_factors` | `json` | YES | `NULL` | Contributing factors (array of `{factor, category, impact}`) |
| 10 | `timeline_events` | `json` | YES | `NULL` | Timeline of events (array of `{timestamp, event, description, source}`) |
| 11 | `recommendations` | `text` | YES | `NULL` | Corrective action recommendations (required before `completed`) |
| 12 | `investigator_id` | `bigint` | NO | — | **FK → `users.id`**. Lead investigator |
| 13 | `started_at` | `timestamp` | YES | `NULL` | When investigation was started (draft → in_progress) |
| 14 | `completed_at` | `timestamp` | YES | `NULL` | When investigation was completed |
| 15 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 16 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed, auto-updated on save |

### JSON Column Structures

#### `five_whys` (JSON)

```json
[
  {
    "level": 1,
    "question": "Mengapa kecelakaan terjadi?",
    "answer": "Pekerja terpeleset di lantai basah.",
    "is_root_cause": false
  },
  {
    "level": 2,
    "question": "Mengapa lantai basah?",
    "answer": "Terdapat tumpahan oli dari mesin.",
    "is_root_cause": false
  },
  {
    "level": 3,
    "question": "Mengapa terjadi tumpahan oli?",
    "answer": "Seal pada mesin rusak dan tidak terdeteksi.",
    "is_root_cause": false
  },
  {
    "level": 4,
    "question": "Mengapa seal rusak tidak terdeteksi?",
    "answer": "Maintenance preventif tidak sesuai jadwal.",
    "is_root_cause": false
  },
  {
    "level": 5,
    "question": "Mengapa maintenance preventif tidak sesuai jadwal?",
    "answer": "Tidak ada sistem monitoring maintenance yang efektif.",
    "is_root_cause": true
  }
]
```

#### `fishbone` (JSON)

```json
[
  {
    "category": "Man",
    "causes": [
      "Operator tidak mendapat training ulang SOP terbaru",
      "Kelelahan karena lembur berlebihan"
    ]
  },
  {
    "category": "Method",
    "causes": [
      "Prosedur lockout/tagout tidak diikuti",
      "Tidak ada checklist pre-operation"
    ]
  },
  {
    "category": "Machine",
    "causes": [
      "Seal mesin rusak",
      "Tidak ada sensor kebocoran oli"
    ]
  },
  {
    "category": "Material",
    "causes": []
  },
  {
    "category": "Environment",
    "causes": [
      "Pencahayaan area kurang optimal",
      "Lantai licin saat basah"
    ]
  },
  {
    "category": "Management",
    "causes": [
      "Tidak ada sistem monitoring maintenance",
      "Resource allocation untuk maintenance kurang"
    ]
  }
]
```

#### `contributing_factors` (JSON)

```json
[
  {
    "factor": "Operator baru transfer dari department lain",
    "category": "Man",
    "impact": "indirect"
  },
  {
    "factor": "APD sepatu anti-slip tidak tersedia",
    "category": "Material",
    "impact": "direct"
  },
  {
    "factor": "Lokasi mesin terlalu dekat dengan jalur pejalan kaki",
    "category": "Environment",
    "impact": "indirect"
  }
]
```

#### `timeline_events` (JSON)

```json
[
  {
    "timestamp": "2026-07-11T14:00:00+07:00",
    "event": "Mesin mulai beroperasi",
    "description": "Shift pagi dimulai, operator menyalakan mesin produksi #3.",
    "source": "witness_statement"
  },
  {
    "timestamp": "2026-07-11T14:15:00+07:00",
    "event": "Kebocoran oli terdeteksi",
    "description": "Tampak oli menetes dari bagian seal mesin.",
    "source": "cctv_footage"
  },
  {
    "timestamp": "2026-07-11T14:30:00+07:00",
    "event": "Kecelakaan terjadi",
    "description": "Pekerja terpeleset di lantai yang basah akibat tumpahan oli.",
    "source": "incident_report"
  },
  {
    "timestamp": "2026-07-11T14:32:00+07:00",
    "event": "Pertolongan pertama",
    "description": "First responder memberikan pertolongan pertama dan mengevakuasi korban.",
    "source": "witness_statement"
  }
]
```

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE investigations (
    id                    BIGSERIAL       PRIMARY KEY,
    investigation_number  VARCHAR(50)     NOT NULL UNIQUE,
    incident_id           BIGINT          NOT NULL REFERENCES incidents(id),
    title                 VARCHAR(255)    NOT NULL,
    status                VARCHAR(50)     NOT NULL DEFAULT 'draft',
    root_cause            TEXT            NULL,
    five_whys             JSONB           NULL,
    fishbone              JSONB           NULL,
    contributing_factors  JSONB           NULL,
    timeline_events       JSONB           NULL,
    recommendations       TEXT            NULL,
    investigator_id       BIGINT          NOT NULL REFERENCES users(id),
    started_at            TIMESTAMP       NULL,
    completed_at          TIMESTAMP       NULL,
    created_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT investigations_status_check CHECK (
        status IN ('draft', 'in_progress', 'completed', 'cancelled')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('investigations', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('investigation_number', 50)->unique();
    $table->foreignId('incident_id')->constrained('incidents');
    $table->string('title', 255);
    $table->string('status', 50)->default('draft');
    $table->text('root_cause')->nullable();
    $table->json('five_whys')->nullable();
    $table->json('fishbone')->nullable();
    $table->json('contributing_factors')->nullable();
    $table->json('timeline_events')->nullable();
    $table->text('recommendations')->nullable();
    $table->foreignId('investigator_id')->constrained('users');
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    // Check constraint for status enum
    $table->check("status IN ('draft', 'in_progress', 'completed', 'cancelled')", 'investigations_status_check');
});
```

### Design Notes

- **No soft deletes** (`deleted_at`) — investigations are never hard-deleted; use `status = 'cancelled'` instead. Admin/Super Admin can hard-delete only if status is `draft`.
- **`investigation_number`** is unique and generated at **create** time (not at start/submit). Generated via `NumberingService::generate('investigation')`.
- **JSON columns** (`five_whys`, `fishbone`, `contributing_factors`, `timeline_events`) use PostgreSQL `JSONB` for indexing and query performance. Stored as nullable — they are populated progressively during the investigation.
- **`root_cause`** is a separate text field that summarizes the root cause found through 5-Why/Fishbone analysis. It is the human-readable summary, while `five_whys` contains the structured data.
- **`investigator_id`** is the lead investigator. Additional team members are stored in the `investigation_team` pivot table.
- **`started_at`** is set when transition `draft → in_progress` occurs.
- **`completed_at`** is set when transition `in_progress → completed` occurs.
- **`incident_id`** has `ON DELETE RESTRICT` — cannot delete an incident that has an active investigation.

---

## 3. Pivot Table: `investigation_team`

Many-to-many relationship between investigations and users who are part of the investigation team.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `investigation_id` | `bigint` | NO | — | **FK → `investigations.id`**, `ON DELETE CASCADE` |
| 3 | `user_id` | `bigint` | NO | — | **FK → `users.id`** |
| 4 | `role` | `varchar(50)` | NO | `'investigator'` | Team role: `lead_investigator`, `investigator`, `subject_matter_expert`, `recorder` |
| 5 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 6 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE investigation_team (
    id                BIGSERIAL       PRIMARY KEY,
    investigation_id  BIGINT          NOT NULL REFERENCES investigations(id) ON DELETE CASCADE,
    user_id           BIGINT          NOT NULL REFERENCES users(id),
    role              VARCHAR(50)     NOT NULL DEFAULT 'investigator',
    created_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT investigation_team_role_check CHECK (
        role IN ('lead_investigator', 'investigator', 'subject_matter_expert', 'recorder')
    ),
    CONSTRAINT investigation_team_unique UNIQUE (investigation_id, user_id)
);
```

### Laravel Migration (Reference)

```php
Schema::create('investigation_team', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users');
    $table->string('role', 50)->default('investigator');
    $table->timestamps();

    // Prevent duplicate team member per investigation
    $table->unique(['investigation_id', 'user_id']);

    // Check constraint for role enum
    $table->check("role IN ('lead_investigator', 'investigator', 'subject_matter_expert', 'recorder')", 'investigation_team_role_check');
});
```

### Design Notes

- **Cascade delete** on `investigation_id` — when an investigation is deleted, all team members go with it.
- **Unique composite** (`investigation_id`, `user_id`) prevents duplicate entries for the same user on the same investigation.
- **`role`** defines the team member's role: `lead_investigator` (should match `investigations.investigator_id`), `investigator` (regular team member), `subject_matter_expert` (SME for specific domain), `recorder` (documentation).
- No soft deletes — removing a team member is a hard delete from the pivot table (activity log captures the removal).

---

## 4. ERD Diagram (ASCII)

```
┌──────────────────────┐         ┌───────────────────────────┐         ┌─────────────────────┐
│      incidents        │         │      investigations        │         │      users          │
├──────────────────────┤         ├───────────────────────────┤         ├─────────────────────┤
│ id          BIGINT PK │◄──┐    │ id                   BIGINT PK│──┐   │ id          BIGINT PK│
│ incident_number VARCHAR│   │    │ investigation_number VARCHAR │  │   │ name        VARCHAR  │
│ title        VARCHAR   │   │    │ incident_id    BIGINT FK ────┼──┘   │ email       VARCHAR  │
│ category     VARCHAR   │   │    │ title             VARCHAR     │      │ password    VARCHAR  │
│ occurred_at  TIMESTAMP │   │    │ status            VARCHAR     │      │ is_active   BOOLEAN │
│ site_id      BIGINT FK │   │    │ root_cause        TEXT        │      │ company_id  BIGINT  │
│ area_id      BIGINT FK │   │    │ five_whys         JSONB       │      │ employee_id BIGINT  │
│ department_id BIGINT FK│   │    │ fishbone          JSONB       │      └─────────────────────┘
│ reporter_id  BIGINT FK │   │    │ contributing_factors JSONB    │              ▲
│ severity_id  BIGINT FK │   │    │ timeline_events   JSONB       │              │
│ priority_id  BIGINT FK │   │    │ recommendations   TEXT        │              │
│ description  TEXT      │   │    │ investigator_id   BIGINT FK ─┼──────────────┘
│ immediate_action TEXT  │   │    │ started_at        TIMESTAMP  │
│ status       VARCHAR   │   │    │ completed_at      TIMESTAMP  │
│ created_at   TIMESTAMP │   │    │ created_at        TIMESTAMP  │
│ updated_at   TIMESTAMP │   │    │ updated_at        TIMESTAMP  │
└──────────────────────┘   │    └───────────────────────────┘   │
                            │                 │  ▲                │
                            │                 │  │                │
                            │                 ▼  │                │
                            │    ┌────────────────────────────┐  │
                            │    │    investigation_team       │  │
                            │    ├────────────────────────────┤  │
                            │    │ id              BIGINT PK  │  │
                            │    │ investigation_id BIGINT FK ─┼──┘ (cascade)
                            │    │ user_id         BIGINT FK ──┼────► users
                            │    │ role            VARCHAR     │
                            │    │ created_at      TIMESTAMP   │
                            │    │ updated_at      TIMESTAMP   │
                            │    └────────────────────────────┘
                            │
                            └── (1 incident : 0..N investigations)
```

### Relationship Summary

```
                        ┌──────────────┐
                        │   incidents  │
                        │  (id: PK)    │
                        └──────┬───────┘
                               │ 1
                               │
                          ┌────▼────────┐          ┌──────────────┐
                  N:1     │investigations│     1:N  │inv_team      │
                ┌────────►│(main)        │◄─────────│  (pivot)     │
                │         └──┬──────────┘          └──────┬───────┘
                │            │                            │
                │            │ N:1                        │ N:1
                │     ┌──────┘                     ┌──────┘
                │     │                            │
                │  ┌──▼─────┐                ┌───▼──────┐
                │  │ users   │                │  users    │
                │  │(investi-│                │(team mem) │
                │  │ gator)  │                └───────────┘
                │  └────────┘
                │
          ┌─────▼─────┐
          │  sites    │  (via incident.site_id)
          └───────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `incidents` | `investigations` | `incident_id` | 1:N | RESTRICT (default) |
| `users` | `investigations` | `investigator_id` | 1:N | RESTRICT (default) |
| `investigations` | `investigation_team` | `investigation_id` | 1:N | CASCADE |
| `users` | `investigation_team` | `user_id` | 1:N | RESTRICT (default) |

---

## 5. Index Specifications

### `investigations` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `investigations_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `investigations_investigation_number_unique` | `investigation_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `investigations_incident_id_index` | `incident_id` | btree | Find investigations by incident |
| 4 | `investigations_investigator_id_index` | `investigator_id` | btree | Find investigations by investigator |
| 5 | `investigations_status_index` | `status` | btree | Filter/list by workflow status |
| 6 | `investigations_started_at_index` | `started_at` | btree | Sort/filter by start date |
| 7 | `investigations_completed_at_index` | `completed_at` | btree | Sort/filter by completion date |
| 8 | `investigations_created_at_index` | `created_at` | btree | Sort by creation date |

### `investigation_team` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `investigation_team_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `investigation_team_investigation_id_index` | `investigation_id` | btree | Find all team members for an investigation |
| 3 | `investigation_team_user_id_index` | `user_id` | btree | Find all investigations for a user |
| 4 | `investigation_team_investigation_id_user_id_unique` | `investigation_id, user_id` | UNIQUE (btree) | Prevent duplicate team member per investigation |

### Laravel Migration Indexes

```php
// investigations table
$table->index('incident_id');
$table->index('investigator_id');
$table->index('status');
$table->index('started_at');
$table->index('completed_at');
$table->index('created_at');

// investigation_team table
$table->index('investigation_id');
$table->index('user_id');
$table->unique(['investigation_id', 'user_id']);
```

---

## 6. Shared Relations

The Investigation & RCA module uses the **polymorphic `module_name + reference_id`** pattern for all cross-cutting platform services. For this module:

- `module_name = 'investigation'`
- `reference_id = investigations.id`

### 6.1 Managed Files (`managed_files`)

File attachments (evidence, reports, documents) for an investigation.

| Column | Value |
|---|---|
| `module_name` | `'investigation'` |
| `reference_id` | `investigations.id` |
| `collection` | `'evidence'`, `'report'`, `'attachment'` |
| `uploaded_by` | `users.id` (FK) |

```
investigations.id ──► managed_files.reference_id
                    managed_files.module_name = 'investigation'
```

**Usage**: `Investigation::files()` returns all files where `module_name='investigation'` AND `reference_id=$this->id`.

### 6.2 Comments (`comments`)

Threaded comments / discussion on an investigation.

| Column | Value |
|---|---|
| `module_name` | `'investigation'` |
| `reference_id` | `investigations.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` — internal-only vs visible |

```
investigations.id ──► comments.reference_id
                    comments.module_name = 'investigation'
```

**Usage**: `Investigation::comments()` returns all comments where `module_name='investigation'` AND `reference_id=$this->id`.

### 6.3 Activity Logs (`activity_logs`)

Timeline of actions performed on an investigation.

| Column | Value |
|---|---|
| `module_name` | `'investigation'` |
| `reference_id` | `investigations.id` |
| `event` | `'created'`, `'started'`, `'completed'`, `'cancelled'`, `'team_added'`, `'team_removed'`, etc. |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot, metadata) |

```
investigations.id ──► activity_logs.reference_id
                    activity_logs.module_name = 'investigation'
```

**Usage**: `Investigation::activities()` returns all activity log entries for this investigation.

### 6.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes.

| Column | Value |
|---|---|
| `module_name` | `'investigation'` |
| `reference_id` | `investigations.id` |
| `auditable_type` | `'Investigation'` (or fully-qualified model class) |
| `auditable_id` | `investigations.id` |
| `old_values` | JSON (previous field values) |
| `new_values` | JSON (new field values) |
| `actor_id` | `users.id` (FK) |

```
investigations.id ──► audit_logs.reference_id
                    audit_logs.module_name = 'investigation'
                    audit_logs.auditable_id = investigations.id
```

**Usage**: `Investigation::audits()` returns all audit log entries for this investigation.

### 6.5 Workflow Instances (`workflow_instances`)

Each investigation that enters a workflow gets a workflow instance.

| Column | Value |
|---|---|
| `module_name` | `'investigation'` |
| `reference_id` | `investigations.id` |
| `workflow_definition_id` | FK to `workflow_definitions.id` |
| `current_status` | Mirrors `investigations.status` |
| `started_by` | `users.id` (FK) |
| `completed_at` | nullable, set when investigation is completed/cancelled |

### 6.6 Workflow Histories (`workflow_histories`)

Every workflow transition for an investigation is logged here.

| Column | Value |
|---|---|
| `module_name` | `'investigation'` |
| `reference_id` | `investigations.id` |
| `workflow_instance_id` | FK to `workflow_instances.id` |
| `from_status` | Previous status (e.g., `'draft'`) |
| `to_status` | New status (e.g., `'in_progress'`) |
| `action_key` | `'start'`, `'complete'`, `'cancel'` |
| `actor_id` | `users.id` (FK) |

### Shared Relations Summary

```
                          ┌──────────────────┐
                          │  investigations  │
                          │  (id: PK)        │
                          └──────┬───────────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='investigation'      │
              reference_id=investigations.id   │
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

    All linked via: module_name='investigation' AND reference_id=investigations.id
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

### Migration Files for This Module

| # | Migration Filename | Description |
|---|---|---|
| 1 | `YYYY_MM_DD_HHMMSS_create_investigations_table.php` | Creates the `investigations` table |
| 2 | `YYYY_MM_DD_HHMMSS_create_investigation_team_table.php` | Creates the `investigation_team` pivot table |

### Eloquent Model

File: `app/Models/Modules/Investigation/Investigation.php`

```php
<?php

namespace App\Models\Modules\Investigation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Investigation extends Model
{
    protected $table = 'investigations';

    protected $fillable = [
        'investigation_number',
        'incident_id',
        'title',
        'status',
        'root_cause',
        'five_whys',
        'fishbone',
        'contributing_factors',
        'timeline_events',
        'recommendations',
        'investigator_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'five_whys'            => 'array',
        'fishbone'             => 'array',
        'contributing_factors' => 'array',
        'timeline_events'      => 'array',
        'started_at'           => 'datetime',
        'completed_at'         => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'incident_id');
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigator_id');
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'investigation_team')
            ->withPivot('role')
            ->withTimestamps();
    }

    // ── Shared Relations (module_name + reference_id) ────────────

    public function files()
    {
        return ManagedFile::where('module_name', 'investigation')
            ->where('reference_id', $this->id)
            ->whereNull('deleted_at');
    }

    public function comments()
    {
        return Comment::where('module_name', 'investigation')
            ->where('reference_id', $this->id);
    }

    public function activities()
    {
        return ActivityLog::where('module_name', 'investigation')
            ->where('reference_id', $this->id);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
```

### Factory Definition

File: `database/factories/Modules/Investigation/InvestigationFactory.php`

```php
<?php

namespace Database\Factories\Modules\Investigation;

use App\Models\Modules\Incident\Incident;
use App\Models\Modules\Investigation\Investigation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestigationFactory extends Factory
{
    protected $model = Investigation::class;

    public function definition(): array
    {
        return [
            'investigation_number' => 'INV-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'incident_id'          => Incident::factory(),
            'title'                => fake()->sentence(6),
            'status'               => 'draft',
            'root_cause'           => null,
            'five_whys'            => null,
            'fishbone'             => null,
            'contributing_factors' => null,
            'timeline_events'      => null,
            'recommendations'      => null,
            'investigator_id'      => User::factory(),
            'started_at'           => null,
            'completed_at'         => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attrs) => [
            'status'     => 'in_progress',
            'started_at' => now()->subDays(3),
            'five_whys'  => [
                ['level' => 1, 'question' => 'Mengapa?', 'answer' => 'Jawaban.', 'is_root_cause' => false],
            ],
            'fishbone' => [
                ['category' => 'Man', 'causes' => ['Test cause']],
            ],
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attrs) => [
            'status'          => 'completed',
            'started_at'       => now()->subDays(10),
            'completed_at'     => now()->subDays(2),
            'root_cause'      => 'Root cause identified during investigation.',
            'recommendations' => 'Implement improved maintenance schedule.',
            'five_whys'  => [
                ['level' => 1, 'question' => 'Mengapa?', 'answer' => 'Jawaban.', 'is_root_cause' => false],
                ['level' => 2, 'question' => 'Mengapa?', 'answer' => 'Root cause.', 'is_root_cause' => true],
            ],
            'fishbone' => [
                ['category' => 'Man', 'causes' => ['Test cause']],
                ['category' => 'Method', 'causes' => ['Test method cause']],
            ],
        ]);
    }
}
```

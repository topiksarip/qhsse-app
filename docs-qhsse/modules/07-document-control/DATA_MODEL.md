# Data Model — Document Control

> Phase 7 schema for the Document Control module.  
> Laravel 12 + PostgreSQL. Uses bigint PKs, string enums, and the shared `module_name + reference_id` polymorphic pattern for all cross-cutting concerns (files, comments, logs, workflow).

---

## 1. Table of Contents

1. [Main Table: `controlled_documents`](#2-main-table-controlled_documents)
2. [Reviews Table: `document_reviews`](#3-reviews-table-document_reviews)
3. [ERD Diagram (ASCII)](#4-erd-diagram-ascii)
4. [Index Specifications](#5-index-specifications)
5. [Shared Relations](#6-shared-relations)
6. [Migration File Naming Convention](#7-migration-file-naming-convention)

---

## 2. Main Table: `controlled_documents`

Stores the core controlled document record — SOP, WI, JSA, HIRADC, MSDS, Policy, Form, Manual, or Other.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `document_number` | `varchar(50)` | NO | — | **Unique**. Auto-generated at create (format: `DOC-{YYYY}-{0001}`) |
| 3 | `title` | `varchar(255)` | NO | — | Document title |
| 4 | `type` | `varchar(20)` | NO | — | **Check constraint** enum: `sop`, `wi`, `jsa`, `hiradc`, `msds`, `policy`, `form`, `manual`, `other` |
| 5 | `version` | `varchar(20)` | NO | `'1.0'` | Version string (e.g., `1.0`, `1.1`, `2.0`) |
| 6 | `revision_notes` | `text` | YES | `NULL` | Notes about what changed in this revision |
| 7 | `effective_date` | `date` | YES | `NULL` | When the document becomes effective (set on make_effective) |
| 8 | `review_date` | `date` | YES | `NULL` | Scheduled review date for expiry reminder |
| 9 | `expiry_date` | `date` | YES | `NULL` | When the document expires |
| 10 | `department_id` | `bigint` | YES | `NULL` | **FK → `departments.id`**. Owning department |
| 11 | `owner_id` | `bigint` | NO | — | **FK → `users.id`**. User who owns/maintains this document |
| 12 | `approver_id` | `bigint` | YES | `NULL` | **FK → `users.id`**. User who approved (set on approve) |
| 13 | `status` | `varchar(20)` | NO | `'draft'` | Lifecycle state: `draft`, `review`, `approved`, `effective`, `obsolete`, `rejected` |
| 14 | `is_confidential` | `boolean` | NO | `false` | If true, download restricted to authorized users |
| 15 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |
| 16 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE controlled_documents (
    id                  BIGSERIAL       PRIMARY KEY,
    document_number     VARCHAR(50)     NOT NULL UNIQUE,
    title               VARCHAR(255)    NOT NULL,
    type                VARCHAR(20)     NOT NULL,
    version             VARCHAR(20)     NOT NULL DEFAULT '1.0',
    revision_notes      TEXT            NULL,
    effective_date      DATE            NULL,
    review_date         DATE            NULL,
    expiry_date         DATE            NULL,
    department_id       BIGINT          NULL REFERENCES departments(id) ON DELETE SET NULL,
    owner_id            BIGINT          NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    approver_id         BIGINT          NULL REFERENCES users(id) ON DELETE SET NULL,
    status              VARCHAR(20)     NOT NULL DEFAULT 'draft',
    is_confidential     BOOLEAN         NOT NULL DEFAULT false,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT controlled_documents_type_check CHECK (
        type IN (
            'sop', 'wi', 'jsa', 'hiradc', 'msds',
            'policy', 'form', 'manual', 'other'
        )
    ),

    CONSTRAINT controlled_documents_status_check CHECK (
        status IN (
            'draft', 'review', 'approved', 'effective', 'obsolete', 'rejected'
        )
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('controlled_documents', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('document_number', 50)->unique();
    $table->string('title', 255);
    $table->string('type', 20);
    $table->string('version', 20)->default('1.0');
    $table->text('revision_notes')->nullable();
    $table->date('effective_date')->nullable();
    $table->date('review_date')->nullable();
    $table->date('expiry_date')->nullable();
    $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
    $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
    $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('status', 20)->default('draft');
    $table->boolean('is_confidential')->default(false);
    $table->timestamps();

    // Check constraint for type enum
    $table->check("type IN ('sop','wi','jsa','hiradc','msds','policy','form','manual','other')", 'controlled_documents_type_check');

    // Check constraint for status enum
    $table->check("status IN ('draft','review','approved','effective','obsolete','rejected')", 'controlled_documents_status_check');
});
```

### Design Notes

- **No soft deletes** (`deleted_at`) — documents are never hard-deleted; use `status = 'obsolete'` instead. Admin can hard-delete via separate command if needed.
- **`document_number`** is unique and generated at create via `NumberingService::generate('document')`.
- **`type`** is stored as `varchar(20)` with a CHECK constraint — simplifies validation and future additions.
- **`version`** is a free-form string (e.g., `1.0`, `2.1`) — not auto-incremented. Owner sets it manually.
- **`owner_id`** is NOT NULL — every document must have an owner.
- **`approver_id`** is NULL until the document is approved, then set to the approving user's ID.
- **`effective_date`** is NULL until `make_effective` transition, then set to the current date (or provided date).
- **`review_date`** and **`expiry_date`** are nullable — used for expiry reminder notifications.

---

## 3. Reviews Table: `document_reviews`

Tracks each review/revision cycle for a controlled document. Each time a document is submitted for review, a new record is created.

### Column Definitions

| # | Column | Type | Nullable | Default | Constraints / Notes |
|---|--------|------|----------|---------|---------------------|
| 1 | `id` | `bigint` | NO | — | **Primary Key**, auto-increment |
| 2 | `document_id` | `bigint` | NO | — | **FK → `controlled_documents.id`**, `ON DELETE CASCADE` |
| 3 | `reviewer_id` | `bigint` | YES | `NULL` | **FK → `users.id`**. The reviewer (set when reviewer acts) |
| 4 | `review_date` | `date` | YES | `NULL` | Date the review was completed (set when reviewer acts) |
| 5 | `review_notes` | `text` | YES | `NULL` | Notes from the reviewer (also stores reject/revise reason) |
| 6 | `decision` | `varchar(20)` | NO | `'pending'` | **Check constraint** enum: `pending`, `approve`, `reject`, `revise` |
| 7 | `created_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed — set when review record is created (at submit_review) |
| 8 | `updated_at` | `timestamp` | NO | `CURRENT_TIMESTAMP` | Laravel managed — updated when reviewer acts |

### PostgreSQL DDL (Reference)

```sql
CREATE TABLE document_reviews (
    id              BIGSERIAL       PRIMARY KEY,
    document_id     BIGINT          NOT NULL REFERENCES controlled_documents(id) ON DELETE CASCADE,
    reviewer_id     BIGINT          NULL REFERENCES users(id) ON DELETE SET NULL,
    review_date     DATE            NULL,
    review_notes    TEXT            NULL,
    decision        VARCHAR(20)     NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT document_reviews_decision_check CHECK (
        decision IN ('pending', 'approve', 'reject', 'revise')
    )
);
```

### Laravel Migration (Reference)

```php
Schema::create('document_reviews', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('document_id')->constrained('controlled_documents')->cascadeOnDelete();
    $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
    $table->date('review_date')->nullable();
    $table->text('review_notes')->nullable();
    $table->string('decision', 20)->default('pending');
    $table->timestamps();

    // Check constraint for decision enum
    $table->check("decision IN ('pending','approve','reject','revise')", 'document_reviews_decision_check');
});
```

### Design Notes

- **Cascade delete** on `document_id` — when a document is deleted, all reviews go with it.
- **`reviewer_id`** is NULL when the review record is created (at `submit_review`). It's set when the reviewer performs an action (approve/reject).
- **`review_date`** is NULL until the reviewer acts, then set to the current date.
- **`review_notes`** stores both the reviewer's notes AND the reject/revise reason.
- **`decision`** starts as `'pending'` and is updated to `'approve'`, `'reject'`, or `'revise'` when the reviewer acts.
- **Version tracking**: Each submit_review creates a new `document_reviews` record. The history of all reviews for a document is its version history.
- No unique constraint — a document can have many review records over its lifecycle.

---

## 4. ERD Diagram (ASCII)

```
┌─────────────────────────┐         ┌──────────────────────────────┐         ┌─────────────────────┐
│      departments         │         │    controlled_documents       │         │      users          │
├─────────────────────────┤         ├──────────────────────────────┤         ├─────────────────────┤
│ id          BIGINT  PK  │◄──┐    │ id              BIGINT  PK    │──┐    │ id          BIGINT PK│
│ code        VARCHAR      │   │    │ document_number VARCHAR(50) U│  │    │ name        VARCHAR  │
│ name        VARCHAR      │   │    │ title           VARCHAR(255)  │  │    │ email       VARCHAR  │
│ site_id     BIGINT FK    │   │    │ type            VARCHAR(20)   │  │    │ password    VARCHAR  │
│ is_active   BOOLEAN      │   │    │ version         VARCHAR(20)   │  │    │ is_active   BOOLEAN  │
└─────────────────────────┘   │    │ revision_notes  TEXT          │  │    │ company_id BIGINT FK │
                              │    │ effective_date  DATE          │  │    │ employee_id BIGINT FK │
                              └────│ department_id   BIGINT FK     │  │    └─────────────────────┘
                                   │ owner_id        BIGINT FK     │──┼────► (owner_id → users.id)
                                   │ approver_id     BIGINT FK     │──┼────► (approver_id → users.id)
                                   │ status          VARCHAR(20)   │  │
                                   │ is_confidential BOOLEAN        │  │
                                   │ created_at      TIMESTAMP     │  │
                                   │ updated_at      TIMESTAMP     │  │
                                   └──────────────────────────────┘  │
                                                │  ▲                  │
                                                │  │                  │
                                                ▼  │                  │
                                   ┌──────────────────────────────┐  │
                                   │      document_reviews         │  │
                                   ├──────────────────────────────┤  │
                                   │ id           BIGINT  PK       │  │
                                   │ document_id  BIGINT  FK ──────┘  │ (cascade)
                                   │ reviewer_id  BIGINT  FK ────────►│ users.id
                                   │ review_date  DATE               │
                                   │ review_notes TEXT               │
                                   │ decision     VARCHAR(20)         │
                                   │ created_at   TIMESTAMP          │
                                   │ updated_at   TIMESTAMP          │
                                   └──────────────────────────────┘
```

### Relationship Summary

```
                        ┌──────────────┐
                        │    users      │
                        │ (owner/approver│
                        │  /reviewer)   │
                        └──────┬───────┘
                               │ 1
                               │
                          ┌────▼────┐          ┌──────────────────┐
                  N:1     │controlled│    1:N  │ document_reviews  │
                ┌────────►│documents │◄─────────│  (version history)│
                │         └──┬───────┘          └──────┬───────────┘
                │            │                          │
                │            │ N:1                      │ N:1
                │     ┌──────┘                          │
                │     │                                  │
          ┌─────▼─────┐                          ┌─────▼─────┐
          │departments │                          │   users    │
          └───────────┘                          │(reviewer)  │
                                                 └───────────┘
```

### Entity Relationship Summary (Text)

| Parent Table | Child Table | FK Column | Cardinality | On Delete |
|---|---|---|---|---|
| `departments` | `controlled_documents` | `department_id` | 1:N | SET NULL |
| `users` | `controlled_documents` | `owner_id` | 1:N | RESTRICT |
| `users` | `controlled_documents` | `approver_id` | 1:N | SET NULL |
| `controlled_documents` | `document_reviews` | `document_id` | 1:N | CASCADE |
| `users` | `document_reviews` | `reviewer_id` | 1:N | SET NULL |

---

## 5. Index Specifications

### `controlled_documents` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `controlled_documents_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `controlled_documents_document_number_unique` | `document_number` | UNIQUE (btree) | Number lookup, prevent duplicates |
| 3 | `controlled_documents_type_index` | `type` | btree | Filter by document type |
| 4 | `controlled_documents_status_index` | `status` | btree | Filter/list by workflow status |
| 5 | `controlled_documents_department_id_index` | `department_id` | btree | Filter by department |
| 6 | `controlled_documents_owner_id_index` | `owner_id` | btree | List documents by owner |
| 7 | `controlled_documents_approver_id_index` | `approver_id` | btree | List documents by approver |
| 8 | `controlled_documents_effective_date_index` | `effective_date` | btree | Sort/filter by effective date |
| 9 | `controlled_documents_review_date_index` | `review_date` | btree | Expiry reminder query |
| 10 | `controlled_documents_expiry_date_index` | `expiry_date` | btree | Expiry reminder query |
| 11 | `controlled_documents_created_at_index` | `created_at` | btree | Sort by creation date |

### `document_reviews` Table

| # | Index Name | Columns | Type | Purpose |
|---|---|---|---|---|
| 1 | `document_reviews_pkey` | `id` | PRIMARY KEY (btree) | Row identification |
| 2 | `document_reviews_document_id_index` | `document_id` | btree | Find all reviews for a document |
| 3 | `document_reviews_reviewer_id_index` | `reviewer_id` | btree | Find all reviews by a reviewer |
| 4 | `document_reviews_decision_index` | `decision` | btree | Filter by decision type |
| 5 | `document_reviews_review_date_index` | `review_date` | btree | Sort by review date |

### Laravel Migration Indexes

```php
// controlled_documents table
$table->index('type');
$table->index('status');
$table->index('department_id');
$table->index('owner_id');
$table->index('approver_id');
$table->index('effective_date');
$table->index('review_date');
$table->index('expiry_date');
$table->index('created_at');

// document_reviews table
$table->index('document_id');
$table->index('reviewer_id');
$table->index('decision');
$table->index('review_date');
```

---

## 6. Shared Relations

The Document Control module does **not** duplicate file, comment, log, or workflow tables. Instead, all cross-cutting platform services use the **polymorphic `module_name + reference_id`** pattern. For this module:

- `module_name = 'document'`
- `reference_id = controlled_documents.id`

### 6.1 Managed Files (`managed_files`)

File attachments (document files) for a controlled document.

| Column | Value |
|---|---|
| `module_name` | `'document'` |
| `reference_id` | `controlled_documents.id` |
| `collection` | `'document_file'` |
| `uploaded_by` | `users.id` (FK) |

```
controlled_documents.id ──► managed_files.reference_id
                             managed_files.module_name = 'document'
                             managed_files.collection = 'document_file'
```

**Usage**: `ControlledDocument::files()` returns all files where `module_name='document'` AND `reference_id=$this->id` AND `collection='document_file'`.

### 6.2 Comments (`comments`)

Threaded comments / discussion on a controlled document.

| Column | Value |
|---|---|
| `module_name` | `'document'` |
| `reference_id` | `controlled_documents.id` |
| `parent_id` | `comments.id` (nullable, for threaded replies) |
| `author_id` | `users.id` (FK) |
| `is_internal` | `boolean` — internal-only vs visible to all |

```
controlled_documents.id ──► comments.reference_id
                             comments.module_name = 'document'
```

### 6.3 Activity Logs (`activity_logs`)

Timeline of actions performed on a document (created, submitted, approved, etc.).

| Column | Value |
|---|---|
| `module_name` | `'document'` |
| `reference_id` | `controlled_documents.id` |
| `event` | `'created'`, `'submitted'`, `'approved'`, `'effective'`, `'obsolete'`, `'rejected'`, `'revised'`, etc. |
| `actor_id` | `users.id` (FK) |
| `properties` | JSON (before/after snapshot, metadata) |

### 6.4 Audit Logs (`audit_logs`)

Detailed audit trail of field-level changes on document records.

| Column | Value |
|---|---|
| `module_name` | `'document'` |
| `reference_id` | `controlled_documents.id` |
| `auditable_type` | `'ControlledDocument'` (or fully-qualified model class) |
| `auditable_id` | `controlled_documents.id` (mirrors `reference_id`) |
| `old_values` | JSON (previous field values) |
| `new_values` | JSON (new field values) |
| `actor_id` | `users.id` (FK) |

### 6.5 Workflow Instances (`workflow_instances`)

Each document that enters a workflow gets a workflow instance tracking its progression.

| Column | Value |
|---|---|
| `module_name` | `'document'` |
| `reference_id` | `controlled_documents.id` |
| `workflow_definition_id` | FK to `workflow_definitions.id` |
| `current_status` | Mirrors `controlled_documents.status` |
| `started_by` | `users.id` (FK) |
| `completed_at` | nullable, set when document is `obsolete` |

### 6.6 Workflow Histories (`workflow_histories`)

Every workflow transition (status change) for a document is logged here.

| Column | Value |
|---|---|
| `module_name` | `'document'` |
| `reference_id` | `controlled_documents.id` |
| `workflow_instance_id` | FK to `workflow_instances.id` |
| `from_status` | Previous status (e.g., `'draft'`) |
| `to_status` | New status (e.g., `'review'`) |
| `action_key` | `'submit_review'`, `'approve'`, `'make_effective'`, `'obsolete'`, `'reject'`, `'revise'` |
| `actor_id` | `users.id` (FK) |

### Shared Relations Summary

```
                          ┌──────────────────────┐
                          │ controlled_documents  │
                          │  (id: PK)             │
                          └──────┬───────────────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
              module_name='document'          │
              reference_id=documents.id       │
                    │            │            │
    ┌───────────────▼──┐  ┌────▼────────┐  ┌─▼──────────────┐
    │  managed_files    │  │  comments   │  │ activity_logs  │
    │  (document_file)  │  │ (discussion)│  │  (timeline)    │
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

    All linked via: module_name='document' AND reference_id=controlled_documents.id
    Plus: document_reviews (hard FK, cascade) for version tracking
```

---

## 7. Migration File Naming Convention

### Convention

Migrations follow Laravel's standard naming pattern:

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{verb}_{table_name}_table.php
```

### Migrations for This Module

| # | Migration File | Description |
|---|---|---|
| 1 | `create_controlled_documents_table.php` | Creates the `controlled_documents` table |
| 2 | `create_document_reviews_table.php` | Creates the `document_reviews` table |

### Example Migration Files

**File 1: `create_controlled_documents_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('document_number', 50)->unique();
            $table->string('title', 255);
            $table->string('type', 20);
            $table->string('version', 20)->default('1.0');
            $table->text('revision_notes')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('review_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_confidential')->default(false);
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('department_id');
            $table->index('owner_id');
            $table->index('approver_id');
            $table->index('effective_date');
            $table->index('review_date');
            $table->index('expiry_date');
            $table->index('created_at');
        });

        // Add CHECK constraints
        DB::statement("ALTER TABLE controlled_documents ADD CONSTRAINT controlled_documents_type_check CHECK (type IN ('sop','wi','jsa','hiradc','msds','policy','form','manual','other'))");
        DB::statement("ALTER TABLE controlled_documents ADD CONSTRAINT controlled_documents_status_check CHECK (status IN ('draft','review','approved','effective','obsolete','rejected'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_documents');
    }
};
```

**File 2: `create_document_reviews_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('document_id')->constrained('controlled_documents')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_date')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('decision', 20)->default('pending');
            $table->timestamps();

            $table->index('document_id');
            $table->index('reviewer_id');
            $table->index('decision');
            $table->index('review_date');
        });

        DB::statement("ALTER TABLE document_reviews ADD CONSTRAINT document_reviews_decision_check CHECK (decision IN ('pending','approve','reject','revise'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('document_reviews');
    }
};
```

---

## 8. Eloquent Model Definitions (Reference)

### `ControlledDocument` Model

```php
class ControlledDocument extends Model
{
    protected $table = 'controlled_documents';

    protected $fillable = [
        'document_number', 'title', 'type', 'version', 'revision_notes',
        'effective_date', 'review_date', 'expiry_date',
        'department_id', 'owner_id', 'approver_id',
        'status', 'is_confidential',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'review_date' => 'date',
        'expiry_date' => 'date',
        'is_confidential' => 'boolean',
    ];

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class, 'document_id');
    }

    public function files(): MorphMany
    {
        return ManagedFile::where('module_name', 'document')
            ->where('reference_id', $this->id)
            ->where('collection', 'document_file');
    }

    // Scopes
    public function scopeEffective(Builder $query): Builder
    {
        return $query->where('status', 'effective');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
```

### `DocumentReview` Model

```php
class DocumentReview extends Model
{
    protected $table = 'document_reviews';

    protected $fillable = [
        'document_id', 'reviewer_id', 'review_date',
        'review_notes', 'decision',
    ];

    protected $casts = [
        'review_date' => 'date',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'document_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
```

# Data Model — APD / PPE Management

> Phase 21 schema for the APD / PPE module.  \
> Laravel 12 + PostgreSQL. bigint PKs, string enums, polymorphic `module_name + reference_id` for cross-cutting services (files, comments, logs).  \
> Module: `21-apd-ppe`.

---

## 1. Table of Contents
1. `apd_catalogs` (katalog jenis APD)
2. `apd_items` (inventori — serial & batch)
3. `apd_issuances` (penugasan ke pemegang)
4. `apd_inspections` (inspeksi)
5. `risk_apd_requirements` (hazard → APD wajib)
6. ERD (ASCII)
7. Indexes
8. Shared Relations
9. Migration Naming

---

## 2. `apd_catalogs`

Definisi jenis APD (master). Satu baris = satu jenis (helm, sarung tangan, dll).

| # | Column | Type | Null | Default | Notes |
|---|---|---|---|---|---|
| 1 | `id` | bigint | NO | — | PK |
| 2 | `catalog_number` | varchar(50) | NO | — | Unique, `PPE-YYYY-NNNN` (NumberingService) |
| 3 | `name` | varchar(255) | NO | — | Nama jenis, mis. "Helm Safety" |
| 4 | `body_part` | varchar(30) | NO | — | `head`,`eye`,`hand`,`foot`,`respiratory`,`body` |
| 5 | `standard` | varchar(100) | YES | NULL | SNI/EN/ANSI |
| 6 | `default_track_type` | varchar(10) | NO | `'batch'` | `serial`/`batch` |
| 7 | `default_lifespan_months` | int | YES | NULL | Masa pakai default utk jadwal inspeksi |
| 8 | `unit` | varchar(20) | NO | `'pcs'` | Satuan |
| 9 | `min_stock` | int | NO | `0` | Ambang stok rendah (level katalog) |
| 10 | `description` | text | YES | NULL | |
| 11 | `is_active` | boolean | NO | `true` | |
| 12 | `created_by` | bigint | NO | — | FK→users |
| 13 | `created_at`/`updated_at` | timestamp | NO | — | |

```php
Schema::create('apd_catalogs', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('catalog_number', 50)->unique();
    $table->string('name', 255);
    $table->string('body_part', 30);
    $table->string('standard', 100)->nullable();
    $table->string('default_track_type', 10)->default('batch');
    $table->integer('default_lifespan_months')->nullable();
    $table->string('unit', 20)->default('pcs');
    $table->integer('min_stock')->default(0);
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->check("body_part IN ('head','eye','hand','foot','respiratory','body')", 'apd_catalogs_body_part_check');
    $table->check("default_track_type IN ('serial','batch')", 'apd_catalogs_track_type_check');
});
```

---

## 3. `apd_items`

Inventori fisik. Satu baris = 1 unit serial ATAU 1 lot batch (N unit).

| # | Column | Type | Null | Default | Notes |
|---|---|---|---|---|---|
| 1 | `id` | bigint | NO | — | PK |
| 2 | `catalog_id` | bigint | NO | — | FK→apd_catalogs |
| 3 | `track_type` | varchar(10) | NO | — | `serial`/`batch` |
| 4 | `serial_number` | varchar(100) | YES | NULL | Wajib jika serial; unique saat tidak null |
| 5 | `quantity` | int | YES | NULL | Wajib jika batch (total unit lot) |
| 6 | `available_quantity` | int | YES | NULL | Sisa batch yg bisa di-issue |
| 7 | `site_id` | bigint | NO | — | FK→sites |
| 8 | `area_id` | bigint | YES | NULL | FK→areas (pos/lokasi simpan) |
| 9 | `location_note` | varchar(255) | YES | NULL | Detail lokasi |
| 10 | `lot_number` | varchar(100) | YES | NULL | |
| 11 | `manufacturer` | varchar(150) | YES | NULL | |
| 12 | `model` | varchar(150) | YES | NULL | |
| 13 | `received_date` | date | YES | NULL | |
| 14 | `expiry_date` | date | YES | NULL | Untuk disposable/kadaluarsa |
| 15 | `condition` | varchar(20) | NO | `'good'` | `good`,`worn`,`damaged`,`expired` |
| 16 | `status` | varchar(20) | NO | `'available'` | Lihat enum bawah |
| 17 | `next_inspection_date` | date | YES | NULL | Untuk serial (jadwal) |
| 18 | `current_holder_type` | varchar(20) | YES | NULL | `employee`/`contractor`/`location` |
| 19 | `current_holder_id` | bigint | YES | NULL | FK polimorfik |
| 20 | `created_by` | bigint | NO | — | FK→users |
| 21 | `created_at`/`updated_at` | timestamp | NO | — | |

**Status enum** (`serial`): `available`, `in_use`, `under_inspection`, `damaged`, `expired`, `disposed`.  \
**Status enum** (`batch`): diwakili `available_quantity` (0 = habis). Row batch tidak per-unit.

```php
Schema::create('apd_items', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('catalog_id')->constrained('apd_catalogs')->cascadeOnDelete();
    $table->string('track_type', 10);
    $table->string('serial_number', 100)->nullable()->unique('apd_items_serial_unique');
    $table->integer('quantity')->nullable();
    $table->integer('available_quantity')->nullable();
    $table->foreignId('site_id')->constrained('sites');
    $table->foreignId('area_id')->nullable()->constrained('areas');
    $table->string('location_note', 255)->nullable();
    $table->string('lot_number', 100)->nullable();
    $table->string('manufacturer', 150)->nullable();
    $table->string('model', 150)->nullable();
    $table->date('received_date')->nullable();
    $table->date('expiry_date')->nullable();
    $table->string('condition', 20)->default('good');
    $table->string('status', 20)->default('available');
    $table->date('next_inspection_date')->nullable();
    $table->string('current_holder_type', 20)->nullable();
    $table->bigInteger('current_holder_id')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->check("track_type IN ('serial','batch')", 'apd_items_track_type_check');
    $table->check("condition IN ('good','worn','damaged','expired')", 'apd_items_condition_check');
    $table->check("status IN ('available','in_use','under_inspection','damaged','expired','disposed')", 'apd_items_status_check');
    // serial wajib punya serial_number
    $table->check("(track_type = 'batch') OR (track_type = 'serial' AND serial_number IS NOT NULL)", 'apd_items_serial_required_check');
});
```

---

## 4. `apd_issuances`

Penugasan APD ke pemegang. 1 issuance = 1 item serial ATAU 1 lot batch (quantity).

| # | Column | Type | Null | Default | Notes |
|---|---|---|---|---|---|
| 1 | `id` | bigint | NO | — | PK |
| 2 | `issue_number` | varchar(50) | NO | — | Unique `PPE-ISSUE-YYYY-NNNN` |
| 3 | `apd_item_id` | bigint | NO | — | FK→apd_items |
| 4 | `quantity` | int | NO | `1` | Untuk batch; serial selalu 1 |
| 5 | `issued_to_type` | varchar(20) | NO | — | `employee`/`contractor`/`location` |
| 6 | `issued_to_id` | bigint | NO | — | FK polimorfik |
| 7 | `requested_by` | bigint | YES | NULL | FK→users (jika dari request) |
| 8 | `approved_by` | bigint | YES | NULL | FK→users |
| 9 | `issued_by` | bigint | YES | NULL | FK→users |
| 10 | `issue_date` | date | YES | NULL | |
| 11 | `expected_return_date` | date | YES | NULL | |
| 12 | `expiry_date` | date | YES | NULL | Kadaluarsa pemakaian |
| 13 | `status` | varchar(20) | NO | `'draft'` | Lihat enum |
| 14 | `condition_out` | varchar(20) | YES | NULL | Kondisi saat issue |
| 15 | `condition_in` | varchar(20) | YES | NULL | Kondisi saat return |
| 16 | `notes` | text | YES | NULL | |
| 17 | `created_at`/`updated_at` | timestamp | NO | — | |

**Status enum**: `draft`, `requested`, `approved`, `issued`, `returned`, `disposed`, `rejected`.

```php
Schema::create('apd_issuances', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('issue_number', 50)->unique();
    $table->foreignId('apd_item_id')->constrained('apd_items');
    $table->integer('quantity')->default(1);
    $table->string('issued_to_type', 20);
    $table->bigInteger('issued_to_id');
    $table->foreignId('requested_by')->nullable()->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->foreignId('issued_by')->nullable()->constrained('users');
    $table->date('issue_date')->nullable();
    $table->date('expected_return_date')->nullable();
    $table->date('expiry_date')->nullable();
    $table->string('status', 20)->default('draft');
    $table->string('condition_out', 20)->nullable();
    $table->string('condition_in', 20)->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->check("issued_to_type IN ('employee','contractor','location')", 'apd_issuances_holder_check');
    $table->check("status IN ('draft','requested','approved','issued','returned','disposed','rejected')", 'apd_issuances_status_check');
    $table->index(['issued_to_type','issued_to_id'], 'apd_issuances_holder_index');
});
```

---

## 5. `apd_inspections`

Inspeksi per item serial.

| # | Column | Type | Null | Default | Notes |
|---|---|---|---|---|---|
| 1 | `id` | bigint | NO | — | PK |
| 2 | `apd_item_id` | bigint | NO | — | FK→apd_items (serial) |
| 3 | `inspection_type` | varchar(20) | NO | — | `scheduled`,`incidental`,`manual` |
| 4 | `inspected_by` | bigint | NO | — | FK→users |
| 5 | `inspection_date` | date | NO | — | |
| 6 | `result` | varchar(20) | NO | — | `layak`,`tidak_layak` |
| 7 | `condition` | varchar(20) | YES | NULL | `good`,`worn`,`damaged`,`expired` |
| 8 | `next_inspection_date` | date | YES | NULL | |
| 9 | `notes` | text | YES | NULL | |
| 10 | `created_at`/`updated_at` | timestamp | NO | — | |

Foto: `managed_files` collection `inspection`, `module_name='apd'`.

```php
Schema::create('apd_inspections', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('apd_item_id')->constrained('apd_items')->cascadeOnDelete();
    $table->string('inspection_type', 20);
    $table->foreignId('inspected_by')->constrained('users');
    $table->date('inspection_date');
    $table->string('result', 20);
    $table->string('condition', 20)->nullable();
    $table->date('next_inspection_date')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->check("inspection_type IN ('scheduled','incidental','manual')", 'apd_inspections_type_check');
    $table->check("result IN ('layak','tidak_layak')", 'apd_inspections_result_check');
    $table->index(['apd_item_id','inspection_date'], 'apd_inspections_item_date_index');
});
```

---

## 6. `risk_apd_requirements`

Link hazard (RiskRegister) → APD wajib (ApdCatalog).

| # | Column | Type | Null | Default | Notes |
|---|---|---|---|---|---|
| 1 | `id` | bigint | NO | — | PK |
| 2 | `risk_register_id` | bigint | NO | — | FK→risk_registers |
| 3 | `apd_catalog_id` | bigint | NO | — | FK→apd_catalogs |
| 4 | `mandatory` | boolean | NO | `true` | Wajib vs rekomendasi |
| 5 | `notes` | text | YES | NULL | |
| 6 | `created_at`/`updated_at` | timestamp | NO | — | |

```php
Schema::create('risk_apd_requirements', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
    $table->foreignId('apd_catalog_id')->constrained('apd_catalogs')->cascadeOnDelete();
    $table->boolean('mandatory')->default(true);
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->unique(['risk_register_id','apd_catalog_id'], 'risk_apd_req_unique');
});
```

---

## 7. ERD (ASCII)

```
┌──────────────────┐      ┌──────────────────┐      ┌────────────────────┐
│  apd_catalogs    │ 1:N  │    apd_items     │ 1:N  │   apd_issuances     │
├──────────────────┤◄─────┤──────────────────┤◄─────┤────────────────────┤
│ id PK            │      │ id PK            │      │ id PK              │
│ catalog_number   │      │ catalog_id FK    │      │ issue_number UNIQUE│
│ name             │      │ track_type       │      │ apd_item_id FK     │
│ body_part        │      │ serial_number    │      │ quantity           │
│ standard         │      │ quantity         │      │ issued_to_type     │
│ default_track..  │      │ available_qty    │      │ issued_to_id       │
│ min_stock        │      │ site_id FK       │      │ status             │
└──────────────────┘      │ status           │      └────────────────────┘
                          │ current_holder.. │                │
                          └────────┬─────────┘                │
                                   │ 1:N                      │
                          ┌────────▼─────────┐                │
                          │  apd_inspections │                │
                          ├──────────────────┤                │
                          │ id PK            │                │
                          │ apd_item_id FK   │                │
                          │ inspection_type  │                │
                          │ result           │                │
                          └──────────────────┘                │
                                                            │ holder poly
                          ┌──────────────────┐               │
                          │ employees/        │◄──────────────┘
                          │ contractors/      │   issued_to_type/id
                          │ areas             │
                          └──────────────────┘

┌──────────────────┐      ┌──────────────────────────────┐
│  risk_registers  │ 1:N  │   risk_apd_requirements       │
├──────────────────┤◄─────┤──────────────────────────────┤
│ id PK            │      │ risk_register_id FK           │
│                  │      │ apd_catalog_id FK ────────────┼──► apd_catalogs
└──────────────────┘      │ mandatory                     │
                          └──────────────────────────────┘
```

**Shared relations** (`module_name='apd'`):
- `managed_files` — `catalog_photo`, `inspection`
- `comments` — diskusi
- `activity_logs` — timeline
- `audit_logs` — field changes
- `workflow_histories` — issuance transitions

---

## 8. Indexes (ringkas)

| Table | Columns | Purpose |
|---|---|---|
| apd_catalogs | catalog_number | lookup/unique |
| apd_catalogs | body_part | filter |
| apd_items | serial_number | unique lookup |
| apd_items | catalog_id | filter jenis |
| apd_items | site_id, area_id | scope |
| apd_items | status, condition | filter |
| apd_items | next_inspection_date, expiry_date | scheduler |
| apd_items | (current_holder_type, current_holder_id) | cari milik pemegang |
| apd_issuances | issue_number | unique |
| apd_issuances | (issued_to_type, issued_to_id) | milik pemegang |
| apd_issuances | status | filter workflow |
| apd_issuances | apd_item_id | item history |
| apd_inspections | (apd_item_id, inspection_date) | latest |
| risk_apd_requirements | (risk_register_id, apd_catalog_id) | unique |

---

## 9. Migration Naming

```
2026_07_16_000001_create_apd_catalogs_table.php
2026_07_16_000002_create_apd_items_table.php
2026_07_16_000003_create_apd_issuances_table.php
2026_07_16_000004_create_apd_inspections_table.php
2026_07_16_000005_create_risk_apd_requirements_table.php
```

Seeder: `database/seeders/ApdSeeder.php` — seed permission `apd.*`, sample catalog (helm, sepatu, sarung tangan, respirator, harness), sample items, sample issuance untuk 1 employee.

---

## 10. Catatan Desain

- **Tidak ada soft delete wajib** di `apd_items` untuk serial rusak → status `disposed` (audit retention). `apd_catalogs` gunakan `is_active` (bukan delete) agar histori issuance tetap valid. Soft delete bisa ditambah bila regulasi butuh.
- **Polymorphic holder** hindari FK fisik → validasi di Service/Request.
- **numbering** immutable; issuance & catalog generate di create.
- **Link ke Incident/Training/Inspection** bersifat logical (field di tabel modul lain / `risk_apd_requirements`), bukan FK dari tabel APD — sesuai pola cross-module modul lain.

# APD / PPE Management

**Ringkasan:** Alat Pelindung Diri: katalog, item (track serial/qty), issuance, inspeksi, requirement.

**Permission prefix:** `apd`

**Workflow states:** catalog: active/inactive; item: available/in_use/damaged/expired; issuance: requested → approved → issued → returned

## Fields (skema DB aktual)

### Tabel `apd_catalogs`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `catalog_code` |  |
| `category` |  |
| `track_type` |  |
| `name` |  |
| `sku` |  |
| `manufacturer` |  |
| `model` |  |
| `description` |  |
| `standard` |  |
| `size` |  |
| `protection_level` |  |
| `default_lifespan_months` |  |
| `inspection_interval_days` |  |
| `default_unit_cost` |  |
| `min_stock` |  |
| `reorder_point` |  |
| `is_active` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `deleted_at` | soft-delete / recovery |

### Tabel `apd_items`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `item_number` |  |
| `catalog_id` | FK referensi |
| `track_type` |  |
| `serial_number` |  |
| `quantity` |  |
| `unit_cost` |  |
| `site_id` | FK organisasi/user |
| `area_id` | FK organisasi/user |
| `department_id` | FK organisasi/user |
| `storage_location` |  |
| `status` | state workflow |
| `condition` |  |
| `manufacture_date` |  |
| `purchase_date` |  |
| `received_date` |  |
| `expiry_date` | tanggal penting (overdue/expiry) |
| `next_inspection_date` |  |
| `holder_type` |  |
| `holder_id` | FK referensi |
| `notes` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `deleted_at` | soft-delete / recovery |

### Tabel `apd_issuances`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `issue_number` |  |
| `apd_item_id` | FK referensi |
| `quantity` |  |
| `holder_type` |  |
| `holder_id` | FK referensi |
| `requested_by` |  |
| `approved_by` |  |
| `issued_by` |  |
| `returned_by` |  |
| `requested_date` |  |
| `issue_date` |  |
| `expected_return_date` |  |
| `returned_date` |  |
| `expiry_date` | tanggal penting (overdue/expiry) |
| `status` | state workflow |
| `condition_out` |  |
| `condition_in` |  |
| `notes` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |

### Tabel `apd_inspections`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `apd_item_id` | FK referensi |
| `inspection_type` |  |
| `inspected_by` |  |
| `inspection_date` |  |
| `result` |  |
| `condition` |  |
| `next_inspection_date` |  |
| `notes` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `deleted_at` | soft-delete / recovery |

### Tabel `apd_requirements`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `risk_register_id` | FK referensi |
| `apd_catalog_id` | FK referensi |
| `quantity` |  |
| `notes` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `deleted_at` | soft-delete / recovery |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /apd/catalogs` | |
| `/apd/items` | |
| `/apd/issuances/{id}/request|approve|issue|process` | |
| `/apd/inspections` | |
| `apd.requirements.manage` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

scopeLowStock pakai where(min_stock,'>',0) (fix Postgres). apd_requirements terikat risk_register. Track type: serial vs quantity.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

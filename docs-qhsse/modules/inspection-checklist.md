# Inspection Checklist

**Ringkasan:** Inspeksi area & multi-unit. Daftar Unit diambil dari master Asset (searchable multi-select).

**Permission prefix:** `inspection.checklists`

**Workflow states:** scheduled → executed → completed (per-unit: pending → done/cancelled)

## Fields (skema DB aktual)

### Tabel `inspections`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `inspection_number` |  |
| `inspection_template_id` | FK referensi |
| `site_id` | FK organisasi/user |
| `area_id` | FK organisasi/user |
| `inspector_id` | FK referensi |
| `scheduled_at` |  |
| `executed_at` |  |
| `status` | state workflow |
| `overall_result` |  |
| `notes` |  |
| `created_at` |  |
| `updated_at` |  |

### Tabel `inspection_units`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `inspection_id` | FK referensi |
| `identifier` |  |
| `status` | state workflow |
| `notes` |  |
| `cancelled_reason` |  |
| `created_at` |  |
| `updated_at` |  |
| `asset_id` | FK referensi |

### Tabel `inspection_results`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `inspection_id` | FK referensi |
| `inspection_item_id` | FK referensi |
| `answer` |  |
| `remark` |  |
| `is_unsafe` |  |
| `created_at` |  |
| `updated_at` |  |
| `photo` | file privat (ManagedFileService) |
| `inspection_unit_id` | FK referensi |

### Tabel `inspection_items`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `inspection_template_id` | FK referensi |
| `question` |  |
| `type` |  |
| `category` |  |
| `is_required` |  |
| `order` |  |
| `created_at` |  |
| `updated_at` |  |

### Tabel `inspection_templates`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `code` |  |
| `name` |  |
| `description` |  |
| `category` |  |
| `is_active` |  |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /inspections` | |
| `/inspections/{id}/units/{unit}/save` | |
| `/export-units` | |
| `inspection.checklists.execute` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

inspection_units.asset_id FK → assets (nullable). Complete terkunci sampai semua unit done/cancelled. inspection_results punya photo (ManagedFileService private).

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

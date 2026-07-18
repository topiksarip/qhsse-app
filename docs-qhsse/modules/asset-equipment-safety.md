# Asset & Equipment Safety

**Ringkasan:** Aset & peralatan: master, sertifikat, inspeksi aset, decommission.

**Permission prefix:** `asset.management / asset.certificates / asset.inspections`

**Workflow states:** asset: active → decommissioned; certificate/inspection tercatat

## Fields (skema DB aktual)

### Tabel `assets`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `asset_number` |  |
| `name` |  |
| `category` |  |
| `serial_number` |  |
| `model` |  |
| `manufacturer` |  |
| `site_id` | FK organisasi/user |
| `area_id` | FK organisasi/user |
| `department_id` | FK organisasi/user |
| `purchase_date` |  |
| `installation_date` |  |
| `warranty_expiry_date` |  |
| `status` | state workflow |
| `safety_critical` |  |
| `next_inspection_date` |  |
| `description` |  |
| `notes` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `legacy_deleted_at` | soft-delete / recovery |
| `legacy_status_before_deletion` | soft-delete / recovery |
| `deleted_at` | soft-delete / recovery |

### Tabel `asset_certificates`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `asset_id` | FK referensi |
| `certificate_type` |  |
| `certificate_number` |  |
| `issuing_body` |  |
| `issued_date` |  |
| `expiry_date` | tanggal penting (overdue/expiry) |
| `status` | state workflow |
| `notes` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `certificate_file_id` | FK referensi |
| `legacy_deleted_at` | soft-delete / recovery |
| `deleted_at` | soft-delete / recovery |

### Tabel `asset_inspections`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `asset_id` | FK referensi |
| `inspection_date` |  |
| `inspector_id` | FK referensi |
| `result` |  |
| `next_inspection_date` |  |
| `notes` |  |
| `findings` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `legacy_deleted_at` | soft-delete / recovery |
| `deleted_at` | soft-delete / recovery |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /assets` | |
| `/assets/{id}/certificates` | |
| `/assets/{id}/inspections` | |
| `assets.decommission` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

Sumber Daftar Unit untuk Inspection. SoftDelete aktif (legacy_deleted_at disimpan utk recovery). certificate_file_id → managed_files.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

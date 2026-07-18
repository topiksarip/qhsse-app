# Reporting & Export

**Ringkasan:** Template laporan & generate/saved report (background).

**Permission prefix:** `reporting.templates / reporting.reports`

**Workflow states:** template: active; saved_report: pending → generated/completed → failed

## Fields (skema DB aktual)

### Tabel `report_templates`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `name` |  |
| `type` |  |
| `description` |  |
| `config` |  |
| `is_active` |  |
| `is_predefined` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `deleted_at` | soft-delete / recovery |

### Tabel `saved_reports`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `name` |  |
| `template_id` | FK referensi |
| `status` | state workflow |
| `parameters` |  |
| `format` |  |
| `file_path` | file privat (ManagedFileService) |
| `file_size` | file privat (ManagedFileService) |
| `generated_by` |  |
| `generated_at` |  |
| `completed_at` |  |
| `failed_at` |  |
| `error_message` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `deleted_at` | soft-delete / recovery |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /reports (templates/saved)` | |
| `reporting.reports.generate` | |
| `reporting.reports.download` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

saved_reports pakai file_path + status async (generated_at/failed_at/error_message).

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

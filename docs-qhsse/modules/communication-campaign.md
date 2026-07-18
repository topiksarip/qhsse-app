# Communication & Campaign

**Ringkasan:** Kampanye keselamatan & acknowledgment karyawan.

**Permission prefix:** `communication.campaigns`

**Workflow states:** draft → published → expired (communication.campaigns.publish); acknowledge per user

## Fields (skema DB aktual)

### Tabel `campaigns`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `campaign_number` |  |
| `title` |  |
| `type` |  |
| `content` |  |
| `target_audience` |  |
| `site_id` | FK organisasi/user |
| `department_id` | FK organisasi/user |
| `target_role` |  |
| `status` | state workflow |
| `published_at` |  |
| `expires_at` |  |
| `view_count` |  |
| `author_id` | FK referensi |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `deleted_at` | soft-delete / recovery |

### Tabel `campaign_acknowledgments`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `campaign_id` | FK referensi |
| `user_id` | FK referensi |
| `acknowledged_at` |  |
| `ip_address` |  |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /campaigns` | |
| `/campaigns/{id}/publish` | |
| `/acknowledge` | |
| `communication.campaigns.export` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

campaign_acknowledgments mencatat ip_address + acknowledged_at. target_role / target_audience mem-filter.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

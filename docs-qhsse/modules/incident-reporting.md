# Incident Reporting

**Ringkasan:** Pelaporan insiden, near-miss, unsafe act/condition, environmental spill, dengan bukti & kaitan APD/PPE.

**Permission prefix:** `incident.reports`

**Workflow states:** draft → submitted → under_review → closed (via incident.reports.submit/review/close)

## Fields (skema DB aktual)

### Tabel `incidents`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `incident_number` |  |
| `title` |  |
| `category` |  |
| `occurred_at` |  |
| `site_id` | FK organisasi/user |
| `area_id` | FK organisasi/user |
| `department_id` | FK organisasi/user |
| `reporter_id` | FK referensi |
| `severity_id` | FK referensi |
| `priority_id` | FK referensi |
| `description` |  |
| `immediate_action` |  |
| `status` | state workflow |
| `created_at` |  |
| `updated_at` |  |
| `ppe_involved` |  |
| `apd_item_id` | FK referensi |
| `ppe_failure` |  |
| `ppe_notes` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |

### Tabel `incident_involved_persons`

| Kolom | Keterangan |
|-------|-----------|

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /incidents` | |
| `/incidents/{id}` | |
| `incident.reports.submit` | |
| `incident.reports.review` | |
| `incident.reports.close` | |
| `incident.reports.evidence (file upload)` | |
| `incident.reports.export` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

Field PPE: ppe_involved, apd_item_id, ppe_failure, ppe_notes. Terhubung ke investigations & capa_actions (source_module='incident'). SoftDelete via deleted_at.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

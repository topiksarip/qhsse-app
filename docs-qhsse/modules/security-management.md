# Security Management

**Ringkasan:** Insiden keamanan, visitor log, & patroli.

**Permission prefix:** `security.incidents / security.visitors / security.patrols`

**Workflow states:** security_incident: open → closed (security.incidents.close); visitor: checked_in → checked_out; patrol: scheduled → completed

## Fields (skema DB aktual)

### Tabel `security_incidents`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `security_number` |  |
| `type` |  |
| `title` |  |
| `description` |  |
| `site_id` | FK organisasi/user |
| `area_id` | FK organisasi/user |
| `occurred_at` |  |
| `reported_by` |  |
| `severity_id` | FK referensi |
| `status` | state workflow |
| `resolution` |  |
| `resolved_at` |  |
| `created_at` |  |
| `updated_at` |  |

### Tabel `visitor_logs`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `visitor_name` |  |
| `visitor_type` |  |
| `visitor_id_number` |  |
| `visitor_company` |  |
| `visitor_phone` |  |
| `host_employee_id` | FK referensi |
| `site_id` | FK organisasi/user |
| `purpose` |  |
| `vehicle_number` |  |
| `checked_in_at` |  |
| `checked_out_at` |  |
| `checked_in_by` |  |
| `checked_out_by` |  |
| `status` | state workflow |
| `notes` |  |
| `created_at` |  |
| `updated_at` |  |

### Tabel `patrol_checklists`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `patrol_number` |  |
| `title` |  |
| `description` |  |
| `site_id` | FK organisasi/user |
| `area_id` | FK organisasi/user |
| `scheduled_at` |  |
| `assigned_to` |  |
| `status` | state workflow |
| `started_at` |  |
| `completed_at` |  |
| `completed_by` |  |
| `notes` |  |
| `created_at` |  |
| `updated_at` |  |

### Tabel `patrol_results`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `patrol_checklist_id` | FK referensi |
| `checkpoint` |  |
| `result` |  |
| `findings` |  |
| `checked_at` |  |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /security (incidents/visitors/patrols)` | |
| `security.incidents.close` | |
| `security.visitors.check_out` | |
| `security.patrols.execute` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

patrol_results terikat patrol_checklists (per-checkpoint result/findings). visitor_logs punya host + check in/out.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

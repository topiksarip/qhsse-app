# CAPA / Action Tracking

**Ringkasan:** Corrective & Preventive Action lintas modul (incident, investigation, audit, environment, quality).

**Permission prefix:** `capa.actions`

**Workflow states:** open → assigned → in_progress → submitted → verified → closed; reject → reopen; restart (capa.actions.submit/verify/reject/restart/close)

## Fields (skema DB aktual)

### Tabel `capa_actions`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `action_number` |  |
| `title` |  |
| `description` |  |
| `source_module` |  |
| `source_reference_id` | FK referensi |
| `source_type` |  |
| `site_id` | FK organisasi/user |
| `department_id` | FK organisasi/user |
| `assigned_to` |  |
| `assigned_by` |  |
| `assigned_at` |  |
| `due_date` | tanggal penting (overdue/expiry) |
| `severity_id` | FK referensi |
| `priority_id` | FK referensi |
| `status` | state workflow |
| `verification_note` |  |
| `verified_by` |  |
| `verified_at` |  |
| `closed_at` |  |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /capa-actions` | |
| `/capa-actions/{id}` | |
| `capa.actions.start` | |
| `capa.actions.submit-verification` | |
| `capa.actions.verify-close` | |
| `capa.actions.reject` | |
| `capa.actions.restart` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

Generic: source_module + source_reference_id + source_type. Mendukung workflow transisi + overdue (due_date).

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

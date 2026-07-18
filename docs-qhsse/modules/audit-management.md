# Audit Management

**Ringkasan:** Audit internal: perencanaan, eksekusi, temuan, close-out, generate report.

**Permission prefix:** `audit.management`

**Workflow states:** planned → in_progress → closed (audits.start/close); findings: open → closed

## Fields (skema DB aktual)

### Tabel `audits`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `audit_number` |  |
| `title` |  |
| `audit_type` |  |
| `scope` |  |
| `department_id` | FK organisasi/user |
| `lead_auditor_id` | FK referensi |
| `scheduled_date` |  |
| `start_date` |  |
| `end_date` |  |
| `report_date` |  |
| `close_date` |  |
| `status` | state workflow |
| `summary` |  |
| `created_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |

### Tabel `audit_findings`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `audit_id` | FK referensi |
| `finding_number` |  |
| `classification` |  |
| `description` |  |
| `recommendation` |  |
| `capa_action_id` | FK referensi |
| `status` | state workflow |
| `due_date` | tanggal penting (overdue/expiry) |
| `closed_date` |  |
| `closed_by` |  |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /audits` | |
| `/audits/{id}/start` | |
| `/findings` | |
| `/findings/{finding}/close` | |
| `/generate-report` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

audit_findings.capital_action_id → capa_actions. Report di-generate (PDF/file).

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

# Permit to Work

**Ringkasan:** Izin kerja: pengajuan, approval, checklist, close/cancel.

**Permission prefix:** `permit.work / permit.checklist`

**Workflow states:** draft → approved → closed/cancelled (permit.work.approve/close/cancel)

## Fields (skema DB aktual)

### Tabel `permits`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `permit_number` |  |
| `type` |  |
| `title` |  |
| `description` |  |
| `site_id` | FK organisasi/user |
| `area_id` | FK organisasi/user |
| `department_id` | FK organisasi/user |
| `contractor_id` | FK referensi |
| `work_location` |  |
| `work_description` |  |
| `start_datetime` |  |
| `end_datetime` |  |
| `validity_hours` |  |
| `status` | state workflow |
| `risk_level` |  |
| `jsa_reference` |  |
| `approved_by` |  |
| `approved_at` |  |
| `closed_by` |  |
| `closed_at` |  |
| `cancellation_reason` |  |
| `created_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |

### Tabel `permit_checklists`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `permit_id` | FK referensi |
| `item_text` |  |
| `is_checked` |  |
| `checked_by` |  |
| `checked_at` |  |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /permit` | |
| `/permits/{id}/approve` | |
| `/close` | |
| `/cancel` | |
| `permit.checklist.sign` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

permit_checklists bisa di-sign (checked_by/checked_at). Terkait contractor_id & JSA (jsa_reference).

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

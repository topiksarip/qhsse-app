# Emergency Preparedness

**Ringkasan:** Rencana darurat, drill, & kontak darurat.

**Permission prefix:** `emergency.plans / emergency.drills / emergency.contacts`

**Workflow states:** plan: active; drill: scheduled → executed → closed (emergency.drills.execute)

## Fields (skema DB aktual)

### Tabel `emergency_plans`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `plan_number` |  |
| `name` |  |
| `type` |  |
| `site_id` | FK organisasi/user |
| `description` |  |
| `response_procedure` |  |
| `escalation_procedure` |  |
| `contact_person_id` | FK referensi |
| `emergency_contacts` |  |
| `equipment_needed` |  |
| `created_at` |  |
| `updated_at` |  |

### Tabel `emergency_drills`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `drill_number` |  |
| `emergency_plan_id` | FK referensi |
| `scheduled_date` |  |
| `executed_date` |  |
| `site_id` | FK organisasi/user |
| `participants_count` |  |
| `observer_id` | FK referensi |
| `result` |  |
| `findings` |  |
| `recommendations` |  |
| `status` | state workflow |
| `created_at` |  |
| `updated_at` |  |

### Tabel `emergency_contacts`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `name` |  |
| `role` |  |
| `phone` |  |
| `email` |  |
| `site_id` | FK organisasi/user |
| `is_active` |  |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /emergency (plans/drills/contacts)` | |
| `emergency.drills.execute` | |
| `emergency.drills.export` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

emergency_plans punya response/escalation_procedure + emergency_contacts (JSON). drill terikat plan.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

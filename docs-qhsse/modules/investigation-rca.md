# Investigation & RCA

**Ringkasan:** Investigasi akar masalah insiden: 5-Why, Fishbone, faktor penyumbang, rekomendasi.

**Permission prefix:** `investigation.reports`

**Workflow states:** draft → submitted → under_review → closed (investigation.reports.submit/review/close)

## Fields (skema DB aktual)

### Tabel `investigations`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `investigation_number` |  |
| `incident_id` | FK referensi |
| `title` |  |
| `status` | state workflow |
| `root_cause` |  |
| `five_whys` |  |
| `fishbone` |  |
| `contributing_factors` |  |
| `timeline_events` |  |
| `recommendations` |  |
| `investigator_id` | FK referensi |
| `started_at` |  |
| `completed_at` |  |
| `created_at` |  |
| `updated_at` |  |

### Tabel `investigation_team`

| Kolom | Keterangan |
|-------|-----------|

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /investigations` | |
| `/investigations/{id}` | |
| `investigation.reports.submit` | |
| `investigation.reports.review` | |
| `investigation.reports.close` | |
| `investigation.reports.export` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

Menghasilkan recommendations yang biasanya berujung ke capa_actions. investigator_id mengikat user.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

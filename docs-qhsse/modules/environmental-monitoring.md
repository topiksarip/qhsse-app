# Environmental Monitoring

**Ringkasan:** Pemantauan lingkungan: emisi, limbah, ekskursi (exceedance), tindak lanjut CAPA.

**Permission prefix:** `environment.records`

**Workflow states:** recorded → approved → closed (environment.records.approve/close)

## Fields (skema DB aktual)

### Tabel `environmental_records`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `record_number` |  |
| `type` |  |
| `title` |  |
| `description` |  |
| `site_id` | FK organisasi/user |
| `area_id` | FK organisasi/user |
| `occurred_at` |  |
| `measured_value` |  |
| `unit` |  |
| `limit_value` |  |
| `is_exceedance` |  |
| `waste_type` |  |
| `quantity` |  |
| `disposal_method` |  |
| `material` |  |
| `volume` |  |
| `containment` |  |
| `parameter` |  |
| `location` |  |
| `reporter_id` | FK referensi |
| `status` | state workflow |
| `capa_action_id` | FK referensi |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /environment` | |
| `/environment/export` | |
| `environment.records.approve` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

is_exceedance flag + capa_action_id. measured_value/limit_value/unit untuk evaluasi batas.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

# Risk Management (HIRADC/JSA)

**Ringkasan:** Register risiko: hazard, kontrol, level risiko (severity × probability), residual risk.

**Permission prefix:** `risk.registers`

**Workflow states:** identified → assessed → monitored (risk.registers.assess)

## Fields (skema DB aktual)

### Tabel `risk_registers`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `register_number` |  |
| `title` |  |
| `type` |  |
| `site_id` | FK organisasi/user |
| `area_id` | FK organisasi/user |
| `department_id` | FK organisasi/user |
| `activity` |  |
| `hazard` |  |
| `existing_controls` |  |
| `severity_id` | FK referensi |
| `probability_id` | FK referensi |
| `risk_level_id` | FK referensi |
| `additional_controls` |  |
| `residual_severity_id` | FK referensi |
| `residual_probability_id` | FK referensi |
| `residual_risk_level_id` | FK referensi |
| `owner_id` | FK referensi |
| `status` | state workflow |
| `review_date` | tanggal penting (overdue/expiry) |
| `created_at` |  |
| `updated_at` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /risk` | |
| `risk.registers.assess` | |
| `risk.registers.export` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

FK risk_matrix_level untuk severity/probability/risk_level + residual. owner_id mengikat user. Terkait apd_requirements.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

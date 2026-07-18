# Training & Competency

**Ringkasan:** Program training & rekam kompetensi karyawan, termasuk fit-test APD.

**Permission prefix:** `training.programs / training.records`

**Workflow states:** training_program: active/inactive; training_record: scheduled → completed → certified/expired

## Fields (skema DB aktual)

### Tabel `training_programs`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `code` |  |
| `name` |  |
| `description` |  |
| `category` |  |
| `duration_hours` |  |
| `is_certification` |  |
| `validity_months` |  |
| `is_active` |  |
| `created_at` |  |
| `updated_at` |  |

### Tabel `training_records`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `training_number` |  |
| `employee_id` | FK referensi |
| `training_program_id` | FK referensi |
| `provider` |  |
| `start_date` |  |
| `end_date` |  |
| `status` | state workflow |
| `score` |  |
| `result` |  |
| `certificate_number` |  |
| `certificate_file_id` | FK referensi |
| `expiry_date` | tanggal penting (overdue/expiry) |
| `notes` |  |
| `created_at` |  |
| `updated_at` |  |
| `training_type` |  |
| `apd_item_id` | FK referensi |
| `fit_test_result` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /training (programs & records)` | |
| `training.records.export` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

training_records punya apd_item_id + fit_test_result (PPE fit-test). certificate_file_id → managed_files.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

# Quality NCR & Complaints

**Ringkasan:** Non-conformance report & keluhan pelanggan, dengan CAPA & link NCR↔complaint.

**Permission prefix:** `quality.ncrs / quality.complaints`

**Workflow states:** ncr: open → closed (quality.ncrs.close); complaint: open → closed (quality.complaints.close)

## Fields (skema DB aktual)

### Tabel `ncrs`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `ncr_number` |  |
| `title` |  |
| `source` |  |
| `description` |  |
| `site_id` | FK organisasi/user |
| `department_id` | FK organisasi/user |
| `product_service` |  |
| `batch_lot` |  |
| `customer_name` |  |
| `severity_id` | FK referensi |
| `status` | state workflow |
| `root_cause` |  |
| `corrective_action` |  |
| `preventive_action` |  |
| `capa_action_id` | FK referensi |
| `closed_at` |  |
| `created_at` |  |
| `updated_at` |  |

### Tabel `customer_complaints`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `complaint_number` |  |
| `customer_name` |  |
| `customer_contact` |  |
| `title` |  |
| `description` |  |
| `site_id` | FK organisasi/user |
| `product_service` |  |
| `severity_id` | FK referensi |
| `status` | state workflow |
| `resolution` |  |
| `ncr_id` | FK referensi |
| `closed_at` |  |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /quality (ncrs/complaints)` | |
| `quality.ncrs.close` | |
| `quality.complaints.close` | |
| `quality.complaints.export` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

ncr.capital_action_id & complaint.ncr_id menghubungkan alur perbaikan. severity_id terikat master.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

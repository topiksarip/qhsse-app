# Legal & Compliance Register

**Ringkasan:** Register kepatuhan regulasi & obligasi (kewajiban periodik).

**Permission prefix:** `legal.register / legal.obligations`

**Workflow states:** register: active → review; obligation: pending → completed (overdue jika next_due lewat)

## Fields (skema DB aktual)

### Tabel `legal_register`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `register_number` |  |
| `title` |  |
| `regulation_name` |  |
| `regulation_number` |  |
| `issuing_body` |  |
| `category` |  |
| `compliance_status` |  |
| `site_id` | FK organisasi/user |
| `department_id` | FK organisasi/user |
| `owner_id` | FK referensi |
| `next_review_date` | tanggal penting (overdue/expiry) |
| `document_id` | FK referensi |
| `notes` |  |
| `status` | state workflow |
| `created_at` |  |
| `updated_at` |  |

### Tabel `legal_obligations`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `legal_register_id` | FK referensi |
| `obligation_description` |  |
| `frequency` |  |
| `last_completed` |  |
| `next_due` |  |
| `evidence_file_id` | FK referensi |
| `status` | state workflow |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /legal` | |
| `legal.obligations.create` | |
| `legal.register.export` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

legal_register.document_id → controlled_documents. legal_obligations.evidence_file_id → managed_files.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

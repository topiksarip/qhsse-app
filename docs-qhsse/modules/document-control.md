# Document Control

**Ringkasan:** Dokumen terkendali ISO 9001: versi, review, approval, effective, obsolete.

**Permission prefix:** `document.control`

**Workflow states:** draft → in_review → approved → effective → obsolete (document.control.submit_review/approve/make_effective/obsolete)

## Fields (skema DB aktual)

### Tabel `controlled_documents`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `document_number` | file privat (ManagedFileService) |
| `title` |  |
| `type` |  |
| `version` |  |
| `revision_notes` |  |
| `effective_date` | tanggal penting (overdue/expiry) |
| `review_date` | tanggal penting (overdue/expiry) |
| `expiry_date` | tanggal penting (overdue/expiry) |
| `department_id` | FK organisasi/user |
| `owner_id` | FK referensi |
| `approver_id` | FK referensi |
| `status` | state workflow |
| `is_confidential` |  |
| `created_at` |  |
| `updated_at` |  |

### Tabel `document_reviews`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `document_id` | FK referensi |
| `reviewer_id` | FK referensi |
| `review_date` | tanggal penting (overdue/expiry) |
| `review_notes` |  |
| `decision` |  |
| `created_at` |  |
| `updated_at` |  |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /documents` | |
| `/documents/{id}/submit-review` | |
| `/approve` | |
| `/make-effective` | |
| `/obsolete` | |
| `/revise` | |
| `/reject` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

Setiap dokumen punya file (managed_files) + audit kolom created_by/updated_by. document_reviews mencatat reviewer & decision.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

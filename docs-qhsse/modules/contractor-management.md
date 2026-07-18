# Contractor Management

**Ringkasan:** Prakualifikasi, kontrak, approval, & evaluasi kinerja kontraktor.

**Permission prefix:** `contractor.management`

**Workflow states:** contractor: registered → prequalified → approved → active/expired; evaluation tercatat

## Fields (skema DB aktual)

### Tabel `contractors`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `contractor_number` |  |
| `company_name` |  |
| `business_registration_number` |  |
| `tax_id` | FK referensi |
| `contact_person` |  |
| `contact_phone` |  |
| `contact_email` |  |
| `address` |  |
| `business_type` |  |
| `scope_of_work` |  |
| `specialization` |  |
| `contract_start_date` |  |
| `contract_end_date` |  |
| `contract_status` |  |
| `contract_terms` |  |
| `safety_induction_required` |  |
| `safety_induction_date` |  |
| `safety_induction_expiry` |  |
| `insurance_required` |  |
| `insurance_policy_number` |  |
| `insurance_expiry` |  |
| `performance_rating` |  |
| `incident_count` |  |
| `violation_count` |  |
| `performance_notes` |  |
| `authorized_sites` |  |
| `authorized_areas` |  |
| `document_files` | file privat (ManagedFileService) |
| `approval_status` |  |
| `approved_by` |  |
| `approved_at` |  |
| `approval_notes` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `deleted_at` | soft-delete / recovery |
| `is_prequalified` |  |
| `prequalified_until` |  |
| `safety_rating` |  |

### Tabel `contractor_evaluations`

| Kolom | Keterangan |
|-------|-----------|
| `id` |  |
| `contractor_id` | FK referensi |
| `evaluation_date` |  |
| `evaluator_id` | FK referensi |
| `criteria` |  |
| `total_score` |  |
| `result` |  |
| `notes` |  |
| `created_by` | FK organisasi/user |
| `updated_by` | FK organisasi/user |
| `created_at` |  |
| `updated_at` |  |
| `deleted_at` | soft-delete / recovery |

## Endpoint Utama

| Method & Path | Keterangan |
|---------------|-----------|
| `GET/POST /contractors` | |
| `/prequalify` | |
| `/evaluations` | |
| `contractor.management.approve` | |

## UI
- Index: search/filter/pagination + export CSV.
- Form: validasi server; field master (severity/priority/status/site/area/dept) via select.
- Show: tab Detail, Comments, Activity, Workflow History, Files, Audit.

## Catatan Implementasi

SoftDelete (deleted_at). authorized_sites/areas (JSON). document_files (managed_files). performance_rating otomatis dari evaluation.

## Relasi Lintas Modul
- File bukti → `managed_files` (modul terikat `module_name`+`reference_id`).
- Komentar/Activity → `comments`/`activity_logs` (shared).
- Audit trail → `audit_logs` (AuditService).
- Penomoran → NumberingService (`*_number`).

# Module Spec — Quality Management

> Spesifikasi lengkap modul Quality Management (Manajemen Mutu) — Non-Conformance Reports (NCR) dan Customer Complaints (Keluhan Pelanggan).

---

## 1. Tujuan Modul

Modul Quality Management mengelola proses ketidaksesuaian (non-conformance) dan keluhan pelanggan secara end-to-end. Modul ini mencakup pembuatan NCR, analisis akar masalah (Root Cause Analysis / RCA), tindakan korektif dan preventif, penautan ke modul CAPA, serta pencatatan dan penyelesaian keluhan pelanggan. Terintegrasi penuh dengan Core Foundation: user, role, permission, master data, file upload, notification, numbering, workflow, audit trail, comments, dashboard, dan reporting.

### Ruang Lingkup Phase 1

- **NCR (Non-Conformance Report)**: pembuatan, edit, submit, review, close, RCA, corrective/preventive action, link ke CAPA.
- **Customer Complaint**: pembuatan, edit, close, link ke NCR.
- **2 resource groups**: `ncrs` dan `customer_complaints`.
- **Numbering**: NCR-2026-0001 (quality module, sudah di-seed di NumberingSeeder).

### Tidak Termasuk Phase 1

- Calibration register (Phase 2+).
- Supplier evaluation (Phase 2+).
- Quality KPI dashboard terpisah (Phase 2+).

---

## 2. Dependency

| Dependency | Deskripsi |
|---|---|
| Core Foundation | User, role, permission, master data (sites, departments, severities), numbering, workflow, audit, activity, comments, notification, file service, ListQuery, CsvExporter |
| CAPA (Module 04) | NCR dapat ditautkan ke `capa_actions` via `capa_action_id` FK |
| Audit (Module 06) | NCR dengan `source = 'audit'` dapat merujuk ke temuan audit |
| Document Control (Module 07) | Dokumen referensi mutu (SOP, work instruction) dapat dilampirkan |
| Asset / Equipment (Module 17) | NCR dapat terkait equipment/asset (referensi di `product_service` field) |

---

## 3. User Role yang Terlibat

| Role | Keterlibatan |
|---|---|
| Employee / Reporter | Dapat membuat NCR draf, melihat NCR miliknya |
| Supervisor | Review dan approve NCR di level department |
| QHSSE Officer | Manage NCR, RCA, corrective/preventive action, manage complaints |
| QHSSE Manager | Approve/close NCR, manage complaints, export data |
| Department Head | Melihat NCR di department-nya, memberi input RCA |
| Contractor | Melihat NCR terkait pekerjaan kontraktornya (scope: company) |
| Auditor | Melihat semua NCR dan complaints (read-only, scope: all) |
| Admin | Full access ke semua fitur |
| Top Management | Melihat dashboard dan report quality (read-only, scope: all) |

---

## 4. Fitur

### 4.1 NCR (Non-Conformance Reports)

- Pembuatan NCR dengan auto-numbering `NCR-{YYYY}-{0001}`.
- Sumber ketidaksesuaian: `internal`, `external`, `customer_complaint`, `audit`, `supplier`.
- Informasi produk/jasa, batch/lot number, nama pelanggan.
- Klasifikasi severity (link ke master `severities`).
- Root Cause Analysis (RCA) — field `root_cause`.
- Corrective Action — field `corrective_action`.
- Preventive Action — field `preventive_action`.
- Link ke CAPA — field `capa_action_id` (FK ke `capa_actions` table).
- Workflow: open → under_review → in_progress → closed.
- File attachment (evidence, foto, dokumen pendukung).
- Comments / discussion thread.
- Activity log dan audit trail.
- CSV export dengan filter.

### 4.2 Customer Complaints

- Pembuatan complaint dengan auto-numbering (format: `NCR-{YYYY}-{0001}` jika dari NCR, atau nomor complaint terpisah di Phase 2).
- Link ke NCR — field `ncr_id` (FK ke `ncrs` table, nullable).
- Informasi pelanggan: nama, kontak.
- Tanggal complaint, deskripsi, severity.
- Resolution dan resolved_at.
- Workflow: open → in_progress → closed.
- File attachment.
- Comments / discussion.
- Activity log dan audit trail.
- CSV export dengan filter.

---

## 5. Workflow

### 5.1 NCR Workflow

```text
open ──(submit)──→ under_review ──(review)──→ in_progress ──(close)──→ closed
                       │                                               │
                       └──(reject)──→ rejected                         │
                                                                       │
                       rejected ──(reopen)──→ open ────────────────────┘
```

### 5.2 Customer Complaint Workflow

```text
open ──(start_review)──→ in_progress ──(close)──→ closed
```

> Workflow final dapat disesuaikan per implementasi. Lihat [WORKFLOW.md](./WORKFLOW.md) untuk detail lengkap transition table dan controller integration.

---

## 6. Data Field

### 6.1 NCR (`ncrs` table)

| Field | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| `id` | bigint PK | Ya | — | Primary key |
| `ncr_number` | varchar(50) | Ya | — | Unique, auto-generated `NCR-{YYYY}-{0001}` |
| `title` | varchar(255) | Ya | — | Judul singkat ketidaksesuaian |
| `source` | varchar(50) | Ya | — | Enum: `internal`, `external`, `customer_complaint`, `audit`, `supplier` |
| `description` | text | Ya | — | Deskripsi detail ketidaksesuaian |
| `site_id` | bigint FK | Ya | — | FK → `sites.id` |
| `department_id` | bigint FK | Tidak | NULL | FK → `departments.id` |
| `product_service` | varchar(255) | Tidak | NULL | Produk atau jasa terkait |
| `batch_lot` | varchar(100) | Tidak | NULL | Nomor batch/lot |
| `customer_name` | varchar(255) | Tidak | NULL | Nama pelanggan (jika source = customer_complaint/external) |
| `severity_id` | bigint FK | Ya | — | FK → `severities.id` |
| `status` | varchar(50) | Ya | `'open'` | Enum: `open`, `under_review`, `in_progress`, `closed`, `rejected` |
| `root_cause` | text | Tidak | NULL | Hasil analisis akar masalah (RCA) |
| `corrective_action` | text | Tidak | NULL | Tindakan korektif |
| `preventive_action` | text | Tidak | NULL | Tindakan preventif |
| `capa_action_id` | bigint FK | Tidak | NULL | FK → `capa_actions.id` (link ke modul CAPA) |
| `closed_at` | timestamp | Tidak | NULL | Tanggal NCR ditutup |
| `created_at` | timestamp | Ya | CURRENT_TIMESTAMP | Laravel managed |
| `updated_at` | timestamp | Ya | CURRENT_TIMESTAMP | Laravel managed |

### 6.2 Customer Complaint (`customer_complaints` table)

| Field | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| `id` | bigint PK | Ya | — | Primary key |
| `complaint_number` | varchar(50) | Ya | — | Unique, auto-generated |
| `ncr_id` | bigint FK | Tidak | NULL | FK → `ncrs.id` (link ke NCR) |
| `customer_name` | varchar(255) | Ya | — | Nama pelanggan |
| `customer_contact` | varchar(255) | Tidak | NULL | Kontak pelanggan (telepon/email) |
| `complaint_date` | date | Ya | — | Tanggal complaint diterima |
| `description` | text | Ya | — | Deskripsi keluhan |
| `severity_id` | bigint FK | Ya | — | FK → `severities.id` |
| `status` | varchar(50) | Ya | `'open'` | Enum: `open`, `in_progress`, `closed` |
| `resolution` | text | Tidak | NULL | Resolusi keluhan |
| `resolved_at` | timestamp | Tidak | NULL | Tanggal complaint diselesaikan |
| `created_at` | timestamp | Ya | CURRENT_TIMESTAMP | Laravel managed |
| `updated_at` | timestamp | Ya | CURRENT_TIMESTAMP | Laravel managed |

---

## 7. Business Rules

1. **Nomor otomatis**: NCR dan Customer Complaint mendapat nomor unik saat create (bukan saat submit). Format mengikuti `NumberingService::generate('quality', ...)`.
2. **NCR source `customer_complaint`**: Jika NCR dibuat dengan source `customer_complaint`, sistem menyarankan pembuatan record Customer Complaint terkait (namun tidak otomatis — user harus membuatnya manual atau via link).
3. **RCA wajib sebelum close**: NCR tidak dapat di-close jika `root_cause`, `corrective_action`, dan `preventive_action` belum diisi. Minimal `root_cause` wajib diisi.
4. **Link ke CAPA**: Jika `capa_action_id` diisi, NCR status mengikuti status CAPA terkait (informasi-only, tidak auto-sync di Phase 1).
5. **Customer Complaint link ke NCR**: Jika `ncr_id` diisi, show page complaint menampilkan link ke NCR terkait.
6. **Close NCR**: Menutup NCR menset `closed_at = now()`. Semua field RCA wajib sudah terisi.
7. **Close Complaint**: Menutup complaint wajib mengisi `resolution`. Menutup complaint menset `resolved_at = now()`.
8. **Edit terbatas**: NCR hanya bisa diedit jika `status = 'open'` atau `status = 'under_review'`. Complaint hanya bisa diedit jika `status = 'open'` atau `status = 'in_progress'`.
9. **Audit trail**: Semua perubahan field penting (status, root_cause, corrective_action, preventive_action, capa_action_id) masuk audit trail.
10. **Data visibility**: Mengikuti role scope — own (reporter), department (supervisor/department head), site (QHSSE), company (contractor), all (admin/auditor/top management).

---

## 8. Notification Rules

### NCR

| Event | Penerima | Tipe Notifikasi |
|---|---|---|
| NCR dibuat (submit) | QHSSE Officer + QHSSE Manager | `quality.ncr.submitted` |
| NCR di-assign RCA | PIC / QHSSE Officer | `quality.ncr.rca_requested` |
| NCR di-close | Reporter + Department Head | `quality.ncr.closed` |
| NCR ditolak | Reporter | `quality.ncr.rejected` |
| NCR link ke CAPA | CAPA owner | `quality.ncr.linked_to_capa` |

### Customer Complaint

| Event | Penerima | Tipe Notifikasi |
|---|---|---|
| Complaint dibuat | QHSSE Officer + QHSSE Manager | `quality.complaint.created` |
| Complaint di-close | Reporter + Customer (jika ada kontak) | `quality.complaint.closed` |

---

## 9. File Attachment Rules

- Attachment memakai `ManagedFileService` core.
- `module_name`: `'quality'` untuk NCR, `'quality_complaint'` untuk Customer Complaint.
- `reference_id`: ID NCR atau ID Customer Complaint.
- `collection`: `'evidence'`, `'photos'`, `'documents'`, `'resolution'`.
- File sensitif mengikuti permission record.
- Evidence tidak boleh dihapus setelah record closed kecuali admin berwenang.
- Maks 10MB per file. Format: jpg, png, pdf, docx, xlsx.

---

## 10. Permission Keys

### 10.1 NCR Permissions

| Permission Key | Deskripsi |
|---|---|
| `quality.ncrs.view` | Lihat daftar dan detail NCR |
| `quality.ncrs.create` | Buat NCR baru |
| `quality.ncrs.update` | Edit NCR (jika status open/under_review) |
| `quality.ncrs.close` | Tutup NCR (set RCA + corrective/preventive action) |
| `quality.ncrs.export` | Export daftar NCR ke CSV |

### 10.2 Customer Complaint Permissions

| Permission Key | Deskripsi |
|---|---|
| `quality.complaints.view` | Lihat daftar dan detail complaint |
| `quality.complaints.create` | Buat complaint baru |
| `quality.complaints.update` | Edit complaint (jika status open/in_progress) |
| `quality.complaints.close` | Tutup complaint (set resolution) |
| `quality.complaints.export` | Export daftar complaint ke CSV |

---

## 11. Role-Permission Matrix

| Role | NCR | Complaint | Scope |
|---|---|---|---|
| Super Admin | All | All | All |
| Admin | All | All | All |
| QHSSE Manager | view, create, update, close, export | view, create, update, close, export | All sites |
| QHSSE Officer | view, create, update, close, export | view, create, update, close, export | Assigned sites |
| Supervisor | view, create, update | view, create, update | Department |
| Department Head | view | view | Department |
| Employee / Reporter | view, create | view, create | Own |
| Contractor | view | view | Company |
| Auditor | view, export | view, export | All |
| Top Management | view, export | view, export | All |

---

## 12. UI Pages

### NCR
- **Index**: `/ncrs` — daftar NCR dengan search, filter (status, source, severity, site, date range), pagination, export CSV.
- **Form (Create/Edit)**: `/ncrs/create`, `/ncrs/{id}/edit` — form multi-section: Informasi Umum, Sumber & Klasifikasi, Detail Produk/Jasa, Deskripsi, Evidence.
- **Show**: `/ncrs/{id}` — detail NCR dengan tab: Detail, Root Cause Analysis (RCA + Corrective + Preventive Action), CAPA Link, Attachments, Comments, Activity Timeline.

### Customer Complaint
- **Index**: `/customer-complaints` — daftar complaint dengan search, filter (status, severity, date range), pagination, export CSV.
- **Form (Create/Edit)**: `/customer-complaints/create`, `/customer-complaints/{id}/edit` — form: Informasi Pelanggan, Detail Complaint, Link ke NCR (opsional).
- **Show**: `/customer-complaints/{id}` — detail complaint dengan tab: Detail, Resolution, NCR Link, Attachments, Comments, Activity Timeline.

> Lihat [UI_PAGES.md](./UI_PAGES.md) untuk wireframe ASCII lengkap.

---

## 13. API Requirement

### NCR
- `GET /ncrs` — list dengan filter/pagination
- `GET /ncrs/create` — render form create
- `POST /ncrs` — store
- `GET /ncrs/{ncr}` — show detail
- `GET /ncrs/{ncr}/edit` — render form edit
- `PUT /ncrs/{ncr}` — update
- `POST /ncrs/{ncr}/submit` — submit (open → under_review)
- `POST /ncrs/{ncr}/review` — review (under_review → in_progress)
- `POST /ncrs/{ncr}/close` — close (in_progress → closed, wajib RCA)
- `GET /ncrs/export` — export CSV

### Customer Complaint
- `GET /customer-complaints` — list dengan filter/pagination
- `GET /customer-complaints/create` — render form create
- `POST /customer-complaints` — store
- `GET /customer-complaints/{complaint}` — show detail
- `GET /customer-complaints/{complaint}/edit` — render form edit
- `PUT /customer-complaints/{complaint}` — update
- `POST /customer-complaints/{complaint}/close` — close (wajib resolution)
- `GET /customer-complaints/export` — export CSV

> Lihat [API_CONTRACT.md](./API_CONTRACT.md) untuk route table, payload, validation, dan response lengkap.

---

## 14. Dashboard Metrics

### NCR Dashboard
- Total NCR (all status).
- NCR Open / Under Review / In Progress / Closed count.
- NCR by severity (Critical, High, Medium, Low).
- NCR by source (Internal, External, Customer Complaint, Audit, Supplier).
- NCR trend by month (current year).
- NCR by site / department.
- Average time to close (days from created to closed_at).

### Customer Complaint Dashboard
- Total complaints.
- Open / In Progress / Closed count.
- Complaints by severity.
- Complaints linked to NCR vs unlinked.
- Average resolution time (days from complaint_date to resolved_at).

---

## 15. Report / Export

### NCR Export (CSV)
Kolom: Nomor NCR, Judul, Sumber, Severity, Status, Site, Departemen, Produk/Jasa, Batch/Lot, Customer, Tanggal Dibuat, Tanggal Ditutup.

### Customer Complaint Export (CSV)
Kolom: Nomor Complaint, NCR Terkait, Nama Pelanggan, Kontak, Tanggal Complaint, Severity, Status, Tanggal Selesai, Resolution.

Filter export mengikuti filter list yang aktif.

---

## 16. Acceptance Criteria

1. User dengan permission `quality.ncrs.create` dapat membuat NCR.
2. User tanpa permission ditolak (403).
3. Nomor NCR auto-generated dengan format `NCR-{YYYY}-{0001}`.
4. NCR tidak bisa di-close tanpa `root_cause` minimal terisi.
5. NCR dapat di-link ke CAPA via `capa_action_id`.
6. Customer Complaint dapat di-link ke NCR via `ncr_id`.
7. Complaint tidak bisa di-close tanpa `resolution` terisi.
8. Workflow status berjalan sesuai rule (open → under_review → in_progress → closed).
9. Attachment bisa upload/download sesuai permission.
10. Comment dan activity log tampil di detail page.
11. Audit trail tercatat untuk create, update, dan status change.
12. Notification terkirim ke penerima tepat.
13. List dapat search/filter/pagination.
14. Export CSV menghasilkan data sesuai filter dan permission.
15. Data visibility mengikuti role scope (own, department, site, company, all).

---

## 17. Open Questions

1. Apakah nomor Customer Complaint memakai prefix terpisah (misal `CC-2026-0001`) atau tetap memakai numbering module `quality`? → **Phase 1: memakai numbering `quality` dengan format `NCR-{YYYY}-{0001}` jika dari NCR. Complaint standalone memakai prefix `CC` — perlu tambahan di NumberingSeeder jika diinginkan.**
2. SLA default untuk penyelesaian NCR (berapa hari dari open ke close)?
3. Apakah NCR dengan source `audit` harus otomatis link ke `audit_findings.id`? → **Phase 1: tidak, hanya text reference di description.**
4. Apakah perlu multi-level approval untuk NCR severity Critical?
5. Template report PDF untuk NCR dan Complaint detail — perlu desain final.
6. Apakah complaint dari eksternal (pelanggan) bisa di-submit via portal tanpa login? → **Phase 2+.**
7. Integrasi dengan calibration register — bagaimana NCR terkait equipment calibration failure?

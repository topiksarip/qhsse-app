# Module Spec — Document Control

> **Module ID:** `07-document-control`  
> **Module Code (numbering):** `document`  
> **Number Prefix:** `DOC`  
> **Workflow Code:** `DOCUMENT_WORKFLOW`  
> **Phase:** Phase 7 — Document Control  
> **Status:** Ready for coding  

---

## 1. Tujuan Modul

Modul Document Control menyediakan sistem pengelolaan dokumen terkontrol (Controlled Documents) secara end-to-end untuk organisasi QHSSE. Modul ini mengakomodasi sembilan tipe dokumen: SOP, WI, JSA, HIRADC, MSDS, Policy, Form, Manual, dan Other.

Tujuan utama:

- Memungkinkan pembuatan dan pengelolaan **controlled documents** dengan **nomor unik** (DOC-YYYY-NNNN) yang di-generate otomatis pada saat create.
- Menyediakan **workflow status** yang jelas: Draft → Review → Approved → Effective → Obsolete (dengan jalur Reject dan Revise).
- Mengelola **file dokumen** (PDF, DOCX, XLSX) melalui `ManagedFileService` dengan collection `document_file`.
- Menyediakan **version tracking** — setiap revisi membuat record `document_reviews` baru, sehingga history revisi tercatat.
- Mengirim **notifikasi expiry reminder** ketika `review_date` mendekat, serta notifikasi saat submit review, approve, reject, dan obsolete.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal (create, update, submit_review, approve, make_effective, obsolete, reject, revise, file upload/download).
- Menghubungkan ke modul **Notification** untuk expiry reminder dan ke modul **Audit** untuk trail.
- Mendukung **confidential documents** (`is_confidential`) yang memerlukan permission khusus untuk download.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 8 permission keys `document.control.*` |
| **NumberingService** | Generate `DOC-YYYY-NNNN` on create |
| **WorkflowService** | Status transitions per `DOCUMENT_WORKFLOW` definition |
| **ManagedFileService** | Upload/download document files via `managed_files` table |
| **NotificationService** | In-app + email notifications for review/approve/reject/obsolete + expiry reminder |
| **AuditService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='document'`) |
| **ActivityService** | Activity timeline via `activity_logs` table |
| **ListQuery** | Paginated list with search/filter/sort |
| **CsvExporter** | CSV export via `document.control.export` permission |
| **MasterData** | Departments, Users (for owner/approver selection) |

### Cross-Module

| Module | Relationship |
|---|---|
| `00-core-foundation` | Uses all core services (Workflow, Numbering, File, Notification, Audit, Activity, Comment) |
| `08-training-management` | Training records may reference controlled documents (SOP, WI) as training materials |
| `13-risk-management` | HIRADC documents produced by risk module are managed here |
| `15-emergency-preparedness` | Emergency response documents (Manual) managed here |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini:

| # | Role | Deskripsi Peran dalam Document Control |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Approve, reject, make_effective, obsolete. Scope: all sites. |
| 4 | **QHSSE Officer** | Create, update, submit_review. Scope: assigned site(s). |
| 5 | **Supervisor** | Create, update, submit_review untuk department-nya. Scope: department. |
| 6 | **Department Head** | View + submit_review dokumen di department-nya. Scope: department. |
| 7 | **Employee/Reporter** | View dokumen yang published (effective). Tidak create/edit. |
| 8 | **Contractor** | View dokumen yang published. Scope: company. |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & documents, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Document CRUD

- **Create** — Form pembuatan controlled document. Nomor `DOC-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal: `draft`. User upload file dokumen + set metadata (type, title, effective_date, review_date, owner, department, confidential flag).
- **List** — Halaman list dengan search (nomor, judul), filter (type, status, department, owner), pagination (default 15 per page), dan tombol Export CSV.
- **Detail** — Halaman detail menampilkan: nomor, judul, type, version, revision_notes, effective_date, review_date, expiry_date, department, owner, approver, status, workflow timeline, version history (all reviews), approval flow, file download (authorized), comments, activity log, audit trail.
- **Update** — Edit record. Hanya bisa edit jika status `draft` atau `rejected`. Setelah submit_review, record tidak bisa di-edit kecuali di-reject (kembali ke draft via revise).
- **Delete** — Soft delete. Hanya Super Admin / Admin. Tidak bisa delete record yang sudah `effective` atau `obsolete`.

### 4.2 Workflow Actions

- **Save Draft** — Simpan tanpa validasi mandatory fields. Status tetap `draft`.
- **Submit Review** — Validasi mandatory fields. Status: `draft` → `review`. Trigger notifikasi ke QHSSE Manager. Membuat record `document_reviews` baru dengan `decision` menunggu.
- **Approve** — QHSSE Manager menyetujui dokumen. Status: `review` → `approved`. Update `document_reviews.decision = 'approve'`. Trigger notifikasi ke owner.
- **Make Effective** — QHSSE Manager menerapkan dokumen. Status: `approved` → `effective`. Set `effective_date`. Trigger notifikasi ke stakeholders.
- **Obsolete** — QHSSE Manager meng-obsolete dokumen. Wajib isi reason. Status: `effective` → `obsolete`. Tidak bisa di-edit lagi.
- **Reject** — QHSSE Manager menolak dokumen. Wajib isi reason. Status: `review` → `rejected`. Update `document_reviews.decision = 'reject'`. Trigger notifikasi ke owner.
- **Revise** — Owner merevisi dokumen yang ditolak. Status: `rejected` → `draft`. Update `document_reviews.decision = 'revise'`. Owner dapat edit dan re-submit.

### 4.3 Version Tracking

- Setiap dokumen memiliki field `version` (string, contoh: `1.0`, `1.1`, `2.0`).
- Setiap kali dokumen di-revise dan di-submit kembali, version dinaikkan dan record `document_reviews` baru dibuat.
- History semua reviews tersimpan di tabel `document_reviews` — menampilkan reviewer, review_date, review_notes, dan decision.
- Halaman Show menampilkan version history lengkap dari semua reviews.

### 4.4 File Management

- File dokumen disimpan via `ManagedFileService`.
- `module_name = 'document'`, `reference_id = document.id`, `collection = 'document_file'`.
- Upload melalui form create/edit.
- Download melalui authorized endpoint (permission check + confidential check).
- Multiple files dapat diupload, tetapi hanya satu file utama per version.
- File tidak bisa dihapus setelah status `effective` atau `obsolete`.

### 4.5 Expiry Reminder

- Field `review_date` dan `expiry_date` nullable pada controlled_documents.
- Scheduled job (cron) mengecek dokumen dengan `review_date` mendekat (30 hari, 7 hari, 1 hari sebelum).
- Notifikasi dikirim ke document owner dan QHSSE Manager.
- Notification type: `document.expiry_reminder`.

### 4.6 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record.
- Activity log otomatis mencatat: create, update, submit_review, approve, make_effective, obsolete, reject, revise, file upload, file download.
- Timeline ditampilkan di halaman detail.

### 4.7 Notification Events

- 6 event notifikasi: `document.submitted`, `document.approved`, `document.effective`, `document.rejected`, `document.obsolete`, `document.expiry_reminder`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.8 Dashboard & Reporting

- Dashboard widget: total documents, breakdown by type/status/department, documents approaching review/expiry date.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Tipe Dokumen

Sembilan tipe dokumen yang dikontrol:

| # | Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `SOP` | **Standard Operating Procedure** | Prosedur operasi standar yang harus diikuti untuk menjalankan suatu aktivitas. |
| 2 | `WI` | **Work Instruction** | Instruksi kerja langkah demi langkah untuk tugas spesifik. |
| 3 | `JSA` | **Job Safety Analysis** | Analisis keselamatan kerja untuk mengidentifikasi hazard per langkah kerja. |
| 4 | `HIRADC` | **Hazard Identification, Risk Assessment & Determining Control** | Dokumen identifikasi hazard, penilaian risiko, dan penentuan kontrol. |
| 5 | `MSDS` | **Material Safety Data Sheet** | Lembar data keselamatan bahan untuk bahan kimia. |
| 6 | `Policy` | **Policy** | Kebijakan formal organisasi terkait QHSSE. |
| 7 | `Form` | **Form** | Formulir yang digunakan dalam sistem manajemen QHSSE. |
| 8 | `Manual` | **Manual** | Manual sistem manajemen atau manual operasional. |
| 9 | `Other` | **Other** | Dokumen terkontrol lain yang tidak masuk kategori di atas. |

### Status Badge Colors

| Status | Color |
|---|---|
| `draft` | gray |
| `review` | blue |
| `approved` | yellow |
| `effective` | green |
| `obsolete` | red |
| `rejected` | red |

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor document di-generate **saat record dibuat** (POST create).
- Format: `DOC-YYYY-NNNN` (contoh: `DOC-2026-0001`).
- Sumber: `NumberingService::generate('document')`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `document`
  - `prefix`: `DOC`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `DOC-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Draft Save Without Mandatory Fields

- Saat status `draft`, record dapat disimpan tanpa mengisi mandatory fields.
- Field wajib hanya divalidasi saat **submit_review**.
- Owner dapat menyimpan draft berkali-kali sebelum submit.

### BR-03: Submit Review Validates Mandatory Fields

Saat user melakukan **submit_review** (transition `draft` → `review`), sistem memvalidasi field mandatory berikut:

| Field | Validation Rule |
|---|---|
| `title` | required, string, max:255 |
| `type` | required, in:sop,wi,jsa,hiradc,msds,policy,form,manual,other |
| `version` | required, string, max:20 |
| `effective_date` | required, date |
| `owner_id` | required, exists in users |
| `document_file` | required (file must be uploaded before submit_review) |

Jika validasi gagal, submit_review ditolak dan record tetap berstatus `draft`.

### BR-04: Reject Requires Reason

- Transition `review` → `rejected` memerlukan field `reason` (wajib, text, min:10 karakter).
- Reason disimpan di `workflow_histories.reason` dan `document_reviews.review_notes`.
- Notifikasi dikirim ke owner.
- Record `document_reviews.decision` diupdate menjadi `'reject'`.

### BR-05: Obsolete Requires Reason

- Transition `effective` → `obsolete` memerlukan field `reason` (wajib, text, min:10 karakter).
- Reason disimpan di `workflow_histories.reason`.
- Notifikasi dikirim ke owner dan stakeholders.
- Setelah obsolete, record menjadi read-only. Tidak bisa edit, tidak bisa hapus file.

### BR-06: Version Tracking via document_reviews

- Setiap kali dokumen di-submit untuk review, record `document_reviews` baru dibuat.
- Record berisi: `document_id`, `reviewer_id` (null saat awal, diisi saat reviewer act), `review_date`, `review_notes`, `decision`.
- `decision` diupdate saat reviewer melakukan action: `'approve'`, `'reject'`, atau `'revise'`.
- History semua reviews tersimpan permanen — tidak bisa dihapus.

### BR-07: Expiry Reminder Notification

- Scheduled job berjalan harian (cron `0 8 * * *`).
- Mengecek dokumen dengan `status = 'effective'` dan `review_date` dalam 30/7/1 hari ke depan.
- Juga cek `expiry_date` dalam 30/7/1 hari ke depan.
- Kirim notifikasi `document.expiry_reminder` ke owner dan QHSSE Manager.
- Tidak kirim notifikasi jika dokumen sudah `obsolete`.

### BR-08: Confidential Document Access

- Jika `is_confidential = true`, download file memerlukan permission `document.control.view` DAN user adalah owner, approver, QHSSE Manager, atau Super Admin.
- Non-confidential documents: semua user dengan `document.control.view` dapat download.
- List page tetap menampilkan confidential documents (judul terlihat), tetapi file tidak bisa di-download oleh unauthorized user.

### BR-09: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='document'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `document.created` | ControlledDocument record | new_values: all fields |
| `document.updated` | ControlledDocument record | changed fields only |
| `document.submitted` | ControlledDocument record | status change |
| `document.approved` | ControlledDocument record | status change |
| `document.effective` | ControlledDocument record | status change + effective_date |
| `document.obsolete` | ControlledDocument record | status change + reason |
| `document.rejected` | ControlledDocument record | status change + reason |
| `document.revised` | ControlledDocument record | status change |
| `document.deleted` | ControlledDocument record | soft delete |
| `document.file.uploaded` | ManagedFile | new_values |
| `document.file.downloaded` | ManagedFile | metadata: user, ip |

### BR-10: Data Visibility by Scope

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter, Contractor | Only documents they own or that are `effective` |
| `department` | Supervisor, Department Head | Documents in their department + all `effective` |
| `site` | QHSSE Officer | Documents in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All documents |

- Scope check dilakukan **server-side** di Controller/Policy.
- `effective` documents visible to all users with `document.control.view`.

---

## 7. Permission Keys

8 permission keys untuk modul Document Control:

| # | Permission Key | Description |
|---|---|---|
| 1 | `document.control.view` | View document list and detail. Scope-filtered. |
| 2 | `document.control.create` | Create new controlled document. Generates DOC number. |
| 3 | `document.control.update` | Update document record. Only draft/rejected status. Own or scope-based. |
| 4 | `document.control.submit_review` | Submit document for review (draft → review). Validates mandatory fields. |
| 5 | `document.control.approve` | Approve/reject document (review → approved/rejected). QHSSE roles. |
| 6 | `document.control.make_effective` | Make document effective (approved → effective). QHSSE Manager. |
| 7 | `document.control.obsolete` | Obsolete document (effective → obsolete). Requires reason. QHSSE Manager. |
| 8 | `document.control.export` | Export document list to CSV. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}` → `document.control.*`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.
- Workflow transition menggunakan permission module-specific: `document.control.submit_review`, `document.control.approve`, `document.control.make_effective`, `document.control.obsolete`.

---

## 8. Role-Permission Matrix

| Role | `view` | `create` | `update` | `submit_review` | `approve` | `make_effective` | `obsolete` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Supervisor | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

### Notes

- **QHSSE Manager** adalah satu-satunya role yang dapat `approve`, `make_effective`, dan `obsolete` (selain Super Admin/Admin).
- **QHSSE Officer** dan **Supervisor** dapat `create`, `update`, dan `submit_review` tetapi tidak dapat approve/effective/obsolete.
- **Department Head** dapat `submit_review` (review dari sisi department) tetapi tidak create/update.
- **Employee/Reporter** dan **Contractor** hanya view (read-only untuk effective documents).
- **Auditor** dan **Top Management** hanya view + export (read-only).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

6 event notifikasi untuk modul Document Control:

### 9.1 `document.submitted`

| Property | Value |
|---|---|
| **Trigger** | User melakukan submit_review (transition `draft` → `review`) |
| **Recipients** | All users with role `QHSSE Manager` |
| **Type** | `document.submitted` |
| **Title (template)** | `Dokumen Baru Untuk Review: {document.number}` |
| **Message (template)** | `{owner.name} telah mengirimkan dokumen {document.number} - {document.title} (type: {document.type}) untuk review. Mohon lakukan review.` |
| **Action URL** | `/documents/{document.id}` |
| **Module/Reference** | `module_name='document'`, `reference_id={document.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `document.approved`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Manager melakukan approve (transition `review` → `approved`) |
| **Recipients** | The document owner (`document.owner_id`) |
| **Type** | `document.approved` |
| **Title (template)** | `Dokumen Disetujui: {document.number}` |
| **Message (template)** | `Dokumen {document.number} - {document.title} telah disetujui oleh {approver.name}. Dokumen siap untuk di-effective-kan.` |
| **Action URL** | `/documents/{document.id}` |
| **Module/Reference** | `module_name='document'`, `reference_id={document.id}` |
| **Channel** | In-app + Email |

### 9.3 `document.effective`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Manager melakukan make_effective (transition `approved` → `effective`) |
| **Recipients** | Document owner, all users in the document's department |
| **Type** | `document.effective` |
| **Title (template)** | `Dokumen Berlaku Efektif: {document.number}` |
| **Message (template)** | `Dokumen {document.number} - {document.title} telah berlaku efektif sejak {effective_date}. Mohon untuk mematuhi dan mengimplementasikan dokumen ini.` |
| **Action URL** | `/documents/{document.id}` |
| **Module/Reference** | `module_name='document'`, `reference_id={document.id}` |
| **Channel** | In-app + Email |

### 9.4 `document.rejected`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Manager melakukan reject (transition `review` → `rejected`) |
| **Recipients** | The document owner (`document.owner_id`) |
| **Type** | `document.rejected` |
| **Title (template)** | `Dokumen Ditolak: {document.number}` |
| **Message (template)** | `Dokumen {document.number} - {document.title} ditolak oleh {rejecter.name}. Alasan: {reject_reason}. Silakan perbaiki dan kirim ulang.` |
| **Action URL** | `/documents/{document.id}` |
| **Module/Reference** | `module_name='document'`, `reference_id={document.id}` |
| **Channel** | In-app + Email |

### 9.5 `document.obsolete`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Manager melakukan obsolete (transition `effective` → `obsolete`) |
| **Recipients** | Document owner, all users in the document's department, QHSSE Officers |
| **Type** | `document.obsolete` |
| **Title (template)** | `Dokumen Usang (Obsolete): {document.number}` |
| **Message (template)** | `Dokumen {document.number} - {document.title} telah dinyatakan obsolete oleh {obsoletter.name}. Alasan: {obsolete_reason}. Dokumen ini tidak lagi berlaku.` |
| **Action URL** | `/documents/{document.id}` |
| **Module/Reference** | `module_name='document'`, `reference_id={document.id}` |
| **Channel** | In-app + Email |

### 9.6 `document.expiry_reminder`

| Property | Value |
|---|---|
| **Trigger** | Scheduled job (cron daily at 08:00) detects document with `review_date` or `expiry_date` approaching (30/7/1 days) |
| **Recipients** | Document owner, QHSSE Manager |
| **Type** | `document.expiry_reminder` |
| **Title (template)** | `Pengingat Review Dokumen: {document.number}` |
| **Message (template)** | `Dokumen {document.number} - {document.title} akan jatuh tempo review pada {review_date}. Mohon untuk melakukan review dan pembaruan dokumen.` |
| **Action URL** | `/documents/{document.id}` |
| **Module/Reference** | `module_name='document'`, `reference_id={document.id}` |
| **Channel** | In-app + Email |

### Implementation Notes

- Notification dikirim setelah DB transaction commit (use Laravel Event/Listener or Observer pattern).
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Recipient resolution: query users with target role + matching scope.

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core `ManagedFileService` (`App\Core\File\ManagedFileService`) |
| **Table** | `managed_files` |
| **module_name** | `document` |
| **reference_id** | `controlled_documents.id` |
| **collection** | `document_file` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `document/{document_id}/document_file/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx` |
| **Allowed MIME types** | Corresponding to extensions above |
| **Max file size** | 50 MB per file |
| **Max files per document** | 10 |
| **Filename** | Original filename stored in `original_name`; generated UUID-based name in `stored_name` |

### 10.3 Access Rules

- **Upload**: User must have `document.control.create` or `document.control.update`.
- **Download**: User must have `document.control.view` AND be within data scope of the document.
  - If `is_confidential = true`: only owner, approver, QHSSE Manager, Super Admin can download.
  - If `is_confidential = false`: all users with `document.control.view` and scope access can download.
- **Delete**: User must have `document.control.update` AND document status must NOT be `effective` or `obsolete`. Once effective/obsolete, files **cannot be deleted** except by Super Admin / Admin.
- Download endpoint streams file from private storage; no direct public URL.
- File access logged in audit trail (`document.file.downloaded`).

### 10.4 File Metadata

Each file record in `managed_files` includes:

- `module_name`: `document`
- `reference_id`: document ID
- `collection`: `document_file`
- `disk`: `local`
- `path`: storage path
- `original_name`: user's original filename
- `stored_name`: generated filename
- `mime_type`: detected MIME
- `extension`: file extension
- `size`: file size in bytes
- `checksum`: SHA-256 hash (optional)
- `metadata`: JSON (e.g., `{"version": "1.0", "uploaded_by_role": "QHSSE Officer"}`)
- `uploaded_by`: user ID
- `deleted_at`: soft delete timestamp
- `deleted_by`: user ID who deleted

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Documents** | Count all controlled documents in scope | Number + icon |
| **Effective Documents** | Count where status = `effective` | Number, green |
| **Pending Review** | Count where status = `review` | Number, yellow |
| **Expiring Soon** | Count where review_date within 30 days AND status = `effective` | Number, orange badge |
| **Obsolete Documents** | Count where status = `obsolete` | Number, gray |
| **This Month** | Count created in current month | Number + trend arrow |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **By Type** | Donut | Count by type (SOP, WI, JSA, HIRADC, MSDS, Policy, Form, Manual, Other) |
| **By Status** | Donut | Count by workflow status |
| **By Department** | Horizontal bar | Count by department (top 10) |
| **Monthly Trend** | Line chart | Document count by month (last 12 months) |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Recent Documents** | Number, Title, Type, Status, Owner, Created At | Last 10, scoped |
| **Pending Review** | Number, Title, Type, Owner, Submitted At, Days Pending | Status = review |
| **Expiring Soon** | Number, Title, Type, Review Date, Expiry Date, Owner | Review/expiry date within 30 days, status = effective |

---

## 12. Export Spec

### 12.1 CSV Export

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `documents_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `document.control.export` |
| **Scope** | Follows user's data scope |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `document_number` | DOC-YYYY-NNNN |
| 2 | `Judul` | `title` | |
| 3 | `Tipe` | `type` | SOP, WI, JSA, etc. |
| 4 | `Versi` | `version` | |
| 5 | `Status` | `status` | draft, review, approved, effective, obsolete, rejected |
| 6 | `Tanggal Berlaku` | `effective_date` | YYYY-MM-DD |
| 7 | `Tanggal Review` | `review_date` | Nullable |
| 8 | `Tanggal Kadaluarsa` | `expiry_date` | Nullable |
| 9 | `Department` | `department.name` | Via `department_id` |
| 10 | `Owner` | `owner.name` | Via `owner_id` → users |
| 11 | `Approver` | `approver.name` | Via `approver_id` → users, nullable |
| 12 | `Rahasia` | `is_confidential` | Yes/No |
| 13 | `Dibuat Pada` | `created_at` | YYYY-MM-DD HH:MM:SS |

### 12.3 Export Rules

- Export event dicatat di audit trail (`document.exported`).
- Export mengikuti permission dan scope user.
- Maksimal 10.000 record per export.

---

## 13. Acceptance Criteria

1. **AC-01: Create with auto-numbering** — User dengan permission `document.control.create` dapat membuat controlled document. Nomor `DOC-YYYY-NNNN` di-generate otomatis. Nomor bersifat unique.

2. **AC-02: Permission enforcement** — User tanpa permission `document.control.view` tidak dapat mengakses halaman list atau detail. Server-side check memblokir akses.

3. **AC-03: Draft save without mandatory fields** — User dapat menyimpan draft document tanpa mengisi field mandatory. Draft tetap tersimpan dan dapat diakses kembali oleh owner.

4. **AC-04: Submit review validates mandatory fields** — Saat submit_review, sistem memvalidasi mandatory fields. Jika ada field kosong/tidak valid, submit gagal dengan pesan error per field.

5. **AC-05: Workflow transitions correct** — Workflow transition berjalan sesuai definisi `DOCUMENT_WORKFLOW`: draft→review(submit_review), review→approved(approve), approved→effective(make_effective), effective→obsolete(obsolete, requires_reason), review→rejected(reject, requires_reason), rejected→draft(revise).

6. **AC-06: Reject and obsolete require reason** — Transition reject dan obsolete wajib mengisi reason (min 10 karakter). Jika reason kosong, transition gagal.

7. **AC-07: File upload and access control** — User dengan permission dapat upload file dokumen. Download hanya melalui authorized endpoint. Confidential documents hanya bisa di-download oleh authorized users.

8. **AC-08: Notifications sent correctly** — Notifikasi terkirim untuk 6 event: submitted, approved, effective, rejected, obsolete, expiry_reminder.

9. **AC-09: Version tracking via document_reviews** — Setiap revisi membuat record `document_reviews` baru. History semua reviews tersimpan permanen dan ditampilkan di halaman Show.

10. **AC-10: Expiry reminder** — Scheduled job mengirim notifikasi `document.expiry_reminder` ketika `review_date` atau `expiry_date` mendekat (30/7/1 hari sebelum).

11. **AC-11: List with search/filter/pagination/export** — Halaman list mendukung search, filter (type, status, department), pagination, dan export CSV.

12. **AC-12: Audit trail complete** — Audit trail tercatat untuk: create, update, submit_review, approve, make_effective, obsolete, reject, revise, file upload, file download, export.

---

## 14. Open Questions

| # | Question | Default Answer |
|---|---|---|
| 1 | Apakah dokumen yang obsolete bisa di-revive? | **No** — obsolete adalah terminal status. Jika perlu dokumen baru, create record baru dengan version yang ditingkatkan. |
| 2 | Apakah ada auto-increment version number? | **No** — version diisi manual oleh owner. Format bebas string (contoh: `1.0`, `1.1`, `2.0`). |
| 3 | Apakah ada template dokumen per type? | **No untuk Phase 7** — tidak ada template generator. Dapat ditambahkan di Phase 8. |
| 4 | Apakah dokumen bisa di-link ke training records? | **Yes** — modul Training dapat reference `document_number` untuk training materials. |
| 5 | Apakah ada approval multi-level? | **No untuk Phase 7** — single approver (QHSSE Manager). Multi-level dapat ditambahkan di Phase 8. |
| 6 | Apakah notifikasi email aktif di Phase 7? | **Optional** — In-app notification wajib. Email jika SMTP configured. |
| 7 | Apakah ada OCR atau full-text search pada file dokumen? | **No untuk Phase 7** — metadata-based search only. |
| 8 | Apakah ada watermark pada file yang didownload? | **No untuk Phase 7** — dapat ditambahkan di Phase 8. |
| 9 | Apakah ada distribusi dokumen otomatis ke departments? | **No untuk Phase 7** — notifikasi dikirim, tetapi distribusi manual. |
| 10 | Apakah document types bisa di-tambah/di-edit oleh admin? | **No** — 9 types hardcoded di CHECK constraint. Jika perlu tambah type, perlu migration. |

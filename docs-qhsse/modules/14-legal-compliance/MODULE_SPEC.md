# Module Spec — Legal & Compliance Register

> **Module ID:** `14-legal-compliance`  
> **Module Code (numbering):** `legal`  
> **Number Prefix:** `LEG`  
> **Phase:** Phase 14 — Legal & Compliance Register  
> **Status:** Ready for coding  
> **Depends on:** Core Foundation (Phase 0), Document Control (Phase 7), Audit Management (Phase 6), CAPA (Phase 4), Notification (Phase 0)

---

## 1. Tujuan Modul

Modul Legal & Compliance Register menyediakan sistem pengelolaan register peraturan dan regulasi eksternal maupun internal yang relevan dengan operasional organisasi. Modul ini mencakup pencatatan peraturan, penilaian status kepatuhan, serta pelacakan kewajiban (obligations) yang harus dipenuhi secara berkala.

Tujuan utama:

- Memungkinkan **QHSSE team** mencatat peraturan dan regulasi dengan nomor unik `LEG-YYYY-NNNN` yang di-generate otomatis pada saat create.
- Mengelola **compliance status** dengan 4 status: `compliant` (hijau), `non_compliant` (merah), `in_progress` (kuning), `not_applicable` (abu-abu).
- Melacak **kewajiban (obligations)** yang terkait dengan setiap peraturan, dengan frekuensi berulang (monthly/quarterly/annual), tanggal jatuh tempo, dan deteksi overdue.
- Menghubungkan register ke **dokumen terkendali** (modul `07-document-control`) melalui field `document_id`.
- Memungkinkan upload **evidence file** untuk setiap kewajiban yang telah dilaksanakan.
- Mengirim **notifikasi** saat peraturan baru dibuat, compliance status berubah, dan obligation mendekati jatuh tempo atau overdue.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal (create, update, obligation CRUD, status change).
- Menyediakan **dashboard metrics** dan **export CSV** untuk analisis dan pelaporan manajemen.
- Mendukung **kategori regulasi**: national, regional, industry, internal.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 7 permission keys: `legal.register.*` (4) + `legal.obligations.*` (3) |
| **NumberingService** | Generate `LEG-YYYY-NNNN` on create |
| **FileService** | Upload/download evidence files via `managed_files` table |
| **NotificationService** | In-app + email notifications via `core_notifications` table |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='legal'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **CsvExporter** | CSV export via `legal.register.export` permission |
| **ListQuery** | Paginated list with search, filter, sort |
| **MasterData** | Sites, Departments, Users (for owner selection) |

### Cross-Module (existing / future phases)

| Module | Relationship |
|---|---|
| `07-document-control` | Register dapat terhubung ke dokumen terkendali melalui `document_id` FK. Dokumen peraturan/referensi tersimpan di modul Document Control. |
| `06-audit-management` | Audit findings dapat mereferensikan legal register (future: link finding ke register untuk verifikasi kepatuhan). |
| `04-capa-action-tracking` | Jika compliance status `non_compliant`, dapat dibuat CAPA untuk corrective action. Future: tombol "Create CAPA" pada register. |
| `01-notification` | Notifikasi overdue obligation dikirim melalui NotificationService. |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini (sesuai `RolesAndPermissionsSeeder`):

| # | Role | Deskripsi Peran dalam Legal & Compliance |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Create, update, export register dan obligations. Scope: all sites. Approve compliance status. |
| 4 | **QHSSE Officer** | Create, update register dan obligations. Scope: assigned site(s). Owner utama register. |
| 5 | **Supervisor** | View register di department-nya. Tidak create/edit. Scope: department. |
| 6 | **Department Head** | View register di department-nya. Menerima notifikasi compliance. Scope: department. |
| 7 | **Employee/Reporter** | View register yang relevan (terbatas). Tidak create/edit. Scope: own. |
| 8 | **Contractor** | View register terkait (terbatas). Tidak create/edit. Scope: company. |
| 9 | **Auditor** | View semua register dalam scope. Export. Tidak create/edit (read-only, independent verification). |
| 10 | **Top Management** | View dashboard & compliance report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Legal Register CRUD

- **Create** — Form pembuatan register peraturan. Nomor `LEG-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal compliance: `in_progress` (default), status record: `active`.
- **List** — Halaman list dengan search (nomor, judul, nama regulasi), filter (category, compliance_status, site, department, owner), pagination (default 15 per page), dan tombol Export CSV.
- **Detail (Show)** — Halaman detail menampilkan: nomor, judul, nama regulasi, nomor regulasi, issuing body, kategori, compliance status, site, department, owner, next review date, document link, notes, obligations list, evidence files, comments, activity log, audit trail.
- **Update** — Edit record. Compliance status dapat diubah kapan saja selama status record `active`.
- **Delete** — Soft delete. Hanya Super Admin / Admin. Tidak bisa delete record yang masih memiliki obligations `pending`.

### 4.2 Compliance Status Management

- **Compliant** (hijau) — Organisasi telah mematuhi regulasi sepenuhnya.
- **Non-Compliant** (merah) — Organisasi tidak mematuhi regulasi. Tindakan korektif diperlukan.
- **In Progress** (kuning) — Sedang dalam proses pemenuhan kepatuhan.
- **Not Applicable** (abu-abu) — Regulasi tidak berlaku untuk organisasi/site.

### 4.3 Obligation Tracking

- **Create Obligation** — Tambah kewajiban ke register. Setiap kewajiban memiliki deskripsi, frekuensi (monthly/quarterly/annual), tanggal terakhir dilaksanakan, dan tanggal jatuh tempo berikutnya.
- **Due Date Calculation** — `next_due` dihitung otomatis berdasarkan `last_completed` + `frequency`:
  - `monthly`: next_due = last_completed + 1 month
  - `quarterly`: next_due = last_completed + 3 months
  - `annual`: next_due = last_completed + 1 year
- **Overdue Detection** — Obligation dengan `next_due` < hari ini dan `status = 'pending'` ditandai sebagai overdue. Badge merah ditampilkan.
- **Complete Obligation** — Saat kewajiban dilaksanakan, user mengisi `last_completed` dan mengupload evidence file. Status berubah menjadi `completed` dan `next_due` di-recalculate otomatis.
- **Evidence Upload** — Setiap obligation dapat memiliki 1 evidence file (bukti pelaksanaan).

### 4.4 Evidence Management

- Upload file bukti kepatuhan melalui File Service core.
- Collection: `evidence` untuk register-level, `obligation_evidence` untuk obligation-level.
- Multiple files per register.
- Download melalui authorized endpoint (permission check).

### 4.5 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record register.
- Activity log otomatis mencatat: create, update, compliance status change, obligation CRUD, obligation completed.
- Timeline ditampilkan di halaman detail register.

### 4.6 Notification

- 4 event notifikasi: `legal.register.created`, `legal.compliance.changed`, `legal.obligation.overdue`, `legal.obligation.due_soon`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.
- Scheduled check untuk overdue dan due soon obligations.

### 4.7 Dashboard & Reporting

- Dashboard widget: total register, breakdown by compliance status/category/site, overdue obligations count, upcoming due obligations.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Kategori Regulasi

Empat kategori regulasi didukung:

| # | Category Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `national` | **Regulasi Nasional** | Undang-undang, peraturan pemerintah, permen, kepmen tingkat nasional. Contoh: UU No. 1 Tahun 1970 tentang Keselamatan Kerja. |
| 2 | `regional` | **Regulasi Regional** | Peraturan daerah, surat keputusan gubernur, surat edaran dinas tingkat regional/provinsi. Contoh: Pergub JKT tentang K3. |
| 3 | `industry` | **Standar Industri** | Standar nasional Indonesia (SNI), standar industri, pedoman asosiasi. Contoh: SNI ISO 45001:2018, pedoman AKLI. |
| 4 | `internal` | **Regulasi Internal** | Kebijakan, prosedur, instruksi kerja internal organisasi. Contoh: Kebijakan K3 Perusahaan. |

### Compliance Status

| Status | Code | Color | Description |
|---|---|---|---|
| **Compliant** | `compliant` | `green` | Organisasi telah mematuhi regulasi sepenuhnya |
| **Non-Compliant** | `non_compliant` | `red` | Organisasi tidak mematuhi regulasi, tindakan korektif diperlukan |
| **In Progress** | `in_progress` | `yellow` | Sedang dalam proses pemenuhan kepatuhan |
| **Not Applicable** | `not_applicable` | `gray` | Regulasi tidak berlaku untuk organisasi/site |

### Obligation Frequency

| Frequency | Code | Description |
|---|---|---|
| **Bulanan** | `monthly` | Kewajiban dilaksanakan setiap bulan |
| **Triwulanan** | `quarterly` | Kewajiban dilaksanakan setiap 3 bulan |
| **Tahunan** | `annual` | Kewajiban dilaksanakan setiap tahun |

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor register di-generate **saat record dibuat** (POST create).
- Format: `LEG-YYYY-NNNN` (contoh: `LEG-2026-0001`).
- Sumber: `NumberingService::generate('legal', ...)`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `legal`
  - `prefix`: `LEG`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `LEG-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Compliance Status Default

- Saat register dibuat, `compliance_status` default ke `in_progress` (kuning).
- User dapat mengubah compliance status kapan saja selama record `active`.
- Perubahan compliance status dicatat di audit trail.
- Jika compliance status diubah ke `non_compliant`, notifikasi dikirim ke QHSSE Manager.

### BR-03: Obligation Due Date Auto-Calculation

- Saat obligation dibuat dengan `last_completed` terisi, `next_due` dihitung otomatis:
  - `monthly`: `next_due = last_completed + 1 month`
  - `quarterly`: `next_due = last_completed + 3 months`
  - `annual`: `next_due = last_completed + 1 year`
- Jika `last_completed` kosong, `next_due` wajib diisi manual.
- Saat obligation di-complete (user mengisi `last_completed` baru), `next_due` di-recalculate otomatis.

### BR-04: Overdue Detection

- Obligation dengan `next_due` < `CURRENT_DATE` dan `status = 'pending'` dianggap **overdue**.
- Overdue obligations ditampilkan dengan badge merah di UI.
- Scheduled job (daily) mengecek obligations yang baru menjadi overdue dan mengirim notifikasi.
- Obligation yang overdue tidak dapat diubah ke `pending` lagi — harus di-complete atau `next_due` di-extend.

### BR-05: Obligation Status Lifecycle

- Status obligation: `pending` (default) → `completed`.
- Saat obligation di-complete:
  - `last_completed` di-update dengan tanggal pelaksanaan.
  - `next_due` di-recalculate berdasarkan frequency.
  - `status` berubah menjadi `completed`.
  - `evidence_file_id` wajib diisi (bukti pelaksanaan).
- Setelah completed, obligation dapat di-reset ke `pending` jika due date baru tiba (untuk obligations berulang).
- Obligation dengan `status = 'completed'` dan `next_due` di masa depan: dianggap "on track".

### BR-06: Evidence Required for Completion

- Saat menyelesaikan obligation, `evidence_file_id` wajib diisi.
- Evidence file diupload via File Service core dengan `module_name='legal'`, `reference_id=legal_obligations.id`, `collection='obligation_evidence'`.
- Jika evidence tidak diupload, completion gagal dengan pesan error.

### BR-07: Next Review Date

- `next_review_date` pada register bersifat opsional.
- Jika diisi, sistem mengirim notifikasi 30 hari sebelum review date.
- Review date dapat digunakan untuk scheduling periodic compliance review.

### BR-08: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='legal'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `legal.register.created` | LegalRegister | new_values: all fields |
| `legal.register.updated` | LegalRegister | changed fields only |
| `legal.compliance.changed` | LegalRegister | compliance_status old/new |
| `legal.register.deleted` | LegalRegister | soft delete |
| `legal.obligation.created` | LegalObligation | new_values |
| `legal.obligation.updated` | LegalObligation | changed fields |
| `legal.obligation.completed` | LegalObligation | status change + last_completed + evidence |
| `legal.file.uploaded` | ManagedFile | new_values |
| `legal.file.downloaded` | ManagedFile | metadata: user, ip |
| `legal.exported` | LegalRegister | metadata: user, filters |

### BR-09: Data Visibility by Scope

Data visibility mengikuti role scope:

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter | Register yang relevan dengan department-nya (read-only) |
| `department` | Supervisor, Department Head | Register di department-nya |
| `site` | QHSSE Officer | Register di assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All registers |

- Scope check dilakukan **server-side** di Controller/Policy.
- Owner dapat melihat register yang dia miliki terlepas dari site scope.
- Contractor hanya melihat register yang terkait dengan company-nya (limited view).

---

## 7. Permission Keys

### 7.1 Legal Register Permissions (4 keys)

| # | Permission Key | Description |
|---|---|---|
| 1 | `legal.register.view` | View register list and detail. Scope-filtered. |
| 2 | `legal.register.create` | Create new register record. Generates LEG number. |
| 3 | `legal.register.update` | Update register record. Edit fields, change compliance status. |
| 4 | `legal.register.export` | Export register list to CSV. Scope-filtered. |

### 7.2 Legal Obligations Permissions (3 keys)

| # | Permission Key | Description |
|---|---|---|
| 5 | `legal.obligations.view` | View obligations for a register. |
| 6 | `legal.obligations.create` | Create new obligation for a register. |
| 7 | `legal.obligations.update` | Update obligation. Complete obligation, update due dates. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{resource}.{action}` → `legal.register.*` + `legal.obligations.*`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.

---

## 8. Role-Permission Matrix

### 8.1 Legal Register Permissions

| Role | `view` | `create` | `update` | `export` |
|---|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ✅ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ✅ |

### 8.2 Legal Obligations Permissions

| Role | `view` | `create` | `update` |
|---|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ |
| Department Head | ✅ | ❌ | ❌ |
| Employee/Reporter | ✅ | ❌ | ❌ |
| Contractor | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ |
| Top Management | ✅ | ❌ | ❌ |

### Notes

- **QHSSE Officer** dan **QHSSE Manager** adalah roles utama yang dapat membuat dan mengelola register serta obligations.
- **Supervisor** dan **Department Head** dapat melihat register dan obligations di department-nya tetapi tidak dapat create/edit.
- **Auditor** role memiliki view-only akses untuk independent verification.
- **Contractor** tidak dapat melihat obligations (informasi sensitif internal).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

4 event notifikasi untuk modul Legal & Compliance:

### 9.1 `legal.register.created`

| Property | Value |
|---|---|
| **Trigger** | User membuat register baru |
| **Recipients** | QHSSE Manager, owner register |
| **Type** | `legal.register.created` |
| **Title (template)** | `Register Baru: {register.register_number}` |
| **Message (template)** | `Register {register.register_number} - {register.title} telah dibuat oleh {actor.name}. Kategori: {register.category}. Compliance Status: {register.compliance_status}.` |
| **Action URL** | `/legal-register/{register.id}` |
| **Module/Reference** | `module_name='legal'`, `reference_id={register.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `legal.compliance.changed`

| Property | Value |
|---|---|
| **Trigger** | User mengubah compliance_status register |
| **Recipients** | QHSSE Manager, owner register, department head |
| **Type** | `legal.compliance.changed` |
| **Title (template)** | `Status Kepatuhan Berubah: {register.register_number}` |
| **Message (template)** | `Status kepatuhan register {register.register_number} - {register.title} telah diubah dari {old_status} menjadi {new_status} oleh {actor.name}.` |
| **Action URL** | `/legal-register/{register.id}` |
| **Module/Reference** | `module_name='legal'`, `reference_id={register.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured, especially for non_compliant) |

### 9.3 `legal.obligation.overdue`

| Property | Value |
|---|---|
| **Trigger** | Scheduled job mendeteksi obligation dengan `next_due` < hari ini dan `status = 'pending'` |
| **Recipients** | Owner register, QHSSE Officer (assigned site), QHSSE Manager |
| **Type** | `legal.obligation.overdue` |
| **Title (template)** | `Kewajiban Overdue: {obligation.obligation_description (truncated)}` |
| **Message (template)** | `Kewajiban "{obligation.obligation_description}" pada register {register.register_number} telah overdue. Jatuh tempo: {obligation.next_due}. Mohon segera laksanakan dan lengkapi evidence.` |
| **Action URL** | `/legal-register/{register.id}?tab=obligations` |
| **Module/Reference** | `module_name='legal'`, `reference_id={register.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.4 `legal.obligation.due_soon`

| Property | Value |
|---|---|
| **Trigger** | Scheduled job mendeteksi obligation dengan `next_due` dalam 7 hari ke depan dan `status = 'pending'` |
| **Recipients** | Owner register, QHSSE Officer (assigned site) |
| **Type** | `legal.obligation.due_soon` |
| **Title (template)** | `Kewajiban Jatuh Tempo: {obligation.obligation_description (truncated)}` |
| **Message (template)** | `Kewajiban "{obligation.obligation_description}" pada register {register.register_number} akan jatuh tempo dalam 7 hari ({obligation.next_due}). Mohon siapkan pelaksanaan dan evidence.` |
| **Action URL** | `/legal-register/{register.id}?tab=obligations` |
| **Module/Reference** | `module_name='legal'`, `reference_id={register.id}` |
| **Channel** | In-app (`core_notifications`) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit.
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Scheduled job berjalan daily (midnight) untuk cek overdue dan due_soon.
- Email hanya dikirim untuk overdue dan compliance changed ke `non_compliant` untuk mengurangi noise.
- Recipient resolution: query users with target role + matching scope (site/department).

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `legal` |
| **reference_id** | `legal_register.id` (for register-level) or `legal_obligations.id` (for obligation-level) |
| **collection** | `evidence` (register-level), `obligation_evidence` (obligation-level) |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `legal/{register_id}/evidence/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx` |
| **Max file size** | 25 MB per file |
| **Max files per register** | 20 |
| **Evidence per obligation** | 1 (single file via `evidence_file_id`) |

### 10.3 Access Rules

- **Upload**: User must have `legal.register.update` (register-level) or `legal.obligations.update` (obligation-level).
- **Download**: User must have `legal.register.view` and be within data scope.
- **Delete**: User must have `legal.register.update` AND register status must be `active`.

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Register** | Count all registers in scope | Number + icon |
| **Compliant** | Count where compliance_status = `compliant` | Number, green |
| **Non-Compliant** | Count where compliance_status = `non_compliant` | Number, red badge |
| **In Progress** | Count where compliance_status = `in_progress` | Number, yellow |
| **Not Applicable** | Count where compliance_status = `not_applicable` | Number, gray |
| **Overdue Obligations** | Count obligations where next_due < today AND status = `pending` | Number, red badge |
| **Due Soon (7 days)** | Count obligations where next_due <= today+7 AND status = `pending` | Number, orange |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Compliance Status Distribution** | Donut | Compliant / Non-Compliant / In Progress / Not Applicable |
| **Register by Category** | Donut | National / Regional / Industry / Internal |
| **Register by Site** | Horizontal bar | Count by site (top 10) |
| **Obligation Status** | Stacked bar | Pending / Completed / Overdue per month (last 12 months) |
| **Monthly Compliance Trend** | Line chart | Compliance rate by month (last 12 months) |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Recent Registers** | Number, Title, Category, Compliance Status, Created At | Last 10, scoped |
| **Overdue Obligations** | Register Number, Obligation Description, Next Due, Days Overdue | next_due < today, status=pending |
| **Upcoming Reviews** | Register Number, Title, Next Review Date, Owner | next_review_date <= today+30, sorted ascending |

### 11.4 Filters

- Category filter (national/regional/industry/internal)
- Compliance status filter
- Site filter
- Date range filter

---

## 12. Export Spec

### 12.1 CSV Export

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `legal_register_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `legal.register.export` |
| **Scope** | Follows user's data scope |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor Register` | `register.register_number` | LEG-YYYY-NNNN |
| 2 | `Judul` | `register.title` | |
| 3 | `Nama Regulasi` | `register.regulation_name` | |
| 4 | `Nomor Regulasi` | `register.regulation_number` | |
| 5 | `Issuing Body` | `register.issuing_body` | |
| 6 | `Kategori` | `register.category` | national/regional/industry/internal |
| 7 | `Status Kepatuhan` | `register.compliance_status` | compliant/non_compliant/in_progress/not_applicable |
| 8 | `Site` | `site.name` | Via `site_id`, nullable |
| 9 | `Department` | `department.name` | Via `department_id`, nullable |
| 10 | `Owner` | `owner.name` | Via `owner_id` → users |
| 11 | `Next Review Date` | `register.next_review_date` | YYYY-MM-DD, nullable |
| 12 | `Total Obligations` | Count of obligations | Integer |
| 13 | `Overdue Obligations` | Count obligations overdue | Integer |
| 14 | `Pending Obligations` | Count obligations status=pending | Integer |
| 15 | `Status Record` | `register.status` | active/inactive |
| 16 | `Notes` | `register.notes` | Truncated to 500 chars |
| 17 | `Created At` | `register.created_at` | YYYY-MM-DD HH:MM:SS |

### 12.3 Export Rules

- Export event dicatat di audit trail (`legal.exported`).
- Export mengikuti permission dan scope user.
- Maksimal 10.000 record per export.

---

## 13. Acceptance Criteria

1. **AC-01: Create with auto-numbering** — User dengan permission `legal.register.create` dapat membuat register. Nomor `LEG-YYYY-NNNN` di-generate otomatis. Nomor bersifat unique.

2. **AC-02: Permission enforcement** — User tanpa permission `legal.register.view` tidak dapat mengakses halaman list atau detail. User tanpa `legal.obligations.create` tidak dapat menambah obligation. Server-side check memblokir akses.

3. **AC-03: Compliance status management** — Compliance status dapat diubah antara 4 nilai: compliant, non_compliant, in_progress, not_applicable. Perubahan dicatat di audit trail dan trigger notifikasi.

4. **AC-04: Obligation CRUD** — User dengan permission dapat membuat obligation. Obligation memiliki deskripsi, frequency, last_completed, next_due, dan evidence_file_id.

5. **AC-05: Due date auto-calculation** — `next_due` dihitung otomatis berdasarkan `last_completed` + `frequency` (monthly=+1mo, quarterly=+3mo, annual=+1yr).

6. **AC-06: Overdue detection** — Obligation dengan `next_due` < hari ini dan `status='pending'` ditandai overdue. Badge merah ditampilkan di UI.

7. **AC-07: Obligation completion with evidence** — Saat menyelesaikan obligation, `evidence_file_id` wajib diisi. `last_completed` di-update, `next_due` di-recalculate, `status` berubah ke `completed`.

8. **AC-08: Notifications sent correctly** — Notifikasi terkirim untuk 4 event: `legal.register.created`, `legal.compliance.changed`, `legal.obligation.overdue`, `legal.obligation.due_soon`.

9. **AC-09: List with search/filter/pagination/export** — Halaman list mendukung search, filter, pagination, dan export CSV.

10. **AC-10: Audit trail complete** — Audit trail tercatat untuk semua event kritikal: create, update, compliance change, obligation CRUD, obligation completed, file upload/download, export.

---

## 14. Open Questions

| # | Question | Default Answer |
|---|---|---|
| 1 | Apakah register perlu workflow approval untuk compliance status change? | **No untuk Phase 14** — compliance status change langsung oleh authorized user. Workflow approval dapat ditambahkan di future phase. |
| 2 | Apakah obligation mendukung custom frequency (bukan monthly/quarterly/annual)? | **No untuk Phase 14** — hanya 3 frequency standar. Custom frequency dapat ditambahkan dengan field `custom_days` di future. |
| 3 | Apakah perlu integrasi CAPA saat compliance non_compliant? | **Future** — tombol "Create CAPA" pada register non_compliant dapat ditambahkan. Phase 14 fokus pada register dan obligation tracking. |
| 4 | Apakah register dapat diarsipkan (archived)? | **Yes** — field `status` dengan nilai `inactive` untuk arsip. Register inactive tidak tampil di list default tapi tetap bisa di-search. |
| 5 | Apakah perlu versioning regulasi (update peraturan)? | **No untuk Phase 14** — update regulasi dilakukan dengan edit record. Versioning dapat ditambahkan di future. |
| 6 | Apakah email notifikasi wajib untuk semua event? | **No** — in-app wajib untuk semua. Email hanya untuk overdue dan compliance changed ke non_compliant. |
| 7 | Apakah obligation dapat memiliki multiple evidence files? | **No untuk Phase 14** — 1 obligation : 1 evidence file (via `evidence_file_id`). Multiple evidence dapat diubah di future. |
| 8 | Apakah perlu scheduled report (monthly compliance summary)? | **Future** — scheduled report dapat ditambahkan via Laravel Scheduler + NotificationService. Phase 14 fokus pada real-time tracking. |

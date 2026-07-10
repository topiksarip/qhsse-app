# Module Spec — Incident Reporting

> **Module ID:** `02-incident-reporting`  
> **Module Code (numbering):** `incident`  
> **Number Prefix:** `INC`  
> **Workflow Code:** `INCIDENT_WORKFLOW`  
> **Phase:** Phase 1 (first business module after Core Foundation)  
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul Incident Reporting menyediakan sistem pelaporan dan pengelolaan kejadian QHSSE (Quality, Health, Safety, Security, Environment) secara end-to-end. Modul ini mengakomodasi tujuh kategori laporan: Accident, Incident, Near Miss, Unsafe Act, Unsafe Condition, Environmental Spill, dan Security Breach.

Tujuan utama:

- Memungkinkan **siapa pun** dengan akun (employee, contractor, supervisor) melaporkan kejadian QHSSE dengan cepat, termasuk dari perangkat mobile.
- Memastikan setiap laporan resmi memiliki **nomor unik** (INC-YYYY-NNNN) yang di-generate otomatis pada saat create.
- Menyediakan **workflow status** yang jelas: Draft → Submitted → Under Review → Investigation → Action Open → Closed (dengan jalur Reject).
- Mengelola **evidence attachments** (foto, dokumen, video) melalui File Service core dengan collection `evidence`.
- Mengirim **notifikasi** ke reviewer/QHSSE Officer saat laporan di-submit, di-review, di-reject, dan di-close.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal (create, submit, review, reject, close, field changes).
- Menyediakan **dashboard metrics** dan **export CSV** untuk analisis dan pelaporan manajemen.
- Menghubungkan ke modul **Investigation & RCA** (module `03`) dan **CAPA** (module `04`) via cross-module link.

---

## 2. Dependency

### Core Foundation (Phase 0 —已完成)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 7 permission keys `incident.reports.*` |
| **NumberingService** | Generate `INC-YYYY-NNNN` on create |
| **WorkflowService** | Status transitions per `INCIDENT_WORKFLOW` definition |
| **FileService** | Upload/download evidence files via `managed_files` table |
| **NotificationService** | In-app + email notifications via `core_notifications` table |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='incident'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **ExportService** | CSV export via `core.export.csv` permission |
| **MasterData** | Sites, Areas, Departments, Companies, Severities, Priorities, Categories |

### Cross-Module (future phases)

| Module | Relationship |
|---|---|
| `03-investigation-rca` | Incident can trigger an Investigation record (1:0..1) |
| `04-capa-action-tracking` | Incident can trigger CAPA records (1:0..n) via `open_action` transition |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini (sesuai `RolesAndPermissionsSeeder`):

| # | Role | Deskripsi Peran dalam Incident Reporting |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Review, reject, close incident. Scope: all sites. Approve tindak lanjut. |
| 4 | **QHSSE Officer** | Review, reject, close incident. Scope: assigned site(s). Investigator utama. |
| 5 | **Supervisor** | Create, update own draft, submit. Review laporan dari department-nya. Scope: department. |
| 6 | **Department Head** | Lihat laporan di department-nya. Approve terkait. Scope: department. |
| 7 | **Employee/Reporter** | Create draft, submit, update own draft. Scope: own. |
| 8 | **Contractor** | Create draft, submit, update own draft. Scope: company (contractor company). |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Incident CRUD

- **Create** — Form pembuatan laporan incident. Nomor `INC-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat (bukan pada submit). Status awal: `draft`.
- **List** — Halaman list dengan search (nomor, judul, reporter), filter (site, department, area, status, category, severity, date range), pagination (default 15 per page), dan tombol Export CSV.
- **Detail** — Halaman detail menampilkan: nomor, judul, deskripsi, kategori, severity, lokasi (site/area/department), reporter, event date/time, status, workflow timeline, attachments, comments, activity log, audit trail.
- **Update** — Edit record. Hanya bisa edit jika status `draft`. Setelah submit, record tidak bisa di-edit kecuali di-reject (kembali ke draft/revisi oleh reporter).
- **Delete** — Soft delete. Hanya Super Admin / Admin. Tidak bisa delete record yang sudah `closed` atau `rejected`.

### 4.2 Workflow Actions

- **Save Draft** — Simpan tanpa validasi mandatory fields. Status tetap `draft`.
- **Submit** — Validasi mandatory fields. Status: `draft` → `submitted`. Trigger notifikasi ke QHSSE Officer/Manager.
- **Start Review** — QHSSE Officer/Manager memulai review. Status: `submitted` → `under_review`.
- **Start Investigation** — QHSSE Officer/Manager mulai investigasi. Status: `under_review` → `investigation`. Dapat memicu pembuatan record di modul Investigation.
- **Open Action** — Buka CAPA/action item dari incident. Status: `under_review` atau `investigation` → `action_open`. Dapat memicu pembuatan record di modul CAPA.
- **Close** — Tutup incident. Wajib isi reason. Status: `action_open` → `closed`. Trigger notifikasi ke reporter dan stakeholder.
- **Reject** — Tolak incident. Wajib isi reason. Status: `submitted` atau `under_review` → `rejected`. Trigger notifikasi ke reporter.

### 4.3 Evidence Management

- Upload file bukti (foto, video, dokumen) melalui File Service core.
- Collection: `evidence`.
- Multiple files per incident.
- Download melalui authorized endpoint (permission check).
- Tidak bisa hapus file setelah status `closed`.

### 4.4 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record.
- Activity log otomatis mencatat: create, submit, review, reject, close, field changes.
- Timeline ditampilkan di halaman detail.

### 4.5 Notification

- 4 event notifikasi: `incident.submitted`, `incident.reviewing`, `incident.closed`, `incident.rejected`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.6 Dashboard & Reporting

- Dashboard widget: total incident, breakdown by status/category/severity/site, trend bulanan.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

### 4.7 Cross-Module Link

- Incident → Investigation: link `investigation_id` (nullable, set saat investigation dibuat dari incident).
- Incident → CAPA: link via CAPA record's `source_module='incident'` dan `source_reference_id`.

---

## 5. Kategori Laporan

Tujuh kategori incident, di-seed di tabel `categories` dengan `module='incident'`:

| # | Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `ACCIDENT` | **Accident** | Kejadian yang mengakibatkan cedera fisik pada person, kerusakan aset/properti, atau dampak lingkungan aktual. Termasuk: kecelakaan kerja, kecelakaan kendaraan, kecelakaan proses. Wajib dilaporkan segera (max 24 jam). |
| 2 | `INCIDENT` | **Incident** | Kejadian tidak terduga yang berpotensi menyebabkan dampak namun belum menimbulkan kerugian signifikan. Termasuk: gangguan operasi, kegagalan prosedur, equipment malfunction yang terdeteksi. |
| 3 | `NEAR_MISS` | **Near Miss** | Kejadian yang hampir menyebabkan cedera, kerusakan, atau dampak tetapi berhasil dicegah atau tidak terjadi. Contoh: hampir tertimpa beban, tersandung tapi tidak jatuh, hampir terpapar bahan kimia. Pelaporan near miss adalah indikator budaya safety yang proaktif. |
| 4 | `UNSAFE_ACT` | **Unsafe Act** | Tindakan atau perilaku person yang tidak sesuai prosedur safety dan berpotensi menyebabkan incident. Contoh: tidak memakai APD, bekerja tanpa izin, mengabaikan prosedur lockout/tagout, menggunakan equipment tanpa training. |
| 5 | `UNSAFE_CONDITION` | **Unsafe Condition** | Kondisi lingkungan kerja, equipment, atau fasilitas yang berpotensi menyebabkan incident. Contoh: lantai licin, guard rail rusak, pencahayaan buruk, kebocoran pipa, kabel terbuka. |
| 6 | `ENVIRONMENTAL_SPILL` | **Environmental Spill** | Tumpahan atau pelepasan bahan (kimia, minyak, limbah, gas) ke lingkungan yang berpotensi menyebabkan pencemaran. Termasuk: spill ke tanah/air/udara, emisi melampaui batas, pembuangan limbah tidak sesuai prosedur. |
| 7 | `SECURITY_BREACH` | **Security Breach** | Pelanggaran keamanan fisik atau sistem yang mengancam keselamatan person, aset, atau informasi. Termasuk: akses tidak sah, pencurian, sabotase, ancaman, pelanggaran area restricted. |

> **Catatan:** Kategori saat ini di-seed: ACCIDENT, NEAR_MISS, UNSAFE_ACT, UNSAFE_CONDITION. Kategori INCIDENT, ENVIRONMENTAL_SPILL, SECURITY_BREACH perlu ditambahkan ke `QhsseMasterDataSeeder` saat implementasi modul.

### Severity Levels (sudah di-seed)

| Code | Name | Level | Color |
|---|---|---|---|
| `LOW` | Low | 1 | green |
| `MEDIUM` | Medium | 2 | yellow |
| `HIGH` | High | 3 | orange |
| `CRITICAL` | Critical | 4 | red |

### Priority Levels (sudah di-seed)

| Code | Name | SLA Days | Color |
|---|---|---|---|
| `LOW` | Low | 30 | green |
| `MEDIUM` | Medium | 14 | yellow |
| `HIGH` | High | 7 | orange |
| `URGENT` | Urgent | 1 | red |

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor incident di-generate **saat record dibuat** (POST create), bukan saat submit.
- Format: `INC-YYYY-NNNN` (contoh: `INC-2026-0001`).
- Sumber: `NumberingService::generate('incident')`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `incident`
  - `prefix`: `INC`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `INC-2026-0001`
- Nomor bersifat **unique**. Jika terjadi race condition, database unique constraint mencegah duplikat; service melakukan retry dengan increment.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Draft Save Without Mandatory Fields

- Saat status `draft`, record dapat disimpan tanpa mengisi mandatory fields.
- Field wajib hanya divalidasi saat **submit**.
- Reporter dapat menyimpan draft berkali-kali sebelum submit.
- Draft yang tidak di-submit tetap tersimpan dan dapat diakses oleh reporter (scope: own).

### BR-03: Submit Validates Mandatory Fields

Saat user melakukan **submit** (transition `draft` → `submitted`), sistem memvalidasi field mandatory berikut:

| Field | Validation Rule |
|---|---|
| `title` | required, string, max:255 |
| `description` | required, text |
| `category_id` | required, exists in categories where module='incident' |
| `severity_id` | required, exists in severities |
| `site_id` | required, exists in sites |
| `area_id` | required, exists in areas |
| `department_id` | required, exists in departments |
| `event_date` | required, date, before_or_equal: today |
| `event_time` | nullable, date format H:i |
| `reporter_id` | required, exists in users (auto-filled from authenticated user) |

Jika validasi gagal, submit ditolak dan record tetap berstatus `draft`.

### BR-04: Reject Requires Reason

- Transition `submitted` → `rejected` dan `under_review` → `rejected` memerlukan field `reason` (wajib, text, min:10 karakter).
- Reason disimpan di `workflow_histories.reason`.
- Notifikasi dikirim ke reporter.

### BR-05: Close Requires Reason

- Transition `action_open` → `closed` memerlukan field `reason` (wajib, text, min:10 karakter).
- Reason disimpan di `workflow_histories.reason`.
- Notifikasi dikirim ke reporter dan stakeholder terkait.
- Setelah close, record menjadi read-only. Tidak bisa edit, tidak bisa hapus file evidence.

### BR-06: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='incident'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `incident.created` | Incident record | new_values: all fields |
| `incident.updated` | Incident record | changed fields only |
| `incident.submitted` | Incident record | status change |
| `incident.reviewing` | Incident record | status change |
| `incident.investigation_started` | Incident record | status change |
| `incident.action_opened` | Incident record | status change |
| `incident.closed` | Incident record | status change + reason |
| `incident.rejected` | Incident record | status change + reason |
| `incident.deleted` | Incident record | soft delete |
| `incident.file.uploaded` | ManagedFile | new_values |
| `incident.file.deleted` | ManagedFile | soft delete |
| `incident.file.downloaded` | ManagedFile | metadata: user, ip |

### BR-07: Data Visibility by Scope

Data visibility mengikuti role scope (sesuai `CorePermissions::roleMap()`):

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter, Contractor | Only incidents they created |
| `department` | Supervisor, Department Head | Incidents in their department |
| `site` | QHSSE Officer | Incidents in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All incidents |

- Scope check dilakukan **server-side** di Controller/Policy, bukan di frontend.
- Contractor hanya melihat incident milik company/contractor-nya.
- Query scope: filter berdasarkan `reporter_id` (own), `department_id` (department), `site_id` (site), atau no filter (all).

---

## 7. Permission Keys

7 permission keys untuk modul Incident Reporting:

| # | Permission Key | Description |
|---|---|---|
| 1 | `incident.reports.view` | View incident list and detail. Scope-filtered. |
| 2 | `incident.reports.create` | Create new incident record. Generates INC number. |
| 3 | `incident.reports.update` | Update incident record. Only draft status. Own or scope-based. |
| 4 | `incident.reports.submit` | Submit incident (draft → submitted). Validates mandatory fields. |
| 5 | `incident.reports.review` | Review/reject/investigate/open action on incident. QHSSE roles. |
| 6 | `incident.reports.close` | Close incident (action_open → closed). Requires reason. |
| 7 | `incident.reports.export` | Export incident list to CSV. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}` → `incident.reports.*`.
- Keys harus di-register di seeder (tambahkan ke `CorePermissions::all()` atau buat `IncidentPermissions` class terpisah).
- Keys di-assign ke roles via `CorePermissions::roleMap()` atau seeder modul.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.
- Workflow transition menggunakan permission core: `core.workflow.transition`.

---

## 8. Role-Permission Matrix

| Role | `view` | `create` | `update` | `submit` | `review` | `close` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |
| Employee/Reporter | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

### Notes

- **Supervisor** dapat `review` (start review) tetapi tidak dapat `close`. Close adalah otoritas QHSSE Officer/Manager.
- **Department Head** dapat `review` (melihat dan menyetujui dari sisi department) tetapi tidak dapat create/update/submit/close.
- **Employee/Reporter** dan **Contractor** dapat create/update/submit incident miliknya sendiri (scope: own/company).
- **Auditor** dan **Top Management** hanya view + export (read-only).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

4 event notifikasi untuk modul Incident Reporting:

### 9.1 `incident.submitted`

| Property | Value |
|---|---|
| **Trigger** | User melakukan submit (transition `draft` → `submitted`) |
| **Recipients** | All users with role `QHSSE Officer` and `QHSSE Manager` in the same site scope. If no site-scoped QHSSE Officer exists, notify all QHSSE Managers. |
| **Type** | `incident.submitted` |
| **Title (template)** | `Laporan Incident Baru: {incident.number}` |
| **Message (template)** | `{reporter.name} telah mengirimkan laporan incident {incident.number} - {incident.title} di {site.name}. Mohon lakukan review.` |
| **Action URL** | `/incidents/{incident.id}` |
| **Module/Reference** | `module_name='incident'`, `reference_id={incident.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `incident.reviewing`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager melakukan start review (transition `submitted` → `under_review`) |
| **Recipients** | The original reporter (`incident.reporter_id`) |
| **Type** | `incident.reviewing` |
| **Title (template)** | `Laporan Anda Sedang Direview: {incident.number}` |
| **Message (template)** | `Laporan incident {incident.number} - {incident.title} sedang dalam proses review oleh {reviewer.name} ({reviewer.role}).` |
| **Action URL** | `/incidents/{incident.id}` |
| **Module/Reference** | `module_name='incident'`, `reference_id={incident.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.3 `incident.closed`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager melakukan close (transition `action_open` → `closed`) |
| **Recipients** | Reporter (`incident.reporter_id`), Supervisor of the department, Department Head |
| **Type** | `incident.closed` |
| **Title (template)** | `Laporan Incident Ditutup: {incident.number}` |
| **Message (template)** | `Laporan incident {incident.number} - {incident.title} telah ditutup oleh {closer.name}. Alasan: {close_reason}.` |
| **Action URL** | `/incidents/{incident.id}` |
| **Module/Reference** | `module_name='incident'`, `reference_id={incident.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.4 `incident.rejected`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager melakukan reject (transition `submitted`/`under_review` → `rejected`) |
| **Recipients** | The original reporter (`incident.reporter_id`) |
| **Type** | `incident.rejected` |
| **Title (template)** | `Laporan Incident Ditolak: {incident.number}` |
| **Message (template)** | `Laporan incident {incident.number} - {incident.title} ditolak oleh {rejecter.name}. Alasan: {reject_reason}. Silakan perbaiki dan kirim ulang.` |
| **Action URL** | `/incidents/{incident.id}` |
| **Module/Reference** | `module_name='incident'`, `reference_id={incident.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit (use Laravel Event/Listener or Observer pattern).
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Template variables (`{incident.number}`, etc.) di-resolve di NotificationService atau listener.
- Recipient resolution: query users with target role + matching scope (site/department).

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `incident` |
| **reference_id** | `incident.id` |
| **collection** | `evidence` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `incident/{incident_id}/evidence/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `mp4`, `mov`, `avi` |
| **Allowed MIME types** | Corresponding to extensions above |
| **Max file size** | 25 MB per file |
| **Max files per incident** | 20 |
| **Filename** | Original filename stored in `original_name`; generated UUID-based name in `stored_name` |

### 10.3 Access Rules

- **Upload**: User must have `incident.reports.update` (or be the reporter of a draft).
- **Download**: User must have `incident.reports.view` and be within data scope of the incident.
- **Delete**: User must have `incident.reports.update` AND incident status must NOT be `closed` or `rejected`. Once an incident is `closed`, evidence files **cannot be deleted** except by Super Admin / Admin.
- Download endpoint streams file from private storage; no direct public URL.
- File access logged in audit trail (`incident.file.downloaded`).

### 10.4 File Metadata

Each file record in `managed_files` includes:

- `module_name`: `incident`
- `reference_id`: incident ID
- `collection`: `evidence`
- `disk`: `local`
- `path`: storage path
- `original_name`: user's original filename
- `stored_name`: generated filename
- `mime_type`: detected MIME
- `extension`: file extension
- `size`: file size in bytes
- `checksum`: SHA-256 hash (optional)
- `metadata`: JSON (e.g., `{"caption": "Photo of spill area", "taken_at": "2026-07-11T14:30:00"}`)
- `uploaded_by`: user ID
- `deleted_at`: soft delete timestamp
- `deleted_by`: user ID who deleted

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Incidents** | Count all incidents in scope | Number + icon |
| **Open Incidents** | Count where status NOT IN (`closed`, `rejected`) | Number, red if > threshold |
| **Closed Incidents** | Count where status = `closed` | Number, green |
| **Critical Incidents** | Count where severity = `CRITICAL` AND status NOT IN (`closed`, `rejected`) | Number, red badge |
| **Rejected Incidents** | Count where status = `rejected` | Number, yellow |
| **This Month** | Count created in current month | Number + trend arrow (vs last month) |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Trend** | Line chart | Incident count by month (last 12 months), split by status (open/closed) |
| **By Category** | Bar chart or Donut | Count by category (Accident, Incident, Near Miss, etc.) |
| **By Severity** | Stacked bar or Donut | Count by severity (Low, Medium, High, Critical) |
| **By Site** | Horizontal bar | Count by site (top 10) |
| **By Status** | Donut | Count by workflow status |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Recent Incidents** | Number, Title, Category, Severity, Site, Status, Created At | Last 10, scoped |
| **Critical Open** | Number, Title, Site, Reporter, Created At, Days Open | Severity=CRITICAL, status open |
| **Aging Report** | Number, Title, Status, Created At, Days Since Created | Sorted by oldest, status open |

### 11.4 Filters

Dashboard metrics support:
- Date range filter (default: current year)
- Site filter
- Department filter
- Category filter
- Severity filter

---

## 12. Export Spec

### 12.1 CSV Export

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `incidents_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `incident.reports.export` |
| **Scope** | Follows user's data scope (own/department/site/all) |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `incident.number` | INC-YYYY-NNNN |
| 2 | `Judul` | `incident.title` | |
| 3 | `Deskripsi` | `incident.description` | Truncated to 500 chars |
| 4 | `Kategori` | `category.name` | Via `category_id` |
| 5 | `Severity` | `severity.name` | Via `severity_id` |
| 6 | `Priority` | `priority.name` | Via `priority_id`, nullable |
| 7 | `Site` | `site.name` | Via `site_id` |
| 8 | `Area` | `area.name` | Via `area_id` |
| 9 | `Department` | `department.name` | Via `department_id` |
| 10 | `Reporter` | `reporter.name` | Via `reporter_id` → users → employee |
| 11 | `Event Date` | `incident.event_date` | YYYY-MM-DD |
| 12 | `Event Time` | `incident.event_time` | HH:MM, nullable |
| 13 | `Status` | `incident.status` | draft, submitted, under_review, etc. |
| 14 | `Created At` | `incident.created_at` | YYYY-MM-DD HH:MM:SS |
| 15 | `Closed At` | `incident.closed_at` | Nullable, from workflow_histories |
| 16 | `Closed By` | `closer.name` | Nullable, from workflow_histories actor |
| 17 | `Close Reason` | `close_reason` | Nullable, from workflow_histories reason |
| 18 | `Investigation Linked` | `investigation_id` | Yes/No |
| 19 | `CAPA Count` | Count of CAPA records linked | Integer |
| 20 | `Attachment Count` | Count of managed_files | Integer |

### 12.3 Export Rules

- Export event dicatat di audit trail (`incident.exported`).
- Export mengikuti permission dan scope user.
- Maksimal 10.000 record per export. Jika lebih, tampilkan pesan untuk mempersempit filter.
- Date format mengikuti ISO 8601 di CSV (YYYY-MM-DD) untuk kompatibilitas.

---

## 13. Acceptance Criteria

1. **AC-01: Create with auto-numbering** — User dengan permission `incident.reports.create` dapat membuat incident record. Nomor `INC-YYYY-NNNN` di-generate otomatis pada saat create. Nomor bersifat unique dan tidak duplikat.

2. **AC-02: Permission enforcement** — User tanpa permission `incident.reports.view` tidak dapat mengakses halaman list atau detail. User tanpa permission `incident.reports.create` tidak dapat mengakses form create. Server-side check memblokir akses meskipun user memanipulasi URL/API.

3. **AC-03: Draft save without mandatory fields** — User dapat menyimpan draft incident tanpa mengisi field mandatory (title, description, category, severity, site, area, department, event_date). Draft tetap tersimpan dan dapat diakses kembali oleh reporter.

4. **AC-04: Submit validates mandatory fields** — Saat submit, sistem memvalidasi semua mandatory fields. Jika ada field yang kosong/tidak valid, submit gagal dengan pesan error per field. Record tetap berstatus `draft`.

5. **AC-05: Workflow transitions correct** — Workflow transition berjalan sesuai definisi `INCIDENT_WORKFLOW`: draft→submitted(submit), submitted→under_review(review), under_review→investigation(investigate), under_review→action_open(open_action), investigation→action_open(open_action), action_open→closed(close, requires_reason), submitted→rejected(reject, requires_reason), under_review→rejected(reject, requires_reason). Transition tidak valid ditolak.

6. **AC-06: Reject and close require reason** — Transition reject (submitted/under_review → rejected) dan close (action_open → closed) wajib mengisi reason (min 10 karakter). Jika reason kosong, transition gagal dengan pesan error.

7. **AC-07: Evidence upload and access control** — User dengan permission dapat upload file evidence (sesuai extension/size limit). Download hanya melalui authorized endpoint. Unauthorized user tidak dapat download. File tidak bisa dihapus setelah incident status `closed`.

8. **AC-08: Notifications sent correctly** — Notifikasi terkirim untuk 4 event: `incident.submitted` (ke QHSSE Officer/Manager), `incident.reviewing` (ke reporter), `incident.closed` (ke reporter + supervisor + dept head), `incident.rejected` (ke reporter). Notifikasi muncul di notification center recipient.

9. **AC-09: List with search/filter/pagination/export** — Halaman list mendukung search (nomor, judul), filter (site, department, area, status, category, severity, date range), pagination (15 per page), dan export CSV. Hasil export sesuai filter dan scope user.

10. **AC-10: Audit trail complete** — Audit trail tercatat untuk: create, update, submit, review, investigation, action_open, close, reject, delete, file upload, file download, file delete, export. Audit trail menampilkan actor, timestamp, old/new values.

---

## 14. Open Questions

| # | Question | Default Answer (if not decided) |
|---|---|---|
| 1 | Apakah kategori INCIDENT, ENVIRONMENTAL_SPILL, SECURITY_BREACH perlu ditambahkan ke seeder? | **Yes** — tambahkan ke `QhsseMasterDataSeeder` saat implementasi. Seed 3 kategori tambahan: `['incident', 'INCIDENT', 'Incident']`, `['incident', 'ENVIRONMENTAL_SPILL', 'Environmental Spill']`, `['incident', 'SECURITY_BREACH', 'Security Breach']`. |
| 2 | Apakah incident bisa di-reopen setelah closed? | **No untuk Phase 1** — closed adalah terminal status. Reopen dapat ditambahkan di Phase 2 jika ada kebutuhan. Jika diperlukan, tambah transition `closed → draft` dengan permission `incident.reports.reopen` (key ke-8) dan wajib reason. |
| 3 | Apakah reporter dapat memilih severity atau ditentukan QHSSE Officer? | **Reporter memilih** severity saat create/submit. QHSSE Officer dapat mengubah severity saat review (update field, tercatat di audit trail). |
| 4 | Apakah ada SLA/time limit untuk review setelah submit? | **No untuk Phase 1** — tidak ada auto-escalation. Notifikasi overdue dapat ditambahkan di Phase 2 dengan cron job checking `submitted` status age > X days. |
| 5 | Apakah incident dapat di-link ke multiple CAPA records? | **Yes** — 1 incident dapat memicu multiple CAPA records. Link via CAPA's `source_module='incident'` dan `source_reference_id=incident.id`. |
| 6 | Apakah ada field untuk person involved (selain reporter)? | **Yes untuk Phase 1** — tambah field JSON `persons_involved` (array of names/employee IDs) di tabel incident. Tidak wajib. |
| 7 | Apakah ada field untuk immediate action taken? | **Yes untuk Phase 1** — tambah field text `immediate_action` di tabel incident. Tidak wajib saat draft, wajib saat submit jika severity = HIGH atau CRITICAL. |
| 8 | Apakah notifikasi email aktif di Phase 1? | **Optional** — In-app notification wajib. Email jika SMTP configured. Gunakan Laravel queue untuk email. |
| 9 | Apakah incident dapat dibuat dari mobile (PWA/mobile browser)? | **Yes** — UI responsive. Form create mobile-friendly. Tidak ada native app di Phase 1. |
| 10 | Apakah ada field untuk estimated cost/damage value? | **No untuk Phase 1** — dapat ditambahkan di Phase 2 sebagai field `estimated_cost` (decimal, nullable). |
| 11 | Apakah permission key menggunakan `incident.reports.*` atau `incident.reporting.*`? | **`incident.reports.*`** — sesuai task spec. Entity = `reports`, module = `incident`. |
| 12 | Apakah perlu PDF report per incident (selain CSV export)? | **No untuk Phase 1** — CSV export list sudah cukup. PDF per incident dapat ditambahkan di Phase 2 menggunakan Laravel DOMPDF atau Snappy. |

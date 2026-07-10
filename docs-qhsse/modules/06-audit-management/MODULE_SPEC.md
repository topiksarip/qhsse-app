# Module Spec — Audit Management

> **Module ID:** `06-audit-management`  
> **Module Code (numbering):** `audit`  
> **Number Prefix:** `AUD`  
> **Workflow Code:** `AUDIT_WORKFLOW`  
> **Phase:** Phase 6 — Audit Management  
> **Status:** Ready for coding  
> **Depends on:** Core Foundation (Phase 0), CAPA (Phase 4), Document Control (Phase 7)

---

## 1. Tujuan Modul

Modul Audit Management menyediakan sistem perencanaan, pelaksanaan, dan pelaporan audit QHSSE secara end-to-end. Modul ini mencakup audit internal, audit eksternal (sertifikasi/pelanggan), dan audit supplier, serta pengelolaan temuan (findings) audit dengan klasifikasi Major, Minor, Observation, dan OFI (Opportunity For Improvement).

Tujuan utama:

- Memungkinkan **QHSSE team** merencanakan dan menjadwalkan audit dengan nomor unik `AUD-YYYY-NNNN` yang di-generate otomatis pada saat create.
- Menyediakan **workflow status** audit: `planned` → `in_progress` → `report_ready` → `closed`.
- Mengelola **findings** audit dengan 4 klasifikasi: Major, Minor, Observation, OFI.
- Menghubungkan setiap finding ke **CAPA action** (modul `04-capa-action-tracking`) melalui tombol "Create CAPA" pada finding.
- Menyimpan **audit evidence** (laporan audit, checklist, dokumen pendukung) melalui File Service core dengan collection `evidence`.
- Mengirim **notifikasi** ke lead auditor, auditee, dan QHSSE Manager saat audit dimulai, report siap, dan audit ditutup.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal (create, update, start, generate report, close, finding CRUD, CAPA link).
- Menyediakan **dashboard metrics** dan **export CSV** untuk analisis dan pelaporan manajemen.
- Mendukung **standard-based audit** (ISO 45001, ISO 9001, ISO 14001, ISO 27001, dll) dengan field `standard`.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 11 permission keys: `audit.management.*` (6) + `audit.findings.*` (4) + shared `core.files.*`, `core.comments.*` |
| **NumberingService** | Generate `AUD-YYYY-NNNN` on create; also `finding_number` via separate numbering or auto-format |
| **WorkflowService** | Status transitions per `AUDIT_WORKFLOW` definition |
| **FileService** | Upload/download audit evidence & report files via `managed_files` table |
| **NotificationService** | In-app + email notifications via `core_notifications` table |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='audit'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **CsvExporter** | CSV export via `audit.management.export` permission |
| **ListQuery** | Paginated list with search, filter, sort |
| **MasterData** | Sites, Departments, Users (for lead auditor selection) |

### Cross-Module (existing / future phases)

| Module | Relationship |
|---|---|
| `04-capa-action-tracking` | Each audit finding can link to a CAPA action via `capa_action_id` FK. "Create CAPA" button on finding creates a new CAPA record with `source_module='audit'` and `source_reference_id=audit_finding.id`. |
| `07-document-control` | Audit may reference controlled documents (audit standards, procedures). Future: link audit checklist to document versions. |
| `14-legal-compliance` | Audit findings may reference legal/regulatory requirements. Future: link finding to legal register. |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini (sesuai `RolesAndPermissionsSeeder`):

| # | Role | Deskripsi Peran dalam Audit Management |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Create, update, execute, close, export audit. Scope: all sites. Approve audit report. |
| 4 | **QHSSE Officer** | Create, update, execute, close, export audit. Create/update/close findings. Scope: assigned site(s). Lead auditor utama. |
| 5 | **Supervisor** | View audit di department-nya. Tidak create/edit. Scope: department. |
| 6 | **Department Head** | View audit di department-nya. Menerima notifikasi audit. Scope: department. |
| 7 | **Employee/Reporter** | View audit yang relevan (terbatas). Tidak create/edit. Scope: own. |
| 8 | **Contractor** | View audit supplier terkait. Tidak create/edit. Scope: company. |
| 9 | **Auditor** | View semua audit dalam scope. Export. Tidak create/edit (read-only, independent verification). |
| 10 | **Top Management** | View dashboard & audit report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Audit CRUD

- **Create** — Form pembuatan audit. Nomor `AUD-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal: `planned`.
- **List** — Halaman list dengan search (nomor, judul, lead auditor), filter (site, type, standard, status, date range), pagination (default 15 per page), dan tombol Export CSV.
- **Detail (Show)** — Halaman detail menampilkan: nomor, judul, type, standard, scope, site, department, lead auditor, date range, status, summary, findings tab, evidence files, workflow timeline, comments, activity log, audit trail.
- **Update** — Edit record. Hanya bisa edit jika status `planned`. Setelah audit dimulai (`in_progress`), record tidak bisa di-edit.
- **Delete** — Soft delete. Hanya Super Admin / Admin. Tidak bisa delete record yang sudah `closed`.

### 4.2 Workflow Actions

- **Start** — Mulai pelaksanaan audit. Status: `planned` → `in_progress`. Trigger notifikasi ke auditee.
- **Generate Report** — Hasilkan audit report. Status: `in_progress` → `report_ready`. Wajib isi summary. Trigger notifikasi ke QHSSE Manager.
- **Close** — Tutup audit. Status: `report_ready` → `closed`. Semua findings harus berstatus `closed` atau `linked` (CAPA created). Trigger notifikasi ke stakeholder.

### 4.3 Finding Management

- **Create Finding** — Tambah finding ke audit. Finding number auto-generated (format: `AUD-YYYY-NNNN-F##`).
- **Klasifikasi Finding**:
  - **Major** — Temuan major: tidak patuh signifikan, risiko tinggi, sistem tidak efektif. Wajib CAPA.
  - **Minor** — Temuan minor: tidak patuh terisolasi, risiko rendah, sistem pada dasarnya efektif. CAPA optional.
  - **Observation** — Observasi: bukan ketidakpatuhan, namun perlu perhatian. CAPA tidak diwajibkan.
  - **OFI** — Opportunity For Improvement: saran perbaikan proaktif. CAPA tidak diwajibkan.
- **Link CAPA** — Setiap finding dapat dihubungkan ke CAPA action melalui tombol "Create CAPA" atau "Link CAPA".
- **Close Finding** — Tutup finding setelah CAPA selesai atau finding ditindaklanjuti. Status: `open` → `closed`.

### 4.4 Evidence Management

- Upload file bukti audit (laporan, checklist, foto, dokumen pendukung) melalui File Service core.
- Collection: `evidence` untuk audit, `finding_evidence` untuk finding-specific evidence.
- Multiple files per audit.
- Download melalui authorized endpoint (permission check).

### 4.5 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record audit.
- Activity log otomatis mencatat: create, update, start, generate report, close, finding CRUD, CAPA link.
- Timeline ditampilkan di halaman detail audit.

### 4.6 Notification

- 4 event notifikasi: `audit.started`, `audit.report_ready`, `audit.closed`, `audit.finding.created`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.7 Dashboard & Reporting

- Dashboard widget: total audit, breakdown by status/type/standard/site, findings summary by classification, trend bulanan.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Kategori Audit

Tiga tipe audit didukung:

| # | Type Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `internal` | **Internal Audit** | Audit internal organisasi terhadap sistem manajemen QHSSE. Dilakukan oleh tim auditor internal. Standar umum: ISO 45001, ISO 9001, ISO 14001, ISO 27001. |
| 2 | `external` | **External Audit** | Audit eksternal oleh pihak ketiga (sertifikasi body, customer, regulator). Termasuk surveillance audit, recertification audit, dan customer audit. |
| 3 | `supplier` | **Supplier Audit** | Audit ke supplier/kontraktor untuk mengevaluasi kepatuhan QHSSE. Dilakukan oleh tim auditor organisasi terhadap supplier. |

### Audit Standards (field `standard`, nullable)

Standar audit yang umum (free-text, contoh):

- `ISO 45001:2018` — Occupational Health & Safety
- `ISO 9001:2015` — Quality Management
- `ISO 14001:2015` — Environmental Management
- `ISO 27001:2022` — Information Security
- `ISO 50001:2018` — Energy Management
- `SMK3` — Sistem Manajemen K3 (Indonesia)
- `Custom` — Standar internal organisasi

### Finding Classifications

| Classification | Code | Color | Description | CAPA Required? |
|---|---|---|---|---|
| **Major** | `major` | `red` | Ketidakpatuhan signifikan, sistem tidak efektif, risiko tinggi | ✅ Wajib |
| **Minor** | `minor` | `orange` | Ketidakpatuhan terisolasi, risiko rendah | ⚠️ Optional |
| **Observation** | `observation` | `yellow` | Bukan ketidakpatuhan, perlu perhatian | ❌ Tidak |
| **OFI** | `ofi` | `blue` | Opportunity For Improvement, saran proaktif | ❌ Tidak |

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor audit di-generate **saat record dibuat** (POST create).
- Format: `AUD-YYYY-NNNN` (contoh: `AUD-2026-0001`).
- Sumber: `NumberingService::generate('audit', ...)`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `audit`
  - `prefix`: `AUD`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `AUD-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Finding Numbering

- Nomor finding di-generate otomatis saat finding dibuat.
- Format: `{audit_number}-F{NN}` (contoh: `AUD-2026-0001-F01`).
- Sequence di-reset per audit (dimulai dari F01 untuk setiap audit baru).
- Nomor finding bersifat unique dalam scope audit tersebut.

### BR-03: Edit Restricted to Planned Status

- Audit record hanya dapat diedit jika status `planned`.
- Setelah audit dimulai (`in_progress`), record tidak dapat diedit kecuali field `summary` (yang dapat diisi saat generate report).
- Findings dapat ditambahkan/diedit saat audit berstatus `in_progress` atau `report_ready`.
- Findings tidak dapat ditambahkan setelah audit `closed`.

### BR-04: Generate Report Requires Summary

- Transition `in_progress` → `report_ready` (action: `generate_report`) memerlukan field `summary` (wajib, text, min:20 karakter).
- Summary disimpan di `audits.summary`.
- Jika summary kosong, transition gagal.

### BR-05: Close Requires All Findings Resolved

- Audit hanya dapat di-close (`report_ready` → `closed`) jika semua findings berstatus `closed`.
- Finding berstatus `open` dengan klasifikasi Major **wajib** memiliki `capa_action_id` (terhubung ke CAPA) sebelum dapat di-close.
- Jika ada finding Major yang belum linked to CAPA, close ditolak dengan pesan error.

### BR-06: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='audit'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `audit.created` | Audit record | new_values: all fields |
| `audit.updated` | Audit record | changed fields only |
| `audit.started` | Audit record | status change |
| `audit.report_generated` | Audit record | status change + summary |
| `audit.closed` | Audit record | status change |
| `audit.deleted` | Audit record | soft delete |
| `audit.finding.created` | AuditFinding | new_values |
| `audit.finding.updated` | AuditFinding | changed fields |
| `audit.finding.closed` | AuditFinding | status change |
| `audit.finding.capa_linked` | AuditFinding | capa_action_id change |
| `audit.file.uploaded` | ManagedFile | new_values |
| `audit.file.downloaded` | ManagedFile | metadata: user, ip |
| `audit.exported` | Audit | metadata: user, filters |

### BR-07: Data Visibility by Scope

Data visibility mengikuti role scope:

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter | Audit yang relevan dengan department-nya (read-only) |
| `department` | Supervisor, Department Head | Audit di department-nya |
| `site` | QHSSE Officer | Audit di assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All audits |

- Scope check dilakukan **server-side** di Controller/Policy.
- Lead auditor dapat melihat audit yang dia pimpin terlepas dari site scope.
- Contractor hanya melihat supplier audit terkait company-nya.

---

## 7. Permission Keys

### 7.1 Audit Management Permissions (6 keys)

| # | Permission Key | Description |
|---|---|---|
| 1 | `audit.management.view` | View audit list and detail. Scope-filtered. |
| 2 | `audit.management.create` | Create new audit record. Generates AUD number. |
| 3 | `audit.management.update` | Update audit record. Only `planned` status. |
| 4 | `audit.management.execute` | Start audit (planned→in_progress), generate report (in_progress→report_ready). |
| 5 | `audit.management.close` | Close audit (report_ready→closed). All findings must be resolved. |
| 6 | `audit.management.export` | Export audit list to CSV. Scope-filtered. |

### 7.2 Audit Findings Permissions (4 keys)

| # | Permission Key | Description |
|---|---|---|
| 7 | `audit.findings.view` | View findings for an audit. |
| 8 | `audit.findings.create` | Create new finding for an audit. |
| 9 | `audit.findings.update` | Update finding. Edit description, classification, recommendation. |
| 10 | `audit.findings.close` | Close finding. Requires CAPA link if classification=Major. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{resource}.{action}` → `audit.management.*` + `audit.findings.*`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.
- Workflow transition menggunakan permission modul: `audit.management.execute` (start, generate_report), `audit.management.close` (close).

---

## 8. Role-Permission Matrix

### 8.1 Audit Management Permissions

| Role | `view` | `create` | `update` | `execute` | `close` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |

### 8.2 Audit Findings Permissions

| Role | `view` | `create` | `update` | `close` |
|---|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ | ❌ |
| Department Head | ✅ | ❌ | ❌ | ❌ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ |
| Contractor | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ |
| Top Management | ✅ | ❌ | ❌ | ❌ |

### Notes

- **QHSSE Officer** dan **QHSSE Manager** adalah roles utama yang dapat membuat dan mengelola audit serta findings.
- **Supervisor** dan **Department Head** dapat melihat audit dan findings di department-nya tetapi tidak dapat create/edit/close.
- **Auditor** role (internal/external auditor) memiliki view-only akses untuk independent verification.
- **Contractor** tidak dapat melihat findings (informasi sensitif internal).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

4 event notifikasi untuk modul Audit Management:

### 9.1 `audit.started`

| Property | Value |
|---|---|
| **Trigger** | User melakukan start (transition `planned` → `in_progress`) |
| **Recipients** | Auditee (department head + supervisor of audited department), lead auditor |
| **Type** | `audit.started` |
| **Title (template)** | `Audit Dimulai: {audit.audit_number}` |
| **Message (template)** | `Audit {audit.audit_number} - {audit.title} telah dimulai oleh {actor.name}. Lead Auditor: {lead_auditor.name}. Mohon siapkan dokumen dan area yang akan diaudit.` |
| **Action URL** | `/audits/{audit.id}` |
| **Module/Reference** | `module_name='audit'`, `reference_id={audit.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `audit.report_ready`

| Property | Value |
|---|---|
| **Trigger** | User melakukan generate report (transition `in_progress` → `report_ready`) |
| **Recipients** | QHSSE Manager, lead auditor, department head of audited department |
| **Type** | `audit.report_ready` |
| **Title (template)** | `Laporan Audit Siap: {audit.audit_number}` |
| **Message (template)** | `Laporan audit {audit.audit_number} - {audit.title} telah dibuat oleh {actor.name}. Total temuan: {findings_count} ({major_count} Major, {minor_count} Minor, {obs_count} Observation, {ofi_count} OFI). Mohon review.` |
| **Action URL** | `/audits/{audit.id}` |
| **Module/Reference** | `module_name='audit'`, `reference_id={audit.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.3 `audit.closed`

| Property | Value |
|---|---|
| **Trigger** | User melakukan close (transition `report_ready` → `closed`) |
| **Recipients** | Lead auditor, department head, QHSSE Manager, all users linked to findings with CAPA |
| **Type** | `audit.closed` |
| **Title (template)** | `Audit Ditutup: {audit.audit_number}` |
| **Message (template)** | `Audit {audit.audit_number} - {audit.title} telah ditutup oleh {actor.name}. Semua temuan telah ditindaklanjuti.` |
| **Action URL** | `/audits/{audit.id}` |
| **Module/Reference** | `module_name='audit'`, `reference_id={audit.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.4 `audit.finding.created`

| Property | Value |
|---|---|
| **Trigger** | User membuat finding baru pada audit |
| **Recipients** | Lead auditor, QHSSE Manager, department head of audited area |
| **Type** | `audit.finding.created` |
| **Title (template)** | `Temuan Baru: {finding.finding_number}` |
| **Message (template)** | `Temuan {finding.finding_number} ({finding.classification}) telah ditambahkan ke audit {audit.audit_number} oleh {actor.name}. Deskripsi: {finding.description (truncated)}.` |
| **Action URL** | `/audits/{audit.id}?tab=findings` |
| **Module/Reference** | `module_name='audit'`, `reference_id={audit.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured, only for Major findings) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit.
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Email hanya dikirim untuk Major findings (event `audit.finding.created`) untuk mengurangi noise.
- Recipient resolution: query users with target role + matching scope (site/department).

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `audit` |
| **reference_id** | `audits.id` (for audit-level files) or `audit_findings.id` (for finding-level files) |
| **collection** | `evidence` (audit-level), `finding_evidence` (finding-level) |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `audit/{audit_id}/evidence/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx` |
| **Max file size** | 25 MB per file |
| **Max files per audit** | 30 |

### 10.3 Access Rules

- **Upload**: User must have `audit.management.update` (audit-level) or `audit.findings.update` (finding-level).
- **Download**: User must have `audit.management.view` and be within data scope.
- **Delete**: User must have `audit.management.update` AND audit status must NOT be `closed`.

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Audit** | Count all audits in scope | Number + icon |
| **Planned Audit** | Count where status = `planned` | Number, blue |
| **In Progress** | Count where status = `in_progress` | Number, yellow |
| **Report Ready** | Count where status = `report_ready` | Number, orange |
| **Closed Audit** | Count where status = `closed` | Number, green |
| **Total Findings** | Count all findings via audits in scope | Number |
| **Major Findings (Open)** | Count findings classification=major, status=open | Number, red badge |
| **Findings Linked to CAPA** | Count findings where capa_action_id IS NOT NULL | Number, green |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Audit Trend** | Line chart | Audit count by month (last 12 months) |
| **Audit by Type** | Donut | Internal / External / Supplier |
| **Findings by Classification** | Stacked bar | Major / Minor / Observation / OFI |
| **Findings by Status** | Donut | Open / Closed |
| **Audit by Site** | Horizontal bar | Count by site (top 10) |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Recent Audits** | Number, Title, Type, Standard, Status, Start Date | Last 10, scoped |
| **Open Major Findings** | Finding Number, Audit Number, Description, Area, Created At | classification=major, status=open |
| **Aging Audits** | Number, Title, Status, Start Date, Days Since Started | Status in (planned, in_progress), sorted oldest |

### 11.4 Filters

- Date range filter (default: current year)
- Site filter
- Type filter (internal/external/supplier)
- Standard filter

---

## 12. Export Spec

### 12.1 CSV Export

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `audits_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `audit.management.export` |
| **Scope** | Follows user's data scope |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor Audit` | `audit.audit_number` | AUD-YYYY-NNNN |
| 2 | `Judul` | `audit.title` | |
| 3 | `Tipe` | `audit.type` | internal/external/supplier |
| 4 | `Standar` | `audit.standard` | Nullable |
| 5 | `Site` | `site.name` | Via `site_id` |
| 6 | `Department` | `department.name` | Via `department_id`, nullable |
| 7 | `Lead Auditor` | `lead_auditor.name` | Via `lead_auditor_id` → users |
| 8 | `Tanggal Mulai` | `audit.start_date` | YYYY-MM-DD |
| 9 | `Tanggal Selesai` | `audit.end_date` | Nullable |
| 10 | `Status` | `audit.status` | planned/in_progress/report_ready/closed |
| 11 | `Total Findings` | Count of findings | Integer |
| 12 | `Major Findings` | Count findings classification=major | Integer |
| 13 | `Minor Findings` | Count findings classification=minor | Integer |
| 14 | `Observation Findings` | Count findings classification=observation | Integer |
| 15 | `OFI Findings` | Count findings classification=ofi | Integer |
| 16 | `Findings Linked to CAPA` | Count findings where capa_action_id IS NOT NULL | Integer |
| 17 | `Summary` | `audit.summary` | Truncated to 500 chars |
| 18 | `Created At` | `audit.created_at` | YYYY-MM-DD HH:MM:SS |
| 19 | `Closed At` | From workflow_histories | Nullable |

### 12.3 Export Rules

- Export event dicatat di audit trail (`audit.exported`).
- Export mengikuti permission dan scope user.
- Maksimal 10.000 record per export.

---

## 13. Acceptance Criteria

1. **AC-01: Create with auto-numbering** — User dengan permission `audit.management.create` dapat membuat audit record. Nomor `AUD-YYYY-NNNN` di-generate otomatis. Nomor bersifat unique.

2. **AC-02: Permission enforcement** — User tanpa permission `audit.management.view` tidak dapat mengakses halaman list atau detail. User tanpa `audit.findings.create` tidak dapat menambah finding. Server-side check memblokir akses.

3. **AC-03: Workflow transitions correct** — Workflow: planned→in_progress (start), in_progress→report_ready (generate_report, requires summary), report_ready→closed (close, requires all findings resolved). Transition tidak valid ditolak.

4. **AC-04: Generate report requires summary** — Saat generate report, field `summary` wajib diisi (min 20 karakter). Jika kosong, transition gagal.

5. **AC-05: Close requires all findings resolved** — Audit hanya dapat di-close jika semua findings berstatus `closed`. Finding Major wajib memiliki `capa_action_id` sebelum audit dapat di-close.

6. **AC-06: Finding CRUD** — User dengan permission `audit.findings.create` dapat membuat finding. Finding number auto-generated (`AUD-YYYY-NNNN-FNN`). Klasifikasi: major, minor, observation, ofi.

7. **AC-07: Create CAPA from finding** — Tombol "Create CAPA" pada finding membuat CAPA record baru di modul `04-capa-action-tracking` dengan `source_module='audit'`, `source_reference_id=audit_finding.id`. `capa_action_id` di-update pada finding.

8. **AC-08: Notifications sent correctly** — Notifikasi terkirim untuk 4 event: `audit.started`, `audit.report_ready`, `audit.closed`, `audit.finding.created`.

9. **AC-09: List with search/filter/pagination/export** — Halaman list mendukung search, filter, pagination, dan export CSV.

10. **AC-10: Audit trail complete** — Audit trail tercatat untuk semua event kritikal: create, update, start, generate report, close, finding CRUD, CAPA link, file upload/download, export.

---

## 14. Open Questions

| # | Question | Default Answer |
|---|---|---|
| 1 | Apakah audit checklist perlu didukung di Phase 6? | **No untuk Phase 6** — checklist dapat ditambahkan di Phase 7+ sebagai sub-feature. Phase 6 fokus pada audit scheduling, findings, dan CAPA link. |
| 2 | Apakah audit team (multiple auditors) perlu didukung? | **No untuk Phase 6** — hanya `lead_auditor_id` (single auditor). Audit team dapat ditambahkan dengan pivot table `audit_team` di future phase. |
| 3 | Apakah findings memerlukan due date? | **Yes** — field `due_date` pada finding (nullable). Jika finding Major, due date wajib diisi. |
| 4 | Apakah audit dapat di-reopen setelah closed? | **No** — closed adalah terminal status. Reopen dapat ditambahkan di future jika diperlukan. |
| 5 | Apakah PDF audit report perlu di-generate? | **No untuk Phase 6** — CSV export list sudah cukup. PDF per audit dapat ditambahkan di Phase 7+ dengan DOMPDF. |
| 6 | Apakah annual audit plan perlu modul terpisah? | **No** — annual audit plan dapat direpresentasikan dengan multiple audit records dengan status `planned` dan date range di masa depan. |
| 7 | Apakah finding dapat di-link ke multiple CAPA? | **No** — 1 finding : 1 CAPA (via `capa_action_id`). Jika diperlukan multiple CAPA, dapat diubah di future. |
| 8 | Apakah email notifikasi wajib untuk semua event? | **No** — in-app wajib untuk semua. Email hanya untuk Major findings dan audit closed. |

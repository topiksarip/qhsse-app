# Module Spec — CAPA / Corrective & Preventive Action Tracking

> **Module ID:** `04-capa-action-tracking`
> **Module Code (numbering):** `capa`
> **Number Prefix:** `ACT`
> **Workflow Code:** `CAPA_WORKFLOW`
> **Phase:** Phase 3
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul CAPA (Corrective & Preventive Action) adalah **sistem pelacakan tindakan terpusat** dalam platform QHSSE. Modul ini menerima action items dari berbagai modul sumber — Incident, Inspection, Audit — serta dapat dibuat secara manual. Setiap action memiliki siklus hidup lengkap: dibuka → dikerjakan → diverifikasi → ditutup, dengan jalur reject dan restart.

Tujuan utama:

- Menyediakan **sistem pelacakan tindakan sentral** yang menerima action dari Incident, Inspection, Audit, dan input manual.
- Memastikan setiap action memiliki **nomor unik** (`ACT-YYYY-NNNN`) yang di-generate otomatis pada saat create.
- Menyediakan **workflow status** yang jelas: Open → In Progress → Waiting Verification → Closed (dengan jalur Reject + Restart).
- Mengelola **assignment** ke PIC (Person In Charge) dengan due date dan prioritas.
- Menyediakan **verifikasi** oleh QHSSE sebelum action dapat di-close.
- Menghitung dan menampilkan **action overdue** (due_date < now() AND status NOT IN closed/rejected) dengan highlight merah di list.
- Mengelola **evidence attachments** melalui File Service core dengan collection `evidence`.
- Mengirim **notifikasi** ke PIC saat di-assign, submit verification, verify, reject, dan close.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal.
- Menyediakan **dashboard metrics** dan **export CSV** untuk analisis dan pelaporan manajemen.

---

## 2. Dependency

### Core Foundation (Phase 0 — complete)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 8 permission keys `capa.actions.*` |
| **NumberingService** | Generate `ACT-YYYY-NNNN` on create |
| **WorkflowService** | Status transitions per `CAPA_WORKFLOW` definition |
| **FileService** | Upload/download evidence files via `managed_files` table |
| **NotificationService** | In-app + email notifications via `core_notifications` table |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='capa'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **ListQuery** | Paginated list with search, filter, sort |
| **CsvExporter** | CSV export via `capa.actions.export` permission |
| **MasterData** | Sites, Departments, Severities, Priorities, Users |

### Cross-Module Dependencies

| Module | Relationship |
|---|---|
| `02-incident-reporting` | Incident dapat membuat CAPA action via `source_module='incident'`, `source_reference_id=incident.id` |
| `05-inspection-management` | Inspection dapat membuat CAPA action via `source_module='inspection'`, `source_reference_id=inspection.id` |
| `06-audit-management` | Audit dapat membuat CAPA action via `source_module='audit'`, `source_reference_id=audit.id` |
| `03-investigation-rca` | Investigation recommendation dapat men-trigger CAPA creation |
| `13-risk-management` | Risk treatment plan dapat men-trigger CAPA creation |
| `14-legal-compliance` | Compliance gap dapat men-trigger CAPA creation |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

| # | Role | Deskripsi Peran dalam CAPA |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Verify, close, reject CAPA. Scope: all sites. |
| 4 | **QHSSE Officer** | Verify, close, reject CAPA. Create, assign, update. Scope: assigned site(s). |
| 5 | **Supervisor** | Create, update, assign CAPA. Submit verification. Scope: department. |
| 6 | **Department Head** | View CAPA in department. Scope: department. |
| 7 | **Employee/Reporter** | View own assigned CAPA. Update progress. Scope: own (assigned_to). |
| 8 | **Contractor** | View own assigned CAPA (if assigned). Scope: company. |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 CAPA CRUD

- **Create** — Form pembuatan action. Nomor `ACT-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal: `open`.
- **List** — Halaman list dengan search (nomor, judul), filter (site, department, status, source_module, priority, overdue), pagination (default 15 per page), highlight baris overdue dengan warna merah, dan tombol Export CSV.
- **Detail** — Halaman detail menampilkan: nomor, judul, deskripsi, source module/link, type (corrective/preventive), site, department, PIC, assigned_by, due_date, severity, priority, status, verification panel, evidence files, comments, activity log, audit trail, workflow timeline.
- **Update** — Edit record. Hanya bisa edit jika status `open` atau `in_progress`. Setelah submit verification, record tidak bisa di-edit kecuali di-reject (restart ke in_progress).
- **Delete** — Soft delete. Hanya Super Admin / Admin. Tidak bisa delete record yang sudah `closed`.

### 4.2 Workflow Actions

- **Start** — Mulai pengerjaan action. Status: `open` → `in_progress`. Set `assigned_at` jika belum di-set.
- **Submit Verification** — PIC men-submit action untuk diverifikasi. Status: `in_progress` → `waiting_verification`. Wajib ada minimal 1 evidence file.
- **Verify & Close** — QHSSE verify dan tutup action. Wajib isi `verification_note`. Status: `waiting_verification` → `closed`. Set `verified_by`, `verified_at`, `closed_at`.
- **Reject** — QHSSE menolak hasil kerja. Wajib isi reason. Status: `waiting_verification` → `rejected`. Notifikasi ke PIC.
- **Restart** — PIC melanjutkan setelah reject. Status: `rejected` → `in_progress`. PIC dapat update action dan re-submit.

### 4.3 Cross-Module Source Linking

- Setiap CAPA action memiliki `source_module` dan `source_reference_id` untuk linking ke modul asal.
- Source module options: `incident`, `inspection`, `audit`, `manual`.
- Jika `source_module='manual'`, `source_reference_id` = NULL.
- UI menampilkan link ke record sumber (contoh: "Dari Insiden: INC-2026-0001").
- Modul sumber dapat membuat CAPA action secara programatik via `CapaActionService::createFromSource()`.

### 4.4 Evidence Management

- Upload file bukti (foto, dokumen, video) melalui File Service core.
- Collection: `evidence`.
- Multiple files per CAPA action.
- Minimal 1 evidence file wajib ada sebelum submit verification.
- Download melalui authorized endpoint (permission check).
- Tidak bisa hapus file setelah status `closed`.

### 4.5 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record.
- Support internal comments (hanya QHSSE team) dan public comments (visible to PIC).
- Activity log otomatis mencatat: create, start, submit, verify, reject, restart, close, field changes, file upload/download.
- Timeline ditampilkan di halaman detail.

### 4.6 Notification

- 5 event notifikasi: `capa.assigned`, `capa.submitted_verification`, `capa.verified_closed`, `capa.rejected`, `capa.overdue_reminder`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.7 Dashboard & Reporting

- Dashboard widget: total CAPA, open count, overdue count, closed count, breakdown by source/priority/severity/site.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

### 4.8 Overdue Tracking

- Sistem menghitung overdue secara real-time: `due_date < now() AND status NOT IN ('closed', 'rejected')`.
- Baris overdue di Index page di-highlight dengan warna merah (`bg-red-50 dark:bg-red-900/20`).
- Overdue badge ditampilkan di list dan detail page.
- Daily reminder notification untuk action yang overdue.

---

## 5. Source Module Categories

Empat sumber action, stored di kolom `source_module`:

| # | Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `incident` | **Dari Insiden** | Action berasal dari modul Incident Reporting. `source_reference_id` = `incidents.id`. |
| 2 | `inspection` | **Dari Inspection** | Action berasal dari modul Inspection Management. `source_reference_id` = `inspections.id`. |
| 3 | `audit` | **Dari Audit** | Action berasal dari modul Audit Management. `source_reference_id` = `audit_findings.id`. |
| 4 | `manual` | **Manual** | Action dibuat secara manual, tidak terhubung ke modul lain. `source_reference_id` = NULL. |

### Source Type

| Code | Nama | Deskripsi |
|---|---|---|
| `corrective` | **Corrective Action** | Tindakan korektif untuk masalah yang sudah terjadi. |
| `preventive` | **Preventive Action** | Tindakan pencegahan untuk mencegah terulangnya masalah. |

### Severity Levels (already seeded)

| Code | Name | Level | Color |
|---|---|---|---|
| `LOW` | Low | 1 | green |
| `MEDIUM` | Medium | 2 | yellow |
| `HIGH` | High | 3 | orange |
| `CRITICAL` | Critical | 4 | red |

### Priority Levels (already seeded)

| Code | Name | SLA Days | Color |
|---|---|---|---|
| `LOW` | Low | 30 | green |
| `MEDIUM` | Medium | 14 | yellow |
| `HIGH` | High | 7 | orange |
| `URGENT` | Urgent | 1 | red |

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor CAPA action di-generate **saat record dibuat** (POST create).
- Format: `ACT-YYYY-NNNN` (contoh: `ACT-2026-0001`).
- Sumber: `NumberingService::generate('capa', $actor, ...)`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `capa`
  - `prefix`: `ACT`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `ACT-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Source Module Validation

- `source_module` wajib diisi, nilainya salah satu dari: `incident`, `inspection`, `audit`, `manual`.
- Jika `source_module` != `manual`, maka `source_reference_id` wajib diisi (nullable=false for non-manual).
- Jika `source_module` = `manual`, maka `source_reference_id` harus NULL.
- `source_type` nullable, jika diisi harus salah satu dari: `corrective`, `preventive`.

### BR-03: Assignment on Create or Update

- `assigned_to` wajib diisi pada saat create (PIC harus ditentukan).
- `assigned_by` auto-filled dari authenticated user.
- `assigned_at` di-set saat action pertama kali di-assign atau saat transition `open → in_progress`.
- Jika `assigned_to` diubah, `assigned_at` di-reset ke now().

### BR-04: Start (Open → In Progress)

- Transition `open` → `in_progress` memerlukan permission `capa.actions.update`.
- Tidak memerlukan reason.
- Set `assigned_at` = now() jika belum di-set.
- Notifikasi dikirim ke PIC.

### BR-05: Submit Verification Requires Evidence

- Transition `in_progress` → `waiting_verification` memerlukan permission `capa.actions.submit`.
- **Wajib ada minimal 1 evidence file** terlampir (checked via `managed_files` where `module_name='capa'` AND `reference_id=$action->id` AND `collection='evidence'` AND `deleted_at IS NULL`).
- Jika tidak ada evidence, submit ditolak dengan error: "Wajib melampirkan minimal 1 bukti sebelum submit verifikasi."

### BR-06: Verify & Close Requires Verification Note

- Transition `waiting_verification` → `closed` memerlukan permission `capa.actions.verify` AND `capa.actions.close`.
- Wajib isi `verification_note` (min: 10 karakter).
- Set `verified_by` = auth user, `verified_at` = now(), `closed_at` = now().
- Notifikasi dikirim ke PIC dan assigned_by.

### BR-07: Reject Requires Reason

- Transition `waiting_verification` → `rejected` memerlukan permission `capa.actions.reject`.
- Wajib isi reason (min: 10 karakter).
- Reason disimpan di `workflow_histories.reason`.
- Notifikasi dikirim ke PIC.

### BR-08: Restart (Rejected → In Progress)

- Transition `rejected` → `in_progress` memerlukan permission `capa.actions.update`.
- Tidak memerlukan reason.
- PIC dapat mengupdate action dan men-submit ulang untuk verifikasi.
- Notifikasi dikirim ke QHSSE team.

### BR-09: Overdue Calculation

- Sebuah action dianggap **overdue** jika: `due_date IS NOT NULL AND due_date < now() AND status NOT IN ('closed', 'rejected')`.
- Overdue dihitung real-time di query level (bukan stored column).
- Index page menampilkan overdue rows dengan highlight merah.
- Detail page menampilkan badge "Overdue" jika applicable.
- Daily job mengirim reminder notification untuk action overdue.

### BR-10: Edit Lock by Status

- Record dapat di-edit hanya jika status dalam: `open`, `in_progress`, `rejected`.
- Setelah `waiting_verification` atau `closed`, record tidak dapat di-edit.
- File evidence tidak dapat dihapus setelah `closed`.

### BR-11: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='capa'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `capa.created` | CapaAction | new_values: all fields |
| `capa.updated` | CapaAction | changed fields only |
| `capa.started` | CapaAction | status change |
| `capa.submitted_verification` | CapaAction | status change |
| `capa.verified_closed` | CapaAction | status change + verification_note |
| `capa.rejected` | CapaAction | status change + reason |
| `capa.restarted` | CapaAction | status change |
| `capa.deleted` | CapaAction | soft delete |
| `capa.file.uploaded` | ManagedFile | new_values |
| `capa.file.deleted` | ManagedFile | soft delete |
| `capa.file.downloaded` | ManagedFile | metadata: user, ip |

### BR-12: Data Visibility by Scope

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter, Contractor | Only CAPA where `assigned_to` = their user id |
| `department` | Supervisor, Department Head | CAPA in their department |
| `site` | QHSSE Officer | CAPA in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All CAPA |

- Scope check dilakukan **server-side** di Controller/Policy.
- Query scope: filter berdasarkan `assigned_to` (own), `department_id` (department), `site_id` (site), atau no filter (all).

---

## 7. Permission Keys

8 permission keys untuk modul CAPA:

| # | Permission Key | Description |
|---|---|---|
| 1 | `capa.actions.view` | View CAPA list and detail. Scope-filtered. |
| 2 | `capa.actions.create` | Create new CAPA action. Generates ACT number. |
| 3 | `capa.actions.update` | Update CAPA action. Only open/in_progress/rejected status. |
| 4 | `capa.actions.submit` | Submit CAPA for verification (in_progress → waiting_verification). Requires evidence. |
| 5 | `capa.actions.verify` | Verify CAPA action (waiting_verification → closed). QHSSE roles only. |
| 6 | `capa.actions.close` | Close CAPA action (paired with verify). QHSSE roles only. |
| 7 | `capa.actions.reject` | Reject CAPA verification (waiting_verification → rejected). QHSSE roles only. |
| 8 | `capa.actions.export` | Export CAPA list to CSV. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}` → `capa.actions.*`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.
- Workflow transition menggunakan permission core: `core.workflow.transition` (module-specific permission checked in controller).

---

## 8. Role-Permission Matrix

| Role | `view` | `create` | `update` | `submit` | `verify` | `close` | `reject` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Employee/Reporter | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

### Notes

- **QHSSE Manager & Officer** dapat verify + close + reject — ini adalah otoritas verifikasi tindakan.
- **Supervisor** dapat create + assign + update + submit verification, tetapi tidak dapat verify/close/reject.
- **Employee/Reporter** dapat view own (assigned_to them) + update + submit verification. Tidak dapat create, verify, close, reject, atau export.
- **Auditor** dan **Top Management** hanya view + export (read-only).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

5 event notifikasi untuk modul CAPA:

### 9.1 `capa.assigned`

| Property | Value |
|---|---|
| **Trigger** | CAPA action di-assign ke PIC (on create or reassign) |
| **Recipients** | User dengan id = `assigned_to` |
| **Type** | `capa.assigned` |
| **Title (template)** | `Tindakan Baru Ditugaskan: {action.action_number}` |
| **Message (template)** | `Anda ditugaskan untuk tindakan {action.action_number} - {action.title}. Batas waktu: {action.due_date}. Mohon segera tindak lanjuti.` |
| **Action URL** | `/capa-actions/{action.id}` |
| **Module/Reference** | `module_name='capa'`, `reference_id={action.id}` |
| **Channel** | In-app + Email (if configured) |

### 9.2 `capa.submitted_verification`

| Property | Value |
|---|---|
| **Trigger** | PIC submit verification (transition `in_progress` → `waiting_verification`) |
| **Recipients** | All users with role QHSSE Officer and QHSSE Manager in same site scope |
| **Type** | `capa.submitted_verification` |
| **Title (template)** | `Tindakan Menunggu Verifikasi: {action.action_number}` |
| **Message (template)** | `{pic.name} telah men-submit tindakan {action.action_number} - {action.title} untuk verifikasi. Mohon lakukan review dan verifikasi.` |
| **Action URL** | `/capa-actions/{action.id}` |
| **Module/Reference** | `module_name='capa'`, `reference_id={action.id}` |
| **Channel** | In-app + Email (if configured) |

### 9.3 `capa.verified_closed`

| Property | Value |
|---|---|
| **Trigger** | QHSSE verify & close (transition `waiting_verification` → `closed`) |
| **Recipients** | PIC (`assigned_to`), `assigned_by` |
| **Type** | `capa.verified_closed` |
| **Title (template)** | `Tindakan Diverifikasi & Ditutup: {action.action_number}` |
| **Message (template)** | `Tindakan {action.action_number} - {action.title} telah diverifikasi dan ditutup oleh {verifier.name}. Catatan verifikasi: {verification_note}.` |
| **Action URL** | `/capa-actions/{action.id}` |
| **Module/Reference** | `module_name='capa'`, `reference_id={action.id}` |
| **Channel** | In-app + Email (if configured) |

### 9.4 `capa.rejected`

| Property | Value |
|---|---|
| **Trigger** | QHSSE reject (transition `waiting_verification` → `rejected`) |
| **Recipients** | PIC (`assigned_to`) |
| **Type** | `capa.rejected` |
| **Title (template)** | `Tindakan Ditolak: {action.action_number}` |
| **Message (template)** | `Hasil tindakan {action.action_number} - {action.title} ditolak oleh {rejecter.name}. Alasan: {reject_reason}. Silakan perbaiki dan kirim ulang.` |
| **Action URL** | `/capa-actions/{action.id}` |
| **Module/Reference** | `module_name='capa'`, `reference_id={action.id}` |
| **Channel** | In-app + Email (if configured) |

### 9.5 `capa.overdue_reminder`

| Property | Value |
|---|---|
| **Trigger** | Daily scheduled job detects overdue action (`due_date < now() AND status NOT IN ('closed','rejected')`) |
| **Recipients** | PIC (`assigned_to`), Supervisor of department, QHSSE Officer in site |
| **Type** | `capa.overdue_reminder` |
| **Title (template)** | `Tindakan Terlambat: {action.action_number}` |
| **Message (template)** | `Tindakan {action.action_number} - {action.title} telah melewati batas waktu ({action.due_date}). Mohon segera tindak lanjuti.` |
| **Action URL** | `/capa-actions/{action.id}` |
| **Module/Reference** | `module_name='capa'`, `reference_id={action.id}` |
| **Channel** | In-app + Email (if configured) |

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `capa` |
| **reference_id** | `capa_actions.id` |
| **collection** | `evidence` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `capa/{action_id}/evidence/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `mp4`, `mov`, `avi` |
| **Allowed MIME types** | Corresponding to extensions above |
| **Max file size** | 25 MB per file |
| **Max files per action** | 20 |
| **Min files for submit** | 1 (required before submit verification) |

### 10.3 Access Rules

- **Upload**: User must have `capa.actions.update` AND action status must be in `open`, `in_progress`, `rejected`.
- **Download**: User must have `capa.actions.view` and be within data scope.
- **Delete**: User must have `capa.actions.update` AND action status NOT `closed`. Once `closed`, evidence files cannot be deleted except by Super Admin / Admin.

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total CAPA** | Count all actions in scope | Number + icon |
| **Open Actions** | Count where status NOT IN (`closed`, `rejected`) | Number, blue |
| **Overdue Actions** | Count where `due_date < now() AND status NOT IN ('closed','rejected')` | Number, **red badge** |
| **Waiting Verification** | Count where status = `waiting_verification` | Number, yellow |
| **Closed Actions** | Count where status = `closed` | Number, green |
| **This Month** | Count created in current month | Number + trend arrow |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Trend** | Line chart | Action count by month (last 12 months), split by status |
| **By Source Module** | Donut | Count by source_module (incident, inspection, audit, manual) |
| **By Priority** | Bar chart | Count by priority (Low, Medium, High, Urgent) |
| **By Site** | Horizontal bar | Count by site (top 10) |
| **By Status** | Donut | Count by workflow status |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Overdue Actions** | Number, Title, PIC, Due Date, Days Overdue | Overdue only, sorted by oldest due_date |
| **Waiting Verification** | Number, Title, PIC, Submitted Date | Status = waiting_verification |
| **Recent Actions** | Number, Title, Source, Priority, Status, Created At | Last 10, scoped |

---

## 12. Export Spec

### 12.1 CSV Export

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `capa_actions_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `capa.actions.export` |
| **Scope** | Follows user's data scope |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `action_number` | ACT-YYYY-NNNN |
| 2 | `Judul` | `title` | |
| 3 | `Deskripsi` | `description` | Truncated to 500 chars |
| 4 | `Sumber` | `source_module` | incident/inspection/audit/manual |
| 5 | `Tipe` | `source_type` | corrective/preventive |
| 6 | `Severity` | `severity.name` | Via `severity_id` |
| 7 | `Priority` | `priority.name` | Via `priority_id` |
| 8 | `Site` | `site.name` | Via `site_id` |
| 9 | `Departemen` | `department.name` | Via `department_id`, nullable |
| 10 | `PIC` | `assigned_to_user.name` | Via `assigned_to` |
| 11 | `Status` | `status` | |
| 12 | `Due Date` | `due_date` | Format: YYYY-MM-DD |
| 13 | `Overdue` | computed | "Ya" if overdue, "" otherwise |
| 14 | `Verified By` | `verified_by_user.name` | Via `verified_by`, nullable |
| 15 | `Closed At` | `closed_at` | Format: YYYY-MM-DD HH:mm |

---

## 13. Acceptance Criteria

1. User dengan permission `capa.actions.create` dapat membuat CAPA action dengan nomor auto-generated.
2. User tanpa permission ditolak (403) pada route yang relevan.
3. Submit verification memvalidasi minimal 1 evidence file terlampir.
4. Workflow status berjalan sesuai rule: open → in_progress → waiting_verification → closed/rejected.
5. Action dengan due_date terlewat dan status aktif ditandai overdue (highlight merah di list).
6. Cross-module link berfungsi: source_module + source_reference_id menampilkan link ke record sumber.
7. Attachment bisa upload/download sesuai permission dan status.
8. Comment dan activity log tampil di halaman detail.
9. Audit trail tercatat untuk semua perubahan kritikal.
10. Notification terkirim ke penerima tepat pada setiap event.
11. List dapat search/filter/pagination dengan highlight overdue.
12. Export menghasilkan data sesuai filter dan permission.
13. Verify & close hanya bisa dilakukan oleh QHSSE roles.
14. Reject wajib reason, restart mengembalikan ke in_progress.

---

## 14. Open Questions

- Apakah perlu fitur "extension request" untuk due date? (Defer to future phase)
- Apakah perlu SLA auto-calculation dari priority? (Priority SLA days already seeded, bisa digunakan untuk default due_date)
- Apakah modul sumber (Incident, Inspection, Audit) perlu auto-create CAPA saat workflow transition? (Implementasi tergantung modul masing-masing)
- Apakah perlu escalation matrix untuk action yang overdue lebih dari X hari? (Defer)

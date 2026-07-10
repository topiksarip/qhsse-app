# Module Spec — Security Management

> **Module ID:** `11-security-management`  
> **Module Code (numbering):** `security`  
> **Number Prefix:** `SEC`  
> **Phase:** Phase 3  
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul Security Management menyediakan sistem pengelolaan keamanan terintegrasi yang mencakup tiga area utama: pelaporan insiden keamanan (security incidents), pengelolaan log pengunjung (visitor logs), dan manajemen patroli keamanan (patrol checklists). Modul ini memastikan setiap aspek keamanan fisik dan akses terdokumentasi, terlacak, dan dapat diaudit.

Tujuan utama:

- Memungkinkan pencatatan dan pengelolaan **insiden keamanan** (akses tidak sah, pencurian, vandalisme, penyusupan, aktivitas mencurigakan) dengan nomor unik `SEC-YYYY-NNNN` yang di-generate otomatis pada saat create.
- Menyediakan **log pengunjung** untuk check-in/check-out tamu, vendor, dan kontraktor dengan pencatatan host, tujuan, identitas, dan kendaraan.
- Mengelola **patroli keamanan** terjadwal dengan checklist per checkpoint, status hasil (ok/issue/na), dan catatan temuan.
- Mengirim **notifikasi** ke security officer dan QHSSE team saat insiden keamanan dilaporkan, tamu check-in, atau patroli menemukan masalah.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal via core services.
- Menyediakan **dashboard metrics** dan **export CSV** untuk analisis dan pelaporan.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 11 permission keys `security.*` |
| **NumberingService** | Generate `SEC-YYYY-NNNN` on security incident create, `SPL-YYYY-NNNN` on patrol create |
| **FileService** | Upload/download evidence files via `managed_files` table |
| **NotificationService** | In-app + email notifications |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='security'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **CsvExporter** | CSV export for incidents, visitors, patrols |
| **ListQuery** | Search, filter, pagination for all 3 resources |
| **MasterData** | Sites, Areas, Users, Employees, Severities |

### Cross-Module

| Module | Relationship |
|---|---|
| `02-incident-reporting` | Security incidents follow a similar pattern to general incidents but are security-focused. Shared severity levels. |
| `07-inspection-management` | Patrol checklists follow the inspection pattern but for security patrols. |
| `16-contractor-management` | Visitor logs reference contractor company employees. |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

| # | Role | Deskripsi Peran dalam Security Management |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi dan master data. |
| 3 | **QHSSE Manager** | Review, close security incidents. Scope: all sites. Export semua. |
| 4 | **QHSSE Officer** | Create, update, close security incidents. Manage visitor logs. Execute patrols. Scope: assigned site(s). |
| 5 | **Security Officer** | Create security incidents, manage visitor logs, execute patrols. Scope: assigned site(s). |
| 6 | **Supervisor** | View security data in department scope. Create visitor log entries. |
| 7 | **Employee / Reporter** | View security incidents own scope. Cannot manage patrols. |
| 8 | **Contractor** | View visitor logs for own company. Check-in via host. |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Security Incident Management

- **Create** — Form pembuatan laporan insiden keamanan. Nomor `SEC-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat create. Status awal: `reported`.
- **List** — Halaman list dengan search (nomor, judul), filter (site, type, severity, status, date range), pagination (default 15 per page), dan tombol Export CSV.
- **Detail** — Halaman detail menampilkan: nomor, judul, deskripsi, tipe, severity, lokasi (site/area), pelapor, waktu kejadian, status, resolution, workflow timeline, attachments, comments, activity log.
- **Update** — Edit record. Hanya bisa edit jika status `reported` atau `under_investigation`.
- **Close** — Tutup insiden dengan resolution text. Status: `under_investigation` → `closed`. Set resolved_at timestamp.
- **Export CSV** — Export filtered list to CSV.

### 4.2 Visitor Log Management

- **Create (Check-In)** — Form check-in pengunjung: nama, perusahaan, tujuan, host, site, jenis ID, nomor ID, plat kendaraan. check_in_at otomatis diisi.
- **List** — Halaman list dengan search (nama, perusahaan, host), filter (site, date range, status check-in/check-out), pagination, dan export CSV.
- **Check-Out** — Tombol check-out pada record pengunjung yang masih berada di dalam. Mengisi check_out_at timestamp.
- **Update** — Edit catatan pengunjung (hanya sebelum check-out).

### 4.3 Patrol Checklist Management

- **Create** — Form pembuatan jadwal patroli. Nomor `SPL-YYYY-NNNN` di-generate otomatis. Status awal: `scheduled`.
- **List** — Halaman list dengan search (nomor, rute, officer), filter (site, status, date range), pagination, dan export CSV.
- **Detail** — Halaman detail menampilkan: nomor, rute, officer, site, jadwal, waktu eksekusi, status, checklist results per checkpoint, notes.
- **Execute** — Eksekusi patroli: officer mengisi hasil setiap checkpoint (ok/issue/na) + remark. Status: `scheduled` → `in_progress`. Set executed_at timestamp.
- **Complete** — Selesaikan patroli setelah semua checkpoint diisi. Status: `in_progress` → `completed`.
- **Export CSV** — Export filtered list to CSV.

### 4.4 Evidence Management

- Upload file bukti (foto, video, dokumen) melalui File Service core.
- Collection: `evidence`.
- Multiple files per security incident.
- Download melalui authorized endpoint (permission check).
- Tidak bisa hapus file setelah status `closed`.

### 4.5 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record.
- Activity log otomatis mencatat: create, update, close, check-in, check-out, patrol execute, patrol complete, field changes.
- Timeline ditampilkan di halaman detail security incident.

### 4.6 Notification

- 6 event notifikasi: `security.incident.reported`, `security.incident.closed`, `security.visitor.checked_in`, `security.visitor.checked_out`, `security.patrol.executed`, `security.patrol.issue_found`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.7 Dashboard & Reporting

- Dashboard widget: total security incidents, open incidents, closed incidents, critical incidents, visitor count today, visitors on-site, patrol completion rate, patrol issues found.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Tipe Insiden Keamanan

Enam tipe insiden keamanan, disimpan sebagai enum di kolom `type` pada tabel `security_incidents`:

| # | Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `unauthorized_access` | **Akses Tidak Sah** | Akses ke area restricted atau fasilitas tanpa otorisasi yang valid. Termasuk: pintasan pos satpam, akses area server tanpa izin, masuk area produksi tanpa ID. |
| 2 | `theft` | **Pencurian** | Tindakan pengambilan barang milik perusahaan atau karyawan tanpa izin. Termasuk: pencurian aset, pencurian properti pribadi di area kerja. |
| 3 | `vandalism` | **Vandalisme** | Perusakan sengaja terhadap properti, fasilitas, atau aset perusahaan. Termasuk: graffiti, merusak pagar, merusak kendaraan. |
| 4 | `trespass` | **Penyusupan** | Orang yang tidak berwenang memasuki area perusahaan tanpa izin. Termasuk: penyusup ke area gudang, orang asing di area restricted. |
| 5 | `suspicious_activity` | **Aktivitas Mencurigakan** | Perilaku atau aktivitas yang mencurigakan namun belum jelas jenis pelanggarannya. Termasuk: orang mengintai, kendaraan mencurigakan, aktivitas tidak biasa. |
| 6 | `other` | **Lainnya** | Insiden keamanan lain yang tidak termasuk kategori di atas. Wajib dijelaskan di deskripsi. |

### Severity Levels (sudah di-seed)

| Code | Name | Level | Color |
|---|---|---|---|
| `LOW` | Low | 1 | green |
| `MEDIUM` | Medium | 2 | yellow |
| `HIGH` | High | 3 | orange |
| `CRITICAL` | Critical | 4 | red |

---

## 6. Business Rules

### BR-01: Numbering on Create (Security Incidents)

- Nomor insiden keamanan di-generate **saat record dibuat** (POST create).
- Format: `SEC-YYYY-NNNN` (contoh: `SEC-2026-0001`).
- Sumber: `NumberingService::generate('security')`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `security`
  - `prefix`: `SEC`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `SEC-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat; service melakukan retry dengan increment.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Security Incident Status Flow

- Status awal: `reported` (default saat create).
- `reported` → `under_investigation` (update oleh QHSSE Officer/Security Officer).
- `under_investigation` → `closed` (close oleh QHSSE Officer/Manager, wajib isi resolution).
- `closed` adalah terminal status. Tidak bisa diedit atau di-reopen di Phase 1.
- `reported` → `closed` (skip investigation, untuk insiden sederhana, wajib resolution).

### BR-03: Close Requires Resolution

- Transition `under_investigation` → `closed` dan `reported` → `closed` memerlukan field `resolution` (wajib, text, min:10 karakter).
- `resolved_at` timestamp diisi otomatis saat close.
- Setelah close, record menjadi read-only. Tidak bisa edit, tidak bisa hapus file evidence.

### BR-04: Visitor Check-In/Check-Out

- Saat check-in, `check_in_at` otomatis diisi timestamp saat ini.
- `check_out_at` nullable saat check-in. Visitor dianggap "on-site" jika `check_out_at` is null.
- Check-out: set `check_out_at` = now(). Tidak bisa check-out jika `check_out_at` sudah terisi.
- Tidak bisa menghapus visitor log yang sudah check-in. Hanya soft-delete oleh Admin.
- Host harus merupakan user/employee yang aktif di site yang sama.

### BR-05: Patrol Execution Flow

- Status awal: `scheduled` (saat create).
- `scheduled` → `in_progress` (execute oleh officer, set `executed_at`).
- `in_progress` → `completed` (complete setelah semua checkpoint diisi).
- `completed` adalah terminal status.
- Patrol results (checkpoint, status, remark) dibuat saat eksekusi.
- Jika ada checkpoint dengan status `issue`, notifikasi dikirim ke QHSSE Officer/Manager.

### BR-06: Patrol Results Validation

- Setiap patrol checklist dapat memiliki multiple patrol results (1 per checkpoint).
- Status per checkpoint: `ok`, `issue`, `na` (not applicable).
- Jika status = `issue`, remark wajib diisi (min:5 karakter).
- Jika status = `ok` atau `na`, remark opsional.
- Patrol tidak bisa di-complete jika masih ada checkpoint yang belum diisi (status null).

### BR-07: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table):

| Event | Auditable | Old/New Values |
|---|---|---|
| `security.incident.created` | SecurityIncident | new_values: all fields |
| `security.incident.updated` | SecurityIncident | changed fields only |
| `security.incident.investigation_started` | SecurityIncident | status change |
| `security.incident.closed` | SecurityIncident | status change + resolution |
| `security.visitor.checked_in` | VisitorLog | new_values |
| `security.visitor.checked_out` | VisitorLog | check_out_at |
| `security.patrol.created` | PatrolChecklist | new_values |
| `security.patrol.executed` | PatrolChecklist | status + executed_at |
| `security.patrol.completed` | PatrolChecklist | status change |
| `security.patrol.result_recorded` | PatrolResult | new_values |

### BR-08: Data Visibility by Scope

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter | Only security incidents they created |
| `site` | QHSSE Officer, Security Officer | Incidents, visitors, patrols in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All security data |
| `company` | Contractor | Only visitor logs for own company |

- Scope check dilakukan **server-side** di Controller/Policy.
- Security Officer melihat data berdasarkan site assignment.

---

## 7. Permission Keys

11 permission keys untuk modul Security Management:

### Resource Group: security.incidents

| # | Permission Key | Description |
|---|---|---|
| 1 | `security.incidents.view` | View security incident list and detail. Scope-filtered. |
| 2 | `security.incidents.create` | Create new security incident. Generates SEC number. |
| 3 | `security.incidents.update` | Update security incident. Only reported/under_investigation status. |
| 4 | `security.incidents.close` | Close security incident. Requires resolution. |
| 5 | `security.incidents.export` | Export security incident list to CSV. |

### Resource Group: security.visitors

| # | Permission Key | Description |
|---|---|---|
| 6 | `security.visitors.view` | View visitor log list. Scope-filtered. |
| 7 | `security.visitors.create` | Create visitor log (check-in). |
| 8 | `security.visitors.update` | Update visitor log + check-out. |

### Resource Group: security.patrols

| # | Permission Key | Description |
|---|---|---|
| 9 | `security.patrols.view` | View patrol checklist list and detail. |
| 10 | `security.patrols.create` | Create new patrol checklist. Generates SPL number. |
| 11 | `security.patrols.execute` | Execute/complete patrol checklist + record results. |
| 12 | `security.patrols.export` | Export patrol checklist list to CSV. |

> **Note:** `security.patrols.export` is the 12th key. Total 12 permission keys.

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}` → `security.*`.
- Keys harus di-register di seeder (tambahkan ke `CorePermissions::all()`).
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.

---

## 8. Role-Permission Matrix

| Role | `incidents.view` | `incidents.create` | `incidents.update` | `incidents.close` | `incidents.export` | `visitors.view` | `visitors.create` | `visitors.update` | `patrols.view` | `patrols.create` | `patrols.execute` | `patrols.export` |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Security Officer | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Contractor | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |

### Notes

- **Security Officer** dapat create/update incidents dan manage visitors/patrols, tetapi tidak dapat `close` incidents (terminal action untuk QHSSE Officer/Manager).
- **Supervisor** dapat view security data, create visitor log entries, namun tidak manage incidents atau patrols.
- **Contractor** hanya dapat view visitor logs untuk company-nya sendiri (scope: company).
- **Employee/Reporter** hanya dapat view security incidents yang mereka buat.
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

### 9.1 `security.incident.reported`

| Property | Value |
|---|---|
| **Trigger** | User membuat security incident baru (POST create) |
| **Recipients** | All users with role `Security Officer` and `QHSSE Officer` in the same site scope |
| **Type** | `security.incident.reported` |
| **Title (template)** | `Insiden Keamanan Baru: {incident.security_number}` |
| **Message (template)** | `{reporter.name} melaporkan insiden keamanan {incident.security_number} - {incident.title} di {site.name}. Tipe: {incident.type}.` |
| **Action URL** | `/security-incidents/{incident.id}` |
| **Module/Reference** | `module_name='security'`, `reference_id={incident.id}` |

### 9.2 `security.incident.closed`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager melakukan close (transition → `closed`) |
| **Recipients** | Reporter (`incident.reported_by`) |
| **Type** | `security.incident.closed` |
| **Title (template)** | `Insiden Keamanan Ditutup: {incident.security_number}` |
| **Message (template)** | `Insiden keamanan {incident.security_number} - {incident.title} telah ditutup. Resolusi: {resolution}.` |
| **Action URL** | `/security-incidents/{incident.id}` |

### 9.3 `security.visitor.checked_in`

| Property | Value |
|---|---|
| **Trigger** | Security Officer/front desk melakukan check-in visitor |
| **Recipients** | Host user (`visitor_log.host_id`) |
| **Type** | `security.visitor.checked_in` |
| **Title (template)** | `Pengunjung Check-In: {visitor.visitor_name}` |
| **Message (template)** | `Pengunjung {visitor.visitor_name} dari {visitor.visitor_company} telah check-in di {site.name}. Tujuan: {visitor.purpose}. Mohon temui di resepsionis.` |
| **Action URL** | `/visitor-logs/{visitor.id}` |

### 9.4 `security.visitor.checked_out`

| Property | Value |
|---|---|
| **Trigger** | Security Officer/front desk melakukan check-out visitor |
| **Recipients** | Host user (`visitor_log.host_id`) |
| **Type** | `security.visitor.checked_out` |
| **Title (template)** | `Pengunjung Check-Out: {visitor.visitor_name}` |
| **Message (template)** | `Pengunjung {visitor.visitor_name} telah check-out dari {site.name} pada {check_out_at}.` |
| **Action URL** | `/visitor-logs/{visitor.id}` |

### 9.5 `security.patrol.executed`

| Property | Value |
|---|---|
| **Trigger** | Officer memulai eksekusi patrol (transition `scheduled` → `in_progress`) |
| **Recipients** | QHSSE Officer in same site scope |
| **Type** | `security.patrol.executed` |
| **Title (template)** | `Patroli Dimulai: {patrol.patrol_number}` |
| **Message (template)** | `Patroli {patrol.patrol_number} - Rute {patrol.patrol_route} sedang dieksekusi oleh {officer.name} di {site.name}.` |
| **Action URL** | `/patrol-checklists/{patrol.id}` |

### 9.6 `security.patrol.issue_found`

| Property | Value |
|---|---|
| **Trigger** | Patrol result dengan status `issue` disimpan |
| **Recipients** | QHSSE Officer and QHSSE Manager in same site scope |
| **Type** | `security.patrol.issue_found` |
| **Title (template)** | `Temuan Issue Patroli: {patrol.patrol_number}` |
| **Message (template)** | `Ditemukan issue pada checkpoint "{checkpoint}" saat patroli {patrol.patrol_number}. Remark: {remark}. Mohon tindak lanjut.` |
| **Action URL** | `/patrol-checklists/{patrol.id}` |

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `security` |
| **reference_id** | `security_incidents.id` |
| **collection** | `evidence` |
| **Disk** | `local` (private) |
| **Path pattern** | `security/{incident_id}/evidence/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `mp4`, `mov` |
| **Max file size** | 25 MB per file |
| **Max files per incident** | 20 |

### 10.3 Access Rules

- **Upload**: User must have `security.incidents.update` (or be the reporter).
- **Download**: User must have `security.incidents.view` and be within scope.
- **Delete**: User must have `security.incidents.update` AND incident status must NOT be `closed`.

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Insiden Keamanan** | Count all security_incidents in scope | Number + icon |
| **Insiden Terbuka** | Count where status NOT IN (`closed`) | Number, red if > threshold |
| **Insiden Kritis** | Count where severity = `CRITICAL` AND status != `closed` | Number, red badge |
| **Pengunjung Hari Ini** | Count visitor_logs where check_in_at = today | Number |
| **Pengunjung On-Site** | Count where check_out_at IS NULL | Number, blue |
| **Patroli Bulan Ini** | Count patrol_checklists where scheduled_at in current month | Number |
| **Issue Patroli** | Count patrol_results where status = `issue` AND created this month | Number, orange |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Trend Insiden** | Line chart | Security incident count by month (last 12 months) |
| **By Type** | Donut | Count by type (unauthorized_access, theft, etc.) |
| **By Severity** | Stacked bar | Count by severity |
| **Pengunjung Harian** | Bar chart | Visitor check-in count by day (last 30 days) |
| **Patroli Completion** | Donut | Scheduled vs completed vs in_progress |

---

## 12. Export Spec

### 12.1 Security Incidents CSV

| Property | Value |
|---|---|
| **Filename** | `security_incidents_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `security.incidents.export` |
| **Scope** | Follows user's data scope |

#### CSV Columns

| # | Column Header | Field Source |
|---|---|---|
| 1 | `Nomor` | `security_number` |
| 2 | `Judul` | `title` |
| 3 | `Tipe` | `type` |
| 4 | `Deskripsi` | `description` |
| 5 | `Severity` | `severity.name` |
| 6 | `Site` | `site.name` |
| 7 | `Area` | `area.name` |
| 8 | `Pelapor` | `reported_by.name` |
| 9 | `Waktu Kejadian` | `occurred_at` |
| 10 | `Status` | `status` |
| 11 | `Resolusi` | `resolution` |
| 12 | `Ditutup Pada` | `resolved_at` |
| 13 | `Dibuat Pada` | `created_at` |

### 12.2 Visitor Logs CSV

| Property | Value |
|---|---|
| **Filename** | `visitor_logs_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `security.visitors.view` |

#### CSV Columns

| # | Column Header | Field Source |
|---|---|---|
| 1 | `Nama Pengunjung` | `visitor_name` |
| 2 | `Perusahaan` | `visitor_company` |
| 3 | `Tujuan` | `purpose` |
| 4 | `Host` | `host.name` |
| 5 | `Site` | `site.name` |
| 6 | `Jenis ID` | `id_type` |
| 7 | `Nomor ID` | `id_number` |
| 8 | `Plat Kendaraan` | `vehicle_plate` |
| 9 | `Check-In` | `check_in_at` |
| 10 | `Check-Out` | `check_out_at` |

### 12.3 Patrol Checklists CSV

| Property | Value |
|---|---|
| **Filename** | `patrol_checklists_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `security.patrols.export` |

#### CSV Columns

| # | Column Header | Field Source |
|---|---|---|
| 1 | `Nomor` | `patrol_number` |
| 2 | `Site` | `site.name` |
| 3 | `Rute` | `patrol_route` |
| 4 | `Officer` | `officer.name` |
| 5 | `Terjadwal` | `scheduled_at` |
| 6 | `Dieksekusi` | `executed_at` |
| 7 | `Status` | `status` |
| 8 | `Catatan` | `notes` |
| 9 | `Total Checkpoint` | count of patrol_results |
| 10 | `Issue Found` | count of patrol_results where status = `issue` |

---

## 13. Acceptance Criteria

1. **AC-01: Create security incident with auto-numbering** — User dengan permission `security.incidents.create` dapat membuat security incident. Nomor `SEC-YYYY-NNNN` di-generate otomatis. Nomor unique.

2. **AC-02: Permission enforcement** — User tanpa permission tidak dapat mengakses halaman atau API. Server-side check memblokir akses meskipun user memanipulasi URL.

3. **AC-03: Visitor check-in/check-out** — User dapat melakukan check-in visitor dengan data lengkap. Check-out mengisi timestamp. Tidak bisa check-out dua kali. Pengunjung on-site terlihat di list.

4. **AC-04: Patrol execution flow** — Patrol checklist dibuat dengan status `scheduled`. Officer dapat execute (status → `in_progress`, set executed_at). Complete setelah semua checkpoint diisi. Issue checkpoint wajib remark.

5. **AC-05: Security incident close with resolution** — Close incident wajib resolution (min 10 karakter). resolved_at diisi otomatis. Record menjadi read-only.

6. **AC-06: Notifications sent correctly** — Notifikasi terkirim untuk: incident reported (ke security team), incident closed (ke reporter), visitor checked_in/out (ke host), patrol executed (ke QHSSE), patrol issue found (ke QHSSE).

7. **AC-07: List with search/filter/pagination/export** — Semua 3 resource (incidents, visitors, patrols) mendukung search, filter, pagination (15 per page), dan export CSV.

8. **AC-08: Audit trail complete** — Audit trail tercatat untuk semua critical events: create, update, close, check-in, check-out, patrol execute, patrol complete.

---

## 14. Open Questions

| # | Question | Default Answer |
|---|---|---|
| 1 | Apakah security incident bisa di-link ke general incident report? | **Yes** — dapat ditambahkan di Phase 2 dengan kolom `linked_incident_id` nullable. |
| 2 | Apakah visitor log butuh pre-registration? | **No untuk Phase 1** — check-in langsung. Pre-registration dapat ditambah di Phase 2. |
| 3 | Apakah patrol checklist mendukung template berulang? | **No untuk Phase 1** — setiap patrol dibuat manual. Template dapat ditambah di Phase 2. |
| 4 | Apakah perlu QR code untuk checkpoint verification? | **No untuk Phase 1** — officer memilih checkpoint dari list. QR code dapat ditambah di Phase 2. |
| 5 | Apakah visitor log perlu approval dari host? | **No untuk Phase 1** — host hanya menerima notifikasi. Approval dapat ditambah di Phase 2. |
| 6 | Apakah ada SLA untuk close security incident? | **No untuk Phase 1** — tidak ada auto-escalation. |
| 7 | Apakah security officer adalah role baru atau subset QHSSE Officer? | **Role baru** — `Security Officer` dengan permission khusus security module. |
| 8 | Apakah patrol results bisa menambahkan foto evidence? | **Yes di Phase 2** — untuk Phase 1 hanya text remark. |

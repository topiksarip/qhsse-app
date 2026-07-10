# Module Spec — Training & Competency

> **Module ID:** `08-training-competency`  
> **Module Code (numbering):** `training`  
> **Number Prefix:** `TRN`  
> **Phase:** Phase 8 — Training & Competency  
> **Status:** Ready for coding  
> **Dependencies:** Core Foundation, Employee, Notification  

---

## 1. Tujuan Modul

Modul Training & Competency menyediakan sistem manajemen pelatihan dan kompetensi QHSSE secara end-to-end. Modul ini mengelola program pelatihan, pencatatan record pelatihan per karyawan, sertifikat, pelacakan kedaluwarsa, dan matriks kompetensi.

Tujuan utama:

- Mengelola **program pelatihan** (training programs) dengan kategori, durasi, dan masa berlaku sertifikat.
- Mencatat **record pelatihan** per karyawan dengan nomor unik `TRN-YYYY-NNNN` yang di-generate otomatis pada saat create.
- Menyimpan **sertifikat** melalui `ManagedFileService` dengan `module_name='training'` dan `collection='certificate'`.
- Melakukan **pelacakan kedaluwarsa** (expiry tracking): record dengan `expiry_date < now()` secara otomatis berstatus `expired`.
- Menyediakan **matriks pelatihan** (training matrix) berupa grid employee × program yang menampilkan status kompetensi.
- Mengirim **notifikasi** reminder ketika tanggal kedaluwarsa sertifikat mendekat.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal (create, update, status changes).
- Menyediakan **dashboard metrics** dan **export CSV** untuk analisis dan pelaporan.
- Menggunakan **simple status** (tanpa workflow engine) — perubahan status dilakukan manual melalui field `status`.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | Permission keys `training.programs.*` + `training.records.*` |
| **NumberingService** | Generate `TRN-YYYY-NNNN` on training record create |
| **ManagedFileService** | Upload/download certificate files via `managed_files` table |
| **NotificationService** | In-app + email notifications for expiry reminders |
| **AuditService** | Audit log via `audit_logs` table |
| **ActivityService** | Activity timeline via `activity_logs` table |
| **ListQuery** | Paginate, search, filter, sort on index pages |
| **CsvExporter** | CSV export for training records |

### Cross-Module Dependencies

| Module | Relationship |
|---|---|
| `Employee` (Core Master Data) | `training_records.employee_id` → `employees.id` (FK). Employee master provides name, department, position. |
| `Notification` (Core Service) | Expiry reminder notifications sent via `NotificationService::notifyMany()` |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini (sesuai `RolesAndPermissionsSeeder`):

| # | Role | Deskripsi Peran dalam Training & Competency |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Kelola program & record. Scope: all sites. Approve training completion. |
| 4 | **QHSSE Officer** | Kelola program & record. Scope: assigned site(s). Create, update records. |
| 5 | **Supervisor** | View training records untuk department-nya. Scope: department. |
| 6 | **Department Head** | View training records untuk department-nya. Scope: department. |
| 7 | **Employee/Reporter** | View training records miliknya sendiri. Scope: own. |
| 8 | **Contractor** | View training records miliknya sendiri. Scope: company (contractor company). |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Training Program CRUD

- **Create** — Form pembuatan program pelatihan: kode, nama, deskripsi, kategori, durasi (jam), apakah sertifikasi, masa berlaku sertifikat (bulan).
- **List** — Halaman list program dengan search (kode, nama), filter (kategori, status aktif), pagination (default 15 per page).
- **Detail** — Halaman detail program menampilkan semua field + jumlah record pelatihan terkait.
- **Update** — Edit program. Field dapat diubah kapan saja selama `is_active = true`.
- **Deactivate** — Set `is_active = false` untuk menonaktifkan program. Program non-aktif tidak bisa dipilih saat membuat record baru, tetapi record yang ada tetap mempertahankan referensi.

### 4.2 Training Record Management

- **Create** — Form pembuatan record pelatihan: pilih karyawan, pilih program, provider, tanggal mulai/selesai, status. Nomor `TRN-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal: `scheduled`.
- **List** — Halaman list dengan search (nomor, nama karyawan), filter (program, karyawan, status, expiry), pagination (default 15 per page), dan tombol Export CSV. **Record dengan `expiry_date < now()` disorot dengan warna MERAH**.
- **Detail** — Halaman detail menampilkan: nomor, karyawan, program, provider, tanggal, status, skor, hasil (pass/fail/pending), nomor sertifikat, file sertifikat (download), tanggal kedaluwarsa, catatan, activity log, audit trail.
- **Update** — Edit record. Bisa update status, skor, hasil, sertifikat, tanggal kedaluwarsa.
- **Status Manual** — Tidak menggunakan workflow engine. Status diubah manual melalui field `status`. Transisi yang diizinkan lihat WORKFLOW.md.

### 4.3 Certificate Management

- Upload file sertifikat melalui `ManagedFileService` core.
- `module_name`: `training`
- `collection`: `certificate`
- `reference_id`: `training_records.id`
- Satu file sertifikat per record (nullable — boleh tidak ada).
- Download melalui authorized endpoint (permission check).
- File tersimpan di private disk, tidak ada direct public URL.

### 4.4 Training Matrix

- View berupa grid: baris = karyawan, kolom = program pelatihan.
- Setiap sel menampilkan status kompetensi: `completed` (hijau), `expired` (merah), `scheduled` (kuning), `not_started` (abu-abu).
- Filter berdasarkan site, department.
- Click sel untuk navigasi ke record detail.
- Matrix mendukung pagination karyawan (default 20 per halaman).
- Export matrix ke CSV.

### 4.5 Expiry Tracking & Reminder

- Sistem secara otomatis mendeteksi record dengan `expiry_date < now()` dan mengubah `status` menjadi `expired` (via scheduled command atau on-access check).
- Notifikasi reminder dikirim ketika `expiry_date` mendekat (30 hari, 7 hari sebelum expiry).
- Dashboard widget menampilkan jumlah sertifikat akan kedaluwarsa (30 hari ke depan) dan sudah kedaluwarsa.

### 4.6 Notification Events

- 3 event notifikasi: `training.expiry_reminder_30d`, `training.expiry_reminder_7d`, `training.record_created`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.7 Dashboard & Reporting

- Dashboard widget: total record, breakdown by status, sertifikat akan kedaluwarsa, sertifikat kedaluwarsa, trend bulanan.
- Export CSV training records dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Kategori Program Pelatihan

Kategori program pelatihan (disimpan sebagai string di field `category`):

| # | Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `safety` | **Keselamatan** | Pelatihan terkait keselamatan kerja: HSE induction, fire safety, PPE, working at height. |
| 2 | `technical` | **Teknis** | Pelatihan teknis operasional: equipment operation, maintenance, prosedur kerja. |
| 3 | `compliance` | **Kepatuhan** | Pelatihan kepatuhan regulasi: ISO, SMK3, legal compliance, audit internal. |
| 4 | `soft_skill` | **Soft Skill** | Pelatihan pengembangan diri: komunikasi, leadership, problem solving. |
| 5 | `environment` | **Lingkungan** | Pelatihan environmental management: spill response, waste management, EMS. |
| 6 | `security` | **Keamanan** | Pelatihan security management: access control, emergency response, threat awareness. |
| 7 | `quality` | **Kualitas** | Pelatihan quality management: QMS, inspection technique, root cause analysis. |
| 8 | `first_aid` | **Pertolongan Pertama** | Pelatihan first aid, CPR, emergency first responder. |

> **Catatan:** Kategori disimpan sebagai string bebas di field `category` (bukan FK ke tabel master). Memungkinkan fleksibilitas tanpa migrasi tambahan. Daftar kategori dapat divalidasi di Form Request.

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor training record di-generate **saat record dibuat** (POST create).
- Format: `TRN-YYYY-NNNN` (contoh: `TRN-2026-0001`).
- Sumber: `NumberingService::generate('training', ...)`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `training`
  - `prefix`: `TRN`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `TRN-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Status Transitions (Manual — No Workflow Engine)

- Training records menggunakan **simple status field** tanpa WorkflowService.
- Status diubah manual melalui update endpoint.
- Status yang tersedia: `scheduled`, `in_progress`, `completed`, `expired`, `cancelled`.
- Transisi yang diizinkan:
  - `scheduled` → `in_progress`, `cancelled`
  - `in_progress` → `completed`, `cancelled`
  - `completed` → `expired` (otomatis via expiry check), `in_progress` (reopen jika perlu koreksi)
  - `expired` → `scheduled` (re-schedule ulang training)
  - Setiap transisi dicatat di `activity_logs` dan `audit_logs`.

### BR-03: Expiry Auto-Detection

- Ketika `expiry_date < now()` dan `status = 'completed'`, sistem mengubah `status` menjadi `expired`.
- Expiry check dilakukan via:
  - **Scheduled command** (daily at 00:01): `php artisan training:check-expiry`
  - **On-access check** (middleware/observer): ketika record di-load di Show page, cek expiry.
- `expiry_date` dihitung otomatis dari `end_date + validity_months` (dari program) jika `validity_months` tidak null. Dapat juga di-set manual.

### BR-04: Certificate Upload

- Sertifikat diunggah melalui `ManagedFileService` dengan `module_name='training'`, `collection='certificate'`.
- Hanya satu file sertifikat per record (nullable).
- File disimpan di private disk (`local`).
- Upload memerlukan permission `training.records.update`.
- Download memerlukan permission `training.records.view` dan data scope.
- Jika record di-update dengan sertifikat baru, file lama di-soft-delete.

### BR-05: Program Active Requirement

- Saat membuat record pelatihan, program yang dipilih harus `is_active = true`.
- Program yang sudah non-aktif tetap muncul di record yang ada (tidak cascade delete).
- Jika program di-nonaktifkan, record yang sudah ada tidak terpengaruh.

### BR-06: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='training'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `training.record.created` | TrainingRecord | new_values: all fields |
| `training.record.updated` | TrainingRecord | changed fields only |
| `training.record.status_changed` | TrainingRecord | status change |
| `training.record.deleted` | TrainingRecord | soft delete |
| `training.program.created` | TrainingProgram | new_values: all fields |
| `training.program.updated` | TrainingProgram | changed fields only |
| `training.file.uploaded` | ManagedFile | new_values |
| `training.file.downloaded` | ManagedFile | metadata: user, ip |

### BR-07: Data Visibility by Scope

Data visibility mengikuti role scope (sesuai `CorePermissions::roleMap()`):

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter, Contractor | Only training records where `employee_id` matches their employee profile |
| `department` | Supervisor, Department Head | Training records for employees in their department |
| `site` | QHSSE Officer | Training records for employees in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All training records |

- Scope check dilakukan **server-side** di Controller/Policy.
- Contractor hanya melihat training records milik company/contractor-nya.
- Training programs (master) dapat dilihat oleh semua role yang punya `training.programs.view`.

---

## 7. Permission Keys

Training & Competency menggunakan **2 resource groups**:

### 7.1 Training Programs (`training.programs.*`)

| # | Permission Key | Description |
|---|---|---|
| 1 | `training.programs.view` | View training program list and detail. |
| 2 | `training.programs.create` | Create new training program. |
| 3 | `training.programs.update` | Update training program (including deactivate). |

### 7.2 Training Records (`training.records.*`)

| # | Permission Key | Description |
|---|---|---|
| 4 | `training.records.view` | View training record list and detail. Scope-filtered. |
| 5 | `training.records.create` | Create new training record. Generates TRN number. |
| 6 | `training.records.update` | Update training record (status, score, certificate, etc.). |
| 7 | `training.records.export` | Export training records to CSV. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}`.
- Keys harus di-register di seeder (tambahkan ke `CorePermissions::all()`).
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Training matrix view memerlukan `training.records.view`.

---

## 8. Role-Permission Matrix

### 8.1 Training Programs (`training.programs.*`)

| Role | `view` | `create` | `update` |
|---|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ |
| Department Head | ✅ | ❌ | ❌ |
| Employee/Reporter | ✅ | ❌ | ❌ |
| Contractor | ✅ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ |
| Top Management | ✅ | ❌ | ❌ |

### 8.2 Training Records (`training.records.*`)

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

### Notes

- **Employee/Reporter** dan **Contractor** dapat view training records miliknya sendiri (scope: own/company).
- **Supervisor** dan **Department Head** dapat view + export untuk department-nya (scope: department).
- **Auditor** dan **Top Management** hanya view + export (read-only).
- **QHSSE Officer/Manager** dapat create/update records.
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

3 event notifikasi untuk modul Training & Competency:

### 9.1 `training.expiry_reminder_30d`

| Property | Value |
|---|---|
| **Trigger** | Scheduled command mendeteksi `expiry_date` dalam 30 hari ke depan |
| **Recipients** | Employee yang bersangkutan + Supervisor department + QHSSE Officer site |
| **Type** | `training.expiry_reminder_30d` |
| **Title (template)** | `Pengingat: Sertifikat Akan Kedaluwarsa — {record.training_number}` |
| **Message (template)** | `Sertifikat training {program.name} untuk {employee.name} akan kedaluwarsa pada {record.expiry_date}. Mohon jadwalkan ulang training.` |
| **Action URL** | `/training-records/{record.id}` |
| **Module/Reference** | `module_name='training'`, `reference_id={record.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `training.expiry_reminder_7d`

| Property | Value |
|---|---|
| **Trigger** | Scheduled command mendeteksi `expiry_date` dalam 7 hari ke depan |
| **Recipients** | Employee + Supervisor + QHSSE Officer + QHSSE Manager |
| **Type** | `training.expiry_reminder_7d` |
| **Title (template)** | `Urgent: Sertifikat Kedaluwarsa 7 Hari — {record.training_number}` |
| **Message (template)** | `Sertifikat training {program.name} untuk {employee.name} akan kedaluwarsa dalam 7 hari pada {record.expiry_date}. Segera jadwalkan ulang training.` |
| **Action URL** | `/training-records/{record.id}` |
| **Module/Reference** | `module_name='training'`, `reference_id={record.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.3 `training.record_created`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager membuat training record baru |
| **Recipients** | Employee yang di-assign ke training |
| **Type** | `training.record_created` |
| **Title (template)** | `Pelatihan Baru: {record.training_number}` |
| **Message (template)** | `Anda dijadwalkan untuk pelatihan {program.name} ({program.code}) pada {record.start_date}. Silakan lihat detail pelatihan.` |
| **Action URL** | `/training-records/{record.id}` |
| **Module/Reference** | `module_name='training'`, `reference_id={record.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit (Event/Listener or Observer pattern).
- Expiry reminders dijalankan via scheduled command: `php artisan training:check-expiry` (daily).
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Recipient resolution: query users with target role + matching scope (site/department).

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core ManagedFileService (`App\Core\File\ManagedFileService`) |
| **Table** | `managed_files` |
| **module_name** | `training` |
| **reference_id** | `training_records.id` |
| **collection** | `certificate` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `training/{record_id}/certificate/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `pdf`, `jpg`, `jpeg`, `png` |
| **Allowed MIME types** | `application/pdf`, `image/jpeg`, `image/png` |
| **Max file size** | 10 MB per file |
| **Max files per record** | 1 (one certificate per record) |
| **Filename** | Original filename stored in `original_name`; generated UUID-based name in `stored_name` |

### 10.3 Access Rules

- **Upload**: User must have `training.records.update`.
- **Download**: User must have `training.records.view` and be within data scope of the record.
- **Delete/Replace**: User must have `training.records.update`. Old certificate is soft-deleted when replaced.
- Download endpoint streams file from private storage; no direct public URL.
- File access logged in audit trail (`training.file.downloaded`).

### 10.4 File Metadata

Each file record in `managed_files` includes:

- `module_name`: `training`
- `reference_id`: `training_records.id`
- `collection`: `certificate`
- `disk`: `local`
- `path`: storage path
- `original_name`: user's original filename
- `stored_name`: generated filename
- `mime_type`: detected MIME
- `extension`: file extension
- `size`: file size in bytes
- `checksum`: SHA-256 hash (optional)
- `metadata`: JSON (e.g., `{"uploaded_at": "2026-07-11T14:30:00"}`)
- `uploaded_by`: user ID
- `deleted_at`: soft delete timestamp
- `deleted_by`: user ID who deleted

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Records** | Count all training records in scope | Number + icon |
| **Scheduled** | Count where status = `scheduled` | Number, blue |
| **In Progress** | Count where status = `in_progress` | Number, yellow |
| **Completed** | Count where status = `completed` | Number, green |
| **Expired** | Count where status = `expired` | Number, **red badge** |
| **Expiring Soon (30d)** | Count where `expiry_date` between now and now+30 days AND status = `completed` | Number, orange |
| **This Month** | Count created in current month | Number + trend arrow |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Trend** | Line chart | Training record count by month (last 12 months) |
| **By Status** | Donut | Count by status (scheduled, in_progress, completed, expired, cancelled) |
| **By Category** | Bar chart | Count by program category |
| **By Site** | Horizontal bar | Count by site (top 10) |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Expiring Certificates** | Number, Employee, Program, Expiry Date, Days Until Expiry | Expiry within 30 days, status = completed |
| **Expired Certificates** | Number, Employee, Program, Expiry Date, Days Overdue | Status = expired |
| **Recent Records** | Number, Employee, Program, Status, Created At | Last 10, scoped |

### 11.4 Filters

Dashboard metrics support:
- Date range filter (default: current year)
- Site filter
- Department filter
- Program category filter

---

## 12. Export Spec

### 12.1 CSV Export — Training Records

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `training_records_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `training.records.export` |
| **Scope** | Follows user's data scope (own/department/site/all) |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `training_number` | TRN-YYYY-NNNN |
| 2 | `Karyawan` | `employee.name` | Via `employee_id` |
| 3 | `Program` | `program.name` | Via `training_program_id` |
| 4 | `Kategori` | `program.category` | |
| 5 | `Provider` | `provider` | Nullable |
| 6 | `Tanggal Mulai` | `start_date` | Format: YYYY-MM-DD |
| 7 | `Tanggal Selesai` | `end_date` | Nullable |
| 8 | `Status` | `status` | |
| 9 | `Skor` | `score` | Nullable |
| 10 | `Hasil` | `result` | pass/fail/pending |
| 11 | `Nomor Sertifikat` | `certificate_number` | Nullable |
| 12 | `Tanggal Kedaluwarsa` | `expiry_date` | Nullable |
| 13 | `Site` | `employee.site.name` | Via employee relation |
| 14 | `Department` | `employee.department.name` | Via employee relation |

---

## 13. Acceptance Criteria

1. User dengan permission `training.programs.create` dapat membuat program pelatihan baru.
2. User dengan permission `training.records.create` dapat membuat record pelatihan dengan nomor TRN otomatis.
3. User tanpa permission ditolak (403) pada aksi yang memerlukan permission.
4. Record dengan `expiry_date < now()` dan `status = 'completed'` berubah menjadi `expired` (via scheduled command atau on-access).
5. Sertifikat dapat di-upload dan di-download melalui ManagedFileService.
6. Training matrix menampilkan grid employee × program dengan status kompetensi.
7. List page menyorot record kedaluwarsa dengan warna merah.
8. Audit trail tercatat untuk create, update, dan status changes.
9. List dapat search/filter/pagination sesuai ListQuery.
10. Export CSV menghasilkan data sesuai filter dan permission.
11. Notifikasi reminder dikirim ketika sertifikat mendekati kedaluwarsa (30 hari, 7 hari).
12. Status record dapat diubah manual (scheduled → in_progress → completed → expired) tanpa workflow engine.

---

## 14. Open Questions

- Apakah perlu fitur mandatory training by position? (Jika ya, tambahkan tabel pivot `position_training_programs`.)
- Apakah perlu attendance tracking (presensi peserta)? (Phase 2 jika diperlukan.)
- Apakah perlu integrasi dengan contractor induction module? (Phase 2 jika diperlukan.)
- Apakah perlu fitur pre-test dan post-test? (Phase 2 jika diperlukan.)
- Default reminder days: 30 hari dan 7 hari — apakah perlu konfigurasi per program?
- Apakah expiry check perlu real-time (on-access) atau cukup scheduled command daily?

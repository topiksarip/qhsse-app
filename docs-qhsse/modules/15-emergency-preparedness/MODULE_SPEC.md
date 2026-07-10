# Module Spec — Emergency Preparedness

> **Module ID:** `15-emergency-preparedness`
> **Module Code (numbering):** `emergency`
> **Number Prefix:** `EMG`
> **Phase:** Phase 15 — Emergency Preparedness
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul Emergency Preparedness menyediakan sistem terpadu untuk pengelolaan kesiapsiagaan darurat. Modul ini mencakup tiga entitas utama: **Rencana Darurat** (emergency plans), **Latihan Darurat** (emergency drills), dan **Kontak Darurat** (emergency contacts). Modul ini memastikan setiap lokasi/site memiliki rencana darurat yang terdokumentasi, latihan darurat yang terjadwal dan terlaksana, serta daftar kontak darurat yang selalu diperbarui.

Tujuan utama:

- Memungkinkan **QHSSE Manager, QHSSE Officer, dan Supervisor** membuat dan mengelola rencana darurat (fire, medical, spill, evacuation, natural disaster, security, other) dengan nomor unik `EMG-YYYY-NNNN`.
- Menjadwalkan dan melacak pelaksanaan **latihan darurat** dengan nomor `EMG-YYYY-NNNN` (sharing numbering sequence dengan plans), mencatat hasil (pass/fail/needs_improvement), temuan, dan rekomendasi.
- Mengelola **kontak darurat** (nama, peran, telepon, email) per site dengan status aktif/non-aktif.
- Menyimpan prosedur respons dan eskalasi pada setiap rencana darurat, serta peralatan yang dibutuhkan.
- Menghubungkan rencana darurat ke modul **Training** (untuk pelatihan terkait) dan **Communication** (untuk broadcast notifikasi darurat).
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal.
- Mendukung **evidence attachments** (foto, dokumen prosedur) melalui File Service core dengan collection `evidence`.
- Menyediakan **dashboard metrics** dan **export CSV** untuk pelaporan manajemen.

---

## 2. Dependency

### Core Foundation (Phase 0 — complete)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | Permission keys `emergency.plans.*`, `emergency.drills.*`, `emergency.contacts.*` |
| **NumberingService** | Generate `EMG-YYYY-NNNN` on create (plans and drills share the `emergency` numbering module) |
| **FileService** | Upload/download evidence files via `managed_files` table |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='emergency'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **ExportService** | CSV export via `emergency.plans.export` / `emergency.drills.export` |
| **ListQuery** | Paginated search/filter/sort |
| **CsvExporter** | Stream CSV export |
| **MasterData** | Sites, Users (for contact_person, observer) |

### Cross-Module (existing modules)

| Module | Relationship |
|---|---|
| `08-training-competency` | Latihan darurat dapat dikaitkan dengan sesi pelatihan terkait |
| `16-asset-management` | Peralatan darurat dapat dirujuk dalam rencana (equipment_needed text) |
| `18-communication` | Kontak darurat dapat digunakan untuk broadcast komunikasi darurat |
| `20-admin-master-data` | Sites, Users digunakan untuk scope dan ownership |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini (sesuai `RolesAndPermissionsSeeder`):

| # | Role | Deskripsi Peran dalam Emergency Preparedness |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Create, update, export semua data. Scope: all sites. Approve hasil latihan. |
| 4 | **QHSSE Officer** | Create, update, schedule drills, execute drills. Scope: assigned site(s). |
| 5 | **Supervisor** | View, create contacts. Scope: department. |
| 6 | **Department Head** | View data di department-nya. Scope: department. |
| 7 | **Employee/Reporter** | View plans and contacts. Scope: own site. |
| 8 | **Contractor** | View plans. Scope: company (contractor company). |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Emergency Plan CRUD

- **Create** — Form pembuatan rencana darurat. Nomor `EMG-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Field meliputi: plan_number, name, type, site_id, description, response_procedure, escalation_procedure, contact_person_id, emergency_contacts (JSON), equipment_needed, timestamps.
- **List** — Halaman daftar dengan search (nomor, nama), filter (site, type), pagination (default 15 per page), dan tombol Export CSV.
- **Detail** — Halaman detail menampilkan: nomor, nama, tipe, deskripsi, prosedur respons, prosedur eskalasi, kontak person, kontak darurat (JSON array), peralatan yang dibutuhkan, drills terkait, timeline aktivitas, komentar, audit trail, dan evidence files.
- **Update** — Edit rencana darurat. Semua field dapat diedit kecuali `plan_number`.
- **Delete** — Soft delete. Hanya Super Admin / Admin.

### 4.2 Emergency Drill CRUD

- **Create** — Form penjadwalan latihan darurat. Nomor `EMG-YYYY-NNNN` di-generate otomatis. Field meliputi: drill_number, emergency_plan_id, scheduled_date, executed_date (nullable), site_id, participants_count, observer_id, result, findings, recommendations, status.
- **List** — Halaman daftar dengan search (nomor), filter (site, status, result, date range), pagination, dan tombol Export CSV.
- **Detail** — Halaman detail menampilkan: nomor, rencana darurat terkait, tanggal terjadwal, tanggal eksekusi, jumlah peserta, observer, hasil, temuan, rekomendasi, status, timeline aktivitas.
- **Update** — Edit latihan darurat. Field `result`, `findings`, `recommendations` hanya dapat diisi saat/ setelah eksekusi.
- **Execute** — Tindakan khusus untuk mencatat pelaksanaan drill: set `executed_date`, `result`, `findings`, `recommendations`, dan status → `executed`.

### 4.3 Emergency Contact CRUD

- **Create** — Form pembuatan kontak darurat. Field: name, role, phone, email (nullable), site_id, is_active.
- **List** — Halaman daftar dengan search (nama, telepon), filter (site, is_active), pagination.
- **Detail** — Kontak darurat ditampilkan dalam card sederhana atau inline di halaman plan detail.
- **Update** — Edit kontak darurat.
- **Delete** — Soft delete. Hanya Super Admin / Admin. Atau set `is_active = false` untuk menonaktifkan tanpa hapus.

### 4.4 Drill Scheduling & Execution Tracking

- **Scheduling** — Drill dapat dijadwalkan dengan `scheduled_date`. Status awal: `scheduled`.
- **Execution** — Saat drill dilaksanakan, user dengan permission `emergency.drills.execute` mencatat: `executed_date`, `participants_count`, `result` (pass/fail/needs_improvement), `findings`, `recommendations`. Status → `executed`.
- **Result Tracking** — Hasil drill ditampilkan di plan detail page. Drill dengan result `fail` atau `needs_improvement` disorot.
- **Notification** — Notifikasi dikirim ke QHSSE team saat drill dijadwalkan dan saat dieksekusi.

### 4.5 Evidence Management

- Upload file bukti (foto, video, dokumen prosedur) melalui File Service core.
- Collection: `evidence`.
- Multiple files per emergency plan.
- Download melalui authorized endpoint (permission check).

### 4.6 Comments & Activity Timeline

- Comment dapat ditambahkan pada emergency plan oleh user yang punya akses.
- Activity log otomatis mencatat: create, update, drill_scheduled, drill_executed, delete.
- Timeline ditampilkan di halaman detail plan.

### 4.7 Notification

- 4 event notifikasi: `emergency.plan_created`, `emergency.drill_scheduled`, `emergency.drill_executed`, `emergency.drill_failed`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.8 Dashboard & Reporting

- Dashboard widget: total plans, total drills, upcoming drills, drills by result, contacts by site.
- Export CSV plans dan drills dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Tipe Rencana Darurat

Tujuh tipe rencana darurat, disimpan sebagai enum pada kolom `type`:

| # | Code | Nama (ID) | Deskripsi |
|---|---|---|---|
| 1 | `fire` | **Kebakaran** | Rencana respons untuk kebakaran, termasuk prosedur evakuasi, penggunaan APAR, dan koordinasi dengan pemadam kebakaran. |
| 2 | `medical` | **Medis** | Rencana respons untuk keadaan darurat medis, pertolongan pertama, dan evakuasi medis. |
| 3 | `spill` | **Tumpahan** | Rencana respons untuk tumpahan bahan kimia atau berbahaya, termasuk prosedur containment dan dekontaminasi. |
| 4 | `evacuation` | **Evakuasi** | Rencana evakuasi umum untuk semua jenis darurat, termasuk rute evakuasi dan titik kumpul. |
| 5 | `natural_disaster` | **Bencana Alam** | Rencana respons untuk gempa bumi, banjir, badai, tsunami, dan bencana alam lainnya. |
| 6 | `security` | **Keamanan** | Rencana respons untuk insiden keamanan, ancaman, intrusi, atau terorisme. |
| 7 | `other` | **Lainnya** | Rencana darurat lainnya yang tidak masuk kategori di atas. |

---

## 6. Business Rules

### BR-01: Numbering on Create (Plans & Drills)

- Nomor rencana darurat dan latihan darurat di-generate **saat record dibuat** (POST create).
- Format: `EMG-YYYY-NNNN` (contoh: `EMG-2026-0001`).
- Sumber: `NumberingService::generate('emergency', $actor, ...)`.
- Kedua resource (plans dan drills) berbagi numbering sequence yang sama (`module_name='emergency'`).
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `emergency`
  - `prefix`: `EMG`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `EMG-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Drill Scheduling

- Drill dapat dijadwalkan dengan `scheduled_date` (date, wajib).
- Status awal drill: `scheduled`.
- `emergency_plan_id` wajib — setiap drill harus terhubung ke sebuah rencana darurat.
- `observer_id` wajib — user yang mengobservasi pelaksanaan drill.
- Notifikasi `emergency.drill_scheduled` dikirim ke QHSSE team saat drill dijadwalkan.

### BR-03: Drill Execution

- Drill hanya dapat dieksekusi jika status = `scheduled`.
- Saat eksekusi, field berikut wajib diisi: `executed_date`, `result`, `participants_count`.
- `findings` dan `recommendations` opsional namun disarankan.
- Status berubah dari `scheduled` → `executed`.
- Jika `result` = `fail` atau `needs_improvement`, notifikasi `emergency.drill_failed` dikirim ke QHSSE Manager.
- Setelah `executed`, drill tidak dapat diedit kecuali oleh Super Admin / Admin.

### BR-04: Emergency Contacts JSON on Plans

- Field `emergency_contacts` pada tabel `emergency_plans` adalah JSON nullable.
- Dapat menyimpan array kontak tambahan spesifik untuk rencana tersebut, format:
  ```json
  [
    {"name": "Budi Santoso", "role": "Fire Warden", "phone": "+62-812-3456-7890"},
    {"name": "Sari Wijaya", "role": "First Aider", "phone": "+62-813-9876-5432"}
  ]
  ```
- Kontak ini bersifat tambahan — kontak darurat utama dikelola di tabel `emergency_contacts`.

### BR-05: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='emergency'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `emergency.plan_created` | EmergencyPlan | new_values: all fields |
| `emergency.plan_updated` | EmergencyPlan | changed fields only |
| `emergency.plan_deleted` | EmergencyPlan | soft delete |
| `emergency.drill_scheduled` | EmergencyDrill | new_values: all fields |
| `emergency.drill_executed` | EmergencyDrill | status change + execution fields |
| `emergency.drill_updated` | EmergencyDrill | changed fields only |
| `emergency.contact_created` | EmergencyContact | new_values: all fields |
| `emergency.contact_updated` | EmergencyContact | changed fields only |
| `emergency.file.uploaded` | ManagedFile | new_values |
| `emergency.file.deleted` | ManagedFile | soft delete |

### BR-06: Data Visibility by Scope

Data visibility mengikuti role scope (sesuai `CorePermissions::roleMap()`):

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter, Contractor | Plans in their site |
| `department` | Supervisor, Department Head | Plans/contacts in their department |
| `site` | QHSSE Officer | Plans/drills/contacts in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All data |

- Scope check dilakukan **server-side** di Controller/Policy, bukan di frontend.

---

## 7. Permission Keys

10 permission keys untuk modul Emergency Preparedness, dibagi dalam 3 resource groups:

### Resource Group 1: Emergency Plans (`emergency.plans.*`)

| # | Permission Key | Description |
|---|---|---|
| 1 | `emergency.plans.view` | View emergency plan list and detail. Scope-filtered. |
| 2 | `emergency.plans.create` | Create new emergency plan. Generates EMG number. |
| 3 | `emergency.plans.update` | Update emergency plan. |
| 4 | `emergency.plans.export` | Export emergency plan list to CSV. Scope-filtered. |

### Resource Group 2: Emergency Drills (`emergency.drills.*`)

| # | Permission Key | Description |
|---|---|---|
| 5 | `emergency.drills.view` | View emergency drill list and detail. Scope-filtered. |
| 6 | `emergency.drills.create` | Create/schedule new emergency drill. Generates EMG number. |
| 7 | `emergency.drills.update` | Update emergency drill (before execution). |
| 8 | `emergency.drills.execute` | Execute drill: set result, findings, recommendations. Status → executed. |
| 9 | `emergency.drills.export` | Export emergency drill list to CSV. Scope-filtered. |

### Resource Group 3: Emergency Contacts (`emergency.contacts.*`)

| # | Permission Key | Description |
|---|---|---|
| 10 | `emergency.contacts.view` | View emergency contact list. Scope-filtered. |
| 11 | `emergency.contacts.create` | Create new emergency contact. |
| 12 | `emergency.contacts.update` | Update emergency contact. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.

---

## 8. Role-Permission Matrix

### Emergency Plans

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

### Emergency Drills

| Role | `view` | `create` | `update` | `execute` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ | ❌ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ✅ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ✅ |

### Emergency Contacts

| Role | `view` | `create` | `update` |
|---|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ✅ | ✅ |
| Department Head | ✅ | ❌ | ❌ |
| Employee/Reporter | ✅ | ❌ | ❌ |
| Contractor | ✅ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ |
| Top Management | ✅ | ❌ | ❌ |

### Notes

- **Supervisor** dapat create/update kontak darurat (untuk department-nya) tetapi tidak dapat membuat/mengubah plans atau drills.
- **QHSSE Officer** memiliki akses penuh untuk scheduling dan execution drills.
- **Auditor** dan **Top Management** hanya view + export (read-only).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

4 event notifikasi untuk modul Emergency Preparedness:

### 9.1 `emergency.plan_created`

| Property | Value |
|---|---|
| **Trigger** | Emergency plan baru dibuat |
| **Recipients** | All users with role `QHSSE Officer` and `QHSSE Manager` in the same site scope |
| **Type** | `emergency.plan_created` |
| **Title (template)** | `Rencana Darurat Baru: {plan.plan_number}` |
| **Message (template)** | `Rencana darurat {plan.plan_number} - {plan.name} telah dibuat oleh {creator.name} untuk site {site.name}.` |
| **Action URL** | `/emergency-plans/{plan.id}` |
| **Module/Reference** | `module_name='emergency'`, `reference_id={plan.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `emergency.drill_scheduled`

| Property | Value |
|---|---|
| **Trigger** | Drill baru dijadwalkan (status = `scheduled`) |
| **Recipients** | QHSSE Officer, QHSSE Manager in the same site; the assigned observer |
| **Type** | `emergency.drill_scheduled` |
| **Title (template)** | `Latihan Darurat Dijadwalkan: {drill.drill_number}` |
| **Message (template)** | `Latihan darurat {drill.drill_number} untuk rencana {plan.name} dijadwalkan pada {drill.scheduled_date}. Observer: {observer.name}.` |
| **Action URL** | `/emergency-drills/{drill.id}` |
| **Module/Reference** | `module_name='emergency'`, `reference_id={drill.id}` |
| **Channel** | In-app + Email (if configured) |

### 9.3 `emergency.drill_executed`

| Property | Value |
|---|---|
| **Trigger** | Drill dieksekusi (status → `executed`) |
| **Recipients** | QHSSE Manager in the same site; the plan's contact_person |
| **Type** | `emergency.drill_executed` |
| **Title (template)** | `Latihan Darurat Dilaksanakan: {drill.drill_number}` |
| **Message (template)** | `Latihan darurat {drill.drill_number} telah dilaksanakan pada {drill.executed_date}. Hasil: {drill.result}. Jumlah peserta: {drill.participants_count}.` |
| **Action URL** | `/emergency-drills/{drill.id}` |
| **Module/Reference** | `module_name='emergency'`, `reference_id={drill.id}` |
| **Channel** | In-app + Email (if configured) |

### 9.4 `emergency.drill_failed`

| Property | Value |
|---|---|
| **Trigger** | Drill dieksekusi dengan result `fail` atau `needs_improvement` |
| **Recipients** | QHSSE Manager, QHSSE Officer in the same site |
| **Type** | `emergency.drill_failed` |
| **Title (template)** | `Latihan Darurat Memerlukan Perbaikan: {drill.drill_number}` |
| **Message (template)** | `Latihan darurat {drill.drill_number} menghasilkan hasil "{drill.result}". Temuan: {drill.findings}. Rekomendasi: {drill.recommendations}. Mohon tindak lanjut.` |
| **Action URL** | `/emergency-drills/{drill.id}` |
| **Module/Reference** | `module_name='emergency'`, `reference_id={drill.id}` |
| **Channel** | In-app + Email (if configured) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit.
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Recipient resolution: query users with target role + matching scope (site/department).

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `emergency` |
| **reference_id** | `emergency_plans.id` |
| **collection** | `evidence` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `emergency/{plan_id}/evidence/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `mp4`, `mov`, `avi` |
| **Max file size** | 25 MB per file |
| **Max files per record** | 20 |
| **Filename** | Original filename stored in `original_name`; UUID-based name in `stored_name` |

### 10.3 Access Rules

- **Upload**: User must have `emergency.plans.update`.
- **Download**: User must have `emergency.plans.view` and be within data scope.
- **Delete**: User must have `emergency.plans.update`. File access logged in audit trail.

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Rencana** | Count all emergency plans in scope | Number + icon |
| **Total Latihan** | Count all emergency drills in scope | Number |
| **Latihan Terjadwal** | Count drills where status = `scheduled` | Number, yellow badge |
| **Latihan Selesai** | Count drills where status = `executed` | Number, green |
| **Lulus** | Count drills where result = `pass` | Number, green |
| **Gagal/Perlu Perbaikan** | Count drills where result IN (`fail`, `needs_improvement`) | Number, red badge |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Drill Trend** | Line chart | Drill count by month (last 12 months), split by result |
| **Plans by Type** | Donut | Count by type (fire, medical, spill, evacuation, natural_disaster, security, other) |
| **Drills by Result** | Donut | Count by result (pass, fail, needs_improvement) |
| **Upcoming Drills** | Table | Next 5 scheduled drills with date, plan name, site |

### 11.3 Filters

Dashboard metrics support:
- Date range filter (default: current year)
- Site filter
- Type filter

---

## 12. Export Spec

### 12.1 CSV Export — Emergency Plans

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `emergency_plans_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `emergency.plans.export` |
| **Scope** | Follows user's data scope |
| **Filter** | Follows current list page filter parameters |

#### CSV Columns (Plans)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `plan_number` | EMG-YYYY-NNNN |
| 2 | `Nama` | `name` | |
| 3 | `Tipe` | `type` | fire/medical/spill/evacuation/natural_disaster/security/other |
| 4 | `Site` | `site.name` | Via `site_id` |
| 5 | `Deskripsi` | `description` | Truncated to 500 chars |
| 6 | `Kontak Person` | `contactPerson.name` | Via `contact_person_id` |
| 7 | `Peralatan` | `equipment_needed` | |
| 8 | `Dibuat Pada` | `created_at` | Format: YYYY-MM-DD HH:mm |

### 12.2 CSV Export — Emergency Drills

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `emergency_drills_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `emergency.drills.export` |
| **Scope** | Follows user's data scope |
| **Filter** | Follows current list page filter parameters |

#### CSV Columns (Drills)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `drill_number` | EMG-YYYY-NNNN |
| 2 | `Rencana Darurat` | `emergencyPlan.plan_number` | |
| 3 | `Site` | `site.name` | |
| 4 | `Tanggal Terjadwal` | `scheduled_date` | Format: YYYY-MM-DD |
| 5 | `Tanggal Eksekusi` | `executed_date` | Format: YYYY-MM-DD, nullable |
| 6 | `Peserta` | `participants_count` | |
| 7 | `Observer` | `observer.name` | |
| 8 | `Hasil` | `result` | pass/fail/needs_improvement |
| 9 | `Status` | `status` | scheduled/executed |
| 10 | `Temuan` | `findings` | Truncated to 500 chars |
| 11 | `Rekomendasi` | `recommendations` | Truncated to 500 chars |

---

## 13. Acceptance Criteria

1. User dengan permission `emergency.plans.create` dapat membuat rencana darurat baru dan nomor `EMG-YYYY-NNNN` ter-generate otomatis.
2. Form rencana darurat menampilkan 7 tipe (fire, medical, spill, evacuation, natural_disaster, security, other) dengan field prosedur respons, prosedur eskalasi, kontak person, dan peralatan.
3. User dengan permission `emergency.drills.create` dapat menjadwalkan latihan darurat yang terhubung ke rencana darurat.
4. User dengan permission `emergency.drills.execute` dapat mencatat pelaksanaan drill dengan result (pass/fail/needs_improvement), findings, dan recommendations.
5. Halaman detail rencana darurat menampilkan kontak darurat (JSON), drills terkait, dan evidence files.
6. Halaman detail drill menampilkan hasil, temuan, rekomendasi, dan link ke rencana darurat terkait.
7. User dapat membuat, mengedit, dan menonaktifkan kontak darurat per site.
8. Notifikasi dikirim saat drill dijadwalkan, dieksekusi, dan saat hasil fail/needs_improvement.
9. Audit trail mencatat semua event kritikal: plan_created, plan_updated, drill_scheduled, drill_executed, contact_created, contact_updated.
10. CSV export berfungsi untuk plans dan drills dengan filter yang aktif.

---

## 14. Open Questions

| # | Question | Status |
|---|---|---|
| 1 | Apakah perlu integrasi langsung dengan modul Communication untuk broadcast SMS/Email ke kontak darurat? | Open — di Phase 15 cukup referensi data, integrasi broadcast di Phase 18. |
| 2 | Apakah `emergency_contacts` JSON pada plans harus divalidasi formatnya di backend? | Open — validasi struktur JSON disarankan di Form Request. |
| 3 | Apakah perlu recurring schedule untuk drills (otomatis menjadwalkan drill berkala)? | Deferred — Phase 15 hanya mendukung manual scheduling. |
| 4 | Apakah drill dengan result `fail` wajib membuka CAPA? | Open — disarankan namun tidak wajib di Phase 15. |

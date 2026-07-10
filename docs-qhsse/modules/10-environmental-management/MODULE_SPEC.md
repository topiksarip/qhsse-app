# Module Spec — Environmental Management

> **Module ID:** `10-environmental-management`
> **Module Code (numbering):** `environment`
> **Number Prefix:** `ENV`
> **Phase:** Phase 10 — Environmental Management
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul Environmental Management menyediakan sistem pencatatan dan pengelolaan catatan lingkungan (environmental records) yang mencakup limbah, tumpahan, emisi, kebisingan, monitoring kualitas air, dan kategori lainnya. Modul ini mendukung deteksi exceedance otomatis — jika nilai pengukuran melebihi batas yang ditetapkan, sistem menandai record sebagai exceedance dan menyorotnya secara visual.

Tujuan utama:

- Memungkinkan **QHSSE Officer, Supervisor, dan Employee** mencatat berbagai jenis pengamatan lingkungan (limbah, spill, emisi, kebisingan, monitoring air) dalam satu sistem terpadu.
- Memastikan setiap catatan memiliki **nomor unik** (`ENV-YYYY-NNNN`) yang di-generate otomatis pada saat create.
- Mendeteksi **exceedance** secara otomatis: jika `measured_value > limit_value` → `is_exceedance = true`. Record dengan exceedance disorot dengan warna MERAH di halaman daftar.
- Menyediakan **form dinamis** dengan field spesifik per jenis catatan (waste, spill, emission, noise, water_monitoring, other).
- Menghubungkan catatan lingkungan ke modul **CAPA** (Corrective and Preventive Action) ketika ditemukan exceedance atau masalah yang memerlukan tindakan korektif.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal.
- Mengelola **evidence attachments** (foto, dokumen) melalui File Service core dengan collection `evidence`.
- Menyediakan **dashboard metrics** dan **export CSV** untuk analisis dan pelaporan manajemen.

---

## 2. Dependency

### Core Foundation (Phase 0 — complete)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 6 permission keys `environment.records.*` |
| **NumberingService** | Generate `ENV-YYYY-NNNN` on create |
| **FileService** | Upload/download evidence files via `managed_files` table |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='environment'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **ExportService** | CSV export via `environment.records.export` permission |
| **ListQuery** | Paginated search/filter/sort |
| **CsvExporter** | Stream CSV export |
| **MasterData** | Sites, Areas, Departments |

### Cross-Module (existing modules)

| Module | Relationship |
|---|---|
| `04-capa-action-tracking` | Environmental record dapat ditautkan ke CAPA melalui `capa_action_id` FK (1:0..1) |
| `14-legal-compliance` | Exceedance dapat dikaitkan dengan persyaratan regulasi lingkungan |
| `20-admin-master-data` | Sites, Areas, Departments digunakan untuk lokasi |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini (sesuai `RolesAndPermissionsSeeder`):

| # | Role | Deskripsi Peran dalam Environmental Management |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Investigasi, close record. Scope: all sites. Approve tindak lanjut. |
| 4 | **QHSSE Officer** | Create, update, investigate, close record. Scope: assigned site(s). |
| 5 | **Supervisor** | Create, update record. Scope: department. |
| 6 | **Department Head** | View record di department-nya. Scope: department. |
| 7 | **Employee/Reporter** | Create, update own record. Scope: own. |
| 8 | **Contractor** | Create, update own record. Scope: company (contractor company). |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Environmental Record CRUD

- **Create** — Form pembuatan catatan lingkungan. Nomor `ENV-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal: `recorded`. Field dinamis muncul berdasarkan `type` yang dipilih.
- **List** — Halaman daftar dengan search (nomor, judul), filter (site, type, status, exceedance only, date range), pagination (default 15 per page), dan tombol Export CSV. Record dengan `is_exceedance = true` disorot dengan warna MERAH.
- **Detail** — Halaman detail menampilkan: nomor, judul, deskripsi, tipe, lokasi (site/area), tanggal kejadian, nilai pengukuran, batas, status exceedance, status record, CAPA terkait (jika ada), timeline aktivitas, komentar, audit trail, dan evidence files.
- **Update** — Edit record. Bisa diedit jika status `recorded` atau `investigated`.
- **Delete** — Soft delete. Hanya Super Admin / Admin. Tidak bisa delete record yang sudah `closed`.

### 4.2 Type-Specific Fields

Form menampilkan field dinamis berdasarkan `type` yang dipilih:

| Type | Label | Type-Specific Fields | Deskripsi |
|---|---|---|---|
| `waste` | Limbah | `waste_type`, `quantity`, `unit`, `disposal_method` | Pencatatan pembuangan limbah (B3, non-B3, dll) |
| `spill` | Tumpahan | `material`, `volume`, `unit`, `containment` | Tumpahan bahan kimia, minyak, atau cairan lain |
| `emission` | Emisi | `parameter`, `measured_value`, `unit`, `limit_value` | Emisi gas/partikulat dengan deteksi exceedance |
| `noise` | Kebisingan | `measured_value` (db_level), `unit` (dB), `location`, `occurred_at` (time) | Pengukuran tingkat kebisingan |
| `water_monitoring` | Monitoring Air | `parameter`, `measured_value`, `unit`, `limit_value` | Monitoring kualitas air dengan deteksi exceedance |
| `other` | Lainnya | `measured_value`, `unit`, `limit_value` (semua opsional) | Pencatatan pengamatan lingkungan lainnya |

> **Catatan:** Field `waste_type`, `quantity`, `disposal_method`, `material`, `volume`, `containment`, `parameter`, `location` disimpan sebagai kolom nullable pada tabel `environmental_records` untuk fleksibilitas. Field yang tidak relevan untuk tipe tertentu disembunyikan di UI.

### 4.3 Exceedance Detection

- Sistem secara otomatis menghitung `is_exceedance` saat create/update.
- Logika: jika `measured_value` dan `limit_value` keduanya tidak null, dan `measured_value > limit_value` → `is_exceedance = true`.
- Jika salah satu null atau `measured_value <= limit_value` → `is_exceedance = false`.
- Exceedance dapat memicu notifikasi ke QHSSE Manager/Officer.
- Record dengan exceedance disorot MERAH di halaman Index dan badge "Exceedance" ditampilkan.

### 4.4 Simple Status Workflow

Tidak ada workflow kompleks. Status sederhana:

| Status | Deskripsi | Siapa yang bisa mengubah |
|---|---|---|
| `recorded` | Record baru dibuat, belum ditindaklanjuti | Reporter, Officer |
| `investigated` | Record sedang dalam investigasi | QHSSE Officer/Manager |
| `action_open` | CAPA telah dibuka untuk record ini | QHSSE Officer/Manager |
| `closed` | Record selesai, tidak bisa diedit lagi | QHSSE Officer/Manager |

### 4.5 Evidence Management

- Upload file bukti (foto, video, dokumen) melalui File Service core.
- Collection: `evidence`.
- Multiple files per environmental record.
- Download melalui authorized endpoint (permission check).
- Tidak bisa hapus file setelah status `closed`.

### 4.6 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record.
- Activity log otomatis mencatat: create, update, investigate, close, exceedance detected, field changes.
- Timeline ditampilkan di halaman detail.

### 4.7 Notification

- 3 event notifikasi: `environment.exceedance_detected`, `environment.investigated`, `environment.closed`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.8 CAPA Linkage

- Record dapat ditautkan ke CAPA melalui `capa_action_id` FK.
- Saat CAPA dibuka dari record (status → `action_open`), `capa_action_id` di-set.
- Halaman detail menampilkan informasi CAPA terkait dengan link ke modul CAPA.

### 4.9 Dashboard & Reporting

- Dashboard widget: total records, total exceedances, breakdown by type/site/status, trend bulanan.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Tipe Catatan

Enam tipe environmental record, disimpan sebagai enum pada kolom `type`:

| # | Code | Nama (ID) | Deskripsi |
|---|---|---|---|
| 1 | `waste` | **Limbah** | Pencatatan pembuangan limbah (B3, non-B3, medis, dll). Termasuk jenis limbah, jumlah, dan metode pembuangan. |
| 2 | `spill` | **Tumpahan** | Tumpahan bahan (kimia, minyak, limbah cair) ke lingkungan. Termasuk jenis material, volume, dan tindakan penahanan. |
| 3 | `emission` | **Emisi** | Pengukuran emisi gas atau partikulat. Termasuk parameter (SOx, NOx, CO, PM), nilai pengukuran, dan batas regulasi. |
| 4 | `noise` | **Kebisingan** | Pengukuran tingkat kebisingan (decibel) di lokasi tertentu pada waktu tertentu. |
| 5 | `water_monitoring` | **Monitoring Air** | Monitoring kualitas air (pH, TSS, BOD, COD, logam berat). Termasuk parameter, nilai, dan batas regulasi. |
| 6 | `other` | **Lainnya** | Pencatatan pengamatan lingkungan lainnya yang tidak masuk kategori di atas. |

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor environmental record di-generate **saat record dibuat** (POST create).
- Format: `ENV-YYYY-NNNN` (contoh: `ENV-2026-0001`).
- Sumber: `NumberingService::generate('environment', $actor, ...)`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `environment`
  - `prefix`: `ENV`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `ENV-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat; service melakukan retry.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Exceedance Auto-Detection

- Saat create atau update, sistem menghitung `is_exceedance`:
  - Jika `measured_value` AND `limit_value` keduanya NOT NULL:
    - Jika `measured_value > limit_value` → `is_exceedance = true`
    - Jika `measured_value <= limit_value` → `is_exceedance = false`
  - Jika salah satu NULL → `is_exceedance = false`
- Deteksi dilakukan di model observer atau service layer, **bukan** di database trigger.
- Jika exceedance baru terdeteksi (false → true), sistem mengirim notifikasi ke QHSSE Manager/Officer.
- Exceedance badge ditampilkan di UI Index dan Show.

### BR-03: Type-Specific Field Validation

Saat `type` = `waste`:
- `waste_type` wajib (string, max:255) — misal: "Limbah B3", "Limbah Medis"
- `quantity` wajib (numeric, min:0)
- `disposal_method` wajib (string, max:255) — misal: "Incinerasi", "TPA", "Pihak Ketiga"

Saat `type` = `spill`:
- `material` wajib (string, max:255) — misal: "Minyak", "Asam Sulfat"
- `volume` wajib (numeric, min:0)
- `containment` wajib (string, max:255) — misal: "Boom oil", "Absorbent"

Saat `type` = `emission`:
- `parameter` wajib (string, max:255) — misal: "SOx", "NOx", "CO", "PM10"
- `measured_value` wajib (decimal)
- `limit_value` wajib (decimal) — batas regulasi

Saat `type` = `noise`:
- `measured_value` wajib (decimal) — tingkat kebisingan dalam dB
- `unit` wajib (default: "dB")
- `location` wajib (string, max:255) — lokasi pengukuran spesifik
- `occurred_at` wajib (timestamp) — waktu pengukuran

Saat `type` = `water_monitoring`:
- `parameter` wajib (string, max:255) — misal: "pH", "TSS", "BOD", "COD"
- `measured_value` wajib (decimal)
- `limit_value` wajib (decimal) — batas regulasi

Saat `type` = `other`:
- Semua field opsional. `description` wajib.

### BR-04: Status Transitions

- `recorded` → `investigated`: QHSSE Officer/Manager memulai investigasi. Tidak memerlukan reason.
- `investigated` → `action_open`: QHSSE Officer/Manager membuka CAPA. Meng-set `capa_action_id`.
- `action_open` → `closed`: QHSSE Officer/Manager menutup record. Memerlukan reason (min:10 karakter).
- `recorded` → `closed`: Direct close untuk record tanpa exceedance. Memerlukan reason.
- `investigated` → `closed`: Close setelah investigasi tanpa CAPA. Memerlukan reason.
- Setelah `closed`, record menjadi read-only. Tidak bisa edit, tidak bisa hapus evidence.

### BR-05: Close Requires Reason

- Semua transisi menuju `closed` memerlukan field `reason` (wajib, text, min:10 karakter).
- Reason disimpan di `activity_logs` dan `audit_logs`.
- Notifikasi dikirim ke reporter dan stakeholder terkait.
- Setelah close, record menjadi read-only.

### BR-06: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='environment'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `environment.created` | EnvironmentalRecord | new_values: all fields |
| `environment.updated` | EnvironmentalRecord | changed fields only |
| `environment.investigated` | EnvironmentalRecord | status change |
| `environment.action_opened` | EnvironmentalRecord | status change + capa_action_id |
| `environment.closed` | EnvironmentalRecord | status change + reason |
| `environment.exceedance_detected` | EnvironmentalRecord | is_exceedance: false → true |
| `environment.deleted` | EnvironmentalRecord | soft delete |
| `environment.file.uploaded` | ManagedFile | new_values |
| `environment.file.deleted` | ManagedFile | soft delete |

### BR-07: Data Visibility by Scope

Data visibility mengikuti role scope (sesuai `CorePermissions::roleMap()`):

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter, Contractor | Only records they created |
| `department` | Supervisor, Department Head | Records in their department |
| `site` | QHSSE Officer | Records in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All records |

- Scope check dilakukan **server-side** di Controller/Policy, bukan di frontend.

---

## 7. Permission Keys

6 permission keys untuk modul Environmental Management:

| # | Permission Key | Description |
|---|---|---|
| 1 | `environment.records.view` | View environmental record list and detail. Scope-filtered. |
| 2 | `environment.records.create` | Create new environmental record. Generates ENV number. |
| 3 | `environment.records.update` | Update environmental record. Only `recorded` or `investigated` status. |
| 4 | `environment.records.investigate` | Investigate environmental record (recorded → investigated). QHSSE roles. |
| 5 | `environment.records.close` | Close environmental record (→ closed). Requires reason. |
| 6 | `environment.records.export` | Export environmental record list to CSV. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}` → `environment.records.*`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.

---

## 8. Role-Permission Matrix

| Role | `view` | `create` | `update` | `investigate` | `close` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Employee/Reporter | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |

### Notes

- **Supervisor** dapat create dan update record tetapi tidak dapat investigate atau close.
- **Department Head** hanya dapat view dan export.
- **Employee/Reporter** dan **Contractor** dapat create/update record miliknya sendiri (scope: own/company).
- **Auditor** dan **Top Management** hanya view + export (read-only).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

3 event notifikasi untuk modul Environmental Management:

### 9.1 `environment.exceedance_detected`

| Property | Value |
|---|---|
| **Trigger** | Sistem mendeteksi `measured_value > limit_value` saat create atau update |
| **Recipients** | All users with role `QHSSE Officer` and `QHSSE Manager` in the same site scope |
| **Type** | `environment.exceedance_detected` |
| **Title (template)** | `Exceedance Terdeteksi: {record.number}` |
| **Message (template)** | `Exceedance terdeteksi pada catatan lingkungan {record.number} - {record.title}. Nilai terukur: {measured_value} {unit}, batas: {limit_value} {unit}. Mohon lakukan investigasi.` |
| **Action URL** | `/environmental-records/{record.id}` |
| **Module/Reference** | `module_name='environment'`, `reference_id={record.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `environment.investigated`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager memulai investigasi (status: `recorded` → `investigated`) |
| **Recipients** | The original reporter (`record.reporter_id`) |
| **Type** | `environment.investigated` |
| **Title (template)** | `Catatan Lingkungan Sedang Dinvestigasi: {record.number}` |
| **Message (template)** | `Catatan lingkungan {record.number} - {record.title} sedang dalam proses investigasi oleh {investigator.name} ({investigator.role}).` |
| **Action URL** | `/environmental-records/{record.id}` |
| **Module/Reference** | `module_name='environment'`, `reference_id={record.id}` |
| **Channel** | In-app + Email (if configured) |

### 9.3 `environment.closed`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager menutup record (status → `closed`) |
| **Recipients** | Reporter, Supervisor of the department, Department Head |
| **Type** | `environment.closed` |
| **Title (template)** | `Catatan Lingkungan Ditutup: {record.number}` |
| **Message (template)** | `Catatan lingkungan {record.number} - {record.title} telah ditutup oleh {closer.name}. Alasan: {close_reason}.` |
| **Action URL** | `/environmental-records/{record.id}` |
| **Module/Reference** | `module_name='environment'`, `reference_id={record.id}` |
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
| **module_name** | `environment` |
| **reference_id** | `environmental_records.id` |
| **collection** | `evidence` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `environment/{record_id}/evidence/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `mp4`, `mov`, `avi` |
| **Max file size** | 25 MB per file |
| **Max files per record** | 20 |
| **Filename** | Original filename stored in `original_name`; UUID-based name in `stored_name` |

### 10.3 Access Rules

- **Upload**: User must have `environment.records.update` (or be the reporter of a `recorded` record).
- **Download**: User must have `environment.records.view` and be within data scope.
- **Delete**: User must have `environment.records.update` AND record status must NOT be `closed`. Once closed, evidence files **cannot be deleted** except by Super Admin / Admin.
- File access logged in audit trail.

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Records** | Count all environmental records in scope | Number + icon |
| **Total Exceedances** | Count where `is_exceedance = true` | Number, red badge |
| **Open Records** | Count where status NOT IN (`closed`) | Number |
| **Closed Records** | Count where status = `closed` | Number, green |
| **This Month** | Count created in current month | Number + trend arrow |
| **CAPA Linked** | Count where `capa_action_id IS NOT NULL` | Number |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Trend** | Line chart | Record count by month (last 12 months), split by type |
| **By Type** | Donut | Count by type (waste, spill, emission, noise, water, other) |
| **By Site** | Horizontal bar | Count by site (top 10) |
| **By Status** | Donut | Count by status (recorded, investigated, action_open, closed) |
| **Exceedance Rate** | Bar chart | Exceedance count by type |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Recent Records** | Nomor, Judul, Tipe, Site, Status, Exceedance, Created At | Last 10, scoped |
| **Open Exceedances** | Nomor, Judul, Tipe, Nilai, Batas, Site, Created At | is_exceedance=true, status open |

### 11.4 Filters

Dashboard metrics support:
- Date range filter (default: current year)
- Site filter
- Type filter
- Exceedance filter

---

## 12. Export Spec

### 12.1 CSV Export

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `environmental_records_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `environment.records.export` |
| **Scope** | Follows user's data scope (own/department/site/all) |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `record_number` | ENV-YYYY-NNNN |
| 2 | `Tipe` | `type` | waste/spill/emission/noise/water_monitoring/other |
| 3 | `Judul` | `title` | |
| 4 | `Deskripsi` | `description` | Truncated to 500 chars |
| 5 | `Site` | `site.name` | Via `site_id` |
| 6 | `Area` | `area.name` | Via `area_id`, nullable |
| 7 | `Tanggal Kejadian` | `occurred_at` | Format: YYYY-MM-DD HH:mm |
| 8 | `Nilai Terukur` | `measured_value` | |
| 9 | `Satuan` | `unit` | |
| 10 | `Batas` | `limit_value` | |
| 11 | `Exceedance` | `is_exceedance` | Yes/No |
| 12 | `Status` | `status` | recorded/investigated/action_open/closed |
| 13 | `CAPA` | `capa_action.number` | Via `capa_action_id`, nullable |
| 14 | `Dibuat Oleh` | `reporter.name` | |
| 15 | `Dibuat Pada` | `created_at` | Format: YYYY-MM-DD HH:mm |

---

## 13. Acceptance Criteria

1. User dengan permission `environment.records.create` dapat membuat environmental record baru dan nomor `ENV-YYYY-NNNN` ter-generate otomatis.
2. Form menampilkan field spesifik berdasarkan `type` yang dipilih (waste, spill, emission, noise, water_monitoring, other).
3. Saat `measured_value > limit_value`, `is_exceedance` otomatis di-set ke `true` dan record disorot MERAH di Index.
4. User dapat memfilter Index berdasarkan tipe dan melihat hanya record dengan exceedance.
5. QHSSE Officer/Manager dapat mengubah status dari `recorded` ke `investigated`, `action_open`, dan `closed`.
6. Close memerlukan reason (min:10 karakter). Setelah closed, record read-only.
7. Record dapat ditautkan ke CAPA melalui `capa_action_id`. Halaman Show menampilkan link ke CAPA.
8. Evidence files dapat di-upload dan di-download. Tidak bisa dihapus setelah status `closed`.
9. Audit trail mencatat semua event kritikal: create, update, investigate, close, exceedance_detected.
10. CSV export berfungsi dengan filter yang aktif.
11. Data visibility di-enforce server-side berdasarkan role scope (own/department/site/all).
12. Semua label UI dalam Bahasa Indonesia.

---

## 14. Open Questions

1. **Regulatory limits**: Apakah batas regulasi (limit_value) perlu di-seed per parameter, atau selalu diinput manual per record? → Untuk Phase 10, manual. Future: tabel master regulatory_limits.
2. **Scheduled monitoring**: Apakah perlu fitur reminder untuk monitoring berkala (misal: air monitoring mingguan)? → Defer to future phase.
3. **Integration with Legal**: Apakah exceedance otomatis membuat record di modul Legal Compliance? → Phase 10: manual link only.
4. **Reopen closed record**: Apakah record yang sudah closed dapat di-reopen? → Phase 10: tidak. Status `closed` adalah terminal.
5. **Waste tracking number**: Apakah limbah B3 perlu nomor manifest tersendiri? → Simpan di `description` atau `metadata` untuk Phase 10. Future: kolom khusus `manifest_number`.

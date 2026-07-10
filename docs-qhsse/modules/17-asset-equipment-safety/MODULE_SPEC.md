# Module Spec — Asset & Equipment Safety

> **Module ID:** `17-asset-equipment-safety`
> **Module Code (numbering):** `asset`
> **Number Prefix:** `AST`
> **Phase:** Phase 17 — Asset & Equipment Safety
> **Status:** Ready for coding
> **Depends on:** Core Foundation (Phase 0), Inspection (Phase 5), Document Control (Phase 7), CAPA (Phase 4)

---

## 1. Tujuan Modul

Modul Asset & Equipment Safety menyediakan sistem manajemen aset dan peralatan keselamatan (safety-critical equipment) secara end-to-end. Modul ini mencakup registrasi aset, pelacakan sertifikat (certificate expiry tracking), dan jadwal inspeksi/pemeriksaan berkala untuk memastikan semua peralatan dalam kondisi aman dan sesuai regulasi.

Tujuan utama:

- Memungkinkan **QHSSE team** mendaftarkan aset dengan nomor unik `AST-YYYY-NNNN` yang di-generate otomatis pada saat create.
- Menyediakan kategori aset: `equipment`, `machinery`, `vehicle`, `safety_equipment`, `fire_equipment`, `lifting`, `other`.
- Mengelola **sertifikat aset** (asset certificates) dengan pelacakan masa berlaku — sertifikat yang `expiry_date < now()` otomatis berstatus `expired`.
- Menandai aset **safety-critical** (`safety_critical = true`) untuk highlight visual dan prioritas monitoring.
- Mengelola **inspeksi aset** (asset inspections) dengan result: `pass`, `fail`, `maintenance_required`, serta jadwal inspeksi berikutnya (`next_inspection_date`).
- Menghubungkan inspeksi yang gagal ke **CAPA action** (modul `04-capa-action-tracking`).
- Mengirim **notifikasi** saat sertifikat akan kedaluwarsa (30 hari, 7 hari) dan saat inspeksi jatuh tempo.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal (create, update, certificate CRUD, inspection CRUD).
- Menyediakan **dashboard metrics** dan **export CSV** untuk analisis dan pelaporan manajemen.
- Mendukung pelacakan **warranty expiry** dan **purchase/installation dates**.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 10 permission keys: `asset.management.*` (4) + `asset.certificates.*` (3) + `asset.inspections.*` (2) + shared `core.files.*`, `core.comments.*` |
| **NumberingService** | Generate `AST-YYYY-NNNN` on create |
| **FileService** | Upload/download certificate files via `managed_files` table |
| **NotificationService** | In-app + email notifications for certificate expiry, inspection due |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='asset'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **CsvExporter** | CSV export via `asset.management.export` permission |
| **ListQuery** | Paginated list with search, filter, sort |
| **MasterData** | Sites, Areas, Departments, Users (for inspector selection) |

### Cross-Module (existing / future phases)

| Module | Relationship |
|---|---|
| `05-inspection-checklist` | Asset inspections may reference inspection checklists from module 05. Future: auto-generate inspection schedule from asset registration. |
| `07-document-control` | Certificate files stored as managed files. Future: link certificates to controlled document versions. |
| `04-capa-action-tracking` | Failed inspections (`result='fail'` or `maintenance_required`) can link to CAPA actions via "Create CAPA" button. CAPA record has `source_module='asset_inspection'` and `source_reference_id=asset_inspections.id`. |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini (sesuai `RolesAndPermissionsSeeder`):

| # | Role | Deskripsi Peran dalam Asset & Equipment Safety |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Create, update, export assets. Manage certificates and inspections. Scope: all sites. |
| 4 | **QHSSE Officer** | Create, update assets. Create/update certificates and inspections. Scope: assigned site(s). |
| 5 | **Supervisor** | View assets di department-nya. Tidak create/edit. Scope: department. |
| 6 | **Department Head** | View assets di department-nya. Menerima notifikasi sertifikat kedaluwarsa. Scope: department. |
| 7 | **Employee/Reporter** | View assets yang relevan (terbatas). Tidak create/edit. Scope: own. |
| 8 | **Contractor** | View assets terkait company-nya. Tidak create/edit. Scope: company. |
| 9 | **Auditor** | View semua assets dalam scope. Export. Tidak create/edit (read-only, independent verification). |
| 10 | **Top Management** | View dashboard & asset report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Asset CRUD

- **Create** — Form pembuatan aset. Nomor `AST-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal: `active`.
- **List** — Halaman list dengan search (nomor, nama, serial number), filter (site, category, status, safety_critical), pagination (default 15 per page), dan tombol Export CSV. Highlight safety-critical assets dengan badge khusus.
- **Detail (Show)** — Halaman detail menampilkan: nomor, nama, kategori, serial number, model, manufacturer, site, area, department, tanggal pembelian/installasi, masa garansi, status, safety_critical flag, certificates tab, inspections tab, linked CAPA, comments, activity log, audit trail.
- **Update** — Edit record. Semua field dapat diedit selama status `active`.
- **Status Management** — Asset status: `active`, `inactive`, `decommissioned`. Status `decommissioned` mengunci record dari perubahan lebih lanjut.

### 4.2 Certificate Management

- **Create Certificate** — Tambah sertifikat ke aset (certificate_type, certificate_number, issued_date, expiry_date, issuing_body, file upload).
- **Certificate Expiry Tracking** — Sertifikat dengan `expiry_date < now()` otomatis berstatus `expired`. Sertifikat dengan `expiry_date` dalam 30 hari berstatus `expiring_soon` (warning YELLOW). Sertifikat dengan `expiry_date` dalam 7 hari berstatus `expiring_critical` (warning RED).
- **Certificate Status** — `valid` (default), `expired`, `expiring_soon`, `expiring_critical`.
- **File Upload** — File sertifikat diupload via File Service core, collection `certificate`.
- **List View** — Sertifikat ditampilkan pada tab Certificates di halaman Show asset, dengan badge warna sesuai status.

### 4.3 Inspection Management

- **Create Inspection** — Tambah hasil inspeksi aset (inspection_date, inspector_id, result, notes, next_inspection_date).
- **Inspection Result**:
  - **Pass** — Aset lulus inspeksi. Dapat lanjut operasi.
  - **Fail** — Aset gagal inspeksi. Wajib ditindaklanjuti. Tombol "Create CAPA" muncul.
  - **Maintenance Required** — Aet memerlukan maintenance. Dapat tetap beroperasi dengan pengawasan.
- **Next Inspection Scheduling** — Field `next_inspection_date` untuk penjadwalan inspeksi berikutnya.
- **CAPA Link** — Inspeksi dengan result `fail` dapat dihubungkan ke CAPA action.

### 4.4 Safety-Critical Asset Highlight

- Aset dengan `safety_critical = true` ditampilkan dengan highlight visual (RED border / badge).
- Safety-critical assets dengan sertifikat expired atau inspeksi overdue mendapat prioritas notifikasi.
- Dashboard widget khusus untuk safety-critical assets yang memerlukan perhatian.

### 4.5 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record aset.
- Activity log otomatis mencatat: create, update, certificate CRUD, inspection CRUD, CAPA link.
- Timeline ditampilkan di halaman detail aset.

### 4.6 Notification

- 4 event notifikasi: `asset.certificate.expiring_soon`, `asset.certificate.expiring_critical`, `asset.certificate.expired`, `asset.inspection.due`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.7 Dashboard & Reporting

- Dashboard widget: total assets, safety-critical count, certificates expiring soon, certificates expired, inspections overdue, breakdown by category/site/status.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Kategori Aset

Tujuh kategori aset didukung:

| # | Category Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `equipment` | **Equipment** | Peralatan umum yang digunakan dalam operasi. |
| 2 | `machinery` | **Machinery** | Mesin produksi dan peralatan berat. |
| 3 | `vehicle` | **Vehicle** | Kendaraan operasional (mobil, truk, forklift). |
| 4 | `safety_equipment` | **Safety Equipment** | Peralatan keselamatan (APD, harness, helmet). |
| 5 | `fire_equipment` | **Fire Equipment** | Peralatan pemadam kebakaran (APAR, hydrant, detektor). |
| 6 | `lifting` | **Lifting Equipment** | Peralatan angkat (crane, hoist, sling, chain block). |
| 7 | `other` | **Other** | Aset lain yang tidak masuk kategori di atas. |

### Asset Status

| Status | Code | Description |
|---|---|---|
| **Active** | `active` | Aset aktif beroperasi. Default. |
| **Inactive** | `inactive` | Aset tidak aktif sementara (mis. maintenance, standby). |
| **Decommissioned** | `decommissioned` | Aset telah dinyatakan tidak beroperasi permanen. Read-only. |

### Certificate Status

| Status | Code | Color | Condition |
|---|---|---|---|
| **Valid** | `valid` | `green` | `expiry_date` is null OR `expiry_date >= now() + 30 days` |
| **Expiring Soon** | `expiring_soon` | `yellow` | `expiry_date` dalam 8–30 hari dari sekarang |
| **Expiring Critical** | `expiring_critical` | `red` | `expiry_date` dalam 1–7 hari dari sekarang |
| **Expired** | `expired` | `red` | `expiry_date < now()` |

### Inspection Result

| Result | Code | Color | CAPA Required? |
|---|---|---|---|
| **Pass** | `pass` | `green` | ❌ Tidak |
| **Fail** | `fail` | `red` | ✅ Wajib |
| **Maintenance Required** | `maintenance_required` | `yellow` | ⚠️ Optional |

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor aset di-generate **saat record dibuat** (POST create).
- Format: `AST-YYYY-NNNN` (contoh: `AST-2026-0001`).
- Sumber: `NumberingService::generate('asset', ...)`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `asset`
  - `prefix`: `AST`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `AST-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Certificate Expiry Tracking

- Status sertifikat dihitung otomatis berdasarkan `expiry_date`:
  - `expired` — jika `expiry_date < now()`
  - `expiring_critical` — jika `expiry_date` dalam 1–7 hari
  - `expiring_soon` — jika `expiry_date` dalam 8–30 hari
  - `valid` — jika `expiry_date >= now() + 30 days` atau `expiry_date` is null
- Perhitungan dilakukan via accessor di Model atau scheduled job yang update `status` field.
- Scheduled job (`AssetCertificateStatusJob`) berjalan harian untuk update status sertifikat.
- Saat sertifikat berubah ke `expired`, notifikasi dikirim ke QHSSE Manager dan Department Head.

### BR-03: Safety-Critical Asset Priority

- Aset dengan `safety_critical = true` mendapat:
  - Highlight visual RED pada list page dan show page.
  - Prioritas pada dashboard widget.
  - Notifikasi lebih agresif untuk sertifikat kedaluwarsa (30 hari, 14 hari, 7 hari, 1 hari).
  - Inspeksi overdue mendapat notifikasi escalation.

### BR-04: Decommission Locks Record

- Aset dengan status `decommissioned` tidak dapat diedit.
- Certificates dan inspections tidak dapat ditambahkan ke aset yang `decommissioned`.
- Decommission hanya dapat dilakukan oleh Super Admin, Admin, atau QHSSE Manager.

### BR-05: Failed Inspection Requires CAPA

- Inspeksi dengan `result = 'fail'` harus terhubung ke CAPA action sebelum aset dapat kembali beroperasi.
- Tombol "Create CAPA" muncul pada inspection record dengan result `fail`.
- Aset dengan inspeksi `fail` yang belum linked to CAPA ditandai dengan warning di list page.

### BR-06: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='asset'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `asset.created` | Asset record | new_values: all fields |
| `asset.updated` | Asset record | changed fields only |
| `asset.decommissioned` | Asset record | status change |
| `asset.deleted` | Asset record | soft delete |
| `asset.certificate.created` | AssetCertificate | new_values |
| `asset.certificate.updated` | AssetCertificate | changed fields |
| `asset.certificate.expired` | AssetCertificate | status change |
| `asset.inspection.created` | AssetInspection | new_values |
| `asset.inspection.updated` | AssetInspection | changed fields |
| `asset.inspection.capa_linked` | AssetInspection | capa_action_id change |
| `asset.file.uploaded` | ManagedFile | new_values |
| `asset.file.downloaded` | ManagedFile | metadata: user, ip |
| `asset.exported` | Asset | metadata: user, filters |

### BR-07: Data Visibility by Scope

Data visibility mengikuti role scope:

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter | Assets yang relevan dengan department-nya (read-only) |
| `department` | Supervisor, Department Head | Assets di department-nya |
| `site` | QHSSE Officer | Assets di assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All assets |

- Scope check dilakukan **server-side** di Controller/Policy.
- Contractor hanya melihat assets terkait company-nya.

---

## 7. Permission Keys

### 7.1 Asset Management Permissions (4 keys)

| # | Permission Key | Description |
|---|---|---|
| 1 | `asset.management.view` | View asset list and detail. Scope-filtered. |
| 2 | `asset.management.create` | Create new asset record. Generates AST number. |
| 3 | `asset.management.update` | Update asset record. Only `active` status. |
| 4 | `asset.management.export` | Export asset list to CSV. Scope-filtered. |

### 7.2 Asset Certificates Permissions (3 keys)

| # | Permission Key | Description |
|---|---|---|
| 5 | `asset.certificates.view` | View certificates for an asset. |
| 6 | `asset.certificates.create` | Create new certificate for an asset. |
| 7 | `asset.certificates.update` | Update certificate. Edit type, number, dates, file. |

### 7.3 Asset Inspections Permissions (2 keys)

| # | Permission Key | Description |
|---|---|---|
| 8 | `asset.inspections.view` | View inspections for an asset. |
| 9 | `asset.inspections.create` | Create new inspection record for an asset. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{resource}.{action}` → `asset.management.*` + `asset.certificates.*` + `asset.inspections.*`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.

---

## 8. Role-Permission Matrix

### 8.1 Asset Management Permissions

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

### 8.2 Asset Certificates Permissions

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

### 8.3 Asset Inspections Permissions

| Role | `view` | `create` |
|---|:---:|:---:|
| Super Admin | ✅ | ✅ |
| Admin | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ |
| Supervisor | ✅ | ❌ |
| Department Head | ✅ | ❌ |
| Employee/Reporter | ✅ | ❌ |
| Contractor | ❌ | ❌ |
| Auditor | ✅ | ❌ |
| Top Management | ✅ | ❌ |

### Notes

- **QHSSE Officer** dan **QHSSE Manager** adalah roles utama yang dapat membuat dan mengelola assets, certificates, dan inspections.
- **Supervisor** dan **Department Head** dapat melihat assets, certificates, dan inspections di department-nya tetapi tidak dapat create/edit.
- **Auditor** role memiliki view-only akses untuk independent verification.
- **Contractor** tidak dapat melihat certificates dan inspections (informasi sensitif internal).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

4 event notifikasi untuk modul Asset & Equipment Safety:

### 9.1 `asset.certificate.expiring_soon`

| Property | Value |
|---|---|
| **Trigger** | Scheduled job mendeteksi sertifikat dengan `expiry_date` dalam 30 hari |
| **Recipients** | QHSSE Manager, QHSSE Officer (assigned site), Department Head |
| **Type** | `asset.certificate.expiring_soon` |
| **Title (template)** | `Sertifikat Aset Akan Kedaluwarsa: {certificate.certificate_number}` |
| **Message (template)** | `Sertifikat {certificate.certificate_type} ({certificate.certificate_number}) untuk aset {asset.asset_number} - {asset.name} akan kedaluwarsa pada {certificate.expiry_date}. Mohon segera lakukan perpanjangan.` |
| **Action URL** | `/assets/{asset.id}?tab=certificates` |
| **Module/Reference** | `module_name='asset'`, `reference_id={asset.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `asset.certificate.expiring_critical`

| Property | Value |
|---|---|
| **Trigger** | Scheduled job mendeteksi sertifikat dengan `expiry_date` dalam 7 hari |
| **Recipients** | QHSSE Manager, QHSSE Officer (assigned site), Department Head, Top Management (if safety_critical) |
| **Type** | `asset.certificate.expiring_critical` |
| **Title (template)** | `PERHATIAN: Sertifikat Aset Kedaluwarsa dalam 7 Hari` |
| **Message (template)** | `Sertifikat {certificate.certificate_type} ({certificate.certificate_number}) untuk aset {asset.asset_number} - {asset.name} akan kedaluwarsa dalam 7 hari pada {certificate.expiry_date}. Tindakan segera diperlukan!` |
| **Action URL** | `/assets/{asset.id}?tab=certificates` |
| **Module/Reference** | `module_name='asset'`, `reference_id={asset.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.3 `asset.certificate.expired`

| Property | Value |
|---|---|
| **Trigger** | Scheduled job mendeteksi sertifikat dengan `expiry_date < now()` |
| **Recipients** | QHSSE Manager, QHSSE Officer (assigned site), Department Head |
| **Type** | `asset.certificate.expired` |
| **Title (template)** | `Sertifikat Aset Kedaluwarsa: {certificate.certificate_number}` |
| **Message (template)** | `Sertifikat {certificate.certificate_type} ({certificate.certificate_number}) untuk aset {asset.asset_number} - {asset.name} telah KEDALUWARSA pada {certificate.expiry_date}. Aset mungkin tidak boleh digunakan hingga sertifikat diperpanjang.` |
| **Action URL** | `/assets/{asset.id}?tab=certificates` |
| **Module/Reference** | `module_name='asset'`, `reference_id={asset.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.4 `asset.inspection.due`

| Property | Value |
|---|---|
| **Trigger** | Scheduled job mendeteksi aset dengan `next_inspection_date` dalam 7 hari |
| **Recipients** | QHSSE Officer (assigned site), Supervisor of department |
| **Type** | `asset.inspection.due` |
| **Title (template)** | `Inspeksi Aset Jatuh Tempo: {asset.asset_number}` |
| **Message (template)** | `Inspeksi berikutnya untuk aset {asset.asset_number} - {asset.name} dijadwalkan pada {next_inspection_date}. Mohon segera lakukan inspeksi.` |
| **Action URL** | `/assets/{asset.id}?tab=inspections` |
| **Module/Reference** | `module_name='asset'`, `reference_id={asset.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured, safety_critical only) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit.
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Scheduled job (`AssetCertificateStatusJob`) berjalan setiap hari pukul 06:00 untuk update status sertifikat dan kirim notifikasi.
- Scheduled job (`AssetInspectionDueJob`) berjalan setiap hari pukul 06:30 untuk cek inspeksi jatuh tempo.

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `asset` |
| **reference_id** | `assets.id` (for asset-level files) or `asset_certificates.id` (for certificate files) |
| **collection** | `certificate` (certificate files), `attachment` (asset-level attachments) |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `asset/{asset_id}/certificates/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx` |
| **Max file size** | 10 MB per file |
| **Max files per certificate** | 1 (single certificate file) |

### 10.3 Access Rules

- **Upload**: User must have `asset.certificates.create` or `asset.certificates.update`.
- **Download**: User must have `asset.certificates.view` and be within data scope.
- **Delete**: User must have `asset.certificates.update` AND asset status must NOT be `decommissioned`.

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Aset** | Count all assets in scope | Number + icon |
| **Safety-Critical Assets** | Count where `safety_critical = true` | Number, red badge |
| **Sertifikat Expired** | Count certificates where status = `expired` | Number, red badge |
| **Sertifikat Expiring Soon** | Count certificates where status in (`expiring_soon`, `expiring_critical`) | Number, yellow badge |
| **Inspeksi Overdue** | Count assets where `next_inspection_date < now()` and no recent inspection | Number, orange badge |
| **Aset Aktif** | Count where status = `active` | Number, green |
| **Aset Decommissioned** | Count where status = `decommissioned` | Number, gray |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Aset per Kategori** | Donut | Equipment / Machinery / Vehicle / Safety / Fire / Lifting / Other |
| **Sertifikat per Status** | Donut | Valid / Expiring Soon / Expiring Critical / Expired |
| **Inspeksi per Result** | Stacked bar | Pass / Fail / Maintenance Required |
| **Aset per Site** | Horizontal bar | Count by site (top 10) |
| **Trend Inspeksi** | Line chart | Inspection count by month (last 12 months) |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Sertifikat Kedaluwarsa** | Asset Number, Name, Certificate Type, Expiry Date, Status | status = expired or expiring_critical |
| **Inspeksi Overdue** | Asset Number, Name, Next Inspection Date, Safety Critical | next_inspection_date < now() |
| **Safety-Critical Assets** | Asset Number, Name, Category, Status, Certificate Status | safety_critical = true |

### 11.4 Filters

- Site filter
- Category filter
- Safety-critical filter
- Status filter

---

## 12. Export Spec

### 12.1 CSV Export

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `assets_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `asset.management.export` |
| **Scope** | Follows user's data scope |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor Aset` | `asset.asset_number` | AST-YYYY-NNNN |
| 2 | `Nama` | `asset.name` | |
| 3 | `Kategori` | `asset.category` | equipment/machinery/vehicle/safety_equipment/fire_equipment/lifting/other |
| 4 | `Serial Number` | `asset.serial_number` | Nullable |
| 5 | `Model` | `asset.model` | Nullable |
| 6 | `Manufacturer` | `asset.manufacturer` | Nullable |
| 7 | `Site` | `site.name` | Via `site_id` |
| 8 | `Area` | `area.name` | Via `area_id`, nullable |
| 9 | `Department` | `department.name` | Via `department_id`, nullable |
| 10 | `Tanggal Pembelian` | `asset.purchase_date` | YYYY-MM-DD, nullable |
| 11 | `Tanggal Instalasi` | `asset.installation_date` | YYYY-MM-DD, nullable |
| 12 | `Masa Garansi` | `asset.warranty_expiry` | YYYY-MM-DD, nullable |
| 13 | `Status` | `asset.status` | active/inactive/decommissioned |
| 14 | `Safety Critical` | `asset.safety_critical` | Yes/No |
| 15 | `Total Sertifikat` | Count of certificates | Integer |
| 16 | `Sertifikat Expired` | Count certificates where status=expired | Integer |
| 17 | `Sertifikat Expiring` | Count certificates where status in expiring_soon/expiring_critical | Integer |
| 18 | `Inspeksi Terakhir` | Latest inspection_date | YYYY-MM-DD, nullable |
| 19 | `Inspeksi Berikutnya` | `next_inspection_date` of latest inspection | YYYY-MM-DD, nullable |
| 20 | `Created At` | `asset.created_at` | YYYY-MM-DD H:i:s |

---

## 13. Acceptance Criteria

1. User dengan permission `asset.management.create` dapat membuat asset baru dengan nomor auto-generated `AST-YYYY-NNNN`.
2. User tanpa permission `asset.management.create` mendapat 403 saat mencoba membuat asset.
3. List page menampilkan asset dengan search, filter, pagination, dan highlight safety-critical.
4. Sertifikat dengan `expiry_date < now()` otomali berstatus `expired`.
5. Sertifikat dengan `expiry_date` dalam 30 hari menampilkan warning YELLOW.
6. Sertifikat dengan `expiry_date` dalam 7 hari menampilkan warning RED.
7. Halaman Show menampilkan 3 tab: Overview, Certificates, Inspections.
8. Inspeksi dengan result `fail` dapat dihubungkan ke CAPA.
9. Audit trail tercatat untuk semua perubahan kritikal.
10. Notifikasi terkirim saat sertifikat kedaluwarsa dan inspeksi jatuh tempo.
11. Export CSV menghasilkan data sesuai filter dan permission scope.
12. Aset dengan status `decommissioned` tidak dapat diedit.

---

## 14. Open Questions

1. Apakah perlu QR code untuk setiap asset untuk scanning cepat?
2. Apakah perlu integrasi dengan sistem maintenance/CMMS yang sudah ada?
3. Berapa default SLA untuk reminder sertifikat kedaluwarsa (30/60/90 hari)?
4. Apakah contractor perlu melihat asset tertentu yang mereka operasikan?
5. Apakah perlu upload foto aset untuk identifikasi visual?
6. Apakah perlu field untuk lokasi fisik (GPS coordinates) aset?
7. Apakah perlu auto-generate inspection schedule dari asset registration?
8. Field mandatory final per perusahaan/site.
9. Apakah perlu multi-file upload untuk certificate (multiple revisions)?
10. Apakah perlu tracking biaya maintenance/inspeksi per aset?

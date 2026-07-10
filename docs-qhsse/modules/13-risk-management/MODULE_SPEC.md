# Module Spec — Risk Management (HIRADC/JSA)

> **Module ID:** `13-risk-management`  
> **Module Code (numbering):** `risk`  
> **Number Prefix:** `RSK`  
> **Phase:** Phase 2  
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul Risk Management menyediakan sistem identifikasi bahaya, penilaian risiko, dan penentuan kontrol (HIRADC/JSA/Risk Assessment). Modul ini mengakomodasi empat tipe penilaian: Hazard Identification, Job Safety Analysis (JSA), HIRADC (Hazard Identification Risk Assessment Determining Control), dan Risk Assessment umum.

Tujuan utama:

- Memungkinkan **QHSSE Officer/Manager** mengidentifikasi bahaya dan menilai risiko pada aktivitas kerja di setiap site, area, atau department.
- Memastikan setiap risiko terdaftar memiliki **nomor unik** (`RSK-YYYY-NNNN`) yang di-generate otomatis pada saat create.
- Menyediakan **risk matrix** berbasis tabel `risk_matrix_levels` — severity × probability = risk level — dengan visualisasi warna RED/ORANGE/YELLOW/GREEN.
- Membedakan **initial risk** (sebelum kontrol) dan **residual risk** (setelah kontrol tambahan).
- Mengelola **status sederhana** tanpa workflow engine: identified → assessed → controls_needed → controls_in_place → monitored → obsolete.
- Menyediakan **audit trail** lengkap dan **export CSV** untuk analisis dan pelaporan.
- Menghubungkan ke modul **CAPA** (jika kontrol tambahan memerlukan action item), **Incident** (jika risiko terealisasi), dan **PTW** (jika aktivitas memerlukan permit).

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 5 permission keys `risk.registers.*` |
| **NumberingService** | Generate `RSK-YYYY-NNNN` on create |
| **FileService** | Upload/download attachment files via `managed_files` table |
| **NotificationService** | In-app + email notifications |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='risk'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **ExportService** | CSV export via `risk.registers.export` permission |
| **ListQuery** | Paginated, searchable, sortable list |
| **MasterData** | Sites, Areas, Departments, Users, Risk Matrix Levels |

### Cross-Module

| Module | Relationship |
|---|---|
| `02-incident-reporting` | Risk register dapat di-link ke incident jika risiko terealisasi |
| `04-capa-action-tracking` | Additional controls dapat memicu CAPA record |
| `10-permit-to-work` | Risk assessment dapat di-link ke PTW untuk aktivitas berisiko tinggi |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

| # | Role | Deskripsi Peran dalam Risk Management |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Assess, update, export risk register. Scope: all sites. Approve residual risk acceptance. |
| 4 | **QHSSE Officer** | Create, assess, update risk register. Scope: assigned site(s). |
| 5 | **Supervisor** | View risk register di department-nya. Dapat create draft. Scope: department. |
| 6 | **Employee/Reporter** | View risk register yang relevan. Scope: own department. |
| 7 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 8 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Risk Register CRUD

- **Create** — Form pembuatan risk register. Nomor `RSK-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal: `identified`.
- **List** — Halaman list dengan search (nomor, judul, aktivitas), filter (site, area, department, type, status, risk level), pagination (default 15 per page), color coding RED/ORANGE/YELLOW/GREEN pada risk level, dan tombol Export CSV.
- **Detail** — Halaman detail menampilkan: nomor, judul, tipe, lokasi (site/area/department), aktivitas, hazard, existing controls, severity × probability = initial risk level, additional controls, residual severity × residual probability = residual risk level, owner, status, review date, timeline, comments, audit trail.
- **Update** — Edit record. Bisa diedit kapan saja kecuali status `obsolete`.
- **Delete** — Soft delete. Hanya Super Admin / Admin. Tidak bisa delete record yang berstatus `monitored`.

### 4.2 Risk Assessment

- **Initial Risk Assessment** — User memilih severity dan probability, sistem menentukan risk level dari tabel `risk_matrix_levels`.
- **Risk Matrix Grid Selector** — UI berupa grid 4×4 atau 5×5 (tergantung konfigurasi `risk_matrix_levels`) yang menampilkan severity (baris) × probability (kolom), setiap sel berwarna sesuai risk level (RED/ORANGE/YELLOW/GREEN). User klik sel untuk memilih kombinasi.
- **Residual Risk Assessment** — Setelah additional controls diisi, user dapat menilai ulang severity dan probability untuk mendapat residual risk level. Perbandingan before/after ditampilkan di halaman detail.

### 4.3 Status Management (No Workflow Engine)

Status berubah melalui controller action langsung (tanpa `WorkflowService`):

| Action | From | To | Keterangan |
|---|---|---|---|
| `assess` | `identified` | `assessed` | Risk assessment selesai (severity + probability + risk level terisi) |
| `needs_controls` | `assessed` | `controls_needed` | Risk level tinggi, perlu additional controls |
| `implement_controls` | `controls_needed` | `controls_in_place` | Additional controls sudah diimplementasi |
| `monitor` | `controls_in_place` | `monitored` | Risiko dipantau secara berkala |
| `obsolete` | any | `obsolete` | Risiko tidak lagi relevan |

### 4.4 File Attachments

- Upload file pendukung (foto hazard, dokumen JSA, procedure) melalui File Service core.
- Collection: `attachments`.
- Multiple files per risk register.
- Download melalui authorized endpoint.

### 4.5 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record.
- Activity log otomatis mencatat: create, update, assess, status change, file upload, file delete, export.

### 4.6 Notification

- 3 event notifikasi: `risk.assessed`, `risk.controls_needed`, `risk.obsolete`.
- In-app notification via `core_notifications` table.

### 4.7 Dashboard & Reporting

- Dashboard widget: total risk register, breakdown by risk level (RED/ORANGE/YELLOW/GREEN), breakdown by type, breakdown by status, trend bulanan.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Tipe Risk Register

Empat tipe penilaian risiko:

| # | Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `hazard_identification` | **Hazard Identification** | Identifikasi bahaya di area/aktivitas kerja tanpa penilaian risiko formal. Fokus pada inventarisasi hazard. |
| 2 | `jsa` | **Job Safety Analysis (JSA)** | Analisis keselamatan pekerjaan tahap demi tahap. Mengidentifikasi hazard per langkah kerja dan menentukan kontrol. |
| 3 | `hiradc` | **HIRADC** | Hazard Identification, Risk Assessment, and Determining Control. Metode komprehensif: identifikasi hazard → assess risk → determine control. |
| 4 | `risk_assessment` | **Risk Assessment** | Penilaian risiko umum menggunakan risk matrix (severity × probability = risk level). |

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor risk register di-generate **saat record dibuat** (POST create).
- Format: `RSK-YYYY-NNNN` (contoh: `RSK-2026-0001`).
- Sumber: `NumberingService::generate('risk', $actor, ...)`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `risk`
  - `prefix`: `RSK`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `RSK-2026-0001`
- Nomor bersifat **unique**. Tidak dapat diubah setelah di-generate.

### BR-02: Risk Level dari risk_matrix_levels

- Risk level ditentukan dari tabel `risk_matrix_levels` berdasarkan kombinasi `severity_level` dan `probability_level`.
- `risk_matrix_levels` berisi mapping: (severity_level, probability_level) → risk_level (RED/ORANGE/YELLOW/GREEN).
- Initial risk: `severity_id` × `probability_id` → lookup `risk_matrix_levels` → `risk_level_id`.
- Residual risk: `residual_severity_id` × `residual_probability_id` → lookup `risk_matrix_levels` → `residual_risk_level_id`.
- Jika severity atau probability belum dipilih, risk level = NULL (status tetap `identified`).

### BR-03: Status Transitions

- Status berubah melalui controller action langsung (POST ke endpoint `/risk-registers/{id}/assess`, dll).
- Tidak menggunakan `WorkflowService` — tidak ada `workflow_instances` atau `workflow_histories` untuk modul ini.
- Status `obsolete` adalah terminal — tidak dapat dikembalikan.
- Setiap status change dicatat di `activity_logs` dan `audit_logs`.
- Validasi: `assess` memerlukan `severity_id`, `probability_id`, dan `risk_level_id` terisi. `implement_controls` memerlukan `additional_controls` terisi.

### BR-04: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='risk'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `risk.created` | RiskRegister record | new_values: all fields |
| `risk.updated` | RiskRegister record | changed fields only |
| `risk.assessed` | RiskRegister record | severity, probability, risk_level change |
| `risk.status_changed` | RiskRegister record | status change |
| `risk.deleted` | RiskRegister record | soft delete |
| `risk.file.uploaded` | ManagedFile | new_values |
| `risk.file.downloaded` | ManagedFile | metadata: user, ip |

### BR-05: Data Visibility by Scope

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter | Risk registers in own department |
| `department` | Supervisor | Risk registers in their department |
| `site` | QHSSE Officer | Risk registers in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All risk registers |

- Scope check dilakukan **server-side** di Controller/Policy.

---

## 7. Permission Keys

5 permission keys untuk modul Risk Management:

| # | Permission Key | Description |
|---|---|---|
| 1 | `risk.registers.view` | View risk register list and detail. Scope-filtered. |
| 2 | `risk.registers.create` | Create new risk register. Generates RSK number. |
| 3 | `risk.registers.update` | Update risk register. Any non-terminal status. |
| 4 | `risk.registers.assess` | Perform risk assessment (set severity/probability, change status). |
| 5 | `risk.registers.export` | Export risk register list to CSV. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}` → `risk.registers.*`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.

---

## 8. Role-Permission Matrix

| Role | `view` | `create` | `update` | `assess` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ✅ | ✅ | ❌ | ✅ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ✅ |

### Notes

- **QHSSE Officer** dan **QHSSE Manager** adalah pengguna utama modul ini — dapat create, assess, update, export.
- **Supervisor** dapat create dan update draft risk register di department-nya, tetapi tidak dapat melakukan assess formal (hanya QHSSE roles).
- **Auditor** dan **Top Management** hanya view + export (read-only).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

3 event notifikasi untuk modul Risk Management:

### 9.1 `risk.assessed`

| Property | Value |
|---|---|
| **Trigger** | User melakukan assess (status `identified` → `assessed`) |
| **Recipients** | QHSSE Manager in the same site scope, Owner (`owner_id`) |
| **Type** | `risk.assessed` |
| **Title (template)** | `Risk Register Dinilai: {risk.register_number}` |
| **Message (template)** | `Risk register {risk.register_number} - {risk.title} telah dinilai. Risk level: {risk_level.name}.` |
| **Action URL** | `/risk-registers/{risk.id}` |
| **Module/Reference** | `module_name='risk'`, `reference_id={risk.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `risk.controls_needed`

| Property | Value |
|---|---|
| **Trigger** | Status berubah ke `controls_needed` (risk level tinggi) |
| **Recipients** | Owner (`owner_id`), Supervisor of department, QHSSE Manager |
| **Type** | `risk.controls_needed` |
| **Title (template)** | `Kontrol Tambahan Diperlukan: {risk.register_number}` |
| **Message (template)** | `Risk register {risk.register_number} - {risk.title} memerlukan kontrol tambahan. Risk level: {risk_level.name}.` |
| **Action URL** | `/risk-registers/{risk.id}` |
| **Module/Reference** | `module_name='risk'`, `reference_id={risk.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.3 `risk.obsolete`

| Property | Value |
|---|---|
| **Trigger** | Status berubah ke `obsolete` |
| **Recipients** | Owner (`owner_id`), QHSSE Manager |
| **Type** | `risk.obsolete` |
| **Title (template)** | `Risk Register Ditetapkan Obsolete: {risk.register_number}` |
| **Message (template)** | `Risk register {risk.register_number} - {risk.title} telah ditetapkan sebagai obsolete.` |
| **Action URL** | `/risk-registers/{risk.id}` |
| **Module/Reference** | `module_name='risk'`, `reference_id={risk.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `risk` |
| **reference_id** | `risk_registers.id` |
| **collection** | `attachments` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `risk/{risk_id}/attachments/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `xls`, `xlsx` |
| **Max file size** | 25 MB per file |
| **Max files per risk register** | 20 |

### 10.3 Access Rules

- **Upload**: User must have `risk.registers.update`.
- **Download**: User must have `risk.registers.view` and be within data scope.
- **Delete**: User must have `risk.registers.update` AND risk register status must NOT be `obsolete`.

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Risk Registers** | Count all in scope | Number + icon |
| **High Risk (RED)** | Count where risk_level = RED AND status NOT IN (`obsolete`) | Number, red badge |
| **Medium Risk (ORANGE/YELLOW)** | Count where risk_level IN (ORANGE, YELLOW) | Number, orange/yellow |
| **Controls Needed** | Count where status = `controls_needed` | Number, orange |
| **Monitored** | Count where status = `monitored` | Number, green |
| **This Month** | Count created in current month | Number + trend arrow |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Risk Level Distribution** | Donut | Count by risk_level (RED/ORANGE/YELLOW/GREEN) |
| **By Type** | Bar chart | Count by type (HIRADC, JSA, etc.) |
| **By Site** | Horizontal bar | Count by site (top 10) |
| **Monthly Trend** | Line chart | Risk register count by month (last 12) |
| **Before vs After Controls** | Grouped bar | Count of initial risk level vs residual risk level |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **High Risk Items** | Number, Title, Activity, Risk Level, Owner, Status | risk_level=RED, status active |
| **Controls Needed** | Number, Title, Activity, Owner, Days Since Identified | status=controls_needed |
| **Aging Report** | Number, Title, Status, Created At, Days Since Created | Sorted by oldest |

---

## 12. Export Spec

### 12.1 CSV Export

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `risk_registers_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `risk.registers.export` |
| **Scope** | Follows user's data scope |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `register_number` | RSK-YYYY-NNNN |
| 2 | `Judul` | `title` | |
| 3 | `Tipe` | `type` | hazard_identification, jsa, hiradc, risk_assessment |
| 4 | `Site` | `site.name` | Via `site_id` |
| 5 | `Area` | `area.name` | Via `area_id`, nullable |
| 6 | `Department` | `department.name` | Via `department_id`, nullable |
| 7 | `Aktivitas` | `activity` | |
| 8 | `Hazard` | `hazard` | |
| 9 | `Existing Controls` | `existing_controls` | |
| 10 | `Initial Severity` | `severity.name` | Via `severity_id` |
| 11 | `Initial Probability` | `probability.name` | Via `probability_id` |
| 12 | `Initial Risk Level` | `riskLevel.name` | Via `risk_level_id` → risk_matrix_levels |
| 13 | `Additional Controls` | `additional_controls` | |
| 14 | `Residual Severity` | `residualSeverity.name` | Via `residual_severity_id` |
| 15 | `Residual Probability` | `residualProbability.name` | Via `residual_probability_id` |
| 16 | `Residual Risk Level` | `residualRiskLevel.name` | Via `residual_risk_level_id` |
| 17 | `Owner` | `owner.name` | Via `owner_id` → users |
| 18 | `Status` | `status` | |
| 19 | `Review Date` | `review_date` | YYYY-MM-DD |
| 20 | `Created At` | `created_at` | YYYY-MM-DD HH:MM:SS |

### 12.3 Export Rules

- Export event dicatat di audit trail (`risk.exported`).
- Export mengikuti permission dan scope user.
- Maksimal 10.000 record per export.

---

## 13. Acceptance Criteria

1. **AC-01: Create with auto-numbering** — User dengan permission `risk.registers.create` dapat membuat risk register. Nomor `RSK-YYYY-NNNN` di-generate otomatis pada saat create. Nomor bersifat unique.

2. **AC-02: Permission enforcement** — User tanpa permission `risk.registers.view` tidak dapat mengakses halaman list atau detail. Server-side check memblokir akses.

3. **AC-03: Risk matrix calculation** — Sistem menentukan risk level dari tabel `risk_matrix_levels` berdasarkan severity × probability. Risk level ditampilkan dengan color coding RED/ORANGE/YELLOW/GREEN.

4. **AC-04: Initial vs residual risk** — Halaman detail menampilkan perbandingan initial risk (sebelum kontrol) dan residual risk (setelah kontrol). Jika residual belum dinilai, hanya initial yang ditampilkan.

5. **AC-05: Status transitions correct** — Status berubah sesuai: identified→assessed(assess), assessed→controls_needed(needs_controls), controls_needed→controls_in_place(implement_controls), controls_in_place→monitored(monitor), any→obsolete. Transition tidak valid ditolak.

6. **AC-06: Assess requires risk fields** — Action `assess` memerlukan `severity_id`, `probability_id`, dan `risk_level_id` terisi. Jika kosong, assess gagal.

7. **AC-07: Risk matrix grid selector** — Form menampilkan grid interaktif severity × probability. User dapat klik sel untuk memilih kombinasi. Selected cell disorot.

8. **AC-08: List with color coding** — Halaman list menampilkan risk level badge dengan warna RED/ORANGE/YELLOW/GREEN. Filter dan search berfungsi.

9. **AC-09: Export CSV** — Export menghasilkan CSV dengan 20 kolom sesuai spec. Data sesuai filter dan scope.

10. **AC-10: Audit trail complete** — Audit trail tercatat untuk: create, update, assess, status change, delete, file upload, file download, export.

---

## 14. Open Questions

| # | Question | Default Answer (if not decided) |
|---|---|---|
| 1 | Apakah risk matrix menggunakan 4×4 atau 5×5 grid? | **Mengikuti data di `risk_matrix_levels`** — grid dinamis berdasarkan jumlah severity_level dan probability_level yang ada di tabel. |
| 2 | Apakah severity dan probability menggunakan tabel `severities` yang sudah ada atau tabel terpisah? | **`severities`** untuk severity. Probability menggunakan `risk_matrix_levels.probability_level` sebagai referensi integer (1-5). Pilihan probability diambil dari distinct `probability_level` di `risk_matrix_levels`. |
| 3 | Apakah risk register dapat di-link ke CAPA? | **Yes** — 1 risk register dapat memicu CAPA records via CAPA's `source_module='risk'` dan `source_reference_id=risk_register.id`. |
| 4 | Apakah ada review schedule otomatis? | **No untuk Phase 1** — `review_date` diisi manual. Cron job untuk reminder review dapat ditambahkan di Phase 2. |
| 5 | Apakah risk register mendukung multi-hazard per aktivitas? | **No untuk Phase 1** — 1 risk register = 1 aktivitas + 1 hazard. Untuk multi-hazard, buat multiple risk registers dengan aktivitas yang sama. |
| 6 | Apakah ada template JSA/HIRADC? | **No untuk Phase 1** — dapat ditambahkan di Phase 2 sebagai `risk_templates` table. |
| 7 | Apakah notifikasi email aktif di Phase 1? | **Optional** — In-app notification wajib. Email jika SMTP configured. |
| 8 | Apakah risk register dapat di-reopen setelah obsolete? | **No** — `obsolete` adalah terminal status. Jika risiko muncul kembali, buat risk register baru. |
